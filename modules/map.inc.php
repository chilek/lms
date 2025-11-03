<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2023 LMS Developers
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

$devices = $DB->GetAllByKey(
    'SELECT
        n.id,
        n.name,
        va.location,
        ' . $DB->GroupConcat('INET_NTOA(CASE WHEN vnodes.ownerid IS NULL THEN vnodes.ipaddr ELSE NULL END)', ',', true) . ' AS ipaddr, '
        . $DB->GroupConcat('CASE WHEN vnodes.ownerid IS NULL THEN vnodes.id ELSE NULL END', ',', true) . ' AS nodeid,
        MAX(lastonline) AS lastonline,
        (CASE WHEN nn.latitude IS NOT NULL AND n.netnodeid > 0 THEN nn.latitude ELSE n.latitude END) AS lat,
        (CASE WHEN nn.longitude IS NOT NULL AND n.netnodeid > 0 THEN nn.longitude ELSE n.longitude END) AS lon,
        ' . $DB->GroupConcat('rs.id') . ' AS radiosectors,
        n.ownerid
    FROM netdevices n
    LEFT JOIN vaddresses va ON va.id = n.address_id
    LEFT JOIN netnodes nn ON nn.id = n.netnodeid
    LEFT JOIN vnodes ON n.id = vnodes.netdev
    LEFT JOIN netradiosectors rs ON rs.netdev = n.id
    WHERE ((nn.latitude IS NULL AND n.latitude IS NOT NULL) OR nn.latitude IS NOT NULL)
        AND ((nn.longitude IS NULL AND n.longitude IS NOT NULL) OR nn.longitude IS NOT NULL)
    GROUP BY n.id, n.name, va.location, n.latitude, n.longitude, nn.latitude, nn.longitude, n.ownerid, n.netnodeid',
    'id'
);

if ($devices) {
    $time_now = time();

    foreach ($devices as $devidx => $device) {
        if (empty($device['location']) && $device['ownerid']) {
            $devices[$devidx]['location'] = $LMS->getAddressForCustomerStuff($device['ownerid']);
        }
        $devices[$devidx]['name'] = trim($device['name'], ' "');
        if ($device['lastonline']) {
            if ($time_now - $device['lastonline'] > ConfigHelper::getConfig('phpui.lastonline_limit')) {
                $devices[$devidx]['state'] = 2;
            } else {
                $devices[$devidx]['state'] = 1;
            }
        } else {
            $devices[$devidx]['state'] = 0;
        }

            $urls = $DB->GetRow(
                'SELECT '.$DB->GroupConcat('url').' AS url,
			'.$DB->GroupConcat('comment').' AS comment FROM managementurls WHERE netdevid = ?',
                array($device['id'])
            );
        if ($urls) {
            $devices[$devidx]['url'] = $urls['url'];
            $devices[$devidx]['comment'] = $urls['comment'];
        }
        if ($device['radiosectors']) {
            $devices[$devidx]['radiosectors'] = $DB->GetAll('SELECT name, azimuth, width, rsrange,
				frequency, frequency2, bandwidth, technology FROM netradiosectors WHERE id IN
				(' . $device['radiosectors'] . ')');
        } else {
            unset($devices[$devidx]['radiosectors']);
        }
    }

    $devlinks = $DB->GetAllByKey(
        'SELECT
            id,
            src,
            dst,
            type,
            technology,
            speed,
            foreignentity
        FROM netlinks
        WHERE src IN ?
            AND dst IN ?',
        'id',
        array(
            array_keys($devices),
            array_keys($devices),
        )
    );
    if ($devlinks) {
        foreach ($devlinks as &$devlink) {
            $devlink['netlinkid'] = $devlink['id'];
            $devlink['srclat'] = $devices[$devlink['src']]['lat'];
            $devlink['srclon'] = $devices[$devlink['src']]['lon'];
            $devlink['dstlat'] = $devices[$devlink['dst']]['lat'];
            $devlink['dstlon'] = $devices[$devlink['dst']]['lon'];
            $devlink['typename'] = trans("Link type:") . ' ' . $LINKTYPES[$devlink['type']];
            $devlink['technologyname'] = ($devlink['technology'] ? trans("Link technology:") . ' ' . $LINKTECHNOLOGIES[$devlink['type']][$devlink['technology']] : '');
            $devlink['speedname'] = trans("Link speed:") . ' ' . $LINKSPEEDS[$devlink['speed']];
            $devlink['points'] = array(
                0 => array(
                    'lon' => $devices[$devlink['src']]['lon'],
                    'lat' => $devices[$devlink['src']]['lat'],
                ),
            );
        }
        unset($devlink);

        $netlinkpoints = $DB->GetAll(
            'SELECT *
            FROM netlinkpoints
            ORDER BY id'
        );
        if (empty($netlinkpoints)) {
            $netlinkpoints = array();
        }

        foreach ($netlinkpoints as $netlinkpoint) {
            $netlinkid = $netlinkpoint['netlinkid'];
            if (!isset($devlinks[$netlinkid])) {
                continue;
            }
            $netlinkpointid = $netlinkpoint['id'];
            $devlinks[$netlinkid]['points'][$netlinkpointid] = array(
                'lon' => $netlinkpoint['longitude'],
                'lat' => $netlinkpoint['latitude'],
            );
        }

        foreach ($devlinks as &$devlink) {
            $devlink['points'][PHP_INT_MAX] = array(
                'lon' => $devlink['dstlon'],
                'lat' => $devlink['dstlat'],
            );
        }
        unset($devlink);
    }
} else {
    $devlinks = null;
}

$nodes = $DB->GetAllByKey(
    'SELECT
        n.id,
        n.name,
        INET_NTOA(n.ipaddr) AS ipaddr,
        n.location,
        n.lastonline,
        n.latitude AS lat,
        n.longitude AS lon,
        n.linktype,
        n.linktechnology
    FROM vnodes n
    WHERE n.latitude IS NOT NULL
        AND n.longitude IS NOT NULL',
    'id'
);

if ($nodes) {
    foreach ($nodes as &$node) {
        if ($node['lastonline']) {
            if (time() - $node['lastonline'] > ConfigHelper::getConfig('phpui.lastonline_limit')) {
                $node['state'] = 2;
            } else {
                $node['state'] = 1;
            }
        } else {
            $node['state'] = 0;
        }

        if ($node['linktype'] == LINKTYPE_WIRE) {
            $node['linktypeicon'] = 'wired';
        } elseif ($node['linktype'] == LINKTYPE_WIRELESS) {
            $node['linktypeicon'] = 'wireless';
        } elseif ($node['linktype'] == LINKTYPE_FIBER) {
            $node['linktypeicon'] = 'fiber';
        } else {
            $node['linktypeicon'] = '';
        }
        $node['linktypename'] = isset($node['linktype']) ? $LINKTYPES[$node['linktype']] : '';
        $node['linktechnologyname'] = isset($node['linktype'], $node['linktechnology']) ? $LINKTECHNOLOGIES[$node['linktype']][$node['linktechnology']] : '';

        $urls = $DB->GetRow(
            'SELECT ' . $DB->GroupConcat('url') . ' AS url,
            ' . $DB->GroupConcat('comment') . ' AS comment
            FROM managementurls
            WHERE nodeid = ?',
            array($node['id'])
        );
        if ($urls) {
            $node['url'] = $urls['url'];
            $node['comment'] = $urls['comment'];
        }
    }
    unset($node);

    if ($devices) {
        $nodelinks = $DB->GetAll(
            'SELECT n.id AS nodeid, netdev, linktype AS type, linktechnology AS technology,
                linkspeed AS speed
            FROM vnodes n
            WHERE netdev IS NOT NULL
                AND ownerid IS NOT NULL
                AND n.id IN ?
                AND netdev IN ?',
            array(
                array_keys($nodes),
                array_keys($devices),
            )
        );
        if ($nodelinks) {
            foreach ($nodelinks as $nodelinkidx => $nodelink) {
                $nodelinks[$nodelinkidx]['nodelat'] = $nodes[$nodelink['nodeid']]['lat'];
                $nodelinks[$nodelinkidx]['nodelon'] = $nodes[$nodelink['nodeid']]['lon'];
                $nodelinks[$nodelinkidx]['netdevlat'] = $devices[$nodelink['netdev']]['lat'];
                $nodelinks[$nodelinkidx]['netdevlon'] = $devices[$nodelink['netdev']]['lon'];
                $nodelinks[$nodelinkidx]['typename'] = trans("Link type:")." ".$LINKTYPES[$nodelink['type']];
                $nodelinks[$nodelinkidx]['technologyname'] = ($nodelink['technology'] ? trans("Link technology:")." ".$LINKTECHNOLOGIES[$nodelink['type']][$nodelink['technology']] : '');
                $nodelinks[$nodelinkidx]['speedname'] = trans("Link speed:")." ".$LINKSPEEDS[$nodelink['speed']];
            }
        }
    }
}

$ranges = $DB->GetAll(
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
    JOIN netranges r ON r.buildingid = b.id
    LEFT JOIN (
        SELECT a.city_id, a.street_id, UPPER(a.house) AS house, COUNT(*) AS nodecount FROM nodes n
        JOIN vaddresses a ON a.id = n.address_id
        WHERE a.city_id IS NOT NULL
        GROUP BY a.city_id, a.street_id, UPPER(a.house)
    ) na ON b.city_id = na.city_id AND (b.street_id IS NULL OR b.street_id = na.street_id) AND na.house = UPPER(b.building_num)
    ORDER BY ls.name, ld.name, lb.name, lc.name, lst.name, b.building_num'
);

if ($ranges) {
    foreach ($ranges as &$range) {
        $range['location'] = $range['city'] . (empty($range['street_id']) ? '' : ', ' . $range['streettype'] . ' ' . $range['street']) . ' ' . $range['building_num'];
        $range['typename'] = trans("Link type:") . ' ' . $LINKTYPES[$range['linktype']];
        $range['technologyname'] = ($range['linktechnology'] ? trans("Link technology:") . ' ' . $SIDUSIS_LINKTECHNOLOGIES[$range['linktype']][$range['linktechnology']] : '');
        $range['speedname'] = trans("Link speed:") . ' ' . trans('$a Mbit/$b Mbit', $range['downlink'], $range['uplink']);
        $range['rangetypename'] = trans('<!netrange>Type:') . ' ' . trans($range['type'] == 1 ? '<!netrange>real' : '<!netrange>theoretical');
        $range['existingname'] = empty($range['existing']) ? '' : trans('<!netrange>Existing');
        $range['servicesname'] = '<ul>' . trans('<!netrange>Services:')
            . ($range['services'] & 1 ? '<li><span>' . trans('<!netrange>wholesale') . '</span></li>' : '')
            . ($range['services'] & 2 ? '<li><span>' . trans('<!netrange>retail') . '</span></li>' : '')
            . '</ul>';
    }
    unset($range);
}

$SMARTY->assign('devices', $devices);
$SMARTY->assign('devlinks', $devlinks);
$SMARTY->assign('nodes', $nodes);
$SMARTY->assign('nodelinks', empty($nodelinks) ? null : $nodelinks);
$SMARTY->assign('ranges', empty($ranges) ? null : $ranges);
