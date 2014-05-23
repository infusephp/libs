<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.21
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse;

class ErrorStack
{
	/////////////////////////////
	// Private Class Variables
	/////////////////////////////
	
	private static $stackInstance;
	private $stack = array();
	private $context = '';
	
	/**
	 * Gets an instance of the stack
	 *
	 * @return ErrorStack
	 */
	static function stack()
	{
		if( !self::$stackInstance )
			self::$stackInstance = new ErrorStack();
		
		return self::$stackInstance;
	}
	
	/**
	 * Adds an error message to the stack
	 *
	 * @param array $error
	 * - error: error code
	 * - params: array of parameters to be passed to message
	 * - context: (optional) the context which the error message occured in
	 * - class: (optional) the class invoking the error
	 * - function: (optional) the function invoking the error
	 *
	 * @return boolean was error valid?
	 */
	function push( array $error )
	{
		if( !isset( $error[ 'context' ] ) )
			$error[ 'context' ] = $this->context;
			
		if( !isset( $error[ 'params' ] ) )
			$error[ 'params' ] = array();
		
		if( !Util::array_value( $error, 'error' ) )
			return false;
		
		$this->stack[] = $error;
		
		return true;
	}

	/**
	 * Adds an error message to the stack statically
	 * NOTE this may be deprecated in the future
	 *
	 * @param array $error
	 * - error: error code
	 * - params: array of parameters to be passed to message
	 * - context: (optional) the context which the error message occured in
	 * - class: (optional) the class invoking the error
	 * - function: (optional) the function invoking the error
	 *
	 * @return boolean was error valid?
	 */
	static function add( $error, $class = null, $function = null, $params = array(), $context = null )
	{
		if( !is_array( $error ) )
		{
			$error = array(
				'error' => $error,
				'params' => $params,
				'class' => $class,
				'function' => $function
			);

			if( $context )
				$error[ 'context' ] = $context;
		}

		return self::stack()->push( $error, $class, $function, $params, $context );
	}

	/**
	 * Sets the current default error context
	 *
	 * @param string $context
	 */
	function setCurrentContext( $context = '' )
	{
		$this->context = $context;
	}
	
	/**
	 * Clears the current default error context
	 */
	function clearCurrentContext( )
	{
		$this->context = '';
	}
	
	/**
	 * Gets all of the errors on the stack and also attempts
	 * translation using the Locale class
	 *
	 * @param string $context optional context
	 * @param string $locale optional locale
	 *
	 * @return array errors
	 */
	function errors( $context = false, $locale = false )
	{
		$errors = array();
		
		foreach( $this->stack as $error )
		{
			if( !$context || $error[ 'context' ] == $context )
			{
				// attempt to translate error into a message
				if( !isset( $error[ 'message' ] ) )
					$error[ 'message' ] = Locale::locale()->t( $error[ 'error' ], $error[ 'params' ], $locale );

				$errors[] = $error;
			}
		}
		
		return $errors;
	}
	
	/**
	 * Gets the messages of errors on the stack
	 *
	 * @param string $context optional context
	 * @param string $locale optional locale
	 *
	 * @return array errors
	 */
	function messages( $context = null, $locale = false )
	{
		$errors = $this->errors( $context );
		
		$messages = array();
		
		foreach( $errors as $error )
			$messages[] = $error[ 'message' ];
		
		return $messages;
	}
	
	/**
	 * Gets an error for a specific parameter on the stack
	 *
	 * @param string $value value we are searching for
	 * @param string $param parameter name
	 *
	 * @return array|false
	 */	
	function find( $value, $param = 'field' )
	{
		foreach( $this->stack as $error )
		{
			if( Util::array_value( $error[ 'params' ], $param ) === $value )
				return $error;
		}
		
		return false;
	}

	/**
	 * Checks if an error exists with a specific parameter on the stack
	 *
	 * @param string $value value we are searching for
	 * @param string $param parameter name
	 *
	 * @return boolean
	 */
	function has( $value, $param = 'field' )
	{
		return $this->find( $value, $param ) !== false;
	}

	/**
	 * Clears the error stack
	 */
	function clear()
	{
		$this->stack = array();
	}
	
	/////////////////////////
	// DEPRECATED
	/////////////////////////

	/**
	 * @deprecated
	 */
	static function setContext( $context )
	{
		self::stack()->setCurrentContext( $context );
	}
	
	/**
	 * @deprecated
	 */
	static function clearContext( )
	{
		self::stack()->clearCurrentContext();
	}
}