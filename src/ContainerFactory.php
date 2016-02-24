<?php
namespace Phabric;

use Phabric\Collecting\BlockCollector;
use Phabric\Collecting\ContentCollector;
use Phabric\Collecting\TaxonomyCollector;
use Parsedown;
use Pimple\Container;
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
            $path = getcwd() . '/config.yml';

            if ( ! file_exists($path)) {
                return new Config([]);
            }

            $file = str_replace(':root', getcwd(), file_get_contents($path));

            return new Config(Yaml::parse($file));
        };

        $container['fs'] = $container->factory(function ($c) {
            return new Filesystem();
        });

        $container['markdown'] = function () {
            return new Parsedown();
        };

        $container['yaml'] = function () {
            return new YamlParser();
        };

        $container['twig'] = function ($c) {
            $config = $c['config'];

            $twig = new Twig_Environment(new Twig_Loader_Filesystem($config->get('paths.layouts')), [
                'cache' => false,
                'debug' => false,
                'autoreload' => true,
            ]);

            // Add the content path to Twig loader (used in parsing of meta data)
            $twig->getLoader()->addPath($config->get('paths.content'));

            // Add Twig extensions
            $twig->addExtension(new Twig_Extension_StringLoader());

            return $twig;
        };

        $container['finder'] = $container->factory(function ($c) {
            return new Finder();
        });

        $container['block_collector'] = function ($c) {
            $blocks = new BlockCollector($c);

            return $blocks->collect();
        };

        $container['taxonomy_collector'] = function ($c) {
            $taxonomies = new TaxonomyCollector($c);

            return $taxonomies->collect();
        };

        $container['content_collector'] = function ($c) {
            $content = new ContentCollector($c);

            return $content->collect();
        };

        return $container;
    }
}