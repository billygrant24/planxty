<?php
namespace Phabric;

use Phabric\Configuration\ServiceProvider;
use Phabric\Provider\BlockProvider;
use Phabric\Provider\ContentProvider;
use Pimple\Container;

final class ContainerFactory
{
    public static function newInstance()
    {
        $container = new Container();

        $container->register(new ServiceProvider());
        $container->register(new ContentProvider());
        $container->register(new BlockProvider());

        return $container;
    }
}