<?php
namespace Planxty;

use Planxty\Tasks\Concerns\BuildsAssets;
use Planxty\Tasks\Concerns\BuildsSite;
use Robo\Common\TaskIO;
use Robo\Tasks;

class PlanxtyFile extends Tasks
{
    use BuildsAssets;
    use BuildsSite;
    use TaskIO;

    /**
     * @var string
     */
    protected $buildDir = '';

    public function __construct()
    {
        $this->container = ContainerFactory::newInstance();
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
        $content = $this->container['content']->collect();

        $this
            ->taskBuildSite($content)
            ->target($this->buildDir)
            ->run();
    }

    public function composeAssets()
    {
        $config = $this->container['config'];

        $this
            ->taskBuildAssets($config->get('paths.assets'))
            ->target($this->buildDir)
            ->run();
    }
}