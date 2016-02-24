<?php
namespace Phabric\Task;

trait RoboTasks
{
    use \Robo\Task\Base\loadTasks;
    use \Robo\Task\Development\loadTasks;
    use \Robo\Task\FileSystem\loadTasks;
    use \Robo\Task\File\loadTasks;
    use \Robo\Task\Vcs\loadTasks;
    use \Robo\Task\Assets\loadTasks;
    use \Robo\Task\Base\loadShortcuts;
    use \Robo\Task\FileSystem\loadShortcuts;
    use \Robo\Task\Vcs\loadShortcuts;
    use \Robo\Common\TaskIO;

    use \Robo\Task\Composer\loadTasks;
    use \Robo\Task\Bower\loadTasks;
    use \Robo\Task\Npm\loadTasks;

    use \Robo\Task\Remote\loadTasks;
    use \Robo\Task\Testing\loadTasks;
    use \Robo\Task\ApiGen\loadTasks;
    use \Robo\Task\Docker\loadTasks;
    use \Robo\Task\Gulp\loadTasks;
}