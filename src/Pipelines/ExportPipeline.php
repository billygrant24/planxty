<?php
namespace Phabric\Pipelines;

use League\Pipeline\Pipeline;
use League\Pipeline\PipelineBuilder;
use Phabric\Stages\Export;
use Pimple\Container;

final class ExportPipeline extends Pipeline
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
        $config = $this->c['config'];
        $storage = $this->c['storage'];
        $template = $this->c['template'];

        $builder = new PipelineBuilder();
        $builder->add(new Export($config, $storage, $template));

        $stages = $config['pipelines.export'];
        foreach ($stages as $stage) {
            $builder->add(new $stage($this->c));
        }

        return $builder->build()->process($payload);
    }
}