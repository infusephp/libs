<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.21.1
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse;

use Pimple\Container;

define( 'QUEUE_TYPE_IRON', 'iron' );
define( 'QUEUE_TYPE_SYNCHRONOUS', 'synchronous' );

class Queue
{
	private static $config = [
		'queues' => [],
		'namespace' => '',
		'container' => null
	];

	// used for synchronous mode
	private static $queues = [];
	private static $idCounter = 1;

	// used for iron.io
	private static $iron;

	private static $queueTypes = [
		QUEUE_TYPE_IRON,
		QUEUE_TYPE_SYNCHRONOUS
	];

	private $type;
	private $listeners;

	/**
	 * Changes the queue settings
	 *
	 * @param array $config
	 */
	static function configure( $config )
	{
		self::$config = array_replace( self::$config, (array)$config );
	}

	function __construct( $type, array $listeners = [] )
	{
		if( !in_array( $type, self::$queueTypes ) )
			$type = QUEUE_TYPE_SYNCHRONOUS;

		$this->type = $type;
		$this->listeners = $listeners;
	}

	/**
	 * Returns the type of the queue
	 *
	 * @return string synchronous|iron
	 */
	function type()
	{
		return $this->type;
	}

	/**
	 * Sets up the queue(s) according to the configuration. Usually only needs to be 
	 * called when the configuration changes, and certainly not on every request
	 *
	 * @param boolean $echoOutput
	 *
	 * @return boolean success
	 */
	static function install( $echoOutput = false )
	{
		if( $this->type == QUEUE_TYPE_IRON )
		{
			$ironmq = self::iron();

			// setup push queues
			if( isset( self::$config[ 'queues' ] ) && isset( self::$config[ 'push_subscribers' ] ) )
			{
				$authToken = Util::array_value( self::$config, 'auth_token' );

		        foreach( self::$config[ 'queues' ] as $q )
		        {
		        	// setup each push subscriber url with an auth token (if used)
		        	$subscribers = [];
		            foreach( (array)Util::array_value( self::$config, 'push_subscribers' ) as $s )
		            {
		            	$url = $s . "?q=$q";

		            	if( !empty( $authToken ) )
		            		$url .= "&auth_token=$authToken";

						$subscribers[] = [ 'url' => $url ];
		            }

		            $ironmq->updateQueue( $q, [
						'push_type' => 'unicast',
						'subscribers' => $subscribers
		            ] );

		            if( $echoOutput )
		            {
		            	echo "Installed $q with subscribers:\n";
		            	print_r( $subscribers );
		            }
		        }
			}
		}
	}

	/**
	 * Puts a message onto the queue
	 *
	 * @param string $queue queue name
	 * @param mixed $message
	 * @param array $parameters
	 *
	 * @return boolean success
	 */
	function enqueue( $queue, $message, $parameters = [] )
	{
		if( $this->type == QUEUE_TYPE_IRON )
		{
			$ironmq = self::iron();

			// serialize arrays and objects stored in queue
			if( is_array( $message ) || is_object( $message ) )
				$message = json_encode( $message );
			
			return $ironmq->postMessage( $queue, $message, $parameters );
		}
		else if( $this->type == QUEUE_TYPE_SYNCHRONOUS )
		{
			if( !isset( self::$queues[ $queue ] ) )
				self::$queues[ $queue ] = [];

			// wrap the message inside of an object
			$messageWrapper = new \stdClass;

			$messageWrapper->id = self::$idCounter;
			$messageWrapper->body = $message;

			self::$idCounter++;

			// add the serialized message wrapper to the queue
			$json = json_encode( $messageWrapper );
			self::$queues[ $queue ][] = $json;

			// since this is synchronous mode, notify all listeners that we have a new message
			$this->receiveMessage( $queue, $json, self::$config[ 'container' ] );

			return true;
		}

		return false;
	}

	/**
	 * Takes one or messages off the queue
	 * WARNING remember to delete the message when finished
	 *
	 * @param string $queue queue name
	 * @param int $n number of messages to dequeue
	 *
	 * @return array($n > 1)|object($n = 1)|null message(s)
	 */
	function dequeue( $queue, $n = 1 )
	{
		$messages = [];

		if( $this->type == QUEUE_TYPE_IRON )
		{
			$ironmq = self::iron();

			$messages = $ironmq->getMessages( $queue, $n );
		}
		else if( $this->type = QUEUE_TYPE_SYNCHRONOUS )
		{
			if( isset( self::$queues[ $queue ] ) )
			{
				$messages = array_slice( self::$queues[ $queue ], 0, $n );

				foreach( $messages as $k => $m )
					$messages[ $k ] = json_decode( $m );
			}
		}

		if( count( $messages ) > 0 && $n == 1 )
			return reset( $messages );
		else if( $n > 1 )
			return $messages;
		else
			return null;
	}

	/**
	 * Removes a message from the queue. This should be called once
	 * done with a message pulled off the queue.
	 *
	 * @param string $queue queue name
	 * @param object $message
	 *
	 * @return boolean
	 */
	function deleteMessage( $queue, $message )
	{
		if( !$message->id )
			return true;
		
		if( $this->type == QUEUE_TYPE_IRON )
		{
			$ironmq = self::iron();

			return $ironmq->deleteMessage( $queue, $message->id );
		}
		else if( $this->type == QUEUE_TYPE_SYNCHRONOUS )
		{
			if( !isset( self::$queues[ $queue ] ) )
				return true;

			// find the message with the specified id, and delete it
			foreach( (array)self::$queues[ $queue ] as $k => $str )
			{
				$m = json_decode( $str );

				if( $m->id == $message->id )
				{
					unset( self::$queues[ $queue ][ $k ] );
					self::$queues[ $queue ] = array_values( self::$queues[ $queue ] );
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Notifies all listeners that a message has been received from the queue
	 *
	 * @param string $queue queue name
	 * @param string $message message
	 * @param Container $container optional DI container
	 */
	function receiveMessage( $queue, $message, Container $container = null )
	{
		$success = true;

		if( is_string( $message ) )
			$message = json_decode( $message );

		$listeners = (array)Util::array_value( $this->listeners, $queue );

		// notify all listeners that we have a new message
		foreach( $listeners as $route )
		{
			list( $controller, $method ) = $route;

			$controller = self::$config[ 'namespace' ] . '\\' . $controller;
			
			if( !class_exists( $controller ) )
				continue;

			$controllerObj = new $controller( $container );

			$controllerObj->$method( $this, $message );
		}
	}

	//////////////////////////
	// QUEUE PROVIDERS
	//////////////////////////

	private static function iron()
	{
		if( !self::$iron )
			self::$iron = new \IronMQ( [
				'token' => Util::array_value( self::$config, 'token' ),
				'project_id' => Util::array_value( self::$config, 'project' ) ] );

		return self::$iron;
	}
}