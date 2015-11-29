<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace Infuse;

use Pimple\Container;

class Router
{
    const SKIP_ROUTE = -1;

    /**
     * @var array
     */
    private $routes;

    /**
     * @var array
     */
    private $settings;

    /**
     * @param array $routes
     * @param array $settings Router settings
     */
    public function __construct(array $routes = [], array $settings = [])
    {
        $this->routes = $routes;
        $this->settings = array_replace([
            'namespace' => '',
            'defaultController' => '',
            'defaultAction' => 'index',
        ], $settings);
    }

    /**
     * Adds a handler to the routing table for a given GET route.
     *
     * @param string $route   path pattern
     * @param mixed  $handler route handler
     *
     * @return self
     */
    public function get($route, $handler)
    {
        $this->map('get', $route, $handler);

        return $this;
    }

    /**
     * Adds a handler to the routing table for a given POST route.
     *
     * @param string $route   path pattern
     * @param mixed  $handler route handler
     *
     * @return self
     */
    public function post($route, $handler)
    {
        $this->map('post', $route, $handler);

        return $this;
    }

    /**
     * Adds a handler to the routing table for a given PUT route.
     *
     * @param string $route   path pattern
     * @param mixed  $handler route handler
     *
     * @return self
     */
    public function put($route, $handler)
    {
        $this->map('put', $route, $handler);

        return $this;
    }

    /**
     * Adds a handler to the routing table for a given DELETE route.
     *
     * @param string $route   path pattern
     * @param mixed  $handler route handler
     *
     * @return self
     */
    public function delete($route, $handler)
    {
        $this->map('delete', $route, $handler);

        return $this;
    }

    /**
     * Adds a handler to the routing table for a given PATCH route.
     *
     * @param string $route   path pattern
     * @param mixed  $handler route handler
     *
     * @return self
     */
    public function patch($route, $handler)
    {
        $this->map('patch', $route, $handler);

        return $this;
    }

    /**
     * Adds a handler to the routing table for a given OPTIONS route.
     *
     * @param string $route   path pattern
     * @param mixed  $handler route handler
     *
     * @return self
     */
    public function options($route, $handler)
    {
        $this->map('options', $route, $handler);

        return $this;
    }

    /**
     * Adds a handler to the routing table for a given route.
     *
     * @param string $method  HTTP method
     * @param string $route   path pattern
     * @param mixed  $handler route handler
     *
     * @return self
     */
    public function map($method, $route, $handler)
    {
        $method = strtolower($method);
        $this->routes[$method.' '.$route] = $handler;

        return $this;
    }

    /**
     * Gets the routing table.
     *
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Routes a request and resopnse to the appropriate controller.
     *
     * @param \Pimple\Container $app DI container
     * @param Request           $req
     * @param Response          $res
     *
     * @return bool was a route match made?
     */
    public function route(Container $app, Request $req, Response $res)
    {
        /*
            Route Precedence:
            1) static routes (i.e. /about)
            2) dynamic routes (i.e. /browse/{category})
        */

        $routeMethodStr = strtolower($req->method()).' '.$req->path();

        $staticRoutes = [];
        $dynamicRoutes = [];

        foreach ($this->routes as $routeStr => $route) {
            if (strpos($routeStr, '{') && strpos($routeStr, '}')) {
                $dynamicRoutes[$routeStr] = $route;
            } else {
                $staticRoutes[$routeStr] = $route;
            }
        }

        /* static routes */
        if (isset($staticRoutes[$routeMethodStr]) &&
            $this->performRoute($staticRoutes[$routeMethodStr], $app, $req, $res) !== self::SKIP_ROUTE) {
            return true;
        }

        /* dynamic routes */
        foreach ($dynamicRoutes as $routeStr => $route) {
            if ($this->matchRouteToRequest($routeStr, $req) &&
                $this->performRoute($route, $app, $req, $res) !== self::SKIP_ROUTE) {
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
     * @param \Pimple\Container DI container
     * @param Request  $req
     * @param Response $res
     *
     * @return bool
     */
    private function performRoute($route, Container $app, $req, $res)
    {
        $result = false;

        if (is_array($route) || is_string($route)) {
            // method name and controller supplied
            if (is_string($route) && $req->params('controller')) {
                $route = [$req->params('controller'), $route];
            }
            // method name supplied
            elseif (is_string($route)) {
                $route = [$this->settings['defaultController'], $route];
            }
            // no method name? fallback to the index() method
            elseif (count($route) == 1) {
                $route[] = $this->settings['defaultAction'];
            }

            list($controller, $method) = $route;

            $controller = $this->settings['namespace'].'\\'.$controller;

            if (!class_exists($controller)) {
                return self::SKIP_ROUTE;
            }

            $controllerObj = new $controller();

            // give the controller access to the DI container
            if (method_exists($controllerObj, 'injectApp')) {
                $controllerObj->injectApp($app);
            }

            // collect any preset route parameters
            if (isset($route[2])) {
                $params = $route[2];
                $req->setParams($params);
            }

            $result = $controllerObj->$method($req, $res);
        } elseif (is_callable($route)) {
            $result = call_user_func($route, $req, $res);
        }

        if ($result === self::SKIP_ROUTE) {
            return self::SKIP_ROUTE;
        } elseif ($result instanceof View) {
            $res->render($result);
        }

        return true;
    }

    /**
     * Checks if a request matches a given route. When there is
     * a match the parameters will be added to the request.
     *
     * @param string  $routeStr route template we are trying to match
     * @param Request $req
     *
     * @return bool
     */
    private function matchRouteToRequest($routeStr, $req)
    {
        $routeParts = explode(' ', $routeStr);

        // verify that the method matches
        if (count($routeParts) != 1 && $routeParts[0] != strtolower($req->method())) {
            return false;
        }

        // break the url into components
        $reqPaths = $req->paths();
        $routePaths = explode('/', end($routeParts));
        if ($routePaths[0] == '') {
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
            if (substr($path, 0, 1) == '{' && substr($path, -1) == '}') {
                $key = substr($path, 1, -1);
                $params[$key] = $reqPaths[$i];
            } else {
                if ($reqPaths[$i] != $path) {
                    return false;
                }
            }
        }

        $req->setParams($params);

        return true;
    }
}
