<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.21
 * @copyright 2014 Jared King
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
		// false
		try
		{
			Logger::configure( [
				'handlers' => [] ] );
			Logger::logger()->popHandler();
		}
		catch( \Exception $e )
		{
			$this->assertInstanceOf( '\Exception', $e );
		}

		// stream
		Logger::configure( [
			'handlers' => [
				'StreamHandler' => [
					'stream' => 'php://stderr' ] ] ] );
		$this->assertInstanceOf( '\Monolog\Handler\StreamHandler', Logger::logger()->popHandler() );

		// firephp
		Logger::configure( [
			'handlers' => [
				'FirePHPHandler' => [
					'level' => 'debug' ] ] ] );
		$this->assertInstanceOf( '\Monolog\Handler\FirePHPHandler', Logger::logger()->popHandler() );

		// syslog
		Logger::configure( [
			'handlers' => [
				'SyslogHandler' => [
					'level' => 'warning',
					'ident' => 'test',
					'facility' => 'syslog' ] ] ] );
		$this->assertInstanceOf( '\Monolog\Handler\SyslogHandler', Logger::logger()->popHandler() );

		// error log
		Logger::configure( [
			'handlers' => [
				'ErrorLogHandler' => [
					'level' => 'error' ] ] ] );
		$this->assertInstanceOf( '\Monolog\Handler\ErrorLogHandler', Logger::logger()->popHandler() );

		// native mail
		Logger::configure( [
			'handlers' => [
				'NativeMailerHandler' => [
					'level' => 'notice',
					'to' => 'test@example.com',
					'from' => 'error@example.com' ] ] ] );
		$this->assertInstanceOf( '\Monolog\Handler\NativeMailerHandler', Logger::logger()->popHandler() );

		// null
		Logger::configure( [
			'handlers' => [
				'NullHandler' => [] ] ] );
		$this->assertInstanceOf( '\Monolog\Handler\NullHandler', Logger::logger()->popHandler() );

		// test
		Logger::configure( [
			'handlers' => [
				'TestHandler' => [] ] ] );
		$this->assertInstanceOf( '\Monolog\Handler\TestHandler', Logger::logger()->popHandler() );

		// test
		Logger::configure( [
			'handlers' => [
				'bogus' => [] ] ] );
	}

	public function testClearHandlers()
	{
		Logger::clearHandlers();

		// TODO verify
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
}