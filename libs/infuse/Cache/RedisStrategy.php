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

class RedisStrategy
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
		if( !self::$app->offsetExists( 'redis' ) )
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

		$result = self::$app[ 'redis' ]->mget( $prefixedKeys );

		// for mget() predis will return an ordered list
		// corresponding to the order the keys were passed in

		$i = 0;
		$values = [];
		foreach( $prefixedKeys as $key )
		{
			$unprefixedKey = $keys[ $i ];
			$value = $result[ $i ];

			$i++;

			// We do not actually know if the value is null
			// because it was set to null or if it is because the
			// key does not exist. Therefore, we only proceed if
			// the key exists.
			// TODO This could get nasty if null values
			// are frequently stored in the cache.
			if( $value === null && !$this->has( $unprefixedKey ) )
				continue;

			// strip cache prefix and add it to return
			$values[ $unprefixedKey ] = $value;
		}

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
		return self::$app[ 'redis' ]->exists( $this->prefix . $key );
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
		if( $expires <= 0 )
			return self::$app[ 'redis' ]->set( $this->prefix . $key, $value );
		else
			return self::$app[ 'redis' ]->setex( $this->prefix . $key, $expires, $value );
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
		return self::$app[ 'redis' ]->incrby( $this->prefix . $key, $amount );
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
		return self::$app[ 'redis' ]->decrby( $this->prefix . $key, $amount );
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
		return self::$app[ 'redis' ]->del( $this->prefix . $key );
	}
}