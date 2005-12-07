<?php

/*
 * LMS version 1.9-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

function drawtext($x, $y, $text, $r, $g, $b)
{
	global $m, $font, $CONFIG;

	if(!$text) return;

	if($CONFIG['phpui']['gd_translate_to'])
		$text = iconv('UTF-8', $CONFIG['phpui']['gd_translate_to'], $text);

	// remove special characters because we haven't got proper font
	// or something else. I don't know what, but we have a problem with 
	// that characters on flash map.
	if(strtoupper($CONFIG['phpui']['gd_translate_to'])=='ISO-8859-2')
	{
		// for Polish diacritical chars
		$from = array('±','¶','ê','¿','¼','æ','ñ','ó','³','¡','¦','Ê','¯','¬','Æ','Ñ','Ó','£');
		$to   = array('a','s','e','z','z','c','n','o','l','A','S','E','Z','Z','C','N','O','L');
		$text = str_replace($from, $to, $text);
	}
	
	$t = new SWFTextField(SWFTEXTFIELD_NOEDIT | SWFTEXTFIELD_NOSELECT);
	$t->setFont($font);
	$t->setHeight(8);
	$t->setColor($r, $g, $b);
	$t->addString($text);
	$i = $m->add($t);
	$i->moveTo($x, $y);	
}

function pngdrawtext($image, $font, $x, $y, $text, $color, $bgcolor)
{
	global $CONFIG;
	if($CONFIG['phpui']['gd_translate_to'])
		$text = iconv('UTF-8', $CONFIG['phpui']['gd_translate_to'], $text);
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
		if($device = $DB->GetOne('SELECT id FROM netdevices ORDER BY id ASC'))
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
			if(!isset($seen[$deviceid]))
			{
				// tego urz±dzenia nie przerabiali¶my jeszcze
				// wyszukajmy wolny punkt w okolicy
				$tx = NULL;
				$ty = NULL;
				for($i=0;$i < sizeof($fields);$i++)
					if($tx == NULL && $ty == NULL && !isset($map[$x + $fields[$i]['x']][$y + $fields[$i]['y']]))
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
					if($ntx == NULL && $nty == NULL && !isset($map[$x + $nodefields[$i]['x']][$y + $nodefields[$i]['y']]))
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

$layout['pagetitle'] = trans('Network Map');

$graph = isset($_GET['graph']) ? $_GET['graph'] : '';
$start = isset($_GET['start']) ? $_GET['start'] : 0;

$minx = 0; $maxx = 0; $miny = 0; $maxy = 0;

if($graph == '')
{
	makemap($DB,$map,$seen,$start);
	if($map)
	{
		foreach($map as $idx => $x)
		{
			if(!$minx)
				$minx = $idx;
			elseif($idx < $minx)
				$minx = $idx;
		
			if($idx > $maxx)
				$maxx = $idx;
			foreach($x as $idy => $y)
			{
				if(!$miny)
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
	}
	
	$deviceslist = $DB->GetAll('SELECT id, name FROM netdevices ORDER BY name ASC');
	$SMARTY->assign('devicemap', $devicemap);
	$SMARTY->assign('nodemap', $nodemap);
	$SMARTY->assign('deviceslist', $deviceslist);
	$SMARTY->assign('start', $start);
	$SMARTY->assign('type', strtolower($CONFIG['phpui']['map_type']));
	$SMARTY->assign('emptydb', sizeof($deviceslist) ? FALSE : TRUE);
	$SMARTY->assign('gd', function_exists('imagepng'));
	$SMARTY->assign('ming', function_exists('ming_useswfversion'));
	$SMARTY->display('netdevmap.html');
} 
elseif ($graph == 'flash')
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

	Ming_setScale(20.0);
	ming_useswfversion(5);
	$m = new SWFMovie();
	$m->setDimension($imgwx, $imgwy);
	$font = new SWFFont("img/Arial.fdb");
	$connections = new SWFShape();

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
			$connections->setLine(1, 0,128,0);
			$connections->movePenTo($src_px+8, $src_py+8);
			$connections->drawLineTo($dst_px+8, $dst_py+8);
		} 
		else 
		{
			$connections->setLine(1, 0,200,255);
			$connections->movePenTo($src_px+8, $src_py+8);
			$connections->drawLineTo($dst_px+8, $dst_py+8);
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
		if($node['linktype']=="0") {
			$connections->setLine(1, 255,0,0);
			$connections->movePenTo($src_px+4, $src_py+4);
			$connections->drawLineTo($dst_px+4, $dst_py+4);
		} else {
			$connections->setLine(1, 0,200,255);
			$connections->movePenTo($src_px+4, $src_py+4);
			$connections->drawLineTo($dst_px+4, $dst_py+4);
		}
	}

	$m->add($connections);

	$im_n_unk = new SWFBitmap(fopen("img/node_unk.jpg","rb"));
	$im_n_off = new SWFBitmap(fopen("img/node_off.jpg","rb"));
	$im_n_on  = new SWFBitmap(fopen("img/node_on.jpg","rb"));
	$im_d_unk = new SWFBitmap(fopen("img/netdev_unk.jpg","rb"));
	$im_d_off = new SWFBitmap(fopen("img/netdev_off.jpg","rb"));
	$im_d_on  = new SWFBitmap(fopen("img/netdev_on.jpg","rb"));
	
	$nodes = $DB->GetAllByKey('SELECT id, name, INET_NTOA(ipaddr) AS ip, lastonline FROM nodes', 'id');

	if($nodemap) foreach($nodemap as $nodeid => $node)
	{
		$button = new SWFButton();
		$squareshape=new SWFShape();
		$celx = $node['x'];
		$cely = $node['y'];
		$px = (($celx * ($cellw)) + $celllmargin);
		$py = (($cely * ($cellh)) + $celltmargin);

		$n = $nodes[$nodeid];
		
		if ($n['lastonline']) {	
			if ((time()-$n['lastonline'])>$LMS->CONFIG['phpui']['lastonline_limit']) {
				$myfill = $squareshape->addFill($im_n_off,SWFFILL_TILED_BITMAP);
			} else {
				$myfill = $squareshape->addFill($im_n_on,SWFFILL_TILED_BITMAP);
			}
		} else {
			$myfill = $squareshape->addFill($im_n_unk,SWFFILL_TILED_BITMAP);
		}
		$myfill->scaleto(9,9);
		$squareshape->setRightFill($myfill);
		$squareshape->drawLine(15,0);  
		$squareshape->drawLine(0,15); 
		$squareshape->drawLine(-15,0); 
		$squareshape->drawLine(0,-15); 
		$button->addShape($squareshape, SWFBUTTON_HIT | SWFBUTTON_UP | SWFBUTTON_DOWN | SWFBUTTON_OVER); 
		$button->addAction(new SWFAction("this.getURL('?m=nodeinfo&id=".$nodeid."');"), SWFBUTTON_MOUSEDOWN); // press
		$i=$m->add($button);
		$i->moveTo($px,$py);
		
		drawtext($px + 15, $py - 4, $n['ip'], 0, 0, 255);
		drawtext($px + 15, $py + 10, $n['name'], 0, 0, 0); 
	}

	$devices = $DB->GetAllByKey('SELECT netdevices.id AS id, netdevices.name AS name, location, MAX(lastonline) AS lastonline 
				    FROM netdevices LEFT JOIN nodes ON (netdevices.id = netdev)
				    GROUP BY netdevices.id, netdevices.name, location', 'id');

	foreach($devicemap as $deviceid => $device)
	{
		$button = new SWFButton();
		$squareshape=new SWFShape(); 
		$celx = $device['x'];
		$cely = $device['y'];
		$px = (($celx * ($cellw)) + $celllmargin);
		$py = (($cely * ($cellh)) + $celltmargin);
		
		$d = $devices[$deviceid];
		
		if ($d['lastonline']) 
		{	
			if ((time()-$d['lastonline'])>$LMS->CONFIG['phpui']['lastonline_limit']) {
				$myfill = $squareshape->addFill($im_d_off,SWFFILL_TILED_BITMAP);
			} else {
				$myfill = $squareshape->addFill($im_d_on,SWFFILL_TILED_BITMAP);
			}
		} else {
			$myfill = $squareshape->addFill($im_d_unk,SWFFILL_TILED_BITMAP);
		}
		
		$myfill->scaleto(9,9);
		$squareshape->setRightFill($myfill);
		$squareshape->drawLine(15,0);  
		$squareshape->drawLine(0,15); 
		$squareshape->drawLine(-15,0); 
		$squareshape->drawLine(0,-15); 
		$button->addShape($squareshape, SWFBUTTON_HIT | SWFBUTTON_UP | SWFBUTTON_DOWN | SWFBUTTON_OVER); 
		$button->addAction(new SWFAction("this.getURL('?m=netdevinfo&id=".$deviceid."');"), SWFBUTTON_MOUSEDOWN); // press
		$i=$m->add($button);
		$i->moveTo($px,$py);

		$devip = $DB->GetCol('SELECT INET_NTOA(ipaddr) FROM nodes WHERE ownerid=0 AND netdev=? ORDER BY ipaddr LIMIT 4', array($deviceid));
		if($devip[0]) drawtext($px + 16, $py - ($devip[1]?16:8), $devip[0], 0,0,255);
		if($devip[1]) drawtext($px + 16, $py - 8, $devip[1], 0,0,255);
		if($devip[2]) drawtext($px + 16, $py + 16, $devip[2], 0,0,255);
		if($devip[3]) drawtext($px + 16, $py + 24, $devip[3], 0,0,255);
		
		drawtext($px + 16, $py + 0, $d['name'], 0,0,0);
		drawtext($px + 16, $py + 8, $d['location'], 0,128,0); 
	}
		
	header("Content-type: application/x-shockwave-flash");
	// Note: this line avoids a bug in InternetExplorer that won't allow
	// downloads over https
	header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: public");	
	$m->output();
} else {
	makemap($DB,$map,$seen,$start);
	foreach($map as $idx => $x)
	{
		if(!$minx)
			$minx = $idx;
		elseif($idx < $minx)
			$minx = $idx;
		
		if($idx > $maxx)
			$maxx = $idx;
		foreach($x as $idy => $y)
		{
			if(!$miny)
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

	$nodes = $DB->GetAllByKey('SELECT id, name, INET_NTOA(ipaddr) AS ip, lastonline FROM nodes', 'id');

	if($nodemap) foreach($nodemap as $nodeid => $node)
	{
		$celx = $node['x'];
		$cely = $node['y'];
		$px = (($celx * ($cellw)) + $celllmargin);
		$py = (($cely * ($cellh)) + $celltmargin);

		$n = $nodes[$nodeid];

		if ($n['lastonline']) {	
			if ((time()-$n['lastonline'])>$LMS->CONFIG['phpui']['lastonline_limit'])
				imagecopy($im,$im_n_off,$px,$py,0,0,16,16);
			else 
				imagecopy($im,$im_n_on,$px,$py,0,0,16,16);
		} else 
			imagecopy($im,$im_n_unk,$px,$py,0,0,16,16);
		
		pngdrawtext($im, 1, $px + 15, $py - 8, $n['ip'], $blue, $lightbrown);
		pngdrawtext($im, 1, $px + 15, $py + 2, $n['name'], $black, $lightbrown);
	}

	$devices = $DB->GetAllByKey('SELECT netdevices.id AS id, netdevices.name AS name, location, MAX(lastonline) AS lastonline 
				    FROM netdevices LEFT JOIN nodes ON (netdevices.id = netdev)
				    GROUP BY netdevices.id, netdevices.name, location', 'id');

	foreach($devicemap as $deviceid => $device)
	{
		$celx = $device['x'];
		$cely = $device['y'];
		$px = (($celx * ($cellw)) + $celllmargin);
		$py = (($cely * ($cellh)) + $celltmargin);

		$d = $devices[$deviceid];
		
		if ($d['lastonline']) {	
			if ((time()-$d['lastonline'])>$LMS->CONFIG['phpui']['lastonline_limit'])
				imagecopy($im,$im_d_off,$px,$py,0,0,16,16);
			else 
				imagecopy($im,$im_d_on,$px,$py,0,0,16,16);
		} else 
			imagecopy($im,$im_d_unk,$px,$py,0,0,16,16);
		
		$devip = $DB->GetCol('SELECT INET_NTOA(ipaddr) FROM nodes WHERE ownerid=0 AND netdev=? ORDER BY ipaddr LIMIT 4', array($deviceid));
		if($devip[0]) pngdrawtext($im, 1, $px + 20, $py - ($devip[1]?17:8), $devip[0], $blue, $lightbrown);
		if($devip[1]) pngdrawtext($im, 1, $px + 20, $py - 8, $devip[1], $blue, $lightbrown);
		if($devip[2]) pngdrawtext($im, 1, $px + 20, $py + 17, $devip[2], $blue, $lightbrown);
		if($devip[3]) pngdrawtext($im, 1, $px + 20, $py + 26, $devip[3], $blue, $lightbrown);
		
		pngdrawtext($im, 3, $px + 20, $py + 2, $d['name'], $black, $lightbrown);
		pngdrawtext($im, 2, $px + 20, $py + 18, $d['location'], $green, $lightbrown);
	}
		
	imagepng($im);
}

?>
