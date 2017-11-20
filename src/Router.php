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
            'cacheFile' => null,
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * Builds a FastRoute dispatcher with the routing table.
     *
     * @return \FastRoute\Dispatcher
     */
    public function getDispatcher()
    {
        if ($this->settings['cacheFile']) {
            $opts = ['cacheFile' => $this->settings['cacheFile']];

            return \FastRoute\cachedDispatcher([$this, 'buildRoutes'], $opts);
        } else {
            return \FastRoute\simpleDispatcher([$this, 'buildRoutes']);
        }
    }

    /**
     * Adds routes to the given collector.
     *
     * @param RouteCollector $r
     */
    public function buildRoutes(RouteCollector $r)
    {
        foreach ($this->routes as $route) {
            $r->addRoute($route[0], $route[1], $route[2]);
        }
    }

    /**
     * Dispatches a request using the routing table.
     *
     * @param string $method
     * @param string $path
     *
     * @return array route [result, handler, parameters]
     */
    public function dispatch($method, $path)
    {
        return $this->getDispatcher()->dispatch($method, $path);
    }
}
