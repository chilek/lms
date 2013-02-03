<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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
 *  $Id: uke.php,v 1.1 2012/03/03 15:27:16 chilek Exp $
 */

/*
ini_set('error_reporting', E_ALL&~E_NOTICE);

$parameters = array(
	'C:' => 'config-file:',
	'q' => 'quiet',
	'h' => 'help',
	'v' => 'version',
	'm:' => 'message-file:',
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
lms-uke.php
(C) 2001-2013 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options))
{
	print <<<EOF
lms-uke.php
(C) 2001-2013 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-m, --message-file=<message-file>       name of message file;
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
lms-uke.php
(C) 2001-2013 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options))
	$CONFIG_FILE = $options['config-file'];
else
	$CONFIG_FILE = '/etc/lms/lms.ini';

if (!$quiet)
	echo "Using file ".$CONFIG_FILE." as config.\n";

if (!is_readable($CONFIG_FILE))
	die('Unable to read configuration file ['.$CONFIG_FILE.']!'); 

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
*/

define(ZIP_CODE, '15-950');

$borough_types = array(
	1 => 'gm. miejska',
	2 => 'gm. wiejska',
	3 => 'gm. miejsko-wiejska',
	4 => 'gm. miejsko-wiejska',
//	4 => 'miasto w gminie miejsko-wiejskiej',
	5 => 'gm. miejsko-wiejska',
//	5 => 'obszar wiejski gminy miejsko-wiejskiej',
	8 => 'dzielnica gminy Warszawa-Centrum',
	9 => 'dzielnica',
);

$linktypes = array(
	array('linia' => "linia kablowa", 'trakt' => "podziemny", 'technologia' => "kablowa", 'typ' => "UTP",
		'pasmo' => "", 'szybkosc_radia' => "",
		'technologia_dostepu' => "Ethernet", 'szybkosc' => "100", 'liczba_jednostek' => "1",
		'jednostka' => "linie w kablu",
		'specyficzne' => array('szybkosc_dystrybucyjna' => "100")),
	array('linia' => "linia bezprzewodowa", 'trakt' => "", 'technologia' => "radiowa", 'typ' => "WLAN",
		'pasmo' => "5GHz", 'szybkosc_radia' => "100",
		'technologia_dostepu' => "WLAN-urządzenie abonenckie", 'szybkosc' => "54", 'liczba_jednostek' => "1",
		'jednostka' => "kanały",
		'specyficzne' => array('szybkosc_dystrybucyjna' => "100")),
	array('linia' => "linia kablowa", 'trakt' => "podziemny w kanalizacji", 'technologia' => "światłowodowa", 'typ' => "SMF", 
		'pasmo' => "", 'szybkosc_radia' => "",
		'technologia_dostepu' => "FTTH", 'szybkosc' => "100", 'liczba_jednostek' => "2",
		'jednostka' => "włókna",
		'specyficzne' => array('szybkosc_dystrybucyjna' => "1000"))
);

// prepare info about network devices from lms database
$netdevices = $DB->GetAll("SELECT nd.id, nd.location_city, nd.location_street, nd.location_house, nd.location_flat, nd.location, 
		(SELECT ls.name FROM location_cities lc
			JOIN location_boroughs lb ON lb.id = lc.boroughid
			JOIN location_districts ld ON ld.id = lb.districtid
			JOIN location_states ls ON ls.id = ld.stateid
			WHERE lc.id = nd.location_city) AS area_woj,
		(SELECT ld.name FROM location_cities lc
			JOIN location_boroughs lb ON lb.id = lc.boroughid
			JOIN location_districts ld ON ld.id = lb.districtid
			JOIN location_states ls ON ls.id = ld.stateid
			WHERE lc.id = nd.location_city) AS area_pow,
		(SELECT lb.name FROM location_boroughs lb JOIN location_cities lc ON lc.boroughid = lb.id WHERE lc.id = nd.location_city) AS area_gmi, 
		(SELECT ".$DB->Concat('ls.ident', "'_'", 'ld.ident', "'_'", 'lb.ident', "'_'", 'lb.type')." 
			FROM location_cities lc 
			JOIN location_boroughs lb ON lb.id = lc.boroughid 
			JOIN location_districts ld ON ld.id = lb.districtid 
			JOIN location_states ls ON ls.id = ld.stateid 
			WHERE lc.id = nd.location_city) AS area_terc, 
		(SELECT lc.name FROM location_cities lc WHERE lc.id = nd.location_city) AS area_city, 
		(SELECT lc.ident FROM location_cities lc WHERE lc.id = nd.location_city) AS area_simc, 
		(SELECT tu.cecha FROM teryt_ulic tu WHERE tu.id = nd.location_street) AS address_cecha, 
		(SELECT (CASE WHEN ls.name2 IS NOT NULL THEN ".$DB->Concat('ls.name2' , "' '", 'ls.name')." ELSE ls.name END) AS name 
			FROM location_streets ls WHERE ls.id = nd.location_street) AS address_ulica, 
		(SELECT tu.sym_ul FROM teryt_ulic tu WHERE tu.id = nd.location_street) AS address_symul, 
		(CASE WHEN nd.location_flat IS NULL THEN nd.location_house ELSE "
			.$DB->Concat('nd.location_house ', "'/'", 'nd.location_flat')." END) AS address_budynek, 
		ports, 
		(CASE WHEN nlsrccable.nlsrccount IS NULL THEN 0 ELSE nlsrccable.nlsrccount END) + (CASE WHEN nldstcable.nldstcount IS NULL THEN 0 ELSE nldstcable.nldstcount END) AS cabledistports, 
		(CASE WHEN nlsrcradio.nlsrccount IS NULL THEN 0 ELSE nlsrcradio.nlsrccount END) + (CASE WHEN nldstradio.nldstcount IS NULL THEN 0 ELSE nldstradio.nldstcount END) AS radiodistports, 
		(CASE WHEN nlsrcfiber.nlsrccount IS NULL THEN 0 ELSE nlsrcfiber.nlsrccount END) + (CASE WHEN nldstfiber.nldstcount IS NULL THEN 0 ELSE nldstfiber.nldstcount END) AS fiberdistports, 
		(CASE WHEN pndpcable.portcount IS NULL THEN 0 ELSE pndpcable.portcount END) AS cablepersonalaccessports, 
		(CASE WHEN cndpcable.portcount IS NULL THEN 0 ELSE cndpcable.portcount END) AS cablecommercialaccessports, 
		(CASE WHEN pndpradio.portcount IS NULL THEN 0 ELSE pndpradio.portcount END) AS radiopersonalaccessports, 
		(CASE WHEN cndpradio.portcount IS NULL THEN 0 ELSE cndpradio.portcount END) AS radiocommercialaccessports, 
		(CASE WHEN pndpfiber.portcount IS NULL THEN 0 ELSE pndpfiber.portcount END) AS fiberpersonalaccessports, 
		(CASE WHEN cndpfiber.portcount IS NULL THEN 0 ELSE cndpfiber.portcount END) AS fibercommercialaccessports, 
		longitude, latitude 
		FROM netdevices nd 
		LEFT JOIN (SELECT netlinks.src AS src, COUNT(netlinks.id) AS nlsrccount FROM netlinks WHERE type = 0 GROUP BY netlinks.src) nlsrccable ON nd.id = nlsrccable.src 
		LEFT JOIN (SELECT netlinks.dst AS dst, COUNT(netlinks.id) AS nldstcount FROM netlinks WHERE type = 0 GROUP BY netlinks.dst) nldstcable ON nd.id = nldstcable.dst 
		LEFT JOIN (SELECT netlinks.src AS src, COUNT(netlinks.id) AS nlsrccount FROM netlinks WHERE type = 1 GROUP BY netlinks.src) nlsrcradio ON nd.id = nlsrcradio.src 
		LEFT JOIN (SELECT netlinks.dst AS dst, COUNT(netlinks.id) AS nldstcount FROM netlinks WHERE type = 1 GROUP BY netlinks.dst) nldstradio ON nd.id = nldstradio.dst 
		LEFT JOIN (SELECT netlinks.src AS src, COUNT(netlinks.id) AS nlsrccount FROM netlinks WHERE type = 2 GROUP BY netlinks.src) nlsrcfiber ON nd.id = nlsrcfiber.src 
		LEFT JOIN (SELECT netlinks.dst AS dst, COUNT(netlinks.id) AS nldstcount FROM netlinks WHERE type = 2 GROUP BY netlinks.dst) nldstfiber ON nd.id = nldstfiber.dst 
		LEFT JOIN (SELECT netdev, COUNT(port) AS portcount FROM nodes LEFT JOIN customers ON customers.id = nodes.ownerid WHERE customers.type = 0 AND linktype = 0 GROUP BY netdev) pndpcable ON pndpcable.netdev = nd.id 
		LEFT JOIN (SELECT netdev, COUNT(port) AS portcount FROM nodes LEFT JOIN customers ON customers.id = nodes.ownerid WHERE customers.type = 1 AND linktype = 0 GROUP BY netdev) cndpcable ON cndpcable.netdev = nd.id 
		LEFT JOIN (SELECT netdev, COUNT(port) AS portcount FROM nodes LEFT JOIN customers ON customers.id = nodes.ownerid WHERE customers.type = 0 AND linktype = 1 GROUP BY netdev) pndpradio ON pndpradio.netdev = nd.id 
		LEFT JOIN (SELECT netdev, COUNT(port) AS portcount FROM nodes LEFT JOIN customers ON customers.id = nodes.ownerid WHERE customers.type = 1 AND linktype = 1 GROUP BY netdev) cndpradio ON cndpradio.netdev = nd.id 
		LEFT JOIN (SELECT netdev, COUNT(port) AS portcount FROM nodes LEFT JOIN customers ON customers.id = nodes.ownerid WHERE customers.type = 0 AND linktype = 2 GROUP BY netdev) pndpfiber ON pndpfiber.netdev = nd.id 
		LEFT JOIN (SELECT netdev, COUNT(port) AS portcount FROM nodes LEFT JOIN customers ON customers.id = nodes.ownerid WHERE customers.type = 1 AND linktype = 2 GROUP BY netdev) cndpfiber ON cndpfiber.netdev = nd.id 
		WHERE EXISTS (SELECT id FROM netlinks nl WHERE nl.src = nd.id OR nl.dst = nd.id) 
		ORDER BY name");

// prepare info about network nodes
$netnodes = array();
$netdevs = array();
$netnodeid = 1;
if ($netdevices)
	foreach ($netdevices as $netdevid => $netdevice) {
		$netdevice['netnodename'] = $netdevices[$netdevid]['netnodename'] =
			empty($netdevice['location_city']) ?
				$netdevice['location'] :
				implode('_', array($netdevice['location_city'], $netdevice['location_street'],
					$netdevice['location_house'], $netdevice['location_flat']));
		$netnodename = $netdevice['netnodename'];
		if (!array_key_exists($netnodename, $netnodes)) {
			$netnodes[$netnodename]['id'] = $netnodeid;
			$netnodes[$netnodename]['location'] = $netdevice['location'];
			$netnodes[$netnodename]['ports'] = 0;
			$netnodes[$netnodename]['distports'] = 0;
			$netnodes[$netnodename]['cabledistports'] = 0;
			$netnodes[$netnodename]['radiodistports'] = 0;
			$netnodes[$netnodename]['fiberdistports'] = 0;
			$netnodes[$netnodename]['personalaccessports'] = 0;
			$netnodes[$netnodename]['commercialaccessports'] = 0;
			$netnodes[$netnodename]['cablepersonalaccessports'] = 0;
			$netnodes[$netnodename]['cablecommercialaccessports'] = 0;
			$netnodes[$netnodename]['radiopersonalaccessports'] = 0;
			$netnodes[$netnodename]['radiocommercialaccessports'] = 0;
			$netnodes[$netnodename]['fiberpersonalaccessports'] = 0;
			$netnodes[$netnodename]['fibercommercialaccessports'] = 0;
			if (isset($netdevice['area_woj'])) {
				$netnodes[$netnodename]['area_woj'] = $netdevice['area_woj'];
				$netnodes[$netnodename]['area_pow'] = $netdevice['area_pow'];
				$netnodes[$netnodename]['area_gmi'] = $netdevice['area_gmi'];
				list ($area_woj, $area_pow, $area_gmi, $area_rodz) = explode('_', $netdevice['area_terc']);
				$netnodes[$netnodename]['area_terc'] = sprintf("%02d%02d%02d%s", $area_woj, $area_pow, $area_gmi, $area_rodz);
				$netnodes[$netnodename]['area_rodz_gmi'] = $borough_types[intval($area_rodz)];
				$netnodes[$netnodename]['area_city'] = $netdevice['area_city'];
				$netnodes[$netnodename]['area_simc'] = sprintf("%07d", $netdevice['area_simc']);
				$netnodes[$netnodename]['address_cecha'] = $netdevice['address_cecha'];
				$netnodes[$netnodename]['address_ulica'] = $netdevice['address_ulica'];
				$netnodes[$netnodename]['address_symul'] = sprintf("%05d", $netdevice['address_symul']);
				$netnodes[$netnodename]['address_budynek'] = $netdevice['address_budynek'];
			}
			$netnodes[$netnodename]['ranges'] = array();
			$netnodes[$netnodename]['netdevices'] = array();
			$netnodes[$netnodename]['longitudes'] = array();
			$netnodes[$netnodename]['latitudes'] = array();
			$netnodeid++;
		}
		$netnodes[$netnodename]['netdevices'][] = $netdevice['id'];
		$netnodes[$netnodename]['ports'] += $netdevice['ports'];
		$netnodes[$netnodename]['cabledistports'] += $netdevice['cabledistports'];
		$netnodes[$netnodename]['radiodistports'] += $netdevice['radiodistports'];
		$netnodes[$netnodename]['fiberdistports'] += $netdevice['fiberdistports'];
		$netnodes[$netnodename]['cablepersonalaccessports'] += $netdevice['cablepersonalaccessports'];
		$netnodes[$netnodename]['cablecommercialaccessports'] += $netdevice['cablecommercialaccessports'];
		$netnodes[$netnodename]['radiopersonalaccessports'] += $netdevice['radiopersonalaccessports'];
		$netnodes[$netnodename]['radiocommercialaccessports'] += $netdevice['radiocommercialaccessports'];
		$netnodes[$netnodename]['fiberpersonalaccessports'] += $netdevice['fiberpersonalaccessports'];
		$netnodes[$netnodename]['fibercommercialaccessports'] += $netdevice['fibercommercialaccessports'];
		$netnodes[$netnodename]['personalaccessports'] += $netdevice['cablepersonalaccessports']
			+ $netdevice['radiopersonalaccessports'] + $netdevice['fiberpersonalaccessports'];
		$netnodes[$netnodename]['commercialaccessports'] += $netdevice['cablecommercialaccessports']
			+ $netdevice['radiocommercialaccessports'] + $netdevice['fibercommercialaccessports'];
		if (isset($netdevice['longitude'])) {
			$netnodes[$netnodename]['longitudes'][] = $netdevice['longitude'];
			$netnodes[$netnodename]['latitudes'][] = $netdevice['latitude'];
		}
		$netdevs[$netdevice['id']] = $netnodename;
	}

$netintid = 1;
$netrangeid = 1;
$snetnodes = '';
$snetinterfaces = '';
$snetranges = '';
if ($netnodes)
foreach ($netnodes as $netnodename => $netnode) {
	//print_r($netnode);
	// if teryt location is not set then try to get location address from network node name
	if (!isset($netnode['area_woj'])) {
		$address = mb_split("[[:blank:]]+", $netnodename);
		$street = mb_ereg_replace("[[:blank:]][[:alnum:]]+$", "", $netnodename);
	}
	// count gps coordinates basing on average longitude and latitude of all network devices located in this network node
	if (count($netnode['longitudes'])) {
		$netnode['longitude'] = $netnode['latitude'] = 0.0;
		foreach ($netnode['longitudes'] as $longitude)
			$netnode['longitude'] += floatval($longitude);
		foreach ($netnode['latitudes'] as $latitude)
			$netnode['latitude'] += floatval($latitude);
		$netnode['longitude'] = str_replace(',', '.', sprintf('%06f', $netnode['longitude'] / count($netnode['longitudes'])));
		$netnode['latitude'] = str_replace(',', '.', sprintf('%06f', $netnode['latitude'] / count($netnode['latitudes'])));
	}
	// save info about network nodes
	if (empty($netnode['address_ulica'])) {
		$netnode['address_ulica'] = "BRAK ULICY";
		$netnode['address_symul'] = "99999";
	}
	if (empty($netnode['address_symul'])) {
		$netnode['address_ulica'] = "ULICA SPOZA ZAKRESU";
		$netnode['address_symul'] = "99998";
	}
	$snetnodes .= $netnode['id'].",własny,skrzynka,"
		.(isset($netnode['area_woj'])
			? implode(',', array($netnode['area_woj'], $netnode['area_pow'], $netnode['area_gmi']." (".$netnode['area_rodz_gmi'].")",
				$netnode['area_terc'], $netnode['area_city'], $netnode['area_simc'], $netnode['address_cecha'],
				$netnode['address_ulica'], $netnode['address_symul'], $netnode['address_budynek'], ZIP_CODE))
			: "LMS netdevinfo ID's: ".implode(' ', $netnode['netdevices']).",".implode(',', array_fill(0, 10, '')))
		.",0,".(isset($netnode['longitude']) ? $netnode['latitude'].",".$netnode['longitude'] : ",")
		.",Nie,".($netnode['cabledistports'] + $netonode['radiodistports'] + $netnode['fiberdistports'] > 1
			|| $netnode['personalaccessports'] + $netnode['commercialaccessports'] == 0 ? "Tak" : "Nie").","
		.($netnode['cablepersonalaccessports'] || $netnode['cablecommercialaccessports']
			|| $netnode['radiopersonalaccessports'] || $netnode['radiocommercialaccessports']
			|| $netnode['fiberpersonalaccessports'] || $netnode['fibercommercialaccessports'] ? "Tak" : "Nie").",\n";

	// save info about network interfaces located in distribution layer
	if ($netnode['cabledistports'] + $netnode['radiodistports'] + $netnode['fiberdistports'] > 1) {
		if ($netnode['cabledistports']) {
			$snetinterfaces .= $netintid.",".$netnode['id'].",sieć szkieletowa lub dystrybucyjna,kablowe,,Ethernet,,0,100,0,"
				.$netnode['cabledistports'].","
				.$netnode['cabledistports'].",0,Nie,0,0\n";
			$netintid++;
		}
		if ($netnode['radiodistports']) {
			$snetinterfaces .= $netintid.",".$netnode['id'].",sieć szkieletowa lub dystrybucyjna,radiowe,,Ethernet,,0,54,0,"
				.$netnode['radiodistports'].","
				.$netnode['radiodistports'].",0,Nie,0,0\n";
			$netintid++;
		}
		if ($netnode['fiberdistports']) {
			$snetinterfaces .= $netintid.",".$netnode['id'].",sieć szkieletowa lub dystrybucyjna,światłowodowe niezwielokrotnione,,Ethernet,,0,1000,0,"
				.$netnode['fiberdistports'].","
				.$netnode['fiberdistports'].",0,Nie,0,0\n";
			$netintid++;
		}
	}
	// save info about network interfaces located in access layer
	if ($netnode['cablepersonalaccessports'] + $netnode['cablecommercialaccessports']) {
		$snetinterfaces .= $netintid.",".$netnode['id'].",sieć dostępowa,kablowe,,Ethernet,100,,,,"
			.($netnode['ports'] - $netnode['cabledistports'] - $netnode['radiodistports'] - $netnode['fiberdistports']
				- $netnode['radiopersonalaccessports'] - $netnode['radiocommercialaccessports']
				- $netnode['fiberpersonalaccessports'] - $netnode['fibercommercialaccessports']).","
			.($netnode['cablepersonalaccessports'] + $netnode['cablecommercialaccessports']).","
			.($netnode['ports'] - $netnode['cabledistports'] - $netnode['radiodistports'] - $netnode['fiberdistports']
				- $netnode['personalaccessports'] - $netnode['commercialaccessports']).",Nie,0,0\n";
		$netintid++;
	}
	if ($netnode['radiopersonalaccessports'] + $netnode['radiocommercialaccessports']) {
		$snetinterfaces .= $netintid.",".$netnode['id'].",sieć dostępowa,radiowe,,Ethernet,54,,,,"
			.($netnode['radiopersonalaccessports'] + $netnode['radiocommercialaccessports']).","
			.($netnode['radiopersonalaccessports'] + $netnode['radiocommercialaccessports']).","
			."0,Nie,0,0\n";
		$netintid++;
	}
	if ($netnode['fiberpersonalaccessports'] + $netnode['fibercommercialaccessports']) {
		$snetinterfaces .= $netintid.",".$netnode['id'].",sieć dostępowa,światłowodowe niezwielokrotnione,,Ethernet,100,,,,"
			.($netnode['fiberpersonalaccessports'] + $netnode['fibercommercialaccessports']).","
			.($netnode['fiberpersonalaccessports'] + $netnode['fibercommercialaccessports']).","
			."0,Nie,0,0\n";
		$netintid++;
	}

	// save info about network ranges
	$ranges = $DB->GetAll("SELECT n.linktype, n.location_street, n.location_city, n.location_house 
		FROM nodes n 
		WHERE n.ownerid > 0 AND n.location_city <> 0 AND n.netdev IN (".implode(',', $netnode['netdevices']).") 
		GROUP BY n.linktype, n.location_street, n.location_city, n.location_house",
		array($netdevice['id']));
	if ($ranges)
		foreach ($ranges as $range) {
			// get teryt info for group of computers connected to network node
			$teryt = $DB->GetRow("SELECT 
				(SELECT ls.name FROM location_cities lc
					JOIN location_boroughs lb ON lb.id = lc.boroughid
					JOIN location_districts ld ON ld.id = lb.districtid
					JOIN location_states ls ON ls.id = ld.stateid
					WHERE lc.id = ?) AS area_woj,
				(SELECT ld.name FROM location_cities lc
					JOIN location_boroughs lb ON lb.id = lc.boroughid
					JOIN location_districts ld ON ld.id = lb.districtid
					JOIN location_states ls ON ls.id = ld.stateid
					WHERE lc.id = ?) AS area_pow,
				(SELECT lb.name FROM location_boroughs lb JOIN location_cities lc ON lc.boroughid = lb.id WHERE lc.id = ?) AS area_gmi, 
				(SELECT ".$DB->Concat('ls.ident', "'_'", 'ld.ident', "'_'", 'lb.ident', "'_'", 'lb.type')." 
					FROM location_cities lc 
					JOIN location_boroughs lb ON lb.id = lc.boroughid 
					JOIN location_districts ld ON ld.id = lb.districtid 
					JOIN location_states ls ON ls.id = ld.stateid 
					WHERE lc.id = ?) AS area_terc, 
				(SELECT lc.name FROM location_cities lc WHERE lc.id = ?) AS area_city, 
				(SELECT lc.ident FROM location_cities lc WHERE lc.id = ?) AS area_simc, 
				(SELECT tu.cecha FROM teryt_ulic tu WHERE tu.id = ?) AS address_cecha, 
				(SELECT (CASE WHEN ls.name2 IS NOT NULL THEN ".$DB->Concat('ls.name2', "' '", 'ls.name')." ELSE ls.name END) AS name 
					FROM location_streets ls WHERE ls.id = ?) AS address_ulica, 
				(SELECT tu.sym_ul FROM teryt_ulic tu WHERE tu.id = ?) AS address_symul",
				array($range['location_city'], $range['location_city'], $range['location_city'],
					$range['location_city'], $range['location_city'], $range['location_city'],
					$range['location_street'], $range['location_street'], $range['location_street']));
			list ($area_woj, $area_pow, $area_gmi, $area_rodz) = explode('_', $teryt['area_terc']);
			$teryt['area_terc'] = sprintf("%02d%02d%02d%s", $area_woj, $area_pow, $area_gmi, $area_rodz);
			$teryt['area_simc'] = sprintf("%07d", $teryt['area_simc']);
			$teryt['address_budynek'] = $range['location_house'];
			if (empty($teryt['address_ulica'])) {
				$teryt['address_ulica'] = "BRAK ULICY";
				$teryt['address_symul'] = "99999";
			}
			if (empty($teryt['address_symul'])) {
				$teryt['address_ulica'] = "ULICA SPOZA ZAKRESU";
				$teryt['address_symul'] = "99998";
			}
			$teryt['address_symul'] = sprintf("%05d", $teryt['address_symul']);

			// get info about computers connected to network node
			$nodes = $DB->GetAll("SELECT na.nodeid, c.type, "
				.$DB->GroupConcat("DISTINCT (CASE t.type WHEN ".TARIFF_INTERNET." THEN 'INT'
					WHEN ".TARIFF_PHONE." THEN 'TEL'
					WHEN ".TARIFF_TV." THEN 'TV'
					ELSE 'INT' END)")." AS servicetypes, SUM(t.downceil) AS downstream, SUM(t.upceil) AS upstream 
				FROM nodeassignments na 
				JOIN nodes n ON n.id = na.nodeid 
				JOIN assignments a ON a.id = na.assignmentid 
				JOIN tariffs t ON t.id = a.tariffid 
				JOIN customers c ON c.id = n.ownerid 
				LEFT JOIN (SELECT aa.customerid AS cid, COUNT(id) AS total FROM assignments aa
					WHERE aa.tariffid = 0 AND aa.liabilityid = 0 
						AND (aa.datefrom < ?NOW? OR aa.datefrom = 0) 
						AND (aa.dateto > ?NOW? OR aa.dateto = 0) GROUP BY aa.customerid) 
					AS allsuspended ON allsuspended.cid = c.id 
				WHERE n.ownerid > 0 AND n.netdev > 0 AND n.linktype = ? AND n.location_city = ? 
					AND (n.location_street = ? OR n.location_street IS NULL) AND n.location_house = ? 
					AND a.suspended = 0 AND a.period IN (".implode(',', array(YEARLY, HALFYEARLY, QUARTERLY, MONTHLY)).") 
					AND (a.datefrom = 0 OR a.datefrom < ?NOW?) AND (a.dateto = 0 OR a.dateto > ?NOW?) 
					AND allsuspended.total IS NULL 
				GROUP BY na.nodeid, c.type",
				array($range['linktype'], $range['location_city'], $range['location_street'], $range['location_house']));

			// count all kinds of link speeds for computers connected to this network node
			$personalnodes = array();
			$commercialnodes = array();
			if ($nodes)
				foreach ($nodes as $node) {
					if ($node['downstream'] < 1000)
						$set = 0;
					elseif ($node['downstream'] == 1000)
						$set = 1;
					elseif ($node['downstream'] < 2000)
						$set = 2;
					elseif ($node['downstream'] == 2000)
						$set = 3;
					elseif ($node['downstream'] < 4000)
						$set = 4;
					elseif ($node['downstream'] == 4000)
						$set = 5;
					elseif ($node['downstream'] < 8000)
						$set = 6;
					elseif ($node['downstream'] == 8000)
						$set = 7;
					elseif ($node['downstream'] < 10000)
						$set = 8;
					elseif ($node['downstream'] == 10000)
						$set = 9;
					elseif ($node['downstream'] < 20000)
						$set = 10;
					elseif ($node['downstream'] == 20000)
						$set = 11;
					elseif ($node['downstream'] < 30000)
						$set = 12;
					elseif ($node['downstream'] == 30000)
						$set = 13;
					else
						$set = 14;
					if ($node['type'] == 0)
						$personalnodes[$node['servicetypes']][$set]++;
					else
						$commercialnodes[$node['servicetypes']][$set]++;
				}
				// save info about computers connected to this network node
			foreach ($personalnodes as $servicetype => $servicenodes) {
				$services = array();
				foreach (array_fill(0, 15, '0') as $key => $value)
					$services[] = isset($servicenodes[$key]) ? $servicenodes[$key] : $value;
				$personalnodes[$servicetype] = $services;
			}
			foreach ($commercialnodes as $servicetype => $servicenodes) {
				$services = array();
				foreach (array_fill(0, 15, '0') as $key => $value)
					$services[] = isset($servicenodes[$key]) ? $servicenodes[$key] : $value;
				$commercialnodes[$servicetype] = $services;
			}
			foreach (array_unique(array_merge(array_keys($personalnodes), array_keys($commercialnodes))) as $servicetype) {
				$services = array_flip(explode(',', $servicetype));
				$ukeservices = array();
				foreach (array('TEL', 'INT', 'TV') as $service)
					if (isset($services[$service]))
						$ukeservices[] = $service;
				$snetranges .= $netrangeid.",".$netnode['id'].","
					.implode(',', array($teryt['area_woj'], $teryt['area_pow'], $teryt['area_gmi']." ("
						.$borough_types[intval($area_rodz)].")",
						$teryt['area_terc'], $teryt['area_city'], $teryt['area_simc'],
						$teryt['address_cecha'], $teryt['address_ulica'], $teryt['address_symul'],
						$teryt['address_budynek'], ZIP_CODE))
					.",0,".$linktypes[$range['linktype']]['technologia_dostepu'].",".implode('_', $ukeservices).",WLASNA,"
					.$linktypes[$range['linktype']]['szybkosc'].","
					.(implode(',', isset($personalnodes[$servicetype]) ? $personalnodes[$servicetype] : array_fill(0, 15, '0'))).","
					.(implode(',', isset($commercialnodes[$servicetype]) ? $commercialnodes[$servicetype] : array_fill(0, 15, '0')))."\n";
				$netrangeid++;
			}
		}
}

//prepare info about network links (only between different network nodes)
$processed_netlinks = array();
$netlinks = array();
if ($netdevices)
	foreach ($netdevices as $netdevice) {
		$ndnetlinks = $DB->GetAll("SELECT src, dst, type, speed FROM netlinks WHERE src = ? OR dst = ?",
			array($netdevice['id'], $netdevice['id']));
		if ($ndnetlinks)
			foreach ($ndnetlinks as $netlink) {
				$netdevnetnode = $netdevs[$netdevice['id']];
				$srcnetnode = $netdevs[$netlink['src']];
				$dstnetnode = $netdevs[$netlink['dst']];
				$netnodeids = array($netnodes[$srcnetnode]['id'], $netnodes[$dstnetnode]['id']);
				sort($netnodeids);
				$netnodelinkid = implode('_', $netnodeids);
				if (!isset($processed_netlinks[$netnodelinkid])) {
					if ($netlink['src'] == $netdevice['id']) {
						if ($netdevnetnode != $dstnetnode) {
							$netlinks[] = array('type' => $netlink['type'], 'speed' => $netlink['speed'],
								'src' => $netdevnetnode, 'dst' => $dstnetnode);
							$processed_netlinks[$netnodelinkid] = true;
							$netnodes[$netdevnetnode]['distports']++;
						}
					} else
						if ($netdevnetnode != $srcnetnode) {
							$netlinks[] = array('type' => $netlink['type'], 'speed' => $netlink['speed'],
								'src' => $netdevnetnode, 'dst' => $srcnetnode);
							$processed_netlinks[$netnodelinkid] = true;
							$netnodes[$netdevnetnode]['distports']++;
						}
				}
			}
	}

// save info about network lines
$netlineid = 1;
$snetlines = '';
if ($netlinks)
	foreach ($netlinks as $netlink)
		if ($netnodes[$netlink['src']]['id'] != $netnodes[$netlink['dst']]['id']) {
			$snetlines .= $netlineid.",węzeł sieci,".$netnodes[$netlink['src']]['id'].",węzeł sieci,".$netnodes[$netlink['dst']]['id'].",Nie,Tak,Nie,"
				.implode(',', array_slice($linktypes[$netlink['type']], 0, 6)).","
				.implode(',', array_fill(0, 3, $linktypes[$netlink['type']]['liczba_jednostek'])).","
				.$linktypes[$netlink['type']]['jednostka'].",0,0\n";
			$netlineid++;
		}

// save info about network links
$netlinkid = 1;
$snetlinks = '';
if ($netlinks)
	foreach ($netlinks as $netlink)
		if ($netnodes[$netlink['src']]['id'] != $netnodes[$netlink['dst']]['id']) {
			$snetlinks .= $netlinkid.",".$netnodes[$netlink['src']]['id'].",".$netnodes[$netlink['dst']]['id'].",Nie,Tak,Nie,"
				."0,0,"
				.implode(',', array_fill(0, 2, floor($netlink['speed'] / 1000))).","
				."0,0,0,0\n";
			$netlinkid++;
		}

// prepare zip archive package containing all generated files
if (!extension_loaded ('zip'))
	die ('<B>Zip extension not loaded! In order to use this extension you must compile PHP with zip support by using the --enable-zip configure option. </B>');
	
$zip = new ZipArchive();
$filename = tempnam('/tmp', 'LMS_SIIS_').'.zip';
if ($zip->open($filename, ZIPARCHIVE::OVERWRITE)) {
	$zip->addFromString('WEZLY.csv', $snetnodes);
	$zip->addFromString('W_INTERFACE.csv', $snetinterfaces);
	$zip->addFromString('W_ZASIEG.csv', $snetranges);
	$zip->addFromString('LINIE.csv', $snetlines);
	$zip->addFromString('POLACZENIA.csv', $snetlinks);
	$zip->close();

	// send zip archive package to web browser
	header('Content-type: application/zip');
	header('Content-Disposition: attachment; filename="LMS_SIIS.zip"');
	header('Pragma: public');
	readfile($filename);

	// remove already unneeded zip archive package file
	unlink($filename);
}

?>
