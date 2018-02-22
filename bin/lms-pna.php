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
	'f' => 'fetch',
	'u' => 'update',
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
-f, --fetch                     fetch PNA file from server;
-u, --update                    update PNA database using PNA file;
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

$fetch = array_key_exists('fetch', $options);
$update = array_key_exists('update', $options);

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

$boroughs = $DB->GetAll("SELECT ls.name AS state_name, ld.name AS district_name,
        lb.name AS borough_name, lb.type AS borough_type, lb.id
	FROM location_boroughs lb
	JOIN location_districts ld ON ld.id = lb.districtid
	JOIN location_states ls ON ls.id = ld.stateid");
if (empty($boroughs))
    die("TERYT database is empty!" . PHP_EOL);

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
	PNA => "PNA",
	CITY => "Miejscowość",
	STREET => "Ulica",
	HOUSE => "Numery",
	BOROUGH => "Gmina",
	DISTRICT => "Powiat",
	STATE => "Województwo"
);

function convert_pna_to_teryt($data) {
	global $DB, $borough_ids, $borough_types, $cols;

	static $street_suffix_mappings = array(
		'al.' => 'al.',
		'bulw.' => 'bulw.',
		'park' => 'park',
		'os.' => 'os.',
		'pl.' => 'pl.',
		'skw.' => 'skwer',
		'wybrz.' => 'wyb.',
	);
	static $street_long_parts = array('/Generała/', '/Księdza/', '/Świętego/', '/Świętej/', '/Arcybiskupa/', '/Rotmistrza/', '/Kardynała/');
	static $street_short_parts = array('gen.', 'ks.', 'św.', 'św.', 'abp.', 'rtm.', 'kard.');

	$cities = array();
	$cities[] = preg_replace('/[[:blank:]]+\(.+\)$/', '', $data[CITY]);
	if (mb_strlen(current($cities)) != mb_strlen($data[CITY]))
		$cities[] = preg_replace('/.+[[:blank:]]+\((.+)\)$/', '\1', $data[CITY]);
	$data[CITY] = $cities;

	$street_suffix = NULL;
	if (!empty($data[STREET])) {
		$streets = array();

		// remove parts in simple brackets
		$street = preg_replace('/[[:blank:]]+\(.+\)$/', '', trim($data[STREET]));

		// remove street prefixes/suffixes
		if (preg_match('/[[:blank:]]+(?<suffix>al\.|bulw\.|park|os\.|pl\.|skw\.|wybrz\.)$/i', $street, $m))
			$street_suffix = $street_suffix_mappings[strtolower($m['suffix'])];
		$street = preg_replace('/[[:blank:]]+(al\.|bulw\.|park|os\.|pl\.|skw\.|wybrz\.)$/i', '', $street);

		$streets[] = $street;
		if (mb_ereg("([a-zA-ZęóąśłżźćńĘÓĄŚŁŻŹĆŃ]{2,})-([a-zA-ZęóąśłżźćńĘÓĄŚŁŻŹĆŃ]{2,})", $street, $regs) && count($regs) == 3)
			$streets[] = mb_ereg_replace($regs[0], $regs[2]."-".$regs[1], $street);
		$street = preg_replace($street_long_parts, $street_short_parts, $street);
		if (mb_strlen(current($streets)) != mb_strlen($street))
			$streets[] = $street;
		$street2 = implode(' ', array_reverse(mb_split(' ', $street)));
		if ($street != $street2)
			$streets[] = $street2;
		if (mb_strlen($streets[0]) != mb_strlen($data[STREET])) {
			$street2 = preg_replace('/.+[[:blank:]]+\((.+)\)$/', '\1', $data[STREET]);
			$street2 = preg_replace($street_long_parts, $street_short_parts, $street2);
			$streets[] = $street2;
		}
		$data[STREET] = $streets;
	}
	else
		$data[STREET] = array($data[STREET]);

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
				$to = '0';
			elseif (empty($to) && !empty($from))
				$to = $from;
			$houses[] = array('from' => $from, 'to' => $to, 'parity' => $parity);
		}
		$data[HOUSE] = $houses;
	}
	else
		$data[HOUSE] = array(array('from' => 0, 'to' => 0, 'parity' => 3));

    $borough_ids_to_check = array();
	$terc = $data[STATE] . ':' . $data[DISTRICT] . ':' . $data[BOROUGH] . ':';
	foreach ($borough_types as $borough_type => $borough_type_name)
        if (isset($borough_ids[$terc . $borough_type]))
            $borough_ids_to_check[] = $borough_ids[$terc . $borough_type];
    if (empty($borough_ids_to_check))
        return;

	if (empty($data[STREET][0]))
		$teryt = $DB->GetRow("SELECT lc.id AS cid, lc2.cid AS cid2 
				FROM location_cities lc 
				LEFT JOIN (SELECT lc2.id AS cid, lc2.name AS name 
					FROM location_cities lc2) lc2 ON lc2.cid = lc.cityid 
				WHERE lc.name = ?" . (!empty($data[CITY][1]) ? " AND lc2.name = ?" : "")
					." AND lc.boroughid" . (count($borough_ids_to_check) == 1 ? " = " . $borough_ids_to_check[0]
                        : " IN (" . implode(',', $borough_ids_to_check) . ")"),
				(!empty($data[CITY][1])
					? array($data[CITY][0], $data[CITY][1])
					: array($data[CITY][0])));
	else
		$teryt = $DB->GetRow("SELECT lst.id AS sid, lst.cityid AS cid 
				FROM location_cities lc 
				LEFT JOIN (SELECT lc2.id AS cid, lc2.name AS name 
					FROM location_cities lc2) lc2 ON lc2.cid = lc.cityid 
				JOIN location_streets lst ON (lst.cityid = lc.id OR lst.cityid = lc2.cid) 
				JOIN location_street_types lstt ON lstt.id = lst.typeid 
				WHERE lc.name = ?" . (!empty($data[CITY][1]) ? " AND lc2.name = ?" : "")
					. " AND lc.boroughid" . (count($borough_ids_to_check) == 1 ? " = " . $borough_ids_to_check[0]
                        : " IN (" . implode(',', $borough_ids_to_check) . ")")
					. (!empty($street_suffix) ? " AND lstt.name = '".$street_suffix."'" : "")
					. " AND ((CASE WHEN lst.name2 IS NOT NULL THEN ".$DB->Concat('lst.name', "' '", 'lst.name2')
						." ELSE lst.name END) IN ('".implode("','", $data[STREET])."') OR
						(CASE WHEN lst.name2 IS NOT NULL THEN ".$DB->Concat('lst.name2', "' '", 'lst.name')
						." ELSE lst.name END) IN ('".implode("','", $data[STREET])."'))",
				(!empty($data[CITY][1])
					? array($data[CITY][0], $data[CITY][1])
					: array($data[CITY][0])));

	if ($teryt)
		foreach ($data[HOUSE] as $house)
			if (!empty($teryt['sid']))
				$DB->Execute("INSERT INTO pna (zip, cityid, streetid, fromhouse, tohouse, parity)
					VALUES (?, ?, ?, ?, ?, ?)",
					array($data[PNA], $teryt['cid'], $teryt['sid'], $house['from'], $house['to'], $house['parity']));
			else
				$DB->Execute("INSERT INTO pna (zip, cityid, fromhouse, tohouse, parity)
					VALUES (?, ?, ?, ?, ?)",
					array($data[PNA], $teryt['cid'], $house['from'], $house['to'], $house['parity']));
	else {
		printf("city=%s", implode(",", $data[CITY]));
		if (!empty($data[STREET][0]))
			printf(" street=%s", implode(",", $data[STREET]));
		printf(" not found." . PHP_EOL);
	}
}

if ($fetch) {
	$fh = fopen("compress.zlib://http://lms.org.pl/spispna.txt.gz", "r");
	if (!$fh)
		die("Unable to fetch http://lms.org.pl/spispna.txt.gz!" . PHP_EOL);
	$lh = fopen("spispna.txt", "w");
	if (!$lh)
		die("Unable to create spispna.txt file!" . PHP_EOL);

	while (!feof($fh)) {
		$line = fgets($fh, 1024);
		fputs($lh, $line);
	}

	fclose($fh);
	fclose($lh);
}

if ($update) {
	$fh = fopen("spispna.txt", "r");
	if (!$fh)
		die("Unable to open spispna.txt file!" . PHP_EOL);

	$DB->Execute("DELETE FROM pna");

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
}

?>
