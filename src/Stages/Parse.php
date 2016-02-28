<?php
namespace Phabric\Stages;

use Illuminate\Support\Collection;
use Symfony\Component\Finder\SplFileInfo;

final class Parse
{
    private $config;
    private $parser;

    public function __construct($config, $parser)
    {
        $this->config = $config;
        $this->parser = $parser;
    }

    public function __invoke($payload)
    {
        return $payload->reject(function (SplFileInfo $item) {
            return starts_with($item->getBasename(), '_');
        })->transform(function ($item) {
            return $this->parseFile($item);
        })->transform(function ($item) {
            return $this->parseMarkdownAttributes($item);
        })->keyBy('permalink');
    }

    private function parseFile($item)
    {
        $parsedItem = $this->parser->parse(file_get_contents($item->getPathname()));
        $yaml = collect($parsedItem->getYAML());

        // Fill attributes
        $yaml['path'] = $item->getRelativePath();
        $yaml['pathname'] = $item->getRelativePathname();
        $yaml['scope'] = $this->inferScope($yaml);
        $yaml['permalink'] = $this->inferUri($yaml);
        $yaml['canonical_url'] = $yaml['permalink'];
        $yaml['layout'] = $this->inferLayout($yaml);
        $yaml['date'] = $this->parseDateString($yaml);

        // Fill body content
        $yaml['body'] = $parsedItem->getContent();

        return $yaml;
    }

    private function parseMarkdownAttributes($item)
    {
        array_walk_recursive($item, function (&$field, $key) {
            if (ends_with($key, '.md')) {
                $parsedField = $this->parser->parse($field);
                $field = $parsedField->getContent();
            }

            return $field;
        });

        return $this->replaceAnnotatedKeys($item);
    }

    private function replaceAnnotatedKeys($item)
    {
        $return = [];
        foreach ($item as $key => $value) {
            $key = str_replace('.md', '', $key);

            if (is_array($value)) {
                $value = $this->replaceAnnotatedKeys($value);
            }

            $return[$key] = $value;
        }

        return $return;
    }

    /**
     * @param \Illuminate\Support\Collection $item
     *
     * @return int|string
     */
    private function inferScope(Collection $item)
    {
        foreach ($this->config['scopes'] as $scope => $config) {
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
        if ($item->has('permalink')) {
            return '/' . trim($item->get('permalink'), '/');
        }

        $scope = $item->get('scope');
        $permalink = $this->config["scopes.$scope.permalink"];

        return '/' . $this->replacePermalinkParts($permalink, $item);
    }

    /**
     * @param string                         $permalink
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

        $uri = trim(ends_with($uri, 'md') ? str_replace('md', '', $uri) : $uri, '/.');

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
        $layout = $this->config["scopes.$scope.layout"];
        $layout = $layout ? $layout : $this->config['scopes.default.layout'];

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