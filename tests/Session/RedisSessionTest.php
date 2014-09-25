<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.2.0
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\Session\Redis as RedisSession;
use Pimple\Container;

class RedisSessionTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        // prevent php session functions from sending headers
        ini_set( 'session.use_cookies', 0 );
        session_cache_limiter( '' );
    }

    public function assertPostConditions()
    {
        session_write_close();
    }

    public function testStart()
    {
        $app = new Container();
        $redis = Mockery::mock( 'Predis\Client' );
        $redis->shouldReceive( 'setex' );
        $redis->shouldReceive( 'get' );
        $app[ 'redis' ] = $redis;

        $this->assertInstanceOf( '\\infuse\\Session\\Redis', RedisSession::start( $app, 'test:' ) );
    }

    public function testRead()
    {
        $app = new Container();
        $redis = Mockery::mock( 'Predis\Client' );
        $redis->shouldReceive( 'get' )->with( 'test:php.session.blah' )->andReturn( 'ok' )->once();
        $app[ 'redis' ] = $redis;

        $session = new RedisSession( $app, 'test:' );

        $this->assertEquals( 'ok', $session->read( 'blah' ) );
    }

    public function testWrite()
    {
        $ttl = ini_get( 'session.gc_maxlifetime' );

        $app = new Container();
        $redis = Mockery::mock( 'Predis\Client' );
        $redis->shouldReceive( 'setex' )->with( 'php.session.blah', $ttl, 'data' )->once();
        $app[ 'redis' ] = $redis;

        $session = new RedisSession( $app );

        $session->write( 'blah', 'data' );
    }

    public function testDestroy()
    {
        $ttl = ini_get( 'session.gc_maxlifetime' );

        $app = new Container();
        $redis = Mockery::mock( 'Predis\Client' );
        $redis->shouldReceive( 'del' )->with( 'test:php.session.blah' )->once();
        $app[ 'redis' ] = $redis;

        $session = new RedisSession( $app, 'test:' );

        $session->destroy( 'blah' );
    }

    public function testOpen()
    {
        $app = new Container();

        $session = new RedisSession( $app );
        $this->assertTrue( $session->open( 'test', 'name' ) );
    }

    public function testClose()
    {
        $app = new Container();

        $session = new RedisSession( $app );
        $this->assertTrue( $session->close() );
    }

    public function testGC()
    {
        $app = new Container();

        $session = new RedisSession( $app );
        $this->assertTrue( $session->gc( 100 ) );
    }
}
