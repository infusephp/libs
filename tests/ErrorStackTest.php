<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.16.4
 * @copyright 2013 Jared King
 * @license MIT
 */

error_reporting( E_ALL | E_STRICT );
ini_set( 'display_errors', true );

require_once 'vendor/autoload.php';

use infuse\ErrorStack;

class ErrorStackTest extends \PHPUnit_Framework_TestCase
{
	static $stack;

	public static function setUpBeforeClass()
	{
		self::$stack = ErrorStack::stack();
	}

	protected function assertPreConditions()
	{
		$this->assertInstanceOf( '\\infuse\\ErrorStack', self::$stack );
	}

	public function testPush()
	{

	}

	/**
	 * @depends testPush
	 */
	public function testErrors()
	{

	}

	/**
	 * @depends testPush
	 */
	public function testMessages()
	{

	}

	/**
	 * @depends testPush
	 */
	public function testHas()
	{

	}

	/**
	 * @depends testPush
	 */
	public function testFind()
	{

	}

	/**
	 * @depends testPush
	 */
	public function testSetCurrentContext()
	{

	}

	/**
	 * @depends testPush
	 */
	public function testClearCurrentContext()
	{

	}

	/**
	 * @depends testErrors
	 */
	public function testDeprecated()
	{

	}
}