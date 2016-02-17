<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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
$CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************

define('START_TIME', microtime(true));
define('LMS-UI', true);
define('K_TCPDF_EXTERNAL_CONFIG', true);
ini_set('error_reporting', E_ALL&~E_NOTICE);

// find alternative config files:
if (is_readable('lms.ini'))
	$CONFIG_FILE = 'lms.ini';
elseif (is_readable(DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms-' . $_SERVER['HTTP_HOST'] . '.ini'))
	$CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms-' . $_SERVER['HTTP_HOST'] . '.ini';
elseif (!is_readable($CONFIG_FILE))
	die('Unable to read configuration file ['.$CONFIG_FILE.']!'); 

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file(CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['doc_dir'] = (!isset($CONFIG['directories']['doc_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'documents' : $CONFIG['directories']['doc_dir']);
$CONFIG['directories']['modules_dir'] = (!isset($CONFIG['directories']['modules_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'modules' : $CONFIG['directories']['modules_dir']);
$CONFIG['directories']['backup_dir'] = (!isset($CONFIG['directories']['backup_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'backups' : $CONFIG['directories']['backup_dir']);
$CONFIG['directories']['config_templates_dir'] = (!isset($CONFIG['directories']['config_templates_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'config_templates' : $CONFIG['directories']['config_templates_dir']);
$CONFIG['directories']['smarty_compile_dir'] = (!isset($CONFIG['directories']['smarty_compile_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'templates_c' : $CONFIG['directories']['smarty_compile_dir']);
$CONFIG['directories']['smarty_templates_dir'] = (!isset($CONFIG['directories']['smarty_templates_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'templates' : $CONFIG['directories']['smarty_templates_dir']);
$CONFIG['directories']['plugin_dir'] = (!isset($CONFIG['directories']['plugin_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'plugins' : $CONFIG['directories']['plugin_dir']);
$CONFIG['directories']['plugins_dir'] = $CONFIG['directories']['plugin_dir'];
$CONFIG['directories']['vendor_dir'] = (!isset($CONFIG['directories']['vendor_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'vendor' : $CONFIG['directories']['vendor_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('DOC_DIR', $CONFIG['directories']['doc_dir']);
define('BACKUP_DIR', $CONFIG['directories']['backup_dir']);
define('MODULES_DIR', $CONFIG['directories']['modules_dir']);
define('SMARTY_COMPILE_DIR', $CONFIG['directories']['smarty_compile_dir']);
define('SMARTY_TEMPLATES_DIR', $CONFIG['directories']['smarty_templates_dir']);
define('PLUGIN_DIR', $CONFIG['directories']['plugin_dir']);
define('PLUGINS_DIR', $CONFIG['directories']['plugin_dir']);
define('VENDOR_DIR', $CONFIG['directories']['vendor_dir']);

// Load autoloader
$composer_autoload_path = VENDOR_DIR . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/");
}

// Do some checks and load config defaults
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'checkdirs.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'Smarty' . DIRECTORY_SEPARATOR . 'Smarty.class.php');

// Init database

$DB = null;

try {
	$DB = LMSDB::getInstance();
} catch (Exception $ex) {
	trigger_error($ex->getMessage(), E_USER_WARNING);
	// can't working without database
	die("Fatal error: cannot connect to database!<BR>");
}

// Call any of upgrade process before anything else

$layout['dbschversion'] = $DB->UpgradeDb();

// Initialize templates engine (must be before locale settings)
$SMARTY = new LMSSmarty;

// test for proper version of Smarty

if (defined('Smarty::SMARTY_VERSION'))
	$ver_chunks = preg_split('/[- ]/', preg_replace('/^smarty-/i', '', Smarty::SMARTY_VERSION), -1, PREG_SPLIT_NO_EMPTY);
else
	$ver_chunks = NULL;
if (count($ver_chunks) < 1 || version_compare('3.1', $ver_chunks[0]) > 0)
	die('<B>Wrong version of Smarty engine! We support only Smarty-3.x greater than 3.1.</B>');

define('SMARTY_VERSION', $ver_chunks[0]);

// add LMS's custom plugins directory
$SMARTY->addPluginsDir(LIB_DIR . DIRECTORY_SEPARATOR . 'SmartyPlugins');

$SMARTY->setMergeCompiledIncludes(true);

$SMARTY->setDefaultResourceType('extendsall');

// uncomment this line if you're not gonna change template files no more
//$SMARTY->compile_check = false;

// Redirect to SSL

$_FORCE_SSL = ConfigHelper::checkConfig('phpui.force_ssl');
if($_FORCE_SSL && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on')) {
	header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
	exit(0);
}

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'unstrip.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'checkip.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'accesstable.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'SYSLOG.class.php');

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
$SMARTY->setPluginManager($plugin_manager);

// Initialize Swekey class

if (ConfigHelper::checkConfig('phpui.use_swekey')) {
	require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'swekey' . DIRECTORY_SEPARATOR . 'lms_integration.php');
	$LMS_SWEKEY = new LmsSwekeyIntegration($DB, $AUTH, $LMS);
	$SMARTY->assign('lms_swekey', $LMS_SWEKEY->GetIntegrationScript($AUTH->id));
}

// Set some template and layout variables

$SMARTY->setTemplateDir(null);
$custom_templates_dir = ConfigHelper::getConfig('phpui.custom_templates_dir');
if (!empty($custom_templates_dir) && file_exists(SMARTY_TEMPLATES_DIR . DIRECTORY_SEPARATOR . $custom_templates_dir)
	&& !is_file(SMARTY_TEMPLATES_DIR . DIRECTORY_SEPARATOR . $custom_templates_dir))
	$SMARTY->AddTemplateDir(SMARTY_TEMPLATES_DIR . DIRECTORY_SEPARATOR . $custom_templates_dir);
$SMARTY->AddTemplateDir(
	array(
		SMARTY_TEMPLATES_DIR . DIRECTORY_SEPARATOR . 'default',
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
$layout['lmsv'] = $LMS->_version;
$layout['lmsvr'] = $LMS->_revision;
$layout['dberrors'] = $DB->GetErrors();
$layout['dbdebug'] = isset($_DBDEBUG) ? $_DBDEBUG : false;
$layout['popup'] = isset($_GET['popup']) ? true : false;

$SMARTY->assignByRef('layout', $layout);
$SMARTY->assignByRef('LANGDEFS', $LANGDEFS);
$SMARTY->assignByRef('_ui_language', $LMS->ui_lang);
$SMARTY->assignByRef('_language', $LMS->lang);

$error = NULL; // initialize error variable needed for (almost) all modules

// Load menu

if(!$layout['popup'])
{
	require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'menu.php');
        
        $menu = $plugin_manager->executeHook('menu_initialized', $menu);
        
	$SMARTY->assign('newmenu', $menu);
}

header('X-Powered-By: LMS/'.$layout['lmsv']);

$modules_dirs = array(MODULES_DIR);
$modules_dirs = $plugin_manager->executeHook('modules_dir_initialized', $modules_dirs);

$plugin_manager->executeHook('lms_initialized', $LMS);

$plugin_manager->executeHook('smarty_initialized', $SMARTY);

$documents_dirs = array(DOC_DIR);
$documents_dirs = $plugin_manager->executeHook('documents_dir_initialized', $documents_dirs);

// Check privileges and execute modules
if ($AUTH->islogged) {
	// Load plugin files and register hook callbacks
	$plugins = $plugin_manager->getAllPluginInfo(LMSPluginManager::OLD_STYLE);
	if (!empty($plugins))
		foreach ($plugins as $plugin_name => $plugin)
			if ($plugin['enabled'])
				require(LIB_DIR . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $plugin_name . '.php');

	$LMS->ExecHook('access_table_init');

	$LMS->executeHook('access_table_initialized');

	LMSConfig::getConfig(array(
		'force' => true,
		'force_user_rights_only' => true,
		'user_id' => $AUTH->id,
	));

	$module = isset($_GET['m']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['m']) : '';
	$deny = $allow = FALSE;

	$res = $LMS->ExecHook('module_load_before', array('module' => $module));
	if (array_key_exists('abort', $res) && $res['abort']) {
		$SESSION->close();
		$DB->Destroy();
		die;
	}
	$module = $res['module'];

	if ($AUTH->passwdrequiredchange)
		$module = 'chpasswd';

	if ($module == '')
		$module = ConfigHelper::getConfig('phpui.default_module');

	$module_dir = null;
	foreach ($modules_dirs as $suspected_module_dir)
		if (file_exists($suspected_module_dir . DIRECTORY_SEPARATOR . $module . '.php')) {
			$module_dir = $suspected_module_dir;
			break;
		}

	if ($module_dir !== null)
	{
		$global_allow = !$AUTH->id || (!empty($global_access_regexp) && preg_match('/' . $global_access_regexp . '/i', $module));

		if ($AUTH->id && ($rights = $LMS->GetUserRights($AUTH->id)))
			$allow = $access->checkRights($module, $rights, $global_allow);

		if ($SYSLOG)
			$SYSLOG->NewTransaction($module);

		if ($global_allow || $allow) {
			$layout['module'] = $module;
			$LMS->InitUI();
			$LMS->executeHook($module.'_on_load');
			include($module_dir . DIRECTORY_SEPARATOR . $module . '.php');
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
