<?php
namespace Phabric;

use Pimple\Container;

final class ContainerFactory
{
    public static function newInstance()
    {
        $container = new Container();
        $container->register(new ServiceProvider());

        return $container;
    }
}