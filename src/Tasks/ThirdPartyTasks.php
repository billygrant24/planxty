<?php
namespace Phabric\Tasks;

trait ThirdPartyTasks
{
    use \Robo\Task\Remote\loadTasks;
    use \Robo\Task\Testing\loadTasks;
    use \Robo\Task\ApiGen\loadTasks;
    use \Robo\Task\Docker\loadTasks;
    use \Robo\Task\Gulp\loadTasks;
}