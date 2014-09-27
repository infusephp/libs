<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.2.1
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\ErrorStack;
use infuse\Locale;
use Pimple\Container;

class ErrorStackTest extends \PHPUnit_Framework_TestCase
{
    public static $app;
    public static $stack;

    public static function setUpBeforeClass()
    {
        self::$app = new Container();
        self::$app[ 'locale' ] = new Locale();
        self::$stack = new ErrorStack( self::$app );
    }

    public function testConstruct()
    {
        $stack = new ErrorStack( self::$app );
    }

    public function testPush()
    {
        $error1 = [
            'error' => 'some_error',
            'message' => 'Something is wrong' ];

        $this->assertTrue( self::$stack->push( $error1 ) );

        $error2 = [
            'error' => 'username_invalid',
            'message' => 'Username is invalid',
            'context' => 'user.create',
            'params' => [
                'field' => 'username' ] ];

        $this->assertTrue( self::$stack->push( $error2 ) );

        $this->assertFalse( self::$stack->push( [
            'message' => 'Username is invalid',
            'context' => 'user.create' ] ) );

        $this->assertTrue( self::$stack->push( [ 'error' => 'some_error' ] ) );
    }

    /**
	 * @depends testPush
	 */
    public function testErrors()
    {
        $expected1 = [
            'error' => 'some_error',
            'message' => 'Something is wrong',
            'context' => '',
            'params' => [] ];

        $expected2 = [
            'error' => 'username_invalid',
            'message' => 'Username is invalid',
            'context' => 'user.create',
            'params' => [
                'field' => 'username' ] ];

        $expected3 = [
            'error' => 'some_error',
            'message' => 'some_error',
            'context' => '',
            'params' => [] ];

        $errors = self::$stack->errors();
        $this->assertEquals( 3, count( $errors ) );
        $this->assertEquals( [ $expected1, $expected2, $expected3 ], $errors );

        $errors = self::$stack->errors( 'user.create' );
        $this->assertEquals( 1, count( $errors ) );
        $this->assertEquals( [ $expected2 ], $errors );
    }

    /**
	 * @depends testPush
	 */
    public function testMessages()
    {
        $expected = [
            'Something is wrong',
            'Username is invalid',
            'some_error' ];

        $messages = self::$stack->messages();
        $this->assertEquals( 3, count( $messages ) );
        $this->assertEquals( $expected, $messages );

        $expected = [ 'Username is invalid' ];

        $messages = self::$stack->messages( 'user.create' );
        $this->assertEquals( 1, count( $messages ) );
        $this->assertEquals( $expected, $messages );
    }

    /**
	 * @depends testPush
	 */
    public function testFind()
    {
        $expected = [
            'error' => 'username_invalid',
            'message' => 'Username is invalid',
            'context' => 'user.create',
            'params' => [
                'field' => 'username' ] ];

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

        self::$stack->push( [ 'error' => 'test_error' ] );

        $expected = [
            'error' => 'test_error',
            'context' => 'test.context',
            'params' => [],
            'message' => 'test_error' ];
        $this->assertEquals( [ $expected ], self::$stack->errors( 'test.context' ) );
    }

    /**
	 * @depends testErrors
	 * @depends testMessages
	 */
    public function testClearCurrentContext()
    {
        self::$stack->clearCurrentContext();

        self::$stack->push( [ 'error' => 'test_error' ] );

        $expected = [
            'error' => 'test_error',
            'context' => '',
            'params' => [],
            'message' => 'test_error' ];
        $errors = self::$stack->errors( '' );
        $this->assertTrue( in_array( $expected, $errors ) );
    }

    /**
	 * @depends testErrors
	 */
    public function testClear()
    {
        self::$stack->clear();
        $this->assertCount( 0, self::$stack->errors() );
    }
}
