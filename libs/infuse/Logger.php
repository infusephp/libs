<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.17.4
 * @copyright 2013 Jared King
 * @license MIT
 */

namespace infuse;

class Logger
{
	private static $monolog;
	
	private static $config = array(
		'productionLevel' => false
	);
	
	/**
	 * Sets up the handlers used by monolog
	 *
	 * @param array $config array of handlers and corresponding settings
	 */
	static function configure( array $config )
	{
		foreach( (array)Util::array_value( $config, 'handlers' ) as $handler => $handlerSettings )
			self::addHandler( $handler, $handlerSettings );

		self::$config = array_replace( self::$config, (array)$config );
	}

	/**
	 * Returns the monolog instance used
	 *
	 * @return \Monolog\Logger
	 */
	static function logger()
	{
		if( !self::$monolog )
			self::$monolog = new \Monolog\Logger( 'infuse' );
		
		return self::$monolog;
	}

	/**
	 * Creates a log entry
	 *
	 * @param string $str message
	 */
	static function emergency( $str )
	{
		self::logger()->addEmergency( $str );	
	}
	
	/**
	 * Creates a log entry
	 *
	 * @param string $str message
	 */
	static function alert( $str )
	{
		self::logger()->addAlert( $str );	
	}
	
	/**
	 * Creates a log entry
	 *
	 * @param string $str message
	 */
	static function critical( $str )
	{
		self::logger()->addCritical( $str );	
	}
	
	/**
	 * Creates a log entry
	 *
	 * @param string $str message
	 */
	static function error( $str )
	{
		self::logger()->addError( $str );
	}
	
	/**
	 * Creates a log entry
	 *
	 * @param string $str message
	 */
	static function warning( $str )
	{
		self::logger()->addWarning( $str );	
	}
	
	/**
	 * Creates a log entry
	 *
	 * @param string $str message
	 */
	static function notice( $str )
	{
		self::logger()->addNotice( $str );	
	}
	
	/**
	 * Creates a log entry
	 *
	 * @param string $str message
	 */
	static function info( $str )
	{
		self::logger()->addInfo( $str );	
	}
	
	/**
	 * Creates a log entry
	 *
	 * @param string $str message
	 */
	static function debug( $str )
	{
		self::logger()->addDebug( $str );
	}
	
	/**
	 * Handles a PHP error
	 */
	static function phpErrorHandler( $errno, $errstr, $errfile, $errline, $errcontext )
	{
		$formattedErrorString = Logger::formatPhpError( $errno, $errstr, $errfile, $errline, $errcontext );
		
		if( !self::$config[ 'productionLevel' ] )
			echo "<pre>$formattedErrorString</pre>";
	
		switch( $errno )
		{
		case E_CORE_ERROR:
		case E_COMPILE_ERROR:
		case E_ERROR:
		case E_PARSE:
			Logger::error( $formattedErrorString );
			die();
		break;
		case E_USER_ERROR:
		case E_RECOVERABLE_ERROR:
			Logger::error( $formattedErrorString );
		break;
		case E_WARNING:
		case E_CORE_WARNING:
		case E_COMPILE_WARNING:
		case E_USER_WARNING:
		case E_NOTICE:
		case E_USER_NOTICE:
		case E_DEPRECATED:
		case E_USER_DEPRECATED:
		case E_STRICT:
			Logger::warning( $formattedErrorString );
		break;
		}
		
		return true;
	}
	
	
	/**
	 * Formats a PHP error into a log message
	 *
	 * @param ing $errno error type
	 * @param string $errstr error message
	 * @param int $errline line the error occurred on
	 * @param array $errcontext currently not used
	 *
	 * @return string
	 */
	static function formatPhpError( $errno, $errstr, $errfile, $errline, $errcontext )
	{
		$errtype = '';
		switch( $errno )
		{
		case E_ERROR:
			$errtype = 'E_ERROR';
		break;
		case E_CORE_ERROR:
			$errtype = 'E_CORE_ERROR';
		break;
		case E_COMPILE_ERROR:
			$errtype = 'E_COMPILE_ERROR';
		break;
		case E_PARSE:
			$errtype = 'E_PARSE';
		break;
		case E_USER_ERROR:
			$errtype = 'E_USER_ERROR';
		break;
		case E_RECOVERABLE_ERROR:
			$errtype = 'E_RECOVERABLE_ERROR';
		break;
		case E_WARNING:
			$errtype = 'E_WARNING';
		break;
		case E_CORE_WARNING:
			$errtype = 'E_CORE_WARNING';
		break;
		case E_COMPILE_WARNING:
			$errtype = 'E_COMPILE_WARNING';
		break;
		case E_USER_WARNING:
			$errtype = 'E_USER_WARNING';
		break;
		case E_NOTICE:
			$errtype = 'E_NOTICE';
		break;
		case E_USER_NOTICE:
			$errtype = 'E_USER_NOTICE';
		break;
		case E_DEPRECATED:
			$errtype = 'E_DEPRECATED';
		break;
		case E_USER_DEPRECATED:
			$errtype = 'E_USER_DEPRECATED';
		break;
		case E_STRICT:
			$errtype = 'E_STRICT';
		break;		
		}
	
		return  "$errfile ($errline): $errstr - $errtype";
	}
	
	/**
	 * Handles an exception
	 */
	static function exceptionHandler( $exception )
	{
		$formattedExceptionString = Logger::formatException( $exception );
			
		if( !self::$config[ 'productionLevel' ] )
			echo $formattedExceptionString;
	
		Logger::error( $formattedExceptionString );
		
		die();
	}	
	
	/** 
	 * Formats an exception into a log message
	 *
	 * @param Exception $exception
	 *
	 * @return string
	 */
	static function formatException( \Exception $exception )
	{
		return $exception->getMessage();
	}
	
	/**
	 * Adds a handler to the logger
	 *
	 * @param string $handler monolog handler name
	 * @param array $settings settings
	 */
	private static function addHandler( $handler, array $settings )
	{
		if( empty( $handler ) )
			return;
	
		$logger = self::logger();
		
		$handlerObj = null;
		
		$level = \Monolog\Logger::INFO;
		
		switch( strtolower( $settings[ 'level' ] ) )
		{
		case 'debug':
			$level = \Monolog\Logger::DEBUG;
		break;
		case 'info':
			$level = \Monolog\Logger::INFO;
		break;
		case 'notice':
			$level = \Monolog\Logger::NOTICE;
		break;
		case 'warning':
			$level = \Monolog\Logger::WARNING;
		break;
		case 'error':
			$level = \Monolog\Logger::ERROR;
		break;
		case 'critical':
			$level = \Monolog\Logger::CRITICAL;
		break;
		case 'alert':
			$level = \Monolog\Logger::ALERT;
		break;
		case 'emergency':
			$level = \Monolog\Logger::EMERGENCY;
		break;
		}
		
		switch( $handler )
		{
		case 'FirePHPHandler':
			$handlerObj = new \Monolog\Handler\FirePHPHandler( $level );
		break;
		case 'SyslogHandler':
			$handlerObj = new \Monolog\Handler\SyslogHandler( $settings[ 'ident' ], $settings[ 'facility' ], $level );
		break;
		case 'NativeMailerHandler':
			$handlerObj = new \Monolog\Handler\NativeMailerHandler( $settings[ 'to' ], $settings[ 'from' ], $level );
		break;
		default:
			return false;
		break;
		}

		$logger->pushHandler( $handlerObj );
	}

	/**
	 * @deprecated
	 */
	static function setConfig( $config )
	{
		return self::configure( $config );
	}
}