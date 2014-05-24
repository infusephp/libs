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

abstract class Acl
{
	private $permissionsCache = [];
	private $permissionsDisabled = false;

	abstract protected function hasPermission( $permission, Model $requester );

	function can( $permission, Model $requester )
	{
		if( $this->permissionsDisabled )
			return true;

		// cache when checking permissions
		$k = $permission . '.' . get_class( $requester ) . '.' . $requester->id();
		$permission = false;
		if( !isset( $this->permissionsCache[ $k ] ) )
		{
			$permission = $this->hasPermission( $permission, $requester );
			$this->permissionsCache[ $k ] = $permission;
		}
		else
			$permission = $this->permissionsCache[ $k ];

		return $permission;
	}

	/**
	 * Disables all permissions checking in can() for this object
	 * DANGER: this should only be used when objects are mutated from application code
	 * Granting all permissions to anyone else, i.e. HTTP requests is dangerous
	 */
	function grantAllPermissions()
	{
		$this->permissionsDisabled = true;
	}

	/**
	 * Ensures that permissions are enforced for this object
	 */
	function enforcePermissions()
	{
		$this->permissionsDisabled = false;
	}
}