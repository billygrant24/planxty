<?php
namespace Phabric;

use Robo\Common\TaskIO;
use Robo\Tasks;

class Application extends Tasks
{
    use TaskIO;

    /**
     * @var \Pimple\Container
     */
    protected $c;
    protected $config;

    public function __construct()
    {
        $this->c = require getcwd() . '/bootstrap.php';
        $this->config = $this->c['config'];
    }

    public function serve()
    {
        $this
            ->taskServer(3000)
            ->dir($this->config['paths.output'])
            ->run();
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
        $config = $this->config;
        $finder = $this->c['finder'];
        $pipeline = $this->c['pipeline'];

        return $pipeline->process(
            $finder->files()->in($config['paths.content'])->name('*.md')
        );
    }

    public function buildAssets()
    {
        $css = new \Assetic\Asset\AssetCollection([
            new \Assetic\Asset\GlobAsset('assets/css/*')
        ], [
            new \Assetic\Filter\CssMinFilter(),
        ]);

        $sass = new \Assetic\Asset\AssetCollection([
            new \Assetic\Asset\GlobAsset('assets/sass/*')
        ], [
            new \Assetic\Filter\ScssphpFilter(),
            new \Assetic\Filter\CssMinFilter(),
        ]);

        $js = new \Assetic\Asset\AssetCollection([
            new \Assetic\Asset\FileAsset('assets/js/jquery-1.10.2.min.js'),
            new \Assetic\Asset\FileAsset('assets/js/bootstrap.min.js'),
            new \Assetic\Asset\FileAsset('assets/js/instantclick.min.js'),
        ], [
            new \Assetic\Filter\JSMinFilter(),
        ]);

        $config = $this->c['config'];
        $fs = $this->c['storage'];

        $assetsDir = $config['paths.assets'];
        $buildDir = $config['paths.output'];

        $fs->copy($assetsDir . '/js/html5shiv.js', $buildDir . '/js/html5shiv.js');
        $fs->copy($assetsDir . '/js/modernizr-2.6.2.min.js', $buildDir . '/js/modernizr-2.6.2.min.js');

        $this->taskCopyDir([
            $assetsDir . '/fonts' => $buildDir . '/fonts',
        ])->run();

        $fs->dumpFile($buildDir . '/js/vendor.min.js', $js->dump());
        $fs->dumpFile($buildDir . '/css/vendor.min.css', $css->dump());
        $fs->dumpFile($buildDir . '/css/compiled.min.css', $sass->dump());
    }
}