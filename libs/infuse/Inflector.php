<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.21.1
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse;

use ICanBoogie\Inflector as iCBInflector;

/*
	This class is a wrapper for iCanBoogie/Inflector by Olivier Laviale:
		https://github.com/ICanBoogie/Inflector
	A wrapper was created instead of using the inflector class directly
	because this class signature was used heavily in other projects.
*/

class Inflector {
	private static $inflector;

	/**
	 * Pluralizes a given word
	 *
	 * @param string $word
	 *
	 * @return string pluralized word
	 */
	static function pluralize( $word )
	{
		return self::inflector()->pluralize( $word );
	}

	/**
	 * Singularizes a given word
	 *
	 * @param string $word
	 *
	 * @return string singularized word
	 */
	static function singularize( $word )
	{
		return self::inflector()->singularize( $word );
	}

	/**
	 * Camelizes a given word, i.e. some word -> SomeWord
	 *
	 * @param string $word
	 * @param boolean $variable when true, makes the first letter lowercase
	 *
	 * @return string camelized word
	 */
	static function camelize( $word, $variable = false )
	{
		return self::inflector()->camelize( $word, $variable );
	}

	/**
	 * Converts spaces to underscores
	 *
	 * @param string $word
	 *
	 * @return string underscored word
	 */
	static function underscore( $word )
	{
		return self::inflector()->underscore( $word );
	}

	/**
	 * Converts a word into a human-friendly form
	 *
	 * @param string $word
	 *
	 * @return string humanized word
	 */
	static function humanize( $word )
	{
		return self::inflector()->humanize( $word );
	}

	/**
	 * Titleizes a word
	 *
	 * @param string $word
	 *
	 * @return string titleized word
	 */
	static function titleize( $word )
	{
		return self::inflector()->titleize( $word );
	}

	/**
	 * Returns the ordinal for a number
	 *
	 * @param int $n
	 *
	 * @return string ordinal
	 */
	static function ordinal( $n )
	{
		return self::inflector()->ordinal( $n );
	}

	/**
	 * Ordinalizes a number
	 *
	 * @param int $n
	 *
	 * @return string ordinalized number
	 */
	static function ordinalize( $n )
	{
		return self::inflector()->ordinalize( $n );
	}

	private static function inflector()
	{
		if( !self::$inflector )
			self::$inflector = iCBInflector::get();

		return self::$inflector;
	}
}