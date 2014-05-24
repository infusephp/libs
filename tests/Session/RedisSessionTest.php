<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.21.1
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\Session\Redis as RedisSession;

class RedisSessionTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$session = new RedisSession( [], 'infuse' );
	}
}