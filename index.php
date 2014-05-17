<?php

/*
 * LMS version 1.11-git
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

$CONFIG_FILE = '/etc/lms/lms.ini';

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************

define('START_TIME', microtime(true));
define('LMS-UI', true);
ini_set('error_reporting', E_ALL&~E_NOTICE);

// find alternative config files:
if(is_readable('lms.ini'))
	$CONFIG_FILE = 'lms.ini';
elseif(is_readable('/etc/lms/lms-'.$_SERVER['HTTP_HOST'].'.ini'))
	$CONFIG_FILE = '/etc/lms/lms-'.$_SERVER['HTTP_HOST'].'.ini';
elseif(!is_readable($CONFIG_FILE))
	die('Unable to read configuration file ['.$CONFIG_FILE.']!'); 

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'].'/lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['doc_dir'] = (!isset($CONFIG['directories']['doc_dir']) ? $CONFIG['directories']['sys_dir'].'/documents' : $CONFIG['directories']['doc_dir']);
$CONFIG['directories']['cache_dir'] = (!isset($CONFIG['directories']['cache_dir']) ? $CONFIG['directories']['sys_dir'].'/cache' : $CONFIG['directories']['cache_dir']);
$CONFIG['directories']['modules_dir'] = (!isset($CONFIG['directories']['modules_dir']) ? $CONFIG['directories']['sys_dir'].'/modules' : $CONFIG['directories']['modules_dir']);
$CONFIG['directories']['backup_dir'] = (!isset($CONFIG['directories']['backup_dir']) ? $CONFIG['directories']['sys_dir'].'/backups' : $CONFIG['directories']['backup_dir']);
$CONFIG['directories']['config_templates_dir'] = (!isset($CONFIG['directories']['config_templates_dir']) ? $CONFIG['directories']['sys_dir'].'/config_templates' : $CONFIG['directories']['config_templates_dir']);
$CONFIG['directories']['smarty_compile_dir'] = (!isset($CONFIG['directories']['smarty_compile_dir']) ? $CONFIG['directories']['sys_dir'].'/templates_c' : $CONFIG['directories']['smarty_compile_dir']);
$CONFIG['directories']['smarty_templates_dir'] = (!isset($CONFIG['directories']['smarty_templates_dir']) ? $CONFIG['directories']['sys_dir'].'/templates' : $CONFIG['directories']['smarty_templates_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('DOC_DIR', $CONFIG['directories']['doc_dir']);
define('CACHE_DIR', $CONFIG['directories']['cache_dir']);
define('BACKUP_DIR', $CONFIG['directories']['backup_dir']);
define('MODULES_DIR', $CONFIG['directories']['modules_dir']);
define('SMARTY_COMPILE_DIR', $CONFIG['directories']['smarty_compile_dir']);
define('SMARTY_TEMPLATES_DIR', $CONFIG['directories']['smarty_templates_dir']);

// Do some checks and load config defaults

require_once(LIB_DIR.'/checkdirs.php');
require_once(LIB_DIR.'/config.php');

/**
 * Autoloader function.
 * 
 * Loads classes "on the fly". Require or incluse statements are no longer needed.
 * Class name should be the same as name of file where it is stored. Class files
 * should have ".php" extension. Already known class paths are stored in special
 * cache file. That cache file is stored in CACHE_DIR path, by default in 
 * SYS_DIR/cache, so that path should be writeable for apache. You can change that
 * path in lms.ini file in directories section. You should clear cache file each 
 * time you move class file to another location.
 * 
 * This function should be registered with spl_autoload_register function before
 * first usage.
 * 
 * @param string $class Class name
 * @package LMS
 */
function application_autoloader($class) {
    
        $base_classes = array(
                'LMSDB_common' => 'LMSDB_common.class.php',
                'LMSDB_driver_mysql' => 'LMSDB_driver_mysql.class.php',
                'LMSDB_driver_mysqli' => 'LMSDB_driver_mysqli.class.php',
                'LMSDB_driver_postgres' => 'LMSDB_driver_postgres.class.php',
                'LMS' => 'LMS.class.php',
                'Auth' => 'Auth.class.php',
                'ExecStack' => 'ExecStack.class.php',
                'Session' => 'Session.class.php',
                'Sysinfo' => 'Sysinfo.class.php',
                'TCPDFpl' => 'tcpdf.php',
                'Smarty' => 'Smarty/Smarty.class.php',
                'SmartyBC' => 'Smarty/SmartyBC.class.php',
                'Cezpdf' => 'ezpdf/class.ezpdf.php',
                'Cpdf' => 'ezpdf/class.pdf.php',
                'HTML2PDF' => 'html2pdf/html2pdf.class.php',
                'TCPDF' => 'tcpdf/tcpdf.php'
        );
        
        if (array_key_exists($class, $base_classes)) {
                require_once LIB_DIR . DIRECTORY_SEPARATOR . $base_classes[$class];
        } else {
                // set cache file path
                $cache_file = CACHE_DIR . "/classpaths.cache";
                // read cache
                $path_cache = (file_exists($cache_file)) ? unserialize(file_get_contents($cache_file)) : array();
                // create empty cache container if cache is empty
                if (!is_array($path_cache)) {
                        $path_cache = array();
                }

                // check if class path exists in cache
                if (array_key_exists($class, $path_cache)) {
                        // try to load file
                        if (file_exists($path_cache[$class])) {
                                require_once $path_cache[$class];
                        }
                } else {
                        // try to find class file in LIB_DIR
                        $directories = new RecursiveDirectoryIterator(LIB_DIR);
                        $suspicious_file_names = array(
                                $class.'.php',
                                $class.'.class.php',
                                strtolower($class).'.php',
                                strtolower($class).'.class.php',
                                strtoupper($class).'.php',
                                strtoupper($class).'.class.php',
                        );
                        foreach (new RecursiveIteratorIterator($directories) as $file) {
                                if (in_array($file->getFilename(), $suspicious_file_names)) {
                                        // get class file path
                                        $full_path = $file->getRealPath();
                                        // store path in cache
                                        $path_cache[$class] = $full_path;
                                        // load class file
                                        require_once $full_path;
                                        break;
                                }
                        }
                }
                // serialize cache
                $serialized_paths = serialize($path_cache);
                // if cache changed save it
                if ($serialized_paths != $path_cache) {
                        file_put_contents($cache_file, $serialized_paths);
                }
        }
}

// register autoloader
spl_autoload_register('application_autoloader');

// Init database

$_DBTYPE = $CONFIG['database']['type'];
$_DBHOST = $CONFIG['database']['host'];
$_DBUSER = $CONFIG['database']['user'];
$_DBPASS = $CONFIG['database']['password'];
$_DBNAME = $CONFIG['database']['database'];
$_DBDEBUG = (isset($CONFIG['database']['debug']) ? chkconfig($CONFIG['database']['debug']) : FALSE);

require(LIB_DIR.'/LMSDB.php');

$DB = DBInit($_DBTYPE, $_DBHOST, $_DBUSER, $_DBPASS, $_DBNAME, $_DBDEBUG);

if(!$DB)
{
	// can't working without database
	die();
}

// Call any of upgrade process before anything else

require_once(LIB_DIR.'/upgradedb.php');

// Initialize templates engine (must be before locale settings)

require_once(LIB_DIR.'/Smarty/Smarty.class.php');

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

// Read configuration of LMS-UI from database

if($cfg = $DB->GetAll('SELECT section, var, value FROM uiconfig WHERE disabled=0'))
	foreach($cfg as $row)
		$CONFIG[$row['section']][$row['var']] = $row['value'];

// Redirect to SSL

$_FORCE_SSL = (isset($CONFIG['phpui']['force_ssl']) ? chkconfig($CONFIG['phpui']['force_ssl']) : FALSE);

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
require_once(LIB_DIR.'/LMS.class.php');
require_once(LIB_DIR.'/Auth.class.php');
require_once(LIB_DIR.'/accesstable.php');
require_once(LIB_DIR.'/Session.class.php');
require_once(LIB_DIR . '/SYSLOG.class.php');

if (check_conf('phpui.logging') && class_exists('SYSLOG'))
	$SYSLOG = new SYSLOG($DB);
else
	$SYSLOG = null;

// Initialize Session, Auth and LMS classes

$SESSION = new Session($DB, $CONFIG['phpui']['timeout']);
$AUTH = new Auth($DB, $SESSION, $SYSLOG);
if ($SYSLOG)
	$SYSLOG->SetAuth($AUTH);
$LMS = new LMS($DB, $AUTH, $CONFIG, $SYSLOG);
$LMS->ui_lang = $_ui_language;
$LMS->lang = $_language;

// Initialize Swekey class

if (chkconfig($CONFIG['phpui']['use_swekey'])) {
	require_once(LIB_DIR . '/swekey/lms_integration.php');
	$LMS_SWEKEY = new LmsSwekeyIntegration($DB, $AUTH, $LMS);
	$SMARTY->assign('lms_swekey', $LMS_SWEKEY->GetIntegrationScript($AUTH->id));
}

// Set some template and layout variables

$SMARTY->setTemplateDir(null);
$custom_templates_dir = get_conf('phpui.custom_templates_dir');
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
$SMARTY->debugging = check_conf('phpui.smarty_debug');

$layout['logname'] = $AUTH->logname;
$layout['logid'] = $AUTH->id;
$layout['lmsdbv'] = $DB->_version;
$layout['smarty_version'] = SMARTY_VERSION;
$layout['hostname'] = hostname();
$layout['lmsv'] = '1.11-git';
//$layout['lmsvr'] = $LMS->_revision.'/'.$AUTH->_revision;
$layout['lmsvr'] = '';
$layout['dberrors'] =& $DB->errors;
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
	$plugins = preg_split('/[;,\s\t\n]+/', $CONFIG['phpui']['plugins'], -1, PREG_SPLIT_NO_EMPTY);
	if (!empty($plugins))
		foreach ($plugins as $plugin_name)
			if(is_readable(LIB_DIR . '/plugins/' . $plugin_name . '.php'))
				require LIB_DIR . '/plugins/' . $plugin_name . '.php';

	$res = $LMS->ExecHook('access_table_init', array('accesstable' => $access['table']));
	if (isset($res['accesstable']))
		$access['table'] = $res['accesstable'];

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
		$module = $CONFIG['phpui']['default_module'];
	}

	if (file_exists(MODULES_DIR.'/'.$module.'.php'))
	{
		$global_allow = !$AUTH->id || (!empty($access['allow']) && preg_match('/'.$access['allow'].'/i', $module));

		if ($AUTH->id && ($rights = $LMS->GetUserRights($AUTH->id)))
			foreach ($rights as $level)
			{
				if ($level === 0) {
					$CONFIG['privileges']['superuser'] = true;
				}

				if (!$global_allow && !$deny && isset($access['table'][$level]['deny_reg']))
					$deny = (bool) preg_match('/'.$access['table'][$level]['deny_reg'].'/i', $module);
				elseif (!$allow && isset($access['table'][$level]['allow_reg']))
					$allow = (bool) preg_match('/'.$access['table'][$level]['allow_reg'].'/i', $module);

				if (isset($access['table'][$level]['privilege']))
					$CONFIG['privileges'][$access['table'][$level]['privilege']] = TRUE;
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
