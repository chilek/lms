#!/usr/bin/env php
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

// REPLACE THIS WITH PATH TO YOUR CONFIG FILE

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************

ini_set('error_reporting', E_ALL&~E_NOTICE);

$parameters = array(
	'C:' => 'config-file:',
	'q' => 'quiet',
	'h' => 'help',
	'v' => 'version',
	'f:' => 'csv-file:',
	't' => 'test',
	'c' => 'cleandb',
);

foreach ($parameters as $key => $val) {
	$val = preg_replace('/:/', '', $val);
	$newkey = preg_replace('/:/', '', $key);
	$short_to_longs[$newkey] = $val;
}
$options = getopt(implode('', array_keys($parameters)), $parameters);
foreach ($short_to_longs as $short => $long)
	if (array_key_exists($short, $options)) {
		$options[$long] = $options[$short];
		unset($options[$short]);
	}

if (array_key_exists('version', $options)) {
	print <<<EOF
lms-teryt-emergency-numbers.php.php
(C) 2001-2016 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options)) {
	print <<<EOF
lms-teryt-emergency-numbers.php.php
(C) 2001-2016 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-f, --csv-file=<csv-file>       name of csv file;
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;
-t, --test                      for single test pass without database modifications
-c, --cleandb                   clean all emergency number info in database

EOF;
	exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
	print <<<EOF
lms-teryt-emergency-numbers.php.php
(C) 2001-2016 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options))
	$CONFIG_FILE = $options['config-file'];
else
	$CONFIG_FILE = '/etc/lms/lms.ini';

if (!$quiet)
	echo "Using file ".$CONFIG_FILE." as config." . PHP_EOL;

if (!is_readable($CONFIG_FILE))
	die('Unable to read configuration file ['.$CONFIG_FILE.']!' . PHP_EOL);

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);

// Load autoloader
$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path))
	require_once $composer_autoload_path;
else
	die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/" . PHP_EOL);

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

$test = isset($options['test']);
$cleandb = isset($options['cleandb']);

if ($test && !$quiet)
	echo "Testing mode is enabled!" . PHP_EOL;

if (isset($options['csv-file'])) {
	$csv_file =  $options['csv-file'];
	if (!is_readable($csv_file))
		die("Couldn't open csv file!" . PHP_EOL);
} else
	$csv_file = 'php://stdin';

$states = $DB->GetAllByKey('SELECT * FROM location_states', 'name');
if (empty($states))
	die($quiet ? '' : "TERYT database is empty!" . PHP_EOL);

$districts_tmp = $DB->GetAll('SELECT * FROM location_districts');
$districts = array();
foreach ($districts_tmp as $district)
	$districts[$district['name'] . '_' . $district['stateid']] = $district;
unset($districts_tmp);

$boroughs_tmp = $DB->GetAll('SELECT * FROM location_boroughs');
foreach ($boroughs_tmp as $borough)
	$boroughs[$borough['name'] . '_' . $borough['districtid'] . '_' . $borough['type']] = $borough;
unset($boroughs_tmp);

$lines = file($csv_file);

$borough_types = array(
	'm' => array(1, 4, 5),
	'gw' => array(2),
	'w' => array(2, 4, 5),
	'm-w' => array(2, 4, 5),
);

$colnames = array('lp', 'wsn', 'gm', 'typ_gm', 'pow', 'woj', 'wsnd', 'hex', 'idl', 'nr');

$result = array();
foreach ($lines as $line) {
	$line = preg_replace('/\s{2,}/', ' ', $line);
	$values = array_slice(array_map('trim', explode('|', str_replace('–', '-', $line))), 0, count($colnames));
	if (count($values) < count($colnames)) {
		if (!$quiet)
			echo "Invalid record format: " . trim($line) . PHP_EOL;
		continue;
	}

	$record = array_combine($colnames, $values);
	if (empty($record['wsnd']) || empty($record['hex']) || empty($record['idl'])) {
		if (!$quiet)
			echo "Emergency number is not supported in given area!" . PHP_EOL;
		continue;
	}

	$state = $states[$record['woj']];
	$district_index = $record['pow'] . '_' . $state['id'];
	if (isset($districts[$district_index])) {
		$district = $districts[$district_index];
		$borough_index = $record['gm'] . '_' . $district['id'] . '_';
		$btypes = $borough_types[$record['typ_gm']];
		$borough_btypes = array();
		foreach ($btypes as $btype)
			if (isset($boroughs[$borough_index . $btype]))
				$borough_btypes[] = $btype;
		if (!empty($borough_btypes))
			foreach ($borough_btypes as $borough_btype) {
				$borough = $boroughs[$borough_index . $borough_btype];
				$result[] = array(
					'terc' => sprintf("%02d%02d%02d%s", $state['ident'], $district['ident'],
						$borough['ident'], $borough['type']),
					'state_ident' => $state['ident'],
					'district_ident' => $district['ident'],
					'borough_ident' => $borough['ident'],
					'borough_type' => $borough['type'],
					'borough_id' => $borough['id'],
					'number' => $record['nr'],
					'fullnumber' => '48' . $record['wsnd'] . $record['hex'] . $record['idl'] . $record['nr'],
				);
			}
		else
			$result[] = array('line' => $line);
	} else
		$result[] = array('line' => $line);
}

if (empty($result)) {
	if (!$quiet)
		echo "No data database records!" . PHP_EOL;
	die;
}

if (!$test) {
	$DB->BeginTrans();
	if ($cleandb) {
		$DB->Execute("DELETE FROM voip_emergency_numbers");
		if (!$quiet)
			echo "Emergency number database has been cleared!" . PHP_EOL;
	}
}

$inserted_records = array();
foreach ($result as $record)
	if (isset($record['borough_id'])) {
		if (!$test) {
			$idx = $record['borough_id'] . '_' . $record['number'];
			if (isset($inserted_records[$idx]))
				continue;
			$DB->Execute("INSERT INTO voip_emergency_numbers (location_borough, number, fullnumber)
				VALUES (?, ?, ?)", array($record['borough_id'], $record['number'], $record['fullnumber']));
			$inserted_records[$idx] = true;
		}
	} elseif (!$quiet)
		echo "TERYT location not found for record: " . trim($record['line']) . PHP_EOL;

if (!$test)
	$DB->CommitTrans();

$DB->Destroy();

?>
