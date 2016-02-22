<?php
namespace Planxty\Block;

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
        $parser = $this->container['block_parser'];

        $blocks = collect([]);

        // Make sure we have specified a blocks directory
        if ($config->has('paths.blocks')) {
            $finder->files()->in($config->get('paths.blocks'))->name('*.yml');

            foreach ($finder as $file) {
                $blocks->put($file->getBasename('.yml'), $parser->parse($file));
            }
        }

        return $blocks;
    }
}