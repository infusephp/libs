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