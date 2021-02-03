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

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['backup_dir'] = (!isset($CONFIG['directories']['backup_dir']) ? $CONFIG['directories']['sys_dir'] . '/backups' : $CONFIG['directories']['backup_dir']);
$CONFIG['directories']['doc_dir'] = (!isset($CONFIG['directories']['doc_dir']) ? $CONFIG['directories']['sys_dir'] . '/documents' : $CONFIG['directories']['doc_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . '/lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['modules_dir'] = (!isset($CONFIG['directories']['modules_dir']) ? $CONFIG['directories']['sys_dir'] . '/modules' : $CONFIG['directories']['modules_dir']);
$CONFIG['directories']['config_templates_dir'] = (!isset($CONFIG['directories']['config_templates_dir']) ? $CONFIG['directories']['sys_dir'] . '/config_templates' : $CONFIG['directories']['config_templates_dir']);
$CONFIG['directories']['smarty_compile_dir'] = (!isset($CONFIG['directories']['smarty_compile_dir']) ? $CONFIG['directories']['sys_dir'] . '/templates_c' : $CONFIG['directories']['smarty_compile_dir']);
$CONFIG['directories']['smarty_templates_dir'] = (!isset($CONFIG['directories']['smarty_templates_dir']) ? $CONFIG['directories']['sys_dir'] . '/templates' : $CONFIG['directories']['smarty_templates_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('DOC_DIR', $CONFIG['directories']['doc_dir']);
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

// Do some checks and load config defaults
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'checkdirs.php');

// Init database

$DB = null;

try {
    $DB = LMSDB::getInstance();
} catch (Exception $ex) {
    trigger_error($ex->getMessage(), E_USER_WARNING);
    // can't working without database
    die("Fatal error: cannot connect to database!" . PHP_EOL);
}

// Call any of upgrade process before anything else

$DB->UpgradeDb();

// test for proper version of Smarty

if (defined('Smarty::SMARTY_VERSION')) {
    $ver_chunks = preg_split('/[- ]/', Smarty::SMARTY_VERSION);
} else {
    $ver_chunks = null;
}
if (count($ver_chunks) != 2 || version_compare('3.0', $ver_chunks[1]) > 0) {
    die('<B>Wrong version of Smarty engine! We support only Smarty-3.x greater than 3.0.</B>');
}

define('SMARTY_VERSION', $ver_chunks[1]);

// system localization

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');

// Redirect to SSL
$_FORCE_SSL = ConfigHelper::checkConfig('phpui.force_ssl');

if ($_FORCE_SSL && $_SERVER['HTTPS'] != 'on') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit(0);
}

// EXPERIMENTAL CODE! USE WITH CAUTION ;-)

$_LMSDIR = dirname(__FILE__);

$ExecStack = new ExecStack((isset($_GET['m']) ? $_GET['m'] : null), (isset($_GET['a']) ? $_GET['a'] : null), $_LMSDIR . '/modules/');

// don't use foreach() below, because privileges checking action
// will probably need to remove some actions from stack
// Note: that action should be executed first

while (list(, $execute) = each($ExecStack->_EXECSTACK['actions'])) {
    // do include once, because testing that language for executed module has been already loaded
    // will take some time, so let's PHP decide if we already loaded it or what...

    @include_once($_LMSDIR . '/modules/' . $execute['module'] . '/lang/' . $_language . '.php');
    @include_once($_LMSDIR . '/modules/' . $execute['module'] . '/modinit.php');

    // execute action

    if ($ExecStack->needExec($execute['module'], $execute['action'])) {
        include($_LMSDIR . '/modules/' . $execute['module'] . '/actions/' . $execute['action'] . '.php');
    }
}

foreach ($ExecStack->_EXECSTACK['templates'] as $step => $execute) {
    $SMARTY->display($_LMSDIR . '/modules/' . $execute['module'] . '/templates/' . $execute['template'] . '.html');
}

$DB->Destroy();


echo '<PRE>';
print_r($ExecStack);
