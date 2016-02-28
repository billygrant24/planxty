<?php
namespace Phabric\Pipelines;

use League\Pipeline\Pipeline;
use Phabric\Stages\Paginate;
use Phabric\Stages\Hydrate;
use Phabric\Stages\Parse;
use Phabric\Stages\Setup;
use Phabric\Stages\Export;
use Pimple\Container;

final class SimpleBuild extends Pipeline
{
    /**
     * @var \Pimple\Container $container
     */
    private $c;

    public function __invoke($payload)
    {
        $config = $this->c['config'];
        $parser = $this->c['parser'];
        $storage = $this->c['storage'];
        $template = $this->c['template'];

        return $this
            ->pipe(new Setup($config['scopes']))
            ->pipe(new Parse($config, $parser))
            ->pipe(new Hydrate($config['taxonomies']))
            ->pipe(new Paginate())
            ->pipe(new Export($config, $storage, $template))
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