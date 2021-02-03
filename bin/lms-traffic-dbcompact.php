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
    'help' => 'h',
    'level:' => 'l:',
    'remove-old' => 'o',
    'remove-deleted' => 'd',
    'version' => 'v',
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
lms-traffic-dbcompact.php
(C) 2001-2020 LMS Developers

EOF;
        exit(0);
}

if (array_key_exists('help', $options)) {
        print <<<EOF
lms-traffic-dbcompact.php
(C) 2001-2020 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                              print this help and exit;
-v, --version                           print version info and exit;
-l, --level                             compact level low,medium,high (default: medium)
                                        low    - Data older than one day will be combined into one day
                                        medium - Data older than one month will be combined into one day
                                        high   - Data older than one month will be combined into one hour
-o, --remove-old                        remove records older than one year
-d, --remove-deleted                    remove stats of deleted nodes

EOF;
        exit(0);
}


if (array_key_exists('config-file', $options)) {
    $CONFIG_FILE = $options['config-file'];
} else {
    $CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';
}

$remove_old = array_key_exists('remove-old', $options);
$remove_deleted = array_key_exists('remove-deleted', $options);

$level = 'medium';
if (array_key_exists('level', $options)) {
    if (in_array($options['level'], array('low', 'medium', 'high'))) {
        $level = $options['level'];
    }
}

if (!is_readable($CONFIG_FILE)) {
    die('Unable to read configuration file ['.$CONFIG_FILE.']!');
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

// Include required files (including sequence is important)

//require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');

set_time_limit(0);

print('Compacting database: ' . ConfigHelper::getConfig('database.database') . PHP_EOL);
print('Level: ' . $level . PHP_EOL);
print('Remove old stats: ' . ($remove_old ? 'Yes' : 'No') . PHP_EOL);
print('Remove stats for deleted nodes: ' . ($remove_deleted ? 'Yes' : 'No') . PHP_EOL);

printf('%d records before compacting ' . PHP_EOL, $DB->GetOne('SELECT COUNT(*) FROM stats'));

if ($remove_old && ($deleted = $DB->Execute('DELETE FROM stats where dt < ?NOW? - 365*24*60*60')) > 0) {
    printf('%d at least one year old records have been removed' . PHP_EOL, $deleted);
}

if ($remove_deleted && ($deleted = $DB->Execute('DELETE FROM stats WHERE nodeid NOT IN (SELECT id FROM vnodes)')) > 0) {
    printf('%d records for deleted nodes have been removed' . PHP_EOL, $deleted);
}

$time = time();
switch ($level) {
    case 'medium':
        $period = $time-30*24*60*60;
        $step = 24*60*60;
        break;//month, day
    case 'high':
        $period = $time-30*24*60*60;
        $step = 60*60;
        break; //month, hour
    default:
        $period = $time-24*60*60;
        $step = 24*60*60;
        break; //1 day, day
}

if ($mintime = $DB->GetOne('SELECT MIN(dt) FROM stats')) {
    $nodes = $DB->GetAll('SELECT id, name FROM vnodes ORDER BY name');

    foreach ($nodes as $node) {
        $deleted = 0;
        $inserted = 0;
        $maxtime = $period;
        $timeoffset = date('Z');
        $dtdivider = 'FLOOR((dt+'.$timeoffset.')/'.$step.')';

        $data = $DB->GetAll('SELECT SUM(download) AS download, SUM(upload) AS upload,
			COUNT(dt) AS count, MIN(dt) AS mintime, MAX(dt) AS maxtime, nodesessionid
			FROM stats WHERE nodeid = ? AND dt >= ? AND dt < ?
			GROUP BY nodeid, nodesessionid, '.$dtdivider.'
			ORDER BY mintime', array($node['id'], $mintime, $maxtime));

        if ($data) {
            // If divider-record contains only one record we can skip it
            // This way we'll minimize delete-insert operations count
            // e.g. in situation when some records has been already compacted
            foreach ($data as $rid => $record) {
                if ($record['count'] == 1) {
                    unset($data[$rid]);
                } else {
                    break;
                }
            }

            // all records for this node has been already compacted
            if (empty($data)) {
                echo $node['name'] . ': 0  - removed, 0 - inserted' . PHP_EOL;
                continue;
            }

            $values = array();
            // set start datetime of the period
            $data = array_values($data);
            $nodemintime = $data[0]['mintime'];

            $DB->BeginTrans();

            // delete old records
            $DB->Execute(
                'DELETE FROM stats WHERE nodeid = ? AND dt >= ? AND dt <= ?',
                array($node['id'], $nodemintime, $maxtime)
            );

            // insert new (summary) records
            foreach ($data as $record) {
                $deleted += $record['count'];

                if (!$record['download'] && !$record['upload']) {
                    continue;
                }

                $values[] = sprintf(
                    '(%d, %d, %d, %d, %s)',
                    $node['id'],
                    $record['maxtime'],
                    $record['upload'],
                    $record['download'],
                    $DB->Escape(empty($record['nodesessionid']) ? null : $record['nodesessionid'])
                );
            }

            if (!empty($values)) {
                $inserted = $DB->Execute('INSERT INTO stats
					(nodeid, dt, upload, download, nodesessionid) VALUES ' . implode(', ', $values));
            }

            $DB->CommitTrans();

            echo $node['name'].': ' . $deleted . ' - removed, ' . $inserted . ' - inserted' . PHP_EOL;
        }
    }
}

printf('%d records after compacting' . PHP_EOL, $DB->GetOne('SELECT COUNT(*) FROM stats'));

$DB->Destroy();

?>
