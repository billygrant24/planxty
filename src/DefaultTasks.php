<?php
namespace Planxty;

use Illuminate\Support\Collection;
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

    /**
     * @var string
     */
    protected $buildDir = '';

    public function __construct()
    {
        $this->container = ContainerFactory::getStaticInstance();
        $this->buildDir = $this->container['config']->get('paths.build');
    }

    /**
     * @param array $opts
     */
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
        $fs = $this->container['fs'];

        if ($fs->exists($this->buildDir)) {
            $this->taskCleanDir($this->buildDir)->run();
        } else {
            $fs->mkdir($this->buildDir);
            $this->say('Could find build directory. Initialised on at: ' . $this->buildDir);
        }
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

    /**
     * @param \Illuminate\Support\Collection|null $content
     */
    public function composeApi(Collection $content = null)
    {
        $config = $this->container['config'];
        $content = $content ? $content : $this->container['content']->collect();

        $this
            ->taskBuildApi($config->get('api.filename') . '.json')
            ->with($content)
            ->target($this->buildDir)
            ->run();
    }

    /**
     * @param \Illuminate\Support\Collection|null $content
     */
    public function composeRss(Collection $content = null)
    {
        $config = $this->container['config'];
        $content = $content ? $content : $this->container['content']->collect();

        $this
            ->taskBuildRss($config->get('rss.filename') . '.xml')
            ->with($content)
            ->target($this->buildDir)
            ->run();
    }

    /**
     * @param \Illuminate\Support\Collection|null $content
     */
    public function composeSitemap(Collection $content = null)
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