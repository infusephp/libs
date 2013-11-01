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

abstract class Controller extends Acl
{
	public static $properties = array(
		'title' => '',
		'version' => 0,
		'description' => '',
		'author' => array(
			'name' => '',
			'email' => '',
			'website' => '' ),
		'model' => false,
		'models' => false,
		'routes' => array() );
	
	protected $models;

	/////////////////////////
	// GETTERS
	/////////////////////////
	
	/**
	 * Gets the name of the controller
	 *
	 * @return string name
	*/
	static function name()
	{
		$parts = explode( '\\', get_called_class() );
		return $parts[ 1 ];
	}
	
	/**
	 * Gets the properties of the controller
	 *
	 * @return array
	 */
	static function properties()
	{
		return array_replace( self::$properties, array( 'name' => static::name() ), static::$properties );
	}
	
	/**
	 * Gets info about the models associated with this controller
	 *
	 * @return array models
	 */
	function models()
	{
		if( !$this->models )
		{
			$properties = static::properties();
			
			$modelParams = array();
			
			$modelNames = array();
			if( $models = Util::array_value( $properties, 'models' ) )
				$modelNames = $models;
			else if( $model = Util::array_value( $properties, 'model' ) )
				$modelNames[] = $model;
			
			$this->models = array();
	
			foreach( $modelNames as $model )
			{
				$modelClassName = '\\app\\ ' . $properties[ 'name' ] . '\\models\\' . $model;
			
				$info = $modelClassName::info();
	
				$this->models[ $model ] = array_replace( $info, array(
					'api' => Util::array_value( $properties, 'api' ),
					'admin' => Util::array_value( $properties, 'admin' ),
					'route_base' => '/' . $properties[ 'name' ] . '/' . $info[ 'plural_key' ] ) );
			}
		}
		
		return $this->models;
	}	
}