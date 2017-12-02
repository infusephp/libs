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

class MessageTest extends MockeryTestCase
{
    public function testGetQueue()
    {
        $queue = new Queue('test');
        $message = new Message($queue, '1', 'body');
        $this->assertEquals($queue, $message->getQueue());
    }

    public function testGetId()
    {
        $queue = new Queue('test');
        $message = new Message($queue, '1', 'body');
        $this->assertEquals('1', $message->getId());
    }

    public function testGetBody()
    {
        $queue = new Queue('test');
        $message = new Message($queue, '1', 'body');
        $this->assertEquals('body', $message->getBody());
    }

    public function testDelete()
    {
        $queue = new Queue('test');
        $message = new Message($queue, '1', 'body');

        $driver = Mockery::mock('Infuse\Queue\Driver\DriverInterface');
        $driver->shouldReceive('deleteMessage')
               ->withArgs([$message])
               ->andReturn(true)
               ->once();
        Queue::setDriver($driver);

        $this->assertTrue($message->delete());
    }
}
