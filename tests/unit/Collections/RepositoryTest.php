<?php
namespace Phabric\Tests\Collections;

use Phabric\Config;
use Phabric\ContainerFactory;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Yaml\Yaml;

class RepositoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Phabric\ContainerFactory
     */
    protected $container;

    /**
     * @var string
     */
    protected $workspace;

    protected function setUp()
    {
        $this->workspace = getcwd() . '/tests/fixtures';

        $this->container = ContainerFactory::newInstance();

        $this->container->extend('config', function ($config, $c) {
            $configFile = file_get_contents($this->workspace . '/config.yml');

            return new Config(Yaml::parse($configFile));
        });

        $this->container['config']->set('paths.blocks', $this->workspace . '/blocks');
        $this->container['config']->set('paths.content', $this->workspace . '/content');
    }

    /**
     * @test
     */
    public function itCollectsBlocks()
    {
        $parsedBlocks = $this->container['block_collector'];

        $this->assertEquals(2, $parsedBlocks->count());
    }

    /**
     * @test
     */
    public function itCollectsContent()
    {
        $parsedContent = $this->container['content_collector'];

        $this->assertEquals(3, $parsedContent->count());

        $parsedContent->each(function ($item) {
            $this->assertArrayHasKey('body', $item);
            $this->assertArrayNotHasKey('body.md', $item);
            $this->assertEquals('<h1>Hello World!</h1>', $item['body']);
        });
    }
}