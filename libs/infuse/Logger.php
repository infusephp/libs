<?php

/**
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

class Logger
{
	private static $monolog;
	
	/**
	 * Returns the monolog instance used
	 *
	 * @return \Monolog\Logger
	 */
	static function logger()
	{
		if( !self::$monolog )
			self::$monolog = new \Monolog\Logger('infuse');
		
		return self::$monolog;
	}
	
	/**
	 * Sets up the handlers used by monolog
	 *
	 * @param array $config array of handlers and corresponding settings
	 */
	static function setConfig( $config )
	{
		foreach( (array)$config as $handler => $handlerSettings )
			self::addHandler( $handler, $handlerSettings );
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
	 * Formats an exception into a log message
	 *
	 * @param Exception $exception
	 *
	 * @return string
	 */
	static function formatException( $exception )
	{
		return $exception->getMessage();
	}
	
	/**
	 * Adds a handler to the logger
	 *
	 * @param string $handler monolog handler name
	 * @param array $settings settings
	 */
	private static function addHandler( $handler, $settings )
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
}