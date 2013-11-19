<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.17.4
 * @copyright 2013 Jared King
 * @license MIT
 */

use infuse\Logger;

class LoggerTest extends \PHPUnit_Framework_TestCase
{
	public static function setupBeforeClass()
	{
		date_default_timezone_set( 'America/Chicago' );
	}

	public function testMonolog()
	{
		$this->assertInstanceOf( '\Monolog\Logger', Logger::logger() );
	}

	/**
	 * @depends testMonolog
	 */
	public function testConfigureHandlers()
	{
		// stream
		Logger::configure( array(
			'handlers' => array(
				'StreamHandler' => array(
					'stream' => 'php://stderr' ) ) ) );
		$this->assertInstanceOf( '\Monolog\Handler\StreamHandler', Logger::logger()->popHandler() );

		// firephp
		Logger::configure( array(
			'handlers' => array(
				'FirePHPHandler' => array(
					'level' => 'debug' ) ) ) );
		$this->assertInstanceOf( '\Monolog\Handler\FirePHPHandler', Logger::logger()->popHandler() );

		// syslog
		Logger::configure( array(
			'handlers' => array(
				'SyslogHandler' => array(
					'level' => 'warning',
					'ident' => 'test',
					'facility' => 'syslog' ) ) ) );
		$this->assertInstanceOf( '\Monolog\Handler\SyslogHandler', Logger::logger()->popHandler() );

		// error log
		Logger::configure( array(
			'handlers' => array(
				'ErrorLogHandler' => array(
					'level' => 'error' ) ) ) );
		$this->assertInstanceOf( '\Monolog\Handler\ErrorLogHandler', Logger::logger()->popHandler() );

		// native mail
		Logger::configure( array(
			'handlers' => array(
				'NativeMailerHandler' => array(
					'level' => 'notice',
					'to' => 'test@example.com',
					'from' => 'error@example.com' ) ) ) );
		$this->assertInstanceOf( '\Monolog\Handler\NativeMailerHandler', Logger::logger()->popHandler() );

		// null
		Logger::configure( array(
			'handlers' => array(
				'NullHandler' => array() ) ) );
		$this->assertInstanceOf( '\Monolog\Handler\NullHandler', Logger::logger()->popHandler() );

		// test
		Logger::configure( array(
			'handlers' => array(
				'TestHandler' => array() ) ) );
		$this->assertInstanceOf( '\Monolog\Handler\TestHandler', Logger::logger()->popHandler() );
	}

	public function testLoggerMethods()
	{
		$handler = new \Monolog\Handler\TestHandler();
		$logger = Logger::logger();
		$logger->pushHandler( $handler );

		Logger::debug( 'debug' );
		$this->assertTrue( $handler->hasDebug( 'debug' ) );

		Logger::info( 'info' );
		$this->assertTrue( $handler->hasInfo( 'info' ) );

		Logger::notice( 'notice' );
		$this->assertTrue( $handler->hasNotice( 'notice' ) );

		Logger::warning( 'warning' );
		$this->assertTrue( $handler->hasWarning( 'warning' ) );

		Logger::error( 'error' );
		$this->assertTrue( $handler->hasError( 'error' ) );

		Logger::critical( 'critical' );
		$this->assertTrue( $handler->hasCritical( 'critical' ) );

		Logger::alert( 'alert' );
		$this->assertTrue( $handler->hasAlert( 'alert' ) );

		Logger::emergency( 'emergency' );
		$this->assertTrue( $handler->hasEmergency( 'emergency' ) );
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