<?php
namespace Phabric\Collection;

use Pimple\Container;

interface CollectionProvider
{
    public function addCollections(Container $c);
}