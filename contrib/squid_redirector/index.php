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

// REPLACE THIS WITH PATH TO YOU CONFIG FILE

$CONFIG_FILE = '/etc/lms/lms.ini';

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************

define('CONFIG_FILE', $CONFIG_FILE);

// Parse configuration file
$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (! $CONFIG['directories']['sys_dir'] ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['backup_dir'] = (! $CONFIG['directories']['backup_dir'] ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'backups' : $CONFIG['directories']['backup_dir']);
$CONFIG['directories']['lib_dir'] = (! $CONFIG['directories']['lib_dir'] ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['modules_dir'] = (! $CONFIG['directories']['modules_dir'] ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'modules' : $CONFIG['directories']['modules_dir']);
$CONFIG['directories']['smarty_compile_dir'] = (! $CONFIG['directories']['smarty_compile_dir'] ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'templates_c' : $CONFIG['directories']['smarty_compile_dir']);
$CONFIG['directories']['smarty_templates_dir'] = (! $CONFIG['directories']['smarty_templates_dir'] ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'templates' : $CONFIG['directories']['smarty_templates_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('BACKUP_DIR', $CONFIG['directories']['backup_dir']);
define('MODULES_DIR', $CONFIG['directories']['modules_dir']);
define('SMARTY_COMPILE_DIR', $CONFIG['directories']['smarty_compile_dir']);
define('SMARTY_TEMPLATES_DIR', $CONFIG['directories']['smarty_templates_dir']);

// Load autoloader
$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/");
}

// Init database

$DB = null;

try {
    $DB = LMSDB::getInstance();
} catch (Exception $ex) {
    trigger_error($ex->getMessage(), E_USER_WARNING);
    // can't working without database
    die("Fatal error: cannot connect to database!" . PHP_EOL);
}

// Initialize templates engine
$SMARTY = new Smarty;
$SMARTY->addPluginsDir(LIB_DIR . DIRECTORY_SEPARATOR . 'SmartyPlugins');

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

// Initialize LMS class

$SESSION = new Session($DB, ConfigHelper::getConfig('phpui.timeout'));
$AUTH = new Auth($DB, $SESSION);
$SYSLOG = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

// set some template and layout variables

$SMARTY->assignByRef('_LANG', $_LANG);
$SMARTY->template_dir = getcwd();
$SMARTY->compile_dir = SMARTY_COMPILE_DIR;
include('lang.php');

$SMARTY->assignByRef('layout', $layout);

if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $forwarded_ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $nodeid = $LMS->GetNodeIDByIP($forwarded_ip['0']);
} else {
    $nodeid = $LMS->GetNodeIDByIP(str_replace('::ffff:', '', $_SERVER['REMOTE_ADDR']));
}

$customerid = $LMS->GetNodeOwner($nodeid);
$nodeinfo = $LMS->GetNode($nodeid);

if (isset($_GET['readed'])) {
    $DB->Execute('UPDATE nodes SET warning = 0 WHERE id = ?', array($nodeid));
    header('Location: '.$_GET['oldurl']);
} else {
    $customerinfo = $LMS->GetCustomer($customerid);
    $layout['oldurl'] = $_GET['oldurl'];
    $SMARTY->assign('customerinfo', $customerinfo);
    $SMARTY->assign('nodeinfo', $nodeinfo);
    $SMARTY->assign('layout', $layout);
    $SMARTY->display('message.html');
}
