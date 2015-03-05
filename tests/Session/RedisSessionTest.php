<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace infuse\Session;

use infuse\Session\Redis as RedisSession;
use Mockery;
use Pimple\Container;

function session_set_save_handler($arg1, $arg2 = true)
{
    return RedisSessionTest::$mock ? RedisSessionTest::$mock->session_set_save_handler($arg1, $arg2) : \session_set_save_handler($arg1, $arg2);
}

class RedisSessionTest extends \PHPUnit_Framework_TestCase
{
    public static $mock;

    public function tearDown()
    {
        self::$mock = false;
    }

    public function testRegisterHandler()
    {
        $c = new Container();
        $session = new RedisSession($c);

        self::$mock = \Mockery::mock('php');
        self::$mock->shouldReceive('session_set_save_handler')->withArgs([$session, true])->andReturn(true)->once();

        $this->assertTrue(RedisSession::registerHandler($session));
    }

    public function testRead()
    {
        $app = new Container();
        $redis = Mockery::mock('Predis\Client');
        $redis->shouldReceive('get')->with('test:php.session.blah')->andReturn('ok')->once();
        $app[ 'redis' ] = $redis;

        $session = new RedisSession($app, 'test:');

        $this->assertEquals('ok', $session->read('blah'));
    }

    public function testWrite()
    {
        $ttl = ini_get('session.gc_maxlifetime');

        $app = new Container();
        $redis = Mockery::mock('Predis\Client');
        $redis->shouldReceive('setex')->with('php.session.blah', $ttl, 'data')->once();
        $app[ 'redis' ] = $redis;

        $session = new RedisSession($app);

        $session->write('blah', 'data');
    }

    public function testDestroy()
    {
        $ttl = ini_get('session.gc_maxlifetime');

        $app = new Container();
        $redis = Mockery::mock('Predis\Client');
        $redis->shouldReceive('del')->with('test:php.session.blah')->once();
        $app[ 'redis' ] = $redis;

        $session = new RedisSession($app, 'test:');

        $session->destroy('blah');
    }

    public function testOpen()
    {
        $app = new Container();

        $session = new RedisSession($app);
        $this->assertTrue($session->open('test', 'name'));
    }

    public function testClose()
    {
        $app = new Container();

        $session = new RedisSession($app);
        $this->assertTrue($session->close());
    }

    public function testGC()
    {
        $app = new Container();

        $session = new RedisSession($app);
        $this->assertTrue($session->gc(100));
    }
}
