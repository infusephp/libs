<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.21.1
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\Response;
use infuse\Request;

class ResponseTest extends \PHPUnit_Framework_TestCase
{
	static $res;

	public static function setUpBeforeClass()
	{
		self::$res = new Response();
	}

	public function assertPreConditions()
	{
		$this->assertInstanceOf( '\\infuse\\Response', self::$res );
	}

	public function testSetCode()
	{
		self::$res->setCode( 502 );
	}

	/**
	 * @depends testSetCode
	 */
	public function testGetCode()
	{
		$this->assertEquals( 502, self::$res->getCode() );
	}

	public function testSetBody()
	{
		self::$res->setBody( 'test' );
	}

	/**
	 * @depends testSetBody
	 */
	public function testGetBody()
	{
		$this->assertEquals( 'test', self::$res->getBody() );
	}

	public function testSetContentType()
	{
		self::$res->setContentType( 'application/pdf' );
	}

	/**
	 * @depends testSetContentType
	 */
	public function testGetContentType()
	{
		$this->assertEquals( 'application/pdf', self::$res->getContentType() );
	}

	/**
	 * @depends testGetBody
	 * @depends testGetContentType
	 */
	public function testSetBodyJson()
	{
		$body = [
			'test' => [
				'meh',
				'blah' ] ];

		self::$res->setBodyJson( $body );

		$this->assertEquals( json_encode( $body ), self::$res->getBody() );
		$this->assertEquals( 'application/json', self::$res->getContentType() );
	}

	public function testRedirect()
	{
		$req = new Request( null, null, null, null, [
			'HTTP_HOST' => 'example.com',
			'DOCUMENT_URI' => '/some/start',
			'REQUEST_URI' => '/some/start/test/index.php' ] );

		$this->assertEquals( 'Location: //example.com/some/start/', self::$res->redirect( '/', $req, false ) );
		$this->assertEquals( 'Location: //example.com/some/start/test/url', self::$res->redirect( '/test/url', $req, false ) );

		$this->assertEquals( 'Location: http://test.com', self::$res->redirect( 'http://test.com', $req, false ) );
		$this->assertEquals( 'Location: http://test.com', self::$res->redirect( 'http://test.com', null, false ) );
	}

	public function testRedirectNonStandardPort()
	{
		$req = new Request( null, null, null, null, [
			'HTTP_HOST' => 'example.com:1234',
			'DOCUMENT_URI' => '/some/start',
			'REQUEST_URI' => '/some/start/test/index.php',
			'SERVER_PORT' => 5000 ] );

		$this->assertEquals( 'Location: //example.com:1234/some/start/', self::$res->redirect( '/', $req, false ) );
		$this->assertEquals( 'Location: //example.com:1234/some/start/test/url', self::$res->redirect( '/test/url', $req, false ) );
	}

	public function testSend()
	{
		$req = new Request( null, null, null, null, [] );

		self::$res->setBody( 'test' );

		ob_start();

		self::$res->send( $req, false, false );

		$output = ob_get_contents();
		ob_end_clean();

		$this->assertEquals( 'test', $output );
	}
}