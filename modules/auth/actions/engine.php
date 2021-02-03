<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

// checks access rights
function is_authorized($module, $action)
{
    global $AUTH_MODULES;

    if (isset($AUTH_MODULES[$module])) {
        if (is_array($AUTH_MODULES[$module])) {
            if (isset($AUTH_MODULES[$module][$action])) {
                return true;
            }
        } else {
            return true;
        }
    }

    return false;
}

// unsets actions and templates of disabled modules/actions and related to them
function authorize($module, $action)
{
    global $ExecStack, $bindtable;

    if (!is_authorized($module, $action)) {
        if (isset($ExecStack->_BINDTABLE['pre/'.$module.':'.$action])) {
            foreach ($ExecStack->_BINDTABLE['pre/'.$module.':'.$action] as $binding) {
                list($mod, $act) = explode(':', $binding);
                
                // we can remove action only if isn't binded to other action
                if (isset($bindtable[$mod.':'.$act]) && $bindtable[$mod.':'.$act]['count'] > 1) {
                    $bindtable[$mod.':'.$act]['count'] --;
                } else {
                    $ExecStack->dropAction($mod, $act);
                    unset($ExecStack->_MODINFO[$mod]['actions'][$act]);
                }
            }
        }
            
        if (isset($ExecStack->_BINDTABLE['post/'.$module.':'.$action])) {
            foreach ($ExecStack->_BINDTABLE['post/'.$module.':'.$action] as $binding) {
                list($mod, $act) = explode(':', $binding);

                // we can remove action only if isn't binded to other action
                if (isset($bindtable[$mod.':'.$act]) && $bindtable[$mod.':'.$act]['count'] > 1) {
                    $bindtable[$mod.':'.$act]['count'] --;
                } else {
                    $ExecStack->dropAction($mod, $act);
                    unset($ExecStack->_MODINFO[$mod]['actions'][$act]);
                }
            }
        }

        $ExecStack->dropAction($module, $action);
        unset($ExecStack->_MODINFO[$module]['actions'][$action]);
    }
}

// first reading user access rights (and marging with defaults)

if ($rights = $DB->GetOne('SELECT data FROM rights WHERE userid = ?', array(Auth::GetCurrentUser()))) {
    $rights = unserialize($rights);
    foreach ($rights as $mod => $mod_val) {
        // module accessible by default
        if (isset($AUTH_MODULES[$mod]) && !is_array($AUTH_MODULES[$mod])) {
            continue;
        } elseif (isset($AUTH_MODULES[$mod]) && is_array($AUTH_MODULES[$mod]) && is_array($mod_val)) {
            $AUTH_MODULES[$mod] = array_merge($AUTH_MODULES[$mod], $mod_val);
        } else {
            $AUTH_MODULES[$mod] = $mod_val;
        }
    }
}

// URL called module/action checking,
// we'll don't show 'noaccess' for non-existing or non-public modules/actions,
// core will print more suitable messages,
// doing this before rebuilding ExecStack array (see below)

$noaccess = false;

if ($ExecStack->moduleExists($ExecStack->module)
    && $ExecStack->moduleIsPublic($ExecStack->module)
    && $ExecStack->actionExists($ExecStack->module, $ExecStack->action)
    && $ExecStack->actionIsPublic($ExecStack->module, $ExecStack->action)
    && !is_authorized($ExecStack->module, $ExecStack->action)) {
    $noaccess = true;
}

// rebuilding _MODINFO and _EXECSTACK arrays
// to remove unwanted actions

// it's not the best solution, maybe someone can do this without so many loops?

// first build special (helper) bindings table for authorize() function
foreach ($ExecStack->_BINDTABLE as $idx => $binding) {
    list($pre, $act) = explode('/', $idx);
    foreach ($binding as $bind) {
        $bindtable[$bind]['action'][] = $act;
        $bindtable[$bind]['count'] ++;
    }
}

reset($ExecStack->_MODINFO); // reset for each() function below

while (list($mod_name, $mod_info) = each($ExecStack->_MODINFO)) {
    if (isset($mod_info['actions'])) {
        foreach ($mod_info['actions'] as $action_name => $action_info) {
            authorize($mod_name, $action_name);
        }
    }
}

// the same with menus (I don't kwnow how to do this in one loop)

$_MODINFO = $ExecStack->_MODINFO; // copy for nested loops below

reset($ExecStack->_MODINFO); // reset again

while (list($mod_name, $mod_info) = each($ExecStack->_MODINFO)) {
    if (isset($mod_info['menus'])) {
        foreach ($mod_info['menus'] as $menu_idx => $menu_array) {
            foreach ($_MODINFO as $mod_array) {
                if (isset($mod_array['actions'])) {
                    foreach ($mod_array['actions'] as $act) {
                        if (isset($act['menu']) && $act['menu'] == $menu_array['id']) {
                            break 3; // skip unset() below
                        }
                    }
                }
            }
            
            // delete menu if any related action wasn't found
            unset($ExecStack->_MODINFO[$mod_name]['menus'][$menu_idx]);
        }
    }
}

// finally...

if ($noaccess) {
    header('Location: ?m=auth&a=noaccess');
    die;
}
