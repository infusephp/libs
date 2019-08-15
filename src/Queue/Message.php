<?php

namespace Infuse\Queue;

use Infuse\Queue;
use Symfony\Contracts\EventDispatcher\Event;

class Message extends Event
{
    /**
     * @var Queue
     */
    private $queue;

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $body;

    /**
     * @param \Infuse\Queue $queue
     * @param string        $id
     * @param string        $body
     */
    public function __construct(Queue $queue, $id, $body)
    {
        $this->queue = $queue;
        $this->id = $id;
        $this->body = $body;
    }

    /**
     * Gets the queue this message was posted to.
     *
     * @return Queue
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Gets the message id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the message body.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Deletes the message from the queue. This should be called
     * once done with a message pulled off the queue.
     *
     * @return bool
     */
    public function delete()
    {
        return $this->queue->getDriver()->deleteMessage($this);
    }
}
