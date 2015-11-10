<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Infuse\Queue;
use Infuse\Queue\Driver\IronDriver;
use Infuse\Queue\Message;
use Infuse\Request;
use Pimple\Container;

class IronTest extends PHPUnit_Framework_TestCase
{
    public function testEnqueue()
    {
        $queue = new Queue('test_iron');

        $result = new stdClass();
        $result->id = 'id';

        $result2 = new stdClass();
        $result2->id = 'id2';

        $c = new Container();
        $iron = Mockery::mock('IronMQ');
        $iron->shouldReceive('postMessage')
             ->withArgs(['test_iron', 'test string!', []])
             ->andReturn($result)
             ->once();
        $iron->shouldReceive('postMessage')
             ->withArgs(['test_iron', 'test2', []])
             ->andReturn($result2)
             ->once();
        $c['ironmq'] = $iron;

        $driver = new IronDriver($c);

        $message = $driver->enqueue($queue, 'test string!', []);

        $this->assertInstanceOf('Infuse\Queue\Message', $message);
        $this->assertEquals('test string!', $message->getBody());
        $this->assertEquals('id', $message->getId());

        $message2 = $driver->enqueue($queue, 'test2', []);
        $this->assertInstanceOf('Infuse\Queue\Message', $message2);
        $this->assertEquals('test2', $message2->getBody());
        $this->assertEquals('id2', $message2->getId());
    }

    public function testDequeue()
    {
        $queue = new Queue('test_iron');

        $message = new Message($queue, 'id', 'message');
        $message2 = new Message($queue, 'id2', 'message2');

        $c = new Container();
        $iron = Mockery::mock('IronMQ');
        $iron->shouldReceive('getMessages')
             ->withArgs(['test_iron', 2])
             ->andReturn([$message, $message2])
             ->once();
        $c['ironmq'] = $iron;

        $driver = new IronDriver($c);

        $messages = $driver->dequeue($queue, 2);

        $this->assertEquals([$message, $message2], $messages);
    }

    public function testDeleteMessage()
    {
        $queue = new Queue('test_iron');

        $message = new Message($queue, 'id', 'message');

        $c = new Container();
        $iron = Mockery::mock('IronMQ');
        $iron->shouldReceive('deleteMessage')
             ->withArgs(['test_iron', 'id'])
             ->andReturn(true)
             ->once();
        $c['ironmq'] = $iron;

        $driver = new IronDriver($c);

        $this->assertTrue($driver->deleteMessage($message));
    }

    public function testBuildMessageFromRequest()
    {
        $req = new Request(['q' => 'test_iron'], 'message_body', [], [], ['HTTP_IRON_MESSAGE_ID' => 'id']);

        $c = new Container();
        $driver = new IronDriver($c);

        $message = $driver->buildMessageFromRequest($req);

        $this->assertInstanceOf('Infuse\Queue\Message', $message);
        $this->assertEquals('id', $message->getId());
        $this->assertEquals('message_body', $message->getBody());
    }

    public function testGetPushQueueUrl()
    {
        $c = new Container();
        $driver = new IronDriver($c);

        $queue = 'test_iron';
        $base = 'https://example.com/iron/message';
        $authToken = 'secret';

        $this->assertEquals('https://example.com/iron/message?q=test_iron&auth_token=secret', $driver->getPushQueueUrl($queue, $base, $authToken));
    }

    public function testInstall()
    {
        $queue1 = [
            'push_type' => 'multicast',
            'subscribers' => [
                [
                    'name' => 'infuse/iron-mq',
                    'url' => 'https://example.com/iron/message?q=test1&auth_token=secret', ],
            ],
        ];

        $queue2 = [
            'push_type' => 'multicast',
            'subscribers' => [
                [
                    'name' => 'infuse/iron-mq',
                    'url' => 'https://example.com/iron/message?q=test2&auth_token=secret', ],
            ],
        ];

        $c = new Container();
        $iron = Mockery::mock('IronMQ');
        $iron->shouldReceive('updateQueue')
             ->withArgs(['test1', $queue1])
             ->andReturn(true)
             ->once();
        $iron->shouldReceive('updateQueue')
             ->withArgs(['test2', $queue2])
             ->andReturn(true)
             ->once();
        $c['ironmq'] = $iron;
        $driver = new IronDriver($c);

        $queues = [
            'test1',
            'test2',
        ];

        $base = 'https://example.com/iron/message';
        $authToken = 'secret';
        $pushType = 'multicast';

        $this->assertTrue($driver->install($queues, $base, $authToken, $pushType));
    }
}
