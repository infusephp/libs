<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.25
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\ViewEngine;

class ViewEngineTest extends \PHPUnit_Framework_TestCase
{
    public static $engine;

    public static function setUpBeforeClass()
    {
        self::$engine = new ViewEngine();
    }

    public function testSmarty()
    {
        $this->assertInstanceOf( 'Smarty', self::$engine->smarty() );
    }

    public function testAssetUrl()
    {
        $engine = new ViewEngine( [
            'assetsBaseUrl' => 'http://localhost' ] );
        $this->assertEquals( 'http://localhost/test', $engine->asset_url( '/test' ) );
    }

    public function testRenderSmarty()
    {
        $engine = new ViewEngine( [ 'engine' => 'smarty' ] );
        $engine->assignData( [ 'param1' => 'hello', 'param2' => 'world' ] );

        // TODO
    }

    public function testRenderPhp()
    {
        $engine = new ViewEngine( [ 'engine' => 'php' ] );
        $engine->assignData( [ 'param1' => 'hello', 'param2' => 'world' ] );

        // TODO
    }

    public function testCompileLess()
    {
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testCompileJs()
    {
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testCompileAngularTemplates()
    {
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
}
