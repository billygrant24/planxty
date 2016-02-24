<?php
namespace Phabric\Configuration;

use Parsedown;
use Phabric\Block\BlockRepository;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser as YamlParser;
use Symfony\Component\Yaml\Yaml;
use Twig_Environment;
use Twig_Extension_StringLoader;
use Twig_Loader_Filesystem;

final class ServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $pimple)
    {
        $pimple['config'] = function () {
            $path = getcwd() . '/config.yml';

            if ( ! file_exists($path)) {
                return new ConfigRepository([]);
            }

            $file = str_replace(':root', getcwd(), file_get_contents($path));

            return new ConfigRepository(Yaml::parse($file));
        };

        $pimple['fs'] = $pimple->factory(function ($c) {
            return new Filesystem();
        });

        $pimple['markdown'] = function () {
            return new Parsedown();
        };

        $pimple['yaml'] = function () {
            return new YamlParser();
        };

        $pimple['twig'] = function ($c) {
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

        $pimple['finder'] = $pimple->factory(function ($c) {
            return new Finder();
        });
    }
}