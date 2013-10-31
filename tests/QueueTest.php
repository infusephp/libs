<?php

use \infuse\Queue;

require_once 'vendor/autoload.php';

class QueueTest extends \PHPUnit_Framework_TestCase
{
	var $test1Message;
	var $test2Message;

	public function testConfigure()
	{
		Queue::configure( array(
			'type' => QUEUE_TYPE_IRON,

		) );

		$this->assertEquals( Queue::type(), QUEUE_TYPE_IRON );

		Queue::configure( array(
			'type' => QUEUE_TYPE_SYNCHRONOUS
		) );

		$this->assertEquals( Queue::type(), QUEUE_TYPE_SYNCHRONOUS );
	}

	/**
	 * @depends testConfigure
	 */
	public function testEnqueue( $notused )
	{
		$this->assertTrue( Queue::enqueue( 'test1', 'test string!' ) );

		$this->assertTrue( Queue::enqueue( 'test2', array( 'does' => 'this', 'thing' => 'work?' ) ) );

		$obj = new stdClass;
		$obj->name = 'test';
		$obj->answer = 42;
		$this->assertTrue( Queue::enqueue( 'test1', $obj ) );
	}

	/**
	 * @depends testEnqueue
	 */
	public function testDequeue( $notused )
	{
		$test1 = Queue::dequeue( 'test1' );

		$this->assertInstanceOf( 'stdClass', $test1 );
		$this->assertEquals( $test1->id, 1 );
		$this->assertEquals( $test1->body, 'test string!' );

		$messages = Queue::dequeue( 'test1', 2 );

		$this->assertEquals( count( $messages ), 2 );
		$this->assertEquals( $messages[ 0 ]->id, 1 );
		$this->assertEquals( $messages[ 0 ]->body, 'test string!' );
		$this->assertEquals( $messages[ 1 ]->id, 3 );
		$expected = new stdClass;
		$expected->name = 'test';
		$expected->answer = 42;
		$this->assertEquals( $messages[ 1 ]->body, $expected );

		$test2 = Queue::dequeue( 'test2' );
		$this->assertEquals( $test2->id, 2 );
		$expected = new stdClass;
		$expected->does = 'this';
		$expected->thing = 'work?';
		$this->assertEquals( $test2->body, $expected );		
	}

	/**
	 * @depends testDequeue
	 */
	public function testDeleteMessage( $notused )
	{
		$test1 = Queue::dequeue( 'test1' );

		$this->assertTrue( Queue::deleteMessage( 'test1', $test1 ) );

		$test1 = Queue::dequeue( 'test1' );
		$this->assertEquals( $test1->id, 3 );
		$expected = new stdClass;
		$expected->name = 'test';
		$expected->answer = 42;
		$this->assertEquals( $test1->body, $expected );
	}

	/**
	 * @depends testDeleteMessage
	 */
	public function testReceiveMessage()
	{
		Queue::configure( array(
			'type' => QUEUE_TYPE_SYNCHRONOUS,
			'queues' => array(
				'test1' => array(
					'listeners' => array(
						array( $this, 'receiveMessageListener1' ) ) ),
				'test2' => array(
					'listeners' => array(
						array( $this, 'receiveMessageListener2' ) ) ) ) ) );

		Queue::enqueue( 'test1', 'test' );
		$this->assertInstanceOf( 'stdClass', $this->test1Message );
		$this->assertTrue( $this->test1Message->id > 0 );
		$this->assertEquals( $this->test1Message->body, 'test' );		

		Queue::enqueue( 'test2', 1234 );
		$this->assertInstanceOf( 'stdClass', $this->test2Message );
		$this->assertTrue( $this->test2Message->id > 0 );
		$this->assertEquals( $this->test2Message->body, 1234 );
	}

	public function receiveMessageListener1( $message )
	{
		$this->test1Message = $message;

		Queue::deleteMessage( 'test1', $message );
	}

	public function receiveMessageListener2( $message )
	{
		$this->test2Message = $message;

		Queue::deleteMessage( 'test2', $message );
	}
}