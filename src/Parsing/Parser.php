<?php
namespace Phabric\Parsing;

use Illuminate\Support\Collection;
use Parsedown;
use Phabric\Config;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Parser as Yaml;
use Twig_Environment;

trait Parser
{
    /**
     * @var \Phabric\Config
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

    /**
     * @param \Symfony\Component\Finder\SplFileInfo $file
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public function parse(SplFileInfo $file)
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

        // Populate some meta fields which describe the given resource
        $page->put('path', $file->getRelativePath());
        $page->put('pathname', $file->getRelativePathname());
        $page->put('real_path', $file->getPath());
        $page->put('real_pathname', $file->getPathname());

        // Transform markdown fields to HTML
        $transformedPage = $this->transformMarkdown($page);

        return $transformedPage;
    }

    /**
     * @param \Illuminate\Support\Collection $page
     * @return \Illuminate\Support\Collection
     */
    protected function transformMarkdown(Collection $page)
    {
        array_walk_recursive($page, function (&$field, $key) {
            if (str_contains($key, '.md')) {
                $field = $this->markdown->parse($field);
            }

            return $field;
        });

        $page = $this->replaceAnnotatedKeys($page);

        return new Collection($page);
    }

    /**
     * @param \Illuminate\Support\Collection|array $page
     * @return array
     */
    protected function replaceAnnotatedKeys($page)
    {
        $return = [];

        foreach ($page as $key => $value) {
            $key = str_replace('.md', '', $key);

            if (is_array($value)) {
                $value = $this->replaceAnnotatedKeys($value);
            }

            $return[$key] = $value;
        }

        return $return;
    }

    /**
     * @param \Phabric\Config $config
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param Parsedown $markdown
     */
    public function setMarkdown(Parsedown $markdown)
    {
        $this->markdown = $markdown;
    }

    /**
     * @param Twig_Environment $twig
     */
    public function setTwig(Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @param Yaml $yaml
     */
    public function setYaml(Yaml $yaml)
    {
        $this->yaml = $yaml;
    }
}