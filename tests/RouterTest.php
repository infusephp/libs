<?php

use \infuse\Router;

require_once 'vendor/autoload.php';

class RouterTest extends \PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		Router::configure( array(
			'namespace' => '\\',
			'default' => array(
				'controller' => 'MockController' ) ) );
	}

	public function testStaticRoute()
	{
		$testRoutes = array(
			'get /this/is/a' => 'nope',
			'get /this/is/a/test/route' => 'nope',
			'post /this/is/a/test/route/:test' => 'nope',
			'post /this/is/a/test/route' => 'staticRoute',
			'delete /this/is/a/test/route' => 'nope',
			'get /this/is/a/test/route/' => 'nope',
		);

		$server = $_SERVER;
		$server[ 'REQUEST_METHOD' ] = 'POST';

		$req = new Request( null, null, null, null, $server );
		$req->setPath( '/this/is/a/test/route' );

		Router::route( $testRoutes, $req );
	}

	public function testDynamicRoute()
	{

	}

	public function testSingleAction()
	{
		// route is a string
	}

	public function testIndex()
	{
		// route is an array with 1 element

		// should call to index()
	}

	public function testNonExistentController()
	{
		// call a route with a bogus controller
	}
}

class MockController
{
	public function staticRoute( $req, $res )
	{

	}

	public function dynamicRoute( $req, $res )
	{

	}

	public function index( $req, $res )
	{

	}
}