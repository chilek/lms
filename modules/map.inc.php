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

$devices = $DB->GetAllByKey('SELECT n.id, n.name, n.location, '.$DB->GroupConcat('INET_NTOA(CASE WHEN vnodes.ownerid = 0 THEN vnodes.ipaddr ELSE NULL END)', ',', true)
				.' AS ipaddr, '.$DB->GroupConcat('CASE WHEN vnodes.ownerid = 0 THEN vnodes.id ELSE NULL END', ',', true).' AS nodeid, 
				MAX(lastonline) AS lastonline, n.latitude AS lat, n.longitude AS lon,
				' . $DB->GroupConcat('rs.id') . ' AS radiosectors
				FROM netdevices n 
				LEFT JOIN vnodes ON n.id = vnodes.netdev 
				LEFT JOIN netradiosectors rs ON rs.netdev = n.id
				WHERE n.latitude IS NOT NULL AND n.longitude IS NOT NULL 
				GROUP BY n.id, n.name, n.location, n.latitude, n.longitude', 'id');

if ($devices) {
	foreach ($devices as $devidx => $device) {
		if ($device['lastonline'])
			if (time() - $device['lastonline'] > ConfigHelper::getConfig('phpui.lastonline_limit'))
				$devices[$devidx]['state'] = 2;
			else
				$devices[$devidx]['state'] = 1;
		else
			$devices[$devidx]['state'] = 0;
		$urls = $DB->GetRow('SELECT '.$DB->GroupConcat('url').' AS url,
			'.$DB->GroupConcat('comment').' AS comment FROM managementurls WHERE netdevid = ?',
			array($device['id']));
		if ($urls) {
			$devices[$devidx]['url'] = $urls['url'];
			$devices[$devidx]['comment'] = $urls['comment'];
		}
		if ($device['radiosectors'])
			$devices[$devidx]['radiosectors'] = $DB->GetAll('SELECT name, azimuth, width, rsrange,
				frequency, frequency2, bandwidth FROM netradiosectors WHERE id IN
				(' . $device['radiosectors'] . ')');
		else
			unset($devices[$devidx]['radiosectors']);
	}

	$devids = implode(',', array_keys($devices));

	$devlinks = $DB->GetAll('SELECT src, dst, type, technology, speed FROM netlinks WHERE src IN ('.$devids.') AND dst IN ('.$devids.')');
	if ($devlinks)
		foreach ($devlinks as $devlinkidx => $devlink) {
			$devlinks[$devlinkidx]['srclat'] = $devices[$devlink['src']]['lat'];
			$devlinks[$devlinkidx]['srclon'] = $devices[$devlink['src']]['lon'];
			$devlinks[$devlinkidx]['dstlat'] = $devices[$devlink['dst']]['lat'];
			$devlinks[$devlinkidx]['dstlon'] = $devices[$devlink['dst']]['lon'];
			$devlinks[$devlinkidx]['typename'] = trans("Link type:")." ".$LINKTYPES[$devlink['type']];
			$devlinks[$devlinkidx]['technologyname'] = ($devlink['technology'] ? trans("Link technology:")." ".$LINKTECHNOLOGIES[$devlink['type']][$devlink['technology']] : '');
			$devlinks[$devlinkidx]['speedname'] = trans("Link speed:")." ".$LINKSPEEDS[$devlink['speed']];
		}
}

$nodes = $DB->GetAllByKey('SELECT n.id, n.name, INET_NTOA(n.ipaddr) AS ipaddr, n.location, n.lastonline, n.latitude AS lat, n.longitude AS lon 
				FROM vnodes n 
				WHERE n.latitude IS NOT NULL AND n.longitude IS NOT NULL', 'id');

if ($nodes) {
	foreach ($nodes as $nodeidx => $node) {
		if ($node['lastonline'])
			if (time() - $node['lastonline'] > ConfigHelper::getConfig('phpui.lastonline_limit'))
				$nodes[$nodeidx]['state'] = 2;
			else
				$nodes[$nodeidx]['state'] = 1;
		else
			$nodes[$nodeidx]['state'] = 0;
		$urls = $DB->GetRow('SELECT '.$DB->GroupConcat('url').' AS url,
			'.$DB->GroupConcat('comment').' AS comment FROM managementurls WHERE nodeid = ?',
			array($node['id']));
		if ($urls) {
			$nodes[$nodeidx]['url'] = $urls['url'];
			$nodes[$nodeidx]['comment'] = $urls['comment'];
		}
	}

	$nodeids = implode(',', array_keys($nodes));

	if ($devices) {
		$nodelinks = $DB->GetAll('SELECT n.id AS nodeid, netdev, linktype AS type, linktechnology AS technology,
			linkspeed AS speed FROM vnodes n WHERE netdev > 0 AND ownerid > 0 
			AND n.id IN ('.$nodeids.') AND netdev IN ('.$devids.')');
		if ($nodelinks)
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

$SMARTY->assign('devices', $devices);
$SMARTY->assign('devlinks', $devlinks);
$SMARTY->assign('nodes', $nodes);
$SMARTY->assign('nodelinks', $nodelinks);

?>
