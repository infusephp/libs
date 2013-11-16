<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.17.1
 * @copyright 2013 Jared King
 * @license MIT
 */

error_reporting( E_ALL | E_STRICT );
ini_set( 'display_errors', true );

require_once 'vendor/autoload.php';

use infuse\Request;

class RequestTest extends \PHPUnit_Framework_TestCase
{
	static $req;
	
	public static function setUpBeforeClass()
	{
		self::$req = new Request(
			// query parameters
			array(
				'test' => 'test',
				'blah' => 'blah' ),
			// request body
			array(
				'testParam' => 'test',
				'meh' => 1,
				'does' => 'this-work' ),
			// cookies
			array(),
			 // files
			array(
				'test' => array(
					'size' => 1234
				),
				'test2' => array(
					'error' => 0
				)
			),
			// server
			array(
				'REMOTE_ADDR' => '1.2.3.4',
				'SERVER_PORT' => '1234',
				'REQUEST_METHOD' => 'PUT',
				'REQUEST_URI' => '/users/comments/10',
				'ARGV' => array(
					'update',
					'force',
					'all'
				),
				'HTTP_HOST' => 'example.com',
				'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
	            'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
	            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
	            'HTTP_USER_AGENT' => 'infuse/libs test',
				'HTTP_AUTHORIZATION' => 'test',
				'HTTP_TEST_HEADER' => 'testing..123',
				'CONTENT_TYPE' => 'application/json',
				'PHP_AUTH_USER' => 'test_user',
				'PHP_AUTH_PW' => 'test_pw',
			),
			// session
			array()
		);
	}

	protected function assertPreConditions()
	{
		$this->assertInstanceOf( '\\infuse\\Request', self::$req );
	}

	public function testIp()
	{
		$this->assertEquals( '1.2.3.4', self::$req->ip() );
	}

	public function testProtocol()
	{
		$this->assertEquals( 'http', self::$req->protocol() );

		// test when HTTPS header set
		$req = new Request( null, null, null, null, array( 'HTTPS' => 'on' ) );

		$this->assertEquals( 'https', $req->protocol() );
	}

	public function testIsSecure()
	{
		$this->assertFalse( self::$req->isSecure() );

		// test when HTTPS header set
		$req = new Request( null, null, null, null, array( 'HTTPS' => 'on' ) );

		$this->assertTrue( $req->isSecure() );
	}

	public function testPort()
	{
		$this->assertEquals( 1234, self::$req->port() );
	}

	public function testHeader()
	{
		$this->assertEquals( 'testing..123', self::$req->headers( 'test_header' ) );

		$expected = array(
			'HOST' => 'example.com',
			'USER_AGENT' => 'infuse/libs test',
			'ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			'ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
			'ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
			'TEST_HEADER' => 'testing..123',
			'PHP_AUTH_USER' => 'test_user',
			'PHP_AUTH_PW' => 'test_pw',
			'AUTHORIZATION' => 'Basic dGVzdF91c2VyOnRlc3RfcHc=',
			'CONTENT_TYPE' => 'application/json' );
		$this->assertEquals( $expected, self::$req->headers() );

		$this->assertNull( self::$req->headers( 'non-existent' ) );
	}

	public function testUser()
	{
		$this->assertEquals( 'test_user', self::$req->user() );
	}

	public function testPassword()
	{
		$this->assertEquals( 'test_pw', self::$req->password() );
	}

	public function testHost()
	{
		$this->assertEquals( 'example.com', self::$req->host() );
	}

	public function testPaths()
	{
		$this->assertEquals( 'comments', self::$req->paths( 1 ) );

		$expected = array( 'users', 'comments', '10' );
		$this->assertEquals( $expected, self::$req->paths() );

		$this->assertNull( self::$req->paths( 100 ) );
	}

	public function testBasePath()
	{
		$this->assertEquals( '/users/comments/10', self::$req->basePath() );
	}

	public function testMethod()
	{
		$this->assertEquals( 'PUT', self::$req->method() );
	}

	public function testContentType()
	{
		$this->assertEquals( 'application/json', self::$req->contentType() );
	}

	public function testAccepts()
	{
		$expected = array(
		    array(
				'main_type' => 'text',
	            'sub_type' => 'html',
	            'precedence' => 1,
	            'tokens' => '', ),
	        array(
	            'main_type' => 'application',
	            'sub_type' => 'xhtml+xml',
	            'precedence' => 1,
	            'tokens' => '' ),
	        array(
	            'main_type' => 'application',
	            'sub_type' => 'xml',
	            'precedence' => 0.9,
	            'tokens' => '' ),
	        array(
	            'main_type' => '*',
	            'sub_type' => '*',
	            'precedence' => 0.8,
	            'tokens' => '' ) );

		$this->assertEquals( $expected, self::$req->accepts() );
	}

	public function testCharsets()
	{
		$expected = array(
		    array(
				'main_type' => 'ISO-8859-1',
	            'sub_type' => '',
	            'precedence' => 1,
	            'tokens' => '', ),
	        array(
	            'main_type' => 'utf-8',
	            'sub_type' => '',
	            'precedence' => 0.7,
	            'tokens' => '' ),
	        array(
	            'main_type' => '*',
	            'sub_type' => '',
	            'precedence' => 0.7,
	            'tokens' => '' ) );

		$this->assertEquals( $expected, self::$req->charsets() );
	}

	public function testLanguages()
	{
		$expected = array(
		    array(
				'main_type' => 'en-us',
	            'sub_type' => '',
	            'precedence' => 1,
	            'tokens' => '', ),
	        array(
	            'main_type' => 'en',
	            'sub_type' => '',
	            'precedence' => 0.5,
	            'tokens' => '' ) );

		$this->assertEquals( $expected, self::$req->languages() );
	}

	public function testAgent()
	{
		$this->assertEquals( 'infuse/libs test', self::$req->agent() );
	}

	public function testIsHtml()
	{
		$this->assertTrue( self::$req->isHtml() );

		$req = new Request( null, null, null, null, array( 'HTTP_ACCEPT' => 'application/json' ) );		
		$this->assertFalse( $req->isHtml() );
	}

	public function testIsJson()
	{
		$this->assertFalse( self::$req->isJson() );

		$req = new Request( null, null, null, null, array( 'HTTP_ACCEPT' => 'application/json' ) );		
		$this->assertTrue( $req->isJson() );
	}

	public function testIsXml()
	{
		$this->assertTrue( self::$req->isXml() );

		$req = new Request( null, null, null, null, array( 'HTTP_ACCEPT' => 'application/json' ) );		
		$this->assertFalse( $req->isXml() );
	}

	public function testIsXhr()
	{
		$this->assertFalse( self::$req->isXhr() );

		$req = new Request( null, null, null, null, array( 'HTTP_X-REQUESTED-WITH' => 'XMLHttpRequest' ) );		
		$this->assertFalse( $req->isXhr() );
	}

	public function testIsApi()
	{
		$this->assertTrue( self::$req->isApi() );

		$req = new Request( null, null, null, null, array() );
		$this->assertFalse( $req->isApi() );

	}

	public function testIsCli()
	{
		if( !defined( 'STDIN' ) ) define( 'STDIN', true );
		$this->assertTrue( self::$req->isCli() );
	}

	public function testParams()
	{
		$expected = array( 'test' => 1, 'test2' => 'meh' );
		self::$req->setParams( $expected );

		$this->assertEquals( 'meh', self::$req->params( 'test2' ) );
		$this->assertEquals( $expected, self::$req->params() );

		$this->assertNull( self::$req->params( 'non-existent' ) );
	}

	public function testQuery()
	{
		$this->assertEquals( 'test', self::$req->query( 'test' ) );

		$expected = array(
			'test' => 'test',
			'blah' => 'blah' );
		$this->assertEquals( $expected, self::$req->query() );

		$this->assertNull( self::$req->query( 'non-existent' ) );
	}

	public function testRequest()
	{
		$this->assertEquals( 'test', self::$req->request( 'testParam' ) );

		$expected = array(
			'testParam' => 'test',
			'meh' => 1,
			'does' => 'this-work' );
		$this->assertEquals( $expected, self::$req->request() );

		$this->assertNull( self::$req->request( 'non-existent' ) );
	}

	public function testSetCookie()
	{
		$this->assertTrue( self::$req->setCookie( 'test', 'testValue', time() + 3600, '/', 'example.com', true, true, true ) );
		$this->assertTrue( self::$req->setCookie( 'test2', 'testValue2', time() + 3600, '/', 'example.com', true, true, true ) );
	}

	/**
	 * @depends testSetCookie
	 */
	public function testCookies()
	{
		$this->assertEquals( 'testValue', self::$req->cookies( 'test' ) );

		$expected = array(
			'test' => 'testValue',
			'test2' => 'testValue2'
		);

		$this->assertEquals( $expected, self::$req->cookies() );

		$this->assertNull( self::$req->cookies( 'non-existent' ) );
	}

	public function testFiles()
	{
		$this->assertEquals( array( 'size' => 1234 ), self::$req->files( 'test' ) );

		$expected = array(
			'test' => array(
				'size' => 1234
			),
			'test2' => array(
				'error' => 0
			)
		);

		$this->assertEquals( $expected, self::$req->files() );

		$this->assertNull( self::$req->files( 'non-existent' ) );
	}

	public function testSetSession()
	{
		global $_SESSION;
		$_SESSION = array();

		self::$req->setSession( 'test', 'test' );
		self::$req->setSession( 'test2', 2 );
	}

	/**
	 * @depends testSetSession
	 */
	public function testSession()
	{
		$this->assertEquals( 'test', self::$req->session( 'test' ) );

		$expected = array( 'test' => 'test', 'test2' => 2 );
		$this->assertEquals( $expected, self::$req->session() );

		$this->assertNull( self::$req->session( 'non-existent' ) );		
	}

	/**
	 * @depends testSession
	 */
	public function testDestroySession()
	{
		self::$req->destroySession();

		$this->assertNull( self::$req->session( 'test' ) );
		$this->assertEquals( array(), self::$req->session() );
	}

	public function testCliArgs()
	{
		$expected = array( 'update', 'force', 'all' );

		$this->assertEquals( 'force', self::$req->cliArgs( 1 ) );
		$this->assertEquals( $expected, self::$req->cliArgs() );

		$this->assertNull( self::$req->cliArgs( 100 ) );
	}
}