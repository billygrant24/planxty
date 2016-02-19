<?php
namespace Planxty\Tasks;

use Illuminate\Support\Collection;
use Planxty\ContainerFactory;
use Robo\Contract\TaskInterface;
use Robo\Result;
use Suin\RSSWriter\Channel;
use Suin\RSSWriter\Feed;
use Suin\RSSWriter\Item;

class BuildRssTask implements TaskInterface
{
    /**
     * @var \Pimple\Container
     */
    protected $container;

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $content;

    /**
     * @var string
     */
    protected $target;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->container = ContainerFactory::getStaticInstance();
        $this->name = $name;
    }

    /**
     * @param \Illuminate\Support\Collection $content
     *
     * @return $this
     */
    public function with(Collection $content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @param string $target
     *
     * @return $this
     */
    public function target($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @return \Robo\Result
     */
    public function run()
    {
        $config = $this->container['config'];
        $fs = $this->container['fs'];

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
        foreach ($this->content as $page) {
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
            implode('/', [rtrim($this->target, '/'), trim($this->name, '/')]),
            $feed
        );

        return Result::success($this, 'Added RSS feed');
    }
}