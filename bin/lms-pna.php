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
    'p' => 'subpattern-failback',
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
-p, --subpattern-failback       street name subpattern matches;
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

$subpattern_failback = isset($options['subpattern-failback']);

$stderr = fopen('php://stderr', 'w');

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

define('PNA', 0);
define('CITY', 1);
define('STREET', 2);
define('HOUSE', 3);
define('BOROUGH', 4);
define('DISTRICT', 5);
define('STATE', 6);

$cols = array(
	PNA => 'PNA',
	CITY => 'Miejscowość',
	STREET => 'Ulica',
	HOUSE => 'Numery',
	BOROUGH => 'Gmina',
	DISTRICT => 'Powiat',
	STATE => 'Województwo'
);

function get_city_ids() {
	$DB = LMSDB::getInstance();

	$cities = $DB->GetAll('SELECT LOWER(ls.name) AS state_name, LOWER(ld.name) AS district_name,
			LOWER(CASE WHEN ld.name = ? AND lb.name NOT ?LIKE? ? THEN ' . $DB->Concat("'Warszawa-'", 'lb.name') . ' ELSE lb.name END) AS borough_name,
			LOWER(CASE WHEN ld.name = ? AND lc.name NOT ?LIKE? ? THEN ' . $DB->Concat("'Warszawa-'", 'lc.name') . ' ELSE lc.name END) AS city_name,
			lc.id
		FROM location_cities lc
		JOIN location_boroughs lb ON lc.boroughid = lb.id
		JOIN location_districts ld ON ld.id = lb.districtid
		JOIN location_states ls ON ls.id = ld.stateid
		WHERE lc.cityid IS NULL',
		array('Warszawa', 'Warszawa%', 'Warszawa', 'Warszawa%'));

	$city_ids = array();
	foreach ($cities as $city)
		$city_ids[$city['state_name'] . '_' . $city['district_name'] . '_'
			. $city['borough_name'] . '_' . $city['city_name']] = $city['id'];

	return $city_ids;
}

function convert_pna_to_teryt($data) {
	global $LMS, $subpattern_failback;
	static $cities_with_sections = null;
	static $city_ids = array();
	static $street_common_part_replaces = array('skw.' => 'skwer', 'wybrz.' => 'wyb.');
	static $street_short_to_long_part_replaces = array('ul.' => 'ulica', 'al.' => 'aleja',
		'pl.' => 'plac', 'os.' => 'osiedle', 'bulw.' => 'bulwar', 'wyb.' => 'wybrzeże');

	if (is_null($cities_with_sections)) {
		$cities_with_sections = $LMS->GetCitiesWithSections();
		if (empty($cities_with_sections))
			$cities_with_sections = array();
	}

	if (empty($city_ids)) {
		$city_ids = get_city_ids();
		if (empty($city_ids))
			die('TERYT database is empty!' . PHP_EOL);
	}

	$state_name = mb_strtolower($data[STATE]);
	$district_name = mb_strtolower($data[DISTRICT]);
	$borough_name = mb_strtolower($data[BOROUGH]);

	$city_name = preg_replace('/[[:blank:]]+\(.+\)$/', '', $data[CITY]);
	$orig_city_name = $city_name;
	$city_name = mb_strtolower($city_name);
	if (mb_strlen($city_name) != mb_strlen($data[CITY]) && isset($cities_with_sections[$city_name]))
		$borough_name = $city_name = preg_replace('/^.+[[:blank:]]+\((.+)\)$/', ($city_name == 'warszawa' ? 'warszawa-' : '') . '$1',
            mb_strtolower($data[CITY]));

	if (isset($city_ids[$state_name . '_' . $district_name . '_' . $borough_name . '_' . $city_name]))
		$cityid = $city_ids[$state_name . '_' . $district_name . '_' . $borough_name . '_' . $city_name];
    else
	    $cityid = null;

	$street_common_parts = array();
	$streets = array();
	if (!empty($data[STREET])) {
		// remove strange characters from beginning and ending of street name
		$street = trim($data[STREET]);
		$street = preg_replace('/"(.+)"/', '$1', $street);

		// replace double quotes by single quote
		$street = str_replace('""', '"', $street);

		$orig_street_name = $street;

		if (!empty($cityid)) {
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
	}

	if (!empty($data[HOUSE])) {
		$house_numbers = preg_replace('/[[:blank:]]/', '', $data[HOUSE]);
		$house_numbers = explode(',', $house_numbers);
		$houses = array();
		foreach ($house_numbers as $token) {
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
		$house_numbers = $houses;
	}
	else
		$house_numbers = array(array('fromnumber' => null, 'fromletter' => null,
			'tonumber' => null, 'toletter' => null,
			'parity' => 3));

	$DB = LMSDB::getInstance();

    if (empty($cityid)) {
		echo 'city: ' . $orig_city_name . (isset($orig_street_name) ? ', street: ' . $orig_street_name : '')
            . ' not found.' . PHP_EOL;
		$streetid = null;
		if (!isset($orig_street_name))
			$orig_street_name = null;
    } elseif (empty($streets))
		$streetid = $orig_street_name = null;
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

		$streetid = $DB->GetOne('SELECT lst.id
			FROM location_cities lc
			JOIN location_streets lst ON (lst.cityid = lc.id)
			JOIN location_street_types lstt ON lstt.id = lst.typeid
			WHERE lc.id = ?'
				. (!empty($street_common_parts) ? ' AND LOWER(lstt.name) IN (' . $street_common_parts . ')' : '')
				. ' AND (LOWER(CASE WHEN lst.name2 IS NOT NULL THEN ' . $DB->Concat('lst.name', "' '", 'lst.name2')
					.' ELSE lst.name END) IN (' . $streets . ') OR
					LOWER(CASE WHEN lst.name2 IS NOT NULL THEN ' . $DB->Concat('lst.name2', "' '", 'lst.name')
					. ' ELSE lst.name END) IN (' . $streets . '))',
			array($cityid));
		if ($subpattern_failback && empty($streetid) && mb_strlen($orig_street_name) >= 10) {
		    $patterned_streets = $DB->GetCol('SELECT lst.id
		        FROM location_cities lc
		        JOIN location_streets lst ON lst.cityid = lc.id
		        WHERE lc.id = ? AND LENGTH(lst.name) >= 10
		            AND LOWER(?) ?LIKE? ' . $DB->Concat("'%'", 'LOWER(lst.name)', "'%'"),
                array($cityid, $orig_street_name));

		    if (empty($patterned_streets) || count($patterned_streets) > 1)
    		    $streetid = null;
		    else
		        $streetid = $patterned_streets[0];
		}
		if (empty($streetid)) {
			$streetid = null;
			echo 'city: ' . $orig_city_name . ', street: ' . $orig_street_name . ' not found.' . PHP_EOL;
		}
	}

	foreach ($house_numbers as $house)
		$DB->Execute('INSERT INTO pna (zip, cityid, cityname, streetid, streetname, fromnumber, fromletter, tonumber, toletter, parity)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array($data[PNA], $cityid, $orig_city_name, $streetid, $orig_street_name,
				$house['fromnumber'], $house['fromletter'],
				$house['tonumber'], $house['toletter'], $house['parity']));
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
