<?php
namespace Phabric\Tests\Collections;

use Phabric\Config;
use Phabric\Configuration\ConfigRepository;
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

            return new ConfigRepository(Yaml::parse($configFile));
        });

        $this->container['config']->set('paths.blocks', $this->workspace . '/blocks');
        $this->container['config']->set('paths.content', $this->workspace . '/content');
    }

    /**
     * @test
     */
    public function itCollectsBlocks()
    {
        $parsedBlocks = $this->container['blocks'];

        $this->assertEquals(2, $parsedBlocks->count());
        $this->assertArrayHasKey('block_1', $parsedBlocks);
        $this->assertArrayHasKey('block_2', $parsedBlocks);
    }

    /**
     * @test
     */
    public function itCollectsContent()
    {
        $parsedContent = $this->container['content']->scope('posts');

        $this->assertEquals(3, $parsedContent->count());
        $this->assertArrayHasKey('/content_1.html', $parsedContent);
        $this->assertArrayHasKey('/blog/mobile-development/hello-world.html', $parsedContent);
        $this->assertArrayHasKey('/our-team/nick-swan.html', $parsedContent);

        $parsedContent->each(function ($item) {
            $this->assertArrayHasKey('body', $item);
            $this->assertArrayNotHasKey('body.md', $item);
            $this->assertEquals('<h1>Hello World!</h1>', $item['body']);
        });
    }
}