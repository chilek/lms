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
$file_name_pattern = ConfigHelper::getConfig(
    $config_section . '.file_name_pattern',
    '^(?<year>[0-9]{4})-(?<month>[0-9]{2})-(?<day>[0-9]{2})_+(?<hour>[0-9]{2})-(?<minute>[0-9]{2})-(?<second>[0-9]{2})'
        . '_+(?<src>[0-9]+)_+(?<dst>[0-9]+)_+(?:(?<durationh>[0-9]+)h)?(?:(?<durationm>[0-9]{1,2})m)?(?:(?<durations>[0-9]{1,2})s)?\.wav$'
);
$file_extension = ConfigHelper::getConfig($config_section . '.file_extension', 'ogg');
$local_number_pattern = ConfigHelper::getConfig(
    $config_section . '.local_number_pattern',
    '^(?<prefix>48)?(?<number>[0-9]{9})$'
);

if (!is_dir($customer_call_dir)) {
    die('Fatal error: customer call directory does not exist!' . PHP_EOL);
}

if (!is_dir($src_dir)) {
    die('Fatal error: source directory does not exist!' . PHP_EOL);
}

$dirs = getdir($src_dir, '^[^\.].*$');
if (empty($dirs)) {
    $dirs[] = '';
}

function normalizePhoneNumber($number)
{
    return preg_replace(
        array(
            '/[^0-9]/',
            '/^0*/',
        ),
        array(
            '',
            '',
        ),
        $number
    );
}

$contacts = $DB->GetAll(
    'SELECT contact AS phone, customerid
    FROM customercontacts
    WHERE (type & ?) > 0',
    array(CONTACT_MOBILE | CONTACT_LANDLINE)
);
if (empty($contacts)) {
    die('Fatal error: customer contact database is empty!' . PHP_EOL);
}

$customers = array();
foreach ($contacts as $contact) {
    $phone = normalizePhoneNumber($contact['phone']);

    if (preg_match('/' . $local_number_pattern . '/', $phone, $m) && isset($m['prefix'])) {
        $phone = $m['number'];
    }

    if (!isset($customers[$phone])) {
        $customers[$phone] = array();
    }
    $customers[$phone][] = $contact['customerid'];
}
unset($contacts);

$users = array();
$user_phones = $DB->GetAll(
    'SELECT u.id, u.phone
    FROM users u
    WHERE u.phone <> ?',
    array('')
);
if (!empty($user_phones)) {
    foreach ($user_phones as $user_phone) {
        $phone = normalizePhoneNumber($user_phone['phone']);

        $users[$phone] = $user_phone['id'];

        if (preg_match('/' . $local_number_pattern . '/', $phone, $m) && isset($m['prefix'])) {
            $users[$m['number']] = $user_phone['id'];
        }
    }
    unset($user_phones);
}

foreach ($dirs as $dir) {
    $src_file_dir = ($dir == '' ? '' : $dir . DIRECTORY_SEPARATOR);
    $dir = $src_dir . ($dir == '' ? '' : DIRECTORY_SEPARATOR . $dir);

    $files = getdir($dir, $file_name_pattern);
    if (empty($files)) {
        continue;
    }

    foreach ($files as $src_file_name) {
        if (!preg_match('/' . $file_name_pattern . '/', $src_file_name, $m)) {
            echo 'File name \'' . $src_file_name . '\' does not match to pattern!' . PHP_EOL;
            continue;
        }

        if (isset($m['timestamp'])) {
            $dt = intval($m['timestamp']);
        } elseif (isset($m['datetime'])) {
            $dt = strtotime($m['datetime']);
        } else {
            $dt = mktime($m['hour'], $m['minute'], $m['second'], $m['month'], $m['day'], $m['year']);
        }

        if (isset($m['durationh'])) {
            $duration = (empty($m['durationh']) ? 0 : intval($m['durationh'])) * 3600
                + (empty($m['durationm']) ? 0 : intval($m['durationm'])) * 60
                + (empty($m['durations']) ? 0 : intval($m['durations']));
        } elseif (isset($m['duration'])) {
            $duration = intval($m['duration']);
        } else {
            die('Fatal error: cannot find duration field!' . PHP_EOL);
        }

        $src = normalizePhoneNumber($m['src']);
        if (preg_match('/' . $local_number_pattern . '/', $src, $mn) && isset($mn['prefix'])) {
            $src_prefix = $mn['prefix'];
            $src_number = $mn['number'];
        } else {
            $src_prefix = '';
            $src_number = $src;
        }

        $dst = normalizePhoneNumber($m['dst']);
        if (preg_match('/' . $local_number_pattern . '/', $dst, $mn) && isset($mn['prefix'])) {
            $dst_prefix = $mn['prefix'];
            $dst_number = $mn['number'];
        } else {
            $dst_prefix = '';
            $dst_number = $dst;
        }

        $outgoing = !empty($dst_prefix) && isset($customers[$dst_prefix . $dst_number]) || isset($customers[$dst_number]);
        if (!$outgoing && !isset($customers[$src_prefix . $src_number]) && !isset($customers[$src_number])) {
            continue;
        }

        $phone = $outgoing ? $dst : $src;

        $dst_file_name = preg_replace('/\.[^\.]+$/', '.' . $file_extension, $src_file_name);

        if ($LMS->isCustomerCallExists(array(
            'filename' => $dst_file_name,
        ))) {
            continue;
        }

        $dst_dir = $customer_call_dir . DIRECTORY_SEPARATOR . date('Y-m-d', $dt);
        $src_file = $src_file_dir . $src_file_name;

        if (!is_dir($dst_dir)) {
            mkdir($dst_dir, $storage_dir_permission, true);
            chown($dst_dir, $storage_dir_owneruid);
            chgrp($dst_dir, $storage_dir_ownergid);
        }

        $dst_file = $dst_dir . DIRECTORY_SEPARATOR . $dst_file_name;

        if (preg_match('/\.(?<ext>[^\.]+)$/', $dst_file_name, $m) && $m['ext'] == $file_extension) {
            if (!@rename($src_file, $dst_file)) {
                die('Fatal error: error during file ' . $src_file . ' rename!' . PHP_EOL);
            }
        } else {
            $cmd = str_replace(
                array('%i', '%o'),
                array($src_file, $dst_file),
                $convert_command
            );
            $ret = 0;
            system($cmd, $ret);
            if (!empty($ret)) {
                die('Fatal error: error during file ' . $src_file . ' conversion!' . PHP_EOL);
            }

            if (!@unlink($src_file)) {
                die('Fatal error: error during file ' . $src_file . ' deletion!' . PHP_EOL);
            }
        }

        chmod($dst_file, $storage_dir_permission);
        chown($dst_file, $storage_dir_owneruid);
        chgrp($dst_file, $storage_dir_ownergid);

        $userid = null;
        if (!empty($src_prefix) && isset($users[$src_prefix . $src_number])) {
            $userid = $users[$src_prefix . $src_number];
        } elseif (isset($users[$src_number])) {
            $userid = $users[$src_number];
        } elseif (!empty($dst_prefix) && isset($users[$dst_prefix . $dst_number])) {
            $userid = $users[$dst_prefix . $dst_number];
        } elseif (isset($users[$dst_number])) {
            $userid = $users[$dst_number];
        }

        $LMS->addCustomerCall(
            array(
                'dt' => $dt,
                'userid' => $userid,
                'filename' => $dst_file_name,
                'outgoing' => $outgoing,
                'phone' => $phone,
                'duration' => $duration,
            )
        );
    }
}

$calls = $LMS->getCustomerCalls(array(
    'order' => 'id,asc'
));
if (empty($calls)) {
    die('Fatal error: the are no customer calls in database!' . PHP_EOL);
}

foreach ($calls as $call) {
    $phone = $call['phone'];
    if (preg_match('/' . $local_number_pattern . '/', $phone, $m) && isset($m['prefix'])) {
        $phone = $m['number'];
    }

    if (isset($customers[$phone])) {
        foreach ($customers[$phone] as $customerid) {
            $LMS->addCustomerCallAssignment(
                $customerid,
                $call['id']
            );
        }
    }
}

$DB->Destroy();