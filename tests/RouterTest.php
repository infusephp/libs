<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.16.3
 * @copyright 2013 Jared King
 * @license MIT
 */

use infuse\Request;
use infuse\Router;

error_reporting( E_ALL | E_STRICT );
ini_set( 'display_errors', true );

require_once 'vendor/autoload.php';

class RouterTest extends \PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		Router::configure( array(
			'namespace' => '',
			'defaultController' => 'MockController' ) );

		MockController::$staticRouteCalled = false;
		MockController::$dynamicRouteCalled = false;
		MockController::$dynamicRouteParams = array();
		MockController::$indexRouteCalled = false;
	}

	public function testStaticRoute()
	{
		$testRoutes = array(
			'get /this/is/a' => array( 'MockController', 'fail' ),
			'get /this/is/a/test/route' => array( 'MockController', 'fail' ),
			'post /this/is/a/test/route/:test' => array( 'MockController', 'fail' ),
			'post /this/is/a/test/route' => array( 'MockController', 'staticRoute' ),
			'delete /this/is/a/test/route' => array( 'MockController', 'fail' ),
			'get /this/is/a/test/route/' => array( 'MockController', 'fail' ),
		);

		$server = $_SERVER;
		$server[ 'REQUEST_METHOD' ] = 'POST';

		$req = new Request( null, null, null, null, $server );
		$req->setPath( '/this/is/a/test/route' );

		$this->assertTrue( Router::route( $testRoutes, $req ) );

		$this->assertTrue( MockController::$staticRouteCalled );
	}

	public function testDynamicRoute()
	{
		$testRoutes = array(
			'get /this/is/a' => 'fail',
			'get /this/is/a/test/route' => 'fail',
			'post /:a1/:a2/:a3/:a4/:a5' => 'fail',
			'put /dynamic/:a1/:a2/:a3/:a4' => 'dynamicRoute',
			'delete /this/is/a/test/route' => 'fail',
			'get /this/is/a/test/route/' => 'fail',
		);

		$server = $_SERVER;
		$server[ 'REQUEST_METHOD' ] = 'PUT';

		$req = new Request( null, null, null, null, $server );
		$req->setPath( '/dynamic/1/2/3/4' );

		$this->assertTrue( Router::route( $testRoutes, $req ) );

		$this->assertTrue( MockController::$dynamicRouteCalled );

		// test route params
		$expected = array( 'a1' => 1, 'a2' => 2, 'a3' => 3, 'a4' => 4 );
		$this->assertEquals( MockController::$dynamicRouteParams, $expected );
	}

	public function testSingleAction()
	{
		$testRoutes = array(
			'get /this/is/a/test/route' => 'fail',
			'post /this/is/a/test/route/:test' => 'fail',
			'post /this/is/a/test/route' => 'staticRoute',
			'delete /this/is/a/test/route' => 'fail',
			'post /this/is/a/test/route/' => 'fail',
		);

		$server = $_SERVER;
		$server[ 'REQUEST_METHOD' ] = 'POST';

		$req = new Request( null, null, null, null, $server );
		$req->setPath( '/this/is/a/test/route' );

		$this->assertTrue( Router::route( $testRoutes, $req ) );

		$this->assertTrue( MockController::$staticRouteCalled );
	}

	public function testIndex()
	{
		// testing to see if index is appended when a method is not specified
		$testRoutes = array(
			'get /this/is/a' => array( 'MockController', 'fail' ),
			'get /this/is/a/test/route' => array( 'MockController', 'fail' ),
			'post /this/is/a/test/route/:test' => array( 'MockController', 'fail' ),
			'post /this/is/a/test/route' => array( 'MockController' ),
			'delete /this/is/a/test/route' => array( 'MockController', 'fail' ),
			'post /this/is/a/test/route/' => array( 'MockController', 'fail' ),
		);

		$server = $_SERVER;
		$server[ 'REQUEST_METHOD' ] = 'POST';

		$req = new Request( null, null, null, null, $server );
		$req->setPath( '/this/is/a/test/route' );

		$this->assertTrue( Router::route( $testRoutes, $req ) );

		$this->assertTrue( MockController::$indexRouteCalled );
	}

	public function testNonExistentController()
	{
		// call a route with a bogus controller
		$testRoutes = array(
			'post /this/is/a/test/route' => array( 'BogusController', 'who_cares' ),
		);

		$server = $_SERVER;
		$server[ 'REQUEST_METHOD' ] = 'POST';

		$req = new Request( null, null, null, null, $server );
		$req->setPath( '/this/is/a/test/route' );

		$this->assertFalse( Router::route( $testRoutes, $req ) );
	}

	public function testRouterControllerParam()
	{
		Router::configure( array( 'defaultController' => 'BogusController' ) );

		$testRoutes = array(
			'post /this/is/a/test/route' => 'staticRoute',
			'get /not/it' => 'fail'
		);

		$server = $_SERVER;
		$server[ 'REQUEST_METHOD' ] = 'POST';

		$req = new Request( null, null, null, null, $server );
		$req->setPath( '/this/is/a/test/route' );

		$req->setParams( array( 'controller' => 'MockController' ) );

		$this->assertTrue( Router::route( $testRoutes, $req ) );

		$this->assertTrue( MockController::$staticRouteCalled );
	}
}

class MockController
{
	public static $staticRouteCalled = false;
	public static $dynamicRouteCalled = false;
	public static $dynamicRouteParams = array();
	public static $indexRouteCalled = false;

	public function staticRoute( $req, $res )
	{
		self::$staticRouteCalled = true;
	}

	public function dynamicRoute( $req, $res )
	{
		self::$dynamicRouteCalled = true;
		self::$dynamicRouteParams = $req->params();
	}

	public function index( $req, $res )
	{
		self::$indexRouteCalled = true;
	}

	public function fail( $req, $res )
	{
		// FAIL
	}
}