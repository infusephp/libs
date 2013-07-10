<?php
/*
 * @package Infuse
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 1.0
 * @copyright 2013 Jared King
 * @license MIT
	Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
	associated documentation files (the "Software"), to deal in the Software without restriction,
	including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
	and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so,
	subject to the following conditions:
	
	The above copyright notice and this permission notice shall be included in all copies or
	substantial portions of the Software.
	
	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT
	LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
	IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
	WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
	SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
 
namespace infuse;
  
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
		if( isset( $this->acl[ 'users' ][ $requestor->id() ] ) &&
			isset( $this->acl[ 'users' ][ $requestor->id() ][ $permission ] ) )
			return $this->cacheResult( $permission, $requestor, true );

		// check requester's group permissions in relation to owner
		foreach( $requestor->groups( $owner ) as $group )
		{
			// admins always get permission
			if( $group->id() == ADMIN )
				return $this->cacheResult( $permission, $requestor, true );
			
			if( isset( $this->acl[ 'groups' ][ $group->id() ] ) &&
				isset( $this->acl[ 'groups' ][ $group->id() ][ $permission ] ) )
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
				$this->acl[ 'users' ][ $acl[ 'uid' ] ][ $acl[ 'permission' ] ] = true;
			
			if( !empty( $acl[ 'gid' ] ) )
				$this->acl[ 'groups' ][ $acl[ 'gid' ] ][ $acl[ 'permission' ] ] = true;
		}

		$this->aclLoaded = true;
	}
	
	/////////////////////////////////
	// PRIVATE FUNCTIONS
	/////////////////////////////////

	private function cachedResult( $permission, $requestor )
	{
		$key = get_class( $requestor ) . '-' . $requestor->id();
		
		if( !isset( $this->aclCache[ $key ] ) )
			$this->aclCache[ $key ] = array();
		
		if( isset( $this->aclCache[ $key ][ $permission ] ) )
			return $this->aclCache[ $key ][ $permission ];

		return ACL_RESULT_NOT_CACHED;
	}
	
	private function cacheResult( $permission, $requestor, $result )
	{
		$key = get_class( $requestor ) . '-' . $requestor->id();
		
		if( !isset( $this->aclCache[ $key ] ) )
			$this->aclCache[ $key ] = array();
		
		$this->aclCache[ $key ][ $permission ] = $result;
	
		return $result;
	}
}