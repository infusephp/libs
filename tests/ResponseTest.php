<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.25
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\Response;
use infuse\Request;
use Pimple\Container;

class ResponseTest extends \PHPUnit_Framework_TestCase
{
    public static $res;

    public static function setUpBeforeClass()
    {
        self::$res = new Response(new Container());
    }

    public function testHeaders()
    {
        $this->assertEquals(self::$res, self::$res->setHeader('Test', 'test'));
        $this->assertEquals('test', self::$res->headers('Test'));

        $this->assertEquals(['Test'=>'test'], self::$res->headers());
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

    public function testJson()
    {
        $body = [
            'test' => [
                'meh',
                'blah' ] ];

        $this->assertEquals(self::$res, self::$res->json($body));

        $this->assertEquals(json_encode($body), self::$res->getBody());
        $this->assertEquals('application/json', self::$res->getContentType());
    }

    public function testRedirect()
    {
        $container = new Container();
        $container['req'] = new Request( null, null, null, null, [
            'HTTP_HOST' => 'example.com',
            'DOCUMENT_URI' => '/some/start',
            'REQUEST_URI' => '/some/start/test/index.php' ] );
        $res = new Response($container);

        $this->assertEquals($res, $res->redirect('/'));
        $this->assertEquals('//example.com/some/start/', $res->headers('Location'));

        $this->assertEquals($res, $res->redirect('/test/url', 301));
        $this->assertEquals('//example.com/some/start/test/url', $res->headers('Location'));
        $this->assertEquals(301, $res->getCode());

        $this->assertEquals($res, $res->redirect('http://test.com'));
        $this->assertEquals('http://test.com', $res->headers('Location'));

        $this->assertEquals($res, $res->redirect('http://test.com'));
        $this->assertEquals('http://test.com', $res->headers('Location'));
    }

    public function testRedirectNonStandardPort()
    {
        $container = new Container();
        $container['req'] = new Request( null, null, null, null, [
            'HTTP_HOST' => 'example.com:1234',
            'DOCUMENT_URI' => '/some/start',
            'REQUEST_URI' => '/some/start/test/index.php',
            'SERVER_PORT' => 5000 ] );
        $res = new Response($container);

        $this->assertEquals($res, $res->redirect('/'));
        $this->assertEquals('//example.com:1234/some/start/', $res->headers('Location'));

        $this->assertEquals($res, $res->redirect( '/test/url'));
        $this->assertEquals('//example.com:1234/some/start/test/url', $res->headers('Location'));
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
