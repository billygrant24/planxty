<?php
namespace Planxty\Concerns;

use Planxty\Tasks\BuildApiTask;

trait BuildsApi
{
    /**
     * @param string $path
     *
     * @return \Planxty\Tasks\BuildApiTask
     */
    public function taskBuildApi($path)
    {
        return new BuildApiTask($path);
    }
}