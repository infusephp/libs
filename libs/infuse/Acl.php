<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.20
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse;

define( 'INFUSE_ACL_NOT_CACHED', -1 );

abstract class Acl
{
	///////////////////////////////
	// Private Class Variables
	///////////////////////////////
	
	/*
	ACL:
	[ individuals: {
		uid: permission },
	  groups: {
	  	id: permission } ]
	*/
	private $acl = array();
	private $aclLoaded = false;
	private $aclCache = array();
	
	/**
	 * Gets the owner of the ACL
	 *
	 * @return Model|false
	 */
	function owner()
	{
		return false;
	}

	/**
	 * Checks if a requester has permission to perform an action
	 *
	 * @param string $permission permission
	 * @param AclRequester $requester requester
	 *
	 * @param boolean
	 */
	function can( $permission, AclRequester $requester )
	{
		// check cache
		$cache = $this->cachedResult( $permission, $requester );
		if( $cache !== INFUSE_ACL_NOT_CACHED )
			return $cache;
		
		// check if owner - owner's always have permission
		$owner = $this->owner();
		if( $owner instanceof $requester && $owner->id() == $requester->id() )
			return $this->cacheResult( $permission, $requester, true );
		
		// load ACL from database for model
		$this->loadACL();
		
		// check requester permissions
		if( Util::array_value( $this->acl, 'individuals.' . $requester->id() . '.' . $permission ) )
			return $this->cacheResult( $permission, $requester, true );

		// check requester's group permissions in relation to owner
		foreach( $requester->groups( $owner ) as $group )
		{
			// admins always get permission
			if( $group->id() == ADMIN )
				return $this->cacheResult( $permission, $requester, true );
			
			if( Util::array_value( $this->acl, 'groups.' . $group->id() . '.' . $permission ) )
				return $this->cacheResult( $permission, $requester, true );
		}
		
		return $this->cacheResult( $permission, $requester, false );
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
			'individuals' => array(),
			'groups' => array() );
		
		$this->aclCache = array();
		
		$where = array( 'model' => get_class( $this ) );
		
		// are we talking about a specific model or any model?
		if( $this->id !== false )
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
				Util::array_set( $this->acl, "individuals.{$acl['uid']}.{$acl['permission']}", true );
			
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
		return Database::sql( 'CREATE TABLE IF NOT EXISTS `Permissions` (`id` int(11) NOT NULL auto_increment, PRIMARY KEY (`id`), `model` varchar(255) NOT NULL, `model_id` int(11) NULL, `uid` int(11) NOT NULL, `gid` int(11) NOT NULL, `permission` varchar(255) NOT NULL);' );
	}
	
	/////////////////////////////////
	// PRIVATE FUNCTIONS
	/////////////////////////////////

	private function cachedResult( $permission, $requester )
	{
		$key = strtolower( str_replace( '\\', '', get_class( $requester ) ) ) . '.' . $requester->id();
		
		if( $value = Util::array_value( $this->aclCache, "$key.$permission" ) )
			return $value;

		return INFUSE_ACL_NOT_CACHED;
	}
	
	private function cacheResult( $permission, $requester, $result )
	{
		$key = strtolower( str_replace( '\\', '', get_class( $requester ) ) ) . '.' . $requester->id();
		
		Util::array_set( $this->aclCache, "$key.$permission", $result );
	
		return $result;
	}
}