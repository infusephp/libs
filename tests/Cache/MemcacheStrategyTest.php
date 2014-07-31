<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.24
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\Cache\MemcacheStrategy;
use Pimple\Container;

class MemcachestrategyTest extends \PHPUnit_Framework_TestCase
{
	static $strategy;

	public static function setUpBeforeClass()
	{
		self::$strategy = new MemcacheStrategy( 'test:' );
	}

	function testConstruct()
	{
		$stategy = new MemcacheStrategy( 'test:' ); 
	}

	function testInit()
	{
		$app = new Container;
		$app[ 'memcache' ] = true;
		MemcacheStrategy::inject( $app );

		$this->assertInstanceOf( '\\infuse\\Cache\\MemcacheStrategy', MemcacheStrategy::init( '' ) );
	}

	public function testSet()
	{
		$app = new Container;
		$memcache = Mockery::mock( 'Memcache' );
		$memcache->shouldReceive( 'set' )->with( 'test:test.key', 100, 0 )->andReturn( true )->once();
		$memcache->shouldReceive( 'set' )->with( 'test:test.key.2', 101, 500 )->andReturn( true )->once();
		$app[ 'memcache' ] = $memcache;
		MemcacheStrategy::inject( $app );

		$this->assertTrue( self::$strategy->set( 'test.key', 100, 0 ) );
		$this->assertTrue( self::$strategy->set( 'test.key.2', 101, 500 ) );
	}

	public function testGet()
	{
		$app = new Container;
		$memcache = Mockery::mock( 'Memcache' );
		$memcache->shouldReceive( 'get' )->with( [ 'test:test.key', 'test:test.key.2' ] )->andReturn( [ 'test.key' => 100, 'test.key.2' => 101 ] )->once();
		$memcache->shouldReceive( 'get' )->with( [ 'test:does.not.exist' ] )->andReturn( [] )->once();
		$app[ 'memcache' ] = $memcache;
		MemcacheStrategy::inject( $app );

		$expected = [ 'test.key' => 100, 'test.key.2' => 101 ];
		$this->assertEquals( $expected, self::$strategy->get( [ 'test.key', 'test.key.2' ] ) );

		$this->assertCount( 0, self::$strategy->get( [ 'does.not.exist' ] ) );
	}

	public function testHas()
	{
		$app = new Container;
		$memcache = Mockery::mock( 'Memcache' );
		$memcache->shouldReceive( 'set' )->with( 'test:test.has', null )->andReturn( false )->once();
		$app[ 'memcache' ] = $memcache;
		MemcacheStrategy::inject( $app );

		$this->assertTrue( self::$strategy->has( 'test.has' ) );
	}

	public function testHasNot()
	{
		$app = new Container;
		$memcache = Mockery::mock( 'Memcache' );
		$memcache->shouldReceive( 'set' )->with( 'test:test.has', null )->andReturn( true )->once();
		$memcache->shouldReceive( 'delete' )->with( 'test:test.has' )->once();
		$app[ 'memcache' ] = $memcache;
		MemcacheStrategy::inject( $app );

		$this->assertFalse( self::$strategy->has( 'test.has' ) );
	}

	public function testIncrement()
	{
		$app = new Container;
		$memcache = Mockery::mock( 'Memcache' );
		$memcache->shouldReceive( 'increment' )->with( 'test:test.inc', 4 )->andReturn( 104 )->once();
		$app[ 'memcache' ] = $memcache;
		MemcacheStrategy::inject( $app );

		$this->assertEquals( 104, self::$strategy->increment( 'test.inc', 4 ) );
	}

	public function testDecrement()
	{
		$app = new Container;
		$memcache = Mockery::mock( 'Memcache' );
		$memcache->shouldReceive( 'decrement' )->with( 'test:test.dec', 4 )->andReturn( 100 )->once();
		$app[ 'memcache' ] = $memcache;
		MemcacheStrategy::inject( $app );

		$this->assertEquals( 100, self::$strategy->decrement( 'test.dec', 4 ) );
	}

	public function testDelete()
	{
		$app = new Container;
		$memcache = Mockery::mock( 'Memcache' );
		$memcache->shouldReceive( 'delete' )->with( 'test:test.delete' )->andReturn( true )->once();
		$app[ 'memcache' ] = $memcache;
		MemcacheStrategy::inject( $app );

		$this->assertTrue( self::$strategy->delete( 'test.delete' ) );
	}
}