<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Infuse\Request;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class RequestTest extends MockeryTestCase
{
    public static $req;

    public static function setUpBeforeClass()
    {
        self::$req = new Request(
            // query parameters
            [
                'test' => 'test',
                'blah' => 'blah', ],
            // request body
            [
                'testParam' => 'test',
                'meh' => 1,
                'does' => 'this-work', ],
            // cookies
            [
                'test' => 1234,
            ],
             // files
            [
                'test' => [
                    'size' => 1234,
                ],
                'test2' => [
                    'error' => 0,
                ],
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
                    'all',
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

    public function testCreateFromGlobals()
    {
        $req = Request::createFromGlobals();

        $this->assertInstanceOf('Infuse\Request', $req);
    }

    public function testCreate()
    {
        $req = Request::create('http://example.com?k=v');

        $this->assertInstanceOf('Infuse\Request', $req);
        $this->assertEquals('http', $req->protocol());
        $this->assertEquals('example.com', $req->host());
        $this->assertEquals(80, $req->port());
        $this->assertEquals('/', $req->path());
        $this->assertEquals('GET', $req->method());
        $this->assertEquals(['k' => 'v'], $req->query());
    }

    public function testCreateFullUrl()
    {
        $req = Request::create('https://user:pass@example.com:1234/test', 'post', ['test' => true]);

        $this->assertInstanceOf('Infuse\Request', $req);
        $this->assertEquals('https', $req->protocol());
        $this->assertEquals('user', $req->user());
        $this->assertEquals('pass', $req->password());
        $this->assertEquals('example.com', $req->host());
        $this->assertEquals(1234, $req->port());
        $this->assertEquals('/test', $req->path());
        $this->assertEquals('POST', $req->method());
        $this->assertEquals(['test' => true], $req->request());
    }

    public function testCreateHead()
    {
        $req = Request::create('/test?k=v', 'HEAD', ['test' => true]);

        $this->assertInstanceOf('Infuse\Request', $req);
        $this->assertEquals('http', $req->protocol());
        $this->assertEquals('/test', $req->path());
        $this->assertEquals('HEAD', $req->method());
        $this->assertEquals(['test' => true, 'k' => 'v'], $req->query());
    }

    public function testIp()
    {
        $this->assertEquals('1.2.3.4', self::$req->ip());
    }

    public function testProtocol()
    {
        $this->assertEquals('http', self::$req->protocol());

        // test when HTTPS header set
        $req = Request::create('/', 'GET', [], [], [], ['HTTPS' => 'on']);

        $this->assertEquals('https', $req->protocol());
    }

    public function testIsSecure()
    {
        $this->assertFalse(self::$req->isSecure());

        // test when HTTPS header set
        $req = Request::create('/', 'GET', [], [], [], ['HTTPS' => 'on']);

        $this->assertTrue($req->isSecure());
    }

    public function testPort()
    {
        $this->assertEquals(1234, self::$req->port());
    }

    public function testHeader()
    {
        $this->assertEquals('testing..123', self::$req->headers('test_header'));

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
            'CONTENT_TYPE' => 'application/json', ];
        $this->assertEquals($expected, self::$req->headers());

        $this->assertNull(self::$req->headers('non-existent'));
    }

    public function testUser()
    {
        $this->assertEquals('test_user', self::$req->user());
    }

    public function testPassword()
    {
        $this->assertEquals('test_pw', self::$req->password());
    }

    public function testHost()
    {
        $this->assertEquals('example.com', self::$req->host());
    }

    public function testHostFromServerName()
    {
        $req = Request::create('/', 'GET', [], [], [], ['HTTP_HOST' => null, 'SERVER_NAME' => 'example.com']);

        $this->assertEquals('example.com', $req->host());
    }

    public function testHostFromServerAddr()
    {
        $req = Request::create('/', 'GET', [], [], [], ['HTTP_HOST' => null, 'SERVER_NAME' => null, 'SERVER_ADDR' => '127.0.0.1']);

        $this->assertEquals('127.0.0.1', $req->host());
    }

    public function testUrl()
    {
        $this->assertEquals('http://example.com:1234/users/comments/10', self::$req->url());

        $req = Request::create('/', 'GET', [], [], [], ['HTTP_HOST' => null, 'SERVER_NAME' => null, 'SERVER_ADDR' => '127.0.0.1']);
        $this->assertEquals('http://127.0.0.1/', $req->url());
    }

    public function testPaths()
    {
        $this->assertEquals('comments', self::$req->paths(1));

        $expected = ['users', 'comments', '10'];
        $this->assertEquals($expected, self::$req->paths());

        $this->assertNull(self::$req->paths(100));
    }

    public function testBasePath()
    {
        $this->assertEquals('/', self::$req->basePath());
    }

    public function testPath()
    {
        $this->assertEquals('/users/comments/10', self::$req->path());
    }

    public function testRemovingStartPath()
    {
        $req = new Request([], [], [], [], ['REQUEST_URI' => '/some/start/path/test', 'DOCUMENT_URI' => '/some/start/path']);

        $this->assertEquals('/some/start/path', $req->basePath());
        $this->assertEquals('/test', $req->path());
        $this->assertEquals(['test'], $req->paths());
    }

    public function testTrailingSlash()
    {
        $req = new Request([], [], [], [], ['REQUEST_URI' => '/some/start/path/test/', 'DOCUMENT_URI' => '/some/start/path']);

        $this->assertEquals('/some/start/path', $req->basePath());
        $this->assertEquals('/test', $req->path());
        $this->assertEquals(['test'], $req->paths());
    }

    public function testPhpUri()
    {
        $req = new Request([], [], [], [], ['REQUEST_URI' => '/some/start/path/test.php', 'DOCUMENT_URI' => '/some/start/path/test.php']);

        $this->assertEquals('/some/start/path/test.php', $req->basePath());
        $this->assertEquals('/', $req->path());
        $this->assertEquals([''], $req->paths());
    }

    public function testMethod()
    {
        $this->assertEquals('PUT', self::$req->method());
    }

    public function testMethodFromPost()
    {
        $req = Request::create('/', 'POST', ['method' => 'DELETE']);
        $this->assertEquals('DELETE', $req->method());

        $req = Request::create('/', 'POST', ['method' => 'PUT']);
        $this->assertEquals('PUT', $req->method());
    }

    public function testMethodFromXHttpMethodOverrideHeader()
    {
        $req = Request::create('/', 'POST', [], [], [], ['HTTP_X_HTTP_METHOD_OVERRIDE' => 'DELETE']);
        $this->assertEquals('DELETE', $req->method());

        $req = Request::create('/', 'POST', [], [], [], ['HTTP_X_HTTP_METHOD_OVERRIDE' => 'PUT']);
        $this->assertEquals('PUT', $req->method());
    }

    public function testContentType()
    {
        $this->assertEquals('application/json', self::$req->contentType());
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
                'tokens' => '', ],
            [
                'main_type' => 'application',
                'sub_type' => 'xml',
                'precedence' => 0.9,
                'tokens' => '', ],
            [
                'main_type' => '*',
                'sub_type' => '*',
                'precedence' => 0.8,
                'tokens' => '', ], ];

        $this->assertEquals($expected, self::$req->accepts());
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
                'tokens' => '', ],
            [
                'main_type' => '*',
                'sub_type' => '',
                'precedence' => 0.7,
                'tokens' => '', ], ];

        $this->assertEquals($expected, self::$req->charsets());
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
                'tokens' => '', ], ];

        $this->assertEquals($expected, self::$req->languages());
    }

    public function testAgent()
    {
        $this->assertEquals('infuse/libs test', self::$req->agent());
    }

    public function testIsHtml()
    {
        $this->assertTrue(self::$req->isHtml());

        $req = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $this->assertFalse($req->isHtml());
    }

    public function testIsJson()
    {
        $this->assertFalse(self::$req->isJson());

        $req = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $this->assertTrue($req->isJson());
    }

    public function testIsXml()
    {
        $this->assertTrue(self::$req->isXml());

        $req = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $this->assertFalse($req->isXml());
    }

    public function testIsXhr()
    {
        $this->assertFalse(self::$req->isXhr());

        $req = Request::create('/', 'GET', [], [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $this->assertTrue($req->isXhr());
    }

    public function testIsNotApi()
    {
        $req = Request::create('/', 'GET');
        $this->assertFalse($req->isApi());
    }

    public function testIsApiHeader()
    {
        $req = Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'test']);
        $this->assertTrue($req->isApi());
    }

    public function testIsApiRequestBody()
    {
        $req = Request::create('/', 'POST', ['access_token' => 'test']);
        $this->assertTrue($req->isApi());
    }

    public function testIsApiQuery()
    {
        $req = Request::create('/', 'GET', ['access_token' => 'test']);
        $this->assertTrue($req->isApi());
    }

    public function testParams()
    {
        $expected = ['test' => 1, 'test2' => 'meh'];
        self::$req->setParams($expected);

        $this->assertEquals('meh', self::$req->params('test2'));
        $this->assertEquals($expected, self::$req->params());

        $this->assertNull(self::$req->params('non-existent'));
    }

    public function testQuery()
    {
        $this->assertEquals('test', self::$req->query('test'));

        $expected = [
            'test' => 'test',
            'blah' => 'blah', ];
        $this->assertEquals($expected, self::$req->query());

        $this->assertNull(self::$req->query('non-existent'));
    }

    public function testRequest()
    {
        $this->assertEquals('test', self::$req->request('testParam'));

        $expected = [
            'testParam' => 'test',
            'meh' => 1,
            'does' => 'this-work', ];
        $this->assertEquals($expected, self::$req->request());

        $this->assertNull(self::$req->request('non-existent'));
    }

    public function testRequestPlainText()
    {
        $req = Request::create('/', 'POST', 'test', [], [], ['CONTENT_TYPE' => 'plain/text']);

        $this->assertEquals('test', $req->request());
        $this->assertEquals(null, $req->request('some_index'));
    }

    public function testCookies()
    {
        $this->assertEquals(1234, self::$req->cookies('test'));

        $expected = [
            'test' => 1234,
        ];

        $this->assertEquals($expected, self::$req->cookies());

        $this->assertNull(self::$req->cookies('non-existent'));
    }

    public function testFiles()
    {
        $this->assertEquals(['size' => 1234], self::$req->files('test'));

        $expected = [
            'test' => [
                'size' => 1234,
            ],
            'test2' => [
                'error' => 0,
            ],
        ];

        $this->assertEquals($expected, self::$req->files());

        $this->assertNull(self::$req->files('non-existent'));
    }

    public function testSetSession()
    {
        global $_SESSION;
        $_SESSION = [];

        self::$req->setSession('test', 'test');
        self::$req->setSession('test2', 2);
    }

    /**
     * @depends testSetSession
     */
    public function testSession()
    {
        $this->assertEquals('test', self::$req->session('test'));

        $expected = ['test' => 'test', 'test2' => 2];
        $this->assertEquals($expected, self::$req->session());

        $this->assertNull(self::$req->session('non-existent'));
    }

    /**
     * @depends testSession
     */
    public function testDestroySession()
    {
        self::$req->destroySession();

        $this->assertNull(self::$req->session('test'));
        $this->assertEquals([], self::$req->session());
    }
}
