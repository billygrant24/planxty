<?php
namespace Planxty\Concerns;

use Planxty\Tasks\BuildSiteTask;

trait BuildsSite
{
    /**
     * @param string $path
     *
     * @return \Planxty\Tasks\BuildSiteTask
     */
    public function taskBuildSite($path)
    {
        return new BuildSiteTask($path);
    }
}