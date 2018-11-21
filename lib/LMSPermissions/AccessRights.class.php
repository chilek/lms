<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

class AccessRights {
	const FIRST_FORBIDDEN_PERMISSION = 'backup_management_forbidden';
	private $permissions;
	static private $accessrights = null;

	public function __construct() {
		$this->permissions = array();
	}

	static public function getInstance() {
		if (empty(self::$accessrights))
			self::$accessrights = new AccessRights();
		return self::$accessrights;
	}

	public function appendPermission($permission) {
		$permname = $permission->getName();
		if (array_key_exists($permname, $this->permissions))
			throw new Exception(__METHOD__ . ': permission ' . $permname . ' already exists!');
		$this->permissions[$permname] = $permission;
	}

	public function insertPermission($permission, $existingpermname, $before = true) {
		$permname = $permission->getName();
		if (array_key_exists($permname, $this->permissions))
			throw new Exception(__METHOD__ . ': permission ' . $permname . ' already exists!');
		if (!array_key_exists($existingpermname, $this->permissions))
			throw new Exception(__METHOD__ . ': permission ' . $existingpermname . ' doesn\'t exist!');
		$first_permissions = array_splice($this->permissions, 0, array_search($existingpermname, array_keys($this->permissions)) + ($before ? 0 : 1));
		$this->permissions = array_merge($first_permissions, array($permname => $permission), $this->permissions);
	}

	public function getPermission($permname) {
		if (!array_key_exists($permname, $this->permissions))
			return null;
		$perm = $this->permissions[$permname];
		return $perm;
	}

	public function removePermission($permname) {
		if (isset($this->permissions[$permname])) {
			unset($this->permissions[$permname]);
			return true;
		} else
			return false;
	}

	public function checkRights($module, $rights, $global_allow = false) {
		$allow = $deny = false;
		foreach ($rights as $permname) {
			if (!array_key_exists($permname, $this->permissions))
				continue;
			if (!$global_allow && !$deny && $this->permissions[$permname]->checkPermission($module, $mode = Permission::REGEXP_DENY))
				$deny = true;
			elseif (!$allow && $this->permissions[$permname]->checkPermission($module, $mode = Permission::REGEXP_ALLOW))
				$allow = true;
		}
		return $global_allow || ($allow && !$deny);
	}

	public function checkPrivilege($privilege) {
		return array_key_exists($privilege, $this->permissions);
	}

	public function getArray($rights) {
		$access = array();
		foreach ($this->permissions as $permname => $permission)
			$access[$permname] = array(
				'name' => $permission->getLabel(),
				'enabled' => in_array($permname, $rights),
			);

		return $access;
	}
}

?>
