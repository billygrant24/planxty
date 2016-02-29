<?php
namespace Phabric\Pipelines;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineBuilder;
use Phabric\Stages\Hydrate;
use Phabric\Stages\Paginate;
use Pimple\Container;

final class TransformPipeline extends Pipeline
{
    /**
     * @var \Pimple\Container $container
     */
    private $c;

    /**
     * @param \Pimple\Container $container
     */
    public function __construct(Container $container)
    {
        $this->c = $container;
    }

    public function __invoke($payload)
    {
        $builder = new PipelineBuilder();
        $builder->add(new Hydrate($this->c['config']['taxonomies']));
        $builder->add(new Paginate());

        $stages = $this->c['config']['pipelines.transform'];
        foreach ($stages as $stage) {
            $builder->add(new $stage($this->c));
        }

        return $builder->build()->process($payload);
    }
}