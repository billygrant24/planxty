<?php
namespace Planxty\Block;

use Planxty\FileParser;
use Symfony\Component\Finder\SplFileInfo;

class Parser
{
    use FileParser;

    /**
     * @param \Symfony\Component\Finder\SplFileInfo $file
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public function parse(SplFileInfo $file)
    {
        return $this->parseFile($file);
    }
}