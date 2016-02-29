<?php
namespace Phabric\Pipelines;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineBuilder;
use Phabric\Stages\Setup;
use Pimple\Container;

final class SetupPipeline extends Pipeline
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
        $builder->add(new Setup($this->c['config']['scopes']));

        $stages = $this->c['config']['pipelines.setup'];
        foreach ($stages as $stage) {
            $builder->add(new $stage($this->c));
        }

        return $builder->build()->process($payload);
    }
}