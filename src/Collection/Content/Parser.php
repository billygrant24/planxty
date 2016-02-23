<?php
namespace Phabric\Collection\Content;

use Illuminate\Support\Collection;
use Phabric\Collection\Parser as ParserTrait;
use Symfony\Component\Finder\SplFileInfo;

final class Parser
{
    use ParserTrait {
        parse as parseFile;
    }

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
        if ($page->has('uri')) {
            return '/' . trim($page->get('uri'), '/');
        }

        foreach ($this->config->get('scopes') as $scope => $scopeConfig) {
            if (starts_with($page->get('path'), $scopeConfig['path']) && $scopeConfig['path'] !== '') {
                return '/' . $this->replacePermalinkParts($scopeConfig['permalink'], $page);
            }
        }

        return '/' . $this->replacePermalinkParts($this->config->get('scopes.default.permalink'), $page);
    }

    protected function replacePermalinkParts($permalink, $page)
    {
        $uri = $permalink;
        $parts = explode('/', $permalink);
        foreach ($parts as $part) {
            if (starts_with($part, ':')) {
                $replacement = $part !== ':pathname' ? str_slug(array_get($page, substr($part, 1))) : $page->get('pathname');
                $uri = str_replace("$part", $replacement, $uri);
            }
        }

        return trim(ends_with($uri, 'yml') ? str_replace('yml', '', $uri) : $uri, '/.') . '.html';
    }
}