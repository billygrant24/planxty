<?php
namespace Planxty\Concerns;

use Planxty\Tasks\BuildAssetsTask;

trait BuildsAssets
{
    /**
     * @param string $path
     *
     * @return \Planxty\Tasks\BuildAssetsTask
     */
    public function taskBuildAssets($path)
    {
        return new BuildAssetsTask($path);
    }
}