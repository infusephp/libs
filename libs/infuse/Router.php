<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.15.5
 * @copyright 2013 Jared King
 * @license MIT
 */

namespace infuse;

class Router
{
	private static $config = array(
		'base_class' => '\\infuse\\controllers',
		'use_modules' => false,
		'default' => array()
	);
	
	/**
	 * Routes a request and resopnse to the appropriate controller. Sends a 404 if nothing was found.
	 *
	 * @param array $routes
	 * @param Request $req
	 * @param Response $res
	 *
	 * @return boolean a route match was made
	 */
	static function route( $routes, $req = null, $res = null )
	{
		if( !$req )
			$req = new Request();
		
		if( !$res )
			$res = new Response();
	
		/*
			Route Precedence:
			1) global static routes (i.e. /about -> Controller::action())
			2) global dynamic routes (i.e. /browse/:category)
			
			Notes:
			- No action supplied in route defaults to the 'index' action
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
	 * Gets a setting of the router
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	static function setting( $key )
	{
		return Util::array_value( self::$config, $key );
	}
	
	/**
	 * Executes a route. If the route returns -1 then failure is assumed.
	 *
	 * @param array $route
	 * @param Request $req
	 * @param Response $res
	 */
	private static function performRoute( $route, $req, $res )
	{
		$controller = (isset($route['controller'])) ? $route[ 'controller' ] : $req->params( 'controller' );

		// fallback to default controller
		if( !$controller )
			$controller = Util::array_value( self::$config[ 'default' ], 'controller' );
		
		$action = (isset($route['action'])) ? $route[ 'action' ] : 'index';
	
		if( !is_array( $route ) )
			$action = $route;

		$result = false;
		
		if( self::$config[ 'use_modules' ] )
		{
			Modules::load( $controller );
			
			$result = Modules::controller( $controller )->$action( $req, $res );
		}
		else
		{
			$controller = self::$config[ 'base_class' ] . '\\' . $controller;
			
			if( !class_exists( $controller ) )
				return false;

			$controllerObj = new $controller();
			
			$result = $controllerObj->$action( $req, $res );
		}
		
		return $result !== -1;
	}
	
	/**
	 * Checks if a request matches a given route. If so, the parameters will
	 * be extracted and returned
	 *
	 * @param array|false
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