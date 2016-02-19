<?php
namespace Planxty\Tasks;

use Illuminate\Support\Collection;
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
    public function target($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @return \Robo\Result
     */
    public function run()
    {
        $fs = $this->container['fs'];

        $json = collect([
            'content' => $this->content->toArray(),
            'categories' => $this->content->pluck('category')->unique()->filter(),
            'tags' => $this->content->pluck('tags')->flatten()->values()->unique()->filter(),
        ])->toJson();

        $fs->dumpFile(
            implode('/', [rtrim($this->target, '/'), trim($this->name, '/')]),
            $json
        );

        return Result::success($this, 'Added API endpoint');
    }
}