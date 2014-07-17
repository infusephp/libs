<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.23
 * @copyright 2014 Jared King
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

	// redis strategy
	private static $redis;
	private static $redisConnectionAttempted;	

	// memcache strategy
	private static $memcache;
	private static $memcacheConnectionAttempted;
	
	// local strategy
	private static $local = [];
	
	/**
	 * Creates a new instance of the cache
	 * Uses the first strategy supplied that does not fail
	 *
	 * @param array $strategies
	 * @param array $parameters
	 */
	function __construct( $strategies = [], $parameters = [] )
	{
		if( count( $strategies ) == 0 )
			$strategies = [ 'local' ];

		foreach( $strategies as $strategy )
		{
			$strategyFunc = "strategy_$strategy";
			if( method_exists( $this, $strategyFunc ) && $this->$strategyFunc( (array)Util::array_value( $parameters, $strategy ) ) )
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
		// force the array keys to renumber sequentially starting at 0
		$keys = array_values( (array)$keys );
		$cachePrefix = $this->cachePrefix;
		$prefixedKeys = array_map( function ($str) use ($cachePrefix) {
			return $cachePrefix . $str; }, $keys );
		
		$return = [];
		
		if( $this->strategy == 'redis' )
		{
			$cache = self::$redis->mget( $prefixedKeys );

			// for mget() predis will return an ordered list
			// corresponding to the order the keys were passed in

			$i = 0;
			foreach( $prefixedKeys as $key )
			{
				$unprefixedKey = $keys[ $i ];
				$value = $cache[ $i ];

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
				$return[ $unprefixedKey ] = $value;
			}
		}
		else if( $this->strategy == 'memcache' )
		{
			$cache = self::$memcache->get( $prefixedKeys );

			foreach( $cache as $key => $value )
				// strip cache prefix and add it to return
				$return[ str_replace( $cachePrefix, '', $key ) ] = $value;
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
		if( $this->strategy == 'redis' )
			return self::$redis->exists( $this->cachePrefix . $key );

		else if( $this->strategy == 'memcache' )
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
	function set( $key, $value, $expires = 0 )
	{
		if( is_array( $value ) || is_object( $value ) )
			$value = json_encode( $value );

		if( $this->strategy == 'redis' )
		{
			if( $expires <= 0 )
				return self::$redis->set( $this->cachePrefix . $key, $value );

			else
				return self::$redis->setex( $this->cachePrefix . $key, $expires, $value );
		}
		else if( $this->strategy == 'memcache' )
			return self::$memcache->set( $this->cachePrefix . $key, $value, $expires );

		else if( $this->strategy == 'local' )
		{
			// NOTE expiration not implemented, this most likely is not a problem
			self::$local[ $this->cachePrefix . $key ] = $value;
			return true;
		}

		return false;
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
		if( $this->strategy == 'redis' )
			return self::$redis->incrby( $this->cachePrefix . $key, $amount );

		else if( $this->strategy == 'memcache' )
			return self::$memcache->increment( $this->cachePrefix . $key, $amount );
			
		else if( $this->strategy == 'local' )
		{
			if( isset( self::$local[ $this->cachePrefix . $key ] ) )
			{
				self::$local[ $this->cachePrefix . $key ] += $amount;
				return self::$local[ $this->cachePrefix . $key ];
			}
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
	function decrement( $key, $amount = 1 )
	{
		if( $this->strategy == 'redis' )
			return self::$redis->decrby( $this->cachePrefix . $key, $amount );

		else if( $this->strategy == 'memcache' )
			return self::$memcache->decrement( $this->cachePrefix . $key, $amount );
			
		else if( $this->strategy == 'local' )
		{
			if( isset( self::$local[ $this->cachePrefix . $key ] ) )
			{
				self::$local[ $this->cachePrefix . $key ] -= $amount;
				return self::$local[ $this->cachePrefix . $key ];
			}
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
		if( $this->strategy == 'redis' )
			return self::$redis->del( $this->cachePrefix . $key );

		else if( $this->strategy == 'memcache' )
			return self::$memcache->delete( $this->cachePrefix . $key );
		
		else if( $this->strategy == 'local' )
		{
			if( isset( self::$local[ $this->cachePrefix . $key ] ) )
				unset( self::$local[ $this->cachePrefix . $key ] );	

			return true;
		}

		return false;
	}
	
	/////////////////////////
	// STRATEGIES
	/////////////////////////

	private function strategy_redis( $parameters = [] )
	{
		// initialize reids if enabled
		if( !self::$redis &&
			!self::$redisConnectionAttempted &&
			class_exists( 'Predis\Client' ) )
		{
			// attempt to connect to redis
			try
			{
				self::$redisConnectionAttempted = true;
				
				self::$redis = new \Predis\Client( $parameters ) or (self::$redis = false);
			}
			catch(\Exception $e)
			{
				self::$redis = false;
			}
		}

		if( self::$redis )
		{
			$this->cachePrefix = Util::array_value( $parameters, 'prefix' );
			
			$this->strategy = 'redis';

			return true;
		}
		
		return false;
	}
	
	private function strategy_memcache( $parameters = [] )
	{
		// initialize memcache if enabled
		if( !self::$memcache &&
			!self::$memcacheConnectionAttempted &&
			class_exists( 'Memcache' ) )
		{
			// attempt to connect to memcache
			try
			{
				self::$memcacheConnectionAttempted = true;
				
				self::$memcache = new \Memcache;
				self::$memcache->connect( $parameters[ 'host' ], $parameters[ 'port' ] ) or (self::$memcache = false);
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
	
	private function strategy_local( $parameters = [] )
	{
		$this->cachePrefix = Util::array_value( $parameters, 'prefix' );
		
		$this->strategy = 'local';
		
		return true;
	}
}