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

use Infuse\Queue\Driver\DriverInterface;
use Infuse\Queue\Message;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Queue
{
    /**
     * @var DriverInterface
     */
    private static $driver;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcher
     */
    private static $dispatcher;

    /**
     * @var string
     */
    private $name;

    /**
     * Sets the driver to be used by all queues.
     *
     * @param DriverInterface $driver queue backend
     */
    public static function setDriver(DriverInterface $driver)
    {
        self::$driver = $driver;
    }

    /**
     * @param string $name queue name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Gets the driver for this queue.
     *
     * @return DriverInterface
     */
    public function getDriver()
    {
        return self::$driver;
    }

    /**
     * Gets the name of this queue.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Puts a message onto the queue.
     *
     * @param string $message
     * @param array  $parameters
     *
     * @return Message
     */
    public function enqueue($message, $parameters = [])
    {
        return self::$driver->enqueue($this, $message, $parameters);
    }

    /**
     * Takes one or messages off the queue
     * WARNING remember to delete the message when finished.
     *
     * @param int $n number of messages to dequeue
     *
     * @return array|object|null when N > 1, returns array, when N = 1, returns object, or false when there's nothing to dequeue
     */
    public function dequeue($n = 1)
    {
        $messages = self::$driver->dequeue($this, $n);

        if ($n === 1) {
            return (isset($messages[0])) ? $messages[0] : false;
        }

        return $messages;
    }

    ///////////////////////////
    // Queue Listeners
    ///////////////////////////

    /**
     * Subscribes a listener to a queue.
     *
     * @param string   $queue    queue name to listen to
     * @param callable $listener
     * @param int      $priority optional priority, higher #s get called first
     */
    public static function listen($queue, callable $listener, $priority = 0)
    {
        self::getDispatcher()->addListener($queue, $listener, $priority);
    }

    /**
     * Dispatches an incoming message from the queue.
     *
     * @param Message $message
     * 
     * @return Message
     */
    public static function receiveMessage(Message $message)
    {
        return self::getDispatcher()->dispatch($message->getQueue()->getName(), $message);
    }

    /**
     * Gets the event dispatcher.
     *
     * @param bool $ignoreCache when true, overwrites existing dispatcher
     *
     * @return \Symfony\Component\EventDispatcher\EventDispatcher
     */
    public static function getDispatcher($ignoreCache = false)
    {
        if (!self::$dispatcher || $ignoreCache) {
            self::$dispatcher = new EventDispatcher();
        }

        return self::$dispatcher;
    }
}
