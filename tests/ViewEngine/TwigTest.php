<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Infuse\ViewEngine\Twig;
use Infuse\View;

class TwigViewEngineTest extends PHPUnit_Framework_TestCase
{
    public static $engine;

    public static function setUpBeforeClass()
    {
        self::$engine = new Twig(__DIR__.'/views');
    }

    public function testViewsDir()
    {
        $engine = new Twig('test');
        $this->assertEquals('test', $engine->getViewsDir());
    }

    public function testAssetUrl()
    {
        $this->assertEquals(self::$engine, self::$engine->setAssetBaseUrl('http://localhost'));
        $this->assertEquals('http://localhost/test', self::$engine->asset_url('/test'));

        $this->assertEquals(self::$engine, self::$engine->setAssetMapFile(__DIR__.'/static_assets.json'));

        $this->assertEquals('http://localhost/img/logo.2v80s34k.png', self::$engine->asset_url('/img/logo.png'));
        $this->assertEquals('http://localhost/test', self::$engine->asset_url('/test'));
    }

    public function testGlobalParameters()
    {
        self::$engine->setGlobalParameters(['test' => true, 'test2' => 'blah']);
        self::$engine->setGlobalParameters(['test' => 'overwrite']);

        $this->assertEquals(['test' => 'overwrite', 'test2' => 'blah'], self::$engine->getGlobalParameters());
    }

    public function testTwig()
    {
        $engine = new Twig(__DIR__.'/views');
        $this->assertInstanceOf('Twig_Environment', $engine->twig());
    }

    public function testRenderView()
    {
        $view = new View('test.html', [
            'to' => 'world',
            'escape' => '<script>console.log("hello");</script>',
            'object' => new stdClass(),
            'array' => [],
        ]);

        self::$engine->setGlobalParameters([
            'to' => 'should_be_overwritten',
            'greeting' => 'Hello', ]);

        $this->assertEquals("Hello, world!\n&lt;script&gt;console.log(&quot;hello&quot;);&lt;/script&gt;", self::$engine->renderView($view));
    }
}
