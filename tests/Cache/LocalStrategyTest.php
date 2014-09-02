<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.25
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\Cache\LocalStrategy;
use Pimple\Container;

class LocalStrategyTest extends \PHPUnit_Framework_TestCase
{
    public static $strategy;

    public static function setUpBeforeClass()
    {
        self::$strategy = new LocalStrategy( 'test:' );
    }

    public function testConstruct()
    {
        $stategy = new LocalStrategy( 'test:' );
    }

    public function testInit()
    {
        $app = new Container();
        LocalStrategy::inject( $app );

        $this->assertInstanceOf( '\\infuse\\Cache\\LocalStrategy', LocalStrategy::init( '' ) );
    }

    public function testSet()
    {
        $this->assertTrue( self::$strategy->set( 'test.key', 100, 0 ) );
        $this->assertTrue( self::$strategy->set( 'test.key.2', 101, 500 ) );
    }

    /**
	 * @depends testSet
	 */
    public function testGet()
    {
        $this->assertEquals( [ 'test.key' => 100 ], self::$strategy->get( [ 'test.key' ] ) );

        $expected = [ 'test.key' => 100, 'test.key.2' => 101 ];
        $this->assertEquals( $expected, self::$strategy->get( [ 'test.key', 'test.key.2' ] ) );

        $expected = [ 'test.key.2' => 101 ];
        $this->assertEquals( $expected, self::$strategy->get( [ 'test.key.2' ], true ) );

        $this->assertCount( 0, self::$strategy->get( [ 'does.not.exist' ] ) );
    }

    /**
	 * @depends testSet
	 */
    public function testHas()
    {
        $this->assertTrue( self::$strategy->has( 'test.key' ) );
        $this->assertFalse( self::$strategy->has( 'does.not.exist' ) );
    }

    /**
	 * @depends testGet
	 */
    public function testIncrement()
    {
        $this->assertEquals( 104, self::$strategy->increment( 'test.key', 4 ) );
        $this->assertEquals( [ 'test.key' => 104 ], self::$strategy->get( [ 'test.key' ] ) );

        $this->assertFalse( self::$strategy->increment( 'does.not.exist', 1 ) );
    }

    /**
	 * @depends testGet
	 */
    public function testDecrement()
    {
        $this->assertEquals( 100, self::$strategy->decrement( 'test.key', 4 ) );
        $this->assertEquals( [ 'test.key' => 100 ], self::$strategy->get( [ 'test.key' ] ) );

        $this->assertFalse( self::$strategy->decrement( 'does.not.exist', 1 ) );
    }

    /**
	 * @depends testGet
	 */
    public function testDelete()
    {
        $this->assertTrue( self::$strategy->delete( 'test.key' ) );
        $this->assertCount( 0, self::$strategy->get( [ 'test.key' ] ) );

        $this->assertTrue( self::$strategy->delete( 'does.not.exist' ) );

        $this->assertEquals( [ 'test.key.2' => 101 ], self::$strategy->get( [ 'test.key.2' ] ) );
    }
}
