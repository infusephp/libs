<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.14.8
 * @copyright 2013 Jared King
 * @license MIT
 */

namespace infuse;

class ErrorStack
{
	/////////////////////////////
	// Private Class Variables
	/////////////////////////////
	
	private static $stack = array();
	private static $context = '';
	private static $stackInstance;
	
	/**
	 * Gets an instance of the stack
	 *
	 * @return ErrorStack
	 */
	public static function stack()
	{
		if( !self::$stackInstance )
			self::$stackInstance = new ErrorStack();
		
		return self::$stackInstance;
	}
	
	/**
	 * @deprecated
	 */
	public static function it()
	{
		return self::stack();
	}
	
	////////////////////////////
	// GETTERS
	////////////////////////////
	
	/**
	 * Gets all of the errors on the stack
	 *
	 * @param string $context optional context
	 *
	 * @return array errors
	 */
	function errors( $context = null )
	{
		if( $context )
		{
			$errors = array();
			
			foreach( self::$stack as $error )
			{
				if( $error[ 'context' ] == $context )
					$errors[] = $error;
			}
			
			return $errors;
		}
		else
			return self::$stack;
	}
	
	function messages( $context = null )
	{
		$errors = self::errors( $context );
		
		$messages = array();
		
		foreach( $errors as $error )
			$messages[] = $error[ 'message' ];
		
		return $messages;
	}
	
	/**
	 * Checks if an error exists with a specific property on the stack
	 *
	 * @param string $value value we are searching for
	 * @param string $parameter parameter name
	 *
	 * @return boolean
	 */
	function has( $value, $parameter = 'field' )
	{
		return (boolean)$this->find( $value, $parameter );
	}
	
	/**
	 * Gets an error for a specific property on the stack
	 *
	 * @param string $value value we are searching for
	 * @param string $parameter parameter name
	 *
	 * @return array|false
	 */	
	function find( $value, $parameter = 'field' )
	{
		foreach( self::$stack as $error )
		{
			if( Util::array_value( $error[ 'params' ], $parameter ) === $value )
				return $error;
		}
		
		return false;	
	}

	/////////////////////////////////////
	// SETTERS
	/////////////////////////////////////
	
	/**
	* Sets the context for all errors created.
	*
	* Unless explicitly overridden all errors will be created with the current context. Don't forget to clear
	* the context when finished with it.
	*
	* @param string context
	*
	* @return null
	*/
	public static function setContext( $context )
	{
		self::$context = $context;
	}
	
	/**
	* Clears the error context
	*
	* @return null
	*/
	public static function clearContext( )
	{
		self::$context = '';
	}	
	
	/**
	* Adds an error message to the stack
	*
	* @param array $message message
	* - error: error code
	* - params: array of parameters to be passed to message
	* - message: (optional) the error message, this is typically generated automatically from the \infuse\Messages class
	* - context: (optional) the context which the error message occured in
	* - class: (optional) the class invoking the error
	* - function: (optional) the function invoking the error
	*
	* N.B.: the other arguments are here for compatibility, for now, aim to remove them eventually
	*
	* @return boolean true if successful
	*/
	public static function add( $error, $class = null, $function = null, $params = array(), $context = null )
	{
		// all of the arguments will be deprecated soon...
		if( !is_array( $error ) )
		{
			$error = array(
				'error' => $error,
				'params' => $params,
				'context' => ($context) ? $context : self::$context,
				'class' => $class,
				'function' => $function,
				'message' => Messages::get( $error, $params )
			);
		}
		else
		{
			if( !isset( $error[ 'context' ] ) )
				$error[ 'context' ] = self::$context;
				
			if( !isset( $error[ 'params' ] ) )
				$error[ 'params' ] = array();
			
			if( !isset( $error[ 'message' ] ) )
				$error[ 'message' ] = Messages::get( $error[ 'error' ], $error[ 'params' ] );
		}
		
		if( !Util::array_value( $error, 'error' ) )
			return false;
	
		if( !Util::array_value( $error, 'function' ) )
		{
			// try to look up the call history using debug_backtrace()
			$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 );
			if( isset( $trace[ 1 ] ) )
			{
				// $trace[0] is ourself
				// $trace[1] is our caller
				// and so on...
				$error[ 'class' ] = $trace[1]['class'];
				$error[ 'function' ] = $trace[1]['function'];
			}
		}
		
		self::$stack[] = $error;
		
		return true;
	}
}