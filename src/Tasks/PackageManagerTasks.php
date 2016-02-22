<?php
namespace Phabric\Tasks;

trait PackageManagerTasks
{
    use \Robo\Task\Composer\loadTasks;
    use \Robo\Task\Bower\loadTasks;
    use \Robo\Task\Npm\loadTasks;
}