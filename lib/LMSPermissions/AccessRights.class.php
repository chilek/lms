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

class AccessRights
{
    const FIRST_FORBIDDEN_PERMISSION = 'backup_management_forbidden';
    private $permissions;
    private static $accessrights = null;

    public function __construct()
    {
        $this->permissions = array();
    }

    public static function getInstance()
    {
        if (empty(self::$accessrights)) {
            self::$accessrights = new AccessRights();
        }
        return self::$accessrights;
    }

    public function appendPermission($permission)
    {
        $permname = $permission->getName();
        if (array_key_exists($permname, $this->permissions)) {
            throw new Exception(__METHOD__ . ': permission ' . $permname . ' already exists!');
        }
        $this->permissions[$permname] = $permission;
    }

    public function insertPermission($permission, $existingpermname, $before = true)
    {
        $permname = $permission->getName();
        if (array_key_exists($permname, $this->permissions)) {
            throw new Exception(__METHOD__ . ': permission ' . $permname . ' already exists!');
        }
        if (!array_key_exists($existingpermname, $this->permissions)) {
            throw new Exception(__METHOD__ . ': permission ' . $existingpermname . ' doesn\'t exist!');
        }
        $first_permissions = array_splice($this->permissions, 0, array_search($existingpermname, array_keys($this->permissions)) + ($before ? 0 : 1));
        $this->permissions = array_merge($first_permissions, array($permname => $permission), $this->permissions);
    }

    public function getPermission($permname)
    {
        if (!array_key_exists($permname, $this->permissions)) {
            return null;
        }
        $perm = $this->permissions[$permname];
        return $perm;
    }

    public function removePermission($permname)
    {
        if (isset($this->permissions[$permname])) {
            unset($this->permissions[$permname]);
            return true;
        } else {
            return false;
        }
    }

    public function checkRights($module, $rights, $global_allow = false)
    {
        $allow = $deny = false;
        foreach ($rights as $permname) {
            if (!array_key_exists($permname, $this->permissions)) {
                continue;
            }
            if (!$global_allow && !$deny && $this->permissions[$permname]->checkPermission($module, $mode = Permission::REGEXP_DENY)) {
                $deny = true;
            } elseif (!$allow && $this->permissions[$permname]->checkPermission($module, $mode = Permission::REGEXP_ALLOW)) {
                $allow = true;
            }
        }
        return $global_allow || ($allow && !$deny);
    }

    public function checkPrivilege($privilege)
    {
        return array_key_exists($privilege, $this->permissions);
    }

    public function getArray($rights)
    {
        $access = array();
        foreach ($this->permissions as $permname => $permission) {
            $access[$permname] = array(
                'name' => $permission->getLabel(),
                'enabled' => in_array($permname, $rights),
            );
        }

        return $access;
    }

    public function applyMenuPermissions(&$menu, $rights)
    {
        $all_menus = array();
        foreach ($menu as $menukey => $menuitem) {
            $all_menus[$menukey] = isset($menuitem['submenu']) ? array_keys($menuitem['submenu']) : Permission::MENU_ALL;
        }

        $effective_menus = array();
        foreach ($rights as $permname) {
            if (!isset($this->permissions[$permname])) {
                continue;
            }
            $menuperms = $this->permissions[$permname]->getMenuPermissions();
            if (is_int($menuperms['allow_menu_items'])) {
                if ($menuperms['allow_menu_items'] == Permission::MENU_ALL) {
                    $effective_menus = $all_menus;
                }
            } else {
                foreach ($menuperms['allow_menu_items'] as $menukey => $menuitem) {
                    if (is_int($menuitem)) {
                        if ($menuitem == Permission::MENU_ALL) {
                            $menuperms['allow_menu_items'][$menukey] = $all_menus[$menukey];
                        }
                    }
                }
                $effective_menus = array_merge_recursive($effective_menus, $menuperms['allow_menu_items']);
            }
            if (is_int($menuperms['deny_menu_items'])) {
                if ($menuperms['deny_menu_items'] == Permission::MENU_ALL) {
                    $effective_menus = array_diff_key($effective_menus, $all_menus);
                    if (!empty($effective_menus)) {
                        foreach ($effective_menus as $menukey => $menuitem) {
                            $effective_menus[$menukey] = array_diff_key($effective_menus[$menukey], $all_menus[$menukey]);
                        }
                    }
                }
            } else {
                $effective_menus = array_diff_key($effective_menus, $menuperms['deny_menu_items']);
                if (!empty($effective_menus)) {
                    foreach ($effective_menus as $menukey => $menuitem) {
                        if (isset($menuperms['deny_menu_items'][$menukey])) {
                            $effective_menus[$menukey] = array_diff_key($effective_menus[$menukey], $menuperms['deny_menu_items'][$menukey]);
                        }
                    }
                }
            }
        }
        if (!empty($effective_menus)) {
            foreach ($effective_menus as $menukey => $menuitem) {
                if (is_array($menuitem) && !empty($menuitem)) {
                    $effective_menus[$menukey] = array_flip(array_unique($menuitem));
                }
            }
        }
        foreach ($menu as $menukey => &$menuitem) {
            if (isset($effective_menus[$menukey])) {
                if (isset($menuitem['submenu'])) {
                    foreach ($menuitem['submenu'] as $submenukey => $submenuitem) {
                        if (!isset($effective_menus[$menukey][$submenukey])) {
                            unset($menuitem['submenu'][$submenukey]);
                        }
                    }
                }
            } else {
                unset($menu[$menukey]);
            }
        }
        unset($menuitem);
    }
}
