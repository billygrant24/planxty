<?php
namespace Planxty;

use Mni\FrontYAML\Bridge\Parsedown\ParsedownParser;
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
        $parser = $this->container['parser'];
        $twig = $this->container['twig'];

        $content = collect([]);

        // Parse all documents in the content directory
        $finder->files()->in($config->get('paths.content'))->name('*.md');
        foreach ($finder as $file) {
            $twigTemplate = twig_template_from_string($twig, file_get_contents($file->getPathName()));
            $parsedFile = $parser->parse($twigTemplate->render(compact('config')));

            $meta = $parsedFile->getYAML();
            $meta['body'] = $parsedFile->getContent();
            $meta['blocks'] = $this->parseBlocks($meta);

            if (isset($meta['uri'])) {
                $meta['uri'] = '/' . trim($meta['uri'], '/');
            } else {
                $meta['uri'] = '/' . str_replace('.md', '.html', $file->getRelativePathname());
            }

            $content->push($meta);
        }

        return $content;
    }

    /**
     * @param $layoutData
     *
     * @return mixed
     */
    protected function parseBlocks($layoutData)
    {
        $blocks = isset($layoutData['blocks']) ? $layoutData['blocks'] : [];
        $parsedown = new ParsedownParser();

        foreach ($blocks as $name => $block) {
            if (isset($block['body'])) {
                $blocks[$name]['body'] = $parsedown->parse($block['body']);
            }
        }

        return $blocks;
    }
}