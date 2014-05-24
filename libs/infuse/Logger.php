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

class Logger
{
	private static $monolog;
	
	private static $config = [
		'productionLevel' => false
	];

	private static $monologLevels = [
		'debug' => \Monolog\Logger::DEBUG,
		'info' => \Monolog\Logger::INFO,
		'notice' => \Monolog\Logger::NOTICE,
		'warning' => \Monolog\Logger::WARNING,
		'error' => \Monolog\Logger::ERROR,
		'critical' => \Monolog\Logger::CRITICAL,
		'alert' => \Monolog\Logger::ALERT,
		'emergency' => \Monolog\Logger::EMERGENCY ];
	
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

	static function clearHandlers()
	{
		try
		{
			while( 1 )
				self::logger()->popHandler();
		}
		catch( \LogicException $e )
		{ }
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