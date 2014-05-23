<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.21
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse\Session;

class Redis implements \SessionHandlerInterface
{
	private $prefix;
	
	static function start( array $redisConf = [], $prefix = '' )
	{
		$obj = new self( $redisConf, $prefix );

		session_set_save_handler( $obj, true );
		session_start();

		return $obj;
	}
	
	function __construct( array $redisConf, $prefix )
	{
		$this->prefix = 'php.session.' . $prefix . '.';
		$this->redis = new \Predis\Client( $redisConf );
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
	function open( $path, $name ) { return true; }
	function close() { return true; }
	function gc( $age ) { return true; }
}

// the following prevents unexpected effects when using objects as save handlers
register_shutdown_function('session_write_close');