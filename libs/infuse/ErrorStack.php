<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.16.4
 * @copyright 2013 Jared King
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
	public static function stack()
	{
		if( !self::$stackInstance )
			self::$stackInstance = new ErrorStack();
		
		return self::$stackInstance;
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
			
			foreach( $this->stack as $error )
			{
				if( $error[ 'context' ] == $context )
					$errors[] = $error;
			}
			
			return $errors;
		}
		else
			return $this->stack;
	}
	
	/**
	 * Gets the messages of errors on the stack
	 *
	 * @param string $context optional context
	 *
	 * @return array errors
	 */
	function messages( $context = null )
	{
		$errors = $this->errors( $context );
		
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
		foreach( $this->stack as $error )
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
	function push( array $error )
	{
		if( !isset( $error[ 'context' ] ) )
			$error[ 'context' ] = $this->context;
			
		if( !isset( $error[ 'params' ] ) )
			$error[ 'params' ] = array();
		
		if( !isset( $error[ 'message' ] ) )
			$error[ 'message' ] = Messages::get( $error[ 'error' ], $error[ 'params' ] );
		
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
		
		$this->stack[] = $error;
		
		return true;
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

	/////////////////////////
	// DEPRECATED
	/////////////////////////
	
	/**
	 * @deprecated
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

		return self::stack()->push( $error, $class, $function, $params, $context );
	}

	/**
	 * @deprecated
	 */
	public static function setContext( $context )
	{
		self::stack()->setCurrentContext( $context );
	}
	
	/**
	 * @deprecated
	 */
	public static function clearContext( )
	{
		self::stack()->clearCurrentContext();
	}

	/**
	 * @deprecated
	 */
	public static function it()
	{
		return self::stack();
	}	
}