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

if (isset($_GET['ajax']) && (isset($_POST['what']) || isset($_GET['what']))) {
    header('Content-type: application/json');
    if (isset($_GET['what']) && isset($_GET['mode'])) {
        $what = trim($_GET['what']);
        $mode = trim($_GET['mode']);
        if (!strlen($what)) {
            die;
        }

        $list = $DB->GetAll('SELECT c.id, c.name,
                ct.name AS citytype,
                b.name AS borough, b.type AS btype,
                d.name AS district, d.id AS districtid,
                s.name AS state, s.id AS stateid
            FROM location_cities c
            LEFT JOIN location_city_types ct ON ct.id = c.type
            JOIN location_boroughs b ON (c.boroughid = b.id)
            JOIN location_districts d ON (b.districtid = d.id)
            JOIN location_states s ON (d.stateid = s.id)
            WHERE c.name ?LIKE? ' . $DB->Escape("%$what%") . '
            ORDER BY c.name, b.type LIMIT '
            . intval(ConfigHelper::getConfig(
                'phpui.location_autosuggest_max_length',
                intval(ConfigHelper::getConfig('phpui.autosuggest_max_length', 40))
            )));

        $result = array();
        if ($list) {
            foreach ($list as $idx => $row) {
                $name_alternative = sprintf(
                    '%s (%s)<br>%s, %s%s, %s',
                    preg_replace('/(' . $what . ')/i', '<strong>$1</strong>', $row['name']),
                    empty($row['citytype']) ? '-' : $row['citytype'],
                    trans('<!state_abbr>') . ' ' . mb_strtoupper($row['state']),
                    $row['btype'] < 4 ? trans('<!borough_abbr>') . ' ' : '',
                    $row['borough'],
                    trans('<!district_abbr>') . ' ' . $row['district']
                );

                $name = sprintf(
                    '%s (%s)',
                    $row['name'],
                    empty($row['citytype']) ? '-' : $row['citytype']
                );

                $name_class = '';
                $description = $description_class = '';
                $action = array(
                    'cityid' => $row['id'],
                    'districtid' => $row['districtid'],
                    'stateid' => $row['stateid'],
                );

                $result[$row['id']] = compact('name', 'name_alternative', 'name_class', 'description', 'description_class', 'action');
            }
        }
    } elseif (isset($_GET['id']) && isset($_GET['what'])) {
        $id = trim($_GET['id']);
        $what = trim($_GET['what']);
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

        header('Content-Type: application/json');

        $result = array();
        $index = 0;

        if ($stateid) {
            $list = $DB->GetAll('SELECT id, name
                FROM location_districts WHERE stateid = ?
                ORDER BY name', array($stateid));
            $result[] = array(
                'type' => 'district',
                'data' => $list ? $list : array(),
                'selected' => !$what ? $districtid : 0,
            );
        }

        if ($districtid) {
            $list = get_loc_cities($districtid);
            $result[] = array(
                'type' => 'city',
                'data' => $list ? $list : array(),
                'selected' => !$what ? $cityid : 0,
            );
        }

        if ($cityid) {
            $list = get_loc_streets($cityid);
            $result[] = array(
                'type' => 'street',
                'data' => $list ? $list : array(),
                'selected' => 0,
            );
        }
    }

    die(json_encode(array_values($result)));
}

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

$SMARTY->display('file:choose/chooselocation.html');
