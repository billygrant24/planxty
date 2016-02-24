<?php
namespace Phabric\Collection;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

abstract class CollectionAbstract implements ServiceProviderInterface, CollectionProvider
{
    public final function register(Container $c)
    {
        foreach ($this->addCollections($c) as $identifier => $collection) {
            $c["collections.$identifier"] = $collection;
        }
    }
}