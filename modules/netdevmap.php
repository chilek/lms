<?php

/*
 * LMS version 1.3-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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

                                 _
 _   ___      ____ _  __ _  __ _| |
| | | \ \ /\ / / _` |/ _` |/ _` | |
| |_| |\ V  V / (_| | (_| | (_| |_|
 \__,_| \_/\_/ \__,_|\__, |\__,_(_)
                     |___/

jak macie w³asne pomys³y, to nie modyfikujcie tego pliku a zróbcie tymczasowy
nowy.

*/

function makemap(&$DB, &$map, &$seen, $device = 0, $x = 50, $y = 50)
{
//	$fields[] = array( 'x' => -1, 'y' => 1 );
	$fields[] = array( 'x' => 0, 'y' => 5 );
	$fields[] = array( 'x' => 5, 'y' => 5 );     
	$fields[] = array( 'x' => 5, 'y' => 0 );
	$fields[] = array( 'x' => 5, 'y' => -5 );
	$fields[] = array( 'x' => 0, 'y' => -5 );
	$fields[] = array( 'x' => -5, 'y' => -5 );
	$fields[] = array( 'x' => -5, 'y' => 0 );
	$fields[] = array( 'x' => -5, 'y' => 5 );

	unset($nodefields);

	$nodefields[] = array( 'x' => -2, 'y' => 2 );
	$nodefields[] = array( 'x' => -1, 'y' => 2 );
	$nodefields[] = array( 'x' => 0, 'y' => 2 );
	$nodefields[] = array( 'x' => 1, 'y' => 2 );
	$nodefields[] = array( 'x' => 2, 'y' => 2 );
	$nodefields[] = array( 'x' => 2, 'y' => 1 );
	$nodefields[] = array( 'x' => 2, 'y' => 0 );
	$nodefields[] = array( 'x' => 2, 'y' => -1 );
	$nodefields[] = array( 'x' => 2, 'y' => -2 );
	$nodefields[] = array( 'x' => 1, 'y' => -2 );
	$nodefields[] = array( 'x' => 0, 'y' => -2 );
	$nodefields[] = array( 'x' => -1, 'y' => -2 );
	$nodefields[] = array( 'x' => -2, 'y' => -2 );
	$nodefields[] = array( 'x' => -2, 'y' => -1 );
	$nodefields[] = array( 'x' => -2, 'y' => 0 );
	$nodefields[] = array( 'x' => -2, 'y' => 1 );
	$nodefields[] = array( 'x' => -1, 'y' => 1 );
	$nodefields[] = array( 'x' => 0, 'y' => 1 );
	$nodefields[] = array( 'x' => 1, 'y' => 1 );
	$nodefields[] = array( 'x' => 1, 'y' => 0 );
	$nodefields[] = array( 'x' => 1, 'y' => -1 );
	$nodefields[] = array( 'x' => 0, 'y' => -1 );
	$nodefields[] = array( 'x' => -1, 'y' => -1 );
	$nodefields[] = array( 'x' => -1, 'y' => 0 );
	
	if($device == 0)
	{
		$device = $DB->GetOne('SELECT id FROM netdevices ORDER BY id ASC');
		makemap($DB, $map, $seen, $device, $x, $y);
	}
	else
	{
		// umie¶æmy urz±dzenie nasze w przestrzeni
		
		$map[$x][$y] = $device;

		// 'JÓZEF TKACZÓK TU BY£'

		$seen[$device] = TRUE;
		
		// zobaczmy device'y tego urz±dzenia
		
		$devices = $DB->GetCol("SELECT (CASE src WHEN ? THEN dst ELSE src END) AS dst, (CASE src WHEN ? THEN src ELSE dst END) AS src FROM netlinks WHERE src = ? OR dst = ?",array($device, $device, $device, $device));

		if($devices) foreach($devices as $deviceid)
		{
			if(! $seen[$deviceid])
			{
				// tego urz±dzenia nie przerabiali¶my jeszcze
				// wyszukajmy wolny punkt w okolicy
				$tx = NULL;
				$ty = NULL;
				for($i=0;$i < sizeof($fields);$i++)
					if($tx == NULL && $ty == NULL && $map[$x + $fields[$i]['x']][$y + $fields[$i]['y']] == NULL)
					{
						$tx = $x + $fields[$i]['x'];
						$ty = $y + $fields[$i]['y'];
					}

				if($tx != NULL && $ty != NULL)
					makemap($DB, $map, $seen, $deviceid, $tx, $ty);
			}				
		}

		if($nodes = $DB->GetCol("SELECT id FROM nodes WHERE netdev=? AND ownerid>0 ORDER BY name ASC",array($device)))
		{
			foreach($nodes as $nodeid)
			{
				$ntx = NULL;
				$nty = NULL;
				for($i=0;$i < sizeof($nodefields);$i++)
					if($ntx == NULL && $nty == NULL && $map[$x + $nodefields[$i]['x']][$y + $nodefields[$i]['y']] == NULL)
					{
						$ntx = $x + $nodefields[$i]['x'];
						$nty = $y + $nodefields[$i]['y'];
					}
				if($ntx != NULL && $nty != NULL)
					$map[$ntx][$nty] = 'n'.$nodeid.'.'.$device;
			}
		}
	}
}

$layout['pagetitle'] = "Mapa po³±czeñ sieciowych";

if($_GET['graph'] == "")
{
	$SMARTY->assign('deviceslist',$DB->GetAll('SELECT id, name FROM netdevices ORDER BY name ASC'));
	$SMARTY->assign('gderror', ! function_exists('imagepng'));
	$SMARTY->assign('start',$_GET['start']);
	$SMARTY->display('netdevmap.html');
}
else
{	
	$start = sprintf('%d',$_GET['start']);
	makemap($DB,$map,$seen,$start);
	foreach($map as $idx => $x)
	{
		if($minx == NULL)
			$minx = $idx;
		elseif($idx < $minx)
			$minx = $idx;
		
		if($idx > $maxx)
			$maxx = $idx;
		foreach($x as $idy => $y)
		{
			if($miny == NULL)
				$miny = $idy;
			elseif($idy < $miny)
				$miny = $idy;

			if($idy > $maxy)
				$maxy = $idy;
		}
	}

	header('Content-type: image/png');
	$widthx = $maxx - $minx;
	$widthy = $maxy - $miny;
	$cellw = 70;
	$cellh = 30;
	$celltmargin = 10;
	$celllmargin = 10;
	$imgwx = $cellw * ($widthx + 2);
	$imgwy = $cellh * ($widthy + 2);

	$im = imagecreatetruecolor($imgwx, $imgwy);
	$lightbrown = imagecolorallocate($im, 234,228,214);
	$black = imagecolorallocate($im, 0,0,0);
	$white = imagecolorallocate($im, 255,255,255);
	$red = imagecolorallocate($im, 255,0,0);
	$green = imagecolorallocate($im, 0,128,0);
	$blue = imagecolorallocate($im, 0,0,255);
	$darkred = imagecolorallocate($im, 128,0,0);

	imagefill($im,0,0,$lightbrown);

	foreach($map as $idx => $x)
	{
		foreach($x as $idy => $device)
		{
			$celx = $idx - $minx;
			$cely = $idy - $miny;
			if(eregi('^n',$device))
			{
				
				$device = str_replace('n','',$device);
				list($nodeid,$device) = explode('.',$device);
				$nodemap[$nodeid]['x'] = $celx;
				$nodemap[$nodeid]['y'] = $cely;
				$nodemap[$nodeid]['device'] = $device;
			}
			else
			{
				$devicemap[$device]['x'] = $celx;
				$devicemap[$device]['y'] = $cely;
			}
		}
	}

	$links = $DB->GetAll('SELECT src, dst FROM netlinks');
	if($links) foreach($links as $link)
	{
		$src_celx = $devicemap[$link['src']]['x'];
		$src_cely = $devicemap[$link['src']]['y'];
		$dst_celx = $devicemap[$link['dst']]['x'];
		$dst_cely = $devicemap[$link['dst']]['y'];
		$src_px = (($src_celx * $cellw) + $celllmargin);
		$src_py = (($src_cely * $cellh) + $celltmargin);
		$dst_px = (($dst_celx * $cellw) + $celllmargin);
		$dst_py = (($dst_cely * $cellh) + $celltmargin);
		imageline($im, $src_px+8, $src_py+8, $dst_px+8, $dst_py+8, $green);
		imageline($im, $src_px+9, $src_py+9, $dst_px+9, $dst_py+9, $green);
	}

	foreach($nodemap as $node)
	{
		$src_celx = $node['x'];
		$src_cely = $node['y'];
		$dst_celx = $devicemap[$node['device']]['x'];
		$dst_cely = $devicemap[$node['device']]['y'];
		$src_px = (($src_celx * $cellw) + $celllmargin);
		$src_py = (($src_cely * $cellh) + $celltmargin);
		$dst_px = (($dst_celx * $cellw) + $celllmargin);
		$dst_py = (($dst_cely * $cellh) + $celltmargin);
		imageline($im, $src_px+4, $src_py+4, $dst_px+4, $dst_py+4, $red);
	}

	$im_nd = imagecreatefrompng('img/netdev.png');
	$im_n = imagecreatefrompng('img/node.png');

//	print_r($nodemap);
	
	foreach($nodemap as $nodeid => $node)
	{
		$celx = $node['x'];
		$cely = $node['y'];
		$px = (($celx * ($cellw)) + $celllmargin);
		$py = (($cely * ($cellh)) + $celltmargin);
		imagecopy($im,$im_n,$px,$py,0,0,15,16);
//		imagestring($im, 1, $px + 20, $py - 8, $nodeid.' ('.$celx.','.$cely.')', $blue);
		imagestring($im, 1, $px + 20, $py + 2, $DB->GetOne('SELECT name FROM nodes WHERE id=?',array($nodeid)), $black);
	}
				
		

	foreach($devicemap as $deviceid => $device)
	{
		$celx = $device['x'];
		$cely = $device['y'];
		$px = (($celx * ($cellw)) + $celllmargin);
		$py = (($cely * ($cellh)) + $celltmargin);
		imagecopy($im,$im_nd,$px,$py,0,0,16,16);
//		imagestring($im, 1, $px + 20, $py - 8, $deviceid.' ('.$celx.','.$cely.')', $blue);
		imagestring($im, 3, $px + 20, $py + 2, $DB->GetOne('SELECT name FROM netdevices WHERE id=?',array($deviceid)), $darkred);
	}
	
	
		
	imagepng($im);
}
?>
