<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.14.7
 * @copyright 2013 Jared King
 * @license MIT
 */

namespace infuse;

define( 'ACL_RESULT_NOT_CACHED', -1 );
define( 'ACL_NO_ID', -1 );

abstract class Acl
{
	///////////////////////////////
	// Private Class Variables
	///////////////////////////////
	
	/*
	ACL:
	[ users: {
		uid: permission },
	  groups: {
	  	id: permission } ]
	*/
	private $acl = array();
	private $aclLoaded = false;
	private $aclCache = array();
	protected $id = ACL_NO_ID;

	//////////////////////////////
	// GETTERS
	//////////////////////////////
	
	/**
	 * Gets the owner of the ACL
	 *
	 * @return object|false
	 */
	public function owner()
	{
		return false;
	}

	/**
	 * Checks if a requestor has permission to perform an action
	 *
	 * @param string $permission permission
	 * @param User $requestor requester
	 *
	 * @param boolean
	 */
	function can( $permission, $requestor = null )
	{
		if( $requestor === null )
			$requestor = \infuse\models\User::currentUser();
		
		// check cache
		$cache = $this->cachedResult( $permission, $requestor );
		if( $cache !== ACL_RESULT_NOT_CACHED )
			return $cache;
		
		// check if owner - owner's always have permission
		$owner = $this->owner();
		if( $owner instanceof $requestor && $owner->id() == $requestor->id() )
			return $this->cacheResult( $permission, $requestor, true );
		
		// load ACL from database for model
		$this->loadACL();
		
		// check requester permissions
		if( Util::array_value( $this->acl, 'users.' . $requestor->id() . '.' . $permission ) )
			return $this->cacheResult( $permission, $requestor, true );

		// check requester's group permissions in relation to owner
		foreach( $requestor->groups( $owner ) as $group )
		{
			// admins always get permission
			if( $group->id() == ADMIN )
				return $this->cacheResult( $permission, $requestor, true );
			
			if( Util::array_value( $this->acl, 'groups.' . $group->id() . '.' . $permission ) )
				return $this->cacheResult( $permission, $requestor, true );
		}
		
		return $this->cacheResult( $permission, $requestor, false );
	}
		
	/**
	 * Gets all ACL values for the model
	 *
	 * @return void
	 */
	function loadACL()
	{
		if( $this->aclLoaded )
			return;
		
		$this->acl = array(
			'users' => array(),
			'groups' => array() );
		
		$this->aclCache = array();
		
		$where = array( 'model' => get_class( $this ) );
		
		// are we talking about a specific model or any model?
		if( $this->id !== ACL_NO_ID )
			$where[ 'model_id' ] = $this->id;
		
		// setup objects ACL
		$acl_db = (array)Database::select(
			'Permissions',
			'uid,gid,permission',
			array(
				'where' => $where ) );

		foreach( $acl_db as $acl )
		{
			if( !empty( $acl[ 'uid' ] ) )
				Util::array_set( $this->acl, "users.{$acl['uid']}.{$acl['permission']}", true );
			
			if( !empty( $acl[ 'gid' ] ) )
				Util::array_set( $this->acl, "groups.{$acl['gid']}.{$acl['permission']}", true );
		}

		$this->aclLoaded = true;
	}

	/**
	 * Sets up the ACL in the database
	 *
	 * @return boolean success
	 */
	static function install()
	{
		return Database::sql( 'CREATE TABLE `Permissions` (`id` int(11) NOT NULL auto_increment, PRIMARY KEY (`id`), `model` varchar(255) NOT NULL, `model_id` int(11) NULL, `uid` int(11) NOT NULL, `gid` int(11) NOT NULL, `permission` varchar(255) NOT NULL);' );
	}
	
	/////////////////////////////////
	// PRIVATE FUNCTIONS
	/////////////////////////////////

	private function cachedResult( $permission, $requestor )
	{
		$key = strtolower( str_replace( '\\', '', get_class( $requestor ) ) ) . '.' . $requestor->id();
		
		if( $value = Util::array_value( $this->aclCache, "$key.$permission" ) )
			return $value;

		return ACL_RESULT_NOT_CACHED;
	}
	
	private function cacheResult( $permission, $requestor, $result )
	{
		$key = strtolower( str_replace( '\\', '', get_class( $requestor ) ) ) . '.' . $requestor->id();
		
		Util::array_set( $this->aclCache, "$key.$permission", $result );
	
		return $result;
	}
}