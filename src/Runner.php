<?php
namespace Phabric;

final class Runner extends \Robo\Runner
{
    public function __construct()
    {
        $this->roboClass = Application::class;
        $this->dir = getcwd();
    }

    protected function loadRoboFile()
    {
        if ( ! file_exists($this->dir)) {
            $this->yell(
                "Path in `{$this->dir}` is invalid, please provide valid absolute path to load Robofile",
                40,
                'red'
            );

            return false;
        }

        chdir($this->dir);

        return true;
    }
}