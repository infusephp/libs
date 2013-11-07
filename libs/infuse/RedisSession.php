<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.16.3
 * @copyright 2013 Jared King
 * @license MIT
 */

namespace infuse;

class RedisSession
{
	private $prefix;
	
	static function start( $redis_conf = array(), $prefix = '' )
	{
		$obj = new self( $redis_conf, $prefix );

		session_set_save_handler(
			array( $obj, 'open' ),
			array( $obj, 'close' ),
			array( $obj, 'read' ),
			array( $obj, 'write' ),
			array( $obj, 'destroy' ),
			array( $obj, 'gc' ) );

		session_start();

		return $obj;
	}
	
	function __construct( $redis_conf, $prefix )
	{
		$this->prefix = 'php.session.' . $prefix . '.';

		$this->redis = new \Predis\Client( $redis_conf );
	}
	
	/**
	 * Loads a session into $_SESSION given a session id
	 *
	 * @param string $id session id
	 */
	function read( $id )
	{
		return $this->redis->get( $this->prefix . $id );
	}
	
	/**
	 * Persists data from the session
	 *
	 * @param string $id session id
	 * @param string $data data
	 */
	function write( $id, $data )
	{
		$id = $this->prefix . $id;
		$ttl = ini_get( 'session.gc_maxlifetime' );
		
		$this->redis->pipeline( function( $r ) use ( &$id, &$ttl, &$data )
		{
			$r->setex( $id, $ttl, $data );
		} );
	}	
	
	/**
	 * Destroys a session
	 *
	 * @param string $id session id
	 */
	function destroy( $id )
	{
		$this->redis->del( $this->prefix . $id );
	}
	
	/**
	 * These functions are all noops for various reasons...
	 * open() and close() have no practical meaning in terms of non-shared Redis connections
	 * Garbage collection is handled by Redis with ttls.
	 */
	function open( $path, $name ) {}
	function close() {}
	function gc( $age ) {}
}

// the following prevents unexpected effects when using objects as save handlers
register_shutdown_function('session_write_close');