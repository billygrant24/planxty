<?php
namespace Planxty\Tasks;

use Planxty\ContainerFactory;
use Robo\Contract\TaskInterface;
use Robo\Result;

class BuildSiteTask implements TaskInterface
{
    /**
     * @var \Pimple\Container
     */
    protected $container;

    protected $content;

    public function __construct($content)
    {
        $this->container = ContainerFactory::getStaticInstance();
        $this->content = $content;
    }

    public function target($target)
    {
        $this->target = $target;

        return $this;
    }

    public function run()
    {
        $config = $this->container['config'];
        $fs = $this->container['fs'];
        $twig = $this->container['twig'];

        foreach ($this->content as $data) {
            $path = rtrim($this->target, '/') . '/' . $data['uri'];

            $fs->dumpFile(
                $path,
                $twig->render($data['layout'] . '.twig', array_merge([
                    'categories' => $this->content->pluck('category')->unique()->filter(),
                    'config' => $config,
                    'content' => $this->content,
                    'tags' => $this->content->pluck('tags')->flatten()->values()->unique()->filter(),
                ], [
                    'page' => $data,
                ]))
            );
        }

        return Result::success($this, 'Generated static HTML');
    }
}