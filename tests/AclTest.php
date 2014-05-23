<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.20
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\Acl;

class AclTest extends \PHPUnit_Framework_TestCase
{
	public function testTodo()
	{
		$acl = new AclObject();
		
        $this->markTestIncomplete( 'This test has not been implemented yet.' );
	}
}

class AclObject extends Acl
{

}