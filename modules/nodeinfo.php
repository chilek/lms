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

if(!$LMS->NodeExists($_GET[id]))
	if(isset($_GET[ownerid]))
	{
		header("Location: ?m=userinfo&id=".$_GET[ownerid]);
		die;
	}
	else
	{
		header("Location: ?m=nodelist");
		die;
	}
elseif($LMS->GetNodeOwner($_GET[id]) == 0)
{
	header("Location: ?m=netdevinfo&id=".$LMS->GetNetDevIDByNode($_GET[id]));
	die;
}

$nodeid = $_GET[id];
$ownerid = $LMS->GetNodeOwner($nodeid);
$tariffs = $LMS->GetTariffs();
$userinfo = $LMS->GetUser($ownerid);
$nodeinfo = $LMS->GetNode($nodeid);
$balancelist = $LMS->GetUserBalanceList($ownerid);
$assignments = $LMS->GetUserAssignments($ownerid);

$_SESSION[backto] = $_SERVER[QUERY_STRING];

if(!isset($_GET[ownerid]))
	$_SESSION[backto] .= "&ownerid=".$ownerid;

if($nodeinfo['netdev'] == 0) {
	$netdevices = $LMS->GetNetDevList();
	
} else
	$netdevices = $LMS->GetNetDev($nodeinfo['netdev']);

unset($netdevices[total]);
unset($netdevices[order]);
unset($netdevices[direction]);

$layout[pagetitle]="Informacje o komputerze: ".$nodeinfo[name];

$SMARTY->assign("netdevices",$netdevices);
$SMARTY->assign("balancelist",$balancelist);
$SMARTY->assign("userinfo",$userinfo);
$SMARTY->assign("nodeinfo",$nodeinfo);
$SMARTY->assign("assignments",$assignments);
$SMARTY->assign("tariffs",$tariffs);
$SMARTY->assign("layout",$layout);
$SMARTY->display("nodeinfo.html");

/*
 * $Log$
 * Revision 1.25  2003/10/08 04:39:38  lukasz
 * - temporary save
 *
 * Revision 1.24  2003/10/01 16:06:37  alec
 * removed bad array members
 *
 * Revision 1.23  2003/09/26 17:44:10  alec
 * ujednolicone nag³ówki (dodany ':')
 *
 * Revision 1.22  2003/09/22 18:12:33  lexx
 * - komputery moga sie linkowac
 *
 * Revision 1.21  2003/09/22 17:47:28  alec
 * added node name to page title
 *
 * Revision 1.20  2003/09/19 11:00:03  lukasz
 * - temporary save
 *
 * Revision 1.19  2003/09/12 02:52:57  lukasz
 * - cosmetics
 *
 * Revision 1.18  2003/09/12 02:48:13  lukasz
 * - parse error @ 51;
 *
 * Revision 1.17  2003/09/12 02:47:37  lukasz
 * - cosmetics
 *
 * Revision 1.16  2003/09/09 01:44:07  lukasz
 * - poprawki node{edit,info,add}
 *
 * Revision 1.15  2003/08/24 13:12:54  lukasz
 * - massive attack: s/<?/<?php/g - that was causing problems on some fucked
 *   redhat's :>
 *
 * Revision 1.14  2003/08/18 16:52:19  lukasz
 * - added CVS Log tags
 *
 */
?>
