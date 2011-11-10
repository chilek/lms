<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2011 LMS Developers
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

$layout['pagetitle'] = trans('Select gps coordinates');

$p = isset($_GET['p']) ? $_GET['p'] : '';
$js = '';

if(!$p)
{
	$js = 'var targetfield1 = window.parent.targetfield1;';
	$js .= 'var targetfield2 = window.parent.targetfield2;';
}
elseif($p == 'main')
{
	$js = 'var targetfield1 = window.parent.targetfield1;';
	$js .= 'var targetfield2 = window.parent.targetfield2;';

	$devices = $DB->GetAllByKey('SELECT n.id, n.name, n.location, MAX(lastonline) AS lastonline, n.latitude, n.longitude 
					FROM netdevices n 
					LEFT JOIN nodes ON (n.id = netdev) 
					WHERE n.latitude IS NOT NULL AND n.longitude IS NOT NULL 
					GROUP BY n.id, n.name, n.location, n.latitude, n.longitude', 'id');

	if ($devices)
	{
		foreach ($devices as $devidx => $device)
			if ($device['lastonline'])
				if (time() - $device['lastonline'] > $CONFIG['phpui']['lastonline_limit'])
				{
					$devices[$devidx]['img'] = 'img/netdev_off.png';
					$devices[$devidx]['state'] = 2;
				}
				else
				{
					$devices[$devidx]['img'] = 'img/netdev_on.png';
					$devices[$devidx]['state'] = 1;
				}
			else
			{
				$devices[$devidx]['img'] = 'img/netdev_unk.png';
				$devices[$devidx]['state'] = 0;
			}

		$devids = implode(',', array_keys($devices));

		$links = $DB->GetAll('SELECT src, dst, type FROM netlinks WHERE src IN ('.$devids.') AND dst IN ('.$devids.')');
		if ($links)
			foreach ($links as $linkidx => $link)
			{
				$links[$linkidx]['srclat'] = $devices[$link['src']]['latitude'];
				$links[$linkidx]['srclon'] = $devices[$link['src']]['longitude'];
				$links[$linkidx]['dstlat'] = $devices[$link['dst']]['latitude'];
				$links[$linkidx]['dstlon'] = $devices[$link['dst']]['longitude'];
			}
	}

	$nodes = $DB->GetAllByKey('SELECT n.id, n.name, n.location, MAX(n.lastonline) AS lastonline, n.latitude, n.longitude 
					FROM nodes n 
					WHERE n.latitude IS NOT NULL AND n.longitude IS NOT NULL 
					GROUP BY n.id, n.name, n.location, n.latitude, n.longitude', 'id');

	if ($nodes)
	{
		foreach ($nodes as $nodeidx => $node)
			if ($node['lastonline'])
				if (time() - $node['lastonline'] > $CONFIG['phpui']['lastonline_limit'])
				{
					$nodes[$nodeidx]['img'] = 'img/node_off.png';
					$nodes[$nodeidx]['state'] = 2;
				}
				else
				{
					$nodes[$nodeidx]['img'] = 'img/node_on.png';
					$nodes[$nodeidx]['state'] = 1;
				}
			else
			{
				$nodes[$nodeidx]['img'] = 'img/node_unk.png';
				$nodes[$nodeidx]['state'] = 0;
			}

		$nodeids = implode(',', array_keys($nodes));

		$nodelinks = $DB->GetAll('SELECT n.id AS nodeid, netdev, linktype AS type FROM nodes n WHERE netdev > 0 AND ownerid > 0 
			AND n.id IN ('.$nodeids.') AND netdev IN ('.$devids.')');
		if ($nodelinks)
			foreach ($nodelinks as $nodelinkidx => $nodelink)
			{
				$nodelinks[$nodelinkidx]['nodelat'] = $nodes[$nodelink['nodeid']]['latitude'];
				$nodelinks[$nodelinkidx]['nodelon'] = $nodes[$nodelink['nodeid']]['longitude'];
				$nodelinks[$nodelinkidx]['netdevlat'] = $devices[$nodelink['netdev']]['latitude'];
				$nodelinks[$nodelinkidx]['netdevlon'] = $devices[$nodelink['netdev']]['longitude'];
			}
	}

	$SMARTY->assign('type', $type);
	$SMARTY->assign('devices', $devices);
	$SMARTY->assign('links', $links);
	$SMARTY->assign('nodes', $nodes);
	$SMARTY->assign('nodelinks', $nodelinks);
}

$SMARTY->assign('part', $p);
$SMARTY->assign('js', $js);
$SMARTY->display('choosegpscoords.html');

?>
