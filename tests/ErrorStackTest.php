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
		$error1 = array(
			'error' => 'some_error',
			'message' => 'Something is wrong' );

		$this->assertTrue( self::$stack->push( $error1 ) );

		$error2 = array(
			'error' => 'username_invalid',
			'message' => 'Username is invalid',
			'context' => 'user.create',
			'class' => 'User',
			'function' => 'create' );

		$this->assertTrue( self::$stack->push( $error2 ) );

		$this->assertFalse( self::$stack->push( array(
			'message' => 'Username is invalid',
			'context' => 'user.create',
			'class' => 'User',
			'function' => 'create' ) ) );
	}

	/**
	 * @depends testPush
	 */
	public function testErrors()
	{
		$expected1 = array(
			'error' => 'some_error',
			'message' => 'Something is wrong',
			'context' => '',
			'params' => array() );

		$expected2 = array(
			'error' => 'username_invalid',
			'message' => 'Username is invalid',
			'context' => 'user.create',
			'class' => 'User',
			'function' => 'create',
			'params' => array() );

		$errors = self::$stack->errors();

		$this->assertEquals( 2, count( $errors ) );
		$this->assertEquals( array( $expected1, $expected2 ), $errors );
	}

	/**
	 * @depends testPush
	 */
	public function testMessages()
	{
		$expected = array(
			'Something is wrong.',
			'Username is invalid.' );

		$messages = self::$stack->messages();

		$this->assertEquals( 2, count( $messages ) );
		$this->assertEquals( $expected, $messages );
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
	 * @depends testErrors
	 * @depends testMessages
	 */
	public function testSetCurrentContext()
	{

	}

	/**
	 * @depends testErrors
	 * @depends testMessages
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