<?php
namespace Planxty\Tasks;

use Illuminate\Support\Collection;
use Planxty\ContainerFactory;
use Robo\Contract\TaskInterface;
use Robo\Result;

class BuildSiteTask implements TaskInterface
{
    /**
     * @var \Pimple\Container
     */
    protected $container;

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $content;

    /**
     * @param \Illuminate\Support\Collection $content
     */
    public function __construct(Collection $content)
    {
        $this->container = ContainerFactory::newInstance();
        $this->content = $content;
    }

    /**
     * @param string $target
     *
     * @return $this
     */
    public function target($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @return \Robo\Result
     */
    public function run()
    {
        foreach ($this->content as $page) {
            $twigData = array_merge([
                'blocks' => $this->container['blocks']->collect(),
                'categories' => $this->content->pluck('category')->unique()->filter(),
                'config' => $this->container['config'],
                'content' => $this->content,
                'tags' => $this->content->pluck('tags')->flatten()->values()->unique()->filter(),
            ], compact('page'));

            $this->writeFile($page, $twigData);
        }

        return Result::success($this, 'Generated static HTML');
    }

    /**
     * @param \Illuminate\Support\Collection $page
     * @param array                          $twigData
     */
    protected function writeFile(Collection $page, array $twigData)
    {
        $fs = $this->container['fs'];
        $twig = $this->container['twig'];

        if ( ! $page->has('pagination')) {
            $fs->dumpFile(
                rtrim($this->target, '/') . $page->get('uri'),
                $twig->render($page->get('layout'), $twigData)
            );
        } else {
            $pagination = collect($page->get('pagination'));

            $type = $pagination->get('from');
            $size = $pagination->get('size', null);

            $scopedContent = $this->content->where('type', $type);
            $contentCount = $scopedContent->count();
            $pageCount = ceil($contentCount / ($size ? $size : 1));

            $getPagedPath = function ($pageNumber) use ($page) {
                $uri = $page->get('uri');

                return $pageNumber > 1 ? str_replace('.html', '-' . $pageNumber . '.html', $uri) : $uri;
            };

            for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                $page->put('uri', $getPagedPath($pageNumber));

                $pagination->put('items', $scopedContent->forPage($pageNumber, $size));
                $pagination->put('current', $pageNumber);
                $pagination->put('current_uri', $getPagedPath($pageNumber));
                $pagination->put('next', $pageNumber < $pageCount ? $pageNumber + 1 : null);
                $pagination->put('next_uri', $pageNumber < $pageCount ? $getPagedPath($pageNumber + 1) : null);
                $pagination->put('previous', $pageNumber > 1 ? $pageNumber - 1 : null);
                $pagination->put('previous_uri', $pageNumber > 1 ? $getPagedPath($pageNumber - 1) : null);
                $pagination->put('first', 1);
                $pagination->put('first_uri', $page->get('uri'));
                $pagination->put('last', $pageCount);
                $pagination->put('last_uri', $getPagedPath($pageCount));
                $pagination->put('total', $contentCount);

                $twigData['pagination'] = $pagination;

                $fs->dumpFile(
                    rtrim($this->target, '/') . $page->get('uri'),
                    $twig->render($page->get('layout'), $twigData)
                );
            }
        }
    }
}