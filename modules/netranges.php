<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

$linktechnologies = array(
    LINKTYPE_WIRE => array(
        1 => 'ADSL',
        2 => 'ADSL2',
        3 => 'ADSL2+',
        4 => 'VDSL',
        5 => 'VDSL2',
        13 => 'VDSL2(vectoring)',
        14 => 'G.Fast',
        50 => '(EURO)DOCSIS 1.x',
        51 => '(EURO)DOCSIS 2.x',
        52 => '(EURO)DOCSIS 3.x',
        6 => '10 Mb/s Ethernet',
        7 => '100 Mb/s Fast Ethernet',
        8 => '1 Gigabit Ethernet',
        15 => '2,5 Gigabit Ethernet',
        16 => '5 Gigabit Ethernet',
        9 => '10 Gigabit Ethernet',
        11 => 'SDH/PDH',
        17 => 'MoCA',
        18 => 'EoC',
    ),
    LINKTYPE_WIRELESS => array(
        112 => 'LTE',
        117 => 'LTE-A',
        118 => 'LTE-Pro',
        119 => 'NR SA',
        120 => 'NR NSA',
    ),
    LINKTYPE_FIBER => array(
        250 => '(EURO)DOCSIS 1.x',
        251 => '(EURO)DOCSIS 2.x',
        252 => '(EURO)DOCSIS 3.x',
        203 => '10 Mb/s Ethernet',
        204 => '100 Mb/s Fast Ethernet',
        205 => '1 Gigabit Ethernet',
        213 => '2,5 Gigabit Ethernet',
        214 => '5 Gigabit Ethernet',
        206 => '10 Gigabit Ethernet',
        215 => '25 Gigabit Ethernet',
        207 => '100 Gigabit Ethernet',
        217 => 'CWDM',
        218 => 'DWDM',
        212 => 'SDH/PDH',
        208 => 'EPON',
        216 => '10G-EPON',
        209 => 'GPON',
        219 => 'NGPON1 (XGPON)',
        220 => 'NGPON2 (XGPON)',
        221 => 'XGSPON',
        222 => '25G PON',
        223 => 'MoCA',
        224 => 'EoC',
    ),
);

$linkspeeds = array(
    2 => '2 Mb/s',
    10 => '10 Mb/s',
    20 => '20 Mb/s',
    30 => '30 Mb/s',
    40 => '40 Mb/s',
    50 => '50 Mb/s',
    60 => '60 Mb/s',
    70 => '70 Mb/s',
    80 => '80 Mb/s',
    90 => '90 Mb/s',
    100 => '100 Mb/s',
    200 => '200 Mb/s',
    300 => '300 Mb/s',
    400 => '400 Mb/s',
    500 => '500 Mb/s',
    600 => '600 Mb/s',
    700 => '700 Mb/s',
    800 => '800 Mb/s',
    900 => '900 Mb/s',
    1000 => '1000 Mb/s',
    2000 => '2000 Mb/s',
    3000 => '3000 Mb/s',
    4000 => '4000 Mb/s',
    5000 => '5000 Mb/s',
    6000 => '6000 Mb/s',
    7000 => '7000 Mb/s',
    8000 => '8000 Mb/s',
    9000 => '9000 Mb/s',
    10000 => '10000 Mb/s',
);

function getTerritoryUnits()
{
    global $BOROUGHTYPES;

    $DB = LMSDB::getInstance();

    $territory_units = $DB->GetAll(
        'SELECT
            lb.id AS boroughid,
            lb.type AS boroughtype,
            lb.name AS borough,
            ld.id AS districtid,
            ld.name AS district,
            ls.id AS stateid,
            ls.name AS state
        FROM location_boroughs lb
        JOIN location_districts ld ON ld.id = lb.districtid
        JOIN location_states ls ON ls.id = ld.stateid
        ORDER BY LOWER(ls.name), LOWER(ld.name), LOWER(lb.name)'
    );

    $boroughs = array();

    if (!empty($territory_units)) {
        foreach ($territory_units as $territory_unit) {
            $stateid = $territory_unit['stateid'];
            $districtid = $territory_unit['districtid'];
            $boroughid = $territory_unit['boroughid'];

            if (!isset($boroughs['id-' . $stateid])) {
                $boroughs['id-' . $stateid] = array(
                    'id' => $stateid,
                    'name' => $territory_unit['state'],
                    'label' => $territory_unit['state'],
                    'districts' => array(),
                );
            }
            if (!isset($boroughs['id-' . $stateid]['districts']['id-' . $districtid])) {
                $boroughs['id-' . $stateid]['districts']['id-' . $districtid] = array(
                    'id' => $districtid,
                    'name' => $territory_unit['district'],
                    'label' => $territory_unit['district'],
                    'boroughs' => array(),
                );
            }

            $boroughs['id-' . $stateid]['districts']['id-' . $districtid]['boroughs']['id-' . $boroughid] = array(
                'id' => $boroughid,
                'name' => $territory_unit['borough'],
                'label' => sprintf("%s (%s)", $territory_unit['borough'], $BOROUGHTYPES[$territory_unit['boroughtype']]),
                'type' => $territory_unit['boroughtype'],
            );
        }
    }

    return $boroughs;
}

function getCities($boroughid)
{
    $DB = LMSDB::getInstance();

    $cities = $DB->GetAll(
        'SELECT
            lc.id,
            lc.name AS label
        FROM location_cities lc
        WHERE EXISTS (
            SELECT 1 FROM location_buildings b
            JOIN location_cities lc2 ON lc2.id = b.city_id
            WHERE b.city_id = lc.id
                AND lc2.boroughid = ?
        )
        ORDER BY lc.name',
        array($boroughid)
    );

    if (empty($cities)) {
        $cities = array();
    }

    return $cities;
}

function getStreets($cityid)
{
    $DB = LMSDB::getInstance();

    if (!isset($streets)) {
        $streets = $DB->GetAll(
            'SELECT
                lst.id,
                lst.name AS name1,
                lst.name2 AS name2, 
                (CASE WHEN lst.name2 IS NOT NULL THEN ' . $DB->Concat('lst.name', "' '", 'lst.name2') . ' ELSE lst.name END) AS label,
                (CASE WHEN lst.name2 IS NOT NULL THEN ' . $DB->Concat('lst.name2', "' '", 'lst.name') . ' ELSE lst.name END) AS rlabel,
                t.name AS typename
            FROM location_streets lst
            LEFT JOIN location_street_types t ON t.id = lst.typeid
            WHERE EXISTS (
                SELECT 1 FROM location_buildings b
                WHERE b.street_id = lst.id
                    AND b.city_id = ?
            )
            ORDER BY lst.name',
            array($cityid)
        );
    }

    if (empty($streets)) {
        $streets = array();
    }

    return $streets;
}

function getBuildings(array $filter)
{
    $DB = LMSDB::getInstance();

    $count = isset($filter['count']) && !empty($filter['count']);

    $where = array();

    if (isset($filter['streetid']) && is_numeric($filter['streetid'])) {
        $where[] = 'b.street_id = ' . intval($filter['streetid']);
    } elseif (isset($filter['cityid']) && is_numeric($filter['cityid'])) {
        $where[] = 'b.city_id = ' . intval($filter['cityid']);
    } elseif (isset($filter['boroughid']) && is_numeric($filter['boroughid'])) {
        $where[] = 'lc.boroughid = ' . intval($filter['boroughid']);
    }
    if (isset($filter['districtid']) && is_numeric($filter['districtid'])) {
        $where[] = 'lb.districtid = ' . intval($filter['districtid']);
    }
    if (isset($filter['stateid']) && is_numeric($filter['stateid'])) {
        $where[] = 'ld.stateid = ' . intval($filter['stateid']);
    }
    if (!empty($filter['numberparity'])) {
        $where[] = $DB->RegExp('b.building_num', $filter['numberparity'] == 'odd' ? '[13579][[:alpha:]]*$' : '[02468][[:alpha:]]*$');
    }

    if ($count) {
        return $DB->GetOne(
            'SELECT COUNT(*)
            FROM location_buildings b
            LEFT JOIN location_streets lst ON lst.id = b.street_id
            JOIN location_cities lc ON lc.id = b.city_id
            JOIN location_boroughs lb ON lb.id = lc.boroughid
            JOIN location_districts ld ON ld.id = lb.districtid
            JOIN location_states ls ON ls.id = ld.stateid'
            . (!empty($where) ? ' WHERE ' . implode(' AND ', $where) : '')
        );
    } else {
        $buildings = $DB->GetAll(
            'SELECT b.*,
                lst.name AS street1,
                lst.name2 AS street2,
                (CASE WHEN lst.name2 IS NOT NULL THEN ' . $DB->Concat('lst.name', "' '", 'lst.name2') . ' ELSE lst.name END) AS street,
                (CASE WHEN lst.name2 IS NOT NULL THEN ' . $DB->Concat('lst.name2', "' '", 'lst.name') . ' ELSE lst.name END) AS rstreet,
                t.name AS streettype,
                lc.id AS cityid,
                lc.name AS city,
                lb.id AS boroughid,
                lb.type AS boroughtype,
                lb.name AS borough,
                ld.id AS districtid,
                ld.name AS district,
                ls.id AS stateid,
                ls.name AS state
            FROM location_buildings b
            LEFT JOIN location_streets lst ON lst.id = b.street_id
            LEFT JOIN location_street_types t ON t.id = lst.typeid
            JOIN location_cities lc ON lc.id = b.city_id
            JOIN location_boroughs lb ON lb.id = lc.boroughid
            JOIN location_districts ld ON ld.id = lb.districtid
            JOIN location_states ls ON ls.id = ld.stateid'
            . (!empty($where) ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY ls.name, ld.name, lb.name, lc.name, lst.name, b.building_num'
            . (isset($filter['limit']) && is_numeric($filter['limit']) ? ' LIMIT ' . intval($filter['limit']) : '')
            . (isset($filter['offset']) && is_numeric($filter['offset']) ? ' OFFSET ' . intval($filter['offset']) : '')
        );
    }
    if (empty($buildings)) {
        $buildings = array();
    }
    return $buildings;
}

if (isset($_GET['boroughid'])) {
    if (is_numeric($_GET['boroughid'])) {
        $cities = getCities($_GET['boroughid']);
    } else {
        $cities = array();
    }
    die(json_encode($cities));
} elseif (isset($_GET['cityid'])) {
    if (is_numeric($_GET['cityid'])) {
        $streets = getStreets($_GET['cityid']);
    } else {
        $streets = array();
    }
    die(json_encode($streets));
}

$layout['pagetitle'] = trans('Network Ranges');

if (isset($_POST['location_stateid'])) {
    $location_stateid = strlen($_POST['location_stateid']) ? intval($_POST['location_stateid']) : '';
} else {
    $location_stateid = $SESSION->get('netranges_stateid');
}
$SESSION->save('netranges_stateid', $location_stateid);

if (isset($_POST['location_districtid'])) {
    $location_districtid = strlen($_POST['location_districtid']) ? intval($_POST['location_districtid']) : '';
} else {
    $location_districtid = $SESSION->get('netranges_districtid');
}
if (empty($location_stateid)) {
    $location_districtid = '';
}
$SESSION->save('netranges_districtid', $location_districtid);

if (isset($_POST['location_boroughid'])) {
    $location_boroughid = strlen($_POST['location_boroughid']) ? intval($_POST['location_boroughid']) : '';
} else {
    $location_boroughid = $SESSION->get('netranges_boroughid');
}
if (empty($location_districtid)) {
    $location_boroughid = '';
}
$SESSION->save('netranges_boroughid', $location_boroughid);

if (isset($_POST['location_cityid'])) {
    $location_cityid = strlen($_POST['location_cityid']) ? intval($_POST['location_cityid']) : '';
} else {
    $location_cityid = $SESSION->get('netranges_cityid');
}
if (empty($location_boroughid)) {
    $location_cityid = '';
}
$SESSION->save('netranges_cityid', $location_cityid);

if (isset($_POST['location_streetid'])) {
    $location_streetid = strlen($_POST['location_streetid']) ? intval($_POST['location_streetid']) : '';
} else {
    $location_streetid = $SESSION->get('netranges_streetid');
}
if (empty($location_cityid)) {
    $location_streetid = '';
}
$SESSION->save('netranges_streetid', $location_streetid);

$streets = empty($location_cityid) ? array() : getStreets($location_cityid);
if (isset($_POST['location_number_parity'])) {
    $location_number_parity = $_POST['location_number_parity'];
} else {
    $location_number_parity = $SESSION->get('netranges_number_parity');
}
if (empty($location_cityid) || !empty($streets) && empty($location_streetid)) {
    $location_number_parity = '';
}
$SESSION->save('netranges_number_parity', $location_number_parity);

$filter = array(
    'stateid' => $location_stateid,
    'districtid' => $location_districtid,
    'boroughid' => $location_boroughid,
    'cityid' => $location_cityid,
    'streetid' => $location_streetid,
    'numberparity' => $location_number_parity,
    'count' => true,
);

$total = !empty($filter['stateid']) && !empty($filter['districtid']) ? intval(getBuildings($filter)) : 0;
if (isset($_GET['page'])) {
    $page = intval($_GET['page']);
} elseif ($SESSION->is_set('netranges_page')) {
    $page = intval($SESSION->restore('netranges_page'));
} else {
    $page = 1;
}
if (empty($page)) {
    $page = 1;
}
$limit = intval(ConfigHelper::getConfig('phpui.netranges_pagelimit', '100'));

$pagination = LMSPaginationFactory::getPagination($page, $total, $limit, ConfigHelper::checkConfig('phpui.short_pagescroller'));

if (!empty($total)) {
    $filter['offset'] = ($page - 1) * $limit;
    $filter['limit'] = $limit;
    if ($total && $total < $filter['offset']) {
        $filter['page'] = 1;
        $filter['offset'] = 0;
    }
    $filter['count'] = false;
    $buildings = getBuildings($filter);
} else {
    $buildings = array();
}

$SMARTY->assign('buildings', $buildings);

$SMARTY->assign('boroughs', getTerritoryUnits());
$SMARTY->assign('cities', empty($location_boroughid) ? array() : getCities($location_boroughid));
$SMARTY->assign('streets', $streets);
$SMARTY->assign('pagination', $pagination);
$SMARTY->assign(compact('location_stateid', 'location_districtid', 'location_boroughid', 'location_cityid', 'location_streetid', 'location_number_parity'));

$oldrange = $SESSION->get('netranges_update_range');
if (!isset($oldrange)) {
    $oldrange = array();
}
$range = array();
if (isset($_POST['range']['type'])) {
    $range['type'] = strlen($_POST['range']['type']) ? intval($_POST['range']['type']) : '';
} else {
    $range['type'] = isset($oldrange['type']) ? $oldrange['type'] : '';
}

if (isset($_POST['range']['technology'])) {
    $range['technology'] = strlen($_POST['range']['technology']) ? intval($_POST['range']['technology']) : '';
} else {
    $range['technology'] = isset($oldrange['technology']) ? $oldrange['technology'] : '';
}

if (isset($_POST['range']['downlink'])) {
    $range['downlink'] = strlen($_POST['range']['downlink']) ? intval($_POST['range']['downlink']) : '';
} else {
    $range['downlink'] = isset($oldrange['downlink']) ? $oldrange['downlink'] : '';
}

if (isset($_POST['range']['uplink'])) {
    $range['uplink'] = strlen($_POST['range']['uplink']) ? intval($_POST['range']['uplink']) : '';
} else {
    $range['uplink'] = isset($oldrange['uplink']) ? $oldrange['uplink'] : '';
}

if (isset($_POST['range']['rangetype'])) {
    $range['rangetype'] = strlen($_POST['range']['rangetype']) ? intval($_POST['range']['rangetype']) : '';
} else {
    $range['rangetype'] = isset($oldrange['rangetype']) ? $oldrange['rangetype'] : '';
}

$range['service'] = 0;
if (isset($_POST['range']['service']['1'])) {
    if (!empty($_POST['range']['service']['1'])) {
        $range['service'] |= 1;
    }
} else {
    $range['service'] |= isset($oldrange['service']) && ($oldrange['service'] & 1) ? 1 : 0;
}
if (isset($_POST['range']['service']['2'])) {
    if (!empty($_POST['range']['service']['2'])) {
        $range['service'] |= 2;
    }
} else {
    $range['service'] |= isset($oldrange['service']) && ($oldrange['service'] & 2) ? 2 : 0;
}
$SESSION->save('netranges_update_range', $range);

$SMARTY->assign(array(
    'linktechnologies' => $linktechnologies,
    'linkspeeds' => $linkspeeds,
    'range'=> $range,
));

$SMARTY->display('net/netranges.html');
