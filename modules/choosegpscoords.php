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
					$devices[$devidx]['img'] = 'img/netdev_off.png';
				else
					$devices[$devidx]['img'] = 'img/netdev_on.png';
			else
				$devices[$devidx]['img'] = 'img/netdev_unk.png';

		$devids = implode(',', array_keys($devices));

		$links = $DB->GetAll('SELECT src, dst, type FROM netlinks WHERE src IN ('.$devids.') AND dst IN ('.$devids.')');
	}

	if ($links)
		foreach ($links as $linkidx => $link)
		{
			$links[$linkidx]['srclat'] = $devices[$link['src']]['latitude'];
			$links[$linkidx]['srclon'] = $devices[$link['src']]['longitude'];
			$links[$linkidx]['dstlat'] = $devices[$link['dst']]['latitude'];
			$links[$linkidx]['dstlon'] = $devices[$link['dst']]['longitude'];
		}

	$SMARTY->assign('devices', $devices);
	$SMARTY->assign('links', $links);
}

$SMARTY->assign('part', $p);
$SMARTY->assign('js', $js);
$SMARTY->display('choosegpscoords.html');

?>
