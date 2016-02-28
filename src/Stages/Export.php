<?php
namespace Phabric\Stages;

use Illuminate\Support\Collection;
use SitemapPHP\Sitemap;
use Suin\RSSWriter\Channel;
use Suin\RSSWriter\Feed;
use Suin\RSSWriter\Item;

final class Export
{
    private $config;
    private $storage;
    private $template;

    public function __construct($config, $storage, $template)
    {
        $this->config = $config;
        $this->storage = $storage;
        $this->template = $template;
    }

    public function __invoke($payload)
    {
        $this->exportSitemap($payload);
        $this->exportRssFeed($payload);
        $this->exportApi($payload);

        return $payload->each(function ($item) {
            $this->storage->dumpFile(
                $this->config['paths.output'] . '/' . trim($item['permalink'], '/'),
                $this->template->render($item['layout'], $item)
            );
        });
    }

    private function exportApi($payload)
    {
        $item = collect($payload->first());
        $content = new Collection([
            'content' => $item->except('app'),
            'taxonomies' => [
                'categories' => $item['app']['taxonomies']['categories'],
                'tags' => $item['app']['taxonomies']['tags'],
            ],
        ]);

        $this->storage->dumpFile(
            rtrim($this->config['paths.output'], '/') . '/index.json',
            $content->toJson()
        );
    }

    private function exportRssFeed($payload)
    {
        // Initialise an RSS feed
        $feed = new Feed();
        $channel = new Channel();
        $channel
            ->title($this->config['title'])
            ->description($this->config['description'])
            ->url($this->config['url'])
            ->language('en-GB')
            ->pubDate(time())
            ->lastBuildDate(time())
            ->ttl(60)
            ->appendTo($feed);

        // Add pages to RSS
        $payload->unique('canonical_url')->each(function ($page) use ($channel) {
            $item = new Item();
            $item
                ->title($page['title'])
                ->description($page['body'])
                ->url($page['canonical_url'])
                ->pubDate($page['date'])
                ->guid($page['canonical_url'], true)
                ->appendTo($channel);
        });

        // Write out the RSS feed
        $this->storage->dumpFile(
            rtrim($this->config['paths.output'], '/') . '/rss.xml',
            $feed
        );
    }

    private function exportSitemap($payload)
    {
        // Initialise the sitemap
        $sitemap = new Sitemap($this->config['url']);
        $sitemap
            ->setPath(rtrim($this->config['paths.output'], '/') . '/')
            ->setFilename('sitemap');

        // Add each page
        $payload->unique('canonical_url')->each(function ($page) use ($sitemap) {
            $sitemap->addItem($page['canonical_url']);
        });

        // Finalise the generated sitemap
        $sitemap->createSitemapIndex($this->config['url'] . '/', 'Today');
    }
}