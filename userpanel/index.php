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

// REPLACE THIS WITH PATH TO YOUR CONFIG FILE

$CONFIG_FILE = (is_readable('lms.ini')) ? 'lms.ini' : '/etc/lms/lms.ini';

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************

ini_set('session.name','LMSSESSIONID');
ini_set('error_reporting', E_ALL&~E_NOTICE);

// find alternative config files:
if(is_readable('lms.ini'))
        $CONFIG_FILE = 'lms.ini';
elseif(is_readable('/etc/lms/lms-'.$_SERVER['HTTP_HOST'].'.ini'))
        $CONFIG_FILE = '/etc/lms/lms-'.$_SERVER['HTTP_HOST'].'.ini';
elseif(!is_readable($CONFIG_FILE))
        die('Unable to read configuration file ['.$CONFIG_FILE.']!');

define('CONFIG_FILE', $CONFIG_FILE);

// Parse configuration file
$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'].'/lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['userpanel_dir'] = (!isset($CONFIG['directories']['userpanel_dir']) ? getcwd() : $CONFIG['directories']['userpanel_dir']);
$CONFIG['directories']['modules_dir'] = (!isset($CONFIG['directories']['modules_dir']) ? $CONFIG['directories']['userpanel_dir'].'/modules' : $CONFIG['directories']['modules_dir']);
$CONFIG['directories']['smarty_compile_dir'] = $CONFIG['directories']['userpanel_dir'].'/templates_c';
$CONFIG['directories']['plugins_dir'] = (!isset($CONFIG['directories']['plugins_dir']) ? $CONFIG['directories']['sys_dir'].'/plugins' : $CONFIG['directories']['plugins_dir']);

define('USERPANEL_DIR', $CONFIG['directories']['userpanel_dir']);
define('USERPANEL_LIB_DIR', USERPANEL_DIR.'/lib/');
define('USERPANEL_MODULES_DIR', USERPANEL_DIR.'/modules/');

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('DOC_DIR', $CONFIG['directories']['doc_dir']);
define('MODULES_DIR', $CONFIG['directories']['modules_dir']);
define('SMARTY_COMPILE_DIR', $CONFIG['directories']['smarty_compile_dir']);
define('PLUGINS_DIR', $CONFIG['directories']['plugins_dir']);

// include required files

// Load autloader
require_once(LIB_DIR.'/autoloader.php');

require_once(USERPANEL_LIB_DIR.'/checkdirs.php');
require_once(LIB_DIR.'/config.php');

// Initialize database

$DB = null;

try {

    $DB = LMSDB::getInstance();

} catch (Exception $ex) {
    
    trigger_error($ex->getMessage(), E_USER_WARNING);
    
    // can't working without database
    die("Fatal error: cannot connect to database!\n");
    
}

// Initialize templates engine (must be before locale settings)
$SMARTY = new Smarty;

// test for proper version of Smarty

if (constant('Smarty::SMARTY_VERSION'))
	$ver_chunks = preg_split('/[- ]/', Smarty::SMARTY_VERSION);
else
	$ver_chunks = NULL;

if (count($ver_chunks) < 2 || version_compare('3.1', $ver_chunks[1]) > 0)
	die('<B>Wrong version of Smarty engine! We support only Smarty-3.x greater than 3.0.</B>');

define('SMARTY_VERSION', $ver_chunks[1]);

// add LMS's custom plugins directory
$SMARTY->addPluginsDir(LIB_DIR.'/SmartyPlugins');

// Redirect to SSL

$_FORCE_SSL = ConfigHelper::checkConfig('phpui.force_ssl');

if($_FORCE_SSL && $_SERVER['HTTPS'] != 'on')
{
     header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
     exit(0);
}

$_TIMEOUT = ConfigHelper::getConfig('phpui.timeout');

// Include required files (including sequence is important)

require_once(LIB_DIR.'/language.php');
include_once(LIB_DIR.'/definitions.php');
require_once(LIB_DIR.'/unstrip.php');
require_once(LIB_DIR.'/common.php');

$AUTH = NULL;
$SYSLOG = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

require_once(USERPANEL_LIB_DIR.'/Session.class.php');
require_once(USERPANEL_LIB_DIR.'/Userpanel.class.php');
require_once(USERPANEL_LIB_DIR.'/ULMS.class.php');
@include(USERPANEL_DIR.'/lib/locale/'.$_ui_language.'/strings.php');

unset($LMS); // reset LMS class to enable wrappers for LMS older versions

$LMS = new ULMS($DB, $AUTH, $SYSLOG);
$SESSION = new Session($DB, $_TIMEOUT);
$USERPANEL = new USERPANEL($DB, $SESSION);
$LMS->ui_lang = $_ui_language;
$LMS->lang = $_language;

// Initialize modules

$dh  = opendir(USERPANEL_MODULES_DIR);
while (false !== ($filename = readdir($dh))) {
    if ((preg_match('/^[a-zA-Z0-9]/',$filename)) && (is_dir(USERPANEL_MODULES_DIR.$filename)) && file_exists(USERPANEL_MODULES_DIR.$filename.'/configuration.php'))
    {
	@include(USERPANEL_MODULES_DIR.$filename.'/locale/'.$_ui_language.'/strings.php');
	include(USERPANEL_MODULES_DIR.$filename.'/configuration.php');
	if (is_dir(USERPANEL_MODULES_DIR.$filename.'/plugins/'))
	{
		$plugins = glob(USERPANEL_MODULES_DIR.$filename.'/plugins/*.php');
		if (!empty($plugins))
			foreach ($plugins as $plugin_name)
				if(is_readable($plugin_name))
					include($plugin_name);
	}
    }
};

$SMARTY->assignByRef('LANGDEFS', $LANGDEFS);
$SMARTY->assignByRef('_ui_language', $LMS->ui_lang);
$SMARTY->assignByRef('_language', $LMS->lang);
$SMARTY->setTemplateDir(null);
$style = ConfigHelper::getConfig('userpanel.style', 'default');
$SMARTY->addTemplateDir(array(
	USERPANEL_DIR . '/style/' .  $style . '/templates',
	USERPANEL_DIR . '/templates',
));
$SMARTY->setCompileDir(SMARTY_COMPILE_DIR);
$SMARTY->debugging = ConfigHelper::checkConfig('phpui.smarty_debug');
require_once(USERPANEL_LIB_DIR.'/smarty_addons.php');

$layout['upv'] = $USERPANEL->_version.' ('.$USERPANEL->_revision.'/'.$SESSION->_revision.')';
$layout['lmsdbv'] = $DB->GetVersion();
$layout['lmsv'] = $LMS->_version;
$layout['smarty_version'] = SMARTY_VERSION;
$layout['hostname'] = hostname();
$layout['dberrors'] =& $DB->GetErrors();

$SMARTY->assignByRef('modules', $USERPANEL->MODULES);
$SMARTY->assignByRef('layout', $layout);

header('X-Powered-By: LMS/'.$layout['lmsv']);

if($SESSION->islogged)
{
	$module = isset($_GET['m']) ? $_GET['m'] : '';

	if (isset($USERPANEL->MODULES[$module])) $USERPANEL->MODULES[$module]['selected'] = true;

	// Userpanel rights module
	$rights = $USERPANEL->GetCustomerRights($SESSION->id);
	$SMARTY->assign('rights', $rights);

	if(ConfigHelper::checkConfig('userpanel.hide_nodes_modules'))
	{
		if(!$DB->GetOne('SELECT COUNT(*) FROM nodes WHERE ownerid = ? LIMIT 1', array($SESSION->id)))
		{
			unset($USERPANEL->MODULES['notices']);
			unset($USERPANEL->MODULES['stats']);
		}
	}

	// Userpanel popup for urgent notice
	$res = $LMS->ExecHook('userpanel_module_call_before', array('module' => $USERPANEL->MODULES['notices']));

	if( file_exists(USERPANEL_MODULES_DIR.$module.'/functions.php')
	    && isset($USERPANEL->MODULES[$module]) )
        {
    		include(USERPANEL_MODULES_DIR.$module.'/functions.php');

		$function = isset($_GET['f']) && $_GET['f']!='' ? $_GET['f'] : 'main';
		if (function_exists('module_'.$function)) 
		{
		    $to_execute = 'module_'.$function;
		    $to_execute();
		} else {
    		    $layout['error'] = trans('Function <b>$a</b> in module <b>$b</b> not found!', $function, $module);
    		    $SMARTY->display('error.html');
		}
        }
        // if no module selected, redirect on module with lowest prio
	elseif ($module=='')
        {
		$redirectmodule = 'nomodulesfound';
		$redirectprio = 999;
		foreach ($USERPANEL->MODULES as $menupos)
		    if ($redirectprio > $menupos['prio']) 
		    {
			$redirectmodule = $menupos['module'];
			$redirectprio = $menupos['prio'];
		    }
		if ($redirectmodule == 'nomodulesfound') 
		{
    		    $layout['error'] = trans('No modules found!');
    		    $SMARTY->display('error.html');
		} 
		else
		{
		    header('Location: ?m='.$redirectmodule);
		}
        }
        else
        {
    		$layout['error'] = trans('Module <b>$a</b> not found!', $module);
    		$SMARTY->display('error.html');
    	}

        if(!isset($_SESSION['lastmodule']) || $_SESSION['lastmodule'] != $module)
    		$_SESSION['lastmodule'] = $module;
}
else
{
        $SMARTY->assign('error', $SESSION->error);
        $SMARTY->assign('target','?'.$_SERVER['QUERY_STRING']);
        $SMARTY->display('login.html');
}

$DB->Destroy();

?>
