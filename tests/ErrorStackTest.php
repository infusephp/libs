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

	public function testAdd()
	{

	}

	public function testErrors()
	{

	}

	public function testMessages()
	{
		
	}

	public function testSetContext()
	{

	}

	public function testClearContext()
	{

	}
}