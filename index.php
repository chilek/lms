<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************

define('START_TIME', microtime(true));
define('LMS-UI', true);
define('K_TCPDF_EXTERNAL_CONFIG', true);
define('K_TCPDF_CALLS_IN_HTML', true);
ini_set('error_reporting', E_ALL & ~E_NOTICE);

$CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

// find alternative config files:
if (is_readable('lms.ini')) {
    $CONFIG_FILE = 'lms.ini';
} elseif (is_readable(DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms-' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . '.ini')) {
    $CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms-' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . '.ini';
} elseif (is_readable(DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms-' . $_SERVER['HTTP_HOST'] . '.ini')) {
    $CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms-' . $_SERVER['HTTP_HOST'] . '.ini';
} elseif (!is_readable($CONFIG_FILE)) {
    die('Unable to read configuration file [' . $CONFIG_FILE . ']!');
}

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

// Init database

$DB = null;

try {
    $DB = LMSDB::getInstance();
} catch (Exception $ex) {
    trigger_error($ex->getMessage(), E_USER_WARNING);
    // can't work without database
    die("Fatal error: cannot connect to database!<BR>");
}

$api = isset($_GET['api']);

if (!$api) {
    // Call any of upgrade process before anything else

    $layout['dbschversion'] = $DB->UpgradeDb();

    // Initialize templates engine (must be before locale settings)
    $SMARTY = new LMSSmarty;

    // test for proper version of Smarty

    if (defined('Smarty::SMARTY_VERSION')) {
        $ver_chunks = preg_split('/[- ]/', preg_replace('/^smarty-/i', '', Smarty::SMARTY_VERSION), -1, PREG_SPLIT_NO_EMPTY);
    } else {
        $ver_chunks = null;
    }
    if (count($ver_chunks) < 1 || version_compare('3.1', $ver_chunks[0]) > 0) {
        die('<B>Wrong version of Smarty engine! We support only Smarty-3.x greater than 3.1.</B>');
    }

    define('SMARTY_VERSION', $ver_chunks[0]);

    // add LMS's custom plugins directory
    $SMARTY->addPluginsDir(LIB_DIR . DIRECTORY_SEPARATOR . 'SmartyPlugins');

    $SMARTY->setMergeCompiledIncludes(true);

    $SMARTY->setDefaultResourceType('extendsall');

    // uncomment this line if you're not gonna change template files no more
    //$SMARTY->compile_check = false;
}

// Redirect to SSL

$_FORCE_SSL = ConfigHelper::checkConfig('phpui.force_ssl');
if ($_FORCE_SSL && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on')) {
    header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
    exit(0);
}

// Include required files (including sequence is important)

$_SERVER['REMOTE_ADDR'] = str_replace("::ffff:", "", $_SERVER['REMOTE_ADDR']);

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'checkip.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'accesstable.php');

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes

$SESSION = new Session(
    $DB,
    ConfigHelper::getConfig('phpui.timeout'),
    ConfigHelper::getConfig('phpui.settings_timeout')
);
// new browser tab can be opened as hidden or tabid of new tab can be not initialised
// so we have to be careful and handle 'backto' session variable in special way and
// correct this variable when new tab id has been determined before the moment
if (isset($_GET['oldtabid']) && isset($_GET['tabid']) && isset($_GET['oldbackto']) && isset($_GET['backto'])
    && preg_match('/^[0-9]+$/', $_GET['oldtabid'])
    && preg_match('/^[0-9]+$/', $_GET['tabid'])) {
    $SESSION->fixBackTo($_GET['oldtabid'], base64_decode($_GET['oldbackto']), $_GET['tabid'], base64_decode($_GET['backto']));
    $SESSION->close();
    header('Content-Type: application/json');
    die('[]');
}
$AUTH = new Auth($DB, $SESSION);
$LMS = new LMS($DB, $AUTH, $SYSLOG);

Localisation::initDefaultCurrency();

$plugin_manager = new LMSPluginManager();
$LMS->setPluginManager($plugin_manager);

if (!$api) {
    $SMARTY->setPluginManager($plugin_manager);

    // Set some template and layout variables

    $SMARTY->setTemplateDir(null);
    $custom_templates_dir = ConfigHelper::getConfig('phpui.custom_templates_dir');
    if (!empty($custom_templates_dir) && file_exists(SMARTY_TEMPLATES_DIR . DIRECTORY_SEPARATOR . $custom_templates_dir)
        && !is_file(SMARTY_TEMPLATES_DIR . DIRECTORY_SEPARATOR . $custom_templates_dir)) {
        $SMARTY->AddTemplateDir(SMARTY_TEMPLATES_DIR . DIRECTORY_SEPARATOR . $custom_templates_dir);
    }
    $SMARTY->AddTemplateDir(
        array(
            SMARTY_TEMPLATES_DIR . DIRECTORY_SEPARATOR . 'default',
            SMARTY_TEMPLATES_DIR,
        )
    );
    $SMARTY->setCompileDir(SMARTY_COMPILE_DIR);
    $SMARTY->debugging = ConfigHelper::checkConfig('phpui.smarty_debug');

    $layout['smarty_version'] = SMARTY_VERSION;
}

$layout['logname'] = $AUTH->logname;
$layout['logid'] = Auth::GetCurrentUser();
$layout['lmsdbv'] = $DB->GetVersion();
$layout['hostname'] = hostname();
$layout['lmsv'] = LMS::SOFTWARE_VERSION;
$layout['lmsvr'] = LMS::getSoftwareRevision();
$layout['dberrors'] = &$DB->GetErrors();
$layout['dbdebug'] = isset($_DBDEBUG) ? $_DBDEBUG : false;
$layout['popup'] = isset($_GET['popup']) ? true : false;

if (!$api) {
    $SMARTY->assignByRef('layout', $layout);
}

$error = null; // initialize error variable needed for (almost) all modules
$warning = null; // initialize warning variable needed for (almost) all modules

// Load menu

if (!$layout['popup'] && !$api) {
    require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'menu.php');

    $menu = $plugin_manager->executeHook('menu_initialized', $menu);

    $SMARTY->assignByRef('newmenu', $menu);
}

header('X-Powered-By: LMS/'.$layout['lmsv']);

$modules_dirs = array(MODULES_DIR);
$modules_dirs = $plugin_manager->executeHook('modules_dir_initialized', $modules_dirs);

$plugin_manager->executeHook('lms_initialized', $LMS);

if (!$api) {
    $plugin_manager->executeHook('smarty_initialized', $SMARTY);
}

$documents_dirs = array(DOC_DIR);
$documents_dirs = $plugin_manager->executeHook('documents_dir_initialized', $documents_dirs);

// Check privileges and execute modules
if ($AUTH->islogged) {
    $qs_properties = $SESSION->get_persistent_setting('qs-properties');
    if (empty($qs_properties)) {
        $qs_properties = array();
    } else {
        foreach ($qs_properties as $mode => $properties) {
            $qs_properties[$mode] = array_flip(explode(',', $properties));
        }
    }

    $user_divisions = $LMS->GetDivisions(array('userid' => Auth::GetCurrentUser()));
    $user_divisions_ids = array_keys($user_divisions);
    $persistentDivisionContext = $SESSION->get_persistent_setting('division_context');
    $tabDivisionContext = $SESSION->get('division_context', true);
    // check if user has any division
    if (!$user_divisions) {
        $SESSION->save_persistent_setting('division_context', '');
        $tabDivisionContext = '';
        $SESSION->save('division_context', $tabDivisionContext, true);
    } else {
        $user_division = reset($user_divisions);
        if (count($user_divisions) > 1) {
            if (!isset($persistentDivisionContext)
                || (!in_array($persistentDivisionContext, $user_divisions_ids)
                    && !empty($persistentDivisionContext))) {
                $SESSION->save_persistent_setting('division_context', '');
                $persistentDivisionContext = $SESSION->get_persistent_setting('division_context');
            }
            if (!isset($tabDivisionContext)
                || (!in_array($persistentDivisionContext, $user_divisions_ids)
                    && !empty($persistentDivisionContext))) {
                $tabDivisionContext = $persistentDivisionContext;
                $SESSION->save('division_context', $tabDivisionContext, true);
            }
        } else {
            $SESSION->save_persistent_setting('division_context', $user_division['id']);
            $SESSION->save('division_context', $user_division['id'], true);
        }
    }
    $layout['division'] = $tabDivisionContext;

    if (!$api) {
        $SMARTY->assign('division_context', $tabDivisionContext);
        $SMARTY->assign('main_menu_sortable_order', $SESSION->get_persistent_setting('main-menu-order'));
        $SMARTY->assign('qs_properties', $qs_properties);

        $qs_fields = $SESSION->get_persistent_setting('qs-fields');
        if (empty($qs_fields)) {
            $qs_fields = array();
        } else {
            $qs_fields = array_flip(explode(',', $qs_fields));
        }
        $SMARTY->assign('qs_fields', $qs_fields);

        if (isset($_GET['backid'])) {
            $SESSION->save('backid', $_GET['backid']);
        }
        if ($backid = $SESSION->get('backid')) {
            $SMARTY->assign('backid', $backid);
        }
    }

    // Load plugin files and register hook callbacks
    $plugins = $plugin_manager->getAllPluginInfo(LMSPluginManager::OLD_STYLE);
    if (!empty($plugins)) {
        foreach ($plugins as $plugin_name => $plugin) {
            if ($plugin['enabled']) {
                require(LIB_DIR . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $plugin_name . '.php');
            }
        }
    }

    $LMS->ExecHook('access_table_init');

    $LMS->executeHook('access_table_initialized');

    LMSConfig::getConfig(array(
        'force' => true,
        'force_user_rights_only' => true,
        'user_id' => Auth::GetCurrentUser(),
    ));

    LMSConfig::getConfig(array(
        'force' => true,
        'force_user_settings_only' => true,
        'user_id' => Auth::GetCurrentUser(),
    ));

    Localisation::initDefaultCurrency();

    $module = isset($_GET['m']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['m']) : '';
    $deny = $allow = false;

    $res = $LMS->ExecHook('module_load_before', array('module' => $module));
    if (array_key_exists('abort', $res) && $res['abort']) {
        $SESSION->close();
        $DB->Destroy();
        die;
    }
    $module = $res['module'];

    if ($module != 'logout') {
        if ($AUTH->requiredPasswordChange()) {
            $module = 'chpasswd';
        } elseif ($AUTH->requiredTwoFactorAuthChange()) {
            $module = 'twofactorauthedit';
        }
    }

    if ($module == '') {
        $module = ConfigHelper::getConfig('phpui.default_module');
    }

    $module_dir = null;
    foreach ($modules_dirs as $suspected_module_dir) {
        if (file_exists($suspected_module_dir . DIRECTORY_SEPARATOR . $module . '.php')) {
            $module_dir = $suspected_module_dir;
            break;
        }
    }

    if ($module_dir !== null) {
        $global_allow = !Auth::GetCurrentUser() || (!empty($global_access_regexp) && preg_match('/' . $global_access_regexp . '/i', $module));

        if (Auth::GetCurrentUser() && ($rights = $LMS->GetUserRights(Auth::GetCurrentUser()))) {
            $allow = $access->checkRights($module, $rights, $global_allow);
        }

        if ($SYSLOG) {
            $SYSLOG->NewTransaction($module);
        }

        // everyone should have access to documentation
        $rights[] = 'documentation';

        $access->applyMenuPermissions($menu, $rights);

        if ($global_allow || $allow) {
            $layout['module'] = $module;

            $SESSION->save('module', $module);

            if (!$api) {
                $SMARTY->assign('url', 'http' . ($_SERVER['HTTPS'] == 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST']
                    . substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1));

                // get all persistent filters
                $SMARTY->assign('persistent_filters', $SESSION->getAllPersistentFilters());

                // persister filter apply
                if (isset($_GET['persistent-filter'])) {
                    $filter = $SESSION->getPersistentFilter($_GET['persistent-filter']);
                    $filter['persistent_filter'] = $_GET['persistent-filter'];
                    $SESSION->saveFilter($filter);
                } else {
                    $filter = $SESSION->getFilter();
                }
                $SMARTY->assignByRef('filter', $filter);

                // restore selected persistent filter info
                if (isset($filter['persistent_filter'])) {
                    $SMARTY->assign('persistent_filter', $filter['persistent_filter']);
                }

                // tab visibility toggle support
                $resource_tabs = $SESSION->get_persistent_setting($layout['module'] . '-resource-tabs');
                $SMARTY->assign('serialized_resource_tabs', $resource_tabs);
                if (!empty($resource_tabs)) {
                    $resource_tabs = explode(';', $resource_tabs);
                    $all_tabs = array();
                    foreach ($resource_tabs as $resource_tab) {
                        list ($resource_tab_id, $resource_tab_state) = explode(':', $resource_tab);
                        $all_tabs[$resource_tab_id] = intval($resource_tab_state) != 0;
                    }
                    $resource_tabs = $all_tabs;
                } else {
                    $resource_tabs = array();
                }
                $SMARTY->assign('resource_tabs', $resource_tabs);

                // preset error and warning smarty variable
                // they can be easily filled later in modules
                $SMARTY->assignByRef('error', $error);
                $SMARTY->assignByRef('warning', $warning);

                // preload warnings from submitted form to $warning variable
                if (isset($_GET['warning'])) {
                    $warnings = $_GET['warning'];
                } elseif (isset($_POST['warning'])) {
                    $warnings = $_POST['warning'];
                }
            } else {
                // persistent filter ajax management
                if (isset($_GET['persistent-filter']) && isset($_GET['action'])) {
                    switch ($_GET['action']) {
                        case 'update':
                            $SESSION->savePersistentFilter($_GET['persistent-filter'], $SESSION->getFilter());
                            $persistent_filters = $SESSION->getAllPersistentFilters();
                            $SESSION->close();
                            header('Content-type: application/json');
                            die(json_encode($persistent_filters));
                        break;
                        case 'delete':
                            $SESSION->removePersistentFilter($_GET['persistent-filter']);
                            $persistent_filters = $SESSION->getAllPersistentFilters();
                            $SESSION->close();
                            header('Content-type: application/json');
                            die(json_encode($persistent_filters));
                        break;
                    }
                }
            }

            $LMS->InitUI();
            $LMS->executeHook($module.'_on_load');

            try {
                include($module_dir . DIRECTORY_SEPARATOR . $module . '.php');
            } catch (Exception $e) {
                if (!$api) {
                    $SMARTY->display('header.html');
                    echo '<div class="bold">' . $e->getFile() . '[' . $e->getLine() . ']: <span class="red">'
                        . str_replace("\n", '<br>', $e->getMessage())
                        . '</span></div>';
                    $SMARTY->display('footer.html');
                }
                die;
            }
        } else {
            if ($SYSLOG) {
                $SYSLOG->AddMessage(
                    SYSLOG::RES_USER,
                    SYSLOG::OPER_USERNOACCESS,
                    array(SYSLOG::RES_USER => Auth::GetCurrentUser())
                );
            }
            if (!$api) {
                $SMARTY->display('noaccess.html');
            }
        }
    } else {
        $layout['module'] = 'notfound';
        $layout['pagetitle'] = trans('Error!');

        if (!$api) {
            $SMARTY->assign('layout', $layout);
            $SMARTY->assign('server', $_SERVER);
            $SMARTY->display('notfound.html');
        }
    }

    if ($SESSION->get('lastmodule') != $module) {
        $SESSION->save('lastmodule', $module);
    }
} else {
    if (!$api) {
        $SMARTY->assign('error', $AUTH->error);
        $SMARTY->assign('target', '?'.$_SERVER['QUERY_STRING']);
        if ($AUTH->authCodeRequired()) {
            $SMARTY->display('twofactorauth/twofactorauthcode.html');
        } else {
            $SMARTY->display('login.html');
        }
    }
}

$SESSION->close();
