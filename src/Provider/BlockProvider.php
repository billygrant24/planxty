<?php
namespace Phabric\Provider;

use Illuminate\Support\Collection;
use Phabric\Parsing\ContentParser;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class BlockProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $c)
    {
        $c['block_parser'] = function ($c) {
            $parser = new ContentParser();

            $parser->setConfig($c['config']);
            $parser->setMarkdown($c['markdown']);
            $parser->setTwig($c['twig']);
            $parser->setYaml($c['yaml']);

            return $parser;
        };

        $c['blocks'] = function ($c) {
            $path = $c['config']->get('paths.blocks');

            $finder = $c['finder'];
            $finder->files()->in($path)->name('*.yml');

            $items = [];
            foreach ($finder as $file) {
                $items[$file->getBasename('.yml')] = $c['block_parser']->parse($file);
            }

            $blocks = new Collection($items);

            return $blocks;
        };
    }
}