<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.23
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\Cache;

class CacheTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$cache = new Cache( [ CACHE_STRATEGY_LOCAL ], 'test' );
		$this->assertInstanceOf( '\\infuse\\Cache', $cache );
		$this->assertInstanceOf( '\\infuse\\Cache\\LocalStrategy', $cache->strategy() );

		$cache = new Cache;
		$this->assertInstanceOf( '\\infuse\\Cache', $cache );
		$this->assertInstanceOf( '\\infuse\\Cache\\LocalStrategy', $cache->strategy() );
	}

	public function testBogusStrategy()
	{
		$cache = new Cache( [ 'non_existent_strategy' ] );
		$this->assertInstanceOf( '\\infuse\\Cache\\LocalStrategy', $cache->strategy() );
	}

	public function testFailedStrategy()
	{
		$c = new Pimple\Container;

		$cache = new Cache( [ '\\infuse\\Cache\\RedisStrategy', '\\infuse\\Cache\\MemcacheStrategy' ], '', $c );
		$this->assertInstanceOf( '\\infuse\\Cache\\LocalStrategy', $cache->strategy() );
	}

	public function testGetAndSetFib()
	{
		// generate a fibonacci sequence
		// memoize to cache
		// check for proper values

		$cache = new Cache;

		$this->assertEquals( 13, fibonacci( 6, $cache ) );

		$this->assertEquals( 1, $cache->get( 'fib.1' ) );
		$this->assertEquals( 2, $cache->get( 'fib.2' ) );
		$this->assertEquals( 3, $cache->get( 'fib.3' ) );
		$this->assertEquals( 5, $cache->get( 'fib.4' ) );
		$this->assertEquals( 8, $cache->get( 'fib.5' ) );
		$this->assertEquals( 13, $cache->get( 'fib.6' ) );
	}

	public function testGetAndSetJson()
	{
		$cache = new Cache;

		$json = [ 'test' => true, 'test2' => [ 1, 2, 3 ] ];

		$cache->set( 'json', $json );

		$expected = json_encode( $json );
		$this->assertEquals( $expected, $cache->get( 'json' ) );
	}

	public function testHas()
	{
		$cache = new Cache;

		$cache->set( 'test.has', true );
		$this->assertTrue( $cache->has( 'test.has' ) );
		$this->assertFalse( $cache->has( 'does_not_exist' ) );
	}

	public function testIncrement()
	{
		$cache = new Cache;

		$cache->set( 'inc.key', 0 );
		$this->assertEquals( 1, $cache->increment( 'inc.key' ) );
		$this->assertEquals( 1, $cache->get( 'inc.key' ) );
	}

	public function testDecrement()
	{
		$cache = new Cache;

		$cache->set( 'dec.key', 1 );
		$this->assertEquals( 0, $cache->decrement( 'dec.key' ) );
		$this->assertEquals( 0, $cache->get( 'dec.key' ) );
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