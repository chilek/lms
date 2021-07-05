#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2021 LMS Developers
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

ini_set('error_reporting', E_ALL & ~E_NOTICE);

$parameters = array(
    'config-file:' => 'C:',
    'quiet' => 'q',
    'help' => 'h',
    'version' => 'v',
    'section:' => 's:',
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

if (isset($options['version'])) {
    print <<<EOF
lms-customercalls.php
(C) 2001-2021 LMS Developers

EOF;
    exit(0);
}

if (isset($options['help'])) {
    print <<<EOF
lms-customercalls.php
(C) 2001-2021 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;
-s, --section=<section-name>    section name from lms configuration where settings
                                are stored

EOF;
    exit(0);
}

$quiet = isset($options['quiet']);
if (!$quiet) {
    print <<<EOF
lms-customercalls.php
(C) 2001-2021 LMS Developers

EOF;
}

if (isset($options['config-file'])) {
    $CONFIG_FILE = $options['config-file'];
} else {
    $CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';
}

if (!$quiet) {
    echo "Using file ".$CONFIG_FILE." as config." . PHP_EOL;
}

$config_section = (array_key_exists('section', $options) && preg_match('/^[a-z0-9-]+$/i', $options['section']) ? $options['section'] : 'customercalls');

if (!is_readable($CONFIG_FILE)) {
    die('Unable to read configuration file [' . $CONFIG_FILE . ']!' . PHP_EOL);
}

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['storage_dir'] = (!isset($CONFIG['directories']['storage_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'storage' : $CONFIG['directories']['storage_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('STORAGE_DIR', $CONFIG['directories']['storage_dir']);

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
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes
$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

$src_dir = ConfigHelper::getConfig($config_section . '.source_directory', '.');
$customer_call_dir = STORAGE_DIR . DIRECTORY_SEPARATOR . 'customercalls';
$storage_dir_permission = intval(ConfigHelper::getConfig('storage.dir_permission', '0700'), 8);
$storage_dir_owneruid = ConfigHelper::getConfig('storage.dir_owneruid', 'root');
$storage_dir_ownergid = ConfigHelper::getConfig('storage.dir_ownergid', 'root');
$convert_command = ConfigHelper::getConfig($config_section . '.call_convert_command', 'sox %i %o');
$file_extension = ConfigHelper::getConfig($config_section . '.file_extension', 'ogg');

if (!is_dir($customer_call_dir)) {
    die('Fatal error: customer call directory does not exist!' . PHP_EOL);
}

$dirs = getdir($src_dir, '^[0-9]{4}-[0-9]{2}-[0-9]{2}$');
if (empty($dirs)) {
    die('Fatal error: there are no customer call directories!' . PHP_EOL);
}

define(
    'FILENAME_PATTERN',
    '^(?<year>[0-9]{4})-(?<month>[0-9]{2})-(?<day>[0-9]{2})_+(?<hour>[0-9]{2})-(?<minute>[0-9]{2})-(?<second>[0-9]{2})'
    . '_+(?<src>[0-9]+)_+(?<dst>[0-9]+)_+(?:(?<durationh>[0-9]+)h)?(?:(?<durationm>[0-9]{1,2})m)?(?:(?<durations>[0-9]{1,2})s)?\.wav$'
);

foreach ($dirs as $dir) {
    $files = getdir($src_dir . DIRECTORY_SEPARATOR . $dir, FILENAME_PATTERN);
    if (empty($files)) {
        continue;
    }
    foreach ($files as $file) {
        $filename = $dir . DIRECTORY_SEPARATOR . $file;

        preg_match('/' . FILENAME_PATTERN . '/', $file, $m);
        $dt = mktime($m['hour'], $m['minute'], $m['second'], $m['month'], $m['day'], $m['year']);
        $duration = (empty($m['durationh']) ? 0 : intval($m['durationh'])) * 3600
            + (empty($m['durationm']) ? 0 : intval($m['durationm'])) * 60
            + (empty($m['durations']) ? 0 : intval($m['durations']));
        $outgoing = strlen($m['dst']) > 4 ? 1 : 0;
        if (!$outgoing && strlen($m['src']) <= 4) {
            continue;
        }
        $phone = $outgoing ? $m['dst'] : $m['src'];
        $phone = preg_replace('/^0*/', '', $phone);

        $out_filename = preg_replace('/\.[^\.]+$/', '.' . $file_extension, $filename);

        if ($LMS->isCustomerCallExists(array(
            'filename' => $out_filename,
        ))) {
            continue;
        }

        if (!is_dir($customer_call_dir . DIRECTORY_SEPARATOR . $dir)) {
            mkdir($customer_call_dir . DIRECTORY_SEPARATOR . $dir, $storage_dir_permission, true);
            chown($customer_call_dir . DIRECTORY_SEPARATOR . $dir, $storage_dir_owneruid);
            chgrp($customer_call_dir . DIRECTORY_SEPARATOR . $dir, $storage_dir_ownergid);
        }

        $dst_file = $customer_call_dir . DIRECTORY_SEPARATOR . $out_filename;

        if (preg_match('/\.(?<ext>[^\.]+)$/', $filename, $m) && $m['ext'] == $file_extension) {
            if (!@rename($src_dir . DIRECTORY_SEPARATOR . $filename, $dst_file)) {
                die('Fatal error: error during file ' . $src_dir . DIRECTORY_SEPARATOR . $out_filename . ' rename!' . PHP_EOL);
            }
        } else {
            $cmd = str_replace(
                array('%i', '%o'),
                array($src_dir . DIRECTORY_SEPARATOR . $filename, $dst_file),
                $convert_command
            );
            $ret = 0;
            system($cmd, $ret);
            if (!empty($ret)) {
                die('Fatal error: error during file ' . $src_dir . DIRECTORY_SEPARATOR . $filename . ' conversion!' . PHP_EOL);
            }

            if (!@unlink($src_dir . DIRECTORY_SEPARATOR . $filename)) {
                die('Fatal error: error during file ' . $src_dir . DIRECTORY_SEPARATOR . $filename . ' deletion!' . PHP_EOL);
            }
        }

        chmod($dst_file, $storage_dir_permission);
        chown($dst_file, $storage_dir_owneruid);
        chgrp($dst_file, $storage_dir_ownergid);

        $LMS->addCustomerCall(
            array(
                'dt' => $dt,
                'filename' => $out_filename,
                'outgoing' => $outgoing,
                'phone' => $phone,
                'duration' => $duration,
            )
        );
    }
}

$contacts = $DB->GetAll(
    'SELECT REPLACE(REPLACE(contact, \' \', \'\'), \'-\', \'\') AS phone, customerid
    FROM customercontacts
    WHERE (type & ?) > 0',
    array(CONTACT_MOBILE | CONTACT_LANDLINE)
);
if (empty($contacts)) {
    die('Fatal error: customer contact database is empty!' . PHP_EOL);
}

$customers = array();
foreach ($contacts as $contact) {
    if (!isset($customers[$contact['phone']])) {
        $customers[$contact['phone']] = array();
    }
    $customers[$contact['phone']][] = $contact['customerid'];
}

$calls = $LMS->getCustomerCalls(null, 0);
if (empty($calls)) {
    die('Fatal error: the are no customer calls in database!' . PHP_EOL);
}

foreach ($calls as $call) {
    if (isset($customers[$call['phone']])) {
        foreach ($customers[$call['phone']] as $customerid) {
            $LMS->addCustomerCallAssignment(
                $customerid,
                $call['id']
            );
        }
    }
}

$DB->Destroy();
