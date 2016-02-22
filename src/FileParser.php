<?php
namespace Phabric;

use Illuminate\Support\Collection;
use Parsedown;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Parser as Yaml;
use Twig_Environment;

trait FileParser
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

    /**
     * @param \Symfony\Component\Finder\SplFileInfo $file
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public function parseFile(SplFileInfo $file)
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
        $page->put('_meta', [
            'extension' => $file->getExtension(),
            'path' => $file->getPath(),
            'pathname' => $file->getPathname(),
            'relative_path' => $file->getRelativePath(),
            'relative_pathname' => $file->getRelativePathname(),
            'raw' => $file->getContents(),
        ]);

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
     * @param Collection $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @param Parsedown $markdown
     */
    public function setMarkdown($markdown)
    {
        $this->markdown = $markdown;
    }

    /**
     * @param Twig_Environment $twig
     */
    public function setTwig($twig)
    {
        $this->twig = $twig;
    }

    /**
     * @param Yaml $yaml
     */
    public function setYaml($yaml)
    {
        $this->yaml = $yaml;
    }
}