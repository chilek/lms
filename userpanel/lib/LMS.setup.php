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

define('USERPANEL_SETUPMODE', 1);

// register smarty extensions

function module_get_template($tpl_name, &$tpl_source, $smarty_obj)
{
	global $LMS;
	$template = explode(':', $tpl_name);
	$template_path = $LMS->CONFIG['directories']['userpanel_dir'].'/modules/'.$template[0].'/templates/'.$template[1];
	if (file_exists($template_path))
	{
		$tpl_source = file_get_contents($template_path);
		return true;
	} else
		return false;
}

function module_get_timestamp($tpl_name, &$tpl_timestamp, $smarty_obj)
{
	global $LMS;
	$template = explode(':', $tpl_name);
	$template_path = $LMS->CONFIG['directories']['userpanel_dir'].'/modules/'.$template[0].'/templates/'.$template[1];
	if (file_exists($template_path))
	{
		$tpl_timestamp = filectime($template_path);
		return true;
	} else
		return false;
}

function module_get_secure($tpl_name, &$smarty_obj)
{
	// assume all templates are secure
	return true;
}

function module_get_trusted($tpl_name, &$smarty_obj)
{
	// not used for templates
}

// register the resource name "module"
$SMARTY->registerResource('module', array('module_get_template',
					'module_get_timestamp',
					'module_get_secure',
					'module_get_trusted'));

// Include locale file (main)
@include(USERPANEL_DIR.'/lib/locale/'.$_ui_language.'/strings.php');

// Include userpanel.class
require_once(USERPANEL_DIR.'/lib/Userpanel.class.php');
$USERPANEL = new USERPANEL($DB, $SESSION, $CONFIG);

// Initialize modules
$dh  = opendir(USERPANEL_MODULES_DIR);
while (false !== ($filename = readdir($dh))) 
{
	if ((preg_match('/^[a-zA-Z0-9]/',$filename)) && (is_dir(USERPANEL_MODULES_DIR.$filename)) && file_exists(USERPANEL_MODULES_DIR.$filename.'/configuration.php'))
	{
		@include(USERPANEL_MODULES_DIR.$filename.'/locale/'.$_ui_language.'/strings.php');
		include(USERPANEL_MODULES_DIR.$filename.'/configuration.php');
	}
};

$SMARTY->assignByRef('menu', $USERPANEL->MODULES);

$module = isset($_GET['module']) ? $_GET['module'] : 'userpanel';

// Execute module
$layout['pagetitle'] = trans('Configure Module: $a',$module);

if($module == 'userpanel')
	$modulefile_include = USERPANEL_DIR.'/lib/setup_functions.php';
else
	$modulefile_include = file_exists(USERPANEL_MODULES_DIR.$module.'/functions.php') ? USERPANEL_MODULES_DIR.$module.'/functions.php' : NULL;

if (isset($modulefile_include))
{
	include($modulefile_include);

	$function = isset($_GET['f']) && $_GET['f']!='' ? $_GET['f'] : 'setup';

	if (function_exists('module_'.$function))
	{
		$to_execute = 'module_'.$function;
		$to_execute();
	} 
	else 
	{
		if ($function=='setup') {
			$layout['info'] = trans('This module does not have any configuration settings');
			$SMARTY->display($LMS->CONFIG['directories']['userpanel_dir'].'/templates/setup_error.html');
		} else {
			$layout['error'] = trans('Function <b>$a</b> in module <b>$b</b> not found!', $function, $module);
			$SMARTY->display($LMS->CONFIG['directories']['userpanel_dir'].'/templates/setup_error.html');
		}
	}
} 
else
{
	$layout['error'] = trans('Userpanel module <b>$a</b> not found!', $module);
	$SMARTY->display($LMS->CONFIG['directories']['userpanel_dir'].'/templates/setup_error.html');
}

?>
