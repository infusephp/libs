<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.18.1
 * @copyright 2013 Jared King
 * @license MIT
 */

use infuse\Database;

class DatabaseTest extends \PHPUnit_Framework_TestCase
{
	public function testTodo()
	{
		Database::configure( array() );
		
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
	}
}