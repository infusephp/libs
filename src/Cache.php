<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.2.2
 * @copyright 2014 Jared King
 * @license MIT
 */

/*
	TODO possible strategies to add:
	- file
	- database
*/

namespace infuse;

use Pimple\Container;

define( 'CACHE_STRATEGY_LOCAL', '\\infuse\\Cache\\Local' );
define( 'CACHE_STRATEGY_MEMCACHE', '\\infuse\\Cache\\Memcache' );
define( 'CACHE_STRATEGY_REDIS', '\\infuse\\Cache\\RedisStrategy' );

class Cache
{
    private $strategy;
    private static $shortcuts = [
        'local' => CACHE_STRATEGY_LOCAL,
        'memcache' => CACHE_STRATEGY_MEMCACHE,
        'redis' => CACHE_STRATEGY_REDIS
    ];

    /**
	 * Creates a new instance of the cache
	 * Uses the first strategy supplied that does not fail
	 *
	 * @param array $strategies list of cache strategy classes to try in order
	 * @param string $prefix cache key prefix
	 * @param Container DI container
	 */
    public function __construct(array $strategies = [], $prefix = '', Container $app = null)
    {
        foreach ($strategies as $strategy) {
            // provide shortcuts for referencing built-in cache strategies
            // by a keyword instead of the full class name
            if( isset( self::$shortcuts[ $strategy ] ) )
                $strategy = self::$shortcuts[ $strategy ];

            if ( class_exists( $strategy ) ) {
                if( $app )
                    $strategy::inject( $app );

                if( $this->strategy = $strategy::init( $prefix ) )
                    break;
            }
        }

        // fall back to local strategy
        if( !$this->strategy )
            $this->strategy = Cache\LocalStrategy::init( $prefix );
    }

    /**
	 * Gets the cache strategy being used
	 *
	 * @return object strategy
	 */
    public function strategy()
    {
        return $this->strategy;
    }

    /**
	 * Gets a cached value according to the strategy.
	 *
	 * NOTE: If a value is not found, it will not be bundled in the array returned.
	 *       Also, if only a single value is requested, the return will be that value.
	 *       Otherwise, a key-value array will be returned.
	 *		 Regarding objects and arrays, they will automatically be serialized
	 *		 in set(), but when they come back they will still be serialized
	 *		 as JSON. It is up to you to unserialize the JSON returned.
	 *
	 * @param array|string key(s) to lookup values for
	 * @param array force a key-value array to be returned, even if only 1 key is provided
	 *
	 * @return array|mixed values or value
	 */
    public function get($keys, $forceArray = false)
    {
        // force the array keys to renumber sequentially starting at 0
        $keys = array_values( (array) $keys );

        $return = $this->strategy->get( $keys );

        if ( !$forceArray && count( $return ) <= 1 ) {
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
    public function has($key)
    {
        return $this->strategy->has( $key );
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
    public function set($key, $value, $expires = 0)
    {
        // encode objects/arrays as JSON for consistency
        // amongst various strategies
        if( is_array( $value ) || is_object( $value ) )
            $value = json_encode( $value );

        return $this->strategy->set( $key, $value, $expires );
    }

    /**
	 * Increments the value stored in a key
	 *
	 * @param string $key
	 * @param int $amount optional amount to increment
	 *
	 * @return number
	 */
    public function increment($key, $amount = 1)
    {
        return $this->strategy->increment( $key, $amount );
    }

    /**
	 * Decrements the value stored in a key
	 *
	 * @param string $key
	 * @param int $amount optional amount to decrement
	 *
	 * @return number
	 */
    public function decrement($key, $amount = 1)
    {
        return $this->strategy->decrement( $key, $amount );
    }

    /**
	 * Deletes a value stored in the cache
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
    public function delete($key)
    {
        return $this->strategy->delete( $key );
    }
}
