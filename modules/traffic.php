<?
/* LMS version 1.1-cvs
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
 */

$layout[pagetitle]="Statystyki wykorzystania ³±cza";

$bars = 1;
switch($_GET['bar'])
{
  case "hour":
	$traffic = $LMS->Traffic( "?NOW?-(60*60)", "?NOW?", 0, "download");
	break;
  case "day":
	$traffic = $LMS->Traffic( "?NOW?-(60*60*24)","?NOW?",  0, "download");
	break;
  case "month":
	$traffic = $LMS->Traffic( "?NOW?-(60*60*24*30)", "?NOW?", 0, "download");
	break;
  case "year":
	$traffic = $LMS->Traffic( "?NOW?-(60*60*24*365)", "?NOW?", 0, "download");
	break;
  case "user": 
  	$traffic = $LMS->Traffic($_POST['from'], $_POST['to'], $_POST['net'], $_POST['order'], $_POST['limit']);
	break;
  default: // set filter window
	$now[rok]   = date("Y");
	$now[mies]  = date("n");
	$now[dzien] = date("j");
	$now[godz]  = date("H");
	$now[min]   = date("i");
	for($x=1990; $x<=$now[rok]; $x++) $data[year][]=$x;
	for($x=1; $x<=12; $x++) $data[month][]=$x;
	for($x=1; $x<=31; $x++) $data[day][]=$x;
	for($x=0; $x<=23; $x++) $data[hour][] = $x;
	for($x=0; $x<=59; $x++) $data[minute][] = $x;
	$SMARTY->assign("data",$data);
	$SMARTY->assign("now",$now);
	$SMARTY->assign("netlist",$LMS->GetNetworks());
	$SMARTY->assign("nodelist",$LMS->GetNodeList());
	$bars = 0;
	break;
}

$download = $traffic[download];
$upload = $traffic[upload];

unset($traffic);

$SMARTY->assign("showips",$_POST['showips']);
$SMARTY->assign("layout",$layout);
$SMARTY->assign("download",$download);
$SMARTY->assign("upload",$upload);
$SMARTY->assign("bars",$bars);
$SMARTY->display("traffic.html");

?>