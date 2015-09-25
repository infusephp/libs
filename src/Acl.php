<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace infuse;

abstract class Acl
{
    /**
     * @staticvar Model|false
     */
    protected static $requester;

    /**
     * @var array
     */
    private $permissionsCache = [];

    /**
     * @var bool
     */
    private $permissionsDisabled = false;

    /**
     * Sets the requester.
     *
     * @param Model $requester
     */
    public static function setRequester(Model $requester)
    {
        static::$requester = $requester;
    }

    /**
     * Gets the requester.
     *
     * @return Model|false
     */
    public static function getRequester()
    {
        return static::$requester;
    }

    /**
     * Checks if the requesting model has a specific permission
     * on this object.
     *
     * @param string $permission
     * @param Model  $requester
     *
     * @return bool
     */
    public function can($permission, Model $requester)
    {
        if ($this->permissionsDisabled) {
            return true;
        }

        $perm = false;

        // cache when checking permissions
        $k = $permission.'.'.$requester;
        if (!isset($this->permissionsCache[$k])) {
            $perm = $this->hasPermission($permission, $requester);
            $this->permissionsCache[$k] = $perm;
        } else {
            $perm = $this->permissionsCache[$k];
        }

        return $perm;
    }

    /**
     * Disables all permissions checking in can() for this object
     * DANGER: this should only be used when objects are mutated from application code
     * Granting all permissions to anyone else, i.e. HTTP requests is dangerous.
     *
     * @return self
     */
    public function grantAllPermissions()
    {
        $this->permissionsDisabled = true;

        return $this;
    }

    /**
     * Ensures that permissions are enforced for this object.
     *
     * @return self
     */
    public function enforcePermissions()
    {
        $this->permissionsDisabled = false;

        return $this;
    }

    abstract protected function hasPermission($permission, Model $requester);
}
