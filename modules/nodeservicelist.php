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

if (!$api) {
    die;
}

$nodes = $LMS->GetNodeList();

unset($nodes['total']);
unset($nodes['order']);
unset($nodes['direction']);
unset($nodes['totalon']);
unset($nodes['totaloff']);

$nodeservices = array();

if (!empty($nodes)) {
    $nodetariffs = $DB->GetAllByKey(
        "SELECT id, downrate, downceil, uprate, upceil FROM vnodetariffs",
        'id'
    );
    $netdev_statuses = $DB->GetAllByKey(
        "SELECT id, status FROM netdevices",
        'id'
    );
    $services = $DB->GetAllByKey("SELECT n.id, " . $DB->GroupConcat('DISTINCT s.type') . " AS tarifftypes,
			c.type AS customertype
		FROM nodes n
		JOIN customers c ON c.id = n.ownerid
		JOIN (
			SELECT na.nodeid, t.type FROM nodeassignments na
			JOIN assignments a ON a.id = na.assignmentid
			JOIN tariffs t ON t.id = a.tariffid
			LEFT JOIN (
				SELECT customerid, COUNT(id) AS allsuspended FROM assignments
				WHERE tariffid IS NULL AND liabilityid IS NULL
				AND datefrom <= ?NOW?
				AND (dateto = 0 OR dateto > ?NOW?)
				GROUP BY customerid
			) s ON s.customerid = a.customerid
			WHERE s.allsuspended IS NULL AND a.suspended = 0 AND a.commited = 1
				AND a.datefrom <= ?NOW?
				AND (a.dateto = 0 OR a.dateto >= ?NOW?)
				AND t.type IN (?, ?, ?)
		) s ON s.nodeid = n.id
		GROUP BY n.id, c.type
		ORDER BY n.id", 'id', array(SERVICE_INTERNET, SERVICE_PHONE, SERVICE_TV));
    if (empty($services)) {
        $services = array();
    } else {
        foreach ($services as &$service) {
            $service['tarifftypes'] = array_flip(explode(',', $service['tarifftypes']));
        }
        unset($service);
    }

    foreach ($nodes as &$node) {
        $nodeid = $node['id'];

        $phone_pots = isset($services[$nodeid]['tarifftypes'][SERVICE_PHONE]) && $node['linktechnology'] == 12 ? 1 : 0;
        $phone_voip = isset($services[$nodeid]['tarifftypes'][SERVICE_PHONE]) && $node['linktechnology'] != 12
            && ($node['linktechnology'] < 105 || $node['linktechnology'] >= 200) ? 1 : 0;
        $phone_mobile = isset($services[$nodeid]['tarifftypes'][SERVICE_PHONE])
            && $node['linktechnology'] >= 105 && $node['linktechnology'] < 200 ? 1 : 0;
        $internet_fixed = isset($services[$nodeid]['tarifftypes'][SERVICE_INTERNET])
            && ($node['linktechnology'] < 105 || $node['linktechnology'] >= 200) ? 1 : 0;
        $internet_mobile = isset($services[$nodeid]['tarifftypes'][SERVICE_INTERNET])
            && $node['linktechnology'] >= 105 && $node['linktechnology'] < 200 ? 1 : 0;
        $tv = isset($services[$nodeid]['tarifftypes'][SERVICE_TV]) ? 1 : 0;
        if (!$phone_pots && !$phone_voip && !$phone_mobile && !$internet_fixed && !$internet_mobile && !$tv) {
            continue;
        }

        $nodeservices[] = array(
            'nodeid' => $nodeid,
            'status' => empty($node['netdev']) || !isset($netdev_statuses[$node['netdev']])
                ? 0 : $netdev_statuses[$node['netdev']]['status'],
            'project' => $node['project'],
            'linktechnology' => $node['linktechnology'],
            'phone_pots' => $phone_pots,
            'phone_voip' => $phone_voip,
            'phone_mobile' => $phone_mobile,
            'internet_fixed' => $internet_fixed,
            'internet_mobile' => $internet_mobile,
            'tv' => $tv,
            'type' => $services[$nodeid]['customertype'],
            'downrate' => empty($nodetariffs[$nodeid]['downrate']) ? null : $nodetariffs[$nodeid]['downrate'],
            'downceil' => empty($nodetariffs[$nodeid]['downceil']) ? null : $nodetariffs[$nodeid]['downceil'] ,
            'uprate' => empty($nodetariffs[$nodeid]['uprate']) ? null : $nodetariffs[$nodeid]['uprate'],
            'upceil' => empty($nodetariffs[$nodeid]['upceil']) ? null : $nodetariffs[$nodeid]['upceil'],
        );
    }
    unset($node);
}

header('Content-Type: application/json');
echo json_encode($nodeservices);
die;
