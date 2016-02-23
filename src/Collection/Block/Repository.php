<?php
namespace Phabric\Collection\Block;

use Phabric\Collection\Parser;
use Phabric\Collection\Repository as RepositoryInterface;
use Phabric\Collection\SelfParsingRepository;
use Symfony\Component\Finder\Finder;

final class Repository implements RepositoryInterface, SelfParsingRepository
{
    use Parser;

    /**
     * @var \Symfony\Component\Finder\Finder
     */
    protected $finder;

    /**
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public function collect()
    {
        $blocks = collect([]);

        // Make sure we have specified a blocks directory
        if ($this->config->has('paths.blocks')) {
            $this->finder->files()->in($this->config->get('paths.blocks'))->name('*.yml');

            foreach ($this->finder as $file) {
                $blocks->put(
                    $file->getBasename('.yml'),
                    $this->parse($file)
                );
            }
        }

        return $blocks;
    }

    /**
     * @param \Symfony\Component\Finder\Finder $finder
     */
    public function setFinder(Finder $finder)
    {
        $this->finder = $finder;
    }
}