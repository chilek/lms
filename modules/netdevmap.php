<?php

/*
 * LMS version 1.5-cvs
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
 */

function drawtext($image, $font, $x, $y, $text, $color, $bgcolor)
{
	imagestring($image, $font, $x + 1, $y + 1, $text, $bgcolor);
	imagestring($image, $font, $x + 1, $y - 1, $text, $bgcolor);
	imagestring($image, $font, $x - 1, $y + 1, $text, $bgcolor);
	imagestring($image, $font, $x - 1, $y - 1, $text, $bgcolor);
	imagestring($image, $font, $x, $y, $text, $color);
}

function makemap(&$DB, &$map, &$seen, $device = 0, $x = 50, $y = 50)
{
	$fields[] = array( 'x' => 0, 'y' => 5 );
	$fields[] = array( 'x' => 5, 'y' => 5 );     
	$fields[] = array( 'x' => 5, 'y' => 0 );
	$fields[] = array( 'x' => 5, 'y' => -5 );
	$fields[] = array( 'x' => 0, 'y' => -5 );
	$fields[] = array( 'x' => -5, 'y' => -5 );
	$fields[] = array( 'x' => -5, 'y' => 0 );
	$fields[] = array( 'x' => -5, 'y' => 5 );
	$fields[] = array( 'x' => 5, 'y' => 10 );
	$fields[] = array( 'x' => 5, 'y' => -10 );
	$fields[] = array( 'x' => -5, 'y' => 10 );
	$fields[] = array( 'x' => -5, 'y' => -10 );
	$fields[] = array( 'x' => 10, 'y' => 5 );
	$fields[] = array( 'x' => 10, 'y' => -5 );
	$fields[] = array( 'x' => -10, 'y' => 5 );
	$fields[] = array( 'x' => -10, 'y' => -5 );
	$fields[] = array( 'x' => 5, 'y' => 15 );
	$fields[] = array( 'x' => 5, 'y' => -15 );
	$fields[] = array( 'x' => -5, 'y' => 15 );
	$fields[] = array( 'x' => -5, 'y' => -15 );
	$fields[] = array( 'x' => 10, 'y' => 15 );
	$fields[] = array( 'x' => 10, 'y' => -15 );
	$fields[] = array( 'x' => -10, 'y' => 15 );
	$fields[] = array( 'x' => -10, 'y' => -15 );

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

		if($nodes = $DB->GetAll("SELECT id, linktype FROM nodes WHERE netdev=? AND ownerid>0 ORDER BY name ASC",array($device)))
		{
			foreach($nodes as $node)
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
					$map[$ntx][$nty] = 'n'.$node['id'].'.'.$device.'.'.$node['linktype'];
			}
		}
	}
}

$layout['pagetitle'] = "Mapa po³±czeñ sieciowych";

$start = sprintf('%d',$_GET['start']);
	
if($_GET['graph'] == "")
{
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

	$widthx = $maxx - $minx;
	$widthy = $maxy - $miny;
	$cellw = 70;
	$cellh = 30;
	$celltmargin = 20;
	$celllmargin = 10;
	$imgwx = $cellw * ($widthx + 2);
	$imgwy = $cellh * ($widthy + 2);

	foreach($map as $idx => $x)
	{
		foreach($x as $idy => $device)
		{
			$celx = $idx - $minx;
			$cely = $idy - $miny;
			if(eregi('^n',$device))
			{
				$device = str_replace('n','',$device);
				list($nodeid,$device,$linktype) = explode('.',$device);
				$nodemap[$nodeid]['x'] = (($celx * ($cellw)) + $celllmargin) +4;
				$nodemap[$nodeid]['y'] = (($cely * ($cellh)) + $celltmargin) +4;
				$nodemap[$nodeid]['id'] = $nodeid;
				$nodemap[$nodeid]['linktype'] = $linktype;
			}
			else
			{
				$devicemap[$device]['x'] = (($celx * ($cellw)) + $celllmargin) +4;
				$devicemap[$device]['y'] = (($cely * ($cellh)) + $celltmargin) +4;
				$devicemap[$device]['id'] = $device;
			}
		}
	}
	if(sizeof($nodemap)) sort($nodemap);
	sort($devicemap);

	$SMARTY->assign('devicemap',$devicemap);
	$SMARTY->assign('nodemap',$nodemap);
	$SMARTY->assign('deviceslist',$DB->GetAll('SELECT id, name FROM netdevices ORDER BY name ASC'));
	$SMARTY->assign('gderror', ! function_exists('imagepng'));
	$SMARTY->assign('start',$_GET['start']);
	$SMARTY->display('netdevmap.html');
} 
else
{	
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
	$celltmargin = 20;
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
	$lightblue = imagecolorallocate($im, 0,200,255);
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
				list($nodeid,$device,$linktype) = explode('.',$device);
				$nodemap[$nodeid]['x'] = $celx;
				$nodemap[$nodeid]['y'] = $cely;
				$nodemap[$nodeid]['device'] = $device;
				$nodemap[$nodeid]['linktype'] = $linktype;
			}
			else
			{
				$devicemap[$device]['x'] = $celx;
				$devicemap[$device]['y'] = $cely;
			}
		}
	}

	$links = $DB->GetAll('SELECT src, dst, type FROM netlinks');
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
		if(! $link['type'])
		{
			imageline($im, $src_px+8, $src_py+8, $dst_px+8, $dst_py+8, $green);
			imageline($im, $src_px+9, $src_py+9, $dst_px+9, $dst_py+9, $green);
		} 
		else 
		{
			imageline($im, $src_px+8, $src_py+8, $dst_px+8, $dst_py+8, $lightblue);
			imageline($im, $src_px+9, $src_py+9, $dst_px+9, $dst_py+9, $lightblue);
		}
	}

	if($nodemap) foreach($nodemap as $node)
	{
		$src_celx = $node['x'];
		$src_cely = $node['y'];
		$dst_celx = $devicemap[$node['device']]['x'];
		$dst_cely = $devicemap[$node['device']]['y'];
		$src_px = (($src_celx * $cellw) + $celllmargin);
		$src_py = (($src_cely * $cellh) + $celltmargin);
		$dst_px = (($dst_celx * $cellw) + $celllmargin);
		$dst_py = (($dst_cely * $cellh) + $celltmargin);
		if($node['linktype']=="0")
			imageline($im, $src_px+4, $src_py+4, $dst_px+4, $dst_py+4, $red);
		else
			imageline($im, $src_px+4, $src_py+4, $dst_px+4, $dst_py+4, $lightblue);
	}

	$im_n_unk = imagecreatefrompng('img/node_unk.png');
	$im_n_off = imagecreatefrompng('img/node_off.png');
	$im_n_on = imagecreatefrompng('img/node_on.png');
	$im_d_unk = imagecreatefrompng('img/netdev_unk.png');
	$im_d_off = imagecreatefrompng('img/netdev_off.png');
	$im_d_on = imagecreatefrompng('img/netdev_on.png');

	if($nodemap) foreach($nodemap as $nodeid => $node)
	{
		$celx = $node['x'];
		$cely = $node['y'];
		$px = (($celx * ($cellw)) + $celllmargin);
		$py = (($cely * ($cellh)) + $celltmargin);
		$nodedata = $DB->GetRow('SELECT name, INET_NTOA(ipaddr) AS ip, lastonline FROM nodes WHERE id=?',array($nodeid));
		if ($nodedata['lastonline']) {	
			if ((time()-$nodedata['lastonline'])>$LMS->CONFIG['phpui']['lastonline_limit'])
				imagecopy($im,$im_n_off,$px,$py,0,0,15,16);
			else 
				imagecopy($im,$im_n_on,$px,$py,0,0,15,16);
		} else 
			imagecopy($im,$im_n_unk,$px,$py,0,0,15,16);
		
		drawtext($im, 1, $px + 15, $py - 8, $nodedata['ip'], $blue, $lightbrown);
		drawtext($im, 1, $px + 15, $py + 2, $nodedata['name'], $black, $lightbrown);
	}

	foreach($devicemap as $deviceid => $device)
	{
		$celx = $device['x'];
		$cely = $device['y'];
		$px = (($celx * ($cellw)) + $celllmargin);
		$py = (($cely * ($cellh)) + $celltmargin);
		
		$lastonline = $DB->GetOne('SELECT MAX(lastonline) FROM nodes WHERE ownerid=0 AND netdev=?', array($deviceid));
		if ($lastonline) {	
			if ((time()-$lastonline)>$LMS->CONFIG['phpui']['lastonline_limit'])
				imagecopy($im,$im_d_off,$px,$py,0,0,16,16);
			else 
				imagecopy($im,$im_d_on,$px,$py,0,0,16,16);
		} else 
			imagecopy($im,$im_d_unk,$px,$py,0,0,16,16);
		
		$devip = $DB->GetCol('SELECT INET_NTOA(ipaddr) FROM nodes WHERE ownerid=0 AND netdev=? ORDER BY ipaddr LIMIT 4', array($deviceid));
		if($devip[0]) drawtext($im, 1, $px + 20, $py - ($devip[1]?17:8), $devip[0], $blue, $lightbrown);
		if($devip[1]) drawtext($im, 1, $px + 20, $py - 8, $devip[1], $blue, $lightbrown);
		if($devip[2]) drawtext($im, 1, $px + 20, $py + 17, $devip[2], $blue, $lightbrown);
		if($devip[3]) drawtext($im, 1, $px + 20, $py + 26, $devip[3], $blue, $lightbrown);
		
		drawtext($im, 3, $px + 20, $py + 2, $DB->GetOne('SELECT name FROM netdevices WHERE id=?',array($deviceid)), $black, $lightbrown);
		drawtext($im, 2, $px + 20, $py + 18, $DB->GetOne('SELECT location FROM netdevices WHERE id=?',array($deviceid)), $green, $lightbrown);
	}
		
	imagepng($im);
}
?>
