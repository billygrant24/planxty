<?php
namespace Planxty\Concerns;

use Planxty\Tasks\BuildSitemapTask;

trait BuildsSitemap
{
    /**
     * @param string $path
     *
     * @return \Planxty\Tasks\BuildSitemapTask
     */
    public function taskBuildSitemap($path)
    {
        return new BuildSitemapTask($path);
    }
}