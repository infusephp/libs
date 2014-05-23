<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.21
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse\Session;

class Database implements \SessionHandlerInterface
{
	private $tablename = 'Sessions';

	/**
	 * Installs schema for handling sessions in a database
	 *
	 * @return boolean success
	 */
	static function install()
	{
		return \infuse\Database::sql( 'CREATE TABLE IF NOT EXISTS `Sessions` (`id` varchar(32) NOT NULL, PRIMARY KEY (`id`), `session_data` longtext NULL, `access` int(10) NULL);' );
	}

	/**
	 * Starts the session using this handler
	 *
	 * @return DatabaseSession
	 */
	static function start()
	{
		$obj = new self();

		session_set_save_handler( $obj, true );

		session_start();

		return $obj;
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
			)
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
	 * These functions are all noops for various reasons...
	 * open() and close() have no practical meaning in terms of database connections
	 */
	function open( $path, $name ) { return true; }
	function close() { return true; }
}