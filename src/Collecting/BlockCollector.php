<?php
namespace Phabric\Collecting;

use Illuminate\Support\Collection;
use Phabric\Parsing\Parser;
use Pimple\Container;

final class BlockCollector
{
    use Parser;

    public function __construct(Container $c)
    {
        $this->config = $c['config'];
        $this->finder = $c['finder'];
        $this->markdown = $c['markdown'];
        $this->twig = $c['twig'];
        $this->yaml = $c['yaml'];
    }

    public function collect()
    {
        $path = $this->config->get('paths.blocks');

        $this->finder->files()->in($path)->name('*.yml');

        $items = [];
        foreach ($this->finder as $file) {
            $items[] = $this->parse($file);
        }

        return new Collection($items);
    }
}