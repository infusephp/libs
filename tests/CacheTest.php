<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.20
 * @copyright 2013 Jared King
 * @license MIT
 */

use infuse\Cache;

class CacheTest extends \PHPUnit_Framework_TestCase
{
	static $cache;

	public static function setUpBeforeClass()
	{
		self::$cache = new Cache( array( 'local' ), array( 'local' => array( 'prefix' => 'test' ) ) );
	}

	protected function assertPreConditions()
	{
		$this->assertInstanceOf( '\\infuse\\Cache', self::$cache );
	}

	public function testConstruct()
	{
		$cache = new Cache( array( 'local' ), array( 'local' => array( 'prefix' => 'test' ) ) );
		$this->assertInstanceOf( '\\infuse\\Cache', $cache );

		$cache = new Cache();
		$this->assertInstanceOf( '\\infuse\\Cache', $cache );		
	}

	public function testSet()
	{
		$this->assertTrue( self::$cache->set( 'test.key', 100 ) );
		$this->assertTrue( self::$cache->set( 'test.key.2', 101, 500 ) );
	}

	/**
	 * @depends testSet
	 */
	public function testGet()
	{
		$this->assertEquals( 100, self::$cache->get( 'test.key' ) );

		$expected = array( 'test.key' => 100, 'test.key.2' => 101 );
		$this->assertEquals( $expected, self::$cache->get( array( 'test.key', 'test.key.2' ) ) );

		$expected = array( 'test.key.2' => 101 );
		$this->assertEquals( $expected, self::$cache->get( array( 'test.key.2' ), true ) );

		$this->assertNull( self::$cache->get( 'does.not.exist' ) );
	}

	/**
	 * @depends testSet
	 */
	public function testHas()
	{
		$this->assertTrue( self::$cache->has( 'test.key' ) );
		$this->assertFalse( self::$cache->has( 'does.not.exist' ) );
	}

	/**
	 * @depends testGet
	 */
	public function testIncrement()
	{
		$this->assertEquals( 101, self::$cache->increment( 'test.key' ) );
		$this->assertEquals( 101, self::$cache->get( 'test.key' ) );

		$this->assertEquals( 105, self::$cache->increment( 'test.key.2', 4 ) );
		$this->assertEquals( 105, self::$cache->get( 'test.key.2' ) );

		$this->assertFalse( self::$cache->increment( 'does.not.exist' ) );
	}

	/**
	 * @depends testGet
	 */
	public function testDecrement()
	{
		$this->assertEquals( 100, self::$cache->decrement( 'test.key' ) );
		$this->assertEquals( 100, self::$cache->get( 'test.key' ) );

		$this->assertEquals( 101, self::$cache->decrement( 'test.key.2', 4 ) );
		$this->assertEquals( 101, self::$cache->get( 'test.key.2' ) );

		$this->assertFalse( self::$cache->decrement( 'does.not.exist' ) );
	}

	/**
	 * @depends testGet
	 */
	public function testDelete()
	{
		$this->assertTrue( self::$cache->delete( 'test.key' ) );
		$this->assertNull( self::$cache->get( 'test.key' ) );

		$this->assertTrue( self::$cache->delete( 'does.not.exist' ) );

		$this->assertEquals( 101, self::$cache->get( 'test.key.2' ) );
	}

	/**
	 * @depends testGet
	 */
	public function testFib()
	{
		// generate a fibonacci sequence
		// memoize to cache
		// check for proper values

		$this->assertEquals( 13, fibonacci( 6, self::$cache ) );

		$this->assertEquals( 1, self::$cache->get( 'fib.1' ) );
		$this->assertEquals( 2, self::$cache->get( 'fib.2' ) );
		$this->assertEquals( 3, self::$cache->get( 'fib.3' ) );
		$this->assertEquals( 5, self::$cache->get( 'fib.4' ) );
		$this->assertEquals( 8, self::$cache->get( 'fib.5' ) );
		$this->assertEquals( 13, self::$cache->get( 'fib.6' ) );
	}

	/**
	 * @depends testGet
	 */
	public function testBogusStrategy()
	{
		$cache = new Cache( array( 'non_existent_strategy' ) );
		$this->assertInstanceOf( '\\infuse\\Cache', $cache );

		$this->assertFalse( $cache->set( 'test', 123 ) );
		$this->assertFalse( $cache->has( 'test' ) );
		$this->assertNull( $cache->get( 'test' ) );
	}
}

function fibonacci( $n, Cache $cache )
{
	
	if( !$val = $cache->get( "fib.$n" ) )
	{
		$val = ($n > 2) ? fibonacci( $n - 1, $cache ) + fibonacci( $n - 2, $cache ) : $n;
		$cache->set( "fib.$n", $val );
	}

	return $val;
}