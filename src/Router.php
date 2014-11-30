<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse;

use Pimple\Container;

if (!defined('SKIP_ROUTE')) {
    define('SKIP_ROUTE', -1);
}

class Router
{
    private static $config = [
        'namespace' => '',
        'defaultController' => '',
        'defaultAction' => 'index',
    ];

    /**
     * Changes the router settings
     *
     * @param array $config
     */
    public static function configure($config)
    {
        self::$config = array_replace(self::$config, (array) $config);
    }

    /**
     * Routes a request and resopnse to the appropriate controller.
     *
     * @param array     $routes
     * @param Request   $req
     * @param Response  $res
     * @param Container $app    DI container
     *
     * @return boolean was a route match made?
     */
    public static function route(array $routes, Container $app, Request $req, Response $res)
    {
        /*
            Route Precedence:
            1) global static routes (i.e. /about)
            2) global dynamic routes (i.e. /browse/:category)
        */

        $routeMethodStr = strtolower($req->method()).' '.$req->path();
        $routeGenericStr = $req->path();

        $staticRoutes = [];
        $dynamicRoutes = [];

        foreach ($routes as $routeStr => $route) {
            if (strpos($routeStr, ':')) {
                $dynamicRoutes[ $routeStr ] = $route;
            } else {
                $staticRoutes[ $routeStr ] = $route;
            }
        }

        /* global static routes */
        if (isset($staticRoutes[ $routeMethodStr ]) &&
            self::performRoute($staticRoutes[ $routeMethodStr ], $app, $req, $res) !== SKIP_ROUTE) {
            return true;
        }

        if (isset($staticRoutes[ $routeGenericStr ]) &&
            self::performRoute($staticRoutes[ $routeGenericStr ], $app, $req, $res) !== SKIP_ROUTE) {
            return true;
        }

        /* global dynamic routes */

        foreach ($dynamicRoutes as $routeStr => $route) {
            if (self::matchRouteToRequest($routeStr, $req) &&
                self::performRoute($route, $app, $req, $res) !== SKIP_ROUTE) {
                return true;
            }
        }

        return false;
    }

    //////////////////////////
    // PRIVATE METHODS
    //////////////////////////

    /**
     * Executes a route. If the route returns SKIP_ROUTE then failure is assumed.
     *
     * @param array|string $route array('controller','method') or array('controller')
     *                            or 'method'
     * @param Container DI container
     * @param Request      $req
     * @param Response     $res
     *
     * @return boolean
     */
    private static function performRoute($route, Container $app, $req, $res)
    {
        $result = false;

        if (is_array($route) || is_string($route)) {
            // method name and controller supplied
            if (is_string($route) && $req->params('controller')) {
                $route = [ $req->params('controller'), $route ];
            }
            // method name supplied
            elseif (is_string($route)) {
                $route = [ self::$config[ 'defaultController' ], $route ];
            }
            // no method name? fallback to the index() method
            elseif (count($route) == 1) {
                $route[] = self::$config[ 'defaultAction' ];
            }

            list($controller, $method) = $route;

            $controller = self::$config[ 'namespace' ].'\\'.$controller;

            if (!class_exists($controller)) {
                return SKIP_ROUTE;
            }

            $controllerObj = new $controller();

            if (method_exists($controllerObj, 'injectApp')) {
                $controllerObj->injectApp($app);
            }

            $result = $controllerObj->$method($req, $res);
        } elseif (is_callable($route)) {
            $result = call_user_func($route, $req, $res);
        }

        if ($result === SKIP_ROUTE) {
            return SKIP_ROUTE;
        } elseif ($result instanceof View) {
            $res->render($result);
        }

        return true;
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
    private static function matchRouteToRequest($route, $req)
    {
        $routeParts = explode(' ', $route);

        // verify that the method matches
        if (count($routeParts) != 1 && $routeParts[ 0 ] != strtolower($req->method())) {
            return false;
        }

        // break the url into components
        $reqPaths = $req->paths();
        $routePaths = explode('/', end($routeParts));
        if ($routePaths[ 0 ] == '') {
            array_splice($routePaths, 0, 1);
        }

        // check that the number of components match
        if (count($reqPaths) != count($routePaths)) {
            return false;
        }

        // compare each component of url, grab parameters along the way
        $params = [];
        foreach ($routePaths as $i => $path) {
            // is this a parameter
            if (substr($path, 0, 1) == ':') {
                $key = substr_replace($path, '', 0, 1);
                $params[ $key ] = $reqPaths[ $i ];
            } else {
                if ($reqPaths[ $i ] != $path) {
                    return false;
                }
            }
        }

        $req->setParams($params);

        return true;
    }
}
