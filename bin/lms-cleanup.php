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
    'resources:' => 'r:',
    'time-limit:' => 't:',
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
lms-cleanup.php
(C) 2001-2020 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
lms-cleanup.php
(C) 2001-2020 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;
-r, --resources=<finances>
                                system resource type list to clean up;
-t, --time-limit=<days>
                                only resources older than specified 'days' are cleaned up;

EOF;
    exit(0);
}

$quiet = isset($options['quiet']);
if (!$quiet) {
    print <<<EOF
lms-cleanup.php
(C) 2001-2020 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options)) {
    $CONFIG_FILE = $options['config-file'];
} else {
    $CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';
}

if (!$quiet) {
    echo "Using file " . $CONFIG_FILE . " as config." . PHP_EOL;
}

if (!is_readable($CONFIG_FILE)) {
    die("Unable to read configuration file [" . $CONFIG_FILE . "]!" . PHP_EOL);
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

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes

$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

Localisation::initDefaultCurrency();
Localisation::setUILanguage(Localisation::getCurrentSystemLanguage());

$SYSLOG->NewTransaction('lms-cleanup.php');

$resources = array();

if (isset($options['resources'])) {
    $resources = explode(',', $options['resources']);
}

if (empty($resources)) {
    die('Fatal error: No resource types to clean up specified!' . PHP_EOL);
}

$supported_resources = array(
    'finances' => true,
);
foreach ($resources as $resource) {
    if (!isset($supported_resources[$resource])) {
        die('Fatal error: resource type \'' . $resource . '\' is not supported!' . PHP_EOL);
    }
}

if (isset($options['time-limit'])) {
    if (!preg_match('/^[0-9]+$/', $options['time-limit'])) {
        die('Fatal error: --time-limit parameter value syntax error!' . PHP_EOL);
    }
    $time_limit = intval($options['time-limit']);
} else {
    $time_limit = 6 * 366;
}

$time = strtotime($time_limit . ' days ago');

$currency = Localisation::getDefaultCurrency();

$resources = array_flip($resources);

if (!$quiet) {
    echo PHP_EOL . 'Current time limit operation threshold: ' . $time_limit . ' days' . PHP_EOL;
}

if (isset($resources['finances'])) {
    if (!$quiet) {
        echo PHP_EOL;
        echo '###################' . PHP_EOL;
        echo 'Financial resources' . PHP_EOL;
        echo '###################' . PHP_EOL;
    }

    $balances = $DB->GetAll(
        'SELECT customerid, SUM(value * currencyvalue) AS balance
        FROM cash
        WHERE time < ?
        GROUP BY customerid',
        array($time)
    );

    if (empty($balances)) {
        $balances = array();
    } else {
        $balances = Utils::array_column($balances, 'balance', 'customerid');
    }

    $DB->BeginTrans();

    echo 'Creating starting balance records... ';
    foreach ($balances as $customerid => $balance) {
        $DB->Execute(
            'INSERT INTO cash (time, type, value, currency, comment) VALUES (?, ?, ?, ?, ?)',
            array(
                $time,
                1,
                $balance,
                $currency,
                trans('Starting balance at $a', date('Y/m/d', $time)),
            )
        );
    }
    echo count($balances) . ' record(s) created.' . PHP_EOL;

    $documents = $DB->GetAll(
        'SELECT DISTINCT cash.docid AS id, d.archived
        FROM cash
        JOIN documents d ON d.id = cash.docid
        WHERE d.type IN ? AND cash.time < ?',
        array(
            array(DOC_INVOICE, DOC_CNOTE, DOC_INVOICE_PRO, DOC_DNOTE),
            $time,
        )
    );
    if (!empty($documents)) {
        if (!$quiet) {
            echo 'Removing trade and cash document(s)... ';
        }
        foreach ($documents as $document) {
            if (!empty($document['archived'])) {
                $LMS->DeleteArchiveTradeDocument($document['id']);
            }
        }

        $docids = Utils::array_column($documents, 'id');
        $count = $DB->Execute(
            'DELETE FROM documents
            WHERE id IN ?',
            array(Utils::array_column($documents, 'id'))
        );
        echo $count . ' removed.'. PHP_EOL;
    }

    if (!$quiet) {
        echo 'Removing cash import record(s)... ';
    }
    $count = $DB->Execute(
        'DELETE FROM cashimport
        WHERE id IN (
            SELECT importid
            FROM cash
            WHERE time < ?
        )',
        array($time)
    );
    echo $count . ' cash import(s) removed. ';
    $count = $DB->Execute(
        'DELETE FROM sourcefiles
        WHERE idate < ?
          AND NOT EXISTS (
              SELECT 1
              FROM cashimport
              WHERE cashimport.sourcefileid = sourcefiles.id
          )',
        array($time)
    );
    echo $count . ' source file(s) removed. ' . PHP_EOL;

    if (!$quiet) {
        echo 'Removing financial operations... ';
    }
    $count = $DB->Execute(
        'DELETE FROM cash
        WHERE cash.time < ?',
        array(
            $time,
        )
    );
    echo $count . ' operation(s) removed. ' . PHP_EOL;

    $DB->CommitTrans();
}
