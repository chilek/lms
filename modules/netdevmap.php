<?

/*
 * LMS version 1.1-cvs
 *
 *  (C) Copyright 2001-2003 LMS Developers
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
	$fields[] = array( 'x' => 0, 'y' => 1 );
	$fields[] = array( 'x' => 1, 'y' => 1 );     
	$fields[] = array( 'x' => 1, 'y' => 0 );
	$fields[] = array( 'x' => 1, 'y' => -1 );
	$fields[] = array( 'x' => 0, 'y' => -1 );
	$fields[] = array( 'x' => -1, 'y' => -1 );
	$fields[] = array( 'x' => -1, 'y' => 0 );
	$fields[] = array( 'x' => -1, 'y' => 1 );

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

		foreach($devices as $deviceid)
		{
			if(! $seen[$deviceid])
			{
				// tego urz±dzenia nie przerabiali¶my jeszcze
				// wyszukajmy wolny punkt w okolicy
				$tx = NULL;
				$ty = NULL;
				for($i=0;$i < sizeof($fields);$i++)
					if($tx == NULL && $ty == NULL && $map[$x + $fields[$i][x]][$y + $fields[$i][y]] == NULL)
					{
						$tx = $x + $fields[$i][x];
						$ty = $y + $fields[$i][y];
					}

				if($tx != NULL && $ty != NULL)
					makemap($DB, $map, $seen, $deviceid, $tx, $ty);
			}				
		}
	}
}

$layout[pagetitle] = "Mapa po³±czeñ sieciowych";
$SMARTY->assign('layout',$layout);

if($_GET[graph] == "")
{
	$SMARTY->assign('deviceslist',$DB->GetAll('SELECT id, name FROM netdevices ORDER BY name ASC'));
	$SMARTY->assign('start',$_GET[start]);
	$SMARTY->display('netdevmap.html');
}
else
{
	$start = sprintf('%d',$_GET[start]);
	makemap($DB,$map,$seen,$start);
/*	echo "<TABLE BORDER=1>";
	for($y=48; $y < 60; $y++)
	{
		echo '<TR>';
		for($x=48;$x < 60; $x++)
			if($map[$x][$y])
				echo '<TD>'.$map[$x][$y].'</TD>';
			else
				echo '<TD>&nbsp;</TD>';
		echo '</TR>';
	}
	echo "</TABLE>";
	echo "<PRE>";
	print_r($map);
	echo "</PRE>";*/
	
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

	header('Content-type: image/gif');
	
//	echo "minx;$minx;maxx;$maxx;";
//	echo "miny;$miny;maxy;$maxy;";
	$widthx = $maxx - $minx;
	$widthy = $maxy - $miny;
	$cellw = 200;
	$cellh = 50;
	$celltmargin = 20;
	$celllmargin = 20;
	$imgwx = ($cellw + 1) * ($widthx + 1);
	$imgwy = ($cellh + 1) * ($widthy + 1);

	$im = imagecreate($imgwx, $imgwy);

	$withe = imagecolorallocate($im, 255,255,255);
	$black = imagecolorallocate($im, 0,0,0);
	$red = imagecolorallocate($im, 255,0,0);
	$green = imagecolorallocate($im, 0,255,0);
	$blue = imagecolorallocate($im, 0,0,255);

	foreach($map as $idx => $x)
	{
		foreach($x as $idy => $device)
		{
		//	echo "$idx/$idy/$device<BR>";
			$celx = $idx - $minx;
			$cely = $idy - $miny;
			$px = (($celx * ($cellw + 1)) + $celllmargin);
			$py = (($cely * ($cellh + 1)) + $celltmargin);
			$devicemap[$device][x] = $celx;
			$devicemap[$device][y] = $cely;
			imagesetpixel($im, $px, $py, $red);
			imagestring($im, 3, $px + 5, $py - 5, $device, $blue);
			imagestring($im, 3, $px + 5, $py - 15, $DB->GetOne('SELECT name FROM netdevices WHERE id=?',array($device)), $black);
		}
	}

	$links = $DB->GetAll('SELECT src, dst FROM netlinks');
	foreach($links as $link)
	{
		$src_celx = $devicemap[$link[src]][x];
		$src_cely = $devicemap[$link[src]][y];
		$dst_celx = $devicemap[$link[dst]][x];
		$dst_cely = $devicemap[$link[dst]][y];
		$src_px = (($src_celx * ($cellw + 1)) + $celllmargin);
		$src_py = (($src_cely * ($cellh + 1)) + $celltmargin);
		$dst_px = (($dst_celx * ($cellw + 1)) + $celllmargin);
		$dst_py = (($dst_cely * ($cellh + 1)) + $celltmargin);
		imageline($im, $src_px+1, $src_py+1, $dst_px+1, $dst_py+1, $green);
	}
		
		
	imagegif($im);
}
?>
