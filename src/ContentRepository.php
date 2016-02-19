<?php
namespace Planxty;

use Pimple\Container;

class ContentRepository
{
    /**
     * @param \Pimple\Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
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

            $page->put('date', strtotime($page->get('date', 'now')));
            $page->put('date_updated', strtotime($page->get('date_updated', $page->get('date'))));
            $page->put('type', $page->get('type', $config->get('default_type')));
            $page->put('layout', $page->get('layout', $config->get('types.' . $page->get('type') . '.layout')));
            $page->put('body', $markdown->parse($page->get('body', '')));

            if ($uri = $page->get('uri')) {
                $page->put('uri', '/' . trim($uri, '/'));
            } else {
                $page->put('uri', '/' . str_replace('.yml', '.html', $file->getRelativePathname()));
            }

            if ($blocks = $page->get('blocks')) {
                collect($blocks)->map(function ($block) use ($markdown) {
                    $block = collect($block);

                    if ($blockBody = $block->get('body')) {
                        $block->put('body', $markdown->parse($blockBody));
                    }
                });
            }

            $content->push($page);
        }

        return $content->sortByDesc('date');
    }
}