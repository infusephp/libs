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

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Pimple\Container;

class Router
{
    /**
     * @var array
     */
    private $routes = [];

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
        $this->settings = array_replace([
            'namespace' => '',
            'defaultController' => '',
            'defaultAction' => 'index',
        ], $settings);

        foreach ($routes as $route => $handler) {
            $parts = explode(' ', $route);
            list($method, $endpoint) = $parts;

            $this->map($method, $endpoint, $handler);
        }
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
        $this->map('GET', $route, $handler);

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
        $this->map('POST', $route, $handler);

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
        $this->map('PUT', $route, $handler);

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
        $this->map('DELETE', $route, $handler);

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
        $this->map('PATCH', $route, $handler);

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
        $this->map('OPTIONS', $route, $handler);

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
        $method = strtoupper($method);
        $this->routes[] = [$method, $route, $handler];

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
        $router = $this;
        $dispatcher = \FastRoute\simpleDispatcher(function (RouteCollector $r) use ($router) {
            foreach ($router->getRoutes() as $route) {
                $r->addRoute($route[0], $route[1], $route[2]);
            }
        });

        // the dispatcher returns an array in the format:
        // [result, handler, parameters]
        $routeInfo = $dispatcher->dispatch($req->method(), $req->path());

        // 404 Not Found
        if ($routeInfo[0] === Dispatcher::NOT_FOUND) {
            $res->setCode(404);

            return false;
        }

        // 405 Method Not Allowed
        if ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            // $allowedMethods = $routeInfo[1];
            $res->setCode(405);

            return false;
        }

        // the route was found
        // set any parameters that come from matching the route
        $req->setParams($routeInfo[2]);

        return $this->performRoute($routeInfo[1], $app, $req, $res);
    }

    //////////////////////////
    // PRIVATE METHODS
    //////////////////////////

    /**
     * Executes a route handler.
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
                $res->setCode(404);

                return false;
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

        if ($result instanceof View) {
            $res->render($result);
        }

        return true;
    }
}
