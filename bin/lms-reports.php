#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

ini_set('error_reporting', E_ALL & ~E_NOTICE);

$parameters = array(
    'config-file:' => 'C:',
    'quiet' => 'q',
    'help' => 'h',
    'version' => 'v',
    'overdue-events' => 'o',
    'overdue-events-all-mail:' => 'A:',
);

$long_to_shorts = array();
foreach ($parameters as $long => $short) {
    $long = str_replace(':', '', $long);
    if (isset($short)) {
        $short = str_replace(':', '', $short);
    }
    $long_to_shorts[$long] = $short;
}

$options = getopt(
    implode(
        '',
        array_filter(
            array_values($parameters),
            function ($value) {
                return isset($value);
            }
        )
    ),
    array_keys($parameters)
);

foreach (array_flip(array_filter($long_to_shorts, function ($value) {
    return isset($value);
})) as $short => $long) {
    if (array_key_exists($short, $options)) {
        $options[$long] = $options[$short];
        unset($options[$short]);
    }
}

if (array_key_exists('version', $options)) {
    print <<<EOF
lms-raport.php
(C) 2001-2020 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
lms-raport.php
(C) 2001-2020 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;
-o, --overdue-events            overdue events raport for event assigned users;
-A, --overdue-events-all-mail   send all overdue events raport to;

EOF;
    exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
    print <<<EOF
lms-raport.php
(C) 2001-2020 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options)) {
    $CONFIG_FILE = $options['config-file'];
} else {
    $CONFIG_FILE = '/etc/lms/lms.ini';
}

if (!$quiet) {
    echo "Using file " . $CONFIG_FILE . " as config." . PHP_EOL;
}

if (!is_readable($CONFIG_FILE)) {
    die("Unable to read configuration file [" . $CONFIG_FILE . "]!" . PHP_EOL);
}

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array)parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['modules_dir'] = (!isset($CONFIG['directories']['modules_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'modules' : $CONFIG['directories']['modules_dir']);
$CONFIG['directories']['plugin_dir'] = (!isset($CONFIG['directories']['plugin_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'plugins' : $CONFIG['directories']['plugin_dir']);
$CONFIG['directories']['plugins_dir'] = $CONFIG['directories']['plugin_dir'];

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('MODULES_DIR', $CONFIG['directories']['modules_dir']);
define('PLUGIN_DIR', $CONFIG['directories']['plugin_dir']);
define('PLUGINS_DIR', $CONFIG['directories']['plugin_dir']);

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
    // can't work without database
    die("Fatal error: cannot connect to database!" . PHP_EOL);
}

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes

$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

$plugin_manager = new LMSPluginManager();
$LMS->setPluginManager($plugin_manager);

$overdue_events_all = array_key_exists('overdue-events-all-mail', $options);
if (empty($overdue_events_all)) {
    $error = 'You didn\'t put the raport receipient email address';
}

if (!empty($error)) {
    die($error);
}

if (array_key_exists('overdue-events', $options)) {
    $params['forward'] = -1;
    $params['closed'] = 0;
    $params['type'] = 0;

    $overdue_events = $LMS->GetEventList($params);
    if (!empty($overdue_events)) {
        unset($params);
        $params['access'] = 1;
        $params['short'] = 1;
        $params['overdueusers'] = 1;
        $userlist = $LMS->GetUserList($params);
        foreach ($userlist as $user) {
            unset($params);
            $params['forward'] = -1;
            $params['short'] = 1;
            $params['closed'] = 0;

            $overdue_events_userid = $LMS->GetEventList($params);

            $mail_msg = print '<html>';
            foreach ($overdue_events_userid as $oe) {
                $mail_msg .= print '<a href="?m=eventinfo&id=' . $oe['id'] . '">' . $oe['description'] . '</a>';
            }
            $mail_msg += print '</html>';

            if (!empty($user['email'])) {
                mail($user['email'], 'Raport zdarzeń zaległych', "$mail_msg");
            }
        }
    }
}

if (array_key_exists('overdue-events-all-mail', $options)) {
    $raportreceipient = $options['overdue-events-all-mail'];
    $params['forward'] = -1;
    $params['closed'] = 0;
    $params['type'] = 0;
    $overdue_events_all = $LMS->GetEventList($params);

    if (!empty($overdue_events_all)) {
        $mail_msg = '<html>';
        foreach ($overdue_events_all as $oe) {
            $mail_msg .= '<a href="?m=eventinfo&id=' . $oe['id'] . '">' . $oe['description'] . '</a><br>';
        }
        $mail_msg .= '</html>';

        mail($raportreceipient, 'Raport zdarzeń zaległych', "$mail_msg");
    }
}
