#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

ini_set('error_reporting', E_ALL&~E_NOTICE);

$parameters = array(
	'C:' => 'config-file:',
	'q' => 'quiet',
	'h' => 'help',
	'v' => 'version',
	'f:' => 'file:',
	'l:' => 'list:',
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
lms-pna.php
(C) 2001-2018 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options)) {
	print <<<EOF
lms-pna.php
(C) 2001-2018 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-f, --file                      PNA csv database file;
-l, --list=<list>               comma-separated list of state IDs;
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;

EOF;
	exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
	print <<<EOF
lms-pna.php
(C) 2001-2018 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options))
	$CONFIG_FILE = $options['config-file'];
else
	$CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

if (!$quiet)
	echo "Using file ".$CONFIG_FILE." as config." . PHP_EOL;

if (!is_readable($CONFIG_FILE))
	die("Unable to read configuration file [".$CONFIG_FILE."]!" . PHP_EOL);

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

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

$SYSLOG = null;
$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

$stderr = fopen('php://stderr', 'w');

define('PNA', 0);
define('CITY', 1);
define('STREET', 2);
define('HOUSE', 3);
define('BOROUGH', 4);
define('DISTRICT', 5);
define('STATE', 6);

$all_states = array(
	'dolnoslaskie' => 2,
	'kujawsko-pomorskie' => 4,
	'lubelskie' => 6,
	'lubuskie' => 8,
	'lodzkie' => 10,
	'malopolskie' => 12,
	'mazowieckie' => 14,
	'opolskie' => 16,
	'podkarpackie' => 18,
	'podlaskie' => 20,
	'pomorskie' => 22,
	'slaskie' => 24,
	'swietokrzyskie' => 26,
	'warminsko-mazurskie' => 28,
	'wielkopolskie' => 30,
	'zachodniopomorskie' => 32,
);

$states = ConfigHelper::getConfig('pna.state_list', '', true);

$state_lists = array();
if (!empty($states))
	$state_lists[$states] = "Invalid state list format in ini file!";
if (isset($options['list']))
	$state_lists[$options['list']] = "Invalid state list format entered in command line!";
foreach ($state_lists as $states => $error_message) {
	$states = explode(',', $states);
	foreach ($states as &$state) {
		if (preg_match('/^[0-9]+$/', $state))
			continue;
		$state = iconv('UTF-8', 'ASCII//TRANSLIT', $state);
		if (!isset($all_states[$state])) {
			fwrite($stderr,  $error_message . PHP_EOL);
			die;
		}
		$state = $all_states[$state];
	}
	unset($state);
	$state_list = array_combine($states, array_fill(0, count($states), '1'));
}

fclose($stderr);

$boroughs = $DB->GetAll('SELECT ls.name AS state_name, ld.name AS district_name,
		lb.name AS borough_name, lb.type AS borough_type, lb.id
	FROM location_boroughs lb
	JOIN location_districts ld ON ld.id = lb.districtid
	JOIN location_states ls ON ls.id = ld.stateid');
if (empty($boroughs))
	die('TERYT database is empty!' . PHP_EOL);

$boroughs_ids = array();
foreach ($boroughs as $borough)
    $borough_ids[$borough['state_name'] . ':' . $borough['district_name'] . ':' . $borough['borough_name'] . ':' . $borough['borough_type']] =
        $borough['id'];
unset($boroughs);

$borough_types = array(
	1 => 'gm. miejska',
	2 => 'gm. wiejska',
	3 => 'gm. miejsko-wiejska',
	4 => 'gm. miejsko-wiejska',
	5 => 'gm. miejsko-wiejska',
	8 => 'dzielnica gminy Warszawa-Centrum',
	9 => 'dzielnica',
);

$cols = array(
	PNA => 'PNA',
	CITY => 'Miejscowość',
	STREET => 'Ulica',
	HOUSE => 'Numery',
	BOROUGH => 'Gmina',
	DISTRICT => 'Powiat',
	STATE => 'Województwo'
);

function convert_pna_to_teryt($data) {
	global $cities_with_sections, $borough_ids, $borough_types;

	$DB = LMSDB::getInstance();

	$cities = array();
	$cities[] = preg_replace('/[[:blank:]]+\(.+\)$/', '', $data[CITY]);
	if (mb_strlen(current($cities)) != mb_strlen($data[CITY]))
		$cities[] = preg_replace('/.+[[:blank:]]+\((.+)\)$/', '\1', $data[CITY]);
	foreach ($cities as $city) {
		$city = mb_strtolower($city);
		if (isset($cities_with_sections[$city]))
			$boroughs = $cities_with_sections[$city]['boroughs'];
	}
	$data[CITY] = $cities;

	static $street_common_part_replaces = array('skw.' => 'skwer', 'wybrz.' => 'wyb.');
	static $street_short_to_long_part_replaces = array('ul.' => 'ulica', 'al.' => 'aleja',
		'pl.' => 'plac', 'os.' => 'osiedle', 'bulw.' => 'bulwar', 'wyb.' => 'wybrzeże');
	$street_common_parts = array();
	$streets = array();
	if (!empty($data[STREET])) {
		// remove strange characters from beginning and ending of street name
		$street = trim($data[STREET], " \t\n\r\0\x0B");
		$street = preg_replace('/"(.+)"/', '$1', $street);

		// replace double quotes by single quote
		$street = str_replace('""', '"', $street);

		// fix mispelled common street sufixes/prefixes
        $street = strtr($street, $street_common_part_replaces);

		// remove parts in simple brackets
		$street = preg_replace('/[[:blank:]]+\(.+\)$/', '', $street);

		// changes letters to lowercase
		$street = mb_strtolower($street);

		$streets[] = $street;

		// analyze street prefixes/suffixes
		if (preg_match('/^(?<prefix>ul\.|inne|al\.|rynek|pl\.|rondo|park|os\.|szosa|skwer|bulw\.|wyspa|ogród|wyb\.|droga)?[[:blank:]]/', $street, $pmatches)
			 || preg_match('/.+[[:blank:]](?<suffix>ul\.|inne|al\.|rynek|pl\.|rondo|park|os\.|szosa|skwer|bulw\.|wyspa|ogród|wyb\.|droga)?$/', $street, $smatches)) {
			$replaces = array();
			if (isset($pmatches['prefix']) && !empty($pmatches['prefix'])) {
				$street_common_parts[] = $street_common_part = $pmatches['prefix'];
				$street_common_parts[] = strtr($street_common_part, $street_short_to_long_part_replaces);
				$replaces[$street_common_part] = '';
			}
			if (isset($smatches['suffix']) && !empty($smatches['suffix'])) {
				$street_common_parts[] = $street_common_part = $smatches['suffix'];
				$street_common_parts[] = strtr($street_common_part, $street_short_to_long_part_replaces);
				$replaces[$street_common_part] = '';
			}

			$streets[] = $street_without_common_parts = trim(strtr($street, $replaces));

			// streets with suffix used as prefix and prefix used as suffix
			$streets[] = $street_without_common_parts . ' ' . $street_common_part;
			$streets[] = $street_without_common_parts . ' ' . strtr($street_common_part, $street_short_to_long_part_replaces);
			$streets[] = $street_common_part . ' ' . $street_without_common_parts;
			$streets[] = strtr($street_common_part, $street_short_to_long_part_replaces) . ' ' . $street_without_common_parts;
		}
	}

	if (!empty($data[HOUSE])) {
		$data[HOUSE] = preg_replace('/[[:blank:]]/', '', $data[HOUSE]);
		$data[HOUSE] = explode(',', $data[HOUSE]);
		$houses = array();
		foreach ($data[HOUSE] as $token) {
			$parity = 0;
			if (preg_match('/\(n\)$/', $token))
				$parity += 1;
			elseif (preg_match('/\(p\)$/', $token))
				$parity += 2;
			else $parity = 3;
			if ($parity < 3)
				$token = preg_replace('/\([pn]\)$/', '', $token);
			list ($from, $to) = explode("-", $token);
			if ($to == 'DK')
				$to = null;
			elseif (empty($to) && !empty($from))
				$to = $from;

			if (empty($from))
				$fromnumber = $fromletter = null;
			else {
				preg_match('/^(?<number>[0-9]+)(?<letter>[a-z]*)$/', $from, $m);
				$fromnumber = $m['number'];
				$fromletter = empty($m['letter']) ? null : $m['letter'];
			}

			if (empty($to))
			    $tonumber = $toletter = null;
            else {
				preg_match('/^(?<number>[0-9]+)(?<letter>[a-z]*)$/', $to, $m);
				$tonumber = $m['number'];
				$toletter = empty($m['letter']) ? null : $m['letter'];
			}

			$houses[] = array('fromnumber' => $fromnumber, 'fromletter' => $fromletter,
				'tonumber' => $tonumber, 'toletter' => $toletter,
				'parity' => $parity);
		}
		$data[HOUSE] = $houses;
	}
	else
		$data[HOUSE] = array(array('fromnumber' => null, 'fromletter' => null,
			'tonumber' => null, 'toletter' => null,
			'parity' => 3));

	$borough_ids_to_check = array();
	$terc = $data[STATE] . ':' . $data[DISTRICT] . ':' . $data[BOROUGH] . ':';
	foreach ($borough_types as $borough_type => $borough_type_name)
		if (isset($borough_ids[$terc . $borough_type]))
			$borough_ids_to_check[] = $borough_ids[$terc . $borough_type];
	if (empty($borough_ids_to_check))
		return;

	$city_name = $DB->Escape($data[CITY][0]);
	$city_name2 = empty($data[CITY][1]) ? '' : $DB->Escape($data[CITY][1]);

	if (empty($streets))
		$teryt = $DB->GetRow('SELECT lc.id AS cid, lc2.cid AS cid2
			FROM location_cities lc
			LEFT JOIN (
				SELECT lc2.id AS cid, lc2.name AS name
				FROM location_cities lc2
			) lc2 ON lc2.cid = lc.cityid
			WHERE ((lc.name = ' . $city_name . (empty($city_name2) ? ')' : ' AND lc2.name = ' . $city_name2 . ')')
				. (empty($city_name2) ? ')' : ' OR (lc.name = ' . $city_name2 . ' AND lc2.name = ' . $city_name . '))')
				. ' AND lc.boroughid' . (count($borough_ids_to_check) == 1 ? ' = ' . $borough_ids_to_check[0]
					: ' IN (' . implode(',', $borough_ids_to_check) . ')'));
	else {
		$streets = array_unique($streets);
		foreach ($streets as &$street)
			$street = $DB->Escape($street);
		unset($street);
		$streets = implode(',', $streets);

		if (!empty($street_common_parts)) {
			foreach ($street_common_parts as &$street_common_part)
				$street_common_part = $DB->Escape($street_common_part);
			unset($street_common_part);
			$street_common_parts[] = $DB->Escape('ul.');
			$street_common_parts = implode(',', $street_common_parts);
		}

		$teryt = $DB->GetRow('SELECT lst.id AS sid, lst.cityid AS cid
			FROM location_cities lc
			LEFT JOIN (
				SELECT lc2.id AS cid, lc2.name AS name
				FROM location_cities lc2
			) lc2 ON lc2.cid = lc.cityid
			JOIN location_streets lst ON (lst.cityid = lc.id)
			JOIN location_street_types lstt ON lstt.id = lst.typeid
			WHERE ' . (isset($boroughs) ? 'lc.boroughid IN (' . $boroughs . ')'
					: '((lc.name = ' . $city_name . ')'
						. (empty($city_name2) ? ')' : ' OR (lc.name = ' . $city_name2 . ' AND lc2.name = ' . $city_name . '))')
						. ' AND lc.boroughid' . (count($borough_ids_to_check) == 1 ? ' = ' . $borough_ids_to_check[0]
							: ' IN (' . implode(',', $borough_ids_to_check) . ')'))
				. (!empty($street_common_parts) ? ' AND LOWER(lstt.name) IN (' . $street_common_parts . ')' : '')
				. ' AND (LOWER(CASE WHEN lst.name2 IS NOT NULL THEN ' . $DB->Concat('lst.name', "' '", 'lst.name2')
					.' ELSE lst.name END) IN (' . $streets . ') OR
					LOWER(CASE WHEN lst.name2 IS NOT NULL THEN ' . $DB->Concat('lst.name2', "' '", 'lst.name')
					. ' ELSE lst.name END) IN (' . $streets . '))');
	}

	if ($teryt)
		foreach ($data[HOUSE] as $house)
			if (!empty($teryt['sid']))
				$DB->Execute('INSERT INTO pna (zip, cityid, streetid, fromnumber, fromletter, tonumber, toletter, parity)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
					array($data[PNA], $teryt['cid'], $teryt['sid'], $house['fromnumber'], $house['fromletter'],
						$house['tonumber'], $house['toletter'], $house['parity']));
			else
				$DB->Execute('INSERT INTO pna (zip, cityid, fromnumber, fromletter, tonumber, toletter, parity)
					VALUES (?, ?, ?, ?, ?, ?, ?)',
					array($data[PNA], $teryt['cid'], $house['fromnumber'], $house['fromletter'],
						$house['tonumber'], $house['toletter'], $house['parity']));
	else {
		echo 'city=' . implode(',', $data[CITY]);
		if (!empty($streets))
			echo ' street=' . $streets;
		echo ' not found.' . PHP_EOL;
	}
}

if (!isset($options['file']))
	die('Missed PNA csv database file parameter!' . PHP_EOL);

$file = $options['file'];
if (!file_exists($file))
	die('PNA csv database file (' . $file . ') does not exist!' . PHP_EOL);

$fh = fopen('compress.zlib://' . $file, "r");
if (!$fh)
	die('Unable to open PNA csv database file (' . $file . ')!' . PHP_EOL);

$DB->Execute('DELETE FROM pna');

$cities_with_sections = $LMS->GetCitiesWithSections();

while (!feof($fh)) {
	$line = fgets($fh, 200);
	$data = explode(';', trim($line));
	$state = $all_states[iconv('UTF-8', 'ASCII//TRANSLIT', $data[STATE])];
	if (preg_match('/^[0-9]{2}-[0-9]{3}$/', $data[PNA])
		&& (!isset($state_list) || (isset($state_list) && isset($state_list[$state])))) {
		convert_pna_to_teryt($data);
	}
}

fclose($fh);

?>
