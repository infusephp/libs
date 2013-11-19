<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.17.4
 * @copyright 2013 Jared King
 * @license MIT
 */

error_reporting( E_ALL | E_STRICT );
ini_set( 'display_errors', true );

require_once 'vendor/autoload.php';

use infuse\Logger;

class LoggerTest extends \PHPUnit_Framework_TestCase
{
	public function testConfigureHandlers()
	{
		// firephp
		Logger::configure( array(
			'handlers' => array(
				'FirePHPHandler' => array(
					'level' => 'debug' ) ) ) );

		// syslog
		Logger::configure( array(
			'handlers' => array(
				'SyslogHandler' => array(
					'level' => 'warning',
					'ident' => 'test',
					'facility' => 'syslog' ) ) ) );

		// error log
		Logger::configure( array(
			'handlers' => array(
				'ErrorLogHandler' => array(
					'level' => 'error' ) ) ) );

		// native mail
		Logger::configure( array(
			'handlers' => array(
				'ErrorLogHandler' => array(
					'level' => 'notice',
					'to' => 'test@example.com',
					'from' => 'error@example.com' ) ) ) );
	}

	public function testMonolog()
	{
		$this->assertInstanceOf( '\Monolog\Logger', Logger::logger() );
	}

	public function testFormatPhpError()
	{
		$errorStr = Logger::formatPhpError( E_USER_ERROR, 'This is an error.', 'index.php', 103, false );

		$this->assertGreaterThan( 1, strlen( $errorStr ) );
	}

	public function testFormatException()
	{
		$exception = new \Exception( 'Some exception' );

		$errorStr = Logger::formatException( $exception );

		$this->assertGreaterThan( 1, strlen( $errorStr ) );
	}
}