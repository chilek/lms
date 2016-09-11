<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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

//require_once(dirname(__FILE__) . '/../contrib/initLMS.php');

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

define(EOL, "\r\n");
define(ZIP_CODE, '15-950');

if (isset($_POST['sheets']) && is_array($_POST['sheets']))
	$sheets = array_keys($_POST['sheets']);
else
	$sheets = array();

$format = intval($_POST['format']);
if ($format == 2) {
	$buffer = '#SIIS wersja 5.28' . EOL;
	$header = ConfigHelper::getConfig('siis.header', '');
	if (strlen($header))
		$buffer .= str_replace("\n", EOL, $header) . EOL;
}

// prepare old csv key arrays
$ob_keys = array('ob_id', 'ob_invproject', 'ob_name', 'ob_nip', 'ob_regon', 'ob_rpt', 'ob_state',
	'ob_district', 'ob_borough', 'ob_terc', 'ob_city', 'ob_simc', 'ob_street', 'ob_ulic', 'ob_house',
	'ob_zip');
$proj_keys = array('proj_id', 'proj_name', 'proj_agreementnr', 'proj_title', 'proj_program', 'proj_case',
	'proj_companyname', 'proj_startdate', 'proj_enddate', 'proj_state', 'proj_range');
$ww_keys = array('ww_id', 'ww_invproject', 'ww_invstatus', 'ww_id', 'ww_ownership', 'ww_coowner', 'ww_coloc',
	'ww_state', 'ww_district', 'ww_borough', 'ww_terc', 'ww_city', 'ww_simc',
	'ww_street', 'ww_ulic', 'ww_house', 'ww_zip', 'ww_latitude', 'ww_longitude', 'ww_objtype',
	'ww_uip', 'ww_miar', 'ww_eu');
$wo_keys = array('wo_id', 'wo_invproject', 'wo_id', 'wo_agreement', 'wo_coowner',
	'wo_state', 'wo_district', 'wo_borough', 'wo_terc', 'wo_city', 'wo_simc',
	'wo_street', 'wo_ulic', 'wo_house', 'wo_zip', 'wo_latitude', 'wo_longitude', 'wo_objtype');
$int_keys = array('int_id', 'int_invproject', 'int_invstatus', 'int_id', 'int_wwid', 'int_blayer',
	'int_dlayer', 'int_alayer', 'int_tech', 'int_bandwidth', 'int_ltech', 'int_maxdown', 'int_maxup',
	'int_totalports', 'int_usedports', 'int_freeports', 'int_portleasing');
$sr_keys = array('sr_id', 'sr_invproject', 'sr_invstatus', 'sr_id', 'sr_wwid', 'sr_intid',
	'sr_license', 'sr_licensenr', 'sr_azimuth', 'sr_width', 'sr_altitude', 'sr_range', 'sr_maxspeed');
$ps_keys = array('ps_id', 'ps_invproject', 'ps_invstatus', 'ps_id', 'ps_wwid', 'ps_woid', 'ps_intid',
	'ps_internetusage', 'ps_voiceusage', 'ps_otherusage', 'ps_totalspeed', 'ps_internetspeed');
$pol_keys = array('pol_id', 'pol_invproject', 'pol_invstatus', 'pol_id', 'pol_owner', 'pol_foreignerid',
	'pol_wa', 'pol_wb', 'pol_blayer', 'pol_dlayer', 'pol_alayer', 'pol_internetusage', 'pol_voiceusage',
	'pol_otherusage', 'pol_totalspeed', 'pol_internetspeed');
$lp_keys = array('lp_id', 'lp_invproject', 'lp_invstatus', 'lp_id', 'lp_owner', 'lp_foreignerid', 'lp_anodetype',
	'lp_anodeid', 'lp_bnodetype', 'lp_bnodeid', 'lp_tech', 'lp_fibertype', 'lp_fibertotal',
	'lp_fiberused', 'lp_eu', 'lp_passiveavail', 'lp_passivetype', 'lp_fiberlease', 'lp_fiberleasecount',
	'lp_bandwidthlease', 'lp_duct', 'lp_length');
$rl_keys = array('rl_id', 'rl_invproject', 'rl_invstatus', 'rl_id', 'rl_anodeid', 'rl_bnodeid', 'rl_mediumtype',
	'rl_licencenr', 'rl_bandwidth', 'rl_transmission', 'rl_throughput', 'rl_leaseavail');
$zas_keys = array('zas_id', 'zas_invproject', 'zas_invstatus', 'zas_id', 'zas_ownership', 'zas_leasetype',
	'zas_foreignerid', 'zas_nodeid', 'zas_state', 'zas_district', 'zas_borough', 'zas_terc', 'zas_city',
	'zas_simc', 'zas_street', 'zas_ulic', 'zas_house', 'zas_zip', 'zas_latitude', 'zas_longitude',
	'zas_tech', 'zas_ltech', 'zas_phonepots', 'zas_phonevoip', 'zas_phonemobile', 'zas_internetstationary',
	'zas_internetmobile', 'zas_tv', 'zas_other', 'zas_stationarymaxspeed', 'zas_mobilemaxspeed');
$us_keys = array('us_id', 'us_invproject', 'us_invstatus', 'us_id', 'us_netbuildingid', 'us_phonepots',
	'us_phonevoip', 'us_phonemobile', 'us_internetstationary', 'us_internetmobile', 'us_tv', 'us_other');
for ($i = 0; $i < 11; $i++)
	$us_keys[] = 'us_personal' . $i;
for ($i = 0; $i < 11; $i++)
	$us_keys[] = 'us_commercial' . $i;

function to_csv($data) {
	foreach ($data as $key => $val)
		$data[$key] = '"' . str_replace('"', '""', $val) . '"';
	return implode(',', array_values($data));
}

function to_old_csv($keys, $array) {
	$result = array();
	foreach ($keys as $key)
		$result[] = strpos($array[$key], ',') === FALSE ? $array[$key] : '"' . $array[$key] . '"';
	return implode(',', $result);
}

function to_wgs84($coord, $ifLongitude = true) {
	return str_replace(',', '.', sprintf("%.04f", $coord));
}

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
		'technologia_dostepu' => "100 Mb/s Fast Ethernet", 'liczba_jednostek' => "1",
		'jednostka' => "linie w kablu"),
	array('linia' => "bezprzewodowa", 'trakt' => "NIE DOTYCZY", 'technologia' => "radiowe", 'typ' => "WiFi",
		'technologia_dostepu' => "WiFi - 2,4 GHz", 'liczba_jednostek' => "1",
		'jednostka' => "kanały"),
	array('linia' => "kablowa", 'trakt' => "podziemny w kanalizacji", 'technologia' => "światłowodowe", 'typ' => "G.652", 
		'technologia_dostepu' => "100 Mb/s Fast Ethernet", 'liczba_jednostek' => "2",
		'jednostka' => "włókna")
);

$projects = $DB->GetAllByKey("SELECT id, name FROM invprojects WHERE type <> ?", "id", array(INV_PROJECT_SYSTEM));
if (!empty($invprojects))
	foreach ($projects as $idx => $project)
		if (!in_array($idx, $invprojects))
			unset($projects[$idx]);
$projectid = 1;
$sprojects = '';
if (!empty($projects))
	foreach ($projects as $project) {
		if ($format == 2) { 
			$res = preg_grep('/^PR,.+,"' . str_replace('/', '\/', $project['name']) . '",/', preg_split('/\r?\n/', $header));
			if (!empty($res))
				continue;
		}
		$data = array(
			'proj_id' => $projectid,
			'proj_name' => $project['name'],
			'proj_agreementnr' => '',
			'proj_title' => '',
			'proj_program' => '',
			'proj_case' => '',
			'proj_companyname' => '',
			'proj_startdate' => '',
			'proj_enddate' => '',
			'proj_state' => '',
			'proj_range' => '',
		);
		if (in_array('proj', $sheets))
			if ($format == 2)
				$buffer .= 'PR,' . to_csv($data) . EOL;
			else
				$sprojects .= to_old_csv($proj_keys, $data) . EOL;
		$projectid++;
	}

$allradiosectors = $DB->GetAllByKey("SELECT * FROM netradiosectors ORDER BY id", "id");

$truenetnodes = $DB->GetAllByKey("SELECT nn.id, nn.name, nn.invprojectid, nn.type, nn.status, nn.ownership, nn.coowner,
		nn.uip, nn.miar,
		nn.location_city, nn.location_street, nn.location_house, nn.location_flat, nn.location,
		(SELECT ls.name FROM location_cities lc
			JOIN location_boroughs lb ON lb.id = lc.boroughid
			JOIN location_districts ld ON ld.id = lb.districtid
			JOIN location_states ls ON ls.id = ld.stateid
			WHERE lc.id = nn.location_city) AS area_woj,
		(SELECT ld.name FROM location_cities lc
			JOIN location_boroughs lb ON lb.id = lc.boroughid
			JOIN location_districts ld ON ld.id = lb.districtid
			JOIN location_states ls ON ls.id = ld.stateid
			WHERE lc.id = nn.location_city) AS area_pow,
		(SELECT lb.name FROM location_boroughs lb JOIN location_cities lc ON lc.boroughid = lb.id WHERE lc.id = nn.location_city) AS area_gmi, 
		(SELECT ".$DB->Concat('ls.ident', "'_'", 'ld.ident', "'_'", 'lb.ident', "'_'", 'lb.type')." 
			FROM location_cities lc 
			JOIN location_boroughs lb ON lb.id = lc.boroughid 
			JOIN location_districts ld ON ld.id = lb.districtid 
			JOIN location_states ls ON ls.id = ld.stateid 
			WHERE lc.id = nn.location_city) AS area_terc, 
		(SELECT lc.name FROM location_cities lc WHERE lc.id = nn.location_city) AS area_city, 
		(SELECT lc.ident FROM location_cities lc WHERE lc.id = nn.location_city) AS area_simc, 
		(SELECT tu.cecha FROM teryt_ulic tu WHERE tu.id = nn.location_street) AS address_cecha, 
		(SELECT (CASE WHEN ls.name2 IS NOT NULL THEN ".$DB->Concat('ls.name2' , "' '", 'ls.name')." ELSE ls.name END) AS name 
			FROM location_streets ls WHERE ls.id = nn.location_street) AS address_ulica, 
		(SELECT tu.sym_ul FROM teryt_ulic tu WHERE tu.id = nn.location_street) AS address_symul, 
		(CASE WHEN (nn.location_flat IS NULL OR nn.location_flat = '') THEN nn.location_house ELSE "
			.$DB->Concat('nn.location_house ', "'/'", 'nn.location_flat')." END) AS address_budynek, 
		(SELECT zip FROM pna WHERE pna.cityid = nn.location_city
			AND (pna.streetid IS NULL OR (pna.streetid IS NOT NULL AND pna.streetid = nn.location_street)) LIMIT 1) AS location_zip,
		nn.longitude, nn.latitude
	FROM netnodes nn
	ORDER BY nn.id", "id");
if (empty($truenetnodes))
	$truenetnodes = array();
//foreach ($truenetnodes as $idx => $netnode)
//	echo "network node $idx: " . print_r($netnode, true) . '<br>';

// prepare info about network devices from lms database
$netdevices = $DB->GetAllByKey("SELECT nd.id, nd.location_city, nd.location_street, nd.location_house, nd.location_flat, nd.location, 
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
		nd.longitude, nd.latitude, nd.status, nd.netnodeid,
		(CASE WHEN nd.invprojectid = 1 THEN nn.invprojectid ELSE nd.invprojectid END) AS invprojectid
		FROM netdevices nd
		LEFT JOIN netnodes nn ON nn.id = nd.netnodeid
		WHERE EXISTS (SELECT id FROM netlinks nl WHERE nl.src = nd.id OR nl.dst = nd.id) 
		ORDER BY nd.name", "id");

// prepare info about network nodes
$netnodes = array();
$netdevs = array();
$foreigners = array();
$netnodeid = 1;
if ($netdevices)
	foreach ($netdevices as $netdevid => $netdevice) {
		$backboneports = $DB->GetAll("SELECT nl.type, nl.technology, speed,
				(CASE src WHEN ? THEN (CASE WHEN rssrc.frequency IS NULL THEN rsdst.frequency ELSE rssrc.frequency END)
					ELSE (CASE WHEN rsdst.frequency IS NULL THEN rssrc.frequency ELSE rsdst.frequency END) END) AS freq,
				COUNT(nl.id) AS portcount
			FROM netlinks nl
			JOIN netdevices ndsrc ON ndsrc.id = nl.src
			LEFT JOIN netnodes nnsrc ON nnsrc.id = ndsrc.netnodeid
			LEFT JOIN netradiosectors rssrc ON rssrc.id = nl.srcradiosector
			JOIN netdevices nddst ON nddst.id = nl.dst
			LEFT JOIN netnodes nndst ON nndst.id = nddst.netnodeid
			LEFT JOIN netradiosectors rsdst ON rsdst.id = nl.dstradiosector
			WHERE (src = ? OR dst = ?)
				AND ((ndsrc.netnodeid IS NOT NULL AND nnsrc.ownership = 2)
					OR (nddst.netnodeid IS NOT NULL AND nndst.ownership = 2))
			GROUP BY nl.type, nl.technology, speed, freq",
			array($netdevice['id'], $netdevice['id'], $netdevice['id']));
		$distports = $DB->GetAll("SELECT nl.type, nl.technology, speed,
				(CASE src WHEN ? THEN (CASE WHEN rssrc.frequency IS NULL THEN rsdst.frequency ELSE rssrc.frequency END)
					ELSE (CASE WHEN rsdst.frequency IS NULL THEN rssrc.frequency ELSE rsdst.frequency END) END) AS freq,
				COUNT(nl.id) AS portcount FROM netlinks nl
			JOIN netdevices ndsrc ON ndsrc.id = nl.src
			LEFT JOIN netnodes nnsrc ON nnsrc.id = ndsrc.netnodeid
			LEFT JOIN netradiosectors rssrc ON rssrc.id = nl.srcradiosector
			JOIN netdevices nddst ON nddst.id = nl.dst
			LEFT JOIN netnodes nndst ON nndst.id = nddst.netnodeid
			LEFT JOIN netradiosectors rsdst ON rsdst.id = nl.dstradiosector
			WHERE (src = ? OR dst = ?)
				AND (ndsrc.netnodeid IS NULL OR nnsrc.ownership < 2)
				AND (nddst.netnodeid IS NULL OR nndst.ownership < 2)
			GROUP BY nl.type, nl.technology, speed, freq",
			array($netdevice['id'], $netdevice['id'], $netdevice['id']));
		$accessports = $DB->GetAll("SELECT linktype AS type, linktechnology AS technology,
				linkspeed AS speed, rs.frequency, " . $DB->GroupConcat('rs.id') . " AS radiosectors,
				c.type AS customertype, COUNT(port) AS portcount
			FROM nodes n
			JOIN customers c ON c.id = n.ownerid
			LEFT JOIN netradiosectors rs ON rs.id = n.linkradiosector
			WHERE n.netdev = ? 
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
			GROUP BY linktype, linktechnology, linkspeed, rs.frequency, c.type
			ORDER BY c.type", array($netdevice['id']));

		$netdevices[$netdevid]['invproject'] = $netdevice['invproject'] =
			!strlen($netdevice['invprojectid']) ? '' : $projects[$netdevice['invprojectid']]['name'];

		$projectname = $prj = '';
		if (array_key_exists($netdevice['netnodeid'], $truenetnodes)) {
			$netnodename = $truenetnodes[$netdevice['netnodeid']]['name'];
			if (strlen($truenetnodes[$netdevice['netnodeid']]['invprojectid']))
				$projectname = $prj = $projects[$truenetnodes[$netdevice['netnodeid']]['invprojectid']]['name'];
		} else {
			$netnodename = empty($netdevice['location_city']) ? $netdevice['location'] :
				implode('_', array($netdevice['location_city'], $netdevice['location_street'],
					$netdevice['location_house'], $netdevice['location_flat']));
			if (array_key_exists($netnodename, $netnodes)) {
				if (!in_array($netdevice['invproject'], $netnodes[$netnodename]['invproject']))
					$netnodes[$netnodename]['invproject'][] = $netdevice['invproject'];
			} else {
				$prj = $netdevice['invproject'];
				$projectname = array($prj);
			}
		}

		$netdevice['netnodename'] = $netdevices[$netdevid]['netnodename'] = $netnodename;
		if (!array_key_exists($netnodename, $netnodes)) {
			$netnodes[$netnodename]['ports'] = 0;
			$netnodes[$netnodename]['backboneports'] = array();
			$netnodes[$netnodename]['totalbackboneports'] = 0;
			$netnodes[$netnodename]['distports'] = array();
			$netnodes[$netnodename]['totaldistports'] = 0;
			$netnodes[$netnodename]['accessports'] = array();
			$netnodes[$netnodename]['personalaccessports'] = 0;
			$netnodes[$netnodename]['commercialaccessports'] = 0;

			$netnodes[$netnodename]['id'] = $netnodeid;
			$netnodes[$netnodename]['invproject'] = $projectname;

			if (array_key_exists($netdevice['netnodeid'], $truenetnodes)) {
				$netnode = $truenetnodes[$netdevice['netnodeid']];
				$netnodes[$netnodename]['location'] = $netnode['location'];
				$netnodes[$netnodename]['location_city'] = $netnode['location_city'];
				$netnodes[$netnodename]['location_street'] = $netnode['location_street'];
				$netnodes[$netnodename]['location_house'] = $netnode['location_house'];
				$netnodes[$netnodename]['status'] = intval($netnode['status']);
				$netnodes[$netnodename]['type'] = intval($netnode['type']);
				$netnodes[$netnodename]['uip'] = intval($netnode['uip']);
				$netnodes[$netnodename]['miar'] = intval($netnode['miar']);
				$netnodes[$netnodename]['ownership'] = intval($netnode['ownership']);
				$netnodes[$netnodename]['coowner'] = $netnode['coowner'];
				if (strlen($netnode['coowner'])) {
					$coowner = $netnode['coowner'];
					if (!array_key_exists($coowner, $foreigners))
						$foreigners[$coowner] = array(
							'projects' => array(),
						);
					if (!in_array($prj, $foreigners[$coowner]['projects']))
						$foreigners[$coowner]['projects'][] = $prj;
				}
				if (isset($netnode['area_woj'])) {
					$netnodes[$netnodename]['area_woj'] = $netnode['area_woj'];
					$netnodes[$netnodename]['area_pow'] = $netnode['area_pow'];
					$netnodes[$netnodename]['area_gmi'] = $netnode['area_gmi'];
					list ($area_woj, $area_pow, $area_gmi, $area_rodz) = explode('_', $netnode['area_terc']);
					$netnodes[$netnodename]['area_terc'] = sprintf("%02d%02d%02d%s", $area_woj, $area_pow, $area_gmi, $area_rodz);
					$netnodes[$netnodename]['area_rodz_gmi'] = $borough_types[intval($area_rodz)];
					$netnodes[$netnodename]['area_city'] = $netnode['area_city'];
					$netnodes[$netnodename]['area_simc'] = sprintf("%07d", $netnode['area_simc']);
					$netnodes[$netnodename]['address_cecha'] = $netnode['address_cecha'];
					$netnodes[$netnodename]['address_ulica'] = $netnode['address_ulica'];
					$netnodes[$netnodename]['address_symul'] = sprintf("%05d", $netnode['address_symul']);
					$netnodes[$netnodename]['address_budynek'] = $netnode['address_budynek'];
					$netnodes[$netnodename]['location_zip'] = $netnode['location_zip'];
				}
			} else {
				$netnodes[$netnodename]['location'] = $netdevice['location'];
				$netnodes[$netnodename]['location_city'] = $netdevice['location_city'];
				$netnodes[$netnodename]['location_street'] = $netdevice['location_street'];
				$netnodes[$netnodename]['location_house'] = $netdevice['location_house'];
				$netnodes[$netnodename]['status'] = 0;
				$netnodes[$netnodename]['type'] = 8;
				$netnodes[$netnodename]['uip'] = 0;
				$netnodes[$netnodename]['miar'] = 0;
				$netnodes[$netnodename]['ownership'] = 0;
				$netnodes[$netnodename]['coowner'] = '';
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
			}
			$netnodes[$netnodename]['netdevices'] = array();
			$netnodes[$netnodename]['longitudes'] = array();
			$netnodes[$netnodename]['latitudes'] = array();
			$netnodeid++;
		}
		$netdevices[$netdevid]['ownership'] = $netnodes[$netnodename]['ownership'];

		$projectname = $prj = $netdevice['invproject'];
		if (!strlen($projectname))
			$status = 0;
		else
			$status = $netdevice['status'];

		if (!empty($backboneports))
			foreach ($backboneports as $port) {
				$linktype = $port['type'];
				$linktechnology = $port['technology'];
				$linkspeed = $port['speed'];
				$linkfrequency = empty($port['freq']) ? '' : (float) $port['freq'];
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
				if ($linktype == 1 && empty($linkfrequency))
					switch ($linktechnology) {
						case 100:
							$linkfrequency = 2.4;
							break;
						case 101:
							$linkfrequency = 5.5;
							break;
						default:
							$linkfrequency = '';
					}
				if (!empty($linkfrequency))
					$linkfrequency = str_replace(',', '.', (float) $linkfrequency);
				if (!isset($netnodes[$netnodename]['backboneports'][$prj][$status][$linktype][$linktechnology][$linkspeed][$linkfrequency]))
					$netnodes[$netnodename]['backboneports'][$prj][$status][$linktype][$linktechnology][$linkspeed][$linkfrequency] = 0;
				$netnodes[$netnodename]['backboneports'][$prj][$status][$linktype][$linktechnology][$linkspeed][$linkfrequency] += $port['portcount'];
				$netnodes[$netnodename]['totalbackboneports'] += $port['portcount'];
				$netnodes[$netnodename]['ports'] += $port['portcount'];
			}

		if (!empty($distports))
			foreach ($distports as $port) {
				$linktype = $port['type'];
				$linktechnology = $port['technology'];
				$linkspeed = $port['speed'];
				$linkfrequency = empty($port['freq']) ? '' : (float) $port['freq'];
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
				if ($linktype == 1 && empty($linkfrequency))
					switch ($linktechnology) {
						case 100:
							$linkfrequency = 2.4;
							break;
						case 101:
							$linkfrequency = 5.5;
							break;
						default:
							$linkfrequency = '';
					}
				if (!empty($linkfrequency))
					$linkfrequency = str_replace(',', '.', (float) $linkfrequency);
				if (!isset($netnodes[$netnodename]['distports'][$prj][$status][$linktype][$linktechnology][$linkspeed][$linkfrequency]))
					$netnodes[$netnodename]['distports'][$prj][$status][$linktype][$linktechnology][$linkspeed][$linkfrequency] = 0;
				$netnodes[$netnodename]['distports'][$prj][$status][$linktype][$linktechnology][$linkspeed][$linkfrequency] += $port['portcount'];
				$netnodes[$netnodename]['totaldistports'] += $port['portcount'];
				$netnodes[$netnodename]['ports'] += $port['portcount'];
			}

		if (!empty($accessports))
			foreach ($accessports as $ports) {
				$linktype = $ports['type'];
				$linktechnology = $ports['technology'];
				$linkspeed = $ports['speed'];
				$linkfrequency = empty($ports['frequency']) ? '' : (float) $ports['frequency'];
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
				if ($linktype == 1 && empty($linkfrequency))
					switch ($linktechnology) {
						case 100:
							$linkfrequency = 2.4;
							break;
						case 101:
							$linkfrequency = 5.5;
							break;
						default:
							$linkfrequency = '';
					}
				if (!empty($linkfrequency))
					$linkfrequency = str_replace(',', '.', (float) $linkfrequency);
				if (!isset($netnodes[$netnodename]['accessports'][$prj][$status][$linktype][$linktechnology][$linkspeed][$linkfrequency]['customers'][$customertype]))
					$netnodes[$netnodename]['accessports'][$prj][$status][$linktype][$linktechnology][$linkspeed][$linkfrequency]['customers'][$customertype] = 0;
				$netnodes[$netnodename]['accessports'][$prj][$status][$linktype][$linktechnology][$linkspeed][$linkfrequency]['customers'][$customertype] += $ports['portcount'];
				$netnodes[$netnodename]['accessports'][$prj][$status][$linktype][$linktechnology][$linkspeed][$linkfrequency]['radiosectors'] =
					empty($ports['radiosectors']) ? array() : explode(',', $ports['radiosectors']);
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

$foreignerid = 1;
$sforeigners = '';
foreach ($foreigners as $name => $foreigner)
	foreach ($foreigner['projects'] as $project) {
		$data = array(
			'ob_id' => $foreignerid,
			'ob_name' => $name,
			'ob_nip' => '',
			'ob_regon' => '',
			'ob_rpt' => '',
			'ob_state' => '',
			'ob_district' => '',
			'ob_borough' => '',
			'ob_terc' => '',
			'ob_city' => '',
			'ob_simc' => '',
			'ob_street' => '',
			'ob_ulic' => '',
			'ob_house' => '',
			'ob_zip' => '',
			'ob_invproject' => $project,
		);
		if (in_array('po', $sheets))
			if ($format == 2)
				$buffer .= 'PO,' . to_csv($data) . EOL;
			else
				$sforeigners .= to_old_csv($ob_keys, $data) . EOL;
		$foreignerid++;
	}

$netintid = 1;
$netbuildingid = 1;
$netrangeid = 1;
$radiosectorid = 1;
$snetnodes = '';
$sforeignernetnodes = '';
$snetconnections = '';
$snetinterfaces = '';
$sradiosectors = '';
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

	if (is_array($netnode['invproject']))
		$netnodes[$netnodename]['invproject'] = $netnode['invproject'] =
			count($netnode['invproject']) == 1 ? $netnode['invproject'][0] : '';

	if ($netnode['ownership'] < 2) {
		$data = array(
			'ww_id' => $netnode['id'],
			'ww_ownership' => $NETELEMENTOWNERSHIPS[$netnode['ownership']],
			'ww_coowner' => $netnode['coowner'],
			'ww_coloc' => '',
			'ww_state' => isset($netnode['area_woj']) ? $netnode['area_woj']
				: "LMS netdevinfo ID's:" . implode(' ', $netnode['netdevices']) . "," . implode(',', array_fill(0, 9, '')),
			'ww_district' => $netnode['area_pow'],
			'ww_borough' => $netnode['area_gmi'],
			'ww_terc' => sprintf("%07d", $netnode['area_terc']),
			'ww_city' => $netnode['area_city'],
			'ww_simc' => sprintf("%07d", $netnode['area_simc']),
			'ww_street' => (!empty($netnode['address_cecha']) && $netnode['address_cecha'] != 'inne'
				? $netnode['address_cecha'] . ' ' : '') . $netnode['address_ulica'],
			'ww_ulic' => sprintf("%05d", $netnode['address_symul']),
			'ww_house' => $netnode['address_budynek'],
			'ww_zip' => $netnode['location_zip'],
			'ww_latitude' =>  isset($netnode['latitude']) ? $netnode['latitude'] : '',
			'ww_longitude' => isset($netnode['longitude']) ? $netnode['longitude'] : '',
			'ww_objtype' => $NETELEMENTTYPES[$netnode['type']],
			'ww_uip' => $netnode['uip'] ? 'Tak' : 'Nie',
			'ww_miar' => $netnode['miar'] ? 'Tak' : 'Nie',
			'ww_eu' => $netnode['invproject'] ? 'Tak' : 'Nie',
			'ww_invproject' => $netnode['invproject'],
			'ww_invstatus' => $netnode['invproject'] ? $NETELEMENTSTATUSES[$netnode['status']] : '',
		);
		if (in_array('ww', $sheets))
			if ($format == 2)
				$buffer .= 'WW,' . to_csv($data) . EOL;
			else
				$snetnodes .= to_old_csv($ww_keys, $data) . EOL;
	} else {
		$data = array(
			'wo_id' => $netnode['id'],
			'wo_agreement' => 'Umowa o dostęp do sieci telekomunikacyjnej',
			'wo_coowner' => $netnode['coowner'],
			'wo_state' => isset($netnode['area_woj']) ? $netnode['area_woj']
				: "LMS netdevinfo ID's:" . implode(' ', $netnode['netdevices']) . "," . implode(',', array_fill(0, 9, '')),
			'wo_district' => $netnode['area_pow'],
			'wo_borough' => $netnode['area_gmi'],
			'wo_terc' => sprintf("%07d", $netnode['area_terc']),
			'wo_city' => $netnode['area_city'],
			'wo_simc' => sprintf("%07d", $netnode['area_simc']),
			'wo_street' => (!empty($netnode['address_cecha']) && $netnode['address_cecha'] != 'inne'
				? $netnode['address_cecha'] . ' ' : '') . $netnode['address_ulica'],
			'wo_ulic' => sprintf("%05d", $netnode['address_symul']),
			'wo_house' => $netnode['address_budynek'],
			'wo_zip' => $netnode['location_zip'],
			'wo_latitude' =>  isset($netnode['latitude']) ? $netnode['latitude'] : '',
			'wo_longitude' => isset($netnode['longitude']) ? $netnode['longitude'] : '',
			'wo_objtype' => $NETELEMENTTYPES[$netnode['type']],
			'wo_invproject' => $netnode['invproject'],
		);
		if (in_array('wo', $sheets))
			if ($format == 2)
				$buffer .= 'WO,' . to_csv($data) . EOL;
			else
				$sforeignernetnodes .= to_old_csv($wo_keys, $data) . EOL;
	}
	if ($netnode['ownership'] == 2)
		continue;

	// save info about network interfaces located in backbone layer
	if (!empty($netnode['totalbackboneports']))
		foreach ($netnode['backboneports'] as $prj => $statuses) {
			foreach ($statuses as $status => $backboneports)
				foreach ($backboneports as $linktype => $linktechnologies)
					foreach ($linktechnologies as $linktechnology => $linkspeeds) {
						if ($linktechnology < 50 || $linktechnology >= 100)
							$technology = $linktypes[$linktype]['technologia'];
						else
							$technology = 'kablowe współosiowe miedziane';
						$ltech = $LINKTECHNOLOGIES[$linktype][$linktechnology];

						foreach ($linkspeeds as $linkspeed => $linkfrequencies) {
							foreach ($linkfrequencies as $linkfrequency => $totalbackboneports) {
								$data = array(
									'int_id' => $netintid,
									'int_wwid' => $netnode['id'],
									'int_blayer' => 'Tak',
									'int_dlayer' => 'Nie',
									'int_alayer' => 'Nie',
									'int_tech' => $technology,
									'int_bandwidth' => strlen($linkfrequency) ? str_replace(',', '.', sprintf("%.2f", $linkfrequency)) : '',
									'int_ltech' => $ltech,
									'int_maxdown' => round($linkspeed / 1000),
									'int_maxup' => round($linkspeed / 1000),
									'int_totalports' => $totalbackboneports,
									'int_usedports' => $totalbackboneports,
									'int_freeports' => 0,
									'int_portleasing' => 'Nie',
									'int_invproject' => strlen($prj) ? $prj : '',
									'int_invstatus' => strlen($prj) ? $NETELEMENTSTATUSES[$status] : '',
								);
								if (in_array('int', $sheets))
									if ($format == 2)
										$buffer .= 'I,' . to_csv($data) . EOL;
									else
										$snetinterfaces .= to_old_csv($int_keys, $data) . EOL;
								$netnodes[$netnodename]['backbonenetintid'][$prj][$status][$linktype][$linktechnology][$linkspeed] =
									$netintid;
								$netintid++;
							}
						}
					}
		}

	// save info about network interfaces located in distribution layer
	if ($netnode['totaldistports'] > 0
		|| $netnode['personalaccessports'] + $netnode['commercialaccessports'] == 0)
		foreach ($netnode['distports'] as $prj => $statuses) {
			foreach ($statuses as $status => $distports)
				foreach ($distports as $linktype => $linktechnologies)
					foreach ($linktechnologies as $linktechnology => $linkspeeds) {
						if ($linktechnology < 50 || $linktechnology >= 100)
							$technology = $linktypes[$linktype]['technologia'];
						else
							$technology = 'kablowe współosiowe miedziane';
						$ltech = $LINKTECHNOLOGIES[$linktype][$linktechnology];

						foreach ($linkspeeds as $linkspeed => $linkfrequencies) {
							foreach ($linkfrequencies as $linkfrequency => $totaldistports) {
								$data = array(
									'int_id' => $netintid,
									'int_wwid' => $netnode['id'],
									'int_blayer' => 'Nie',
									'int_dlayer' => 'Tak',
									'int_alayer' => 'Nie',
									'int_tech' => $technology,
									'int_bandwidth' => strlen($linkfrequency) ? str_replace(',', '.', sprintf("%.2f", $linkfrequency)) : '',
									'int_ltech' => $ltech,
									'int_maxdown' => round($linkspeed / 1000),
									'int_maxup' => round($linkspeed / 1000),
									'int_totalports' => $totaldistports,
									'int_usedports' => $totaldistports,
									'int_freeports' => 0,
									'int_portleasing' => 'Nie',
									'int_invproject' => strlen($prj) ? $prj : '',
									'int_invstatus' => strlen($prj) ? $NETELEMENTSTATUSES[$status] : '',
								);
								if (in_array('int', $sheets))
									if ($format == 2)
										$buffer .= 'I,' . to_csv($data) . EOL;
									else
										$snetinterfaces .= to_old_csv($int_keys, $data) . EOL;
								$netintid++;
							}
						}
					}
		}

	// save info about network interfaces located in access layer
	if (!empty($netnode['accessports'])) {
		$idx = 0;
		foreach ($netnode['accessports'] as $prj => $statuses) {
			foreach ($statuses as $status => $accessports)
				foreach ($accessports as $linktype => $linktechnologies)
					foreach ($linktechnologies as $linktechnology => $linkspeeds) {
						if ($linktechnology < 50 || $linktechnology >= 100)
							$technology = $linktypes[$linktype]['technologia'];
						else
							$technology = 'kablowe współosiowe miedziane';
						$ltech = $LINKTECHNOLOGIES[$linktype][$linktechnology];

						foreach ($linkspeeds as $linkspeed => $linkfrequencies)
							foreach ($linkfrequencies as $linkfrequency => $customertypes) {
								$ports = 0;
								foreach ($customertypes['customers'] as $customertypeports)
									$ports += $customertypeports;
								$data = array(
									'int_id' => $netintid,
									'int_wwid' => $netnode['id'],
									'int_blayer' => 'Nie',
									'int_dlayer' => 'Nie',
									'int_alayer' => 'Tak',
									'int_tech' => $technology,
									'int_bandwidth' => strlen($linkfrequency) ? str_replace(',', '.', sprintf("%.2f", $linkfrequency)) : '',
									'int_ltech' => $ltech,
									'int_maxdown' => round($linkspeed / 1000),
									'int_maxup' => round($linkspeed / 1000),
									'int_totalports' => ($netnode['ports'] - $netnode['totaldistports']
										- $netnode['personalaccessports'] - $netnode['commercialaccessports']
										+ $ports),
									'int_usedports' => $ports,
									'int_freeports' => ($netnode['ports'] - $netnode['totaldistports']
										- $netnode['personalaccessports'] - $netnode['commercialaccessports']),
									'int_portleasing' => 'Nie',
									'int_invproject' => strlen($prj) ? $prj : '',
									'int_invstatus' => strlen($prj) ? $NETELEMENTSTATUSES[$status] : '',
								);
								if (!$idx) {
									$data['int_totalports'] = $netnode['ports'] - $netnode['totaldistports']
										- $netnode['personalaccessports'] - $netnode['commercialaccessports']
										+ $ports;
									$data['int_usedports'] = $ports;
									$data['int_freeports'] = $netnode['ports'] - $netnode['totaldistports']
										- $netnode['personalaccessports'] - $netnode['commercialaccessports'];
								} else {
									$data['int_totalports'] = $ports;
									$data['int_usedports'] = $ports;
									$data['int_freeports'] = 0;
								}
								if (in_array('int', $sheets))
									if ($format == 2)
										$buffer .= 'I,' . to_csv($data) . EOL;
									else
										$snetinterfaces .= to_old_csv($int_keys, $data) . EOL;
								if ($linktype == 1) {
									$radiosectors = $customertypes['radiosectors'];
									if (empty($radiosectors)) {
										$data = array(
											'sr_id' => $radiosectorid,
											'sr_wwid' => $netnode['id'],
											'sr_intid' => $netintid,
											'sr_license' => 'Nie',
											'sr_licensenr' => '',
											'sr_azimuth' => 0,
											'sr_width' => 360,
											'sr_altitude' => 20,
											'sr_range' => 500,
											'sr_maxspeed' => round($linkspeed / 1000),
											'sr_invproject' => strlen($prj) ? $prj : '',
											'sr_invstatus' => strlen($prj) ? $NETELEMENTSTATUSES[$status] : '',
										);
										if (in_array('sr', $sheets))
											if ($format == 2)
												$buffer .= 'Z,' . to_csv($data) . EOL;
											else
												$sradiosectors .= to_old_csv($sr_keys, $data) . EOL;
										$radiosectorid++;
									} else
										foreach ($radiosectors as $radiosector) {
											$radiosector = $allradiosectors[$radiosector];
											$data = array(
												'sr_id' => $radiosectorid,
												'sr_wwid' => $netnode['id'],
												'sr_intid' => $netintid,
												'sr_license' => empty($radiosector['license']) ? 'Nie' : 'Tak',
												'sr_licensenr' => empty($radiosector['license']) ? '' : $radiosector['license'],
												'sr_azimuth' => round($radiosector['azimuth']),
												'sr_width' => round($radiosector['width']),
												'sr_altitude' => $radiosector['altitude'],
												'sr_range' => $radiosector['rsrange'],
												'sr_maxspeed' => round($linkspeed / 1000),
												'sr_invproject' => strlen($prj) ? $prj : '',
												'sr_invstatus' => strlen($prj) ? $NETELEMENTSTATUSES[$status] : '',
											);
											if (in_array('sr', $sheets))
												if ($format == 2)
													$buffer .= 'Z,' . to_csv($data) . EOL;
												else
													$sradiosectors .= to_old_csv($sr_keys, $data) . EOL;
											$radiosectorid++;
										}
								}
								$netintid++;
							}
					}
		}
	}

	// save info about network ranges
	$ranges = $DB->GetAll("SELECT n.linktype, n.linktechnology, n.location_street, n.location_city, n.location_house
		FROM nodes n
		WHERE n.ownerid > 0 AND n.location_city <> 0 AND n.netdev IN (" . implode(',', $netnode['netdevices']) . ")
		GROUP BY n.linktype, n.linktechnology, n.location_street, n.location_city, n.location_house");
	if (empty($ranges))
		continue;

	// this variable will change its value to true if network node will have range with the same location (city, street, house)
	$range_netbuilding = false;

	$range_maxdownstream = 0;
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

		// get info about computers connected to network node
		$nodes = $DB->GetAll("SELECT na.nodeid, c.type, n.invprojectid, nd.id AS netdevid, nd.status,"
			. $DB->GroupConcat("DISTINCT (CASE t.type WHEN ".TARIFF_INTERNET." THEN 'INT'
				WHEN ".TARIFF_PHONE." THEN 'TEL'
				WHEN ".TARIFF_TV." THEN 'TV'
				ELSE 'INT' END)") . " AS servicetypes, SUM(t.downceil) AS downstream, SUM(t.upceil) AS upstream
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
			JOIN netdevices nd ON nd.id = n.netdev
			WHERE n.ownerid > 0 AND n.netdev > 0 AND n.linktype = ? AND n.linktechnology = ? AND n.location_city = ?
				AND (n.location_street = ? OR n.location_street IS NULL) AND n.location_house = ?
				AND a.suspended = 0 AND a.period IN (".implode(',', array(YEARLY, HALFYEARLY, QUARTERLY, MONTHLY, DISPOSABLE)).")
				AND (a.datefrom = 0 OR a.datefrom < ?NOW?) AND (a.dateto = 0 OR a.dateto > ?NOW?)
				AND allsuspended.total IS NULL
			GROUP BY na.nodeid, c.type, n.invprojectid, nd.id, nd.status",
			array($range['linktype'], $range['linktechnology'], $range['location_city'], $range['location_street'], $range['location_house']));

		if (empty($nodes))
			continue;

		// check if this is range with the same location as owning network node
		if ($range['location_city'] == $netnode['location_city']
			&& $range['location_street'] == $netnode['location_street']
			&& $range['location_house'] == $netnode['location_house'])
			$range_netbuilding = true;

		$prjnodes = array();
		foreach ($nodes as $node) {
			$status = $node['status'];
			if (!strlen($node['invprojectid']))
				$prj = '';
			elseif ($node['invprojectid'] > 1)
				$prj = $projects[$node['invprojectid']]['name'];
			else
				$prj = $netdevices[$node['netdevid']]['invproject'];
			if (!isset($prjnodes[$prj][$status]))
				$prjnodes[$prj][$status] = array();
			$prjnodes[$prj][$status][] = $node;
		}

		foreach ($prjnodes as $prj => $statuses) {
			foreach ($statuses as $status => $nodes) {
				// count all kinds of link speeds for computers connected to this network node
				$personalnodes = array();
				$commercialnodes = array();
				$maxdownstream = 0;
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

				$data = array(
					'zas_id' => $netbuildingid,
					'zas_ownership' => 'Własna',
					'zas_leasetype' => '',
					'zas_foreignerid' => '',
					'zas_nodeid' => $netnode['id'],
					'zas_state' => $teryt['area_woj'],
					'zas_district' => $teryt['area_pow'],
					'zas_borough' => $teryt['area_gmi'],
					'zas_terc' => $teryt['area_terc'],
					'zas_city' => $teryt['area_city'],
					'zas_simc' => sprintf("%07d", $teryt['area_simc']),
					'zas_street' => (!empty($teryt['address_cecha']) && $teryt['address_cecha'] != 'inne'
						? $teryt['address_cecha'] . ' ' : '') . $teryt['address_ulica'],
					'zas_ulic' => sprintf("%05d", $teryt['address_symul']),
					'zas_house' => $teryt['address_budynek'],
					'zas_zip' => $teryt['location_zip'],
					'zas_latitude' => $netnode['latitude'],
					'zas_longitude' => $netnode['longitude'],
					'zas_tech' => $technology,
					'zas_ltech' => $linktechnology,
				);

				$allservices = array();

				foreach (array_unique(array_merge(array_keys($personalnodes), array_keys($commercialnodes))) as $servicetype) {
					$services = array_flip(explode(',', $servicetype));
					$ukeservices = array();
					foreach (array('TEL', 'INT', 'TV') as $service)
						if (isset($services[$service])) {
							$ukeservices[] = $service;
							$allservices[] = $service;
						}
					$us_data = array(
						'us_id' => $netrangeid,
						'us_netbuildingid' => $netbuildingid,
						'us_phonepots' => array_search('TEL', $ukeservices) !== FALSE
							&& $range['linktechnology'] == 12 ? 'Tak' : 'Nie',
						'us_phonevoip' => array_search('TEL', $ukeservices) !== FALSE && $range['linktechnology'] != 12
							&& ($range['linktechnology'] < 105 || $range['linktechnology'] >= 200) ? 'Tak' : 'Nie',
						'us_phonemobile' => array_search('TEL', $ukeservices) !== FALSE
							&& $range['linktechnology'] >= 105 && $range['linktechnology'] < 200 ? 'Tak' : 'Nie',
						'us_internetstationary' => array_search('INT', $ukeservices) !== FALSE
							&& ($range['linktechnology'] < 105 || $range['linktechnology'] >= 200) ? 'Tak' : 'Nie',
						'us_internetmobile' => array_search('INT', $ukeservices) !== FALSE
							&& $range['linktechnology'] >= 105 && $range['linktechnology'] < 200 ? 'Tak' : 'Nie',
						'us_tv' => array_search('TV', $ukeservices) !== FALSE ? 'Tak' : 'Nie',
						'us_other' => '',
					);

					if (isset($personalnodes[$servicetype])) {
						if (array_search('INT', $ukeservices) !== FALSE)
							$personalservices = $personalnodes[$servicetype];
						else {
							$count = 0;
							foreach ($personalnodes[$servicetype] as $nodes)
								$count += $nodes;
							$personalservices = array_fill(0, 10, '0');
							array_unshift($personalservices, $count);
						}
					} else
						$personalservices = array_fill(0, 11, '0');

					if (isset($commercialnodes[$servicetype])) {
						if (array_search('INT', $ukeservices) !== FALSE)
							$commercialservices = $commercialnodes[$servicetype];
						else {
							$count = 0;
							foreach ($commercialnodes[$servicetype] as $nodes)
								$count += $nodes;
							$commercialservices = array_fill(0, 10, '0');
							array_unshift($commercialservices, $count);
						}
					} else
						$commercialservices = array_fill(0, 11, '0');

					foreach ($personalservices as $idx => $service)
						$us_data['us_personal' . $idx] = $service;
					foreach ($commercialservices as $idx => $service)
						$us_data['us_commercial' . $idx] = $service;

					$us_data = array_merge($us_data, array(
						'us_invproject' => strlen($prj) ? $prj : '',
						'us_invstatus' => strlen($prj) ? $NETELEMENTSTATUSES[$status] : '',
					));

					if (in_array('us', $sheets))
						if ($format == 2)
							$buffer .= 'U,' . to_csv($us_data) . EOL;
						else
							$snetranges .= to_old_csv($us_keys, $us_data) . EOL;
					$netrangeid++;
				}

				$allservices = array_unique($allservices);

				$maxdownstream = round($maxdownstream / 1000, 2);
				if ($maxdownstream <= 1)
					$maxdownstream = 1;
				elseif ($maxdownstream <= 2)
					$maxdownstream = 2;
				elseif ($maxdownstream <= 4)
					$maxdownstream = 4;
				elseif ($maxdownstream <= 6)
					$maxdownstream = 6;
				elseif ($maxdownstream <= 8)
					$maxdownstream = 8;
				elseif ($maxdownstream <= 10)
					$maxdownstream = 10;
				elseif ($maxdownstream <= 20)
					$maxdownstream = 20;
				elseif ($maxdownstream <= 30)
					$maxdownstream = 30;
				elseif ($maxdownstream <= 40)
					$maxdownstream = 40;
				elseif ($maxdownstream <= 60)
					$maxdownstream = 60;
				elseif ($maxdownstream <= 80)
					$maxdownstream = 80;
				elseif ($maxdownstream <= 100)
					$maxdownstream = 100;
				elseif ($maxdownstream <= 120)
					$maxdownstream = 120;
				elseif ($maxdownstream <= 150)
					$maxdownstream = 150;
				elseif ($maxdownstream <= 250)
					$maxdownstream = 250;
				elseif ($maxdownstream <= 500)
					$maxdownstream = 500;
				elseif ($maxdownstream <= 1000)
					$maxdownstream = 1000;
				elseif ($maxdownstream <= 2500)
					$maxdownstream = 2500;
				elseif ($maxdownstream <= 10000)
					$maxdownstream = 10000;
				elseif ($maxdownstream <= 40000)
					$maxdownstream = 40000;
				else
					$maxdownstream = 100000;

				if ($maxdownstream > $range_maxdownstream) {
					$range_maxdownstream = $maxdownstream;
					$range_technology = $technology;
					$range_linktechnology = $linktechnology;
				}

				$data = array_merge($data, array(
					'zas_phonepots' => array_search('TEL', $allservices) !== FALSE
						&& $range['linktechnology'] == 12 ? 'Tak' : 'Nie',
					'zas_phonevoip' => array_search('TEL', $allservices) !== FALSE && $range['linktechnology'] != 12
						&& ($range['linktechnology'] < 105 || $range['linktechnology'] >= 200) ? 'Tak' : 'Nie',
					'zas_phonemobile' => array_search('TEL', $allservices) !== FALSE
						&& $range['linktechnology'] >= 105 && $range['linktechnology'] < 200 ? 'Tak' : 'Nie',
					'zas_internetstationary' => array_search('INT', $allservices) !== FALSE
						&& ($range['linktechnology'] < 105 || $range['linktechnology'] >= 200) ? 'Tak' : 'Nie',
					'zas_internetmobile' => array_search('INT', $allservices) !== FALSE
						&& $range['linktechnology'] >= 105 && $range['linktechnology'] < 200 ? 'Tak' : 'Nie',
					'zas_tv' => array_search('TV', $allservices) !== FALSE ? 'Tak' : 'Nie',
					'zas_other' => '',
					'zas_stationarymaxspeed' => array_search('INT', $allservices) !== FALSE 
						&& ($range['linktechnology'] < 105 || $range['linktechnology'] >= 200) ? $maxdownstream : '0',
					'zas_mobilemaxspeed' => array_search('INT', $allservices) !== FALSE
						&& $range['linktechnology'] >= 105 && $range['linktechnology'] < 200 ? $maxdownstream : '0',
					'zas_invproject' => strlen($prj) ? $prj : '',
					'zas_invstatus' => strlen($prj) ? $NETELEMENTSTATUSES[$status] : '',
				));
				if (in_array('zas', $sheets))
					if ($format == 2)
						$buffer .= 'ZS,' . to_csv($data) . EOL;
					else
						$snetbuildings .= to_old_csv($zas_keys, $data) . EOL;
				$netbuildingid++;
			}
		}
	}
	// unfortunately network node doesn't have range with the same location
	if (!$range_netbuilding) {
		$data = array(
			'zas_id' => $netbuildingid,
			'zas_ownership' => 'Własna',
			'zas_leasetype' => '',
			'zas_foreignerid' => '',
			'zas_nodeid' => $netnode['id'],
			'zas_state' => $netnode['area_woj'],
			'zas_district' => $netnode['area_pow'],
			'zas_borough' => $netnode['area_gmi'],
			'zas_terc' => $netnode['area_terc'],
			'zas_city' => $netnode['area_city'],
			'zas_simc' => sprintf("%07d", $netnode['area_simc']),
			'zas_street' => (!empty($netnode['address_cecha']) && $netnode['address_cecha'] != 'inne'
				? $netnode['address_cecha'] . ' ' : '') . $netnode['address_ulica'],
			'zas_ulic' => sprintf("%05d", $netnode['address_symul']),
			'zas_house' => $netnode['address_budynek'],
			'zas_zip' => $netnode['location_zip'],
			'zas_latitude' => $netnode['latitude'],
			'zas_longitude' => $netnode['longitude'],
			'zas_tech' => $range_technology,
			'zas_ltech' => $range_linktechnology,
			'zas_phonepots' => 'Nie',
			'zas_phonevoip' => 'Nie',
			'zas_phonemobile' => 'Nie',
			'zas_internetstationary' => 'Tak',
			'zas_internetmobile' => 'Nie',
			'zas_tv' => 'Nie',
			'zas_other' => '',
			'zas_stationarymaxspeed' => $range_maxdownstream,
			'zas_mobilemaxspeed' => 0,
			'zas_invproject' => '',
			'zas_invstatus' => '',
		);
		if (in_array('zas', $sheets))
			if ($format == 2)
				$buffer .= 'ZS,' . to_csv($data) . EOL;
			else
				$snetbuildings .= to_old_csv($zas_keys, $data) . EOL;
		$netbuildingid++;
	}
}

//prepare info about network links (only between different network nodes)
$netconnectionid = 1;
$processed_netlinks = array();
$netlinks = array();
if ($netdevices)
	foreach ($netdevices as $netdevice) {
		$ndnetlinks = $DB->GetAll("SELECT src, dst, type, speed, nl.technology,
			(CASE src WHEN ? THEN (CASE WHEN srcrs.license IS NULL THEN dstrs.license ELSE srcrs.license END)
				ELSE (CASE WHEN dstrs.license IS NULL THEN srcrs.license ELSE dstrs.license END) END) AS license,
			(CASE src WHEN ? THEN (CASE WHEN srcrs.frequency IS NULL THEN dstrs.frequency ELSE srcrs.frequency END)
				ELSE (CASE WHEN dstrs.frequency IS NULL THEN srcrs.frequency ELSE dstrs.frequency END) END) AS frequency
			FROM netlinks nl
			LEFT JOIN netradiosectors srcrs ON srcrs.id = nl.srcradiosector
			LEFT JOIN netradiosectors dstrs ON dstrs.id = nl.dstradiosector
			WHERE src = ? OR dst = ?",
			array($netdevice['id'], $netdevice['id'], $netdevice['id'], $netdevice['id']));
		if ($ndnetlinks)
			foreach ($ndnetlinks as $netlink) {
				$netdevnetnode = $netdevs[$netdevice['id']];
				$srcnetnode = $netdevs[$netlink['src']];
				$dstnetnode = $netdevs[$netlink['dst']];
				$netnodeids = array($netnodes[$srcnetnode]['id'], $netnodes[$dstnetnode]['id']);
				sort($netnodeids);
				$netnodelinkid = implode('_', $netnodeids);
				if (!isset($processed_netlinks[$netnodelinkid])) {
					$linkspeed = $netlink['speed'];
					$speed = floor($linkspeed / 1000);
					$netintid = '';
					if ($netlink['src'] == $netdevice['id']) {
						if ($netdevnetnode != $dstnetnode) {
							if ($netdevices[$netlink['src']]['invproject'] == $netdevices[$netlink['dst']]['invproject']
								|| strlen($netdevices[$netlink['src']]['invproject']) || strlen($netdevices[$netlink['dst']]['invproject']))
								$invproject = $netdevices[$netlink['src']]['invproject'];
							else
								$invproject = '';
							if ($netdevices[$netlink['src']]['status'] == $netdevices[$netlink['dst']]['status'])
								$status = $netdevices[$netlink['src']]['status'];
							elseif ($netdevices[$netlink['src']]['status'] == 2 || $netdevices[$netlink['dst']]['status'] == 2)
								$status = 2;
							elseif ($netdevices[$netlink['src']]['status'] == 1 || $netdevices[$netlink['dst']]['status'] == 1)
								$status = 1;

							$processed_netlinks[$netnodelinkid] = true;
							$netnodes[$netdevnetnode]['distports']++;
							$foreign = false;

							if ($netnodes[$netdevnetnode]['ownership'] == 2 && $netnodes[$dstnetnode]['ownership'] < 2) {
								$invproject = strlen($netnodes[$dstnetnode]['invproject']) ? $netnodes[$dstnetnode]['invproject'] : '';
								$netintid = $netnodes[$dstnetnode]['backbonenetintid'][$invproject][$netnodes[$dstnetnode]['status']][$netlink['type']][$netlink['technology']][$netlink['speed']];
								$data = array(
									'ps_id' => $netconnectionid,
									'ps_wwid' => $netnodes[$dstnetnode]['id'],
									'ps_woid' => $netnodes[$netdevnetnode]['id'],
									'ps_intid' => $netintid,
									'ps_internetusage' => 'Tak',
									'ps_voiceusage' => 'Nie',
									'ps_otherusage' => 'Nie',
									'ps_totalspeed' => $speed,
									'ps_internetspeed' => $speed,
									'ps_invproject' => $netnodes[$netdevnetnode]['invproject'],
									'ps_invstatus' => strlen($netnodes[$netdevnetnode]['invproject']) ? $NETELEMENTSTATUSES[$netnodes[$netdevnetnode]['status']] : '',
								);
								if (in_array('ps', $sheets))
									if ($format == 2)
										$buffer .= 'PS,' . to_csv($data) . EOL;
									else
										$snetconnections .= to_old_csv($ps_keys, $data) . EOL;

								$netconnectionid++;
								$foreign = true;
							}
							if ($netnodes[$netdevnetnode]['ownership'] < 2 && $netnodes[$dstnetnode]['ownership'] == 2) {
								$invproject = strlen($netnodes[$netdevnetnode]['invproject']) ? $netnodes[$netdevnetnode]['invproject'] : '';
								$netintid = $netnodes[$netdevnetnode]['backbonenetintid'][$invproject][$netnodes[$netdevnetnode]['status']][$netlink['type']][$netlink['technology']][$netlink['speed']];
								$data = array(
									'ps_id' => $netconnectionid,
									'ps_wwid' => $netnodes[$netdevnetnode]['id'],
									'ps_woid' => $netnodes[$dstnetnode]['id'],
									'ps_intid' => $netintid,
									'ps_internetusage' => 'Tak',
									'ps_voiceusage' => 'Nie',
									'ps_otherusage' => 'Nie',
									'ps_totalspeed' => $speed,
									'ps_internetspeed' => $speed,
									'ps_invproject' => $netnodes[$dstnetnode]['invproject'],
									'ps_invstatus' => strlen($netnodes[$dstnetnode]['invproject']) ? $NETELEMENTSTATUSES[$netnodes[$dstnetnode]['status']] : '',
								);
								if (in_array('ps', $sheets))
									if ($format == 2)
										$buffer .= 'PS,' . to_csv($data) . EOL;
									else
										$snetconnections .= to_old_csv($ps_keys, $data) . EOL;
								$netconnectionid++;
								$foreign = true;
							}

							$netlinks[] = array(
								'type' => $netlink['type'],
								'speed' => $speed,
								'technology' => $netlink['technology'],
								'src' => $netdevnetnode,
								'dst' => $dstnetnode,
								'license' => $netlink['license'],
								'frequency' => $netlink['frequency'],
								'invproject' => $invproject,
								'status' => $status,
								'foreign' => $foreign,
							);
						}
					} else
						if ($netdevnetnode != $srcnetnode) {
							if ($netdevices[$netlink['src']]['invproject'] == $netdevices[$netlink['dst']]['invproject']
								|| strlen($netdevices[$netlink['src']]['invproject']) || strlen($netdevices[$netlink['dst']]['invproject']))
								$invproject = $netdevices[$netlink['src']]['invproject'];
							else
								$invproject = '';
							if ($netdevices[$netlink['src']]['status'] == $netdevices[$netlink['dst']]['status'])
								$status = $netdevices[$netlink['src']]['status'];
							elseif ($netdevices[$netlink['src']]['status'] == 2 || $netdevices[$netlink['dst']]['status'] == 2)
								$status = 2;
							elseif ($netdevices[$netlink['src']]['status'] == 1 || $netdevices[$netlink['dst']]['status'] == 1)
								$status = 1;

							$processed_netlinks[$netnodelinkid] = true;
							$netnodes[$netdevnetnode]['distports']++;
							$foreign = false;

							if ($netnodes[$netdevnetnode]['ownership'] == 2 && $netnodes[$srcnetnode]['ownership'] < 2) {
								$invproject = strlen($netnodes[$srcnetnode]['invproject']) ? $netnodes[$srcnetnode]['invproject'] : '';
								$netintid = $netnodes[$srcnetnode]['backbonenetintid'][$invproject][$netnodes[$srcnetnode]['status']][$netlink['type']][$netlink['technology']][$netlink['speed']];
								$data = array(
									'ps_id' => $netconnectionid,
									'ps_wwid' => $netnodes[$srcnetnode]['id'],
									'ps_woid' => $netnodes[$netdevnetnode]['id'],
									'ps_intid' => $netintid,
									'ps_internetusage' => 'Tak',
									'ps_voiceusage' => 'Nie',
									'ps_otherusage' => 'Nie',
									'ps_totalspeed' => $speed,
									'ps_internetspeed' => $speed,
									'ps_invproject' => $netnodes[$netdevnetnode]['invproject'],
									'ps_invstatus' => strlen($netnodes[$netdevnetnode]['invproject']) ? $NETELEMENTSTATUSES[$netnodes[$netdevnetnode]['status']] : '',
								);
								if (in_array('ps', $sheets))
									if ($format == 2)
										$buffer .= 'PS,' . to_csv($data) . EOL;
									else
										$snetconnections .= to_old_csv($ps_keys, $data) . EOL;
								$netconnectionid++;
								$foreign = true;
							}
							if ($netnodes[$netdevnetnode]['ownership'] < 2 && $netnodes[$srcnetnode]['ownership'] == 2) {
								$invproject = strlen($netnodes[$netdevnetnode]['invproject']) ? $netnodes[$netdevnetnode]['invproject'] : '';
								$netintid = $netnodes[$netdevnetnode]['backbonenetintid'][$invproject][$netnodes[$netdevnetnode]['status']][$netlink['type']][$netlink['technology']][$netlink['speed']];
								$data = array(
									'ps_id' => $netconnectionid,
									'ps_wwid' => $netnodes[$netdevnetnode]['id'],
									'ps_woid' => $netnodes[$srcnetnode]['id'],
									'ps_intid' => $netintid,
									'ps_internetusage' => 'Tak',
									'ps_voiceusage' => 'Nie',
									'ps_otherusage' => 'Nie',
									'ps_totalspeed' => $speed,
									'ps_internetspeed' => $speed,
									'ps_invproject' => $netnodes[$srcnetnode]['invproject'],
									'ps_invstatus' => strlen($netnodes[$srcnetnode]['invproject']) ? $NETELEMENTSTATUSES[$netnodes[$srcnetnode]['status']] : '',
								);
								if (in_array('ps', $sheets))
									if ($format == 2)
										$buffer .= 'PS,' . to_csv($data) . EOL;
									else
										$snetconnections .= to_old_csv($ps_keys, $data) . EOL;
								$netconnectionid++;
								$foreign = true;
							}

							$netlinks[] = array(
								'type' => $netlink['type'],
								'speed' => $speed,
								'src' => $netdevnetnode,
								'dst' => $srcnetnode,
								'license' => $netlink['license'],
								'frequency' => $netlink['frequency'],
								'invproject' => $invproject,
								'status' => $status,
								'foreign' => $foreign,
							);
						}
				}
			}
	}

// save info about network lines
$netlineid = 1;
$snetcablelines = '';
$snetradiolines = '';
if ($netlinks)
	foreach ($netlinks as $netlink)
		if ($netnodes[$netlink['src']]['id'] != $netnodes[$netlink['dst']]['id']) {
			if ($netlink['type'] == 1) {
				$linktechnology = $netlink['technology'];
				if (!$linktechnology)
					$linktechnology = 101;
				switch ($linktechnology) {
					case 100: case 101:
						$linktransmission = 'WiFi';
						break;
					case 102: case 103: case 104:
						$linktransmission = $LINKTECHNOLOGIES[1][$linktechnology];
						break;
					default:
						$linktransmission = '';
				}
				$linkfrequency = $netlink['frequency'];
				if (empty($linkfrequency))
					$linkfrequency = '5.5';
				else
					$linkfrequency = str_replace(',', '.', (float) $linkfrequency);
				$data = array(
					'rl_id' => $netlineid,
					'rl_anodeid' => $netnodes[$netlink['src']]['id'],
					'rl_bnodeid' => $netnodes[$netlink['dst']]['id'],
					'rl_mediumtype' => empty($netlink['license'])
						? 'radiowe na częstotliwości ogólnodostępnej'
						: 'radiowe na częstotliwości wymagającej uzyskanie pozwolenia radiowego',
					'rl_licencenr' => empty($netlink['license']) ? '' : $netlink['license'],
					'rl_bandwidth' => $linkfrequency,
					'rl_transmission' => $linktransmission,
					'rl_throughput' => $netlink['speed'],
					'rl_leaseavail' => 'Nie',
					'rl_invproject' => $netlink['invproject'],
					'rl_invstatus' => strlen($netlink['invproject']) ? $NETELEMENTSTATUSES[$netlink['status']] : '',
				);
				if (in_array('rl', $sheets))
					if ($format == 2)
						$buffer .= 'LB,' . to_csv($data) . EOL;
					else
						$snetradiolines .= to_old_csv($rl_keys, $data) . EOL;
			} else {
				$data = array(
					'lp_id' => $netlineid,
					'lp_owner' => 'Własna',
					'lp_foreingerid' => '',
					'lp_anodetype' => $NETELEMENTOWNERSHIPS[$netnodes[$netlink['src']]['ownership']],
					'lp_anodeid' => $netnodes[$netlink['src']]['id'],
					'lp_bnodetype' => $NETELEMENTOWNERSHIPS[$netnodes[$netlink['dst']]['ownership']],
					'lp_bnodeid' => $netnodes[$netlink['dst']]['id'],
					'lp_tech' => $linktypes[$netlink['type']]['technologia'],
					'lp_fibertype' => $netlink['type'] == 2 ? $linktypes[$netlink['type']]['typ'] : '',
					'lp_fibertotal' => $netlink['type'] == 2 ? $linktypes[$netlink['type']]['liczba_jednostek'] : '',
					'lp_fiberused' => $netlink['type'] == 2 ? $linktypes[$netlink['type']]['liczba_jednostek'] : '',
					'lp_eu' => strlen($netlink['invproject']) ? 'Tak' : 'Nie',
					'lp_passiveavail' => 'Brak danych',
					'lp_passivetype' => '',
					'lp_fiberlease' => $netlink['type'] == 2 ? 'Nie' : '',
					'lp_fiberleasecount' => '',
					'lp_bandwidthlease' => 'Nie',
					'lp_duct' => $linktypes[$netlink['type']]['trakt'],
					'lp_length' => $netlink['type'] == 2 ? '0.1' : '',
					'lp_invproject' => $netlink['invproject'],
					'lp_invstatus' => strlen($netlink['invproject']) ? $NETELEMENTSTATUSES[$netlink['status']] : '',
				);
				if (in_array('lp', $sheets))
					if ($format == 2)
						$buffer .= 'LK,' . to_csv($data) . EOL;
					else
						$snetcablelines .= to_old_csv($lp_keys, $data) . EOL;
			}
			$netlineid++;
		}

// save info about network links
$netlinkid = 1;
$snetlinks = '';
if ($netlinks)
	foreach ($netlinks as $netlink)
		if ($netnodes[$netlink['src']]['id'] != $netnodes[$netlink['dst']]['id']) {
			$data = array(
				'pol_id' => $netlinkid,
				'pol_owner' => 'Własna',
				'pol_foreignerid' => '',
				'pol_wa' => $netnodes[$netlink['src']]['id'],
				'pol_wb' => $netnodes[$netlink['dst']]['id'],
				'pol_blayer' => $netlink['foreign'] ? 'Tak' : 'Nie',
				'pol_dlayer' => $netlink['foreign'] ? 'Nie' : 'Tak',
				'pol_alayer' => 'Nie',
				'pol_internetusage' => 'Tak',
				'pol_voiceusage' => 'Nie',
				'pol_otherusage' => 'Nie',
				'pol_totalspeed' => $netlink['speed'],
				'pol_internetspeed' => $netlink['speed'],
				'pol_invproject' => $netlink['invproject'],
				'pol_invstatus' => strlen($netlink['invproject']) ? $NETELEMENTSTATUSES[$netlink['status']] : '',
			);
			if (in_array('pol', $sheets))
				if ($format == 2)
					$buffer .= 'P,' . to_csv($data) . EOL;
				else
					$snetlinks .= to_old_csv($pol_keys, $data) . EOL;
			$netlinkid++;
		}

if ($format == 2) {
	header('Content-type: text/csv');
	header('Content-Disposition: attachment; filename="LMS_SIIS.csv"');
	header('Pragma: public');
	echo $buffer;
	die;
}

// prepare zip archive package containing all generated files
if (!extension_loaded('zip'))
	die ('<B>Zip extension not loaded! In order to use this extension you must compile PHP with zip support by using the --enable-zip configure option. </B>');

$zip = new ZipArchive();
$filename = tempnam('/tmp', 'LMS_SIIS_').'.zip';
if ($zip->open($filename, ZIPARCHIVE::CREATE)) {
	if (in_array('proj', $sheets)) $zip->addFromString('PROJ.csv', $sprojects);
	if (in_array('ob', $sheets)) $zip->addFromString('OB.csv', $sforeigners);
	if (in_array('ww', $sheets)) $zip->addFromString('WW.csv', $snetnodes);
	if (in_array('wo', $sheets)) $zip->addFromString('WO.csv', $sforeignernetnodes);
	if (in_array('int', $sheets)) $zip->addFromString('INT.csv', $snetinterfaces);
	if (in_array('sr', $sheets)) $zip->addFromString('SR.csv', $sradiosectors);
	if (in_array('ps', $sheets)) $zip->addFromString('PS.csv', $snetconnections);
	if (in_array('lp', $sheets)) $zip->addFromString('LP.csv', $snetcablelines);
	if (in_array('rl', $sheets)) $zip->addFromString('RL.csv', $snetradiolines);
	if (in_array('pol', $sheets)) $zip->addFromString('POL.csv', $snetlinks);
	if (in_array('zas', $sheets)) $zip->addFromString('ZAS.csv', $snetbuildings);
	if (in_array('us', $sheets)) $zip->addFromString('US.csv', $snetranges);
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
