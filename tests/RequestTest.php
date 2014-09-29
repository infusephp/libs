<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.2.2
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\Request;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    public static $req;

    public static function setUpBeforeClass()
    {
        self::$req = new Request(
            // query parameters
            [
                'test' => 'test',
                'blah' => 'blah' ],
            // request body
            [
                'testParam' => 'test',
                'meh' => 1,
                'does' => 'this-work' ],
            // cookies
            [],
             // files
            [
                'test' => [
                    'size' => 1234
                ],
                'test2' => [
                    'error' => 0
                ]
            ],
            // server
            [
                'REMOTE_ADDR' => '1.2.3.4',
                'SERVER_PORT' => '1234',
                'REQUEST_METHOD' => 'PUT',
                'REQUEST_URI' => '/users/comments/10',
                'argv' => [
                    'update',
                    'force',
                    'all'
                ],
                'HTTP_HOST' => 'example.com:1234',
                'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
                'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
                'HTTP_USER_AGENT' => 'infuse/libs test',
                'HTTP_AUTHORIZATION' => 'test',
                'HTTP_TEST_HEADER' => 'testing..123',
                'CONTENT_TYPE' => 'application/json',
                'PHP_AUTH_USER' => 'test_user',
                'PHP_AUTH_PW' => 'test_pw',
            ],
            // session
            []
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
        $req = new Request( null, null, null, null, [ 'HTTPS' => 'on' ] );

        $this->assertEquals( 'https', $req->protocol() );
    }

    public function testIsSecure()
    {
        $this->assertFalse( self::$req->isSecure() );

        // test when HTTPS header set
        $req = new Request( null, null, null, null, [ 'HTTPS' => 'on' ] );

        $this->assertTrue( $req->isSecure() );
    }

    public function testPort()
    {
        $this->assertEquals( 1234, self::$req->port() );
    }

    public function testHeader()
    {
        $this->assertEquals( 'testing..123', self::$req->headers( 'test_header' ) );

        $expected = [
            'HOST' => 'example.com:1234',
            'USER_AGENT' => 'infuse/libs test',
            'ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
            'ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'TEST_HEADER' => 'testing..123',
            'PHP_AUTH_USER' => 'test_user',
            'PHP_AUTH_PW' => 'test_pw',
            'AUTHORIZATION' => 'Basic dGVzdF91c2VyOnRlc3RfcHc=',
            'CONTENT_TYPE' => 'application/json' ];
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

    public function testUrl()
    {
        $this->assertEquals( 'http://example.com:1234/users/comments/10', self::$req->url() );
    }

    public function testPaths()
    {
        $this->assertEquals( 'comments', self::$req->paths( 1 ) );

        $expected = [ 'users', 'comments', '10' ];
        $this->assertEquals( $expected, self::$req->paths() );

        $this->assertNull( self::$req->paths( 100 ) );
    }

    public function testBasePath()
    {
        $this->assertEquals( '/', self::$req->basePath() );
    }

    public function testPath()
    {
        $this->assertEquals( '/users/comments/10', self::$req->path() );
    }

    public function testRemovingStartPath()
    {
        $req = new Request( null, null, null, null, [ 'REQUEST_URI' => '/some/start/path/test', 'DOCUMENT_URI' => '/some/start/path'  ] );

        $expected = [ 'test' ];
        $this->assertEquals( $expected, $req->paths() );

        $this->assertEquals( '/some/start/path', $req->basePath() );

        $this->assertEquals( '/test', $req->path() );
    }

    public function testMethod()
    {
        $this->assertEquals( 'PUT', self::$req->method() );
    }

    public function testMethodFromPost()
    {
        $req = new Request( null, [ 'method' => 'DELETE' ], null, null, [ 'REQUEST_METHOD' => 'POST' ] );
        $this->assertEquals( 'DELETE', $req->method() );

        $req = new Request( null, [ 'method' => 'PUT' ], null, null, [ 'REQUEST_METHOD' => 'POST' ] );
        $this->assertEquals( 'PUT', $req->method() );
    }

    public function testContentType()
    {
        $this->assertEquals( 'application/json', self::$req->contentType() );
    }

    public function testAccepts()
    {
        $expected = [
            [
                'main_type' => 'text',
                'sub_type' => 'html',
                'precedence' => 1,
                'tokens' => '', ],
            [
                'main_type' => 'application',
                'sub_type' => 'xhtml+xml',
                'precedence' => 1,
                'tokens' => '' ],
            [
                'main_type' => 'application',
                'sub_type' => 'xml',
                'precedence' => 0.9,
                'tokens' => '' ],
            [
                'main_type' => '*',
                'sub_type' => '*',
                'precedence' => 0.8,
                'tokens' => '' ] ];

        $this->assertEquals( $expected, self::$req->accepts() );
    }

    public function testCharsets()
    {
        $expected = [
            [
                'main_type' => 'ISO-8859-1',
                'sub_type' => '',
                'precedence' => 1,
                'tokens' => '', ],
            [
                'main_type' => 'utf-8',
                'sub_type' => '',
                'precedence' => 0.7,
                'tokens' => '' ],
            [
                'main_type' => '*',
                'sub_type' => '',
                'precedence' => 0.7,
                'tokens' => '' ] ];

        $this->assertEquals( $expected, self::$req->charsets() );
    }

    public function testLanguages()
    {
        $expected = [
            [
                'main_type' => 'en-us',
                'sub_type' => '',
                'precedence' => 1,
                'tokens' => '', ],
            [
                'main_type' => 'en',
                'sub_type' => '',
                'precedence' => 0.5,
                'tokens' => '' ] ];

        $this->assertEquals( $expected, self::$req->languages() );
    }

    public function testAgent()
    {
        $this->assertEquals( 'infuse/libs test', self::$req->agent() );
    }

    public function testIsHtml()
    {
        $this->assertTrue( self::$req->isHtml() );

        $req = new Request( null, null, null, null, [ 'HTTP_ACCEPT' => 'application/json' ] );
        $this->assertFalse( $req->isHtml() );
    }

    public function testIsJson()
    {
        $this->assertFalse( self::$req->isJson() );

        $req = new Request( null, null, null, null, [ 'HTTP_ACCEPT' => 'application/json' ] );
        $this->assertTrue( $req->isJson() );
    }

    public function testIsXml()
    {
        $this->assertTrue( self::$req->isXml() );

        $req = new Request( null, null, null, null, [ 'HTTP_ACCEPT' => 'application/json' ] );
        $this->assertFalse( $req->isXml() );
    }

    public function testIsXhr()
    {
        $this->assertFalse( self::$req->isXhr() );

        $req = new Request( null, null, null, null, [ 'HTTP_X-REQUESTED-WITH' => 'XMLHttpRequest' ] );
        $this->assertFalse( $req->isXhr() );
    }

    public function testIsNotApi()
    {
        $req = new Request(null, null, null, null, []);
        $this->assertFalse($req->isApi());
    }

    public function testIsApiHeader()
    {
        $req = new Request(null, null, null, null, ['HTTP_AUTHORIZATION' => 'test']);
        $this->assertTrue($req->isApi());
    }

    public function testIsApiRequestBody()
    {
        $req = new Request(['access_token' => 'test']);
        $this->assertTrue($req->isApi());
    }

    public function testIsApiQuery()
    {
        $req = new Request(null, ['access_token' => 'test']);
        $this->assertTrue($req->isApi());
    }

    public function testIsCli()
    {
        if( !defined( 'STDIN' ) ) define( 'STDIN', true );
        $this->assertTrue( self::$req->isCli() );
    }

    public function testParams()
    {
        $expected = [ 'test' => 1, 'test2' => 'meh' ];
        self::$req->setParams( $expected );

        $this->assertEquals( 'meh', self::$req->params( 'test2' ) );
        $this->assertEquals( $expected, self::$req->params() );

        $this->assertNull( self::$req->params( 'non-existent' ) );
    }

    public function testQuery()
    {
        $this->assertEquals( 'test', self::$req->query( 'test' ) );

        $expected = [
            'test' => 'test',
            'blah' => 'blah' ];
        $this->assertEquals( $expected, self::$req->query() );

        $this->assertNull( self::$req->query( 'non-existent' ) );
    }

    public function testRequest()
    {
        $this->assertEquals( 'test', self::$req->request( 'testParam' ) );

        $expected = [
            'testParam' => 'test',
            'meh' => 1,
            'does' => 'this-work' ];
        $this->assertEquals( $expected, self::$req->request() );

        $this->assertNull( self::$req->request( 'non-existent' ) );
    }

    public function testRequestPlainText()
    {
        $req = new Request(null, 'test', null, null, ['CONTENT_TYPE' => 'plain/text']);

        $this->assertEquals('test', $req->request());
        $this->assertEquals(null, $req->request('some_index'));
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

        $expected = [
            'test' => 'testValue',
            'test2' => 'testValue2'
        ];

        $this->assertEquals( $expected, self::$req->cookies() );

        $this->assertNull( self::$req->cookies( 'non-existent' ) );
    }

    public function testFiles()
    {
        $this->assertEquals( [ 'size' => 1234 ], self::$req->files( 'test' ) );

        $expected = [
            'test' => [
                'size' => 1234
            ],
            'test2' => [
                'error' => 0
            ]
        ];

        $this->assertEquals( $expected, self::$req->files() );

        $this->assertNull( self::$req->files( 'non-existent' ) );
    }

    public function testSetSession()
    {
        global $_SESSION;
        $_SESSION = [];

        self::$req->setSession( 'test', 'test' );
        self::$req->setSession( 'test2', 2 );
    }

    /**
	 * @depends testSetSession
	 */
    public function testSession()
    {
        $this->assertEquals( 'test', self::$req->session( 'test' ) );

        $expected = [ 'test' => 'test', 'test2' => 2 ];
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
        $this->assertEquals( [], self::$req->session() );
    }

    public function testCliArgs()
    {
        $expected = [ 'update', 'force', 'all' ];

        $this->assertEquals( 'force', self::$req->cliArgs( 1 ) );
        $this->assertEquals( $expected, self::$req->cliArgs() );

        $this->assertNull( self::$req->cliArgs( 100 ) );
    }
}
