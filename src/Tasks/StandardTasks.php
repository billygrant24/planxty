<?php
namespace Phabric\Tasks;

trait StandardTasks
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
}