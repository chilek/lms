#!/usr/bin/php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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
 *  $Id: lms-pna.php,v 1.1 2012/03/03 15:27:16 chilek Exp $
 */

ini_set('error_reporting', E_ALL&~E_NOTICE);

$parameters = array(
	'C:' => 'config-file:',
	'q' => 'quiet',
	'h' => 'help',
	'v' => 'version',
	'f' => 'fetch',
	'u' => 'update',
);

foreach ($parameters as $key => $val) {
	$val = preg_replace('/:/', '', $val);
	$newkey = preg_replace('/:/', '', $key);
	$short_to_longs[$newkey] = $val;
}
$options = getopt(implode('', array_keys($parameters)), $parameters);
foreach($short_to_longs as $short => $long)
	if (array_key_exists($short, $options))
	{
		$options[$long] = $options[$short];
		unset($options[$short]);
	}

if (array_key_exists('version', $options))
{
	print <<<EOF
lms-pna.php
(C) 2001-2012 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options))
{
	print <<<EOF
lms-pna.php
(C) 2001-2012 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-f, --fetch                     fetch PNA file from server;
-u, --update                    update PNA database using PNA file;
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;

EOF;
	exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet)
{
	print <<<EOF
lms-pna.php
(C) 2001-2012 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options))
	$CONFIG_FILE = $options['config-file'];
else
	$CONFIG_FILE = '/etc/lms/lms.ini';

if (!$quiet) {
	echo "Using file ".$CONFIG_FILE." as config.\n";
}

$fetch = array_key_exists('fetch', $options);
$update = array_key_exists('update', $options);

if (!is_readable($CONFIG_FILE))
	die("Unable to read configuration file [".$CONFIG_FILE."]!\n");

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'].'/lib' : $CONFIG['directories']['lib_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
// Do some checks and load config defaults

require_once(LIB_DIR.'/config.php');

// Init database
 
$_DBTYPE = $CONFIG['database']['type'];
$_DBHOST = $CONFIG['database']['host'];
$_DBUSER = $CONFIG['database']['user'];
$_DBPASS = $CONFIG['database']['password'];
$_DBNAME = $CONFIG['database']['database'];

require(LIB_DIR.'/LMSDB.php');

$DB = DBInit($_DBTYPE, $_DBHOST, $_DBUSER, $_DBPASS, $_DBNAME);

if(!$DB)
{
	// can't working without database
	die("Fatal error: cannot connect to database!\n");
}

// Read configuration from database

if($cfg = $DB->GetAll('SELECT section, var, value FROM uiconfig WHERE disabled=0'))
	foreach($cfg as $row)
		$CONFIG[$row['section']][$row['var']] = $row['value'];

// Include required files (including sequence is important)

require_once(LIB_DIR.'/language.php');
include_once(LIB_DIR.'/definitions.php');
require_once(LIB_DIR.'/unstrip.php');
require_once(LIB_DIR.'/common.php');
require_once(LIB_DIR.'/LMS.class.php');

// Initialize Session, Auth and LMS classes

$AUTH = NULL;
$LMS = new LMS($DB, $AUTH, $CONFIG);
$LMS->ui_lang = $_ui_language;
$LMS->lang = $_language;

define('PNA', 0);
define('CITY', 1);
define('STREET', 2);
define('HOUSE', 3);
define('BOROUGH', 4);
define('DISTRICT', 5);
define('STATE', 6);

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
	global $DB, $cols;

	$cities = array();
	$cities[] = mb_ereg_replace("[[:blank:]]+\(.+\)$", "", $data[CITY]);
	if (mb_strlen(current($cities)) != mb_strlen($data[CITY]))
		$cities[] = mb_ereg_replace(".+[[:blank:]]+\((.+)\)$", "\\1", $data[CITY]);
	$data[CITY] = $cities;

	$street_suffix = NULL;
	if (!empty($data[STREET])) {
		$long = array("Generała", "Księdza", "Świętego", "Świętej", "Arcybiskupa", "Rotmistrza", "Kardynała");
		$short = array("gen.", "ks.", "św.", "św.", "abp.", "rtm.", "kard.");
		$streets = array();
		$street = mb_ereg_replace("[[:blank:]]+\(.+\)$", "", $data[STREET]);
		if (mb_ereg("[[:blank:]]+Al.$", $street))
			$street_suffix = "al.";
		elseif (mb_ereg("[[:blank:]]+Pl.$", $street))
			$street_suffix = "pl.";
		$street = mb_ereg_replace("[[:blank:]]+(Al|Pl).$", "", $street);
		$streets[] = $street;
		if (mb_ereg("([a-zA-ZęóąśłżźćńĘÓĄŚŁŻŹĆŃ]{2,})-([a-zA-ZęóąśłżźćńĘÓĄŚŁŻŹĆŃ]{2,})", $street, $regs) && count($regs) == 3)
			$streets[] = mb_ereg_replace($regs[0], $regs[2]."-".$regs[1], $street);
		for ($i = 0; $i < sizeof($long); $i++)
			$street = mb_ereg_replace($long[$i], $short[$i], $street);
		if (mb_strlen(current($streets)) != mb_strlen($street))
			$streets[] = $street;
		$street2 = implode(' ', array_reverse(mb_split(' ', $street)));
		if ($street != $street2)
			$streets[] = $street2;
		if (mb_strlen($streets[0]) != mb_strlen($data[STREET])) {
			$street2 = mb_ereg_replace(".+[[:blank:]]+\((.+)\)$", "\\1", $data[STREET]);
			for ($i = 0; $i < sizeof($long); $i++)
				$street2 = mb_ereg_replace($long[$i], $short[$i], $street2);
			$streets[] = $street2;
		}
		$data[STREET] = $streets;
	}
	else
		$data[STREET] = array($data[STREET]);

	if (!empty($data[HOUSE])) {
		$data[HOUSE] = mb_ereg_replace("[[:blank:]]", "", $data[HOUSE]);
		$data[HOUSE] = mb_split(",", $data[HOUSE]);
		$houses = array();
		foreach ($data[HOUSE] as $token) {
			$parity = 0;
			if (mb_ereg("\(n\)$", $token))
				$parity += 1;
			elseif (mb_ereg("\(p\)$", $token))
				$parity += 2;
			else $parity = 3;
			if ($parity < 3)
				$token = mb_ereg_replace("\([pn]\)$", "", $token);
			list ($from, $to) = mb_split("-", $token);
			if ($to == "DK")
				$to = "0";
			elseif (empty($to) && !empty($from))
				$to = $from;
			$houses[] = array('from' => $from, 'to' => $to, 'parity' => $parity);
		}
		$data[HOUSE] = $houses;
	}
	else
		$data[HOUSE] = array(array('from' => 0, 'to' => 0, 'parity' => 3));

	if (empty($data[STREET][0]))
		$teryt = $DB->GetRow("SELECT lc.id AS cid, lc2.cid AS cid2 
				FROM location_cities lc 
				LEFT JOIN (SELECT lc2.id AS cid, lc2.name AS name 
					FROM location_cities lc2) lc2 ON lc2.cid = lc.cityid 
				JOIN location_boroughs lb ON lb.id = lc.boroughid 
				JOIN location_districts ld ON ld.id = lb.districtid 
				JOIN location_states ls ON ls.id = ld.stateid 
				WHERE lc.name = ? ".(!empty($data[CITY][1]) ? "AND lc2.name = ? " : "")
					."AND lb.name = ? AND ld.name = ? AND ls.name = ?",
				(!empty($data[CITY][1])
					? array($data[CITY][0], $data[CITY][1], $data[BOROUGH], $data[DISTRICT], $data[STATE])
					: array($data[CITY][0], $data[BOROUGH], $data[DISTRICT], $data[STATE])));
	else
		$teryt = $DB->GetRow("SELECT lst.id AS sid, lst.cityid AS cid 
				FROM location_cities lc 
				LEFT JOIN (SELECT lc2.id AS cid, lc2.name AS name 
					FROM location_cities lc2) lc2 ON lc2.cid = lc.cityid 
				JOIN location_streets lst ON (lst.cityid = lc.id OR lst.cityid = lc2.cid) 
				JOIN location_street_types lstt ON lstt.id = lst.typeid 
				JOIN location_boroughs lb ON lb.id = lc.boroughid 
				JOIN location_districts ld ON ld.id = lb.districtid 
				JOIN location_states ls ON ls.id = ld.stateid 
				WHERE lc.name = ? ".(!empty($data[CITY][1]) ? "AND lc2.name = ? " : "")
					."AND lb.name = ? AND ld.name = ? AND ls.name = ?"
					.(!empty($street_suffix) ? " AND lstt.name = '".$street_suffix."'" : "")
					." AND ((CASE WHEN lst.name2 IS NOT NULL THEN ".$DB->Concat('lst.name', "' '", 'lst.name2')
						." ELSE lst.name END) IN ('".implode("','", $data[STREET])."') OR
						(CASE WHEN lst.name2 IS NOT NULL THEN ".$DB->Concat('lst.name2', "' '", 'lst.name')
						." ELSE lst.name END) IN ('".implode("','", $data[STREET])."'))",
				(!empty($data[CITY][1])
					? array($data[CITY][0], $data[CITY][1], $data[BOROUGH], $data[DISTRICT], $data[STATE])
					: array($data[CITY][0], $data[BOROUGH], $data[DISTRICT], $data[STATE])));
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
		printf(" not found.\n");
	}
}

if ($fetch) {
	$fh = fopen("compress.zlib://http://lms.org.pl/spispna.txt.gz", "r");
	if (!$fh)
		die("Unable to fetch http://lms.org.pl/spispna.txt.gz!\n");
	$lh = fopen("spispna.txt", "w");
	if (!$lh)
		die("Unable to create spispna.txt file!\n");

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
		die("Unable to open spispna.txt file!\n");

	$DB->Execute("DELETE FROM pna");
	while (!feof($fh)) {
		$line = fgets($fh, 200);
		$line = mb_ereg_replace("\n$", "", $line);
		$data = mb_split(";", $line);
		if (mb_ereg_match("^[[:digit:]]{2}-[[:digit:]]{3}$", $data[PNA]))
			convert_pna_to_teryt($data);
	}

	fclose($fh);
}

?>
