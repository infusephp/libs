<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.14.8
 * @copyright 2013 Jared King
 * @license MIT
 */

namespace infuse;

class DatabaseSession
{
	private $tablename = 'Sessions';

	/**
	 * Starts the session using this handler
	 *
	 * @return DatabaseSession
	 */
	static function start()
	{
		$obj = new self();

		session_set_save_handler(
			array( $obj, 'open' ),
			array( $obj, 'close' ),
			array( $obj, 'read' ),
			array( $obj, 'write' ),
			array( $obj, 'destroy' ),
			array( $obj, 'gc' ) );

		session_start();

		return $obj;
	}

	/**
	 * Opens a session
	 *
	 * @return boolean success
	 */
	function open()
	{
		return true;
	}

	/**
	 * Closes a session
	 *
	 * @return boolean success
	 */
	function close()
	{
		return true;
	}

	/**
	 * Reads a session
	 *
	 * @param int $id session ID
	 *
	 * @return boolean success
	 */
	function read( $id )
	{
		return Database::select(
			$this->tablename,
			'session_data',
			array(
				'where' => array(
					'id' => $id
				),
				'single' => true
			),
			0
		);
	}

	/**
	 * Writes a session
	 *
	 * @param int $id session ID
	 * @param string $data session data
	 *
	 * @return boolean success
	 */
	function write( $id, $data )
	{
		Database::delete( 'Sessions', array( 'id' => $id ) );

		return Database::insert(
			$this->tablename,
			array(
				'id' => $id,
				'access' => time(),
				'session_data' => $data ) );
	}

	/**
	 * Destroys a session
	 *
	 * @param int $id session ID
	 *
	 * @return boolean success
	 */
	function destroy( $id )
	{
		return Database::delete( $this->tablename, array( 'id' => $id ) );
	}

	/**
	 * Performs garbage collection on sessions.
	 *
	 * @param int $max maximum number of seconds a session can live
	 *
	 * @return boolean success
	 */
	function gc( $max )
	{
		// delete sessions older than max TTL
		Database::delete( $this->tablename, array( 'access < ' . (time() - $max) ) );
		
		return true;
	}

	/**
	 * Installs schema for handling sessions in a database
	 *
	 * @return boolean success
	 */
	static function install()
	{
		return Database::sql( 'CREATE TABLE IF NOT EXISTS `Sessions` (`id` varchar(32) NOT NULL, PRIMARY KEY (`id`), `session_data` longtext NULL, `access` int(10) NULL);' );
	}
}

// the following prevents unexpected effects when using objects as save handlers
register_shutdown_function( 'session_write_close' );