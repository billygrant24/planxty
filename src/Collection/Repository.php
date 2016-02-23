<?php
namespace Phabric\Collection;

use Phabric\Config;
use Symfony\Component\Finder\Finder;

interface Repository
{
    /**
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public function collect();

    /**
     * @param \Phabric\Config $config
     */
    public function setConfig(Config $config);

    /**
     * @param \Symfony\Component\Finder\Finder $finder
     */
    public function setFinder(Finder $finder);
}