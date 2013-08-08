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

class Modules
{
	/**
	* Module directory
	* @staticvar string
	*/
	public static $moduleDirectory;
	
	////////////////////////////////
	// Private Class Variables
	////////////////////////////////
	
	private static $info = array();
	private static $controllers = array();
	private static $loaded = array();
	
	//////////////////////////////////
	// GETTERS
	//////////////////////////////////

	/**
	 * Checks if a module exists
	 *
	 * @param string $module module
	 *
	 * @return boolean
	 */
	static function exists( $module )
	{
		$module = strtolower( $module );
		
		return strlen( $module ) > 0 && file_exists( self::$moduleDirectory . '/' . $module . '/controller.php' );
	}
	
	/**
	 * Checks if a module has been initialized
	 *
	 * @param string $module module
	 *
	 * @return boolean
	 */
	static function initialized( $module )
	{
		return isset( self::$info[ $module ] );
	}	

	/**
	 * Checks if a module has been loaded
	 *
	 * @param string $module module
	 *
	 * @return boolean
	 */
	static function loaded( $module )
	{
		return isset( self::$loaded[ $module ] );
	}
	
	/**
	 * Gets information about a module from its properties
	 *
	 * @return array info
	 */
	static function info( $module )
	{
		self::initialize( $module );
		
		return self::$info[ strtolower( $module ) ];
	}
	
	/**
	 * Returns a list of all modules
	 *
	 * @return array modules
	 */
	static function all()
	{
		// search directory to locate all modules
		$modules = glob( self::$moduleDirectory . '/*' , GLOB_ONLYDIR );
		array_walk( $modules, function( &$n ) {
			$n = str_replace(self::$moduleDirectory . '/','',$n);
		});
		
		// sort by name
		sort( $modules );
		
		return $modules;
	}
	
	/**
	 * Returns a list of modules with an admin section
	 *
	 * @return array modules with admin enabled
	 */
	static function adminModules()
	{
		$return = array();
		
		foreach( self::all() as $module )
		{
			$info = self::info( $module );
			
			if( Util::array_value( $info, 'admin' ) )
				$return[] = $info;
		}
		
		return $return;
	}
	
	/**
	 * Gets the controller of a module
	 *
	 * @param string $module module
	 *
	 * @return Controller
	 */
	static function controller( $module )
	{
		if( !self::loaded( $module ) )
			self::load( $module );

		return self::$controllers[ strtolower( $module ) ];
	}	
	
	//////////////////////////////
	// UTILITIES
	//////////////////////////////

	/**
	 * Initializes a module. This means that the properties of the controller are loaded.
	 *
	 * @param array|string module name(s)
	 *
	 * @return boolean
	 */
	static function initialize( $module )
	{
		// initialize several modules at once
		if( is_array( $module ) )
		{
			$success = true;
			
			foreach( $module as $m )
				$success = self::initialize( $m ) && $success;
			
			return $success;
		}
		
		$module = strtolower( $module );
	
		if( isset( self::$info[ $module ] ) )
			return true;
		
		// load module code
		@include_once self::$moduleDirectory . '/' . $module . '/' . 'controller.php';
		
		// check if controller exists
		$class = '\\infuse\\controllers\\' . Inflector::camelize( $module );
		if( !class_exists( $class ) )
			return false;
		
		self::$info[ $module ] = $class::properties();
		
		return true;
	}
	
	/**
	 * Loads a module. This means that the module is initialized, the controller is instantiated,
	 * and it is available for autoloading.
	 * 
	 * @param array|string module name(s)
	 *
	 * @return boolean
	 */
	static function load( $module )
	{
		// load several modules at once
		if( is_array( $module ) )
		{
			$success = true;
			
			foreach( $module as $m )
				$success = self::load( $m ) && $success;
			
			return $success;
		}
		
		$module = strtolower( $module );

		// check if module has already been loaded
		if( self::loaded( $module ) )
			return true;
		
		// load settings
		if( !self::initialize( $module ) )
			return false;

		// load module code
		include_once self::$moduleDirectory . '/' . $module . '/' . 'controller.php';
		
		// add module to loaded modules list
		self::$loaded[] = $module;
		
		// setup controller
		$class = '\\infuse\\controllers\\' . Inflector::camelize( $module );
		$controller = new $class();
		self::$controllers[ $module ] = $controller;

		// load dependencies
		if( isset( self::$info[ $module ][ 'dependencies' ] ) )
		{
			foreach( (array)self::$info[ $module ][ 'dependencies' ] as $dependency )
			{
				if( !self::load( $dependency ) )
					return false;
			}
		}
		
		return true;	
	}
	
	/**
	 * Performs middleware on all loaded modules
	 *
	 * @param Request $request
	 * @param Repsonse $response
	 *
	 * @return void
	 */
	static function middleware( $req, $res )
	{
		foreach( self::$loaded as $name )
			self::$controllers[ $name ]->middleware( $req, $res );
	}
	
	/**
	 * Class autoloader
	 *
	 * @param string $class class
	 *
	 * @return void
	*/
	public static function autoloader( $class )
	{
		foreach( self::$loaded as $module )
		{
			// look in modules/:module/:class.php
			// i.e. /infuse/models/User -> modules/users/models/User.php

			if( $module != 'Module' || $module != '' )
			{
				$name = str_replace( '\\', '/', str_replace( 'infuse\\', '', $class ) );
				$path = self::$moduleDirectory . "/$module/$name.php";
	
				if (file_exists($path) && is_readable($path))
				{
					include_once $path;
					return;
				}
			}
		}
	}
}