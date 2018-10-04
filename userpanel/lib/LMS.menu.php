<?php

/*
 *  LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

// variables
$CONFIG['directories']['userpanel_dir'] = (!isset($CONFIG['directories']['userpanel_dir']) ? getcwd() . DIRECTORY_SEPARATOR . 'userpanel' : $CONFIG['directories']['userpanel_dir']);

define('USERPANEL_DIR', $CONFIG['directories']['userpanel_dir']);
define('USERPANEL_LIB_DIR', USERPANEL_DIR . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR);
define('USERPANEL_MODULES_DIR', USERPANEL_DIR . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR);

@include(USERPANEL_DIR.'/lib/locale/'.$_ui_language.'/strings.php');

// Clear submenu array
$submenu = NULL;

// Add Configutation submenu
$submenu[] = 
	array(
		'name' => trans('Configuration'),
		'link' => '?m=userpanel',
		'tip' => trans('Userpanel configuration'),
		'prio' => 10,
	);
$submenu[] =
	array(
		'name' => trans('Rights'),
		'link' => '?m=userpanel&f=rights',
		'tip' => trans('Customers\' rights'),
		'prio' => 20,
	);

// *** HERE ADD YOUR OWN SUBMENU ***


// Include userpanel.class
require_once(USERPANEL_DIR.'/lib/Userpanel.class.php');
$USERPANEL = new USERPANEL($DB, $SESSION);

// Initialize modules
$dh  = opendir(USERPANEL_MODULES_DIR);
while (false !== ($filename = readdir($dh)))
	if ((preg_match('/^[a-zA-Z0-9]/',$filename)) && (is_dir(USERPANEL_MODULES_DIR.$filename)) && file_exists(USERPANEL_MODULES_DIR.$filename."/configuration.php"))
	{
        	@include(USERPANEL_MODULES_DIR.$filename.'/locale/'.$_ui_language.'/strings.php');
	        include(USERPANEL_MODULES_DIR.$filename.'/configuration.php');
	}

foreach($USERPANEL->MODULES as $menupos)
	if(isset($menupos['submenu']))
		foreach($menupos['submenu'] as $modulemenu)
			$submenu[] = $modulemenu;

// *** HERE ADD YOUR OWN SUBMENU ***



// Add Userpanel menu to LMS main menu
$menu['userpanel'] = array(
	'name' => trans('Userpanel'),
	'css' => 'lms-ui-menu-item-icon lms-ui-userpanel-icon',
	'link' => '?m=userpanel',
	'tip' => trans('Userpanel'),
	'prio' => '80',
	'submenu' => $submenu,
);

?>
