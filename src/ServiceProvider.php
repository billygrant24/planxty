<?php
namespace Phabric;

use Parsedown;
use Phabric\Collection\Block\Repository as BlockRepository;
use Phabric\Collection\Content\Parser;
use Phabric\Collection\Content\Repository;
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
        $pimple['config'] = function ($c) {

            $configFile = 'null: set';

            if ($c['fs']->exists(getcwd() . '/config.yml')) {
                $configFile = str_replace(
                    '%base_path%',
                    getcwd(),
                    file_get_contents(getcwd() . '/config.yml')
                );
            }

            return new Config(Yaml::parse($configFile));
        };

        $pimple['content'] = $pimple->factory(function ($c) {
            $repository = new Repository();

            $repository->setConfig($c['config']);
            $repository->setFinder($c['finder']);
            $repository->setParser($c['parser']);

            return $repository->collect();
        });

        $pimple['blocks'] = $pimple->factory(function ($c) {
            $repository = new BlockRepository();

            $repository->setFinder($c['finder']);

            $repository->setConfig($c['config']);
            $repository->setMarkdown($c['markdown']);
            $repository->setTwig($c['twig']);
            $repository->setYaml($c['yaml']);

            return $repository->collect();
        });

        $pimple['fs'] = $pimple->factory(function ($c) {
            return new Filesystem();
        });

        $pimple['markdown'] = function () {
            return new Parsedown();
        };

        $pimple['yaml'] = function () {
            return new YamlParser();
        };

        $pimple['parser'] = function ($c) {
            $parser = new Parser();

            $parser->setConfig($c['config']);
            $parser->setMarkdown($c['markdown']);
            $parser->setTwig($c['twig']);
            $parser->setYaml($c['yaml']);

            return $parser;
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