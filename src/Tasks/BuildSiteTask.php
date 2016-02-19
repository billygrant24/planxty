<?php
namespace Planxty\Tasks;

use Planxty\ContainerFactory;
use Robo\Contract\TaskInterface;
use Robo\Result;

class BuildSiteTask implements TaskInterface
{
    /**
     * @var \Pimple\Container
     */
    protected $container;

    protected $content;

    public function __construct($content)
    {
        $this->container = ContainerFactory::getStaticInstance();
        $this->content = $content;
    }

    public function target($target)
    {
        $this->target = $target;

        return $this;
    }

    public function run()
    {
        $config = $this->container['config'];

        foreach ($this->content as $page) {
            $twigData = array_merge([
                'categories' => $this->content->pluck('category')->unique()->filter(),
                'config' => $config,
                'content' => $this->content,
                'tags' => $this->content->pluck('tags')->flatten()->values()->unique()->filter(),
            ], compact('page'));

            if ($page->has('pagination')) {
                $this->handlePagination($page, $twigData);
                continue;
            }

            $this->writeFile($page, $twigData);
        }

        return Result::success($this, 'Generated static HTML');
    }

    private function writeFile($page, $data)
    {
        $fs = $this->container['fs'];
        $twig = $this->container['twig'];

        $fs->dumpFile(
            rtrim($this->target, '/') . $page->get('uri'),
            $twig->render($page->get('layout'), $data)
        );
    }

    private function handlePagination($page, $twigData)
    {
        $pagination = collect($page->get('pagination'));

        $type = $pagination->get('use', true);
        $size = $pagination->get('size', null);

        $scopedContent = $this->content->where('layout', $type);
        $contentCount = $scopedContent->count();
        $pageCount = ceil($contentCount / ($size ? $size : 1));

        $getPagedPath = function ($pageNumber) use ($page) {
            $uri = $page->get('uri');
            return $pageNumber > 1 ? str_replace('.html', '-' . $pageNumber . '.html', $uri) : $uri;
        };

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++ ) {
            $page->put('uri', $getPagedPath($pageNumber));

            $pagination->put('items', $scopedContent->forPage($pageNumber, $size));
            $pagination->put('current', $pageNumber);
            $pagination->put('current_uri', $getPagedPath($pageNumber));
            $pagination->put('next', $pageNumber < $pageCount ? $pageNumber + 1 : null);
            $pagination->put('next_uri', $pageNumber < $pageCount ? $getPagedPath($pageNumber + 1) : null);
            $pagination->put('previous', $pageNumber > 1 ? $pageNumber - 1 : null);
            $pagination->put('previous_uri', $pageNumber > 1 ? $getPagedPath($pageNumber - 1) : null);
            $pagination->put('first', 1);
            $pagination->put('first_uri', $getPagedPath(1));
            $pagination->put('last', $pageCount);
            $pagination->put('last_uri', $getPagedPath($pageCount));
            $pagination->put('total', $contentCount);

            $twigData['pagination'] = $pagination;

            $this->writeFile($page, $twigData);
        }
    }
}