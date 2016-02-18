<?php
namespace Planxty\Tasks;

use Planxty\ContainerFactory;
use Robo\Contract\TaskInterface;
use Robo\Result;

class BuildAssetsTask implements TaskInterface
{
    /**
     * @var \Pimple\Container
     */
    protected $container;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $target;

    public function __construct($path)
    {
        $this->container = ContainerFactory::getStaticInstance();
        $this->path = $path;
    }

    public function target($target)
    {
        $this->target = $target;

        return $this;
    }

    public function run()
    {
        $finder = $this->container['finder'];
        $fs = $this->container['fs'];

        $finder->files()->in($this->path);

        if ($finder->count() === 0) {
            return Result::success($this, 'No assets to compile');
        }

        // Copy our assets over to the build directory
        foreach ($finder as $file) {
            $fs->copy(
                $file->getPathName(),
                rtrim($this->target, '/') . '/' . trim($file->getRelativePathname(), '/')
            );
        }

        return Result::success($this, 'Compiled assets');
    }
}