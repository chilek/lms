<?php

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
 */

if($_GET[action]=="disconnect")
{
	$LMS->NetDevUnLink($_GET[id],$_GET[devid]);
	header("Location: ?m=netdevinfo&id=".$_GET[id]);
	die;
}

if($_GET[action]=="disconnectnode")
{
	$LMS->NetDevLinkComputer($_GET[nodeid],0);
	header("Location: ?m=netdevinfo&id=".$_GET[id]);
	die;
}

if($_GET[action]=="connect")
{
	if(! $LMS->NetDevLink($_GET[netdev], $_GET[id]) )
		$error[link] = "Brak wolnych portów w urz±dzeniu";
	else
		header("Location: ?m=netdevinfo&id=".$_GET[id]);
}
    
if($_GET[action]=="connectnode") 
{
	if(! $LMS->NetDevLinkComputer($_GET[nodeid], $_GET[id]) )
		$error[linknode] = "Brak wolnych portów w urz±dzeniu";
	else
		header("Location: ?m=netdevinfo&id=".$_GET[id]);
}

$netdevdata = $_POST[netdev];

if(isset($netdevdata))
{
	$netdevdata[id] = $_GET[id];

	if($netdevdata[name] == "")
		$error[name] = "Pole nazwa nie mo¿e byæ puste!";
	
	if(!$error)
	{
		$LMS->NetDevUpdate($netdevdata);
		header("Location: ?m=netdevinfo&id=".$_GET[id]);
		die;
	}
}
else
	$netdevdata = $LMS->GetNetDev($_GET[id]);

$netdevdata[id] = $_GET[id];

$netdevconnected = $LMS->GetNetDevConnectedNames($_GET[id]);
$netcomplist = $LMS->GetNetdevLinkedNodes($_GET[id]);
$netdevlist = $LMS->GetNotConnectedDevices($_GET[id]);

unset($netdevlist[total]);
unset($netdevlist[order]);
unset($netdevlist[direction]);

$nodelist = $LMS->GetNodeList();

unset($nodelist[totaloff]);
unset($nodelist[totalon]);
unset($nodelist[total]);
unset($nodelist[order]);
unset($nodelist[direction]);

$layout[pagetitle]="Edycja urz±dzenia: ".$netdevdata[name]." ".$netdevdata[producer];

$SMARTY->assign("layout",$layout);
$SMARTY->assign("error",$error);
$SMARTY->assign("netdevinfo",$netdevdata);
$SMARTY->assign("netdevlist",$netdevconnected);
$SMARTY->assign("netcomplist",$netcomplist);
$SMARTY->assign("nodelist",$nodelist);
$SMARTY->assign("restnetdevlist",$netdevlist);
$SMARTY->display("netdevedit.html");

/*
 * $Log$
 * Revision 1.10  2003/10/06 08:14:55  lexx
 * - nie zawsze rozpoczynamy edycje przez post
 *
 * Revision 1.9  2003/10/06 05:33:04  lukasz
 * - temporary save / lot of fixes
 *
 * Revision 1.8  2003/10/06 04:46:49  lukasz
 * - temp save
 *
 * Revision 1.7  2003/10/05 20:45:33  lexx
 * - Lista urzadzen do podlaczenia wyswietla tylko urzadzenia jeszcze nie
 *   podlaczone
 *
 * Revision 1.6  2003/10/04 20:05:36  alec
 * now we can connect nodes to netdevices
 *
 * Revision 1.5  2003/10/04 19:23:25  alec
 * now we can link net devices
 *
 * Revision 1.4  2003/10/03 19:55:21  alec
 * teraz mozna tutaj od³±czaæ kompy
 *
 * Revision 1.3  2003/10/03 19:22:09  alec
 * now we can netdev disconnect
 *
 * Revision 1.2  2003/09/25 19:00:10  lexx
 * - w netdevedit widac do czego urzadzenie jest podlaczone
 *
 * Revision 1.1  2003/09/12 20:57:05  lexx
 * - netdev
 *
 */
?>
