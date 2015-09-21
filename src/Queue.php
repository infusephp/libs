<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace infuse;

use Pimple\Container;

if (!defined('QUEUE_TYPE_IRON')) {
    define('QUEUE_TYPE_IRON', 'iron');
}
if (!defined('QUEUE_TYPE_SYNCHRONOUS')) {
    define('QUEUE_TYPE_SYNCHRONOUS', 'synchronous');
}

class Queue
{
    /**
     * @staticvar array
     */
    private static $config = [
        'queues' => [],
        'namespace' => '',
        // used for iron.io
        'push_type' => 'unicast',
        'token' => '',
        'project' => '',
    ];

    /**
     * Used for synchronous mode.
     *
     * @staticvar array
     */
    private static $queues = [];

    /**
     * @var int
     */
    private static $idCounter = 1;

    /**
     * @staticvar \Pimple\Container
     */
    private static $app;

    /**
     * @staticvar array
     */
    private static $queueTypes = [
        QUEUE_TYPE_IRON,
        QUEUE_TYPE_SYNCHRONOUS,
    ];

    /**
     * @var string
     */
    private $type;

    /**
     * @var array
     */
    private $listeners;

    /**
     * Changes the queue settings.
     *
     * @param array $config
     */
    public static function configure(array $config)
    {
        self::$config = array_replace(self::$config, $config);
    }

    public function __construct($type, array $listeners = [])
    {
        if (!in_array($type, self::$queueTypes)) {
            $type = QUEUE_TYPE_SYNCHRONOUS;
        }

        $this->type = $type;
        $this->listeners = $listeners;
    }

    /**
     * Returns the type of the queue.
     *
     * @return string synchronous|iron
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Puts a message onto the queue.
     *
     * @param string $queue      queue name
     * @param mixed  $message
     * @param array  $parameters
     *
     * @return bool success
     */
    public function enqueue($queue, $message, $parameters = [])
    {
        if ($this->type == QUEUE_TYPE_IRON) {
            // serialize arrays and objects stored in queue
            if (is_array($message) || is_object($message)) {
                $message = json_encode($message);
            }

            try {
                return self::$app[ 'ironmq' ]->postMessage($queue, $message, $parameters);
            } catch (\Exception $e) {
                self::$app[ 'logger' ]->error($e);

                return false;
            }
        }
        // synchronous queue
        else {
            if (!isset(self::$queues[ $queue ])) {
                self::$queues[ $queue ] = [];
            }

            // wrap the message inside of an object
            $messageWrapper = new \stdClass();

            $messageWrapper->id = self::$idCounter;
            $messageWrapper->body = $message;

            ++self::$idCounter;

            // add the serialized message wrapper to the queue
            $json = json_encode($messageWrapper);
            self::$queues[ $queue ][] = $json;

            // since this is synchronous mode, notify all listeners that we have a new message
            $this->receiveMessage($queue, $json);

            return true;
        }
    }

    /**
     * Takes one or messages off the queue
     * WARNING remember to delete the message when finished.
     *
     * @param string $queue queue name
     * @param int    $n     number of messages to dequeue
     *
     * @return array($n > 1)|object($n = 1)|null message(s) or not found
     */
    public function dequeue($queue, $n = 1)
    {
        $messages = [];

        if ($this->type == QUEUE_TYPE_IRON) {
            try {
                $messages = self::$app[ 'ironmq' ]->getMessages($queue, $n);
            } catch (\Exception $e) {
                self::$app[ 'logger' ]->error($e);

                return;
            }
        }
        // synchronous queue
        else {
            if (isset(self::$queues[ $queue ])) {
                $messages = array_slice(self::$queues[ $queue ], 0, $n);

                foreach ($messages as $k => $m) {
                    $messages[ $k ] = json_decode($m);
                }
            }
        }

        if (count($messages) > 0 && $n == 1) {
            return reset($messages);
        } elseif ($n > 1) {
            return $messages;
        } else {
            return;
        }
    }

    /**
     * Removes a message from the queue. This should be called once
     * done with a message pulled off the queue.
     *
     * @param string $queue   queue name
     * @param object $message
     *
     * @return bool
     */
    public function deleteMessage($queue, $message)
    {
        if (!$message->id) {
            return true;
        }

        if ($this->type == QUEUE_TYPE_IRON) {
            try {
                return self::$app[ 'ironmq' ]->deleteMessage($queue, $message->id);
            } catch (\Exception $e) {
                self::$app[ 'logger' ]->error($e);

                return false;
            }
        }
        // synchronous queue
        else {
            if (!isset(self::$queues[ $queue ])) {
                return false;
            }

            // find the message with the specified id, and delete it
            foreach ((array) self::$queues[ $queue ] as $k => $str) {
                $m = json_decode($str);

                if ($m->id == $message->id) {
                    unset(self::$queues[ $queue ][ $k ]);
                    self::$queues[ $queue ] = array_values(self::$queues[ $queue ]);

                    return true;
                }
            }

            return false;
        }
    }

    /**
     * Notifies all listeners that a message has been received from the queue.
     *
     * @param string $queue   queue name
     * @param string $message message
     */
    public function receiveMessage($queue, $message)
    {
        $success = true;

        if (is_string($message)) {
            $message = json_decode($message);
        }

        $listeners = (array) Utility::array_value($this->listeners, $queue);

        // notify all listeners that we have a new message
        foreach ($listeners as $route) {
            list($controller, $method) = $route;

            $controller = self::$config[ 'namespace' ].'\\'.$controller;

            if (!class_exists($controller)) {
                continue;
            }

            $controllerObj = new $controller();

            if (method_exists($controllerObj, 'injectApp')) {
                $controllerObj->injectApp(self::$app);
            }

            $controllerObj->$method($this, $message);
        }
    }

    /**
     * Sets up the queue(s) according to the configuration. Usually only needs to be
     * called when the configuration changes, and certainly not on every request.
     *
     * @return bool success
     */
    public function install()
    {
        if ($this->type == QUEUE_TYPE_IRON) {
            // setup push queues with iron.io
            $ironmq = self::$app[ 'ironmq' ];
            $subscribers = $this->pushQueueSubscribers();

            $success = true;
            foreach ($subscribers as $q => $subscribers) {
                try {
                    $success = $ironmq->updateQueue($q, [
                        'push_type' => self::$config[ 'push_type' ],
                        'subscribers' => $subscribers, ]) && $success;
                } catch (\Exception $e) {
                    self::$app[ 'logger' ]->error($e);
                    $success = false;
                }
            }

            return $success;
        }

        return true;
    }

    /**
     * Generates the endpoints of the push queue subscribers (iron.io) for
     * each queue in the configuration.
     *
     * @return array subscriber endpoints
     */
    public function pushQueueSubscribers()
    {
        $subscribers = [];

        $authToken = Utility::array_value(self::$config, 'auth_token');

        foreach (self::$config[ 'queues' ] as $q) {
            // setup each push subscriber url with an auth token (if used)

            foreach ((array) Utility::array_value(self::$config, 'push_subscribers') as $s) {
                $url = $s."?q=$q";

                if (!empty($authToken)) {
                    $url .= "&auth_token=$authToken";
                }

                if (!isset($subscribers[ $q ])) {
                    $subscribers[ $q ] = [];
                }

                $subscribers[ $q ][] = ['url' => $url];
            }
        }

        return $subscribers;
    }

    /**
     * Injects a DI container.
     *
     * @param \Pimple\Container $app
     */
    public static function inject(Container $app)
    {
        self::$app = $app;
    }
}
