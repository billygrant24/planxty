<?php
namespace Phabric;

use Pimple\Container;
use Robo\Common\TaskIO;
use Robo\Tasks;

class Application extends Tasks
{
    use TaskIO;

    /**
     * @var \Pimple\Container
     */
    protected $c;

    /**
     * @var \Phabric\Config
     */
    protected $config;

    public function __construct()
    {
        $this->c = new Container();
        $this->c->register(new ServiceProvider());

        $this->c['env']->load();

        $this->config = $this->c['config'];
    }

    public function serve()
    {
        $this->taskServer(3000)->dir($this->config['paths.output'])->run();
    }

    public function watch()
    {
        $this
            ->taskWatch()
            ->monitor([
                $this->config['paths.content'],
                $this->config['paths.layouts'],
            ], function () {
                $this->say('Changes detected');
                $this->buildHtml();
            })
            ->run();
    }

    public function clean()
    {
        if ($this->c['storage']->exists($this->config['paths.output'])) {
            $this->taskCleanDir($this->config['paths.output'])->run();
        } else {
            $this->c['storage']->mkdir($this->config['paths.output']);
            $this->say('Build dir does not exist. Initialised one at: ' . $this->config['paths.output']);
        }
    }

    public function build($opts = ['watch|w' => false])
    {
        $this->printTaskInfo('Starting build process');

        $this->clean();
        $this->buildHtml();

        // Install NPM dependencies
        if ($this->c['storage']->exists(getcwd() . '/package.json')) {
            $this->taskNpmInstall()->printed(false)->run();
        }

        // Run default Gulp task
        if ($this->c['storage']->exists(getcwd() . '/gulpfile.js')) {
            $this->taskGulpRun()->silent()->run();
        }

        $this->printTaskSuccess('Congratulations, your build was successful!');

        if ($opts['watch']) {
            $this->watch();
        }
    }

    public function buildHtml()
    {
        $config = $this->c['config'];
        $storage = $this->c['storage'];
        $pipeline = $this->c['pipeline'];

        $pipeline->process($this->c['finder']->in('_content')->name('*.md'));

        foreach ($this->c['finder']->notName('*.md') as $file) {
            $storage->symlink(
                $file->getPathname(),
                $config['paths.output'] . '/' . $file->getRelativePathname()
            );
        }
    }
}