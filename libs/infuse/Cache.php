<?php

/*
 * @package Infuse
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 1.0
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
 
/*
	TODO possible strategies to add:
	- redis
	- file
	- database
*/
 
namespace infuse;
  
class Cache
{
	private $strategy;
	private $cachePrefix;

	// memcache strategy
	private static $memcache;
	private static $memcacheConnectionAttempted;
	
	// local strategy
	private static $local = array();	
	
	/**
	 *
	 *
	 * @param array $strategies
	 * @param array $parameters
	 */
	function __construct( $strategies, $parameters = array() )
	{
		foreach( $strategies as $strategy )
		{
			$strategyFunc = "strategy_$strategy";
			if( $this->$strategyFunc( (array)Util::array_value( $parameters, $strategy ) ) )
				return true;
		}
		
		return false;
	}
	
	/**
	 * Gets a cached value according to the strategy.
	 *
	 * NOTE: If a value is not found, it will not be bundled in the array returned.
	 *       Also, if only a single value is requested, the return will be that value.
	 *       Otherwise, a key-value array will be returned.
	 *
	 * @param array|string key(s) to lookup values for
	 * @param array force a key-value array to be returned, even if only 1 key is provided
	 *
	 * @return array|mixed values or value
	 */
	function get( $keys, $forceArray = true )
	{
		$keys = (array)$keys;
		$prefixedKeys = array_map( function ($str) { return $this->cachePrefix . $str; }, $keys );
		
		$return = array();
		
		if( $this->strategy == 'memcache' )
		{
			$cache = self::$memcache->get( $prefixedKeys );

			foreach( $cache as $key => $value )
				// strip cache prefix and add it to return
				$return[ str_replace( $this->cachePrefix, '', $key ) ] = $value;
		}
		else if( $this->strategy == 'local' )
		{
			foreach( $keys as $i => $key )
			{
				if( isset( self::$local[ $prefixedKeys[ $i ] ] ) )
					$return[ $key ] = $this->localCache[ $prefixedKeys[ $i ] ];
			}
		}
		
		return ( !$forceArray && count( $keys ) == 1 ) ? reset( $return ) : $return;
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
		if( $this->strategy == 'memcache' )
		{
			$added = self::$memcache->set( $this->cachePrefix . $key, null );
			
			// key already exists
			if( !$added )
				return true;
			
			// added key - now we need to delete what we just added
			self::$memcache->delete( $this->cachePrefix . $key );
			
			return false;
		}
			
		else if( $this->strategy == 'local' )
			return isset( self::$local[ $this->cachePrefix . $key ] );
	}
	
	/**
	 * Sets a value in the cache for a given key
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $expires expiration time (0 = never)
	 */
	function set( $key, $value, $expires = 0 )
	{
		if( $this->strategy == 'memcache' )
			self::$memcache->set( $this->cachePrefix . $key, $value, $expires );

		else if( $this->strategy == 'local' )
			// NOTE expiration not implemented, this most likely is not a problem
			self::$local[ $this->cachePrefix . $key ] = $value;
	}
	
	/**
	 * Increments the value stored in a key
	 *
	 * @param string $key
	 * @param int $amount optional amount to increment
	 */
	function increment( $key, $amount = 1 )
	{
		if( $this->strategy == 'memcache' )
			self::$memcache->increment( $this->cachePrefix . $key, $amount );
			
		else if( $this->strategy == 'local' )
			self::$local[ $this->cachePrefix . $key ] += $amount;
	}
	
	/**
	 * Decrements the value stored in a key
	 *
	 * @param string $key
	 * @param int $amount optional amount to decrement
	 */
	function decrement( $key, $amount = 1 )
	{
		if( $this->strategy == 'memcache' )
			self::$memcache->decrement( $this->cachePrefix . $key, $amount );
			
		else if( $this->strategy == 'local' )
			self::$local[ $this->cachePrefix . $key ] -= $amount;
	}
	
	/**
	 * Deletes a value stored in the cache
	 *
	 * @param string $key
	 */
	function delete( $key )
	{
		// use memcache
		if( $this->strategy == 'memcache' )
			self::$memcache->delete( $this->cachePrefix . $key );
		
		// fallback to local cache
		else if( $this->strategy == 'local ')
			unset( self::$local[ $this->cachePrefix . $key ] );	
	}
	
	/////////////////////////
	// STRATEGIES
	/////////////////////////
	
	private function strategy_memcache( $parameters = array() )
	{
		// initialize memcache if enabled
		if( class_exists('Memcache') )
		{
			// attempt to connect to memcache
			try
			{
				if( !self::$memcache && !self::$memcacheConnectionAttempted )
				{
					self::$memcacheConnectionAttempted = true;
					
					self::$memcache = new \Memcache;
					
					self::$memcache->connect( $parameters[ 'host' ], $parameters[ 'port' ] ) or (self::$memcache = false);
				}
			}
			catch(\Exception $e)
			{
				self::$memcache = false;
			}
		}
		
		if( self::$memcache )
		{
			$this->cachePrefix = Util::array_value( $parameters, 'prefix' );
			
			$this->strategy = 'memcache';

			return true;
		}
		
		return false;
	}
	
	private function strategy_local( $parameters = array() )
	{
		$this->cachePrefix = Util::array_value( $parameters, 'prefix' );
		
		$this->strategy = 'local';
		
		return true;
	}
}