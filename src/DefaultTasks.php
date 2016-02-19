<?php
namespace Planxty;

use Planxty\Concerns\BuildsApi;
use Planxty\Concerns\BuildsAssets;
use Planxty\Concerns\BuildsRss;
use Planxty\Concerns\BuildsSite;
use Planxty\Concerns\BuildsSitemap;
use Robo\Common\TaskIO;
use Robo\Tasks;

class DefaultTasks extends Tasks
{
    use BuildsApi;
    use BuildsAssets;
    use BuildsRss;
    use BuildsSite;
    use BuildsSitemap;
    use TaskIO;

    protected $buildDir = '';

    public function __construct()
    {
        $this->container = ContainerFactory::getStaticInstance();
        $this->buildDir = $this->container['config']->get('paths.build');
    }

    public function compose($opts = ['play|p' => false, 'listen|l' => false])
    {
        $this->printTaskInfo('Starting build process');

        // Purge old builds
        $this->clean();

        // Build static HTML pages
        $this->composeHtml();

        // Compile and copy assets over to the build folder
        $this->composeAssets();

        $this->printTaskSuccess('Congratulations, your build was successful!');

        if ($opts['play']) {
            $this->play();
        }

        if ($opts['listen']) {
            $this->listen();
        }
    }

    public function play()
    {
        $this
            ->taskServer(3000)
            ->dir($this->buildDir)
            ->run();
    }

    public function listen()
    {
        $config = $this->container['config'];

        $this
            ->taskWatch()
            ->monitor([
                $config->get('paths.assets'),
                $config->get('paths.content'),
                $config->get('paths.layouts'),
            ], function () use ($config) {
                $this->say('Changes detected');
                $this->compose(null);
            })
            ->run();
    }

    public function clean()
    {
        $this->taskCleanDir($this->buildDir)->run();
    }

    protected function composeHtml()
    {
        $config = $this->container['config'];
        $content = $this->container['content']->collect();

        $this
            ->taskBuildSite($content)
            ->target($this->buildDir)
            ->run();

        if ($config->get('sitemap.enabled')) {
            $this->composeSitemap($content);
        }

        if ($config->get('rss.enabled')) {
            $this->composeRss($content);
        }

        if ($config->get('api.enabled')) {
            $this->composeApi($content);
        }
    }

    public function composeAssets()
    {
        $config = $this->container['config'];

        $this
            ->taskBuildAssets($config->get('paths.assets'))
            ->target($this->buildDir)
            ->run();
    }

    public function composeApi($content = null)
    {
        $config = $this->container['config'];
        $content = $content ? $content : $this->container['content']->collect();

        $this
            ->taskBuildApi($config->get('api.filename') . '.json')
            ->with($content)
            ->target($this->buildDir)
            ->run();
    }

    public function composeRss($content = null)
    {
        $config = $this->container['config'];
        $content = $content ? $content : $this->container['content']->collect();

        $this
            ->taskBuildRss($config->get('rss.filename') . '.xml')
            ->with($content)
            ->target($this->buildDir)
            ->run();
    }

    public function composeSitemap($content = null)
    {
        $config = $this->container['config'];
        $content = $content ? $content : $this->container['content']->collect();

        $this
            ->taskBuildSitemap($config->get('sitemap.filename'))
            ->with($content)
            ->target($this->buildDir)
            ->run();
    }
}