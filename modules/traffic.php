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

$layout[pagetitle]="Statystyki wykorzystania ��cza";

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
		$SMARTY->assign("netlist",$LMS->GetNetworks());
		$SMARTY->assign("nodelist",$LMS->GetNodeList());
		$bars = 0;
	break;
}

$download = $traffic[download];
$upload = $traffic[upload];

// fuck this anyway... Maybe i write function in LMS:: for this, but not now

$starttime = $DB->GetOne("SELECT MIN(dt) FROM stats");
$endtime = $DB->GetOne("SELECT MAX(dt) FROM stats");
$startyear = date('Y',$starttime);
$endyear = date('Y',$endtime);

unset($traffic);

$SMARTY->assign("starttime",$starttime);
$SMARTY->assign("startyear",$startyear);
$SMARTY->assign("endtime",$endtime);
$SMARTY->assign("endyear",$endyear);
$SMARTY->assign("showips",$_POST['showips']);
$SMARTY->assign("layout",$layout);
$SMARTY->assign("download",$download);
$SMARTY->assign("upload",$upload);
$SMARTY->assign("bars",$bars);
$SMARTY->display("traffic.html");

?>
