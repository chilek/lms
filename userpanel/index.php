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

// REPLACE THIS WITH PATH TO YOUR CONFIG FILE

$CONFIG_FILE = (is_readable('lms.ini')) ? 'lms.ini' : DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************

ini_set('session.name', 'LMSSESSIONID');
ini_set('error_reporting', E_ALL&~E_NOTICE);

// find alternative config files:
if (is_readable('lms.ini')) {
    $CONFIG_FILE = 'lms.ini';
} elseif (is_readable(DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms-' . $_SERVER['HTTP_HOST'] . '.ini')) {
    $CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms-' . $_SERVER['HTTP_HOST'] . '.ini';
} elseif (is_readable('..' . DIRECTORY_SEPARATOR .'lms.ini')) {
    $CONFIG_FILE = '..' . DIRECTORY_SEPARATOR .'lms.ini';
} elseif (!is_readable($CONFIG_FILE)) {
    die('Unable to read configuration file ['.$CONFIG_FILE.']!');
}

define('CONFIG_FILE', $CONFIG_FILE);

// Parse configuration file
$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['userpanel_dir'] = (!isset($CONFIG['directories']['userpanel_dir']) ? getcwd() : $CONFIG['directories']['userpanel_dir']);
$CONFIG['directories']['modules_dir'] = (!isset($CONFIG['directories']['modules_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'modules' : $CONFIG['directories']['modules_dir']);
$CONFIG['directories']['smarty_compile_dir'] = $CONFIG['directories']['userpanel_dir'] . DIRECTORY_SEPARATOR . 'templates_c';
$CONFIG['directories']['plugin_dir'] = (!isset($CONFIG['directories']['plugin_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'plugins' : $CONFIG['directories']['plugin_dir']);
$CONFIG['directories']['plugins_dir'] = $CONFIG['directories']['plugin_dir'];
$CONFIG['directories']['doc_dir'] = (!isset($CONFIG['directories']['doc_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'documents' : $CONFIG['directories']['doc_dir']);
$CONFIG['directories']['vendor_dir'] = (!isset($CONFIG['directories']['vendor_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'vendor' : $CONFIG['directories']['vendor_dir']);

define('USERPANEL_DIR', $CONFIG['directories']['userpanel_dir']);
define('USERPANEL_LIB_DIR', USERPANEL_DIR . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR);
define('USERPANEL_MODULES_DIR', USERPANEL_DIR . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('DOC_DIR', $CONFIG['directories']['doc_dir']);
define('MODULES_DIR', $CONFIG['directories']['modules_dir']);
define('SMARTY_COMPILE_DIR', $CONFIG['directories']['smarty_compile_dir']);
define('PLUGIN_DIR', $CONFIG['directories']['plugin_dir']);
define('PLUGINS_DIR', $CONFIG['directories']['plugin_dir']);
define('VENDOR_DIR', $CONFIG['directories']['vendor_dir']);

define('K_TCPDF_EXTERNAL_CONFIG', true);

// include required files

// Load autoloader
$composer_autoload_path = VENDOR_DIR . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/");
}

require_once(USERPANEL_LIB_DIR . DIRECTORY_SEPARATOR . 'checkdirs.php');

// Initialize database

$DB = null;

try {
    $DB = LMSDB::getInstance();
} catch (Exception $ex) {
    trigger_error($ex->getMessage(), E_USER_WARNING);
    // can't working without database
    die("Fatal error: cannot connect to database!<BR>");
}

// Initialize templates engine (must be before locale settings)
$SMARTY = new LMSSmarty;

// test for proper version of Smarty

if (constant('Smarty::SMARTY_VERSION')) {
    $ver_chunks = preg_split('/[- ]/', preg_replace('/^smarty-/i', '', Smarty::SMARTY_VERSION), -1, PREG_SPLIT_NO_EMPTY);
} else {
    $ver_chunks = null;
}

if (count($ver_chunks) < 1 || version_compare('3.1', $ver_chunks[0]) > 0) {
    die('<B>Wrong version of Smarty engine! We support only Smarty-3.x greater than 3.0.</B>');
}

define('SMARTY_VERSION', $ver_chunks[0]);

// add LMS's custom plugins directory
$SMARTY->addPluginsDir(LIB_DIR . DIRECTORY_SEPARATOR . 'SmartyPlugins');

// Redirect to SSL
$_FORCE_SSL = ConfigHelper::checkConfig('userpanel.force_ssl', ConfigHelper::getConfig('phpui.force_ssl'));
if ($_FORCE_SSL && $_SERVER['HTTPS'] != 'on') {
     header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
     exit(0);
}

$_TIMEOUT = ConfigHelper::getConfig('userpanel.timeout');

// Include required files (including sequence is important)
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

$_SERVER['REMOTE_ADDR'] = str_replace("::ffff:", "", $_SERVER['REMOTE_ADDR']);

$AUTH = null;
$SYSLOG = SYSLOG::getInstance();
if ($SYSLOG) {
    $SYSLOG->NewTransaction('userpanel');
}

$LMS = new LMS($DB, $AUTH, $SYSLOG);

require_once(USERPANEL_LIB_DIR . DIRECTORY_SEPARATOR . 'Session.class.php');
require_once(USERPANEL_LIB_DIR . DIRECTORY_SEPARATOR . 'Userpanel.class.php');
require_once(USERPANEL_LIB_DIR . DIRECTORY_SEPARATOR . 'ULMS.class.php');

Localisation::appendUiLanguage(USERPANEL_DIR . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'locale');

unset($LMS); // reset LMS class to enable wrappers for LMS older versions

$LMS = new ULMS($DB, $AUTH, $SYSLOG);

$plugin_manager = new LMSPluginManager();
$LMS->setPluginManager($plugin_manager);
$SMARTY->setPluginManager($plugin_manager);

// Load plugin files and register hook callbacks
$plugins = $plugin_manager->getAllPluginInfo(LMSPluginManager::OLD_STYLE);
if (!empty($plugins)) {
    foreach ($plugins as $plugin_name => $plugin) {
        if ($plugin['enabled']) {
            require(LIB_DIR . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $plugin_name . '.php');
        }
    }
}

$SESSION = new Session($DB, $_TIMEOUT);
$USERPANEL = new USERPANEL($DB, $SESSION);
$LMS->ui_lang = Localisation::getCurrentUiLanguage();
$LMS->lang = Localisation::getCurrentSystemLanguage();
LMS::$currency = Localisation::getCurrentCurrency();

// Initialize modules

$enabled_modules = ConfigHelper::getConfig('userpanel.enabled_modules', null, true);
if (!is_null($enabled_modules)) {
    $enabled_modules = explode(',', $enabled_modules);
}

$modules_dirs = array(USERPANEL_MODULES_DIR);
$modules_dirs = $plugin_manager->executeHook('userpanel_modules_dir_initialized', $modules_dirs);
$USERPANEL->setModuleDirectories($modules_dir);

foreach ($modules_dirs as $suspected_module_dir) {
    $dh  = opendir($suspected_module_dir);
    while (false !== ($filename = readdir($dh))) {
        if ((is_null($enabled_modules) || in_array($filename, $enabled_modules)) && (preg_match('/^[a-zA-Z0-9]/', $filename))
            && (is_dir($suspected_module_dir . $filename)) && file_exists($suspected_module_dir . $filename . DIRECTORY_SEPARATOR . 'configuration.php')) {
            Localisation::appendUiLanguage($suspected_module_dir . $filename . DIRECTORY_SEPARATOR . 'locale');
            include($suspected_module_dir . $filename . DIRECTORY_SEPARATOR . 'configuration.php');
            if (is_dir($suspected_module_dir . $filename . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR)) {
                $plugins = glob($suspected_module_dir . $filename . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . '*.php');
                if (!empty($plugins)) {
                    foreach ($plugins as $plugin_name) {
                        if (is_readable($plugin_name)) {
                            include($plugin_name);
                        }
                    }
                }
            }
        }
    }
}

$SMARTY->assignByRef('LANGDEFS', $LANGDEFS);
$SMARTY->assignByRef('_ui_language', $LMS->ui_lang);
$SMARTY->assignByRef('_language', $LMS->lang);
$SMARTY->setTemplateDir(null);
$style = ConfigHelper::getConfig('userpanel.style', 'default');
$startupmodule = ConfigHelper::getConfig('userpanel.startup_module', 'info');
$SMARTY->addTemplateDir(array(
    USERPANEL_DIR . DIRECTORY_SEPARATOR . 'style' . DIRECTORY_SEPARATOR .  $style . DIRECTORY_SEPARATOR . 'templates',
    USERPANEL_DIR . DIRECTORY_SEPARATOR . 'templates',
));
$SMARTY->setCompileDir(SMARTY_COMPILE_DIR);
$SMARTY->debugging = ConfigHelper::checkConfig('phpui.smarty_debug');
require_once(USERPANEL_LIB_DIR . DIRECTORY_SEPARATOR . 'smarty_addons.php');

$layout['lmsdbv'] = $DB->GetVersion();
$layout['lmsv'] = LMS::SOFTWARE_VERSION;
$layout['lmsvr'] = LMS::getSoftwareRevision();
$layout['smarty_version'] = SMARTY_VERSION;
$layout['hostname'] = hostname();
$layout['dberrors'] =& $DB->GetErrors();

$SMARTY->assignByRef('modules', $USERPANEL->MODULES);
$SMARTY->assignByRef('layout', $layout);
$SMARTY->assign('page_header', ConfigHelper::getConfig('userpanel.page_header'));
$SMARTY->assign('company_logo', ConfigHelper::getConfig('userpanel.company_logo'));
$SMARTY->assign('timeout', $_TIMEOUT);

header('X-Powered-By: LMS/'.$layout['lmsv']);

$plugin_manager->executeHook('userpanel_lms_initialized', $LMS);

$plugin_manager->executeHook('userpanel_smarty_initialized', $SMARTY);

if ($SESSION->islogged) {
    $module = isset($_GET['m']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['m']) : '';

    if (isset($USERPANEL->MODULES[$module])) {
        $USERPANEL->MODULES[$module]['selected'] = true;
    }

    // Userpanel rights module
    $rights = $USERPANEL->GetCustomerRights($SESSION->id);
    $SMARTY->assign('rights', $rights);

    if (ConfigHelper::checkConfig('userpanel.hide_nodes_modules')) {
        if (!$DB->GetOne('SELECT COUNT(*) FROM vnodes WHERE ownerid = ? LIMIT 1', array($SESSION->id))) {
            $USERPANEL->RemoveModule('notices');
            $USERPANEL->RemoveModule('stats');
        }
    }

    // Userpanel popup for urgent notice
    $res = $LMS->ExecHook('userpanel_module_call_before');

    $LMS->executeHook('userpanel_' . $module . '_on_load');

    $module_dir = null;
    foreach ($modules_dirs as $suspected_module_dir) {
        if (file_exists($suspected_module_dir . $module . DIRECTORY_SEPARATOR . 'functions.php')
            && isset($USERPANEL->MODULES[$module])) {
            $module_dir = $suspected_module_dir;
            break;
        }
    }

    if ($module_dir !== null) {
        include($module_dir . $module . DIRECTORY_SEPARATOR . 'functions.php');

        $function = isset($_GET['f']) && $_GET['f']!='' ? $_GET['f'] : 'main';
        if (function_exists('module_'.$function)) {
            $to_execute = 'module_'.$function;
            $layout['userpanel_module'] = $module;
            $layout['userpanel_function'] = $function;

            $SMARTY->assign('callback_result', $USERPANEL->executeCallbacks($SMARTY));

            $to_execute();
        } else {
                $layout['error'] = trans('Function <b>$a</b> in module <b>$b</b> not found!', $function, $module);
                $SMARTY->display('error.html');
        }
    } elseif ($module=='') {
        if (!empty($module)) {
            header('Location: ?m=' . $module);
        } else {
            header('Location: ?m=' . $startupmodule);
        }
    } else {
        $layout['error'] = trans('Module <b>$a</b> not found!', $module);
        $SMARTY->display('error.html');
    }

    if (!isset($_SESSION['lastmodule']) || $_SESSION['lastmodule'] != $module) {
        $_SESSION['lastmodule'] = $module;
    }
} else {
        $SMARTY->assign('error', $SESSION->error);
        $SMARTY->assign('target', '?'.$_SERVER['QUERY_STRING']);
        $SMARTY->display('login.html');
}

$DB->Destroy();
