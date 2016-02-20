<?php
namespace Planxty\Content;

use Pimple\Container;

class Repository
{
    /**
     * @param \Pimple\Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public function collect()
    {
        $config = $this->container['config'];
        $finder = $this->container['finder'];
        $parser = $this->container['parser'];

        $finder->files()->in($config->get('paths.content'))->name('*.yml');

        $content = collect([]);
        foreach ($finder as $file) {
            $content->push($parser->parse($file));
        }

        return $content->sortByDesc('date');
    }
}