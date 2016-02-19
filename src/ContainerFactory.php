<?php
namespace Planxty;

use Mni\FrontYAML\Bridge\Parsedown\ParsedownParser;
use Mni\FrontYAML\Bridge\Symfony\SymfonyYAMLParser;
use Pimple\Container;
use Planxty\Twig\AssetExtension;
use Planxty\Twig\IlluminateStringExtension;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Twig_Environment;
use Twig_Extension_StringLoader;
use Twig_Loader_Filesystem;

final class ContainerFactory
{
    public static function getStaticInstance()
    {
        $container = new Container();

        $container['config'] = function () {
            $configFile = str_replace('%base_path%', getcwd(), file_get_contents(getcwd() . '/config.yml'));

            return collect(array_dot(Yaml::parse($configFile)));
        };

        $container['content'] = $container->factory(function ($c) {
            return new ContentRepository($c);
        });

        $container['fs'] = $container->factory(function ($c) {
            return new Filesystem();
        });

        $container['markdown'] = function () {
            return new ParsedownParser();
        };

        $container['yaml'] = function () {
            return new SymfonyYAMLParser();
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
            $twig->addExtension(new AssetExtension());
            $twig->addExtension(new IlluminateStringExtension());
            $twig->addExtension(new Twig_Extension_StringLoader());

            return $twig;
        };

        $container['finder'] = $container->factory(function ($c) {
            return new Finder();
        });

        return $container;
    }
}