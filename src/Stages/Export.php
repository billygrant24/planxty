<?php
namespace Phabric\Stages;

final class Export
{
    private $config;
    private $storage;
    private $template;

    public function __construct($config, $storage, $template)
    {
        $this->config = $config;
        $this->storage = $storage;
        $this->template = $template;
    }

    public function __invoke($payload)
    {
        return $payload->each(function ($item) {
            $this->storage->dumpFile(
                $this->config['paths.output'] . '/' . trim($item['permalink'], '/'),
                $this->template->render($item['layout'], $item)
            );
        });
    }
}