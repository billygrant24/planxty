<?php
namespace Planxty;

use Pimple\Container;

class ContentRepository
{
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function collect()
    {
        $config = $this->container['config'];
        $finder = $this->container['finder'];
        $markdown = $this->container['markdown'];
        $yaml = $this->container['yaml'];
        $twig = $this->container['twig'];

        $finder->files()->in($config->get('paths.content'))->name('*.yml');

        $content = collect([]);
        foreach ($finder as $file) {
            $twigTemplate = twig_template_from_string($twig, file_get_contents($file->getPathName()));
            $page = collect($yaml->parse($twigTemplate->render(compact('config'))));

            if ($body = $page->get('body')) {
                $page->put('body', $markdown->parse($body));
            }

            if ($blocks = $page->get('blocks')) {
                collect($blocks)->map(function ($block) use ($markdown) {
                    $block = collect($block);

                    if ($blockBody = $block->get('body')) {
                        $block->put('body', $markdown->parse($blockBody));
                    }
                });
            }

            if ($uri = $page->get('uri')) {
                $page->put('uri', '/' . trim($uri, '/'));
            } else {
                $page->put('uri', '/' . str_replace('.yml', '.html', $file->getRelativePathname()));
            }

            $content->push($page);
        }

        return $content;
    }
}