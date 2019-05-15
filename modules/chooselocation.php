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

function get_loc_streets($cityid)
{
    $DB = LMSDB::getInstance();

    $borough = $DB->GetRow("SELECT lb.id, lb.name, lb.districtid, ld.stateid FROM location_boroughs lb
		JOIN location_cities lc ON lc.boroughid = lb.id
		JOIN location_districts ld ON ld.id = lb.districtid
		WHERE lb.type = 1 AND lc.id = ?", array($cityid));
    if (!empty($borough)) {
        $subcities = $DB->GetCol(
            "SELECT lc.id FROM location_boroughs lb
			JOIN location_cities lc ON lc.boroughid = lb.id
			JOIN location_districts ld ON ld.id = lb.districtid
			WHERE ld.stateid = ? AND lb.districtid = ? AND (lb.type = 8 OR lb.type = 9)",
            array($borough['stateid'], $borough['districtid'])
        );
        if (!empty($subcities)) {
            $list = $DB->GetAll("SELECT s.id, s.name AS name1, s.name2 AS name2, (CASE WHEN s.name2 IS NOT NULL THEN " . $DB->Concat('s.name', "' '", 's.name2') . " ELSE s.name END) AS name, t.name AS typename
				FROM location_streets s
				LEFT JOIN location_street_types t ON (s.typeid = t.id)
				WHERE s.cityid IN (" . implode(',', $subcities) . ")
				ORDER BY s.name");
        }
    }

    if (!isset($list)) {
        $list = $DB->GetAll("SELECT s.id, s.name AS name1, s.name2 AS name2, (CASE WHEN s.name2 IS NOT NULL THEN " . $DB->Concat('s.name', "' '", 's.name2') . " ELSE s.name END) AS name, t.name AS typename
			FROM location_streets s
			LEFT JOIN location_street_types t ON (s.typeid = t.id)
			WHERE s.cityid = ?
			ORDER BY s.name", array($cityid));
    }

    if ($list) {
        foreach ($list as &$row) {
            if ($row['typename']) {
                $row['name'] .= ', ' . $row['typename'];
            }
        }
    } else {
        $list = array();
    }

    return $list;
}

function get_loc_cities($districtid)
{
    global $DB, $BOROUGHTYPES;

    $list = $DB->GetAll('SELECT c.id, c.name, b.name AS borough, b.type AS btype
		FROM location_cities c
		JOIN location_boroughs b ON (c.boroughid = b.id)
		WHERE b.districtid = ?
		ORDER BY c.name, b.type', array($districtid));

    if ($list) {
        foreach ($list as $idx => $row) {
            $name = sprintf('%s (%s %s)', $row['name'], $BOROUGHTYPES[$row['btype']], $row['borough']);
            $list[$idx] = array('id' => $row['id'], 'name' => $name);
        }
    }

    return $list;
}

if (isset($_GET['ajax']) && isset($_GET['what'])) {
    header('Content-type: text/plain');
    $search = urldecode(trim($_GET['what']));

    if (!strlen($search)) {
        print "false;";
        die;
    }

    $list = $DB->GetAll('SELECT c.id, c.name,
			b.name AS borough, b.type AS btype,
			d.name AS district, d.id AS districtid,
			s.name AS state, s.id AS stateid
		FROM location_cities c
		JOIN location_boroughs b ON (c.boroughid = b.id)
		JOIN location_districts d ON (b.districtid = d.id)
		JOIN location_states s ON (d.stateid = s.id)
		WHERE c.name ?LIKE? ' . $DB->Escape("%$search%") . '
		ORDER BY c.name, b.type LIMIT 10');

    $result = array();
    if ($list) {
        foreach ($list as $idx => $row) {
            $name = sprintf('%s (%s%s, %s)', $row['name'], $row['btype'] < 4 ?
            trans('<!borough_abbr>') : '', $row['borough'], trans('<!district_abbr>') . $row['district']);
            $name_class = '';
            $description = $description_class = '';
            $action = sprintf("javascript: search_update(%d,%d,%d)", $row['id'], $row['districtid'], $row['stateid']);

            $result[$row['id']] = compact('name', 'name_class', 'description', 'description_class', 'action');
        }
    }

    header('Content-Type: application/json');
    echo json_encode(array_values($result));
    die;
}

function select_location($what, $id)
{
    global $DB;

    $JSResponse = new xajaxResponse();

    if ($what == 'state') {
        $stateid = $id;
    } else if ($what == 'district') {
        $districtid = $id;
    } else if ($what == 'city') {
        $cityid = $id;
    } else if (!$what && preg_match('/^([0-9]+):([0-9]+):([0-9]+)$/', $id, $m)) {
        $cityid = $m[1];
        $districtid = $m[2];
        $stateid = $m[3];
    }

    if ($stateid) {
        $list = $DB->GetAll('SELECT id, name
			FROM location_districts WHERE stateid = ?
			ORDER BY name', array($stateid));

        $JSResponse->call('update_selection', 'district', $list ? $list : array(), !$what ? $districtid : 0);
    }

    if ($districtid) {
        $list = get_loc_cities($districtid);
        $JSResponse->call('update_selection', 'city', $list ? $list : array(), !$what ? $cityid : 0);
    }

    if ($cityid) {
        $list = get_loc_streets($cityid);
        $JSResponse->call('update_selection', 'street', $list ? $list : array());
    }

    return $JSResponse;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction('select_location');
$SMARTY->assign('xajax', $LMS->RunXajax());

$layout['pagetitle'] = trans('Select location');

$streetid = isset($_GET['street']) ? intval($_GET['street']) : 0;
$cityid = isset($_GET['city']) ? intval($_GET['city']) : 0;

$states = $DB->GetAll('SELECT id, name, ident
	FROM location_states ORDER BY name');

if ($streetid) {
    $data = $DB->GetRow('SELECT s.id AS streetid, s.cityid, b.districtid, d.stateid
		FROM location_streets s
		JOIN location_cities c ON (s.cityid = c.id)
		JOIN location_boroughs b ON (c.boroughid = b.id)
		JOIN location_districts d ON (b.districtid = d.id)
		WHERE s.id = ?', array($streetid));
    if ($data['cityid'] != $cityid) {
        $data['cityid'] = $cityid;
    }
} else if ($cityid) {
    $data = $DB->GetRow('SELECT c.id AS cityid, b.districtid, d.stateid
		FROM location_cities c
		JOIN location_boroughs b ON (c.boroughid = b.id)
		JOIN location_districts d ON (b.districtid = d.id)
		WHERE c.id = ?', array($cityid));
} else if (count($states)) {
    $data['stateid'] = $states[key($states)]['id'];
}

if (!empty($data['stateid'])) {
    $districts = $DB->GetAll('SELECT id, ident, name
		FROM location_districts WHERE stateid = ?', array($data['stateid']));
    $SMARTY->assign('districts', $districts);
}

if (!empty($data['districtid'])) {
    $cities = get_loc_cities($data['districtid']);
    $SMARTY->assign('cities', $cities);
}

if (!empty($data['cityid'])) {
    $streets = get_loc_streets($data['cityid']);
    $SMARTY->assign('streets', $streets);
}

$data['varname']   = $_GET['name'];
$data['formname']  = $_GET['form'];
$data['boxid']     = ( !empty($_GET['boxid'])) ? $_GET['boxid'] : null;
$data['countries'] = $DB->GetAll('SELECT id, name FROM countries;');

$SMARTY->assign('data', $data);
$SMARTY->assign('states', $states);

if (empty($_GET['boxid'])) {
    $SMARTY->display('file:choose/chooselocation.html');
} else {
    $SMARTY->display('file:choose/chooselocation_ext.html');
}
