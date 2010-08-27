<?php

/* 
 *  LMS version 1.11-cvs 
 *
 *  (C) Copyright 2001-2010 LMS Developers
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

if(!function_exists('imagecreate'))
	die;

define('GRAPH_HEIGHT', 180);
define('GRAPH_WIDTH', 500);

function TrafficGraph ($nodeid, $net=NULL, $customer=NULL, $bar=NULL, $fromdate=NULL, $todate=NULL, $add=NULL)
{
	global $LMS;

	// image size
	$ymax = GRAPH_HEIGHT;
	$xmax = GRAPH_WIDTH;
	// graph offset
	$movx = 80;
	$movy = 135;
	// graph size
	$graph_height = 100;
	$graph_width = 400;

	$todate = $todate ? $todate : time();

	switch($bar)
	{
		case 'hour':
			$quantum = 60*60;
			$divisor = $LMS->CONFIG['phpui']['stat_freq'] ? (int) (60/$LMS->CONFIG['phpui']['stat_freq']) : 5;
			$fromdate = $todate - $quantum;
		break;
		case 'day':
			$quantum = 60*60*24;
			$divisor = 100;
			$fromdate = $todate - $quantum;
		break;
		case 'week':
			$quantum = 60*60*24*7;
			$divisor = 100;
			$fromdate = $todate - $quantum;
		break;
		case 'month':
			$quantum = 60*60*24*30;
			$divisor = 200;
			$fromdate = $todate - $quantum;
		break;
		case 'year': 
			$quantum = 60*60*24*365;
			$divisor = 400;
			$fromdate = $todate - $quantum;
		break;
		default: 
			$fromdate = $fromdate ? $fromdate : $todate - 60*60*24*30;
			$quantum = $todate - $fromdate;
			if($quantum < 60*60)
				$divisor = 5;
			elseif($quantum < 60*60*24)
				$divisor = 50;
			elseif($quantum < 60*60*24*7)
				$divisor = 100;
			elseif($quantum < 60*60*24*30)
				$divisor = 200;
			else
				$divisor = 400;
		break;
	}

    if ($add) {
        $fromdate += $add;
        $todate += $add;
    }

	$div = 10;
	$qdivisor = (int) ($quantum/$divisor);

	if($nodeid)
	{
		$node = $LMS->DB->GetRow('SELECT name, INET_NTOA(ipaddr) AS ip
			FROM nodes WHERE id = ?', array($nodeid));

		$stats = $LMS->DB->GetAll('SELECT SUM(upload) AS upload,
				SUM(download) AS download,
				CEIL(dt/?) AS dts
			FROM stats WHERE nodeid = ? AND dt >= ? AND dt <= ?
			GROUP BY CEIL(dt/?) ORDER BY dts ASC',
			array($qdivisor, $nodeid, $fromdate-$qdivisor, $todate+$qdivisor, $qdivisor));
	}
	else
	{
		if($net)
	        {
	                $params = $LMS->GetNetworkParams($net);
			if($params)
			{
	        	        $params['address']++;
	            		$params['broadcast']--;
	            		$net = ' AND (( ipaddr > '.$params['address'].' AND ipaddr < '.$params['broadcast'].')
		            		OR ( ipaddr_pub > '.$params['address'].' AND ipaddr_pub < '.$params['broadcast'].')) ';
	    		}
			else
				$net = '';
		}
		else
			$net = '';

		$stats = $LMS->DB->GetAll('SELECT SUM(upload) AS upload,
			SUM(download) AS download, dts
			FROM (
			    SELECT SUM(upload) AS upload, SUM(download) AS download,
			    CEIL(dt/?) AS dts
			    FROM stats '
			    .($customer || $net ? 'JOIN nodes ON stats.nodeid = nodes.id ' : '')
			    .'WHERE dt >= ? AND dt <= ? '
			    .($customer ? ' AND ownerid = '.intval($customer) : '')
			    .$net
			    .'GROUP BY CEIL(dt/?), nodeid) x
			GROUP BY dts ORDER BY dts',
			array($qdivisor, $fromdate-$qdivisor, $todate+$qdivisor, $qdivisor));
	}

	$down_max = $up_max = 0;
	$last_up = $last_down = 0;
	$avg_up = $avg_down = 0;
	$sum_up = $sum_down = 0;
	$dstart = (int) ($fromdate/$qdivisor);

	if ($stats) foreach($stats as $idx => $row)
	{
		$i = $row['dts'] - $dstart;
		$vstats[$i]['download'] = $row['download']*8/($quantum/$divisor);
		$vstats[$i]['upload'] = $row['upload']*8/($quantum/$divisor);

		if($vstats[$i]['download'] > $down_max)
			$down_max = $vstats[$i]['download'];
		if($vstats[$i]['upload'] > $up_max)
			$up_max = $vstats[$i]['upload'];

   		$sum_down += $row['download']*8;
    	$sum_up += $row['upload']*8;

		unset($stats[$idx]);
	}

	$avg_down = $sum_down/$quantum;
	$avg_up = $sum_up/$quantum;
	$stats_max = max($down_max, $up_max);

	// create image
	$img = imagecreate($xmax, $ymax);

	// color palette
	$background = imagecolorallocate($img,240,240,240);
	$textcolor = imagecolorallocate($img,0,0,0);
	$downloadcolor = imagecolorallocate($img,255,0,0);
	$uploadcolor = imagecolorallocate($img,0,0,255);
	$blendcolor = imagecolorallocate($img,192,192,192);

	imagesetthickness($img, 1);

	// image borders
	imageline($img,0,0,$xmax,0,$textcolor);
	imageline($img,0,$ymax-1,$xmax,$ymax-1,$textcolor);
	imageline($img,0,0,0,$ymax,$textcolor);
	imageline($img,$xmax-1,0,$xmax-1,$ymax,$textcolor);

	$styleline = array($textcolor,IMG_COLOR_TRANSPARENT,IMG_COLOR_TRANSPARENT);
	imagesetstyle($img, $styleline);

	$downx = $upx = $movx;
	$downy = $upy = $movy;

	for($x=0; $x<=$divisor; $x++)
	{
		if(isset($vstats[$x]))
		{
			$down = $vstats[$x]['download'];
			$up = $vstats[$x]['upload'];
			$download = round($graph_height*($down/$stats_max));
			$upload = round($graph_height*($up/$stats_max));
		}
		else
		{
			$down = $up = $download = $upload = 0;
		}

		$posx = ceil($x * $graph_width/$divisor);

		// download
		imageline($img,$downx,$downy,$movx+$posx,$movy-$download,$downloadcolor);
		// upload
		imageline($img,$upx,$upy,$movx+$posx,$movy-$upload,$uploadcolor);

		$downx = $upx = $movx+$posx;
		$downy = $movy-$download;
		$upy = $movy-$upload;

		$last_down = $down;
		$last_up = $up;
	}

	// horizontal scale
	for($i=0; $i<$div+1; $i++)
	{
		$posx = ceil($i * $graph_width/$div);

		switch($bar)
		{
			case 'week':
			case 'month':
			case 'year':
				$str = strftime('%d/%b', $fromdate + $i * ceil($quantum/$div));
			break;
			case 'day':
			case 'hour':
				$str = date('H:i', $fromdate + $i * ceil($quantum/$div));
			break;
			default:
				if(($quantum) > 60*60*24)
					$str = strftime('%d/%b', $fromdate + $i * ceil($quantum/$div));
				else
					$str = date('H:i', $fromdate + $i * ceil($quantum/$div));
			break;
		}

		imageline($img,$movx+$posx,$movy-$graph_height,$movx+$posx,$movy, IMG_COLOR_STYLED);
		imageline($img,$movx+$posx,$movy-1,$movx+$posx,$movy+2,$textcolor);
		$posx -= ceil(imagefontwidth(1) * strlen($str)/2);
		imagestring($img, 1, $movx+$posx+1, $movy+5, iconv('UTF-8','ISO-8859-2//TRANSLIT', $str), $textcolor);
	}

	// graph outlines
	// vertical
	imageline($img,$movx,$movy,$movx,$movy-$graph_height,$textcolor);
	imageline($img,$movx+$graph_width,$movy,$movx+$graph_width,$movy-$graph_height,$textcolor);
	// horizontal
//	imageline($img,$movx-2,$movy+1,$movx+$graph_width+3,$movy+1,$textcolor);
//	imageline($img,$movx-2,10,$movx+$graph_width+3,10,$textcolor);

	if ($stats_max/(1000*1000) > 10)
	{
		$vdiv = 1000*1000;
		$suffix = 'Mbit/s';
	}
	else if ($stats_max/1000 > 1)
	{
		$vdiv = 1000;
		$suffix = 'kbit/s';
	}
	else
	{
		$vdiv = 1;
		$suffix = 'bit/s';
	}

	$n=0;
	$stats_max = round($stats_max/$vdiv);

	// vertical axis labels and graph lines
	for($i=0; $i<=4; $i++)
	{
		$val = round((4-$i) * $stats_max/4);
		$str = str_pad($val.' '.$suffix, round(($movx-15)/imagefontwidth(1)), ' ', STR_PAD_LEFT);
		$posy = round(($i * $graph_height)/4);
		imageline($img,$movx-2,$movy-$graph_height+$posy,$movx+2,$movy-$graph_height+$posy,$textcolor);
		imageline($img,$movx-2+$graph_width,$movy-$graph_height+$posy,$movx+2+$graph_width,$movy-$graph_height+$posy,$textcolor);
		if ($i<4)
			imageline($img,$movx+4,$movy-$graph_height+$posy,$movx-4+$graph_width,$movy-$graph_height+$posy, IMG_COLOR_STYLED);
		else
			imageline($img,$movx,$movy-$graph_height+$posy,$movx+$graph_width,$movy-$graph_height+$posy,$textcolor);
		imagestring($img,1,10,$movy-$graph_height-4+$posy,$str,$textcolor);
	}

	// title
	if ($nodeid) {
		if ($node)
			$title = $node['name'].' - '.$node['ip'];
		else
			$title = iconv('UTF-8','ISO-8859-2//TRANSLIT', trans('unknown')).' (ID: '.$nodeid.')';
	} else
		$title =  iconv('UTF-8','ISO-8859-2//TRANSLIT', trans('Network Statistics'));

	$center = ceil((imagesx($img) - (imagefontwidth(3) * strlen($title)))/2);
	imagestring($img, 3, $center, 8, $title, $textcolor);

    // time period title
    $title = date('Y/m/d H:i', $fromdate).' - '.date('Y/m/d H:i', $todate);
	$center = ceil((imagesx($img) - (imagefontwidth(1) * strlen($title)))/2);
	imagestring($img, 1, $center, 23, $title, $textcolor);

	// summaries
	imagestring($img, 2, 10, $ymax-30, iconv('UTF-8','ISO-8859-2//TRANSLIT', trans('DOWNLOAD')), $downloadcolor);
	imagestring($img, 2, 10, $ymax-18, iconv('UTF-8','ISO-8859-2//TRANSLIT', trans('UPLOAD')), $uploadcolor);
	imagestring($img, 2, 70, $ymax-30, iconv('UTF-8','ISO-8859-2//TRANSLIT', trans('MAX:')).' '.str_pad(round($down_max/$vdiv),7,' ',STR_PAD_LEFT).' '.$suffix, $downloadcolor);
	imagestring($img, 2, 70, $ymax-18, iconv('UTF-8','ISO-8859-2//TRANSLIT', trans('MAX:')).' '.str_pad(round($up_max/$vdiv),7,' ',STR_PAD_LEFT).' '.$suffix, $uploadcolor);
	imagestring($img, 2, 195, $ymax-30, iconv('UTF-8','ISO-8859-2//TRANSLIT', trans('AVG:')).' '.str_pad(floor($avg_down/$vdiv),7,' ',STR_PAD_LEFT).' '.$suffix, $downloadcolor);
	imagestring($img, 2, 195, $ymax-18, iconv('UTF-8','ISO-8859-2//TRANSLIT', trans('AVG:')).' '.str_pad(floor($avg_up/$vdiv),7,' ',STR_PAD_LEFT).' '.$suffix, $uploadcolor);
	imagestring($img, 2, 345, $ymax-30, iconv('UTF-8','ISO-8859-2//TRANSLIT', trans('LAST:')).' '.str_pad(round($last_down/$vdiv),7,' ',STR_PAD_LEFT).' '.$suffix, $downloadcolor);
	imagestring($img, 2, 345, $ymax-18, iconv('UTF-8','ISO-8859-2//TRANSLIT', trans('LAST:')).' '.str_pad(round($last_up/$vdiv),7,' ',STR_PAD_LEFT).' '.$suffix, $uploadcolor);

	imagepng($img);
	imagedestroy($img);
}

$nodeid = isset($_GET['nodeid']) ? $_GET['nodeid'] : 0;
$bar = isset($_GET['bar']) ? $_GET['bar'] : NULL;
$from = isset($_GET['from']) ? $_GET['from'] : NULL;
$to = isset($_GET['to']) ? $_GET['to'] : NULL;
$customer = isset($_GET['customer']) ? $_GET['customer'] : NULL;
$net = isset($_GET['net']) ? $_GET['net'] : NULL;
$add = isset($_GET['add']) ? $_GET['add'] : NULL;

if(empty($_GET['popup']))
{
	header('Content-type: image/png');
	TrafficGraph($nodeid, $net, $customer, $bar, $from, $to, $add);
	die;
}

$SMARTY->assign('nodeid', $nodeid);
$SMARTY->assign('bar', $bar);
$SMARTY->assign('to', $to);
$SMARTY->assign('from', $from);
$SMARTY->assign('add', $add);
$SMARTY->assign('customer', $customer);
$SMARTY->assign('net', $net);
$SMARTY->display('trafficgraph.html');

?>
