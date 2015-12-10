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
use Infuse\Response;
use Infuse\Router;
use Infuse\View;
use Pimple\Container;

class RouterTest extends PHPUnit_Framework_TestCase
{
    public static $app;

    public static $config = [
        'namespace' => '',
        'defaultController' => 'MockController',
    ];

    public static function setUpBeforeClass()
    {
        self::$app = new Container();

        @unlink(__DIR__.'/routes.cache');
    }

    public function setUp()
    {
        MockController::$staticRouteCalled = false;
        MockController::$dynamicRouteCalled = false;
        MockController::$dynamicRouteParams = [];
        MockController::$indexRouteCalled = false;
        MockController::$appInjected = false;
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

    public function testStaticRoute()
    {
        $routes = [
            'get /this/is/a' => ['MockController', 'fail'],
            'get /this/is/a/test/route' => ['MockController', 'fail'],
            'post /this/is/a/test/route/{test}' => ['MockController', 'fail'],
            'post /this/is/a/test/route' => ['MockController', 'staticRoute'],
            'delete /this/is/a/test/route' => ['MockController', 'fail'],
            'get /this/is/a/test/route/' => ['MockController', 'fail'],
        ];

        $router = new Router($routes, self::$config);

        $req = Request::create('/this/is/a/test/route', 'POST');

        $res = new Response();

        $this->assertTrue($router->route(self::$app, $req, $res));

        $this->assertTrue(MockController::$staticRouteCalled);
        $this->assertTrue(MockController::$appInjected);
    }

    public function testDynamicRoute()
    {
        $routes = [
            'get /this/is/a' => 'fail',
            'get /this/is/a/test/route' => 'fail',
            'post /{a1}/{a2}/{a3}/{a4}/{a5}' => 'fail',
            'put /dynamic/{a1}/{a2}/{a3}/{a4}' => 'dynamicRoute',
            'delete /this/is/a/test/route' => 'fail',
            'get /this/is/a/test/route/' => 'fail',
        ];

        $router = new Router($routes, self::$config);

        $req = Request::create('/dynamic/1/2/3/4', 'PUT');

        $res = new Response();

        $this->assertTrue($router->route(self::$app, $req, $res));

        $this->assertTrue(MockController::$dynamicRouteCalled);
        $this->assertTrue(MockController::$appInjected);

        // test route params
        $expected = ['a1' => 1, 'a2' => 2, 'a3' => 3, 'a4' => 4];
        $this->assertEquals(MockController::$dynamicRouteParams, $expected);
    }

    public function testSingleAction()
    {
        $routes = [
            'get /this/is/a/test/route' => 'fail',
            'post /this/is/a/test/route/{test}' => 'fail',
            'post /this/is/a/test/route' => 'staticRoute',
            'delete /this/is/a/test/route' => 'fail',
            'post /this/is/a/test/route/' => 'fail',
        ];

        $router = new Router($routes, self::$config);

        $req = Request::create('/this/is/a/test/route', 'POST');

        $res = new Response();

        $this->assertTrue($router->route(self::$app, $req, $res));

        $this->assertTrue(MockController::$staticRouteCalled);
        $this->assertTrue(MockController::$appInjected);
    }

    public function testIndex()
    {
        // testing to see if index is appended when a method is not specified
        $routes = [
            'get /this/is/a' => ['MockController', 'fail'],
            'get /this/is/a/test/route' => ['MockController', 'fail'],
            'post /this/is/a/test/route/{test}' => ['MockController', 'fail'],
            'post /this/is/a/test/route' => ['MockController'],
            'delete /this/is/a/test/route' => ['MockController', 'fail'],
            'post /this/is/a/test/route/' => ['MockController', 'fail'],
        ];

        $router = new Router($routes, self::$config);

        $req = Request::create('/this/is/a/test/route', 'POST');

        $res = new Response();

        $this->assertTrue($router->route(self::$app, $req, $res));

        $this->assertTrue(MockController::$indexRouteCalled);
        $this->assertTrue(MockController::$appInjected);
    }

    public function testView()
    {
        $router = new Router([], self::$config);
        $router->map('GET', '/view', ['MockController', 'view']);

        $view = new View('test');
        MockController::$view = $view;

        $req = Request::create('/view');

        $res = Mockery::mock('Infuse\Response');
        $res->shouldReceive('render')
            ->withArgs([$view])
            ->once();

        $this->assertTrue($router->route(self::$app, $req, $res));
    }

    public function testNotFound()
    {
        $router = new Router([], self::$config);

        $req = Request::create('/this/is/a/test/route', 'POST');

        $res = new Response();

        $this->assertFalse($router->route(self::$app, $req, $res));
        $this->assertEquals(404, $res->getCode());
    }

    public function testNonExistentController()
    {
        $router = new Router([], self::$config);
        $router->map('POST', '/this/is/a/test/route', ['BogusController', 'who_cares']);

        $req = Request::create('/this/is/a/test/route', 'POST');

        $res = new Response();

        $this->assertFalse($router->route(self::$app, $req, $res));
        $this->assertEquals(404, $res->getCode());
    }

    public function testWrongMethod()
    {
        $router = new Router([], self::$config);
        $router->map('GET', '/this/is/a/test/route', 'handler');

        $req = Request::create('/this/is/a/test/route', 'POST');

        $res = new Response();

        $this->assertFalse($router->route(self::$app, $req, $res));
        $this->assertEquals(405, $res->getCode());
    }

    public function testRouterControllerParam()
    {
        $routes = [
            'post /this/is/a/test/route' => 'staticRoute',
            'get /not/it' => 'fail',
        ];

        $router = new Router($routes, ['defaultController' => 'BogusController']);

        $req = Request::create('/this/is/a/test/route', 'POST');
        $req->setParams(['controller' => 'MockController']);

        $res = new Response();

        $this->assertTrue($router->route(self::$app, $req, $res));

        $this->assertTrue(MockController::$staticRouteCalled);
        $this->assertTrue(MockController::$appInjected);
    }

    public function testClosure()
    {
        $test = false;
        $handler = function ($req, $res) use (&$test) {
            $test = true;
        };

        $router = new Router([], self::$config);
        $router->map('GET', '/test', $handler);

        $req = Request::create('/test');

        $res = new Response();

        $this->assertTrue($router->route(self::$app, $req, $res));

        $this->assertTrue($test);
    }

    public function testPresetParameters()
    {
        $extraParams = [
            'test' => true,
            'hello' => 'world',
        ];

        $router = new Router([], self::$config);
        $router->map('GET', '/test', ['MockController', 'staticRoute', $extraParams]);

        $req = Request::create('/test');

        $res = new Response();

        $this->assertTrue($router->route(self::$app, $req, $res));

        $this->assertTrue(MockController::$staticRouteCalled);
        $this->assertTrue(MockController::$appInjected);
        $this->assertEquals($extraParams, $req->params());
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
}

class MockController
{
    public static $staticRouteCalled = false;
    public static $dynamicRouteCalled = false;
    public static $dynamicRouteParams = [];
    public static $indexRouteCalled = false;
    public static $appInjected = false;
    public static $view;

    public function injectApp($app)
    {
        self::$appInjected = true;
    }

    public function staticRoute($req, $res)
    {
        self::$staticRouteCalled = true;
    }

    public function dynamicRoute($req, $res)
    {
        self::$dynamicRouteCalled = true;
        self::$dynamicRouteParams = $req->params();
    }

    public function index($req, $res)
    {
        self::$indexRouteCalled = true;
    }

    public function view($req, $res)
    {
        return self::$view;
    }

    public function fail($req, $res)
    {
        // FAIL
    }
}
