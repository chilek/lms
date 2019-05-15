<?php

/*
 *  LMS version 1.11-git
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

class USERPANEL
{
    private $DB;
    private $SESSION;
    public $MODULES = array();
    private $module_order = null;

    public function __construct(&$DB, &$SESSION)
    {
 // ustawia zmienne klasy
        $this->DB = &$DB;
        $this->SESSION = &$SESSION;
        $module_order = ConfigHelper::getConfig('userpanel.module_order', '', true);
        if (strlen($module_order)) {
            $this->module_order = array_flip(explode(',', $module_order));
        }
    }

    public function _postinit()
    {
        return true;
    }

    public function AddModule($name = '', $module = '', $tip = '', $prio = 99, $description = '', $submenu = null)
    {
        if (isset($this->module_order[$module])) {
            $prio = $this->module_order[$module];
        }
        if ($name != '') {
            $this->MODULES[$module] = array('name' => $name, 'tip' => $tip, 'prio' => $prio, 'description' => $description, 'selected' => false, 'module' => $module, 'submenu' => $submenu);
            if (!function_exists('cmp')) {
                function cmp($a, $b)
                {
                    if ($a['prio'] == $b['prio']) {
                        return 0;
                    }
                    return ($a['prio'] < $b['prio']) ? -1 : 1;
                }
            }
            uasort($this->MODULES, 'cmp');
            return true;
        }
        return false;
    }

    public function GetCustomerRights($id)
    {
        $result = null;

        $rights = $this->DB->GetAll('SELECT name, module 
					FROM up_rights
					LEFT JOIN up_rights_assignments ON up_rights.id=up_rights_assignments.rightid
					WHERE customerid=?', array($id));

        if (!$rights) {
            $rights = $this->DB->GetAll('SELECT name, module FROM up_rights WHERE setdefault=1');
        }

        if ($rights) {
            foreach ($rights as $right) {
                $result[$right['module']][$right['name']] = true;
            }
        }

        return $result;
    }
}
