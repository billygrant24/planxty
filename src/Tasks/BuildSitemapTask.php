<?php
namespace Planxty\Tasks;

use Planxty\ContainerFactory;
use Robo\Contract\TaskInterface;
use Robo\Result;
use SitemapPHP\Sitemap;

trait BuildsSitemap
{
    /**
     * @param string $path
     *
     * @return \Planxty\Tasks\BuildSitemapTask
     */
    public function taskBuildSitemap($path)
    {
        return new BuildSitemapTask($path);
    }
}

class BuildSitemapTask implements TaskInterface
{
    /**
     * @var \Pimple\Container
     */
    protected $container;

    /**
     * @var Illuminate\Support\Collection
     */
    protected $content;

    /**
     * @var string
     */
    protected $target;

    public function __construct($name)
    {
        $this->container = ContainerFactory::getStaticInstance();
        $this->name = $name;
    }

    public function with($content)
    {
        $this->content = $content;

        return $this;
    }

    public function target($target)
    {
        $this->target = $target;

        return $this;
    }

    public function run()
    {
        $config = $this->container['config'];

        // Initialise the sitemap
        $sitemap = new Sitemap($config->get('url'));
        $sitemap
            ->setPath(rtrim($this->target, '/') . '/')
            ->setFilename($this->name);


        // Add each page
        foreach ($this->content as $page) {
            $sitemap->addItem($page['uri']);
        }

        // Finalise the generated sitemap
        $sitemap->createSitemapIndex($config->get('url') . '/', 'Today');

        return Result::success($this, 'Generated sitemap');
    }
}