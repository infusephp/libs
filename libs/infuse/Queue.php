<?php

namespace infuse;

define( 'QUEUE_TYPE_IRON', 'iron' );
define( 'QUEUE_TYPE_SYNCHRONOUS', 'synchronous' );

class Queue
{
	private static $config = array(
		'type' => QUEUE_TYPE_SYNCHRONOUS,
		'queues' => array(),
		'use_modules' => false
	);

	// used for synchronous mode
	private static $queues = array();
	private static $idCounter = 1;

	// used for iron.io
	private static $iron;

	/**
	 * Changes the queue settings
	 *
	 * @param array $config
	 */
	static function configure( $config )
	{
		self::$config = array_replace( self::$config, (array)$config );
	}

	/**
	 * Returns the type of the queue
	 *
	 * @return string synchronous|iron
	 */
	static function type()
	{
		$type = Util::array_value( self::$config, 'type' );

		if( in_array( $type, array( QUEUE_TYPE_IRON, QUEUE_TYPE_SYNCHRONOUS ) ) )
			return $type;
		
		return QUEUE_TYPE_SYNCHRONOUS;
	}

	/**
	 * Sets up the queue according to the configuration. Usually only needs to be 
	 * called when the configuration changes, and certainly not on every request
	 *
	 * @param boolean $echoOutput
	 *
	 * @return boolean success
	 */
	static function install( $echoOutput = false )
	{
		$type = self::type();

		if( $type == QUEUE_TYPE_IRON )
		{
			$ironmq = self::iron();

			$authToken = Util::array_value( self::$config, 'auth_token' );

	        foreach( self::$config[ 'queues' ] as $q => $settings )
	        {
	        	// setup each push subscriber url with an auth token (if used)
	        	$subscribers = array();
	            foreach( Util::array_value( $settings, 'push_subscribers' ) as $s )
	            {
	            	$url = $s . "?q=$q";

	            	if( !empty( $authToken ) )
	            		$url .= "&auth_token=$authToken";

					$subscribers[] = array( 'url' => $url );
	            }

	            $ironmq->updateQueue( $q, array(
					'push_type' => 'unicast',
					'subscribers' => $subscribers
	            ) );

	            if( $echoOutput )
	            {
	            	echo "Installed $q with subscribers:\n";
	            	print_r( $subscribers );
	            }
	        }
		}
	}

	/**
	 * Puts a message onto the queue
	 *
	 * @param string $queue queue name
	 * @param array|object|string $message
	 * @param array $parameters
	 *
	 * @return boolean success
	 */
	static function enqueue( $queue, $message, $parameters = array() )
	{
		$type = self::type();

		if( $type == QUEUE_TYPE_IRON )
		{
			$ironmq = self::iron();

			// serialize arrays and objects stored in queue
			if( is_array( $message ) || is_object( $message ) )
				$message = json_encode( $message );
			
			return $ironmq->postMessage( $queue, $message, $parameters );
		}
		else if( $type == QUEUE_TYPE_SYNCHRONOUS )
		{
			if( !isset( self::$queues[ $queue ] ) )
				self::$queues[ $queue ] = array();

			// wrap the message inside of an object
			$messageWrapper = new \stdClass;

			$messageWrapper->id = self::$idCounter;
			$messageWrapper->body = $message;

			self::$idCounter++;

			// add the serialized message wrapper to the queue
			$json = json_encode( $messageWrapper );
			self::$queues[ $queue ][] = $json;

			// since this is synchronous mode, notify all listeners that we have a new message
			self::receiveMessage( $queue, $json );

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
	static function dequeue( $queue, $n = 1 )
	{
		$type = self::type();

		$messages = array();

		if( $type == QUEUE_TYPE_IRON )
		{
			$ironmq = self::iron();

			$messages = $ironmq->getMessages( $queue, $n );
		}
		else if( $type = QUEUE_TYPE_SYNCHRONOUS )
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
	static function deleteMessage( $queue, $message )
	{
		$type = self::type();

		if( $type == QUEUE_TYPE_IRON )
		{
			$ironmq = self::iron();

			return $ironmq->deleteMessage( $queue, $message->id );
		}
		else if( $type == QUEUE_TYPE_SYNCHRONOUS )
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
	 */
	static function receiveMessage( $queue, $message )
	{
		$success = true;

		if( isset( self::$config[ 'listeners' ] ) )
		{
			if( is_string( $message ) )
				$message = json_decode( $message );

			$listeners = (array)Util::array_value( self::$config[ 'listeners' ], $queue );

			// notify all listeners that we have a new message
			foreach( $listeners as $function )
			{
				if( self::$config[ 'use_modules' ] )
				{
					list( $controller, $action ) = $function;

					Modules::controller( $controller )->$action( $message );
				}
				else
					call_user_func( $function, $message );
			}
		}
	}

	//////////////////////////
	// QUEUE PROVIDERS
	//////////////////////////

	private static function iron()
	{
		if( !self::$iron )
			self::$iron = new \IronMQ( array(
				'token' => Util::array_value( self::$config, 'token' ),
				'project_id' => Util::array_value( self::$config, 'project' ) ) );

		return self::$iron;
	}
}