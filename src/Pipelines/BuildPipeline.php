<?php
namespace Phabric\Pipelines;

use League\Pipeline\Pipeline;
use Pimple\Container;

final class BuildPipeline extends Pipeline
{
    /**
     * @var \Pimple\Container $container
     */
    private $c;

    public function __invoke($payload)
    {
        return $this
            ->pipe(new SetupPipeline($this->c))
            ->pipe(new ParsePipeline($this->c))
            ->pipe(new TransformPipeline($this->c))
            ->pipe(new ExportPipeline($this->c))
            ->process($payload);
    }

    /**
     * @param \Pimple\Container $container
     */
    public function setContainer(Container $container)
    {
        $this->c = $container;
    }
}