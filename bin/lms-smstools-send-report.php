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

// REPLACE THIS WITH PATH TO YOUR CONFIG FILE

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************

ini_set('error_reporting', E_ALL&~E_NOTICE);

$parameters = array(
    'config-file:' => 'C:',
    'quiet' => 'q',
    'help' => 'h',
    'version' => 'v',
    'message-file:' => 'm:',
    'ok' => 'o',
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
lms-smstools-send-report.php
(C) 2001-2020 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
lms-smstools-send-report.php
(C) 2001-2020 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-m, --message-file=<message-file>       name of message file;
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;

EOF;
    exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
    print <<<EOF
lms-smstools-send-report.php
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
    die('Unable to read configuration file [' . $CONFIG_FILE . ']!' . PHP_EOL);
}

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array)parse_ini_file($CONFIG_FILE, true);

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

// Do some checks and load config defaults
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'config.php');

// Init database

$DB = null;

try {
    $DB = LMSDB::getInstance();
} catch (Exception $ex) {
    trigger_error($ex->getMessage(), E_USER_WARNING);
    // can't workwithout database
    die("Fatal error: cannot connect to database!" . PHP_EOL);
}

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

if (array_key_exists('message-file', $options)) {
    $message_file = $options['message-file'];
} else {
    die("Required message file parameter!" . PHP_EOL);
}

if (!($lines = @file($message_file))) {
    die("Message file doesn't exist!" . PHP_EOL);
}

$msgitemid = preg_grep('/^Msgid: ([0-9]+)$/', $lines);
$phone = preg_grep('/^To: \+?([0-9]+)$/', $lines);
$error = preg_grep('/^Fail_reason: (.*)$/', $lines);

if (!empty($msgitemid) && !empty($phone)) {
    $msgitemid = preg_replace('/^Msgid:\s*/', '', trim(reset($msgitemid)));
    $phone = preg_replace('/^To:\s*\+?/', '', trim(reset($phone)));
}

if (!empty($error)) {
    $error = preg_replace('/^Fail_reason: /', '', trim(reset($error)));
}

if (empty($msgitemid) && preg_match('/lms-([0-9]+)-([0-9]+)/', basename($message_file), $matches)) {
    $msgitemid = $matches[1];
    $phone = $matches[2];
}

if ($msgitemid && $phone) {
    if ($lines = @file($message_file)) {
        $line = preg_grep('/^Message_id:\s*/', $lines);
        if (!empty($line)) {
            $externalmsgid = intval(preg_replace('/^Message_id:\s*/', '', trim(reset($line))));
        }
    }

    $msgitem = $DB->GetRow(
        'SELECT id, destination FROM messageitems WHERE id = ? AND status = ?',
        array($msgitemid, MSG_NEW)
    );

    if (!empty($msgitem)) {
        $sms_prefix = ConfigHelper::getConfig('sms.prefix');
        $prefix = !empty($sms_prefix) ? $sms_prefix : '';
        $number = preg_replace('/^[^0-9]+/', '', $msgitem['destination']);
        $number = preg_replace('/^0+/', '', $number);
        $number = str_replace(' ', '', $number);
        if ($prefix && substr($number, 0, strlen($prefix)) != $prefix) {
            $number = $prefix . $number;
        }
        if ($number == $phone) {
            if (isset($externalmsgid)) {
                $DB->Execute(
                    'UPDATE messageitems SET status = ?, externalmsgid = ?, error = ? WHERE id = ?',
                    array((array_key_exists('ok', $options) ? MSG_SENT : MSG_ERROR), $externalmsgid, empty($error) ? null : $error, $msgitemid)
                );
            } else {
                $DB->Execute(
                    'UPDATE messageitems SET status = ?, error = ? WHERE id = ?',
                    array((array_key_exists('ok', $options) ? MSG_SENT : MSG_ERROR), empty($error) ? null : $error, $msgitemid)
                );
            }
        }
    }
}
