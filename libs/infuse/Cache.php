<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.16.4
 * @copyright 2013 Jared King
 * @license MIT
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
	 * Creates a new instance of the cache
	 *
	 * @param array $strategies
	 * @param array $parameters
	 */
	function __construct( $strategies = array(), $parameters = array() )
	{
		if( count( $strategies ) == 0 )
			$strategies = array( 'local' );

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
	function get( $keys, $forceArray = false )
	{
		$keys = (array)$keys;
		$cachePrefix = $this->cachePrefix;
		$prefixedKeys = array_map( function ($str) use ($cachePrefix) { return $cachePrefix . $str; }, $keys );
		
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
					$return[ $key ] = self::$local[ $prefixedKeys[ $i ] ];
			}
		}

		if( !$forceArray && count( $return ) <= 1 )
		{
			if( count( $return ) == 0 )
				return null;

			return reset( $return );
		}

		return $return;
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
	 *
	 * @return boolean
	 */
	function set( $key, $value, $expires = 0 )
	{
		if( $this->strategy == 'memcache' )
			return self::$memcache->set( $this->cachePrefix . $key, $value, $expires );

		else if( $this->strategy == 'local' )
		{
			// NOTE expiration not implemented, this most likely is not a problem
			self::$local[ $this->cachePrefix . $key ] = $value;
			return true;
		}
	}
	
	/**
	 * Increments the value stored in a key
	 *
	 * @param string $key
	 * @param int $amount optional amount to increment
	 *
	 * @return number
	 */
	function increment( $key, $amount = 1 )
	{
		if( $this->strategy == 'memcache' )
			return self::$memcache->increment( $this->cachePrefix . $key, $amount );
			
		else if( $this->strategy == 'local' )
		{
			if( isset( self::$local[ $this->cachePrefix . $key ] ) )
			{
				self::$local[ $this->cachePrefix . $key ] += $amount;
				return self::$local[ $this->cachePrefix . $key ];
			}

			return false;
		}
	}
	
	/**
	 * Decrements the value stored in a key
	 *
	 * @param string $key
	 * @param int $amount optional amount to decrement
	 *
	 * @return number
	 */
	function decrement( $key, $amount = 1 )
	{
		if( $this->strategy == 'memcache' )
			return self::$memcache->decrement( $this->cachePrefix . $key, $amount );
			
		else if( $this->strategy == 'local' )
		{
			if( isset( self::$local[ $this->cachePrefix . $key ] ) )
			{
				self::$local[ $this->cachePrefix . $key ] -= $amount;
				return self::$local[ $this->cachePrefix . $key ];
			}

			return false;
		}
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
		// use memcache
		if( $this->strategy == 'memcache' )
			return self::$memcache->delete( $this->cachePrefix . $key );
		
		// fallback to local cache
		else if( $this->strategy == 'local' )
		{
			if( isset( self::$local[ $this->cachePrefix . $key ] ) )
				unset( self::$local[ $this->cachePrefix . $key ] );	

			return true;
		}
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