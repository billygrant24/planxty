<?php
namespace Planxty;

use Parsedown;
use Pimple\Container;
use Planxty\Block\Parser as BlockParser;
use Planxty\Block\Repository as BlockRepository;
use Planxty\Content\Parser;
use Planxty\Content\Repository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser as YamlParser;
use Symfony\Component\Yaml\Yaml;
use Twig_Environment;
use Twig_Extension_StringLoader;
use Twig_Loader_Filesystem;

final class ContainerFactory
{
    public static function newInstance()
    {
        $container = new Container();

        $container['config'] = function () {
            $configFile = str_replace(
                '%base_path%',
                getcwd(),
                file_get_contents(getcwd() . '/config.yml')
            );

            return new Config(Yaml::parse($configFile));
        };

        $container['content'] = $container->factory(function ($c) {
            return new Repository($c);
        });

        $container['blocks'] = $container->factory(function ($c) {
            return new BlockRepository($c);
        });

        $container['fs'] = $container->factory(function ($c) {
            return new Filesystem();
        });

        $container['markdown'] = function () {
            return new Parsedown();
        };

        $container['yaml'] = function () {
            return new YamlParser();
        };

        $container['parser'] = function ($c) {
            $parser = new Parser();

            $parser->setConfig($c['config']);
            $parser->setMarkdown($c['markdown']);
            $parser->setTwig($c['twig']);
            $parser->setYaml($c['yaml']);

            return $parser;
        };

        $container['block_parser'] = function ($c) {
            $parser = new BlockParser();

            $parser->setConfig($c['config']);
            $parser->setMarkdown($c['markdown']);
            $parser->setTwig($c['twig']);
            $parser->setYaml($c['yaml']);

            return $parser;
        };

        $container['twig'] = function ($c) {
            $twig = new Twig_Environment(new Twig_Loader_Filesystem($c['config']->get('paths.layouts')), [
                'cache' => false,
                'debug' => false,
                'autoreload' => true,
            ]);

            // Add the content path to Twig loader (used in parsing of meta data)
            $twig->getLoader()->addPath($c['config']->get('paths.content'));

            // Add Twig extensions
            $twig->addExtension(new Twig_Extension_StringLoader());

            return $twig;
        };

        $container['finder'] = $container->factory(function ($c) {
            return new Finder();
        });

        return $container;
    }
}