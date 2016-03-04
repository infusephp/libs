<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Infuse\Router;
use Pimple\Container;

class RouterTest extends PHPUnit_Framework_TestCase
{
    public static $app;

    public static function setUpBeforeClass()
    {
        self::$app = new Container();

        @unlink(__DIR__.'/routes.cache');
    }

    public function testGet()
    {
        $router = new Router();
        $handler = function () {};

        $this->assertEquals($router, $router->get('/users/{id}', $handler));

        $this->assertEquals([['GET', '/users/{id}', $handler]], $router->getRoutes());
    }

    public function testPost()
    {
        $router = new Router();
        $handler = function () {};

        $this->assertEquals($router, $router->post('/users', $handler));

        $this->assertEquals([['POST', '/users', $handler]], $router->getRoutes());
    }

    public function testPut()
    {
        $router = new Router();
        $handler = function () {};

        $this->assertEquals($router, $router->put('/users/{id}', $handler));

        $this->assertEquals([['PUT', '/users/{id}', $handler]], $router->getRoutes());
    }

    public function testDelete()
    {
        $router = new Router();
        $handler = function () {};

        $this->assertEquals($router, $router->delete('/users/{id}', $handler));

        $this->assertEquals([['DELETE', '/users/{id}', $handler]], $router->getRoutes());
    }

    public function testPatch()
    {
        $router = new Router();
        $handler = function () {};

        $this->assertEquals($router, $router->patch('/users/{id}', $handler));

        $this->assertEquals([['PATCH', '/users/{id}', $handler]], $router->getRoutes());
    }

    public function testOptions()
    {
        $router = new Router();
        $handler = function () {};

        $this->assertEquals($router, $router->options('/users/{id}', $handler));

        $this->assertEquals([['OPTIONS', '/users/{id}', $handler]], $router->getRoutes());
    }

    public function testMap()
    {
        $router = new Router();
        $handler = function () {};

        $this->assertEquals($router, $router->map('GET', '/users/{id}', $handler));

        $this->assertEquals([['GET', '/users/{id}', $handler]], $router->getRoutes());
    }

    public function testStaticDispatcher()
    {
        $routes = ['get /test' => 'index'];
        $settings = [];

        $router = new Router($routes, $settings);
        $this->assertInstanceOf('FastRoute\Dispatcher\GroupCountBased', $router->getDispatcher());
    }

    public function testCachedDispatcher()
    {
        $routes = ['get /test' => 'index'];
        $settings = ['cacheFile' => __DIR__.'/routes.cache'];

        $router = new Router($routes, $settings);
        $this->assertInstanceOf('FastRoute\Dispatcher\GroupCountBased', $router->getDispatcher());
        $this->assertTrue(file_exists(__DIR__.'/routes.cache'));
    }

    public function testDispatchDynamicRoute()
    {
        $routes = [
            'get /this/is/a' => 'fail',
            'get /this/is/a/test/route' => 'fail',
            'post /{a1}/{a2}/{a3}/{a4}/{a5}' => 'fail',
            'put /dynamic/{a1}/{a2}/{a3}/{a4}' => 'dynamicRoute',
            'delete /this/is/a/test/route' => 'fail',
            'get /this/is/a/test/route/' => 'fail',
        ];

        $router = new Router($routes);

        $expected = [
            1,
            'dynamicRoute',
            [
                'a1' => '1',
                'a2' => '2',
                'a3' => '3',
                'a4' => '4',
            ],
        ];

        $this->assertEquals($expected, $router->dispatch('PUT', '/dynamic/1/2/3/4'));
    }

    public function testDispatchStaticRoute()
    {
        $routes = [
            'get /this/is/a/test/route' => 'fail',
            'post /this/is/a/test/route/{test}' => 'fail',
            'post /this/is/a/test/route' => 'staticRoute',
            'delete /this/is/a/test/route' => 'fail',
            'post /this/is/a/test/route/' => 'fail',
        ];

        $router = new Router($routes);

        $expected = [
            1,
            'staticRoute',
            [],
        ];

        $this->assertEquals($expected, $router->dispatch('POST', '/this/is/a/test/route'));
    }

    public function testDispatchNotFound()
    {
        $router = new Router([]);

        $expected = [
            0,
        ];

        $this->assertEquals($expected, $router->dispatch('POST', '/this/is/a/test/route'));
    }

    public function testDispatchMethodNotAllowed()
    {
        $router = new Router([]);
        $router->map('GET', '/this/is/a/test/route', 'handler');

        $expected = [
            2,
            ['GET'],
        ];

        $this->assertEquals($expected, $router->dispatch('POST', '/this/is/a/test/route'));
    }
}
