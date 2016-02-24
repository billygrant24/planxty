<?php
namespace Phabric\Collection;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

abstract class ProviderAbstract implements ServiceProviderInterface, Provider
{
    public function register(Container $c)
    {
        foreach ($this->addCollections($c) as $identifier => $collection) {
            $c["collections.$identifier"] = $collection;
        }
    }
}