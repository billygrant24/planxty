<?php
namespace Planxty\Concerns;

use Planxty\Tasks\BuildRssTask;

trait BuildsRss
{
    /**
     * @param string $path
     *
     * @return \Planxty\Tasks\BuildRssTask
     */
    public function taskBuildRss($path)
    {
        return new BuildRssTask($path);
    }
}