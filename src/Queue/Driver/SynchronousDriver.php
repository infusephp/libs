<?php

namespace Infuse\Queue\Driver;

use Infuse\Queue;
use Infuse\Queue\Message;

class SynchronousDriver implements DriverInterface
{
    /**
     * @var array
     */
    private $queues = [];

    /**
     * @var int
     */
    private $idCounter = 1;

    public function enqueue(Queue $queue, $body, array $parameters)
    {
        $name = $queue->getName();
        if (!isset($this->queues[$name])) {
            $this->queues[$name] = [];
        }

        $message = new Message($queue, ++$this->idCounter, $body);

        $this->queues[$name][] = $message;

        // notify any listeners immediately
        // since this is synchronous mode
        Queue::receiveMessage($message);

        return $message;
    }

    public function dequeue(Queue $queue, $n)
    {
        $messages = [];

        $name = $queue->getName();
        if (isset($this->queues[$name])) {
            $messages = array_slice($this->queues[$name], 0, $n);
        }

        return $messages;
    }

    public function deleteMessage(Message $message)
    {
        $queue = $message->getQueue()->getName();
        if (isset($this->queues[$queue])) {
            $id = $message->getId();
            // find the message with a matching id, and delete it
            foreach ($this->queues[$queue] as $k => $message2) {
                if ($message2->getId() === $id) {
                    array_splice($this->queues[$queue], $k, 1);

                    return true;
                }
            }
        }

        return false;
    }
}
