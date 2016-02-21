<?php
namespace Planxty\Content;

use Illuminate\Support\Collection;
use Parsedown;
use Symfony\Component\Yaml\Parser as Yaml;
use Twig_Environment;

class Parser
{
    /**
     * @var \Illuminate\Support\Collection
     */
    protected $config;

    /**
     * @var Parsedown
     */
    protected $markdown;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var Yaml
     */
    protected $yaml;

    public function __construct(Collection $config, Twig_Environment $twig, Yaml $yaml, Parsedown $markdown)
    {
        $this->config = $config;
        $this->twig = $twig;
        $this->markdown = $markdown;
        $this->yaml = $yaml;
    }

    public function parse($file)
    {
        // Load the template using the string loader
        $twigTemplate = twig_template_from_string(
            $this->twig,
            file_get_contents($file->getPathName())
        );

        // Transform the file using Twig, then parse the YAML file
        $page = collect(
            $this->yaml->parse(
                $twigTemplate->render(compact('config'))
            )
        );

        // Parse or infer defaults on some key fields
        $page->put('uri', $this->inferUri($file, $page));
        $page->put('type', $this->inferContentType($page));
        $page->put('layout', $this->inferLayout($page));
        $page->put('date', $this->parseDateString($page));

        // Transform markdown fields to HTML
        $this->transformMarkdown($page);

        // Parse blocks
        if ($blocks = $page->get('blocks')) {
            array_walk_recursive($blocks, function(&$block, $key) {
                if (str_contains($key, '.md') || $key === 'body') {
                    $block = $this->markdown->parse($block);
                }

                return $block;
            });

            $page->put('blocks', $blocks);
        }

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
     * @param                                $file
     * @param \Illuminate\Support\Collection $page
     *
     * @return string
     */
    protected function inferUri($file, Collection $page)
    {
        if ($uri = $page->get('uri')) {
            return '/' . trim($uri, '/');
        }

        return '/' . str_replace('.yml', '.html', $file->getRelativePathname());
    }

    /**
     * @param \Illuminate\Support\Collection $page
     */
    protected function transformMarkdown(Collection $page)
    {
        $page->each(function ($field, $key) use ($page) {
            if (str_contains($key, '.md') || $key === 'body') {
                $page->put(
                    str_replace('.md', '', $key),
                    $this->markdown->parse($field)
                );
            }
        });
    }
}