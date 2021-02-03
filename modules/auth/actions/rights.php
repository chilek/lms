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

$userid = isset($_GET['id']) ? intval($_GET['id']) : null;

$AUTH_MODINFO = array();

// first reading user access rights (for current user it was done in auth/main)

if ($userid) {
    if ($rights = $DB->GetOne('SELECT data FROM rights WHERE userid = ?', array($userid))) {
        $AUTH_MODULES = array_merge($AUTH_MODULES, unserialize($rights));
    }
}

// creating all modules/actions list

if ($handle = opendir($ExecStack->modules_dir)) {
    while (false !== ($file = readdir($handle))) {
        if (is_dir($ExecStack->modules_dir.'/'.$file) && is_readable($ExecStack->modules_dir.'/'.$file.'/modinfo.php')) {
            include($ExecStack->modules_dir.'/'.$file.'/modinfo.php');
            foreach ($_MODINFO as $module_name => $module_info) {
                if (isset($module_info['actions'])) {
                    $AUTH_MODINFO[$module_name]['count'] = 0;
                    
                    if (isset($module_info['description'])) {
                        $AUTH_MODINFO[$module_name]['description'] = $module_info['description'];
                    }
                    if (isset($module_info['summary'])) {
                        $AUTH_MODINFO[$module_name]['description'] = $module_info['summary'];
                    }
                    if (isset($module_info['summary'])) {
                        $AUTH_MODINFO[$module_name]['description'] = $module_info['summary'];
                    }
                        
                    foreach ($module_info['actions'] as $action_name => $action_info) {
                        if ((!isset($action_info['hidden']) ||  $action_info['hidden'] !== true)
                            && (!isset($action_info['notpublic']) || $action_info['notpublic'] !== true)) {
                            $AUTH_MODINFO[$module_name]['actions'][$action_name] = $action_info;
                            $AUTH_MODINFO[$module_name]['count']++;
                        }
                    }
                }
            }
        }
    }
    closedir($handle);
}

$SMARTY->assign('rights', $AUTH_MODULES);
$SMARTY->assign('modinfo', $AUTH_MODINFO);

register_plugin('users-add-beforetableend', '../modules/auth/templates/rightsedit.html');
register_plugin('users-edit-beforetableend', '../modules/auth/templates/rightsedit.html');
register_plugin('users-info-beforetableend', '../modules/auth/templates/rightsinfo.html');
