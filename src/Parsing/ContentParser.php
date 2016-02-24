<?php
namespace Phabric\Parsing;

use Illuminate\Support\Collection;
use Symfony\Component\Finder\SplFileInfo;

final class ContentParser
{
    use Parser {
        parse as parseGeneric;
    }

    /**
     * @param \Symfony\Component\Finder\SplFileInfo $file
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public function parse(SplFileInfo $file)
    {
        $item = $this->parseGeneric($file);

        // Parse or infer defaults on some key fields
        $item->put('scope', $this->inferScope($item));
        $item->put('uri', $this->inferUri($item));
        $item->put('layout', $this->inferLayout($item));
        $item->put('date', $this->parseDateString($item));

        return $item;
    }

    /**
     * @param \Illuminate\Support\Collection $item
     *
     * @return int|string
     */
    private function inferScope(Collection $item)
    {
        foreach ($this->config->get('scopes') as $scope => $config) {
            if (starts_with($item->get('path'), $config['path']) && $config['path'] !== '') {
                return $scope;
            }
        }

        return 'default';
    }

    /**
     * @param \Illuminate\Support\Collection $item
     *
     * @return string
     */
    private function inferUri(Collection $item)
    {
        if ($item->has('uri')) {
            return '/' . trim($item->get('uri'), '/');
        }

        $scope = $item->get('scope');
        $permalink = $this->config->get("scopes.$scope.permalink");

        return '/' . $this->replacePermalinkParts($permalink, $item);
    }

    /**
     * @param string $permalink
     * @param \Illuminate\Support\Collection $item
     *
     * @return string
     */
    private function replacePermalinkParts($permalink, Collection $item)
    {
        $uri = $permalink;
        $parts = explode('/', $permalink);

        foreach ($parts as $part) {
            if (starts_with($part, ':')) {
                $replacement = $part !== ':pathname'
                    ? str_slug(array_get($item, substr($part, 1)))
                    : $item->get('pathname');

                $uri = str_replace($part, $replacement, $uri);
            }
        }

        $uri = trim(ends_with($uri, 'yml') ? str_replace('yml', '', $uri) : $uri, '/.');

        return $uri . '.html';
    }

    /**
     * @param \Illuminate\Support\Collection $item
     *
     * @return string
     */
    private function inferLayout(Collection $item)
    {
        if ($item->has('layout')) {
            return $item->get('layout');
        }

        $scope = $item->get('scope');
        $layout = $this->config->get(
            "scopes.$scope.layout",
            $this->config->get('scopes.default.layout', 'index.twig')
        );

        return $item->get('layout', $layout);
    }

    /**
     * @param \Illuminate\Support\Collection $item
     *
     * @return int
     */
    private function parseDateString(Collection $item)
    {
        return strtotime($item->get('date', 'now'));
    }
}