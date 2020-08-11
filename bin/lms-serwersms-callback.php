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

// REPLACE THIS WITH PATH TO YOUR CONFIG FILE
$CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************


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
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);

// Load autoloader
$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/" . PHP_EOL);
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

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

$allow_from = ConfigHelper::getConfig(
    'sms-customers.callback_allow_from',
    ConfigHelper::getConfig('sms.callback_allow_from', null, true)
);

if ($allow_from) {
    // delete ipv6 prefix if it's present:
    $ipaddr = str_replace('::ffff:', '', $_SERVER['REMOTE_ADDR']);

    if (!Utils::isAllowedIP($ipaddr, $allow_from)) {
        header('HTTP/1.1 403 Forbidden');
        die;
    }
}

$SYSLOG = SYSLOG::getInstance();

if (!isset($_GET['msgitemid'])) {
    header('Content-Type: text/plain');
    die('OK');
}

$msgitemid = intval($_GET['msgitemid']);

if (!isset($_GET['status'])) {
    header('Content-Type: text/plain');
    die('OK');
}

$status = strtolower($_GET['status']);

if (!preg_match('/^(doreczono|niedoreczono|niewyslano|oczekiwanie)$/', $status)) {
    header('Content-Type: text/plain');
    die('OK');
}

if (!isset($_GET['date'])) {
    header('Content-Type: text/plain');
    die('OK');
}

$date = strtotime(urldecode($_GET['date']));

switch ($status) {
    case 'doreczono':
        $DB->Execute(
            'UPDATE messageitems SET status = ?, lastdate = ? WHERE id = ?',
            array(MSG_DELIVERED, $date, $msgitemid)
        );
        break;
    case 'niedoreczono':
        $error = isset($_GET['error']) ? urldecode($_GET['error']) : '';
        $DB->Execute(
            'UPDATE messageitems SET status = ?, lastdate = ?, error = ? WHERE id = ?',
            array(MSG_ERROR, $date, $error, $msgitemid)
        );
        break;
    case 'niewyslano':
        $error = isset($_GET['error']) ? urldecode($_GET['error']) : '';
        $DB->Execute(
            'UPDATE messageitems SET status = ?, lastdate = ?, error = ? WHERE id = ?',
            array(MSG_ERROR, $date, $error, $msgitemid)
        );
        break;
    case 'oczekiwanie':
        $DB->Execute(
            'UPDATE messageitems SET lastdate = ? WHERE id = ?',
            array($date, $msgitemid)
        );
        break;
}

header('Content-Type: text/plain');
echo 'OK';

$DB->Destroy();
