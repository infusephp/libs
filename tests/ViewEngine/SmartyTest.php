<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Infuse\ViewEngine\Smarty;
use Infuse\View;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class SmartyViewEngineTest extends MockeryTestCase
{
    public static $engine;

    public static function setUpBeforeClass()
    {
        self::$engine = new Smarty(__DIR__.'/views');
    }

    public function testViewsDir()
    {
        $engine = new Smarty('test');
        $this->assertEquals('test', $engine->getViewsDir());
    }

    public function testCompileDir()
    {
        $engine = new Smarty('', 'test');
        $this->assertEquals('test', $engine->getCompileDir());
    }

    public function testCacheDir()
    {
        $engine = new Smarty('', '', 'test');
        $this->assertEquals('test', $engine->getCacheDir());
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

    public function testGetSmarty()
    {
        $engine = new Smarty('view', 'compile', 'cache');
        $this->assertInstanceOf('Smarty', $engine->getSmarty());
    }

    public function testRenderView()
    {
        $view = new View('test', [
            'to' => 'world',
            'escape' => '<script>console.log("hello");</script>',
            'object' => new stdClass(),
            'array' => [],
        ]);

        self::$engine->setGlobalParameters([
            'to' => 'should_be_overwritten',
            'greeting' => 'Hello', ]);

        $this->assertEquals("Hello, world!\n&amp;lt;script&amp;gt;console.log(&amp;quot;hello&amp;quot;);&amp;lt;/script&amp;gt;", self::$engine->renderView($view));
    }
}
