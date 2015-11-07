<?php

namespace Infuse\Queue\Driver;

use Infuse\Queue;
use Infuse\Queue\Message;

interface DriverInterface
{
    /**
     * Puts a message onto the queue.
     *
     * @param Queue  $queue
     * @param string $message
     * @param array  $parameters
     *
     * @return Message
     */
    public function enqueue(Queue $queue, $message, array $parameters);

    /**
     * Takes 1 or more messages off the queue.
     *
     * @param Queue $queue
     * @param int   $n     number of messages to dequeue
     *
     * @return array list of Message objects from queue
     */
    public function dequeue(Queue $queue, $n);

    /**
     * Removes a message from its queue.
     *
     * @param Message $message
     *
     * @return bool
     */
    public function deleteMessage(Message $message);
}
