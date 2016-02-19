<?php
namespace Planxty\Tasks;

use Illuminate\Support\Collection;
use Planxty\ContainerFactory;
use Robo\Contract\TaskInterface;
use Robo\Result;
use SitemapPHP\Sitemap;

class BuildSitemapTask implements TaskInterface
{
    /**
     * @var \Pimple\Container
     */
    protected $container;

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $content;

    /**
     * @var string
     */
    protected $target;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->container = ContainerFactory::getStaticInstance();
        $this->name = $name;
    }

    /**
     * @param \Illuminate\Support\Collection $content
     *
     * @return $this
     */
    public function with(Collection $content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @param string $target
     *
     * @return $this
     */
    public function target($target )
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @return \Robo\Result
     */
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
            $sitemap->addItem($page->get('uri'));
        }

        // Finalise the generated sitemap
        $sitemap->createSitemapIndex($config->get('url') . '/', 'Today');

        return Result::success($this, 'Generated sitemap');
    }
}