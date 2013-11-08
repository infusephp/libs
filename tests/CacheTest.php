<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.16.3
 * @copyright 2013 Jared King
 * @license MIT
 */

error_reporting( E_ALL | E_STRICT );
ini_set( 'display_errors', true );

require_once 'vendor/autoload.php';

use infuse\Cache;

class CacheTest extends \PHPUnit_Framework_TestCase
{
	public function testTodo()
	{
		$cache = new Cache( array( 'local' ) );
		
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
	}
}