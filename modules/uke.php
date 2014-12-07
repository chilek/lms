<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2014 LMS Developers
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
(C) 2001-2014 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options))
{
	print <<<EOF
lms-uke.php
(C) 2001-2014 LMS Developers

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
(C) 2001-2014 LMS Developers

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

// Load autloader
require_once(LIB_DIR.'/autoloader.php');

require_once(LIB_DIR.'/config.php');

// Init database

$DB = null;

try {
	$DB = LMSDB::getInstance();
} catch (Exception $ex) {
	trigger_error($ex->getMessage(), E_USER_WARNING);

	// can't working without database
	die("Fatal error: cannot connect to database!\n");
}

// Include required files (including sequence is important)

require_once(LIB_DIR.'/language.php');
include_once(LIB_DIR.'/definitions.php');
require_once(LIB_DIR.'/unstrip.php');
require_once(LIB_DIR.'/common.php');

// Initialize Session, Auth and LMS classes

$AUTH = NULL;
$SYSLOG = NULL;
$LMS = new LMS($DB, $AUTH, $CONFIG, $SYSLOG);
$LMS->ui_lang = $_ui_language;
$LMS->lang = $_language;
*/

/*
function to_wgs84($coord, $ifLongitude = true) {
	if ($ifLongitude) {
		if ($coord >= 0)
			$res = sprintf("%.04fE", $coord);
		else
			$res = sprintf("%.04fW", $coord * -1);
	} else {
		if ($coord >= 0)
			$res = sprintf("%.04fN", $coord);
		else
			$res = sprintf("%.04fS", $coord * -1);
	}
	return str_replace(',', '.', $res);
}
*/

function to_wgs84($coord, $ifLongitude = true) {
	return str_replace(',', '.', sprintf("%.04f", $coord));
}

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
	array('linia' => "kablowa", 'trakt' => "podziemny", 'technologia' => "kablowe parowe miedziane", 'typ' => "UTP",
		'pasmo' => "", 'szybkosc_radia' => "",
		'technologia_dostepu' => "100 Mb/s Fast Ethernet", 'szybkosc' => "100", 'liczba_jednostek' => "1",
		'jednostka' => "linie w kablu",
		'specyficzne' => array('szybkosc_dystrybucyjna' => "100")),
	array('linia' => "bezprzewodowa", 'trakt' => "NIE DOTYCZY", 'technologia' => "radiowe", 'typ' => "WiFi",
		'pasmo' => "5.5", 'szybkosc_radia' => "100",
		'technologia_dostepu' => "WiFi - 2,4 GHz", 'szybkosc' => "54", 'liczba_jednostek' => "1",
		'jednostka' => "kanały",
		'specyficzne' => array('szybkosc_dystrybucyjna' => "100")),
	array('linia' => "kablowa", 'trakt' => "podziemny w kanalizacji", 'technologia' => "światłowodowe", 'typ' => "G.652", 
		'pasmo' => "", 'szybkosc_radia' => "",
		'technologia_dostepu' => "100 Mb/s Fast Ethernet", 'szybkosc' => "100", 'liczba_jednostek' => "2",
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
		(CASE WHEN (nd.location_flat IS NULL OR nd.location_flat = '') THEN nd.location_house ELSE "
			.$DB->Concat('nd.location_house ', "'/'", 'nd.location_flat')." END) AS address_budynek, 
		(SELECT zip FROM pna WHERE pna.cityid = nd.location_city
			AND (pna.streetid IS NULL OR (pna.streetid IS NOT NULL AND pna.streetid = nd.location_street)) LIMIT 1) AS location_zip, 
		ports, 
		longitude, latitude 
		FROM netdevices nd 
		WHERE EXISTS (SELECT id FROM netlinks nl WHERE nl.src = nd.id OR nl.dst = nd.id) 
		ORDER BY name");

// prepare info about network nodes
$netnodes = array();
$netdevs = array();
$netnodeid = 1;
if ($netdevices)
	foreach ($netdevices as $netdevid => $netdevice) {
		$distports = $DB->GetAll("SELECT type, technology, speed, COUNT(netlinks.id) AS portcount FROM netlinks
			WHERE src = ? OR dst = ?
			GROUP BY type, technology, speed", array($netdevice['id'], $netdevice['id']));
		$accessports = $DB->GetAll("SELECT linktype AS type, linktechnology AS technology,
				linkspeed AS speed, c.type AS customertype, COUNT(port) AS portcount
			FROM nodes n
			JOIN customers c ON c.id = n.ownerid
			WHERE netdev = ? 
				AND EXISTS
					(SELECT na.id FROM nodeassignments na
						JOIN assignments a ON a.id = na.assignmentid
						WHERE na.nodeid = n.id AND a.suspended = 0
							AND a.period IN (" . implode(',', array(YEARLY, HALFYEARLY, QUARTERLY, MONTHLY, DISPOSABLE)) . ")
							AND (a.datefrom = 0 OR a.datefrom < ?NOW?) AND (a.dateto = 0 OR a.dateto > ?NOW?))
				AND NOT EXISTS
					(SELECT id FROM assignments aa
						WHERE aa.customerid = c.id AND aa.tariffid = 0 AND aa.liabilityid = 0
							AND (aa.datefrom < ?NOW? OR aa.datefrom = 0)
							AND (aa.dateto > ?NOW? OR aa.dateto = 0))
			GROUP BY linktype, linktechnology, linkspeed, c.type
			ORDER BY c.type", array($netdevice['id']));

		$netdevice['netnodename'] = $netdevices[$netdevid]['netnodename'] =
			empty($netdevice['location_city']) ?
				$netdevice['location'] :
				implode('_', array($netdevice['location_city'], $netdevice['location_street'],
					$netdevice['location_house'], $netdevice['location_flat']));
		$netnodename = $netdevice['netnodename'];
		if (!array_key_exists($netnodename, $netnodes)) {
			$netnodes[$netnodename]['ports'] = 0;
			$netnodes[$netnodename]['distports'] = array();
			$netnodes[$netnodename]['totaldistports'] = 0;
			$netnodes[$netnodename]['accessports'] = array();
			$netnodes[$netnodename]['personalaccessports'] = 0;
			$netnodes[$netnodename]['commercialaccessports'] = 0;

			$netnodes[$netnodename]['id'] = $netnodeid;
			$netnodes[$netnodename]['location'] = $netdevice['location'];
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
				$netnodes[$netnodename]['location_zip'] = $netdevice['location_zip'];
			}
			$netnodes[$netnodename]['ranges'] = array();
			$netnodes[$netnodename]['netdevices'] = array();
			$netnodes[$netnodename]['longitudes'] = array();
			$netnodes[$netnodename]['latitudes'] = array();
			$netnodeid++;
		}
		if (!empty($distports))
			foreach ($distports as $port) {
				$linktype = $port['type'];
				$linktechnology = $port['technology'];
				$linkspeed = $port['speed'];
				if (!$linktechnology)
					switch ($linktype) {
						case 0:
							$linktechnology = 8;
							break;
						case 1:
							$linktechnology = 101;
							break;
						case 2:
							$linktechnology = 205;
							break;
					}
				if (!isset($netnodes[$netnodename]['distports'][$linktype][$linktechnology][$linkspeed]))
					$netnodes[$netnodename]['distports'][$linktype][$linktechnology][$linkspeed] = 0;
				$netnodes[$netnodename]['distports'][$linktype][$linktechnology][$linkspeed] += $port['portcount'];
				$netnodes[$netnodename]['totaldistports'] += $port['portcount'];
				$netnodes[$netnodename]['ports'] += $port['portcount'];
			}
		if (!empty($accessports))
			foreach ($accessports as $ports) {
				$linktype = $ports['type'];
				$linktechnology = $ports['technology'];
				$linkspeed = $ports['speed'];
				$customertype = $ports['customertype'];
				if (!$linktechnology)
					switch ($linktype) {
						case 0:
							$linktechnology = 8;
							break;
						case 1:
							$linktechnology = 101;
							break;
						case 2:
							$linktechnology = 205;
							break;
					}
				if (!isset($netnodes[$netnodename]['accessports'][$linktype][$linktechnology][$linkspeed][$customertype]))
					$netnodes[$netnodename]['accessports'][$linktype][$linktechnology][$linkspeed][$customertype] = 0;
				$netnodes[$netnodename]['accessports'][$linktype][$linktechnology][$linkspeed][$customertype] += $ports['portcount'];
				$netnodes[$netnodename][($customertype ? 'commercialaccessports' : 'personalaccessports')] += $ports['portcount'];
				$netnodes[$netnodename]['ports'] += $ports['portcount'];
			}

		$netnodes[$netnodename]['netdevices'][] = $netdevice['id'];
		if (isset($netdevice['longitude'])) {
			$netnodes[$netnodename]['longitudes'][] = $netdevice['longitude'];
			$netnodes[$netnodename]['latitudes'][] = $netdevice['latitude'];
		}
		$netdevs[$netdevice['id']] = $netnodename;
	}

$netintid = 1;
$netbuildingid = 1;
$netrangeid = 1;
$snetnodes = '';
$snetinterfaces = '';
$snetranges = '';
$snetbuildings = '';
if ($netnodes)
foreach ($netnodes as $netnodename => $netnode) {
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
		$netnode['longitude'] = to_wgs84($netnode['longitude'] / count($netnode['longitudes']));
		$netnode['latitude'] = to_wgs84($netnode['latitude'] / count($netnode['latitudes']));
	}
	// save info about network nodes
	if (empty($netnode['address_ulica'])) {
		$netnode['address_ulica'] = "BRAK ULICY";
		$netnode['address_symul'] = "99999";
	}
	if (empty($netnode['address_symul'])) {
		$netnode['address_ulica'] = "ul. SPOZA ZAKRESU";
		$netnode['address_symul'] = "99998";
	}
	$snetnodes .= $netnode['id'] . "," . $netnode['id'] . ",Węzeł własny,,,"
		.(isset($netnode['area_woj'])
			? implode(',', array($netnode['area_woj'], $netnode['area_pow'], $netnode['area_gmi'],
				$netnode['area_terc'], $netnode['area_city'], $netnode['area_simc'],
				(!empty($netnode['address_cecha']) && $netnode['address_cecha'] != 'inne'
					? $netnode['address_cecha'] . ' ' : '') . $netnode['address_ulica'],
				$netnode['address_symul'], $netnode['address_budynek'], $netnode['location_zip']))
			: "LMS netdevinfo ID's: " . implode(' ', $netnode['netdevices']) . "," . implode(',', array_fill(0, 9, '')))
		. "," . (isset($netnode['longitude']) ? $netnode['latitude'] . "," . $netnode['longitude'] : ",")
		.",skrzynka,Nie,Nie,Brak danych\n";

	// save info about network interfaces located in distribution layer
	if ($netnode['totaldistports'] > 0
		|| $netnode['personalaccessports'] + $netnode['commercialaccessports'] == 0)
		foreach ($netnode['distports'] as $linktype => $linktechnologies)
			foreach ($linktechnologies as $linktechnology => $linkspeeds) {
				if ($linktechnology < 50 || $linktechnology >= 100)
					$technology = $linktypes[$linktype]['technologia'];
				else
					$technology = 'kablowe współosiowe miedziane';
				$ltech = $LINKTECHNOLOGIES[$linktype][$linktechnology];
				switch ($linktechnology) {
					case 100:
						$bandwidth = "2.4";
						break;
					case 101:
						$bandwidth = "5.5";
						break;
					default:
						$bandwidth = "";
						break;
				}
				foreach ($linkspeeds as $linkspeed => $totaldistports) {
					$snetinterfaces .= $netintid . "," . $netintid . "," . $netnode['id']
						. ",Nie,Tak,Nie," . $technology . "," . $bandwidth
						. "," . $ltech . "," . implode(',', array_fill(0, 2, round($linkspeed / 1000))) . ","
						. $totaldistports . ","
						. $totaldistports . ",0,Nie\n";
					$netintid++;
				}
			}

	// save info about network interfaces located in access layer
	if (!empty($netnode['accessports'])) {
		$idx = 0;
		foreach ($netnode['accessports'] as $linktype => $linktechnologies)
			foreach ($linktechnologies as $linktechnology => $linkspeeds) {
				if ($linktechnology < 50 || $linktechnology >= 100)
					$technology = $linktypes[$linktype]['technologia'];
				else
					$technology = 'kablowe współosiowe miedziane';
				$ltech = $LINKTECHNOLOGIES[$linktype][$linktechnology];
				switch ($linktechnology) {
					case 100:
						$bandwidth = "2.4";
						break;
					case 101:
						$bandwidth = "5.5";
						break;
					default:
						$bandwidth = "";
						break;
				}

				foreach ($linkspeeds as $linkspeed => $customertypes) {
					$ports = 0;
					foreach ($customertypes as $customertypeports)
						$ports += $customertypeports;
					if (!$idx)
						$snetinterfaces .= $netintid . "," . $netintid . "," . $netnode['id']
							. ",Nie,Nie,Tak," . $technology . "," . $bandwidth
							. "," . $ltech . "," . implode(',', array_fill(0, 2, round($linkspeed / 1000))) . ","
							. ($netnode['ports'] - $netnode['totaldistports']
								- $netnode['personalaccessports'] - $netnode['commercialaccessports']
								+ $ports ) . ","
							. $ports . ","
							. ($netnode['ports'] - $netnode['totaldistports']
								- $netnode['personalaccessports'] - $netnode['commercialaccessports']) . ",Nie\n";
					else
						$snetinterfaces .= $netintid . "," . $netintid . "," . $netnode['id']
							. ",Nie,Nie,Tak," . $technology . "," . $bandwidth
							. "," . $ltech . "," . implode(',', array_fill(0, 2, $linkspeed)) . ","
							. implode(',', array_fill(0, 2, $ports)) . ","
							. "0,Nie\n";
					$netintid++;
				}
			}
	}

	// save info about network ranges
	$ranges = $DB->GetAll("SELECT n.linktype, n.linktechnology, n.location_street, n.location_city, n.location_house 
		FROM nodes n 
		WHERE n.ownerid > 0 AND n.location_city <> 0 AND n.netdev IN (".implode(',', $netnode['netdevices']).") 
		GROUP BY n.linktype, n.linktechnology, n.location_street, n.location_city, n.location_house",
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
				(SELECT tu.sym_ul FROM teryt_ulic tu WHERE tu.id = ?) AS address_symul,
				(SELECT zip FROM pna WHERE pna.cityid = ?
					AND (pna.streetid IS NULL OR (pna.streetid IS NOT NULL AND pna.streetid = ?)) LIMIT 1) AS location_zip",
				array($range['location_city'],  $range['location_city'], $range['location_city'],
					$range['location_city'], $range['location_city'], $range['location_city'],
					$range['location_street'], $range['location_street'], $range['location_street'],
					$range['location_city'], $range['location_street']));

			list ($area_woj, $area_pow, $area_gmi, $area_rodz) = explode('_', $teryt['area_terc']);
			$teryt['area_terc'] = sprintf("%02d%02d%02d%s", $area_woj, $area_pow, $area_gmi, $area_rodz);
			$teryt['area_simc'] = sprintf("%07d", $teryt['area_simc']);
			$teryt['address_budynek'] = $range['location_house'];
			if (empty($teryt['address_ulica'])) {
				if ($DB->GetOne("SELECT COUNT(*) FROM location_streets WHERE cityid = ?", array($range['location_city']))) {
					$teryt['address_ulica'] = "ul. SPOZA ZAKRESU";
					$teryt['address_symul'] = "99998";
				} else {
					$teryt['address_ulica'] = "BRAK ULICY";
					$teryt['address_symul'] = "99999";
				}
			}
			if (empty($teryt['address_symul'])) {
				$teryt['address_ulica'] = "ul. SPOZA ZAKRESU";
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
				WHERE n.ownerid > 0 AND n.netdev > 0 AND n.linktype = ? AND n.linktechnology = ? AND n.location_city = ? 
					AND (n.location_street = ? OR n.location_street IS NULL) AND n.location_house = ? 
					AND a.suspended = 0 AND a.period IN (".implode(',', array(YEARLY, HALFYEARLY, QUARTERLY, MONTHLY, DISPOSABLE)).") 
					AND (a.datefrom = 0 OR a.datefrom < ?NOW?) AND (a.dateto = 0 OR a.dateto > ?NOW?) 
					AND allsuspended.total IS NULL 
				GROUP BY na.nodeid, c.type",
				array($range['linktype'], $range['linktechnology'], $range['location_city'], $range['location_street'], $range['location_house']));

			// count all kinds of link speeds for computers connected to this network node
			$personalnodes = array();
			$commercialnodes = array();
			$maxdownstream = 0;
			if ($nodes)
				foreach ($nodes as $node) {
					if ($node['downstream'] <= 1000)
						$set = 1;
					elseif ($node['downstream'] < 2000)
						$set = 2;
					elseif ($node['downstream'] == 2000)
						$set = 3;
					elseif ($node['downstream'] <= 10000)
						$set = 4;
					elseif ($node['downstream'] <= 20000)
						$set = 5;
					elseif ($node['downstream'] < 30000)
						$set = 6;
					elseif ($node['downstream'] == 30000)
						$set = 7;
					elseif ($node['downstream'] < 100000)
						$set = 8;
					elseif ($node['downstream'] == 100000)
						$set = 9;
					else
						$set = 10;
					if ($node['type'] == 0)
						$personalnodes[$node['servicetypes']][$set]++;
					else
						$commercialnodes[$node['servicetypes']][$set]++;
					if ($node['downstream'] > $maxdownstream)
						$maxdownstream = $node['downstream'];
				}
				// save info about computers connected to this network node
			foreach ($personalnodes as $servicetype => $servicenodes) {
				$services = array();
				foreach (array_fill(0, 11, '0') as $key => $value)
					$services[] = isset($servicenodes[$key]) ? $servicenodes[$key] : $value;
				$personalnodes[$servicetype] = $services;
			}
			foreach ($commercialnodes as $servicetype => $servicenodes) {
				$services = array();
				foreach (array_fill(0, 11, '0') as $key => $value)
					$services[] = isset($servicenodes[$key]) ? $servicenodes[$key] : $value;
				$commercialnodes[$servicetype] = $services;
			}

			if ($range['linktechnology']) {
				if ($range['linktechnology'] < 50 || $range['linktechnology'] >= 100)
					$technology = $linktypes[$range['linktype']]['technologia'];
				else
					$technology = 'kablowe współosiowe miedziane';
				$linktechnology = $LINKTECHNOLOGIES[$range['linktype']][$range['linktechnology']];
			} else {
				$technology = $linktypes[$range['linktype']]['technologia'];
				$linktechnology = $linktypes[$range['linktype']]['technologia_dostepu'];
			}

			$snetbuildings .= $netbuildingid . "," . $netbuildingid . ",Własna,,," . $netnode['id'] . ","
				. implode(',', array($teryt['area_woj'], $teryt['area_pow'], $teryt['area_gmi'],
					$teryt['area_terc'], $teryt['area_city'], $teryt['area_simc'],
					(!empty($teryt['address_cecha']) && $teryt['address_cecha'] != 'inne'
						? $teryt['address_cecha'] . ' ' : '') . $teryt['address_ulica'], $teryt['address_symul'],
					$teryt['address_budynek'], $teryt['location_zip'])) . "," . $netnode['latitude'] . "," . $netnode['longitude'] . ","
					. $technology . ',"' . $linktechnology . '"';
			$allservices = array();

			foreach (array_unique(array_merge(array_keys($personalnodes), array_keys($commercialnodes))) as $servicetype) {
				$services = array_flip(explode(',', $servicetype));
				$ukeservices = array();
				foreach (array('TEL', 'INT', 'TV') as $service)
					if (isset($services[$service])) {
						$ukeservices[] = $service;
						$allservices[] = $service;
					}
				$snetranges .= $netrangeid . "," . $netrangeid . "," . $netbuildingid;
				$snetranges .= ",Nie," . (array_search('TEL', $ukeservices) !== FALSE ? "Tak" : "Nie")
					. ",Nie," . (array_search('INT', $ukeservices) !== FALSE ? "Tak" : "Nie")
					. ",Nie," . (array_search('TV', $ukeservices) !== FALSE ? "Tak" : "Nie") . ",,";
				$snetranges .= (implode(',', isset($personalnodes[$servicetype]) ? $personalnodes[$servicetype] : array_fill(0, 11, '0'))) . ","
					. (implode(',', isset($commercialnodes[$servicetype]) ? $commercialnodes[$servicetype] : array_fill(0, 11, '0'))) . "\n";
				$netrangeid++;
			}

			$allservices = array_unique($allservices);
			$snetbuildings .= ",Nie," . (array_search('TEL', $allservices) !== FALSE ? "Tak" : "Nie")
					. ",Nie," . (array_search('INT', $allservices) !== FALSE ? "Tak" : "Nie")
					. ",Nie," . (array_search('TV', $allservices) !== FALSE ? "Tak" : "Nie") . ",Nie,"
					. round($maxdownstream / 1000) . ",0\n";
			$netbuildingid++;
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
$netcablelineid = 1;
$netradiolineid = 1;
$snetcablelines = '';
$snetradiolines = '';
if ($netlinks)
	foreach ($netlinks as $netlink)
		if ($netnodes[$netlink['src']]['id'] != $netnodes[$netlink['dst']]['id'])
			if ($netlink['type'] == 1) {
				$snetradiolines .= $netradiolineid . "," . $netradiolineid
					. "," . $netnodes[$netlink['src']]['id'] . "," . $netnodes[$netlink['dst']]['id']
					. ",radiowe na częstotliwości ogólnodostępnej,," . $linktypes[$netlink['type']]['pasmo']
					. "," . $linktypes[$netlink['type']]['typ'] . ","
					. $linktypes[$netlink['type']]['szybkosc_radia'] . ",Nie\n";
				$netradiolineid++;
			} else {
				$snetcablelines .= $netcablelineid . "," . $netcablelineid . ",własna,"
					. ",węzeł własny," . $netnodes[$netlink['src']]['id'] . ",węzeł własny," . $netnodes[$netlink['dst']]['id']
					. "," . $linktypes[$netlink['type']]['technologia'] . ","
					. ($netlink['type'] == 2 ? $linktypes[$netlink['type']]['typ'] . ","
						. implode(',', array_fill(0, 2, $linktypes[$netlink['type']]['liczba_jednostek']))
						: ",,")
					. ",Nie,Brak danych,,"
					. ($netlink['type'] == 2 ? "Nie" : "") . ",,Nie,"
					. $linktypes[$netlink['type']]['trakt'] . ","
					. ($netlink['type'] == 2 ? "0.1" : "") . "\n";
				$netcablelineid++;
			}

// save info about network links
$netlinkid = 1;
$snetlinks = '';
if ($netlinks)
	foreach ($netlinks as $netlink)
		if ($netnodes[$netlink['src']]['id'] != $netnodes[$netlink['dst']]['id']) {
			$snetlinks .= $netlinkid . "," . $netlinkid . ",Własna,,"
				. $netnodes[$netlink['src']]['id'] . "," . $netnodes[$netlink['dst']]['id'] . ",Nie,Tak,Nie,"
				. "Tak,Nie,Nie,"
				. implode(',', array_fill(0, 2, floor($netlink['speed'] / 1000))) . "\n";
			$netlinkid++;
		}

// prepare zip archive package containing all generated files
if (!extension_loaded ('zip'))
	die ('<B>Zip extension not loaded! In order to use this extension you must compile PHP with zip support by using the --enable-zip configure option. </B>');
	
$zip = new ZipArchive();
$filename = tempnam('/tmp', 'LMS_SIIS_').'.zip';
if ($zip->open($filename, ZIPARCHIVE::OVERWRITE)) {
	$zip->addFromString('WW.csv', $snetnodes);
	$zip->addFromString('INT.csv', $snetinterfaces);
	$zip->addFromString('ZAS.csv', $snetbuildings);
	$zip->addFromString('US.csv', $snetranges);
	$zip->addFromString('LP.csv', $snetcablelines);
	$zip->addFromString('RL.csv', $snetradiolines);
	$zip->addFromString('POL.csv', $snetlinks);
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
