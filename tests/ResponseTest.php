<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace infuse;

function headers_sent()
{
    return ResponseTest::$mock ? ResponseTest::$mock->headers_sent() : \headers_sent();
}

function header($arg1, $arg2 = true, $arg3 = 200)
{
    return ResponseTest::$mock ? ResponseTest::$mock->header($arg1, $arg2, $arg3) : \header($arg1, $arg2, $arg3);
}

class ResponseTest extends \PHPUnit_Framework_TestCase
{
    public static $res;
    public static $mock;

    public static function setUpBeforeClass()
    {
        self::$res = new Response();
    }

    public function tearDown()
    {
        self::$mock = false;
    }

    public function testHeaders()
    {
        $this->assertEquals(self::$res, self::$res->setHeader('Test', 'test'));
        $this->assertEquals('test', self::$res->headers('Test'));

        $this->assertEquals(['Test' => 'test'], self::$res->headers());
    }

    public function testVersion()
    {
        $this->assertEquals(self::$res, self::$res->setVersion('1.0'));
        $this->assertEquals('1.0', self::$res->getVersion());
    }

    public function testCode()
    {
        $this->assertEquals(self::$res, self::$res->setCode(502));
        $this->assertEquals(502, self::$res->getCode());
    }

    public function testBody()
    {
        $this->assertEquals(self::$res, self::$res->setBody('test'));
        $this->assertEquals('test', self::$res->getBody());
    }

    public function testContentType()
    {
        $this->assertEquals(self::$res, self::$res->setContentType('application/pdf'));
        $this->assertEquals('application/pdf', self::$res->getContentType());
    }

    public function testRender()
    {
        $view = \Mockery::mock('infuse\\View');
        $view->shouldReceive('render')->andReturn('Hello, world!')->once();

        $this->assertEquals(self::$res, self::$res->render($view));
        $this->assertEquals('Hello, world!', self::$res->getBody());
    }

    public function testJson()
    {
        $body = [
            'test' => [
                'meh',
                'blah', ], ];

        $this->assertEquals(self::$res, self::$res->json($body));

        $this->assertEquals(json_encode($body), self::$res->getBody());
        $this->assertEquals('application/json', self::$res->getContentType());
    }

    public function testJsonDeprecated()
    {
        // DEPRECATED

        $body = [
            'test' => [
                'meh',
                'blah', ], ];

        $this->assertEquals(self::$res, self::$res->setBodyJson($body));

        $this->assertEquals(json_encode($body), self::$res->getBody());
        $this->assertEquals('application/json', self::$res->getContentType());
    }

    public function testRedirect()
    {
        $req = new Request(null, null, null, null, [
            'HTTP_HOST' => 'example.com',
            'DOCUMENT_URI' => '/some/start',
            'REQUEST_URI' => '/some/start/test/index.php' ]);
        $res = new Response();

        $this->assertEquals($res, $res->redirect('/', 302, $req));
        $this->assertEquals('//example.com/some/start/', $res->headers('Location'));

        $this->assertEquals($res, $res->redirect('/test/url', 301, $req));
        $this->assertEquals('//example.com/some/start/test/url', $res->headers('Location'));
        $this->assertEquals(301, $res->getCode());

        $this->assertEquals($res, $res->redirect('http://test.com'));
        $this->assertEquals('http://test.com', $res->headers('Location'));
        $this->assertEquals(302, $res->getCode());

        $this->assertEquals($res, $res->redirect('http://test.com'));
        $this->assertEquals('http://test.com', $res->headers('Location'));
    }

    public function testRedirectNonStandardPort()
    {
        $req = new Request(null, null, null, null, [
            'HTTP_HOST' => 'example.com:1234',
            'DOCUMENT_URI' => '/some/start',
            'REQUEST_URI' => '/some/start/test/index.php',
            'SERVER_PORT' => 5000 ]);
        $res = new Response();

        $this->assertEquals($res, $res->redirect('/', 302, $req));
        $this->assertEquals('//example.com:1234/some/start/', $res->headers('Location'));

        $this->assertEquals($res, $res->redirect('/test/url', 302, $req));
        $this->assertEquals('//example.com:1234/some/start/test/url', $res->headers('Location'));
    }

    public function testSendHeaders()
    {
        self::$mock = \Mockery::mock('php');
        self::$mock->shouldReceive('headers_sent')->andReturn(false)->once();
        self::$mock->shouldReceive('header')->withArgs(['HTTP/1.0 401 Unauthorized', true, 401])->once();
        self::$mock->shouldReceive('header')->withArgs(['Content-type: application/json; charset=utf-8', false, 401])->once();
        self::$mock->shouldReceive('header')->withArgs(['Test: hello', false, 401])->once();

        $res = new Response();
        $res->setVersion('1.0');
        $res->setCode(401);
        $res->setContentType('application/json');
        $res->setHeader('Test', 'hello');
        $res->sendHeaders();
    }

    public function testSendBody()
    {
        self::$res->setBody('test');

        ob_start();

        $this->assertEquals(self::$res, self::$res->sendBody());

        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals('test', $output);
    }

    public function testSend()
    {
        self::$res->setBody('test');

        ob_start();

        $this->assertEquals(self::$res, self::$res->send());

        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals('test', $output);
    }
}
