<?php

class PermissionManager {
	/**
	 * Initialises a PermissionManager instance with the given permissions
	 * 
	 * @param mixed[] $permissions The array of permission names
	 */
	function __construct($permissions) {
		$this->permissions = [];
        if(count($permissions) > 0)
            foreach($permissions as $i => $x) {
                $this->permissions[1 << $i] = $x;
            }
	}

	/**
	 * Finds a permission by its name
	 * 
	 * @param mixed $permission The name of the permission
	 * @return int
	 */
	function findPermission($permission) {
        return array_search($permission, $this->permissions, true);
	}

	/**
	 * Checks if a permission exists in a permission hash
	 * 
	 * @param int $hash The hash (default: 0)
	 * @param mixed $permission Name of the permission
	 * @return boolean
	 */
	function hasPermission($hash, $permission) {
		$perm = $this->findPermission($permission);
		if ($perm === false) return false;
		return ((int)$hash & (int)$perm) > 0;
	}

	/**
	 * Adds a permission to the given hash
	 * 
	 * @param int $hash The hash (default: 0)
	 * @param mixed $permission Name of the permission
	 * @return int
	 */
	function addPermission($hash, $permission) {
		$perm = $this->findPermission($permission);
		if ($perm === false) return $hash;
		return (int)$hash | (int)$perm;
	}

	/**
	 * Adds multiple permissions to the given hash
	 * 
	 * @param int $hash The hash (default: 0)
	 * @param mixed[] $permissions Array of permission names
	 * @return int
	 */
	function addPermissions($hash, $permissions) {
        if(count($permissions) > 0)
            foreach($permissions as $x) {
                $hash = $this->addPermission($hash, $x);
            }

        return $hash;
	}
 
	/**
	 * Deletes a permission from a hash
	 * 
	 * @param int $hash The hash (default: 0)
	 * @param mixed[] $permissions Name of the permission
	 * @return int
	 */
	function revokePermission($hash, $permission) {
		$perm = $this->findPermission($permission);
		if ($perm === false) return $hash;
		return $this->hasPermission($hash, $permission) ? (int)$hash ^ (int)$perm : (int)$hash;
	}

	/**
	 * Deletes multiple permissions from a hash
	 * 
	 * @param int @hash The hash (default: 0)
	 * @param mixed[] $permissions Name of the permission
	 * @return int
	 */
	function revokePermissions($hash, $permissions) {
        if(count($permissions) > 0)
            foreach($permissions as $x) {
                $hash = $this->revokePermission($hash, $x);
            }

        return $hash;
	}

	/**
	 * Returns an array of permission names that can be found in the given hash
	 * 
	 * @param int $hash The hash (default: 0)
	 * @return mixed[]
	 */
	function getPermissions($hash) {
        return array_values(array_filter($this->permissions, function($x) use ($hash) {
            return ((int)$hash & (int)$x) > 0;
        }, ARRAY_FILTER_USE_KEY));
	}
}

?>