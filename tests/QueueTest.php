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
use Infuse\Queue\Message;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class QueueTest extends MockeryTestCase
{
    public static $heard;

    public function testEnqueue()
    {
        $queue = new Queue('test');

        $driver = Mockery::mock('Infuse\Queue\Driver\DriverInterface');
        $driver->shouldReceive('enqueue')
               ->withArgs([$queue, 'test string!', []])
               ->andReturn(true)
               ->once();
        Queue::setDriver($driver);

        $this->assertTrue($queue->enqueue('test string!'));
    }

    public function testDequeue()
    {
        $queue = new Queue('test');

        $driver = Mockery::mock('Infuse\Queue\Driver\DriverInterface');
        $driver->shouldReceive('dequeue')
               ->withArgs([$queue, 1])
               ->andReturn(['message'])
               ->once();
        $driver->shouldReceive('dequeue')
               ->withArgs([$queue, 2])
               ->andReturn(['message', 'message2'])
               ->once();
        Queue::setDriver($driver);

        $this->assertEquals('message', $queue->dequeue());

        $this->assertEquals(['message', 'message2'], $queue->dequeue(2));
    }

    public function testListen()
    {
        // install some queue listeners
        Queue::listen('test', function (Message $message) {
            self::$heard = $message->getId();
        });

        Queue::listen('test', function (Message $message) {
            self::$heard = $message->getId().'_2';
        });

        Queue::listen('test2', function (Message $message) {
            self::$heard = $message->getId();
        });

        // now dispatch some messages
        $queue = new Queue('test');
        $message = new Message($queue, 'id', 'message');
        Queue::receiveMessage($message);

        $this->assertEquals('id_2', self::$heard);

        $queue = new Queue('test2');
        $message = new Message($queue, 'id', 'message');
        Queue::receiveMessage($message);

        $this->assertEquals('id', self::$heard);
    }
}
