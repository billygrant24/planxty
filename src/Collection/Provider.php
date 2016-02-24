<?php
namespace Phabric\Collection;

use Pimple\Container;

interface Provider
{
    public function addCollections(Container $c);
}