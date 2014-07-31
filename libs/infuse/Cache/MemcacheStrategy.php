<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.24
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse\Cache;

use Pimple\Container;

class MemcacheStrategy
{
	private static $app;
	private $prefix;

	/**
	 * Attempts to use the caching strategy
	 *
	 * @param string $prefix cache prefix
	 * 
	 * @return object|false
	 */
	static function init( $prefix )
	{
		if( !self::$app->offsetExists( 'memcache' ) )
			return false;

		return new self( $prefix );
	}

	/**
	 * Injects a DI container
	 *
	 * @param Container $app
	 */
	static function inject( Container $app )
	{
		self::$app = $app;
	}

	function __construct( $prefix )
	{
		$this->prefix = $prefix;
	}

	/**
	 * Looks up values in the cache for each key
	 *
	 * @param array $keys keys to look up
	 *
	 * @return array
	 */
	function get( array $keys )
	{
		$prefix = $this->prefix;

		$prefixedKeys = array_map( function( $key ) use ( $prefix ) {
			return $prefix . $key;
		}, $keys );

		$result = self::$app[ 'memcache' ]->get( $prefixedKeys );

		// strip cache prefixes from keys
		$values = [];
		foreach( $result as $key => $value )
			$values[ str_replace( $prefix, '', $key ) ] = $value;

		return $values;
	}

	/**
	 * Checks if a key exists in the cache
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	function has( $key )
	{
		$added = self::$app[ 'memcache' ]->set( $this->prefix . $key, null );
		
		// key already exists
		if( !$added )
			return true;
		
		// added key - now we need to delete what we just added
		self::$app[ 'memcache' ]->delete( $this->prefix . $key );
		
		return false;
	}

	/**
	 * Sets a value in the cache for a given key
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $expires expiration time in seconds (0 = never)
	 *
	 * @return boolean
	 */
	function set( $key, $value, $expires )
	{
		return self::$app[ 'memcache' ]->set( $this->prefix . $key, $value, $expires );
	}

	/**
	 * Increments the value stored in a key
	 *
	 * @param string $key
	 * @param int $amount optional amount to increment
	 *
	 * @return number
	 */
	function increment( $key, $amount )
	{
		return self::$app[ 'memcache' ]->increment( $this->prefix . $key, $amount );
	}

	/**
	 * Decrements the value stored in a key
	 *
	 * @param string $key
	 * @param int $amount optional amount to decrement
	 *
	 * @return number
	 */
	function decrement( $key, $amount )
	{
		return self::$app[ 'memcache' ]->decrement( $this->prefix . $key, $amount );
	}

	/**
	 * Deletes a value stored in the cache
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	function delete( $key )
	{
		return self::$app[ 'memcache' ]->delete( $this->prefix . $key );
	}
}