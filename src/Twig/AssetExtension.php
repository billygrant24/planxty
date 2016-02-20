<?php
namespace Planxty\Twig;

use Planxty\ContainerFactory;
use Twig_Extension;
use Twig_SimpleFilter;

class AssetExtension extends Twig_Extension
{
    public function __construct()
    {
        $this->container = ContainerFactory::getStaticInstance();
    }

    public function getName()
    {
        return 'AssetExtension';
    }

    public function getFilters()
    {
        $config = $this->container['config'];

        return [
            new Twig_SimpleFilter('asset', function ($path) use ($config) {
                return $config->get('url') . '/' . trim($path, '/');
            }),
            new Twig_SimpleFilter('url', function ($path) use ($config) {
                return $config->get('url') . '/' . trim($path, '/');
            }),
        ];
    }
}