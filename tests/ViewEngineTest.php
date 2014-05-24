<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.21.1
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\ViewEngine;

class ViewEngineTest extends \PHPUnit_Framework_TestCase
{
	static $engine;

	static function setUpBeforeClass()
	{
		self::$engine = ViewEngine::engine();
	}

	function testEngine()
	{
		$engine = ViewEngine::engine();
		$this->assertInstanceOf( '\\infuse\\ViewEngine', $engine );
	}

	function testSmarty()
	{
		$this->assertInstanceOf( 'Smarty', self::$engine->smarty() );
	}

	function testAssetUrl()
	{
		ViewEngine::configure( [
			'assetsBaseUrl' => 'http://localhost' ] );
		self::$engine = ViewEngine::engine();
		$this->assertEquals( 'http://localhost/test', self::$engine->asset_url( '/test' ) );
	}

	function testRenderSmarty()
	{
		ViewEngine::configure( [ 'engine' => 'smarty' ] );
		self::$engine = ViewEngine::engine();
		self::$engine->assignData( [ 'param1' => 'hello', 'param2' => 'world' ] );

        // TODO
	}

	function testRenderPhp()
	{
		ViewEngine::configure( [ 'engine' => 'php' ] );
		self::$engine = ViewEngine::engine();
		self::$engine->assignData( [ 'param1' => 'hello', 'param2' => 'world' ] );

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