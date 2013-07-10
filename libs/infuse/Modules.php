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
	
	private static $controllers;
	private static $info;
	
	//////////////////////////////////
	// GETTERS
	//////////////////////////////////
	
	/**
	* Gets a list of required modules
	*
	* @return array required modules
	*/
	static function requiredModules()
	{
		return explode( ',', Config::value( 'site', 'required-modules' ) );
	}

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
		return strlen( $module ) > 0 && file_exists( self::$moduleDirectory . $module . '/controller.php');
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
		return isset( self::$controllers[ $module ] );
	}	
	
	/**
	* Gets the controller name for the module
	*
	* @param string $module module
	*
	* @return string
	*/
	static function controllerName( $module )
	{
		return '\\infuse\\Controllers\\' . ucfirst( strtolower( $module ) );	
	}
	
	/**
	 * Gets the class name of any module
	 *
	 * @param string $module module
	 *
	 * @return string class name
	 */
	static function controller( $module )
	{
		if( !self::loaded( $module ) )
			self::load( $module );

		return self::$controllers[ strtolower( $module ) ];
	}
	
	/**
	 * Gets the models associated with a module
	 *
	 * @param string $module module
	 *
	 * @return array models
	 */
	static function models( $module )
	{
		$moduleInfo = self::info( $module );
		
		$modelParams = array();
		
		if( isset( $moduleInfo[ 'models' ] ) && is_array( $moduleInfo[ 'models' ] ) )
		{
			foreach( (array)$moduleInfo[ 'models' ] as $model )
				$modelNames[] = $model;
		}
		else if( isset( $moduleInfo[ 'model' ] ) )
		{
			$modelNames[] = $moduleInfo[ 'model' ];
		}
		
		$models = array();

		foreach( $modelNames as $model )
		{		
			$singularKey = Inflector::underscore( $model );
			$pluralKey = Inflector::pluralize( $singularKey );
			$properName = Inflector::humanize( $singularKey );			
			
			$models[ $model ] = array(
				'model' => $model,
				'class_name' => '\\infuse\\models\\' . $model,
				'api' => val( $moduleInfo, 'api' ),
				'admin' => val( $moduleInfo, 'admin' ),
				'singular_key' => $singularKey,
				'plural_key' => $pluralKey,
				'route_base' => '/' . $module . '/' . $pluralKey,
				'proper_name' => $properName,
				'proper_name_plural' => Inflector::humanize( $pluralKey )
			);
		}
		
		return $models;
	}
	
	/**
	 * Gets information about a module from its module.yml file
	 *
	 * @return array info
	 */
	static function info( $module )
	{
		self::initialize( $module );
		
		return self::$info[ strtolower( $module ) ];
	}
	
	/**
	 * Returns a list of all modules and associated meta-data
	 *
	 * @return array modules
	 */
	static function all()
	{
		self::initializeAll();
		
		$return = array();
		
		foreach( self::$info as $module => $info )
		{
			$info[ 'name' ] = $module;
			$return[] = $info;
		}
		
		// sort by name
		$cmp = function($a, $b) {
		    return strcmp($a["name"], $b["name"]);
		};

		usort($return, $cmp);

		return $return;	
	}
	
	/**
	 * Returns a list of modules with an admin section
	 *
	 * @return array modules with admin enabled
	 */
	static function modulesWithAdmin()
	{
		$return = array();
		
		foreach( self::all() as $module => $info )
		{
			if( $info['admin'] )
				$return[] = $info;
		}

		return $return;
	}
	
	//////////////////////////////
	// UTILITIES
	//////////////////////////////

	/**
	* Initializes a module
	*
	* @return null
	*/
	static function initialize( $module )
	{
		$module = strtolower( $module );
	
		if( isset( self::$info[ $module ] ) )
			return true;
		
		$configFile = self::$moduleDirectory . '/' . $module . '/module.yml';
		
		// module defaults
		$info = array(
			'title' => $module,
			'version' => 0,
			'description' => '',
			'author' => array(
				'name' => '',
				'email' => '',
				'website' => '' ),
			'model' => false,
			'models' => false,
			'api' => false,
			'admin' => false,
			'routes' => array()
		);
		
		if( file_exists( $configFile ) )
			$info = array_merge( $info, (array)spyc_load_file( $configFile ) );

		self::$info[ $module ] = $info;
	}
	
	static function load( $module )
	{
		$module = strtolower( $module );

		// check if module has already been loaded
		if( self::loaded( $module ) )
			return true;
		
		// check if module exists
		if( !self::exists( $module ) )
			return false;
		
		// load settings
		self::initialize( $module );

		// load module code
		include_once self::$moduleDirectory . $module . '/' . 'controller.php';

		// create a new instance of the module
		$class = self::controllerName( $module );
		$controller = new $class();
		
		// add module to loaded modules list
		self::$controllers[ $module ] = $controller;
		//echo "$module loaded | ";

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
	* Loads all modules
	*
	* Loading a module only loads the class files into memory
	* @return null
	*/
	static function loadAll()
	{
		// search directory to locate all modules
		$modules = glob(self::$moduleDirectory . '*' , GLOB_ONLYDIR);
		array_walk( $modules, function(&$n) {
			$n = str_replace(self::$moduleDirectory,'',$n);
		});

		foreach( (array)$modules as $name )
			self::load( $name );	
	}
	
	/**
	* Initializes all modules
	* @return null
	*/
	static function initializeAll()
	{
		// search directory to locate all modules
		$modules = glob(self::$moduleDirectory . '*' , GLOB_ONLYDIR);
		array_walk( $modules, function(&$n) {
			$n = str_replace(self::$moduleDirectory,'',$n);
		});

		foreach( (array)$modules as $name )
			self::initialize( $name );	
	}
	
	/**
	 * Loads required modules
	 *
	 */
	static function loadRequired()
	{
		// load required modules
		foreach( self::requiredModules() as $name )
			self::load( $name );
	}
	
	
	/**
	 * Performs middleware on required modules
	 *
	 * @param Request $request
	 * @param Repsonse $response
	 *
	 */
	static function middleware( $request, $response )
	{
		// load required modules
		foreach( self::requiredModules() as $name )
			self::$controllers[ $name ]->middleware( $request, $response );		
	}
	
	/**
	* Looks for new modules in the module directory
	*
	* @return boolean true if successful
	*/
	static function scanModules()
	{
		if ($dir = @opendir( self::$moduleDirectory ))
		{
			while (($mod_name = readdir($dir)) !== false)
			{
				if ($mod_name != '.' && $mod_name != '..')
				{
					if( !self::exists( $mod_name ) )
					{ }
				}
	    	}
		}

		return true;
	}
}

// hack
Modules::$moduleDirectory = INFUSE_MODULES_DIR . '/';