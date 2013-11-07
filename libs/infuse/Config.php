<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.16.1
 * @copyright 2013 Jared King
 * @license MIT
 */

namespace infuse;

class Config
{
	private static $values = array();
	
	/**
	 * Gets a global configuration value, section, or all values
	 *
	 * @param string $property dot value property name
	 * @param string $deprecated when supplied looks up a key in a section (@deprecated)
	 *
	 * @return string|null value
	 */
	static function get( $property = false, $deprecated = false )
	{
		if( !$property )
			return self::$values;

		if( $deprecated )
		{
			if( isset( self::$values[ $property ] ) &&
				isset( self::$values[ $property ][ $deprecated ] ) )
				return self::$values[ $property ][ $deprecated ];
		}
		
		return Util::array_value( self::$values, $property );		
	}
	
	/** 
	 * Sets a configuration value (only persists for the duration of the script)
	 *
	 * @param string $property dot value property name
	 * @param string $value value to set
	 * @param string $deprecated when used sets the value of a key in a section (@deprecated)
	 */
	static function set( $property, $value, $deprecated = false )
	{
		if( $deprecated )
		{
			if( !isset( self::$values[ $property ][ $value ] ) && !is_array( self::$values[ $property ][ $value ] ) )
				self::$values[ $property ][ $value ] = array();

			return self::$values[ $property ][ $value ] = $deprecated;
		}

		Util::array_set( self::$values, $property, $value );
	}
	
	/**
	 * Loads the site configuration from an array
	 *
	 * @param array $value
	 */
	static function load( $values )
	{
		self::$values = $values;
	}
	
	/**
	 * @deprecated
	 */
	static function value( $section, $property )
	{
		return self::get( $section, $property );
	}
}