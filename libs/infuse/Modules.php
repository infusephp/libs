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
		
		return strlen( $module ) > 0 && file_exists( self::$moduleDirectory . '/' . $module . '/Controller.php' );
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
			
			if( Util::array_value( $info, 'scaffoldAdmin' ) || Util::array_value( $info, 'hasAdminView' ) )
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
		@include_once self::$moduleDirectory . '/' . $module . '/' . 'Controller.php';
		
		// check if controller exists
		$class = '\\app\\' . $module . '\\Controller';
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
		include_once self::$moduleDirectory . '/' . $module . '/' . 'Controller.php';
		
		// add module to loaded modules list
		self::$loaded[] = $module;
		
		// setup controller
		$class = '\\app\\' . $module . '\\Controller';
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