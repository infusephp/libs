<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.16.0
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
	 * @param string $section section
	 * @param string $name configuration name
	 *
	 * @return string|null value
	 */
	static function get( $section = false, $property = false )
	{
		if( !$section )
			return self::$values;
		
		if( !$property )
		{
			if( isset( self::$values[ $section ] ) )
				return self::$values[ $section ];
			else
				return null;
		}
		
		if( isset( self::$values[ $section ] ) &&
			isset( self::$values[ $section ][ $property ] ) )
			return self::$values[ $section ][ $property ];
	}
	
	/** 
	 * Sets a configuration value (only persists for the duration of the script)
	 *
	 * @param string $section
	 * @param string $property
	 * @param string $value
	 *
	 * @param return void
	 */
	static function set( $section, $property, $value )
	{
		if( !isset( self::$values[ $section ] ) )
			self::$values[ $section ] = array();
			
		self::$values[ $section ][ $property ] = $value;
	}
	
	/**
	 * Loads the site configuration from an array
	 *
	 * @param array $value
	 *
	 * @return void
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