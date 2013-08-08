<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.14.5
 * @copyright 2013 Jared King
 * @license MIT
	Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
	associated documentation files (the "Software"), to deal in the Software without restriction,
	including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
	and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so,
	subject to the following conditions:
	
	The above copyright notice and this permission notice shall be included in all copies or
	substantial portions of the Software.
	
	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT
	LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
	IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
	WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
	SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace infuse;

if(!class_exists('\Predis\Client'))
{
	require 'Predis/Autoloader.php';
	\Predis\Autoloader::register();
}

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