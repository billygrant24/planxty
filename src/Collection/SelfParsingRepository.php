<?php
namespace Phabric\Collection;

use Parsedown;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Parser as Yaml;
use Twig_Environment;

interface SelfParsingRepository
{
    /**
     * @param \Symfony\Component\Finder\SplFileInfo $file
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public function parse(SplFileInfo $file);

    /**
     * @param Parsedown $markdown
     */
    public function setMarkdown(Parsedown $markdown);

    /**
     * @param Twig_Environment $twig
     */
    public function setTwig(Twig_Environment $twig);

    /**
     * @param Yaml $yaml
     */
    public function setYaml(Yaml $yaml);
}