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
        $this->buildAssets();

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

    public function buildAssets()
    {
        $css = new \Assetic\Asset\AssetCollection([
            new \Assetic\Asset\GlobAsset('css/*')
        ], [
            new \Assetic\Filter\CssMinFilter(),
        ]);

        $sass = new \Assetic\Asset\AssetCollection([
            new \Assetic\Asset\GlobAsset('_sass/*')
        ], [
            new \Assetic\Filter\ScssphpFilter(),
            new \Assetic\Filter\CssMinFilter(),
        ]);

        $js = new \Assetic\Asset\AssetCollection([
            new \Assetic\Asset\FileAsset('js/jquery-1.10.2.min.js'),
            new \Assetic\Asset\FileAsset('js/bootstrap.min.js'),
            new \Assetic\Asset\FileAsset('js/instantclick.min.js'),
        ], [
            new \Assetic\Filter\JSMinFilter(),
        ]);

        $config = $this->c['config'];
        $fs = $this->c['storage'];
        $buildDir = $config['paths.output'];

        $fs->dumpFile($buildDir . '/js/vendor.min.js', $js->dump());
        $fs->dumpFile($buildDir . '/css/vendor.min.css', $css->dump());
        $fs->dumpFile($buildDir . '/css/compiled.min.css', $sass->dump());
    }
}