<?php
namespace Phabric\Collection\Content;

use Phabric\Collection\Repository as RepositoryInterface;
use Phabric\Config;
use Symfony\Component\Finder\Finder;

final class Repository implements RepositoryInterface
{
    /**
     * @var \Phabric\Config
     */
    protected $config;

    /**
     * @var \Symfony\Component\Finder\Finder
     */
    protected $finder;

    /**
     * @var \Phabric\Collection\Content\Parser
     */
    protected $parser;

    /**
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public function collect()
    {
        $config = $this->config;
        $finder = $this->finder;
        $parser = $this->parser;

        $finder->files()->in($config->get('paths.content'))->name('*.yml');

        $content = collect([]);
        foreach ($finder as $file) {
            $page = $parser->parse($file);
            $content->put($page->get('uri'), $page);
        }

        return $content->sortByDesc('date');
    }

    /**
     * @param \Symfony\Component\Finder\Finder $finder
     */
    public function setFinder(Finder $finder)
    {
        $this->finder = $finder;
    }

    /**
     * @param \Phabric\Config $config
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param \Phabric\Collection\Content\Parser $parser
     */
    public function setParser(Parser $parser)
    {
        $this->parser = $parser;
    }
}