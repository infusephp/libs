<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace infuse\Session;

use Pimple\Container;
use SessionHandlerInterface;

class Redis implements SessionHandlerInterface
{
    /**
     * @var Container
     */
    private $app;

    /**
     * @var string
     */
    private $prefix;

    /**
     * Starts the session using this handler
     *
     * @param Session $app
     *
     * @return boolean
     */
    public static function registerHandler(Redis $handler)
    {
        return session_set_save_handler($handler, true);
    }

    /**
     * Creates a new session handler
     *
     * @param Container $app
     */
    public function __construct(Container $app, $prefix = '')
    {
        $this->app = $app;
        $this->prefix = $prefix.'php.session.';
    }

    /**
     * Loads a session into $_SESSION given a session id
     *
     * @param string $id session id
     */
    public function read($id)
    {
        return $this->app['redis']->get($this->prefix.$id);
    }

    /**
     * Persists data from the session
     *
     * @param string $id   session id
     * @param string $data data
     */
    public function write($id, $data)
    {
        $ttl = ini_get('session.gc_maxlifetime');

        $this->app['redis']->setex($this->prefix.$id, $ttl, $data);
    }

    /**
     * Destroys a session
     *
     * @param string $id session id
     */
    public function destroy($id)
    {
        $this->app['redis']->del($this->prefix.$id);
    }

    /**
     * These functions are all noops for various reasons...
     * open() and close() have no practical meaning in terms of non-shared Redis connections
     * Garbage collection is handled by Redis with ttls.
     */
    public function open($path, $name)
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    public function gc($age)
    {
        return true;
    }
}
