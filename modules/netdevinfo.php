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

if(! $LMS->NetDevExists($_GET[id]))
{
	header('Location: ?m=netdevlist');
	die;
}

$netdevinfo = $LMS->GetNetDev($_GET[id]);
$netdevconnected = $LMS->GetNetDevConnectedNames($_GET[id]);
$netcomplist = $LMS->GetNetdevLinkedNodes($_GET[id]);
$netdevlist = $LMS->GetNotConnectedDevices($_GET[id]);
$nodelist = $LMS->GetNodeList();
$replacelist = $LMS->GetNetDevList();

unset($replacelist[order]);
unset($replacelist[total]);
unset($replacelist[direction]);

$nodelist = $LMS->GetUnlinkedNodes();
$netdevips = $LMS->GetNetDevIPs($_GET[id]);

unset($nodelist[total]);
unset($nodelist[order]);
unset($nodelist[totalon]);
unset($nodelist[totaloff]);
unset($nodelist[direction]);

$_SESSION[backto] = $_SERVER[QUERY_STRING];

$layout[pagetitle]="Informacje o urz±dzeniu: ".$netdevinfo[name]." ".$netdevinfo[producer]." ".$netdevinfo[model];

$netdevinfo[id] = $_GET[id];

$SMARTY->assign("netdevinfo",$netdevinfo);
$SMARTY->assign("netdevlist",$netdevconnected);
$SMARTY->assign("netcomplist",$netcomplist);
$SMARTY->assign("restnetdevlist",$netdevlist);
$SMARTY->assign("netdevips",$netdevips);
$SMARTY->assign("nodelist",$nodelist);
$SMARTY->assign("layout",$layout);
$SMARTY->assign("replacelist",$replacelist);
$SMARTY->display("netdevinfo.html");

/*
 * $Log$
 * Revision 1.9  2003/10/10 12:25:58  lexx
 * - Dodana mo¿liwo¶æ zamiany urz±dzeñ miejscami
 *
 * Revision 1.8  2003/10/08 04:39:38  lukasz
 * - temporary save
 *
 * Revision 1.7  2003/10/08 04:01:29  lukasz
 * - html fixes in netdevices
 * - added new smarty function called {confirm text="confirm message"}
 * - little bugfix with netdev field in nodes (alec, pse, add this to
 *   changelog, also consider making 'UPGRADING' chapter in doc if it not
 *   exists yet)
 * - lot of small changes, mainly cosmetic
 *
 * Revision 1.6  2003/10/07 19:37:35  alec
 * unset nieporzebnych elem.
 *
 * Revision 1.5  2003/10/06 04:46:49  lukasz
 * - temp save
 *
 * Revision 1.4  2003/09/22 18:12:33  lexx
 * - komputery moga sie linkowac
 *
 * Revision 1.3  2003/09/21 18:07:47  lexx
 * - netdev
 *
 * Revision 1.2  2003/09/13 20:20:14  lexx
 * - lokalizacja
 *
 * Revision 1.1  2003/09/12 20:57:05  lexx
 * - netdev
 *
 */
?>
