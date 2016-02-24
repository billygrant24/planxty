<?php
namespace Phabric\Collecting;

use Illuminate\Support\Collection;
use Phabric\Parsing\Parser;
use Pimple\Container;
use Symfony\Component\Finder\SplFileInfo;

final class ContentCollector
{
    use Parser;

    public function __construct(Container $c)
    {
        $this->config = $c['config'];
        $this->finder = $c['finder'];
        $this->markdown = $c['markdown'];
        $this->twig = $c['twig'];
        $this->yaml = $c['yaml'];
    }

    public function collect()
    {
        $path = $this->config->get('paths.content');

        $this->finder->files()->in($path)->name('*.yml');

        $items = [];
        foreach ($this->finder as $file) {
            $items[] = $this->parseContent($file);
        }

        $content = new Collection($items);

        $scopes = collect($this->config->get('scopes'));
        $content->macro('scope', function ($scope) use ($scopes) {
            if ( ! $scopes->has($scope)) {
                return $this;
            }

            $scopedItems = $this->where('scope', $scope);

            $sort = $scopes->get("$scope.sort");
            $order = $scopes->get("$scope.order", 'DESC');

            return $scopedItems->sortBy($sort, null, strtoupper($order) === 'DESC');
        });

        return $content;
    }

    /**
     * @param \Symfony\Component\Finder\SplFileInfo $file
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public function parseContent(SplFileInfo $file)
    {
        $item = $this->parse($file);

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