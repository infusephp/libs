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
use Infuse\Queue\Driver\SynchronousDriver;

class SynchronousDriverTest extends PHPUnit_Framework_TestCase
{
    public static $heard;
    public static $driver;

    public static function setUpBeforeClass()
    {
        self::$driver = new SynchronousDriver();
    }

    public function testEnqueue()
    {
        $queue = new Queue('test_sync');

        Queue::listen('test_sync', function ($message) {
            self::$heard = $message->getId();
        });

        $message = self::$driver->enqueue($queue, 'test string!', []);

        $this->assertInstanceOf('Infuse\Queue\Message', $message);
        $this->assertEquals('test string!', $message->getBody());
        // should call the listener right after enqueue()
        $this->assertEquals($message->getId(), self::$heard);

        $message2 = self::$driver->enqueue($queue, 'test2', []);
        $this->assertInstanceOf('Infuse\Queue\Message', $message2);
        $this->assertEquals('test2', $message2->getBody());
        $this->assertNotEquals($message->getId(), $message2->getId());
    }

    /**
     * @depends testEnqueue
     */
    public function testDequeue()
    {
        $queue = new Queue('notfound');
        $messages = self::$driver->dequeue($queue, 2);
        $this->assertCount(0, $messages);

        $queue = new Queue('test_sync');
        $messages = self::$driver->dequeue($queue, 2);

        $this->assertCount(2, $messages);
        $message = $messages[0];
        $this->assertEquals('test string!', $message->getBody());

        $message2 = $messages[1];
        $this->assertEquals('test2', $message2->getBody());
        $this->assertNotEquals($message->getId(), $message2->getId());
    }

    /**
     * @depends testEnqueue
     */
    public function testDeleteMessage()
    {
        $queue = new Queue('test_sync2');

        $message = self::$driver->enqueue($queue, 'test', []);
        $this->assertCount(1, self::$driver->dequeue($queue, 1));

        $this->assertTrue(self::$driver->deleteMessage($message));
        $this->assertCount(0, self::$driver->dequeue($queue, 1));

        $this->assertFalse(self::$driver->deleteMessage($message));
    }
}
