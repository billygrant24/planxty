<?php
namespace Phabric\Pipelines;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineBuilder;
use Phabric\Stages\Parse;
use Pimple\Container;

final class ParsePipeline extends Pipeline
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
        $builder->add(new Parse($this->c['config'], $this->c['parser']));

        $stages = $this->c['config']['pipelines.parse'];
        foreach ($stages as $stage) {
            $builder->add(new $stage($this->c));
        }

        return $builder->build()->process($payload);
    }
}