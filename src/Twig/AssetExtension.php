<?php
namespace Planxty\Twig;

use Twig_Extension;
use Twig_SimpleFilter;

class AssetExtension extends Twig_Extension
{
    public function getName()
    {
        return 'AssetExtension';
    }

    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('asset', function ($path) {
                return '/' . ltrim($path, '/');
            }),
        ];
    }
}