<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @copyright 2015 Jared King
 * @license MIT
 */

use infuse\Cache\RedisStrategy;
use Pimple\Container;

class RedisStrategyTest extends \PHPUnit_Framework_TestCase
{
    public static $strategy;

    public static function setUpBeforeClass()
    {
        self::$strategy = new RedisStrategy('test:');
    }

    public function testConstruct()
    {
        $stategy = new RedisStrategy('test:');
    }

    public function testInit()
    {
        $app = new Container();
        $app[ 'redis' ] = true;
        RedisStrategy::inject($app);

        $this->assertInstanceOf('\\infuse\\Cache\\RedisStrategy', RedisStrategy::init(''));
    }

    public function testSet()
    {
        $app = new Container();
        $redis = Mockery::mock('Predis\Client');
        $redis->shouldReceive('set')->with('test:test.key', 100)->andReturn(true)->once();
        $redis->shouldReceive('setex')->with('test:test.key.2', 500, 101)->andReturn(true)->once();
        $app[ 'redis' ] = $redis;
        RedisStrategy::inject($app);

        $this->assertTrue(self::$strategy->set('test.key', 100, 0));
        $this->assertTrue(self::$strategy->set('test.key.2', 101, 500));
    }

    public function testGet()
    {
        $app = new Container();
        $redis = Mockery::mock('Predis\Client');
        $redis->shouldReceive('mget')->with([ 'test:test.key', 'test:test.key.2' ])->andReturn([ 100, 101 ])->once();
        $app[ 'redis' ] = $redis;
        RedisStrategy::inject($app);

        $expected = [ 'test.key' => 100, 'test.key.2' => 101 ];
        $this->assertEquals($expected, self::$strategy->get([ 'test.key', 'test.key.2' ]));
    }

    public function testGetNull()
    {
        $app = new Container();
        $redis = Mockery::mock('Predis\Client');
        $redis->shouldReceive('mget')->with([ 'test:does.not.exist' ])->andReturn([ null ])->once();
        $redis->shouldReceive('exists')->with('test:does.not.exist')->andReturn(false)->once();
        $app[ 'redis' ] = $redis;
        RedisStrategy::inject($app);

        $this->assertCount(0, self::$strategy->get([ 'does.not.exist' ]));
    }

    public function testHas()
    {
        $app = new Container();
        $redis = Mockery::mock('Predis\Client');
        $redis->shouldReceive('exists')->with('test:test.has')->andReturn(true)->once();
        $app[ 'redis' ] = $redis;
        RedisStrategy::inject($app);

        $this->assertTrue(self::$strategy->has('test.has'));
    }

    public function testIncrement()
    {
        $app = new Container();
        $redis = Mockery::mock('Predis\Client');
        $redis->shouldReceive('incrby')->with('test:test.inc', 4)->andReturn(104)->once();
        $app[ 'redis' ] = $redis;
        RedisStrategy::inject($app);

        $this->assertEquals(104, self::$strategy->increment('test.inc', 4));
    }

    public function testDecrement()
    {
        $app = new Container();
        $redis = Mockery::mock('Predis\Client');
        $redis->shouldReceive('decrby')->with('test:test.dec', 4)->andReturn(100)->once();
        $app[ 'redis' ] = $redis;
        RedisStrategy::inject($app);

        $this->assertEquals(100, self::$strategy->decrement('test.dec', 4));
    }

    public function testDelete()
    {
        $app = new Container();
        $redis = Mockery::mock('Predis\Client');
        $redis->shouldReceive('del')->with('test:test.delete')->andReturn(true)->once();
        $app[ 'redis' ] = $redis;
        RedisStrategy::inject($app);

        $this->assertTrue(self::$strategy->delete('test.delete'));
    }
}
