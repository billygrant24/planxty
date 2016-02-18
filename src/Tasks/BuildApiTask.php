<?php
namespace Planxty\Tasks;

use Planxty\ContainerFactory;
use Robo\Contract\TaskInterface;
use Robo\Result;

class BuildApiTask implements TaskInterface
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
        $fs = $this->container['fs'];

        $json = collect([
            'content' => $this->content->toArray(),
            'categories' => $this->content->pluck('category')->unique()->filter(),
            'tags' => $this->content->pluck('tags')->flatten()->values()->unique()->filter(),
        ])->toJson();

        $fs->dumpFile(rtrim($this->target, '/') . '/' . trim($this->name, '/'), $json);

        return Result::success($this, 'Added API endpoint');
    }
}