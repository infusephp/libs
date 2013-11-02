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
	
	//////////////////////////////////
	// GETTERS
	//////////////////////////////////
	
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
			$controller = '\\app\\' . $module . '\\Controller';
			
			if( Util::array_value( $controller::$properties, 'scaffoldAdmin' ) ||
				Util::array_value( $controller::$properties, 'hasAdminView' ) )
				$return[] = $controller::$properties;
		}
		
		return $return;
	}
}