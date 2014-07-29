<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.23
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse\Session;

use Pimple\Container;

class Redis implements \SessionHandlerInterface
{
	private $app;
	private $prefix;
	
	static function start( Container $app, $prefix = '' )
	{
		$obj = new self( $app, $prefix );

		session_set_save_handler( $obj, true );
		session_start();

		return $obj;
	}
	
	function __construct( Container $app, $prefix = '' )
	{
		$this->app = $app;
		$this->prefix = $prefix . 'php.session.';
	}
	
	/**
	 * Loads a session into $_SESSION given a session id
	 *
	 * @param string $id session id
	 */
	function read( $id )
	{
		return $this->app[ 'redis' ]->get( $this->prefix . $id );
	}
	
	/**
	 * Persists data from the session
	 *
	 * @param string $id session id
	 * @param string $data data
	 */
	function write( $id, $data )
	{
		$ttl = ini_get( 'session.gc_maxlifetime' );
		
		$this->app[ 'redis' ]->setex( $this->prefix . $id, $ttl, $data );
	}
	
	/**
	 * Destroys a session
	 *
	 * @param string $id session id
	 */
	function destroy( $id )
	{
		$this->app[ 'redis' ]->del( $this->prefix . $id );
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