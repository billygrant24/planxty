<?php
namespace Phabric;

use Phabric\Tasks\RoboTasks;
use Robo\Result;
use SitemapPHP\Sitemap;
use Suin\RSSWriter\Channel;
use Suin\RSSWriter\Feed;
use Suin\RSSWriter\Item;

trait Phabric
{
    use RoboTasks;

    public function play()
    {
        $c = $this->getContainer();
        $config = $c['config'];

        $this
            ->taskServer(3000)
            ->dir($config->get('paths.build'))
            ->run();
    }

    public function listen()
    {
        $c = $this->getContainer();
        $config = $c['config'];

        $this
            ->taskWatch()
            ->monitor([
                $config->get('paths.assets'),
                $config->get('paths.content'),
                $config->get('paths.layouts'),
            ], function () use ($config) {
                $this->say('Changes detected');
                $this->compose(null);
            })
            ->run();
    }

    public function clean()
    {
        $c = $this->getContainer();
        $config = $c['config'];
        $fs = $c['fs'];

        if ($fs->exists($config->get('paths.build'))) {
            $this->taskCleanDir($config->get('paths.build'))->run();
        } else {
            $fs->mkdir($config->get('paths.build'));
            $this->say('Build dir does not exist. Initialised one at: ' . $config->get('paths.build'));
        }
    }

    public function composeHtml()
    {
        $c = $this->getContainer();
        $config = $c['config'];
        $content = $c['content'];
        $fs = $c['fs'];
        $twig = $c['twig'];

        foreach ($content as $page) {
            $twigData = array_merge([
                'blocks' => $c['blocks'],
                'categories' => $content->pluck('category')->unique()->filter(),
                'config' => $config,
                'content' => $content,
                'tags' => $content->pluck('tags')->flatten()->values()->unique()->filter(),
            ], compact('page'));

            if ( ! $page->has('pagination')) {
                $fs->dumpFile(
                    rtrim($config->get('paths.build'), '/') . $page->get('uri'),
                    $twig->render($page->get('layout'), $twigData)
                );
            } else {
                $pagination = collect($page->get('pagination'));

                $type = $pagination->get('from');
                $size = $pagination->get('size', null);

                $scopedContent = $content->where('type', $type);
                $contentCount = $scopedContent->count();
                $pageCount = ceil($contentCount / ($size ? $size : 1));

                $getPagedPath = function ($pageNumber) use ($page) {
                    $uri = $page->get('uri');

                    return $pageNumber > 1 ? str_replace('.html', '-' . $pageNumber . '.html', $uri) : $uri;
                };

                for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                    $page->put('uri', $getPagedPath($pageNumber));

                    $pagination->put('items', $scopedContent->forPage($pageNumber, $size));
                    $pagination->put('current', $pageNumber);
                    $pagination->put('current_uri', $getPagedPath($pageNumber));
                    $pagination->put('next', $pageNumber < $pageCount ? $pageNumber + 1 : null);
                    $pagination->put('next_uri', $pageNumber < $pageCount ? $getPagedPath($pageNumber + 1) : null);
                    $pagination->put('previous', $pageNumber > 1 ? $pageNumber - 1 : null);
                    $pagination->put('previous_uri', $pageNumber > 1 ? $getPagedPath($pageNumber - 1) : null);
                    $pagination->put('first', 1);
                    $pagination->put('first_uri', $page->get('uri'));
                    $pagination->put('last', $pageCount);
                    $pagination->put('last_uri', $getPagedPath($pageCount));
                    $pagination->put('total', $contentCount);

                    $twigData['pagination'] = $pagination;

                    $fs->dumpFile(
                        rtrim($config->get('paths.build'), '/') . $page->get('uri'),
                        $twig->render($page->get('layout'), $twigData)
                    );
                }
            }
        }
    }

    public function composeAssets()
    {
        $c = $this->getContainer();
        $config = $c['config'];
        $finder = $c['finder'];
        $fs = $c['fs'];

        $finder->files()->in($config->get('paths.assets'));

        // Copy our assets over to the build directory
        foreach ($finder as $file) {
            $fs->copy(
                $file->getPathName(),
                implode('/', [
                    rtrim($config->get('paths.build'), '/'),
                    trim($file->getRelativePathname(), '/'),
                ])
            );
        }
    }

    public function composeApi()
    {
        $c = $this->getContainer();
        $config = $c['config'];
        $content = $c['content'];
        $fs = $c['fs'];

        $json = collect([
            'content' => $content->toArray(),
            'categories' => $content->pluck('category')->unique()->filter(),
            'tags' => $content->pluck('tags')->flatten()->values()->unique()->filter(),
        ])->toJson();

        $fs->dumpFile(
            implode('/', [
                rtrim($config->get('paths.build'), '/'),
                trim($config->get('api.filename') . '.json', '/'),
            ]),
            $json
        );
    }

    public function composeRss()
    {
        $c = $this->getContainer();
        $config = $c['config'];
        $content = $c['content'];
        $fs = $c['fs'];

        // Initialise an RSS feed
        $feed = new Feed();
        $channel = new Channel();

        $channel
            ->title($config->get('title'))
            ->description($config->get('description'))
            ->url($config->get('url'))
            ->language('en-GB')
            ->copyright('Copyright 2012, Foo Bar')
            ->pubDate(strtotime('Tue, 21 Aug 2012 19:50:37 +0900'))
            ->lastBuildDate(strtotime('Tue, 21 Aug 2012 19:50:37 +0900'))
            ->ttl(60)
            ->appendTo($feed);


        // Add pages to RSS
        foreach ($content as $page) {
            $item = new Item();
            $item
                ->title($page->get('title'))
                ->description("<div>Blog body</div>")
                ->url($page->get('uri'))
                ->pubDate(strtotime('Tue, 21 Aug 2012 19:50:37 +0900'))
                ->guid($page->get('uri'), true)
                ->appendTo($channel);
        }

        // Write out the RSS feed
        $fs->dumpFile(
            implode('/', [
                rtrim($config->get('paths.build'), '/'),
                trim($config->get('rss.filename') . '.xml', '/'),
            ]),
            $feed
        );
    }

    public function composeSitemap()
    {
        $c = $this->getContainer();
        $config = $c['config'];
        $content = $c['content'];

        // Initialise the sitemap
        $sitemap = new Sitemap($config->get('url'));
        $sitemap
            ->setPath(rtrim($config->get('paths.build'), '/') . '/')
            ->setFilename($config->get('sitemap.filename'));

        // Add each page
        foreach ($content as $page) {
            $sitemap->addItem($page->get('uri'));
        }

        // Finalise the generated sitemap
        $sitemap->createSitemapIndex($config->get('url') . '/', 'Today');
    }

    protected function getContainer()
    {
        return ContainerFactory::newInstance();
    }

    protected function stopOnFail($stopOnFail = true)
    {
        Result::$stopOnFail = $stopOnFail;
    }
}