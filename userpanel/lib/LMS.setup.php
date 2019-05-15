<?php

/*
 *  LMS version 1.11-git
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

define('USERPANEL_SETUPMODE', 1);

// register the resource name "module"
$SMARTY->registerResource('module', new Smarty_Resource_Userpanel_Setup_Module());

// Include locale file (main)
@include(USERPANEL_DIR.'/lib/locale/'.$_ui_language.'/strings.php');

// Include userpanel.class
require_once(USERPANEL_DIR.'/lib/Userpanel.class.php');
$USERPANEL = new USERPANEL($DB, $SESSION);

// Initialize modules

$modules_dirs = array(USERPANEL_MODULES_DIR);
$modules_dirs = $plugin_manager->executeHook('userpanel_modules_dir_initialized', $modules_dirs);

foreach ($modules_dirs as $suspected_module_dir) {
    $dh  = opendir($suspected_module_dir);
    while (false !== ($filename = readdir($dh))) {
        if ((preg_match('/^[a-zA-Z0-9]/', $filename)) && (is_dir($suspected_module_dir . $filename))
            && file_exists($suspected_module_dir . $filename.'/configuration.php')) {
            @include($suspected_module_dir . $filename.'/locale/'.$_ui_language.'/strings.php');
            include($suspected_module_dir . $filename.'/configuration.php');
        }
    }
}

$SMARTY->assignByRef('menu', $USERPANEL->MODULES);

$module = isset($_GET['module']) ? $_GET['module'] : 'userpanel';

// Execute module
$layout['pagetitle'] = trans('Configure Module: $a', $module);

if ($module == 'userpanel') {
    $modulefile_include = USERPANEL_DIR.'/lib/setup_functions.php';
} else {
    global $module_dir;
    $module_dir = null;
    foreach ($modules_dirs as $suspected_module_dir) {
        if (file_exists($suspected_module_dir . $module.'/functions.php')) {
            $module_dir = $suspected_module_dir;
            break;
        }
    }
    $modulefile_include = ($module_dir !== null ? $module_dir . $module.'/functions.php' : null);
}

$SMARTY->setDefaultResourceType('extendsall');

if (isset($modulefile_include)) {
    include($modulefile_include);

    $function = isset($_GET['f']) && $_GET['f']!='' ? $_GET['f'] : 'setup';

    if (function_exists('module_'.$function)) {
        $to_execute = 'module_'.$function;
        $to_execute();
    } else {
        if ($function=='setup') {
            $layout['info'] = trans('This module does not have any configuration settings');
            $SMARTY->display('file:' . USERPANEL_DIR . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'setup_error.html');
        } else {
            $layout['error'] = trans('Function <b>$a</b> in module <b>$b</b> not found!', $function, $module);
            $SMARTY->display('file:' . USERPANEL_DIR . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'setup_error.html');
        }
    }
} else {
    $layout['error'] = trans('Userpanel module <b>$a</b> not found!', $module);
    $SMARTY->display('file:' . USERPANEL_DIR . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'setup_error.html');
}
