<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.23
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse\Cache;

use Pimple\Container;

class LocalStrategy
{
	private static $injectedApp;

	private $prefix;
	private static $cache = [];

	/**
	 * Attempts to use the caching strategy
	 *
	 * @param string $prefix cache prefix
	 * 
	 * @return object|false
	 */
	static function init( $prefix )
	{
		return new self( $prefix );
	}

	/**
	 * Injects a DI container
	 *
	 * @param Container $app
	 */
	static function inject( Container $app )
	{
		self::$injectedApp = $app;
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
		$values = [];

		foreach( $keys as $i => $key )
		{
			$prefixedKey = $this->prefix . $key;

			if( isset( self::$cache[ $prefixedKey ] ) )
				$values[ $key ] = self::$cache[ $prefixedKey ];
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
		return isset( self::$cache[ $this->prefix . $key ] );
	}

	/**
	 * Sets a value in the cache for a given key
	 * NOTE expiration not implemented, this most likely is not a problem
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $expires expiration time in seconds (0 = never)
	 *
	 * @return boolean
	 */
	function set( $key, $value, $expires )
	{
		self::$cache[ $this->prefix . $key ] = $value;

		return true;
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
		$key = $this->prefix . $key;

		if( isset( self::$cache[ $key ] ) )
		{
			self::$cache[ $key ] += $amount;
			return self::$cache[ $key ];
		}

		return false;
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
		$key = $this->prefix . $key;

		if( isset( self::$cache[ $key ] ) )
		{
			self::$cache[ $key ] -= $amount;
			return self::$cache[ $key ];
		}

		return false;
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
		$key = $this->prefix . $key;

		if( isset( self::$cache[ $key ] ) )
			unset( self::$cache[ $key ] );	

		return true;
	}
}