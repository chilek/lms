<?php

/*
 * LMS version 1.4-cvs
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

if(!$LMS->NodeExists($_GET['id']))
	if(isset($_GET['ownerid']))
		header('Location: ?m=userinfo&id='.$_GET['ownerid']);
	else
		header('Location: ?m=nodelist');

if($_GET['action']=='link')
{
	$netdev = $LMS->GetNetDev($_GET['devid']); 

	if($netdev['ports'] > $netdev['takenports']) 
	{
		$LMS->NetDevLinkNode($_GET['id'],$_GET['devid']);
		header('Location: ?m=nodeinfo&id='.$_GET['id']);
		die;
	}
	else
	{
		header('Location: ?m=nodeinfo&id='.$_GET['id'].'&devid='.$_GET['devid']);
		die;
	}	
}

$nodeid = $_GET['id'];
$ownerid = $LMS->GetNodeOwner($nodeid);
$_SESSION['backto'] = $_SERVER['QUERY_STRING'];
	
if(!isset($_GET['ownerid']))
	$_SESSION['backto'] .= '&ownerid='.$ownerid;
							
$owner = $ownerid;
$userinfo=$LMS->GetUser($owner);
$layout['pagetitle'] = 'Informacje o u¿ytkowniku: '.$userinfo['username'].' - edycja komputera: '.$LMS->GetNodeName($_GET['id']);

$nodeedit = $_POST['nodeedit'];
$usernodes = $LMS->GetUserNodes($owner);
$nodeinfo = $LMS->GetNode($_GET['id']);

if(isset($nodeedit))
{
	$nodeedit['ipaddr'] = $_POST['nodeeditipaddr'];
	$nodeedit['mac'] = $_POST['nodeeditmac'];
	$nodeedit['mac'] = str_replace('-',':',$nodeedit['mac']);
	foreach($nodeedit as $key => $value)
		$nodeedit[$key] = trim($value);
	
	if($nodeedit['ipaddr']=='' && $nodeedit['mac']=='' && $nodeedit['name']=='')
	{
		header('Location: ?m=nodeinfo&id='.$nodeedit['id']);
		die;
	}

	if(check_ip($nodeedit['ipaddr']))
	{
		if($LMS->IsIPValid($nodeedit['ipaddr'])) 
		{
			if(!$LMS->IsIPFree($nodeedit['ipaddr']) && $LMS->GetNodeIPByID($nodeedit['id'])!=$nodeedit['ipaddr'])
				$error['ipaddr'] = 'Podany adres IP jest zajêty!';
		}
		else
			$error['ipaddr'] = 'Podany adres IP nie nale¿y do ¿adnej sieci!';
	}
	else
		$error['ipaddr'] = 'Podany adres IP jest niepoprawny!';

	if(check_mac($nodeedit['mac']))
	{
		if($LMS->CONFIG['phpui']['allow_mac_sharing'] == FALSE)
			if($LMS->GetNodeIDByMAC($nodeedit['mac']) && $LMS->GetNodeMACByID($nodeedit['id'])!=$nodeedit['mac'])
				$error['mac'] = 'Podany adres MAC jest ju¿ przypisany do innego komputera!';
	}
	else
		$error['mac'] = 'Podany adres MAC jest b³êdny!';

	if($nodeedit['name']=='')
		$error['name'] = 'Podaj nazwê!';
	elseif($LMS->GetNodeIDByName($nodeedit['name']) && $LMS->GetNodeIDByName($nodeedit['name']) != $nodeedit['id'])
		$error['name'] = 'Ta nazwa jest zajêta!';
	elseif(!eregi("^[_a-z0-9-]+$",$nodeedit['name']))
		$error['name'] = 'Podana nazwa zawiera niepoprawne znaki!';
	elseif(strlen($nodeedit['name'])>16)
		$error['name'] = 'Podana nazwa jest za d³uga!';

	if($nodeedit['access']!=1)
		$nodeedit['access'] = 0;
        if($nodeedit['warning'] != 1)
                $nodeedit['warning'] = 0;	

	if($nodeinfo['netdev'] != $nodeedit['netdev'] && $nodeedit['netdev'] != 0)
	{
		$netdev = $LMS->GetNetDev($nodeedit['netdev']); 
		if($netdev['ports'] <= $netdev['takenports'])
		    $error['netdev'] = 'Brak wolnych portów w wybranym urz±dzeniu!';
		$nodeinfo['netdev'] = $nodeedit['netdev'];
	}
	
	$nodeinfo['name'] = $nodeedit['name'];
	$nodeinfo['mac'] = $nodeedit['mac'];
	$nodeinfo['ip'] = $nodeedit['ipaddr'];
	$nodeinfo['access'] = $nodeedit['access'];
	$nodeinfo['ownerid'] = $nodeedit['ownerid'];

	if(!$error)
	{
		$LMS->NodeUpdate($nodeedit);
		header('Location: ?m=nodeinfo&id='.$nodeedit['id']);
	}
}

if($userinfo['status']==3) $userinfo['shownodes'] = TRUE;
$users = $LMS->GetUserNames();
$tariffs = $LMS->GetTariffs();
$assignments = $LMS->GetUserAssignments($ownerid);
$balancelist = $LMS->GetUserBalanceList($owner);
$usergroups = $LMS->UsergroupGetForUser($ownerid);
$otherusergroups = $LMS->GetGroupNamesWithoutUser($ownerid);

$netdevices = $LMS->GetNetDevList();
unset($netdevices['total']);
unset($netdevices['direction']);
unset($netdevices['order']);

$SMARTY->assign('netdevices',$netdevices);
$SMARTY->assign('balancelist',$balancelist);
$SMARTY->assign('assignments',$assignments);
$SMARTY->assign('usergroups',$usergroups);
$SMARTY->assign('otherusergroups',$otherusergroups);
$SMARTY->assign('tariffs',$tariffs);
$SMARTY->assign('error',$error);
$SMARTY->assign('userinfo',$userinfo);
$SMARTY->assign('nodeinfo',$nodeinfo);
$SMARTY->assign('users',$users);
$SMARTY->display('nodeedit.html');

?>


