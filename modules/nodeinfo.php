<?php

/*
 * LMS version 1.4-cvs
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

if(!eregi("^[0-9]+$",$_GET['id']))
{
	header("Location: ?m=nodelist");
	die;
}

if(!$LMS->NodeExists($_GET['id']))
	if(isset($_GET['ownerid']))
	{
		header("Location: ?m=userinfo&id=".$_GET['ownerid']);
		die;
	}
	else
	{
		header("Location: ?m=nodelist");
		die;
	}
elseif($LMS->GetNodeOwner($_GET['id']) == 0)
{
	header("Location: ?m=netdevinfo&id=".$LMS->GetNetDevIDByNode($_GET['id']));
	die;
}

if($_GET['devid'])
{
	$error['netdev'] = 'Brak wolnych portów w wybranym urz±dzeniu!';
	$SMARTY->assign('error', $error);
	$SMARTY->assign('netdevice', $_GET['devid']);
}

$nodeid = $_GET['id'];
$ownerid = $LMS->GetNodeOwner($nodeid);
$tariffs = $LMS->GetTariffs();
$userinfo = $LMS->GetUser($ownerid);
$nodeinfo = $LMS->GetNode($nodeid);
$balancelist = $LMS->GetUserBalanceList($ownerid);
$assignments = $LMS->GetUserAssignments($ownerid);
$usergroups = $LMS->UsergroupGetForUser($ownerid);
$otherusergroups = $LMS->GetGroupNamesWithoutUser($ownerid);

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

if(!isset($_GET['ownerid']))
	$_SESSION['backto'] .= "&ownerid=".$ownerid;

if($nodeinfo['netdev'] == 0) {
	$netdevices = $LMS->GetNetDevList();
	
} else
	$netdevices = $LMS->GetNetDev($nodeinfo['netdev']);

unset($netdevices['total']);
unset($netdevices['order']);
unset($netdevices['direction']);

$layout['pagetitle'] = "Informacje o komputerze: ".$nodeinfo['name'];

$SMARTY->assign('netdevices',$netdevices);
$SMARTY->assign('balancelist',$balancelist);
$SMARTY->assign('userinfo',$userinfo);
$SMARTY->assign('nodeinfo',$nodeinfo);
$SMARTY->assign('assignments',$assignments);
$SMARTY->assign('usergroups',$usergroups);
$SMARTY->assign('otherusergroups',$otherusergroups);
$SMARTY->assign('tariffs',$tariffs);
$SMARTY->display('nodeinfo.html');

?>
