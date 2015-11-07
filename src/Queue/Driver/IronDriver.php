<?php

namespace Infuse\Queue\Driver;

use Infuse\Queue;
use Infuse\Queue\Message;
use Infuse\Request;
use Pimple\Container;

class IronDriver implements DriverInterface
{
    /**
     * @var Container
     */
    private $app;

    /**
     * @param \Pimple\Container $app
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    public function enqueue(Queue $queue, $data, array $parameters)
    {
        $result = $this->app['ironmq']->postMessage($queue->getName(), $data, $parameters);

        return new Message($queue, $result->id, $data);
    }

    public function dequeue(Queue $queue, $n)
    {
        return $this->app['ironmq']->getMessages($queue->getName(), $n);
    }

    public function deleteMessage(Message $message)
    {
        return $this->app['ironmq']->deleteMessage($message->getQueue()->getName(), $message->getId());
    }

    /**
     * Builds a queue message from an incoming push queue
     * request.
     *
     * @param Request $req
     *
     * @return Message
     */
    public function buildMessageFromRequest(Request $req)
    {
        $queue = new Queue($req->query('q'));

        $id = $req->headers('Iron_Message_Id');
        $body = $req->request(); // should be a string

        return new Message($queue, $id, $body);
    }

    /**
     * Setup push queues with iron.io. Replaces any existing
     * configuration or subscribers.
     *
     * @param array  $queues    list of queue names to install
     * @param string $baseUrl   URL of listening endpoint
     * @param string $authToken secret auth token for validating incoming messages
     * @param string $pushType  unicast or multicast
     *
     * @return bool success
     */
    public function install(array $queues, $baseUrl, $authToken, $pushType = 'unicast')
    {
        $ironmq = $this->app['ironmq'];

        $success = true;
        foreach ($queues as $queue) {
            // build the push endpoint for this queue
            $subscriberUrl = $this->getPushQueueUrl($queue, $baseUrl, $authToken);

            // each queue has a single subscriber at the
            // endpoint we just generated
            $subscribers = [['url' => $subscriberUrl]];

            // now create it on iron.io
            $success = $ironmq->updateQueue($queue, [
                'push_type' => $pushType,
                'subscribers' => $subscribers, ]) && $success;
        }

        return $success;
    }

    /**
     * Generates the endpoints of the push queue subscribers (iron.io) for
     * each queue in the configuration.
     *
     * @param string $queue     queue name
     * @param string $baseUrl   URL of listening endpoint
     * @param string $authToken secret auth token for validating incoming messages
     *
     * @return string
     */
    public function getPushQueueUrl($queue, $baseUrl, $authToken)
    {
        $params = [
            'q' => $queue,
            'auth_token' => $authToken,
        ];

        return $baseUrl.'?'.http_build_query($params);
    }
}
