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
	static $engine;

	static function setUpBeforeClass()
	{
		self::$engine = new ViewEngine;
	}

	function testSmarty()
	{
		$this->assertInstanceOf( 'Smarty', self::$engine->smarty() );
	}

	function testAssetUrl()
	{
		$engine = new ViewEngine( [
			'assetsBaseUrl' => 'http://localhost' ] );
		$this->assertEquals( 'http://localhost/test', $engine->asset_url( '/test' ) );
	}

	function testRenderSmarty()
	{
		$engine = new ViewEngine( [ 'engine' => 'smarty' ] );
		$engine->assignData( [ 'param1' => 'hello', 'param2' => 'world' ] );

        // TODO
	}

	function testRenderPhp()
	{
		$engine = new ViewEngine( [ 'engine' => 'php' ] );
		$engine->assignData( [ 'param1' => 'hello', 'param2' => 'world' ] );

        // TODO
	}

	function testCompileLess()
	{
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
	}

	function testCompileJs()
	{
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
	}

	function testCompileAngularTemplates()
	{
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
	}
}