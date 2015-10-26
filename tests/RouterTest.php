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
    }

    public function setUp()
    {
        MockController::$staticRouteCalled = false;
        MockController::$dynamicRouteCalled = false;
        MockController::$dynamicRouteParams = [];
        MockController::$indexRouteCalled = false;
    }

    public function testStaticRoute()
    {
        $routes = [
            'get /this/is/a' => ['MockController', 'fail'],
            'get /this/is/a/test/route' => ['MockController', 'fail'],
            'post /this/is/a/test/route/:test' => ['MockController', 'fail'],
            'post /this/is/a/test/route' => ['MockController', 'staticRoute'],
            'delete /this/is/a/test/route' => ['MockController', 'fail'],
            'get /this/is/a/test/route/' => ['MockController', 'fail'],
        ];

        $router = new Router($routes, self::$config);

        $req = Request::create('/this/is/a/test/route', 'POST');

        $res = new Response();

        $this->assertTrue($router->route(self::$app, $req, $res));

        $this->assertTrue(MockController::$staticRouteCalled);
    }

    public function testDynamicRoute()
    {
        $routes = [
            'get /this/is/a' => 'fail',
            'get /this/is/a/test/route' => 'fail',
            'post /:a1/:a2/:a3/:a4/:a5' => 'fail',
            'put /dynamic/:a1/:a2/:a3/:a4' => 'dynamicRoute',
            'delete /this/is/a/test/route' => 'fail',
            'get /this/is/a/test/route/' => 'fail',
        ];

        $router = new Router($routes, self::$config);

        $req = Request::create('/dynamic/1/2/3/4', 'PUT');

        $res = new Response();

        $this->assertTrue($router->route(self::$app, $req, $res));

        $this->assertTrue(MockController::$dynamicRouteCalled);

        // test route params
        $expected = ['a1' => 1, 'a2' => 2, 'a3' => 3, 'a4' => 4];
        $this->assertEquals(MockController::$dynamicRouteParams, $expected);
    }

    public function testSingleAction()
    {
        $routes = [
            'get /this/is/a/test/route' => 'fail',
            'post /this/is/a/test/route/:test' => 'fail',
            'post /this/is/a/test/route' => 'staticRoute',
            'delete /this/is/a/test/route' => 'fail',
            'post /this/is/a/test/route/' => 'fail',
        ];

        $router = new Router($routes, self::$config);

        $req = Request::create('/this/is/a/test/route', 'POST');

        $res = new Response();

        $this->assertTrue($router->route(self::$app, $req, $res));

        $this->assertTrue(MockController::$staticRouteCalled);
    }

    public function testIndex()
    {
        // testing to see if index is appended when a method is not specified
        $routes = [
            'get /this/is/a' => ['MockController', 'fail'],
            'get /this/is/a/test/route' => ['MockController', 'fail'],
            'post /this/is/a/test/route/:test' => ['MockController', 'fail'],
            'post /this/is/a/test/route' => ['MockController'],
            'delete /this/is/a/test/route' => ['MockController', 'fail'],
            'post /this/is/a/test/route/' => ['MockController', 'fail'],
        ];

        $router = new Router($routes, self::$config);

        $req = Request::create('/this/is/a/test/route', 'POST');

        $res = new Response();

        $this->assertTrue($router->route(self::$app, $req, $res));

        $this->assertTrue(MockController::$indexRouteCalled);
    }

    public function testView()
    {
        $routes = ['/view' => ['MockController', 'view']];

        $router = new Router($routes, self::$config);

        $view = new View('test');
        MockController::$view = $view;

        $req = Request::create('/view');

        $res = Mockery::mock('Infuse\Response');
        $res->shouldReceive('render')->withArgs([$view])->once();

        $this->assertTrue($router->route(self::$app, $req, $res));
    }

    public function testNonExistentController()
    {
        // call a route with a bogus controller
        $routes = [
            'post /this/is/a/test/route' => ['BogusController', 'who_cares'],
        ];

        $router = new Router($routes, self::$config);

        $server = $_SERVER;
        $server['REQUEST_METHOD'] = 'POST';

        $req = Request::create('/this/is/a/test/route', 'POST');

        $res = new Response();

        $this->assertFalse($router->route(self::$app, $req, $res));
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
    }

    public function testClosure()
    {
        $test = false;

        $routes = [
            'get /test' => function ($req, $res) use (&$test) {
                $test = true;
            },
        ];

        $router = new Router($routes, self::$config);

        $server = $_SERVER;
        $server['REQUEST_METHOD'] = 'GET';

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

        $routes = [
            'get /test' => ['MockController', 'staticRoute', $extraParams],
        ];

        $router = new Router($routes, self::$config);

        $req = Request::create('/test');

        $res = new Response();

        $this->assertTrue($router->route(self::$app, $req, $res));

        $this->assertTrue(MockController::$staticRouteCalled);
        $this->assertEquals($extraParams, $req->params());
    }
}

class MockController
{
    public static $staticRouteCalled = false;
    public static $dynamicRouteCalled = false;
    public static $dynamicRouteParams = [];
    public static $indexRouteCalled = false;
    public static $view;

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
