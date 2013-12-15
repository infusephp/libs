<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.18
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

	private static $monologLevels = array(
		'debug' => \Monolog\Logger::DEBUG,
		'info' => \Monolog\Logger::INFO,
		'notice' => \Monolog\Logger::NOTICE,
		'warning' => \Monolog\Logger::WARNING,
		'error' => \Monolog\Logger::ERROR,
		'critical' => \Monolog\Logger::CRITICAL,
		'alert' => \Monolog\Logger::ALERT,
		'emergency' => \Monolog\Logger::EMERGENCY );

	private static $phpErrorLevels = array(
		E_ERROR => 'E_ERROR',
		E_CORE_ERROR => 'E_CORE_ERROR',
		E_COMPILE_ERROR => 'E_COMPILE_ERROR',
		E_PARSE => 'E_PARSE',
		E_USER_ERROR => 'E_USER_ERROR',
		E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
		E_WARNING => 'E_WARNING',
		E_CORE_WARNING => 'E_CORE_WARNING',
		E_COMPILE_WARNING => 'E_COMPILE_WARNING',
		E_USER_WARNING => 'E_USER_WARNING',
		E_NOTICE => 'E_NOTICE',
		E_USER_NOTICE => 'E_USER_NOTICE',
		E_DEPRECATED => 'E_DEPRECATED',
		E_USER_DEPRECATED => 'E_USER_DEPRECATED',
		E_STRICT => 'E_STRICT' );
	
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
		$errtype = (isset(self::$phpErrorLevels[$errno])) ? self::$phpErrorLevels[ $errno ] : '';
	
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
		
		$l = strtolower( Util::array_value( $settings, 'level' ) );
		$level = (isset(self::$monologLevels[$l])) ? self::$monologLevels[ $l ] : \Monolog\Logger::INFO;

		switch( $handler )
		{
		// these handlers require only 1 argument
		case 'NullHandler':
		case 'FirePHPHandler':
		case 'TestHandler':
			$handlerClass = "\\Monolog\\Handler\\$handler";
			$handlerObj = new $handlerClass( $level );
		break;
		// these handlers require custom arguments
		case 'StreamHandler':
			$handlerObj = new \Monolog\Handler\StreamHandler( $settings[ 'stream' ], $level );
		break;
		case 'ErrorLogHandler':
			$handlerObj = new \Monolog\Handler\ErrorLogHandler( \Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM, $level );
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
}