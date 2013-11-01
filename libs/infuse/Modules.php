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

		return strlen( $module ) > 0 && class_exists( '\\app\\' . $module . '\\Controller' );
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
		
		// check if controller exists
		$class = '\\app\\' . $module . '\\Controller';
		if( !class_exists( $class ) )
			return false;
		
		self::$info[ $module ] = $class::properties();
		
		return true;
	}
}