<?php
namespace Phabric;

use Dotenv\Dotenv;
use League\Pipeline\Pipeline;
use League\Plates\Engine;
use Mni\FrontYAML\Parser;
use Phabric\Pipelines\BuildPipeline;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

final class ServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container['config'] = function ($c) {
            return new Config(
                array_replace_recursive(
                    $this->getDefaultConfig(),
                    include getcwd() . '/_config.php'
                )
            );
        };

        $container['env'] = function () {
            return new Dotenv(getcwd(), '.env');
        };

        $container['finder'] = $container->factory(function ($c) {
            $finder = new Finder();
            $finder->files()->in(getcwd())->ignoreVCS(true);

            foreach ($c['config']['exclude.files'] as $path) {
                $finder->notName($path);
            }

            foreach ($c['config']['exclude.folders'] as $path) {
                $finder->exclude($path);
            }

            return $finder;
        });

        $container['parser'] = function () {
            return new Parser(null, null, '```', '```');
        };

        $container['pipeline'] = $container->factory(function ($c) {
            $build = new BuildPipeline();
            $build->setContainer($c);

            return (new Pipeline())->pipe($build);
        });

        $container['storage'] = function () {
            return new Filesystem();
        };

        $container['template'] = function ($c) {
            return new Engine($c['config']['paths.layouts'], 'phtml');
        };
    }

    private function getDefaultConfig()
    {
        return [
            'url' => '',
            'title' => 'My Site',
            'scopes' => [
                'default' => [
                    'path' => '',
                    'permalink' => ':pathname',
                    'layout' => 'page',
                ],
            ],
            'paths' => [
                'content' => rtrim(getcwd(), '/') . '/_content',
                'layouts' => rtrim(getcwd(), '/') . '/_layouts',
                'output' => rtrim(getcwd(), '/') . '/_site',
            ],
            'taxonomies' => [
                'categories' => [
                    'name' => 'category',
                    'type' => 'category',
                ],
                'tags' => [
                    'name' => 'tags',
                    'type' => 'tag',
                ],
            ],
            'providers' => [],
            'pipelines' => [
                'setup' => [],
                'parse' => [],
                'transform' => [],
                'export' => [],
            ],
            'exclude' => [
                'files' => [
                    '.env',
                    '_config.php',
                    'composer.json',
                    'composer.lock',
                    'LICENSE.md',
                    'README.md',
                ],
                'folders' => [
                    '_assets',
                    '_content',
                    '_layouts',
                    '_site',
                    '_src',
                    'node_modules',
                    'vendor',
                ],
            ],
        ];
    }
}