<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.17.1
 * @copyright 2013 Jared King
 * @license MIT
 */

namespace infuse;

class Router
{
	private static $config = array(
		'namespace' => '',
		'defaultController' => '',
		'defaultAction' => 'index'
	);
	
	/**
	 * Changes the router settings
	 *
	 * @param array $config
	 */
	static function configure( $config )
	{
		self::$config = array_replace( self::$config, (array)$config );
	}

	/**
	 * Routes a request and resopnse to the appropriate controller.
	 *
	 * @param array $routes
	 * @param Request $req
	 * @param Response $res
	 *
	 * @return boolean was a route match made?
	 */
	static function route( $routes, $req = null, $res = null )
	{
		if( !$req )
			$req = new Request();
		
		if( !$res )
			$res = new Response();
	
		/*
			Route Precedence:
			1) global static routes (i.e. /about)
			2) global dynamic routes (i.e. /browse/:category)
		*/
		
		$routeMethodStr = strtolower( $req->method() ) . ' ' . $req->basePath();
		$routeGenericStr = $req->basePath();

		$staticRoutes = array();
		$dynamicRoutes = array();
		
		foreach( $routes as $routeStr => $route )
		{
			if( strpos( $routeStr, ':' ) )
				$dynamicRoutes[ $routeStr ] = $route;
			else
				$staticRoutes[ $routeStr ] = $route;
		}
		
		/* global static routes */						
		if( isset( $staticRoutes[ $routeMethodStr ] ) )
			return self::performRoute( $staticRoutes[ $routeMethodStr ], $req, $res );
		
		if( isset( $staticRoutes[ $routeGenericStr ] ) )
			return self::performRoute( $staticRoutes[ $routeGenericStr ], $req, $res );
		
		/* global dynamic routes */
		
		foreach( $dynamicRoutes as $routeStr => $route )
		{
			if( self::matchRouteToRequest( $routeStr, $req ) )
				return self::performRoute( $route, $req, $res );
		}
		
		return false;
	}

	//////////////////////////
	// PRIVATE METHODS
	//////////////////////////
	
	/**
	 * Executes a route. If the route returns -1 then failure is assumed.
	 *
	 * @param array|string $route array('controller','method') or array('controller')
	 * or 'method'
	 * @param Request $req
	 * @param Response $res
	 *
	 * @return boolean
	 */
	private static function performRoute( $route, $req, $res )
	{
		// method name and controller supplied
		if( is_string( $route ) && $req->params( 'controller' ) )
			$route = array( $req->params( 'controller' ), $route );
		// method name supplied
		if( is_string( $route ) )
			$route = array( self::$config[ 'defaultController' ], $route );
		// no method name? fallback to the index() method
		else if( count( $route ) == 1 )
			$route[] = self::$config[ 'defaultAction' ];
		
		list( $controller, $method ) = $route;

		$result = false;
		
		$controller = self::$config[ 'namespace' ] . '\\' . $controller;
		
		if( !class_exists( $controller ) )
			return false;

		$controllerObj = new $controller();
		
		$result = $controllerObj->$method( $req, $res );
		
		return $result !== -1;
	}
	
	/**
	 * Checks if a request matches a given route. If so, the parameters will
	 * be extracted and returned
	 *
	 * @param array|false
	 * @param Request $req
	 *
	 * @return boolean
	 */
	private static function matchRouteToRequest( $route, $req )
	{
		$routeParts = explode( ' ', $route );
		
		// verify that the method matches
		if( count( $routeParts ) != 1 && $routeParts[ 0 ] != strtolower( $req->method() ) )
			return false;
		
		// break the url into components
		$reqPaths = $req->paths();
		$routePaths = explode( '/', end( $routeParts ) );
		if( $routePaths[ 0 ] == '' )
			array_splice( $routePaths, 0, 1 );
		
		// check that the number of components match
		if( count( $reqPaths ) != count( $routePaths ) )
			return false;
		
		// compare each component of url, grab parameters along the way
		$params = array();
		foreach( $routePaths as $i => $path )
		{
			// is this a parameter
			if( substr( $path, 0, 1 ) == ':' )
			{
				$key = substr_replace( $path, '', 0, 1 );
				$params[ $key ] = $reqPaths[ $i ];
			}
			else
			{
				if( $reqPaths[ $i ] != $path )
					return false;
			}
		}
		
		$req->setParams( $params );
		
		return true;
	}
}