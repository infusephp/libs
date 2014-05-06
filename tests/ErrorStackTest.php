<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.18.1
 * @copyright 2013 Jared King
 * @license MIT
 */

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

	public function testStack()
	{
		$this->assertInstanceOf( '\\infuse\\ErrorStack', ErrorStack::stack() );
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
			'function' => 'create',
			'params' => array(
				'field' => 'username' ) );

		$this->assertTrue( ErrorStack::add( $error2 ) );

		$this->assertFalse( self::$stack->push( array(
			'message' => 'Username is invalid',
			'context' => 'user.create',
			'class' => 'User',
			'function' => 'create' ) ) );

		$this->assertTrue( ErrorStack::add( 'some_error' ) );
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
			'params' => array(
				'field' => 'username' ) );

		$expected3 = array(
			'error' => 'some_error',
			'message' => 'some_error',
			'context' => '',
			'params' => array(),
			'class' => null,
			'function' => null );

		$errors = self::$stack->errors();
		$this->assertEquals( 3, count( $errors ) );
		$this->assertEquals( array( $expected1, $expected2, $expected3 ), $errors );

		$errors = self::$stack->errors( 'user.create' );
		$this->assertEquals( 1, count( $errors ) );
		$this->assertEquals( array( $expected2 ), $errors );
	}

	/**
	 * @depends testPush
	 */
	public function testMessages()
	{
		$expected = array(
			'Something is wrong',
			'Username is invalid',
			'some_error' );

		$messages = self::$stack->messages();
		$this->assertEquals( 3, count( $messages ) );
		$this->assertEquals( $expected, $messages );

		$expected = array( 'Username is invalid' );

		$messages = self::$stack->messages( 'user.create' );
		$this->assertEquals( 1, count( $messages ) );
		$this->assertEquals( $expected, $messages );
	}

	/**
	 * @depends testPush
	 */
	public function testFind()
	{
		$expected = array(
			'error' => 'username_invalid',
			'message' => 'Username is invalid',
			'context' => 'user.create',
			'class' => 'User',
			'function' => 'create',
			'params' => array(
				'field' => 'username' ) );

		$this->assertEquals( $expected, self::$stack->find( 'username' ) );
		$this->assertEquals( $expected, self::$stack->find( 'username', 'field' ) );

		$this->assertFalse( self::$stack->find( 'non-existent' ) );
	}

	/**
	 * @depends testPush
	 */
	public function testHas()
	{
		$this->assertTrue( self::$stack->has( 'username' ) );
		$this->assertTrue( self::$stack->has( 'username', 'field' ) );

		$this->assertFalse( self::$stack->has( 'non-existent' ) );
		$this->assertFalse( self::$stack->has( 'username', 'something' ) );
	}

	/**
	 * @depends testErrors
	 * @depends testMessages
	 */
	public function testSetCurrentContext()
	{
		self::$stack->setCurrentContext( 'test.context' );

		self::$stack->push( array( 'error' => 'test_error' ) );

		$expected = array(
			'error' => 'test_error',
			'context' => 'test.context',
			'params' => array(),
			'message' => 'test_error' );
		$this->assertEquals( array( $expected ), self::$stack->errors( 'test.context' ) );
	}

	/**
	 * @depends testErrors
	 * @depends testMessages
	 */
	public function testClearCurrentContext()
	{
		self::$stack->clearCurrentContext();

		self::$stack->push( array( 'error' => 'test_error' ) );

		$expected = array(
			'error' => 'test_error',
			'context' => '',
			'params' => array(),
			'message' => 'test_error' );
		$errors = self::$stack->errors( '' );
		$this->assertTrue( in_array( $expected, $errors ) );
	}
}