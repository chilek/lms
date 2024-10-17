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

$foreign_entities = array();
if (preg_match_all('/(?<id>[[:alnum:]]+)(?:\((?<name>[^\)]+)\))?(?:\s|[\s]*[,;][\s]*|$)/', ConfigHelper::getConfig('uke.sidusis_foreign_entities', '', true), $m)) {
    foreach ($m['id'] as $idx => $id) {
        $foreign_entities[$id] = strlen($m['name'][$idx]) ? $m['name'][$idx] : null;
    }
}

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
    static $nodes = null;

    $DB = LMSDB::getInstance();

    $count = !empty($filter['count']);

    $where = $where2 = array();

    if (isset($filter['streetid']) && is_numeric($filter['streetid'])) {
        $street_ident = $DB->GetOne('SELECT ident FROM location_streets WHERE id = ?', array($filter['streetid']));
        $where[] = 'lst.ident = ' . $DB->Escape($street_ident);
    }
    if (isset($filter['cityid']) && is_numeric($filter['cityid'])) {
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
        $existing = intval($filter['existing']);
    } else {
        $existing = 0;
    }
    if (isset($filter['project']) && is_numeric($filter['project'])) {
        if ($filter['project'] == -1) {
            $where[] = 'r.invprojectid IS NOT NULL';
        } elseif ($filter['project'] == -2) {
            $where[] = 'r.invprojectid IS NULL';
        } else {
            $where[] = 'r.invprojectid = ' . $filter['project'];
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
    if (is_numeric($filter['services']) && !empty($filter['services'])) {
        $where[] = 'r.services = ' . intval($filter['services']);
    }

    if (($filter['services'] & 2) && strlen($filter['foreign-entity'])) {
        $where[] = $filter['foreign-entity'] == '-1' ? 'r.foreignentity = \'\'' : 'r.foreignentity = ' . $DB->Escape($filter['foreign-entity']);
    }

    if (!isset($nodes)) {
        $node_addresses = $DB->GetAll(
            'SELECT
                (CASE WHEN a2.id IS NULL THEN a.city_id ELSE a2.city_id END) AS city_id,
                (CASE WHEN a2.id IS NULL THEN lst.ident ELSE lst2.ident END) AS street_id,
                UPPER(CASE WHEN a2.id IS NULL THEN a.house ELSE a2.house END) AS house,
                COUNT(*) AS nodecount, '
                . $DB->GroupConcat('n.linktechnology', ',') . ' AS linktechnologies, '
                . $DB->GroupConcat('n.ownerid', ',') . ' AS customerids, '
                . $DB->GroupConcat($DB->Concat('c.lastname', "' '", 'c.name'), '|') . ' AS customernames
            FROM nodes n
            JOIN customers c ON c.id = n.ownerid
            LEFT JOIN vaddresses a ON a.id = n.address_id
            LEFT JOIN location_streets lst ON lst.id = a.street_id
            LEFT JOIN (
                SELECT
                    ca2.customer_id,
                    MAX(ca2.address_id) AS address_id
                FROM customer_addresses ca2
                JOIN (
                    SELECT
                        ca.customer_id,
                        MAX(ca.type) AS type
                    FROM customer_addresses ca
                    JOIN vaddresses va2 ON va2.id = ca.address_id AND va2.city_id IS NOT NULL AND va2.house <> \'\'
                    WHERE ca.type > 0
                    GROUP BY ca.customer_id
                ) ca3 ON ca2.customer_id = ca3.customer_id AND ca3.type = ca2.type
                JOIN vaddresses va3 ON va3.id = ca2.address_id
                WHERE va3.city_id IS NOT NULL
                    AND va3.house <> \'\'
                GROUP BY ca2.customer_id
            ) ca4 ON ca4.customer_id = n.ownerid
            LEFT JOIN customer_addresses ca ON n.address_id IS NULL AND ca.customer_id = ca4.customer_id
            LEFT JOIN vaddresses a2 ON a2.id = ca.address_id
            LEFT JOIN location_streets lst2 ON lst2.id = a2.street_id
            WHERE n.ipaddr <> 0
                AND ((a2.id IS NULL AND a.city_id IS NOT NULL ' . (isset($cityid) ? ' AND a.city_id = ' . $cityid : '') . ')
                OR (a2.id IS NOT NULL AND a2.city_id IS NOT NULL ' . (isset($cityid) ? ' AND a2.city_id = ' . $cityid : '') . '))
            GROUP BY
                (CASE WHEN a2.id IS NULL THEN a.city_id ELSE a2.city_id END),
                (CASE WHEN a2.id IS NULL THEN lst.ident ELSE lst2.ident END),
                UPPER(CASE WHEN a2.id IS NULL THEN a.house ELSE a2.house END)'
        );
        if (empty($node_addresses)) {
            $node_addresses = array();
        }

        $nodes = array();
        foreach ($node_addresses as $node_address) {
            $city_id = $node_address['city_id'];
            $street_id = intval($node_address['street_id']);
            $house = $node_address['house'];

            if (!isset($nodes[$city_id])) {
                $nodes[$city_id] = array();
            }
            if (!isset($nodes[$city_id][$street_id])) {
                $nodes[$city_id][$street_id] = array();
            }

            $linktechnologies = array();
            if (!empty($node_address['linktechnologies'])) {
                foreach (explode(',', $node_address['linktechnologies']) as $linktechnology) {
                    if (!strlen($linktechnology)) {
                        continue;
                    }
                    if (!isset($linktechnologies[$linktechnology])) {
                        $linktechnologies[$linktechnology] = 0;
                    }
                    $linktechnologies[$linktechnology]++;
                }
                arsort($linktechnologies);
            }

            $customers = array();
            if (!empty($node_address['customerids'])) {
                $customernames = explode('|', $node_address['customernames']);
                foreach (explode(',', $node_address['customerids']) as $idx => $customerid) {
                    if (!isset($customers[$customerid])) {
                        $customers[$customerid] = array(
                            'id' => $customerid,
                            'name' => $customernames[$idx],
                        );
                    }
                }
            }

            $nodes[$city_id][$street_id][$house] = array(
                'count' => $node_address['nodecount'],
                'linktechnologies' => $linktechnologies,
                'customers' => $customers,
            );
        }
    }

    if ($count) {
        $ranges = $DB->GetAll(
            'SELECT
                b.id,
                b.city_id,
                b.building_num,
                lst.ident AS street_ident,
                (CASE WHEN r.id IS NULL THEN 0 ELSE 1 END) AS ifrange
            FROM location_buildings b
            LEFT JOIN location_streets lst ON lst.id = b.street_id
            JOIN location_cities lc ON lc.id = b.city_id
            JOIN location_boroughs lb ON lb.id = lc.boroughid
            JOIN location_districts ld ON ld.id = lb.districtid
            JOIN location_states ls ON ls.id = ld.stateid
            LEFT JOIN netranges r ON r.buildingid = b.id'
            . (!empty($where) ? ' WHERE ' . implode(' AND ', $where) : '')
        );

        if (empty($ranges)) {
            $ranges = array();
        }
        $result = array(
            'total' => 0,
            'unique_total' => array(),
            'ranges' => 0,
            'existing' => 0,
        );
        foreach ($ranges as $range) {
            $city_id = intval($range['city_id']);
            $street_ident = intval($range['street_ident']);
            $building_num = mb_strtoupper($range['building_num']);

            if (isset($nodes[$city_id][$street_ident][$building_num])) {
                if ($existing == 2) {
                    continue;
                }
                $result['existing']++;
            } elseif ($existing == 1) {
                continue;
            }
            $result['total']++;
            $result['unique_total'][$range['id']] = true;
            if (!empty($range['ifrange'])) {
                $result['ranges']++;
            }
        }
        $result['unique_total'] = count($result['unique_total']);

        return $result;
    } else {
        if (isset($filter['cityid']) && is_numeric($filter['cityid'])) {
            $cityid = intval($filter['cityid']);
        }

        $limit = isset($filter['limit']) && is_numeric($filter['limit']) ? intval($filter['limit']) : null;
        $offset = isset($filter['offset']) && is_numeric($filter['offset']) ? intval($filter['offset']) : null;

        $buildings = $DB->GetAll(
            'SELECT b.*,
                lst.name AS street1,
                lst.name2 AS street2,
                (CASE WHEN lst.name2 IS NOT NULL THEN ' . $DB->Concat('lst.name', "' '", 'lst.name2') . ' ELSE lst.name END) AS street,
                (CASE WHEN lst.name2 IS NOT NULL THEN ' . $DB->Concat('lst.name2', "' '", 'lst.name') . ' ELSE lst.name END) AS rstreet,
                lst.ident AS street_ident,
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
                r.foreignentity,
                r.invprojectid
            FROM location_buildings b
            LEFT JOIN location_streets lst ON lst.id = b.street_id
            LEFT JOIN location_street_types t ON t.id = lst.typeid
            JOIN location_cities lc ON lc.id = b.city_id
            JOIN location_boroughs lb ON lb.id = lc.boroughid
            JOIN location_districts ld ON ld.id = lb.districtid
            JOIN location_states ls ON ls.id = ld.stateid
            LEFT JOIN netranges r ON r.buildingid = b.id'
            . (!empty($where) ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY ls.name, ld.name, lb.name, lc.name, lst.name, '
                . $DB->Cast($DB->SubstringRegExp('b.building_num', '^[0-9]+'), 'integer') . ', b.building_num'
            . (empty($existing) ? (
                (isset($limit) ? ' LIMIT ' . $limit : '')
                . (isset($offset) ? ' OFFSET ' . $offset : '')
            ) : '')
        );
    }
    if (empty($buildings)) {
        $buildings = array();
    } else {
        foreach ($buildings as &$building) {
            $city_id = intval($building['city_id']);
            $street_id = intval($building['street_id']);
            $street_ident = intval($building['street_ident']);
            $building_num = mb_strtoupper($building['building_num']);

            if (isset($nodes[$city_id][$street_ident][$building_num])) {
                $node = $nodes[$city_id][$street_ident][$building_num];
                $building['linktechnologies'] = $node['linktechnologies'];
                $building['customers'] = $node['customers'];
                $building['existing'] = 1;
            } else {
                $building['linktechnologies'] = array();
                $building['customers'] = array();
                $building['existing'] = 0;
            }
        }
        unset($building);
        if (!empty($existing)) {
            $buildings = array_filter($buildings, function ($building) use ($existing) {
                return $building['existing'] == 1 && $existing == 1 || $building['existing'] == 0 && $existing == 2;
            });
            if (isset($limit) || isset($offset)) {
                $buildings = array_slice(
                    $buildings,
                    $offset ?? 0,
                    $limit ?? null
                );
            }
        }
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
$oldrange = $oldrange ?? array();
$range = $_POST['range'] ?? array();

if (isset($range['project'])) {
    $range['project'] = strlen($range['project']) ? intval($range['project']) : null;
} else {
    $range['project'] = $oldrange['project'] ?? null;
}

if (isset($range['linktype'])) {
    $range['linktype'] = strlen($range['linktype']) ? intval($range['linktype']) : '';
} else {
    $range['linktype'] = $oldrange['linktype'] ?? '';
}

if (isset($range['linktechnology'])) {
    $range['linktechnology'] = strlen($range['linktechnology']) ? intval($range['linktechnology']) : '';
} else {
    $range['linktechnology'] = $oldrange['linktechnology'] ?? '';
}

if (isset($range['downlink'])) {
    $range['downlink'] = strlen($range['downlink']) ? intval($range['downlink']) : '';
} else {
    $range['downlink'] = $oldrange['downlink'] ?? '';
}

if (isset($range['uplink'])) {
    $range['uplink'] = strlen($range['uplink']) ? intval($range['uplink']) : '';
} else {
    $range['uplink'] = $oldrange['uplink'] ?? '';
}

if (isset($range['type'])) {
    $range['type'] = strlen($range['type']) ? intval($range['type']) : '';
} else {
    $range['type'] = $oldrange['type'] ?? '';
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

if (isset($range['foreign-entity'])) {
    $range['foreign-entity'] = strlen($range['foreign-entity']) ? $range['foreign-entity'] : '';
} else {
    $range['foreign-entity'] = $oldrange['foreign-entity'] ?? '';
}

$SESSION->save('netranges_update_range', $range);

if (isset($_POST['range'])) {
    if (isset($range['buildings']) || isset($range['ranges'])) {
        $buildings = isset($range['buildings']) ? Utils::filterIntegers($range['buildings']) : array();
        $ranges = isset($range['ranges']) ? Utils::filterIntegers(array_keys($range['ranges'])) : array();
        if (!empty($buildings) || !empty($ranges)) {
            $DB->BeginTrans();

            if (isset($_GET['delete'])) {
                if (!empty($buildings)) {
                    $DB->Execute('DELETE FROM netranges WHERE buildingid IN ?', array($buildings));
                }
                if (!empty($ranges)) {
                    $DB->Execute('DELETE FROM netranges WHERE id IN ?', array($ranges));
                }
            } elseif (isset($_GET['update'])) {
                $args = $range;
                unset($args['buildings'], $args['ranges']);
                $args['foreign-entity'] = ($range['services'] & 2) ? $range['foreign-entity'] : '';
                if (!empty($buildings)) {
                    foreach ($buildings as $buildingid) {
                        $args['buildingid'] = $buildingid;
                        if (!$DB->GetOne('SELECT 1 FROM netranges WHERE buildingid = ? LIMIT 1', array($buildingid))) {
                            $DB->Execute(
                                'INSERT INTO netranges
                                (invprojectid, linktype, linktechnology, downlink, uplink, type, services, foreignentity, buildingid)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                                array_values($args)
                            );
                        }
                    }
                    unset($args['buildingid']);
                }
                if (!empty($ranges)) {
                    $ranges = $range['ranges'];
                    foreach ($ranges as $rangeid => $buildingid) {
                        if (!ctype_digit(strval($rangeid)) || !ctype_digit($buildingid)) {
                            continue;
                        }
                        if ($DB->GetOne('SELECT 1 FROM netranges WHERE id = ?', array($rangeid))) {
                            $args['id'] = $rangeid;
                            $DB->Execute(
                                'UPDATE netranges
                                SET invprojectid = ?,
                                    linktype = ?,
                                    linktechnology = ?,
                                    downlink = ?,
                                    uplink = ?,
                                    type = ?,
                                    services = ?,
                                    foreignentity = ?
                                WHERE id = ?',
                                array_values($args)
                            );
                            unset($args['id']);
                        } else {
                            $args['buildingid'] = $buildingid;
                            $DB->Execute(
                                'INSERT INTO netranges
                                (invprojectid, linktype, linktechnology, downlink, uplink, type, services, foreignentity, buildingid)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                                array_values($args)
                            );
                            unset($args['buildingid']);
                        }
                    }
                }
            } elseif (isset($_GET['add'])) {
                if (!empty($range['ranges'])) {
                    $buildings = array_merge($buildings, array_unique(Utils::filterIntegers($range['ranges'])));
                }
                $args = $range;
                unset($args['buildings'], $args['ranges']);
                $args['foreign-entity'] = ($range['services'] & 2) ? $range['foreign-entity'] : '';
                if (!empty($buildings)) {
                    foreach ($buildings as $buildingid) {
                        $args['buildingid'] = $buildingid;
                        if (!$DB->GetOne(
                            'SELECT 1
                            FROM netranges
                            WHERE (invprojectid = ?' . (isset($args['project']) ? '' : ' OR invprojectid IS NULL') . ')
                                AND linktype = ?
                                AND linktechnology = ?
                                AND downlink = ?
                                AND uplink = ?
                                AND type = ?
                                AND services = ?
                                AND foreignentity = ?
                                AND buildingid = ?
                            LIMIT 1',
                            array_values($args)
                        )) {
                            $DB->Execute(
                                'INSERT INTO netranges
                                (invprojectid, linktype, linktechnology, downlink, uplink, type, services, foreignentity, buildingid)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                                array_values($args)
                            );
                        }
                    }
                }
            }

            $DB->CommitTrans();
        }
    }
}

$layout['pagetitle'] = trans('Network Ranges');

$oldfilter = $SESSION->get('netranges_filter');
$oldfilter = $oldfilter ?? array();
$filter = $_POST['filter'] ?? array();

if (isset($filter['project'])) {
    $filter['project'] = strlen($filter['project']) ? intval($filter['project']) : '';
} else {
    $filter['project'] = $oldfilter['project'] ?? '';
}

if (isset($filter['without-ranges'])) {
    $filter['without-ranges'] = strlen($filter['without-ranges']) ? intval($filter['without-ranges']) : '';
} else {
    $filter['without-ranges'] = $oldfilter['without-ranges'] ?? '';
}

if (isset($filter['existing'])) {
    $filter['existing'] = strlen($filter['existing']) ? intval($filter['existing']) : '';
} else {
    $filter['existing'] = $oldfilter['existing'] ?? '';
}

if (isset($filter['stateid'])) {
    $filter['stateid'] = strlen($filter['stateid']) ? intval($filter['stateid']) : '';
} else {
    $filter['stateid'] = $oldfilter['stateid'] ?? '';
}

if (isset($filter['districtid'])) {
    $filter['districtid'] = strlen($filter['districtid']) ? intval($filter['districtid']) : '';
} else {
    $filter['districtid'] = $oldfilter['districtid'] ?? '';
}
if (empty($filter['stateid'])) {
    $filter['districtid'] = '';
}

if (isset($filter['boroughid'])) {
    $filter['boroughid'] = strlen($filter['boroughid']) ? intval($filter['boroughid']) : '';
} else {
    $filter['boroughid'] = $oldfilter['boroughid'] ?? '';
}
if (empty($filter['districtid'])) {
    $filter['boroughid'] = '';
}

if (isset($filter['cityid'])) {
    $filter['cityid'] = strlen($filter['cityid']) ? intval($filter['cityid']) : '';
} else {
    $filter['cityid'] = $oldfilter['cityid'] ?? '';
}
if (empty($filter['boroughid'])) {
    $filter['cityid'] = '';
}

if (isset($filter['streetid'])) {
    $filter['streetid'] = strlen($filter['streetid']) ? intval($filter['streetid']) : '';
} else {
    $filter['streetid'] = $oldfilter['streetid'] ?? '';
}
if (empty($filter['cityid'])) {
    $filter['streetid'] = '';
}

$streets = empty($filter['cityid']) ? array() : getStreets($filter['cityid']);
if (isset($filter['numberparity'])) {
    $filter['numberparity'] = $filter['numberparity'];
} else {
    $filter['numberparity'] = $oldfilter['numberparity'] ?? '';
}
if (empty($filter['cityid']) || !empty($streets) && empty($filter['streetid'])) {
    $filter['numberparity'] = '';
}

if (isset($filter['linktype'])) {
    $filter['linktype'] = strlen($filter['linktype']) ? intval($filter['linktype']) : '';
} else {
    $filter['linktype'] = $oldfilter['linktype'] ?? '';
}

if (isset($filter['linktechnology'])) {
    $filter['linktechnology'] = strlen($filter['linktechnology']) ? intval($filter['linktechnology']) : '';
} else {
    $filter['linktechnology'] = $oldfilter['linktechnology'] ?? '';
}

if (isset($filter['downlink'])) {
    $filter['downlink'] = strlen($filter['downlink']) ? intval($filter['downlink']) : '';
} else {
    $filter['downlink'] = $oldfilter['downlink'] ?? '';
}

if (isset($filter['uplink'])) {
    $filter['uplink'] = strlen($filter['uplink']) ? intval($filter['uplink']) : '';
} else {
    $filter['uplink'] = $oldfilter['uplink'] ?? '';
}

if (isset($filter['type'])) {
    $filter['type'] = strlen($filter['type']) ? intval($filter['type']) : '';
} else {
    $filter['type'] = $oldfilter['type'] ?? '';
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

if (isset($filter['foreign-entity'])) {
    $filter['foreign-entity'] = strlen($filter['foreign-entity']) ? $filter['foreign-entity'] : '';
} else {
    $filter['foreign-entity'] = $oldfilter['foreign-entity'] ?? '';
}

$SESSION->save('netranges_filter', $filter);

$filter['count'] = true;

$summary = !empty($filter['stateid']) && !empty($filter['districtid']) ? getBuildings($filter) : array();
$total = isset($summary['total']) ? intval($summary['total']) : 0;
$unique_total = isset($summary['unique_total']) ? intval($summary['unique_total']) : 0;
$ranges = isset($summary['ranges']) ? intval($summary['ranges']) : 0;
$existing = isset($summary['existing']) ? intval($summary['existing']) : 0;

if (isset($_GET['page'])) {
    $page = intval($_GET['page']);
} elseif (isset($_POST['page'])) {
    $page = intval($_POST['page']);
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
//$foreign_entities = array();
$SMARTY->assign('foreign_entities', $foreign_entities);

$SMARTY->assign('boroughs', getTerritoryUnits());
$SMARTY->assign('cities', empty($filter['boroughid']) ? array() : getCities($filter['boroughid']));
$SMARTY->assign('streets', $streets);
$SMARTY->assign('pagination', $pagination);

$SMARTY->assign(array(
    'filter' => $filter,
    'linktechnologies' => $SIDUSIS_LINKTECHNOLOGIES,
    'linkspeeds' => $linkspeeds,
    'range'=> $range,
    'unique_total' => $unique_total,
    'total' => $total,
    'ranges' => $ranges,
    'existing' => $existing,
    'invprojects' => $LMS->GetProjects(),
));

$SMARTY->display('net/netranges.html');
