<?php
namespace Phabric\Content;

use Illuminate\Support\Collection;
use Phabric\FileParser;
use Symfony\Component\Finder\SplFileInfo;

class Parser
{
    use FileParser;

    /**
     * @param \Symfony\Component\Finder\SplFileInfo $file
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public function parse(SplFileInfo $file)
    {
        $page = $this->parseFile($file);

        // Parse or infer defaults on some key fields
        $page->put('uri', $this->inferUri($page));
        $page->put('type', $this->inferContentType($page));
        $page->put('layout', $this->inferLayout($page));
        $page->put('date', $this->parseDateString($page));

        return $page;
    }

    /**
     * @param \Illuminate\Support\Collection $page
     *
     * @return string
     */
    protected function inferContentType(Collection $page)
    {
        return $page->get('type', $this->config->get('types.default'));
    }

    /**
     * @param \Illuminate\Support\Collection $page
     *
     * @return int
     */
    protected function parseDateString(Collection $page)
    {
        return strtotime($page->get('date', 'now'));
    }

    /**
     * @param \Illuminate\Support\Collection $page
     *
     * @return string
     */
    protected function inferLayout(Collection $page)
    {
        $isIndexFile = $page->get('uri') === '/' || str_contains($page->get('uri'), 'index.html');
        $inferredLayoutKey = 'types.' . $page->get('type') . '.layout';
        $default = $this->config->get($inferredLayoutKey, $isIndexFile ? 'index.twig' : null);

        return $page->get('layout', $default);
    }

    /**
     * @param \Illuminate\Support\Collection $page
     *
     * @return string
     */
    protected function inferUri(Collection $page)
    {
        if ($uri = $page->get('uri')) {
            return '/' . trim($uri, '/');
        }

        return '/' . str_replace('.yml', '.html', array_get($page, '_meta.relative_pathname'));
    }
}