<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2014 LMS Developers
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
$CONFIG_FILE = '';

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************

define('START_TIME', microtime(true));
define('LMS-UI', true);
ini_set('error_reporting', E_ALL&~E_NOTICE);

if(is_readable('/etc/lms/lms-'.$_SERVER['HTTP_HOST'].'.ini'))
        $CONFIG_FILE = '/etc/lms/lms-'.$_SERVER['HTTP_HOST'].'.ini';
elseif(is_readable('/etc/lms/lms.ini'))
        $CONFIG_FILE = '/etc/lms/lms.ini';
elseif (!is_readable($CONFIG_FILE))
        die('Unable to read configuration file ['.$CONFIG_FILE.']!');

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file(CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'].'/lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['doc_dir'] = (!isset($CONFIG['directories']['doc_dir']) ? $CONFIG['directories']['sys_dir'].'/documents' : $CONFIG['directories']['doc_dir']);
$CONFIG['directories']['modules_dir'] = (!isset($CONFIG['directories']['modules_dir']) ? $CONFIG['directories']['sys_dir'].'/modules' : $CONFIG['directories']['modules_dir']);
$CONFIG['directories']['backup_dir'] = (!isset($CONFIG['directories']['backup_dir']) ? $CONFIG['directories']['sys_dir'].'/backups' : $CONFIG['directories']['backup_dir']);
$CONFIG['directories']['config_templates_dir'] = (!isset($CONFIG['directories']['config_templates_dir']) ? $CONFIG['directories']['sys_dir'].'/config_templates' : $CONFIG['directories']['config_templates_dir']);
$CONFIG['directories']['smarty_compile_dir'] = (!isset($CONFIG['directories']['smarty_compile_dir']) ? $CONFIG['directories']['sys_dir'].'/templates_c' : $CONFIG['directories']['smarty_compile_dir']);
$CONFIG['directories']['smarty_templates_dir'] = (!isset($CONFIG['directories']['smarty_templates_dir']) ? $CONFIG['directories']['sys_dir'].'/templates' : $CONFIG['directories']['smarty_templates_dir']);
$CONFIG['directories']['plugins_dir'] = (!isset($CONFIG['directories']['plugins_dir']) ? $CONFIG['directories']['sys_dir'].'/plugins' : $CONFIG['directories']['plugins_dir']);
$CONFIG['directories']['vendor_dir'] = (!isset($CONFIG['directories']['vendor_dir']) ? $CONFIG['directories']['sys_dir'].'/vendor' : $CONFIG['directories']['vendor_dir']);
$CONFIG['directories']['userpanel_dir'] = (!isset($CONFIG['directories']['userpanel_dir']) ? $CONFIG['directories']['sys_dir'].'/userpanel' : $CONFIG['directories']['userpanel_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('DOC_DIR', $CONFIG['directories']['doc_dir']);
define('BACKUP_DIR', $CONFIG['directories']['backup_dir']);
define('MODULES_DIR', $CONFIG['directories']['modules_dir']);
define('SMARTY_COMPILE_DIR', $CONFIG['directories']['smarty_compile_dir']);
define('SMARTY_TEMPLATES_DIR', $CONFIG['directories']['smarty_templates_dir']);
define('PLUGINS_DIR', $CONFIG['directories']['plugins_dir']);
define('VENDOR_DIR', $CONFIG['directories']['vendor_dir']);
define('USERPANEL_DIR', $CONFIG['directories']['userpanel_dir']);

// Load autloader
require_once(LIB_DIR.'/autoloader.php');

// Do some checks and load config defaults
require_once(LIB_DIR.'/checkdirs.php');
require_once(LIB_DIR.'/config.php');

// Init database

$DB = null;

try {

    $DB = LMSDB::getInstance();

} catch (Exception $ex) {
    
    trigger_error($ex->getMessage(), E_USER_WARNING);
    
    // can't working without database
    die("Fatal error: cannot connect to database!\n");
    
}

// Call any of upgrade process before anything else

require_once(LIB_DIR.'/upgradedb.php');

// Initialize templates engine (must be before locale settings)
$SMARTY = new Smarty;

// test for proper version of Smarty

if (defined('Smarty::SMARTY_VERSION'))
	$ver_chunks = preg_split('/[- ]/', Smarty::SMARTY_VERSION);
else
	$ver_chunks = NULL;
if (count($ver_chunks) < 2 || version_compare('3.1', $ver_chunks[1]) > 0)
	die('<B>Wrong version of Smarty engine! We support only Smarty-3.x greater than 3.1.</B>');

define('SMARTY_VERSION', $ver_chunks[1]);

// add LMS's custom plugins directory
$SMARTY->addPluginsDir(LIB_DIR.'/SmartyPlugins');

// uncomment this line if you're not gonna change template files no more
//$SMARTY->compile_check = false;

// Redirect to SSL

$_FORCE_SSL = ConfigHelper::checkConfig('phpui.force_ssl');
if($_FORCE_SSL && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on'))
{
	header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
	exit(0);
}

// Include required files (including sequence is important)

require_once(LIB_DIR.'/language.php');
require_once(LIB_DIR.'/unstrip.php');
require_once(LIB_DIR.'/definitions.php');
require_once(LIB_DIR.'/common.php');
require_once(LIB_DIR.'/checkip.php');
require_once(LIB_DIR.'/accesstable.php');
require_once(LIB_DIR . '/SYSLOG.class.php');

if (ConfigHelper::checkConfig('phpui.logging') && class_exists('SYSLOG')) {
	$SYSLOG = new SYSLOG($DB);
} else {
	$SYSLOG = null;
}

// Initialize Session, Auth and LMS classes

$SESSION = new Session($DB, ConfigHelper::getConfig('phpui.timeout'));
$AUTH = new Auth($DB, $SESSION, $SYSLOG);
if ($SYSLOG)
	$SYSLOG->SetAuth($AUTH);
$LMS = new LMS($DB, $AUTH, $SYSLOG);
$LMS->ui_lang = $_ui_language;
$LMS->lang = $_language;

$plugin_manager = new LMSPluginManager();
$LMS->setPluginManager($plugin_manager);

// Initialize Swekey class

if (ConfigHelper::checkConfig('phpui.use_swekey')) {
	require_once(LIB_DIR . '/swekey/lms_integration.php');
	$LMS_SWEKEY = new LmsSwekeyIntegration($DB, $AUTH, $LMS);
	$SMARTY->assign('lms_swekey', $LMS_SWEKEY->GetIntegrationScript($AUTH->id));
}

// Set some template and layout variables

$SMARTY->setTemplateDir(null);
$custom_templates_dir = ConfigHelper::getConfig('phpui.custom_templates_dir');
if (!empty($custom_templates_dir) && file_exists(SMARTY_TEMPLATES_DIR . '/' . $custom_templates_dir)
	&& !is_file(SMARTY_TEMPLATES_DIR . '/' . $custom_templates_dir))
	$SMARTY->AddTemplateDir(SMARTY_TEMPLATES_DIR . '/' . $custom_templates_dir);
$SMARTY->AddTemplateDir(
	array(
		SMARTY_TEMPLATES_DIR . '/default',
		SMARTY_TEMPLATES_DIR,
	)
);
$SMARTY->setCompileDir(SMARTY_COMPILE_DIR);
$SMARTY->debugging = ConfigHelper::checkConfig('phpui.smarty_debug');

$layout['logname'] = $AUTH->logname;
$layout['logid'] = $AUTH->id;
$layout['lmsdbv'] = $DB->GetVersion();
$layout['smarty_version'] = SMARTY_VERSION;
$layout['hostname'] = hostname();
$layout['lmsv'] = '1.11-git';
//$layout['lmsvr'] = $LMS->_revision.'/'.$AUTH->_revision;
$layout['lmsvr'] = '';
$layout['dberrors'] = $DB->GetErrors();
$layout['dbdebug'] = $_DBDEBUG;
$layout['popup'] = isset($_GET['popup']) ? true : false;

$SMARTY->assignByRef('layout', $layout);
$SMARTY->assignByRef('LANGDEFS', $LANGDEFS);
$SMARTY->assignByRef('_ui_language', $LMS->ui_lang);
$SMARTY->assignByRef('_language', $LMS->lang);

$error = NULL; // initialize error variable needed for (almost) all modules

// Load menu

if(!$layout['popup'])
{
	require_once(LIB_DIR.'/menu.php');
	$SMARTY->assign('newmenu', $menu);
}

header('X-Powered-By: LMS/'.$layout['lmsv']);

// Check privileges and execute modules
if ($AUTH->islogged) {
	// Load plugin files and register hook callbacks
	$plugins = preg_split('/[;,\s\t\n]+/', ConfigHelper::getConfig('phpui.plugins'), -1, PREG_SPLIT_NO_EMPTY);
	if (!empty($plugins))
		foreach ($plugins as $plugin_name)
			if(is_readable(LIB_DIR . '/plugins/' . $plugin_name . '.php'))
				require LIB_DIR . '/plugins/' . $plugin_name . '.php';

	$res = $LMS->ExecHook('access_table_init', array('accesstable' => $access['table']));
	if (isset($res['accesstable']))
		$access['table'] = $res['accesstable'];
        
        LMSConfig::getConfig(array(
            'force' => true,
            'force_user_rights_only' => true,
            'access_table' => $access['table'],
            'user_id' => $AUTH->id,
        ));

	$module = isset($_GET['m']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['m']) : '';
	$deny = $allow = FALSE;

	$res = $LMS->ExecHook('module_load_before', array('module' => $module));
	if ($res['abort']) {
		$SESSION->close();
		$DB->Destroy();
		die;
	}
	$module = $res['module'];

	if ($AUTH->passwdrequiredchange)
		$module = 'chpasswd';

	if ($module == '')
	{
		$module = ConfigHelper::getConfig('phpui.default_module');
	}

	if (file_exists(MODULES_DIR.'/'.$module.'.php'))
	{
		$global_allow = !$AUTH->id || (!empty($access['allow']) && preg_match('/'.$access['allow'].'/i', $module));

		if ($AUTH->id && ($rights = $LMS->GetUserRights($AUTH->id)))
			foreach ($rights as $level)
			{

				if (!$global_allow && !$deny && isset($access['table'][$level]['deny_reg']))
					$deny = (bool) preg_match('/'.$access['table'][$level]['deny_reg'].'/i', $module);
				elseif (!$allow && isset($access['table'][$level]['allow_reg']))
					$allow = (bool) preg_match('/'.$access['table'][$level]['allow_reg'].'/i', $module);

			}

		if ($SYSLOG)
			$SYSLOG->NewTransaction($module);

		if ($global_allow || ($allow && !$deny))
		{
			$layout['module'] = $module;
			$LMS->InitUI();
			include(MODULES_DIR.'/'.$module.'.php');
		} else {
			if ($SYSLOG)
				$SYSLOG->AddMessage(SYSLOG_RES_USER, SYSLOG_OPER_USERNOACCESS,
					array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $AUTH->id), array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]));
			$SMARTY->display('noaccess.html');
		}
	}
	else
	{
		$layout['module'] = 'notfound';
		$layout['pagetitle'] = trans('Error!');
		$SMARTY->assign('layout', $layout);
		$SMARTY->assign('server', $_SERVER);
		$SMARTY->display('notfound.html');
	}

	if($SESSION->get('lastmodule') != $module)
		$SESSION->save('lastmodule', $module);
}
else
{
	$SMARTY->assign('error', $AUTH->error);
	$SMARTY->assign('target','?'.$_SERVER['QUERY_STRING']);
	$SMARTY->display('login.html');
}

$SESSION->close();
$DB->Destroy();

?>
