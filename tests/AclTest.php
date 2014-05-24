<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.21.1
 * @copyright 2014 Jared King
 * @license MIT
 */

use infuse\Acl;
use infuse\Model;

class AclTest extends \PHPUnit_Framework_TestCase
{
	function testCan()
	{
		$acl = new AclObject();

		$this->assertFalse( $acl->can( 'whatever', new SomeModel ) );
		$this->assertTrue( $acl->can( 'do nothing', new SomeModel(5) ) );
		$this->assertFalse( $acl->can( 'do nothing', new SomeModel ) );
	}

	function testCache()
	{
		$acl = new AclObject();

		for( $i = 0; $i < 10; $i++ )
			$this->assertFalse( $acl->can( 'whatever', new SomeModel ) );
	}

	function testGrantAll()
	{
		$acl = new AclObject;

		$acl->grantAllPermissions();

		$this->assertTrue( $acl->can( 'whatever', new SomeModel ) );
	}

	function testEnforcePermissions()
	{
		$acl = new AclObject;

		$acl->grantAllPermissions();
		$acl->enforcePermissions();

		$this->assertFalse( $acl->can( 'whatever', new SomeModel ) );
	}
}

class AclObject extends Acl
{
	var $first = true;

	protected function hasPermission( $permission, Model $requester )
	{
		if( $permission == 'whatever' )
		{
			// always say no the first time
			if( $this->first )
			{
				$this->first = false;
				return false;
			}

			return true;
		}
		else if( $permission == 'do nothing' )
			return $requester->id() == 5;
	}
}

class SomeModel extends Model
{
	protected function hasPermission( $permission, Model $requester )
	{
		return false;
	}
}