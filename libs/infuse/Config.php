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
 
namespace infuse;

class Config
{
	/////////////////////////////
	// Private class variables
	/////////////////////////////
	
	private static $values = array();
	
	/////////////////////////////
	// GETTERS
	/////////////////////////////
	
	/**
	 * Gets a global configuration value
	 *
	 * @param string $section section
	 * @param string $name configuration name
	 *
	 * @return string|null value
	 */
	static function value( $section, $property )
	{
		if( isset( self::$values[ $section ] ) &&
			isset( self::$values[ $section ][ $property ] ) )
			return self::$values[ $section ][ $property ];
		
		return null;
	}
	
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
	 * Loads the site configuration from a YAML file
	 *
	 * @param string $filename
	 *
	 * @return void
	 */
	static function load( $filename )
	{
		self::$values = (array)spyc_load_file( $filename );
	}
}