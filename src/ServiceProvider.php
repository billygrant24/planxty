<?php
namespace Phabric;

use League\Pipeline\Pipeline;
use League\Plates\Engine;
use Mni\FrontYAML\Parser;
use Phabric\Pipelines\SimpleBuild;
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
            $builder = new SimpleBuild();
            $builder->setContainer($c);

            return (new Pipeline())->pipe($builder);
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
            'providers' => [
                //
            ],
            'exclude' => [
                'files' => [
                    '.env',
                    '_bootstrap.php',
                    '_config.php',
                    'composer.json',
                    'composer.lock',
                    'LICENSE.md',
                    'README.md',
                ],
                'folders' => [
                    '_content',
                    '_layouts',
                    '_sass',
                    '_site',
                    'vendor',
                ],
            ],
        ];
    }
}