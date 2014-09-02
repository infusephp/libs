<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.25
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse\Session;

use infuse\Database as Db;

class Database implements \SessionHandlerInterface
{
	private static $tablename = 'Sessions';

	/**
	 * Installs schema for handling sessions in a database
	 *
	 * @return boolean success
	 */
	public static function install()
	{
		return Db::sql( 'CREATE TABLE IF NOT EXISTS `' . self::$tablename . '` (`id` varchar(32) NOT NULL, PRIMARY KEY (`id`), `session_data` longtext NULL, `access` int(10) NULL);' );
	}

	/**
	 * Starts the session using this handler
	 *
	 * @return DatabaseSession
	 */
	public static function start()
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
	public function read($id)
	{
		return Db::select(
			self::$tablename,
			'session_data',
			[
				'where' => [
					'id' => $id
				],
				'single' => true
			]
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
	public function write($id, $data)
	{
		Db::delete( 'Sessions', [ 'id' => $id ] );

		return Db::insert(
			self::$tablename,
			[
				'id' => $id,
				'access' => time(),
				'session_data' => $data ] );
	}

	/**
	 * Destroys a session
	 *
	 * @param int $id session ID
	 *
	 * @return boolean success
	 */
	public function destroy($id)
	{
		return Db::delete( self::$tablename, [ 'id' => $id ] );
	}

	/**
	 * Performs garbage collection on sessions.
	 *
	 * @param int $max maximum number of seconds a session can live
	 *
	 * @return boolean success
	 */
	public function gc($max)
	{
		// delete sessions older than max TTL
        Db::delete( self::$tablename, [ 'access < ' . (time() - $max) ] );

		return true;
	}

	/**
	 * These functions are all noops for various reasons...
	 * open() and close() have no practical meaning in terms of database connections
	 */
	public function open($path, $name) { return true; }
	public function close() { return true; }
}
