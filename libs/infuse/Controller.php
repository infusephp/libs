<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.16.3
 * @copyright 2013 Jared King
 * @license MIT
 */

namespace infuse;

abstract class Controller extends Acl
{
	protected static $models;

	/**
	 * Gets info about the models associated with this controller
	 *
	 * @return array models
	 */
	static function models()
	{
		if( !self::$models )
		{
			$properties = static::$properties;
			$module = self::name();
			
			self::$models = array();
			
			foreach( (array)Util::array_value( $properties, 'models' ) as $model )
			{
				$modelClassName = '\\app\\' . $module . '\\models\\' . $model;
				
				$info = $modelClassName::info();
				
				self::$models[ $model ] = array_replace( $info, array(
					'route_base' => '/' . $module . '/' . $info[ 'plural_key' ] ) );
			}
		}
		
		return self::$models;
	}
}