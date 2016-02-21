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
 */

if (empty($_POST)) {
	echo "This is the swekey JSON server.<br />It should be called using a http POST request.";
	exit;
}

$CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);

// Load autloader
$composer_autoload_path = VENDOR_DIR . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/");
}

// Do some checks and load config defaults
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'config.php');

// Init database
$DB = null;

try {
	$DB = LMSDB::getInstance();
} catch (Exception $ex) {
	trigger_error($ex->getMessage(), E_USER_WARNING);

	// can't working without database
	die("Fatal error: cannot connect to database!");
}

// Include required files
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'SYSLOG.class.php');

if (ConfigHelper::checkConfig('phpui.logging') && class_exists('SYSLOG'))
	$SYSLOG = new SYSLOG($DB);
else
	$SYSLOG = null;

// Initialize Session, Auth and LMS classes
$SESSION = new Session($DB, ConfigHelper::getConfig('phpui.timeout'));
$AUTH = new Auth($DB, $SESSION, $SYSLOG);
if ($SYSLOG)
	$SYSLOG->SetAuth($AUTH);
$LMS = new LMS($DB, $AUTH, $SYSLOG);
$LMS->lang = $_language;

// Initialize Swekey class
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'swekey'  . DIRECTORY_SEPARATOR . 'lms_integration.php');

if (session_id() == '')
	session_start();

$JSON_SWEKEY = new LmsSwekeyIntegration($DB, $AUTH, $LMS);
$result = $JSON_SWEKEY->AjaxHandler($_POST);

echo json_encode($result);
exit;

?>
