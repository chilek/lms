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

    if (isset($filter['without-ranges']) && is_numeric($filter['without-ranges'])) {
        switch (intval($filter['without-ranges'])) {
            case 1:
                $where[] = 'r.id IS NULL';
                break;
            case 2:
                $where[] = 'r.id IS NOT NULL';
                break;
        }
    }
    if (isset($filter['existing']) && is_numeric($filter['existing'])) {
        switch (intval($filter['existing'])) {
            case 1:
                $where[] = 'na.city_id IS NOT NULL';
                break;
            case 2:
                $where[] = 'na.city_id IS NULL';
                break;
        }
    }
    if (isset($filter['linktype']) && is_numeric($filter['linktype'])) {
        $where[] = 'r.linktype = ' . intval($filter['linktype']);
    }
    if (isset($filter['linktechnology']) && is_numeric($filter['linktechnology'])) {
        $where[] = 'r.linktechnology = ' . intval($filter['linktechnology']);
    }
    if (isset($filter['downlink']) && is_numeric($filter['downlink'])) {
        $where[] = 'r.downlink = ' . intval($filter['downlink']);
    }
    if (isset($filter['uplink']) && is_numeric($filter['uplink'])) {
        $where[] = 'r.uplink = ' . intval($filter['uplink']);
    }
    if (isset($filter['type']) && is_numeric($filter['type'])) {
        $where[] = 'r.type = ' . intval($filter['type']);
    }
    if (isset($filter['services']) && is_numeric($filter['services']) && !empty($filter['services'])) {
        $where[] = 'r.services = ' . intval($filter['services']);
    }

    if ($count) {
        return $DB->GetRow(
            'SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN r.id IS NULL THEN 0 ELSE 1 END) AS ranges,
                SUM(CASE WHEN na.city_id IS NULL THEN 0 ELSE 1 END) AS existing
            FROM location_buildings b
            LEFT JOIN location_streets lst ON lst.id = b.street_id
            JOIN location_cities lc ON lc.id = b.city_id
            JOIN location_boroughs lb ON lb.id = lc.boroughid
            JOIN location_districts ld ON ld.id = lb.districtid
            JOIN location_states ls ON ls.id = ld.stateid
            LEFT JOIN netranges r ON r.buildingid = b.id
            LEFT JOIN (
                SELECT a.city_id, a.street_id, UPPER(a.house) AS house, COUNT(*) AS nodecount FROM nodes n
                JOIN vaddresses a ON a.id = n.address_id
                WHERE a.city_id IS NOT NULL
                GROUP BY a.city_id, a.street_id, UPPER(a.house)
            ) na ON b.city_id = na.city_id AND (b.street_id IS NULL OR b.street_id = na.street_id) AND na.house = UPPER(b.building_num)'
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
                ls.name AS state,
                r.id AS netrangeid,
                r.linktype,
                r.linktechnology,
                r.downlink,
                r.uplink,
                r.type,
                r.services,
                (CASE WHEN na.city_id IS NULL THEN 0 ELSE 1 END) AS existing
            FROM location_buildings b
            LEFT JOIN location_streets lst ON lst.id = b.street_id
            LEFT JOIN location_street_types t ON t.id = lst.typeid
            JOIN location_cities lc ON lc.id = b.city_id
            JOIN location_boroughs lb ON lb.id = lc.boroughid
            JOIN location_districts ld ON ld.id = lb.districtid
            JOIN location_states ls ON ls.id = ld.stateid
            LEFT JOIN netranges r ON r.buildingid = b.id
            LEFT JOIN (
                SELECT a.city_id, a.street_id, UPPER(a.house) AS house, COUNT(*) AS nodecount FROM nodes n
                JOIN vaddresses a ON a.id = n.address_id
                WHERE a.city_id IS NOT NULL
                GROUP BY a.city_id, a.street_id, UPPER(a.house)
            ) na ON b.city_id = na.city_id AND (b.street_id IS NULL OR b.street_id = na.street_id) AND na.house = UPPER(b.building_num)'
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

$oldrange = $SESSION->get('netranges_update_range');
$oldrange = isset($oldrange) ? $oldrange : array();
$range = isset($_POST['range']) ? $_POST['range'] : array();

if (isset($range['linktype'])) {
    $range['linktype'] = strlen($range['linktype']) ? intval($range['linktype']) : '';
} else {
    $range['linktype'] = isset($oldrange['linktype']) ? $oldrange['linktype'] : '';
}

if (isset($range['linktechnology'])) {
    $range['linktechnology'] = strlen($range['linktechnology']) ? intval($range['linktechnology']) : '';
} else {
    $range['linktechnology'] = isset($oldrange['linktechnology']) ? $oldrange['linktechnology'] : '';
}

if (isset($range['downlink'])) {
    $range['downlink'] = strlen($range['downlink']) ? intval($range['downlink']) : '';
} else {
    $range['downlink'] = isset($oldrange['downlink']) ? $oldrange['downlink'] : '';
}

if (isset($range['uplink'])) {
    $range['uplink'] = strlen($range['uplink']) ? intval($range['uplink']) : '';
} else {
    $range['uplink'] = isset($oldrange['uplink']) ? $oldrange['uplink'] : '';
}

if (isset($range['type'])) {
    $range['type'] = strlen($range['type']) ? intval($range['type']) : '';
} else {
    $range['type'] = isset($oldrange['type']) ? $oldrange['type'] : '';
}

$services = 0;
if (isset($range['services']['1'])) {
    if (!empty($range['services']['1'])) {
        $services |= 1;
    }
} else {
    $services |= isset($oldrange['services']) && ($oldrange['services'] & 1) ? 1 : 0;
}
if (isset($range['services']['2'])) {
    if (!empty($range['services']['2'])) {
        $services |= 2;
    }
} else {
    $services |= isset($oldrange['services']) && ($oldrange['services'] & 2) ? 2 : 0;
}
$range['services'] = $services;

$SESSION->save('netranges_update_range', $range);

if (isset($_POST['range'])) {
    if (isset($range['buildings'])) {
        $buildings = Utils::filterIntegers($range['buildings']);
        if (!empty($buildings)) {
            $DB->BeginTrans();

            if (isset($_GET['delete'])) {
                $DB->Execute('DELETE FROM netranges WHERE buildingid IN ?', array($buildings));
            } else {
                $args = $range;
                unset($args['buildings']);
                foreach ($buildings as $buildingid) {
                    $args['buildingid'] = $buildingid;
                    if ($DB->GetOne('SELECT 1 FROM netranges WHERE buildingid = ?', array($buildingid))) {
                        $DB->Execute(
                            'UPDATE netranges
                            SET linktype = ?,
                                linktechnology = ?,
                                downlink = ?,
                                uplink = ?,
                                type = ?,
                                services = ?
                            WHERE buildingid = ?',
                            array_values($args)
                        );
                    } else {
                        $DB->Execute(
                            'INSERT INTO netranges
                            (linktype, linktechnology, downlink, uplink, type, services, buildingid)
                            VALUES (?, ?, ?, ?, ?, ?, ?)',
                            array_values($args)
                        );
                    }
                }
            }

            $DB->CommitTrans();
        }
    }
}

$layout['pagetitle'] = trans('Network Ranges');

$oldfilter = $SESSION->get('netranges_filter');
$oldfilter = isset($oldfilter) ? $oldfilter : array();
$filter = isset($_POST['filter']) ? $_POST['filter'] : array();

if (isset($filter['without-ranges'])) {
    $filter['without-ranges'] = strlen($filter['without-ranges']) ? intval($filter['without-ranges']) : '';
} else {
    $filter['without-ranges'] = isset($oldfilter['without-ranges']) ? $oldfilter['without-ranges'] : '';
}

if (isset($filter['existing'])) {
    $filter['existing'] = strlen($filter['existing']) ? intval($filter['existing']) : '';
} else {
    $filter['existing'] = isset($oldfilter['existing']) ? $oldfilter['existing'] : '';
}

if (isset($filter['stateid'])) {
    $filter['stateid'] = strlen($filter['stateid']) ? intval($filter['stateid']) : '';
} else {
    $filter['stateid'] = isset($oldfilter['stateid']) ? $oldfilter['stateid'] : '';
}

if (isset($filter['districtid'])) {
    $filter['districtid'] = strlen($filter['districtid']) ? intval($filter['districtid']) : '';
} else {
    $filter['districtid'] = isset($oldfilter['districtid']) ? $oldfilter['districtid'] : '';
}
if (empty($filter['stateid'])) {
    $filter['districtid'] = '';
}

if (isset($filter['boroughid'])) {
    $filter['boroughid'] = strlen($filter['boroughid']) ? intval($filter['boroughid']) : '';
} else {
    $filter['boroughid'] = isset($oldfilter['boroughid']) ? $oldfilter['boroughid'] : '';
}
if (empty($filter['districtid'])) {
    $filter['boroughid'] = '';
}

if (isset($filter['cityid'])) {
    $filter['cityid'] = strlen($filter['cityid']) ? intval($filter['cityid']) : '';
} else {
    $filter['cityid'] = isset($oldfilter['cityid']) ? $oldfilter['cityid'] : '';
}
if (empty($filter['boroughid'])) {
    $filter['cityid'] = '';
}

if (isset($filter['streetid'])) {
    $filter['streetid'] = strlen($filter['streetid']) ? intval($filter['streetid']) : '';
} else {
    $filter['streetid'] = isset($oldfilter['streetid']) ? $oldfilter['streetid'] : '';
}
if (empty($filter['cityid'])) {
    $filter['streetid'] = '';
}

$streets = empty($filter['cityid']) ? array() : getStreets($filter['cityid']);
if (isset($filter['numberparity'])) {
    $filter['numberparity'] = $filter['numberparity'];
} else {
    $filter['numberparity'] = isset($oldfilter['numberparity']) ? $oldfilter['numberparity'] : '';
}
if (empty($filter['cityid']) || !empty($streets) && empty($filter['streetid'])) {
    $filter['numberparity'] = '';
}

if (isset($filter['linktype'])) {
    $filter['linktype'] = strlen($filter['linktype']) ? intval($filter['linktype']) : '';
} else {
    $filter['linktype'] = isset($oldfilter['linktype']) ? $oldfilter['linktype'] : '';
}

if (isset($filter['linktechnology'])) {
    $filter['linktechnology'] = strlen($filter['linktechnology']) ? intval($filter['linktechnology']) : '';
} else {
    $filter['linktechnology'] = isset($oldfilter['linktechnology']) ? $oldfilter['linktechnology'] : '';
}

if (isset($filter['downlink'])) {
    $filter['downlink'] = strlen($filter['downlink']) ? intval($filter['downlink']) : '';
} else {
    $filter['downlink'] = isset($oldfilter['downlink']) ? $oldfilter['downlink'] : '';
}

if (isset($filter['uplink'])) {
    $filter['uplink'] = strlen($filter['uplink']) ? intval($filter['uplink']) : '';
} else {
    $filter['uplink'] = isset($oldfilter['uplink']) ? $oldfilter['uplink'] : '';
}

if (isset($filter['type'])) {
    $filter['type'] = strlen($filter['type']) ? intval($filter['type']) : '';
} else {
    $filter['type'] = isset($oldfilter['type']) ? $oldfilter['type'] : '';
}

$services = 0;
if (isset($filter['services']['1'])) {
    if (!empty($filter['services']['1'])) {
        $services |= 1;
    }
} else {
    $services |= isset($oldfilter['services']) && ($oldfilter['services'] & 1) ? 1 : 0;
}
if (isset($filter['services']['2'])) {
    if (!empty($filter['services']['2'])) {
        $services |= 2;
    }
} else {
    $services |= isset($oldfilter['services']) && ($oldfilter['services'] & 2) ? 2 : 0;
}
$filter['services'] = $services;

$SESSION->save('netranges_filter', $filter);

$filter['count'] = true;

$summary = !empty($filter['stateid']) && !empty($filter['districtid']) ? getBuildings($filter) : array();
$total = isset($summary['total']) ? intval($summary['total']) : 0;
$ranges = isset($summary['ranges']) ? intval($summary['ranges']) : 0;
$existing = isset($summary['existing']) ? intval($summary['existing']) : 0;

if (isset($_GET['page'])) {
    $page = intval($_GET['page']);
} elseif ($SESSION->is_set('netranges_page')) {
    $SESSION->restore('netranges_page', $page);
    $page = intval($page);
} else {
    $page = 1;
}
if (empty($page)) {
    $page = 1;
}
$SESSION->save('netranges_page', $page);

$limit = intval(ConfigHelper::getConfig('phpui.netrangelist_pagelimit', '100'));

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
$SMARTY->assign('cities', empty($filter['boroughid']) ? array() : getCities($filter['boroughid']));
$SMARTY->assign('streets', $streets);
$SMARTY->assign('pagination', $pagination);

$SMARTY->assign(array(
    'filter' => $filter,
    'linktechnologies' => $SIDUSIS_LINKTECHNOLOGIES,
    'linkspeeds' => $linkspeeds,
    'range'=> $range,
    'total' => $total,
    'ranges' => $ranges,
    'existing' => $existing,
));

$SMARTY->display('net/netranges.html');