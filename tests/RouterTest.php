<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use infuse\Request;
use infuse\Response;
use infuse\Router;
use infuse\View;
use Pimple\Container;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    public static $app;

    public static function setUpBeforeClass()
    {
        self::$app = new Container();
    }

    public function setUp()
    {
        Router::configure([
            'namespace' => '',
            'defaultController' => 'MockController', ]);

        MockController::$staticRouteCalled = false;
        MockController::$dynamicRouteCalled = false;
        MockController::$dynamicRouteParams = [];
        MockController::$indexRouteCalled = false;
    }

    public function testStaticRoute()
    {
        $testRoutes = [
            'get /this/is/a' => [ 'MockController', 'fail' ],
            'get /this/is/a/test/route' => [ 'MockController', 'fail' ],
            'post /this/is/a/test/route/:test' => [ 'MockController', 'fail' ],
            'post /this/is/a/test/route' => [ 'MockController', 'staticRoute' ],
            'delete /this/is/a/test/route' => [ 'MockController', 'fail' ],
            'get /this/is/a/test/route/' => [ 'MockController', 'fail' ],
        ];

        $server = $_SERVER;
        $server[ 'REQUEST_METHOD' ] = 'POST';

        $req = new Request(null, null, null, null, $server);
        $req->setPath('/this/is/a/test/route');

        $res = new Response();

        $this->assertTrue(Router::route($testRoutes, self::$app, $req, $res));

        $this->assertTrue(MockController::$staticRouteCalled);
    }

    public function testDynamicRoute()
    {
        $testRoutes = [
            'get /this/is/a' => 'fail',
            'get /this/is/a/test/route' => 'fail',
            'post /:a1/:a2/:a3/:a4/:a5' => 'fail',
            'put /dynamic/:a1/:a2/:a3/:a4' => 'dynamicRoute',
            'delete /this/is/a/test/route' => 'fail',
            'get /this/is/a/test/route/' => 'fail',
        ];

        $server = $_SERVER;
        $server[ 'REQUEST_METHOD' ] = 'PUT';

        $req = new Request(null, null, null, null, $server);
        $req->setPath('/dynamic/1/2/3/4');

        $res = new Response();

        $this->assertTrue(Router::route($testRoutes, self::$app, $req, $res));

        $this->assertTrue(MockController::$dynamicRouteCalled);

        // test route params
        $expected = [ 'a1' => 1, 'a2' => 2, 'a3' => 3, 'a4' => 4 ];
        $this->assertEquals(MockController::$dynamicRouteParams, $expected);
    }

    public function testSingleAction()
    {
        $testRoutes = [
            'get /this/is/a/test/route' => 'fail',
            'post /this/is/a/test/route/:test' => 'fail',
            'post /this/is/a/test/route' => 'staticRoute',
            'delete /this/is/a/test/route' => 'fail',
            'post /this/is/a/test/route/' => 'fail',
        ];

        $server = $_SERVER;
        $server[ 'REQUEST_METHOD' ] = 'POST';

        $req = new Request(null, null, null, null, $server);
        $req->setPath('/this/is/a/test/route');

        $res = new Response();

        $this->assertTrue(Router::route($testRoutes, self::$app, $req, $res));

        $this->assertTrue(MockController::$staticRouteCalled);
    }

    public function testIndex()
    {
        // testing to see if index is appended when a method is not specified
        $testRoutes = [
            'get /this/is/a' => [ 'MockController', 'fail' ],
            'get /this/is/a/test/route' => [ 'MockController', 'fail' ],
            'post /this/is/a/test/route/:test' => [ 'MockController', 'fail' ],
            'post /this/is/a/test/route' => [ 'MockController' ],
            'delete /this/is/a/test/route' => [ 'MockController', 'fail' ],
            'post /this/is/a/test/route/' => [ 'MockController', 'fail' ],
        ];

        $server = $_SERVER;
        $server[ 'REQUEST_METHOD' ] = 'POST';

        $req = new Request(null, null, null, null, $server);
        $req->setPath('/this/is/a/test/route');

        $res = new Response();

        $this->assertTrue(Router::route($testRoutes, self::$app, $req, $res));

        $this->assertTrue(MockController::$indexRouteCalled);
    }

    public function testView()
    {
        $view = new View('test');
        MockController::$view = $view;

        $req = new Request();
        $req->setPath('/view');

        $res = Mockery::mock('\\infuse\\Response');
        $res->shouldReceive('render')->withArgs([$view])->once();

        $this->assertTrue(Router::route(['/view' => ['MockController', 'view']], self::$app, $req, $res));
    }

    public function testNonExistentController()
    {
        // call a route with a bogus controller
        $testRoutes = [
            'post /this/is/a/test/route' => [ 'BogusController', 'who_cares' ],
        ];

        $server = $_SERVER;
        $server[ 'REQUEST_METHOD' ] = 'POST';

        $req = new Request(null, null, null, null, $server);
        $req->setPath('/this/is/a/test/route');

        $res = new Response();

        $this->assertFalse(Router::route($testRoutes, self::$app, $req, $res));
    }

    public function testRouterControllerParam()
    {
        Router::configure([ 'defaultController' => 'BogusController' ]);

        $testRoutes = [
            'post /this/is/a/test/route' => 'staticRoute',
            'get /not/it' => 'fail',
        ];

        $server = $_SERVER;
        $server[ 'REQUEST_METHOD' ] = 'POST';

        $req = new Request(null, null, null, null, $server);
        $req->setPath('/this/is/a/test/route');

        $req->setParams([ 'controller' => 'MockController' ]);

        $res = new Response();

        $this->assertTrue(Router::route($testRoutes, self::$app, $req, $res));

        $this->assertTrue(MockController::$staticRouteCalled);
    }

    public function testClosure()
    {
        $test = false;

        $testRoutes = [
            'get /test' => function ($req, $res) use (&$test) {
                $test = true;
            },
        ];

        $server = $_SERVER;
        $server[ 'REQUEST_METHOD' ] = 'GET';

        $req = new Request(null, null, null, null, $server);
        $req->setPath('/test');

        $res = new Response();

        $this->assertTrue(Router::route($testRoutes, self::$app, $req, $res));

        $this->assertTrue($test);
    }

    public function testPresetParameters()
    {
        $extraParams = [
            'test' => true,
            'hello' => 'world',
        ];

        $testRoutes = [
            'get /test' => ['MockController', 'staticRoute', $extraParams],
        ];

        $server = $_SERVER;
        $server[ 'REQUEST_METHOD' ] = 'GET';

        $req = new Request(null, null, null, null, $server);
        $req->setPath('/test');

        $res = new Response();

        $this->assertTrue(Router::route($testRoutes, self::$app, $req, $res));

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
