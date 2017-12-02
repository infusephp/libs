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
use Mockery\Adapter\Phpunit\MockeryTestCase;

class IronTest extends MockeryTestCase
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
        $iron->shouldReceive('reserveMessages')
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

    public function testInstallPush()
    {
        $queue = [
            'type' => 'multicast',
            'message_timeout' => 60,
            'message_expiration' => 2592000,
            'push' => [
                'retries' => 3,
                'retries_delay' => 60,
                'subscribers' => [
                    [
                        'name' => 'infuse/iron-mq',
                        'url' => 'https://example.com/iron/message?q=test1&auth_token=secret',
                    ],
                ],
            ],
        ];

        $c = new Container();
        $iron = Mockery::mock('IronMQ');
        $iron->shouldReceive('createQueue')
             ->withArgs(['test1', $queue])
             ->andReturn(true)
             ->once();
        $c['ironmq'] = $iron;
        $driver = new IronDriver($c);

        $options = ['type' => 'multicast'];
        $base = 'https://example.com/iron/message';
        $authToken = 'secret';

        $this->assertTrue($driver->install('test1', $options, $base, $authToken));
    }

    public function testInstallPull()
    {
        $queue = [
            'type' => 'pull',
            'message_timeout' => 1000,
            'message_expiration' => 2592000,
        ];

        $c = new Container();
        $iron = Mockery::mock('IronMQ');
        $iron->shouldReceive('createQueue')
             ->withArgs(['test2', $queue])
             ->andReturn(true)
             ->once();
        $c['ironmq'] = $iron;
        $driver = new IronDriver($c);

        $options = ['type' => 'pull', 'message_timeout' => 1000];

        $this->assertTrue($driver->install('test2', $options));
    }
}
