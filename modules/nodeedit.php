<?php

/*
 * LMS version 1.5-cvs
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

if(!$LMS->NodeExists($_GET['id']))
	if(isset($_GET['ownerid']))
		header('Location: ?m=userinfo&id='.$_GET['ownerid']);
	else
		header('Location: ?m=nodelist');

$action = isset($_GET['action']) ? $_GET['action'] : '';

if($action=='link')
{
	$netdev = $LMS->GetNetDev($_GET['devid']); 

	if($netdev['ports'] > $netdev['takenports']) 
	{
		$LMS->NetDevLinkNode($_GET['id'],$_GET['devid']);
		$SESSION->redirect('?m=nodeinfo&id='.$_GET['id']);
	}
	else
	{
		$SESSION->redirect('?m=nodeinfo&id='.$_GET['id'].'&devid='.$_GET['devid']);
	}	
}

$nodeid = $_GET['id'];
$ownerid = $LMS->GetNodeOwner($nodeid);
$SESSION->save('backto', $_SERVER['QUERY_STRING']);
	
if(!isset($_GET['ownerid']))
	$SESSION->save('backto', $SESSION->get('backto') . '&ownerid='.$ownerid);
							
$userinfo = $LMS->GetUser($ownerid);
$layout['pagetitle'] = trans('Customer Info: $0 - Node Edit: $1',$userinfo['username'], $LMS->GetNodeName($_GET['id']));

$usernodes = $LMS->GetUserNodes($ownerid);
$nodeinfo = $LMS->GetNode($_GET['id']);

if(isset($_POST['nodeedit']))
{
	$nodeedit = $_POST['nodeedit'];
	
	$nodeedit['ipaddr'] = $_POST['nodeeditipaddr'];
	$nodeedit['ipaddr_pub'] = $_POST['nodeeditipaddrpub'];
	$nodeedit['mac'] = $_POST['nodeeditmac'];
	$nodeedit['mac'] = str_replace('-',':',$nodeedit['mac']);
	foreach($nodeedit as $key => $value)
		$nodeedit[$key] = trim($value);
	
	if($nodeedit['ipaddr']=='' && $nodeedit['ipaddr_pub']=='' && $nodeedit['mac']=='' && $nodeedit['name']=='' && $nodeedit['info']=='' && $nodeedit['passwd']=='')
	{
		$SESSION->redirect('?m=nodeinfo&id='.$nodeedit['id']);
	}

	if(check_ip($nodeedit['ipaddr']))
	{
		if($LMS->IsIPValid($nodeedit['ipaddr'])) 
		{
			$ip = $LMS->GetNodeIPByID($nodeedit['id']);
			if($ip!=$nodeedit['ipaddr'] && !$LMS->IsIPFree($nodeedit['ipaddr']))
				$error['ipaddr'] = trans('Specified IP address is in use!');
			elseif($ip!=$nodeedit['ipaddr'] && $LMS->IsIPGateway($nodeedit['ipaddr']))
				$error['ipaddr'] = trans('Specified IP address is network gateway!');
		}
		else
			$error['ipaddr'] = trans('Specified IP address does not overlap with any network!');
	}
	else
		$error['ipaddr'] = trans('Incorrect IP address!');

	if($nodeedit['ipaddr_pub']!='0.0.0.0' && $nodeedit['ipaddr_pub']!='')
	{
		if(check_ip($nodeedit['ipaddr_pub']))
        	{
        	        if($LMS->IsIPValid($nodeedit['ipaddr_pub']))
        	        {
        	                $ip = $LMS->GetNodePubIPByID($nodeedit['id']);
        	                if($ip!=$nodeedit['ipaddr_pub'] && !$LMS->IsIPFree($nodeedit['ipaddr_pub']))
        	                        $error['ipaddr_pub'] = trans('Specified IP address is in use!');
        	                elseif($ip!=$nodeedit['ipaddr_pub'] && $LMS->IsIPGateway($nodeedit['ipaddr_pub']))
        	                        $error['ipaddr_pub'] = trans('Specified IP address is network gateway!');
        	        }
        	        else
        	                $error['ipaddr_pub'] = trans('Specified IP address does not overlap with any network!');
        	}
        	else
        	        $error['ipaddr_pub'] = trans('Incorrect IP address!');
	}
	else
		$nodeedit['ipaddr_pub'] = '0.0.0.0';

	if(check_mac($nodeedit['mac']))
	{
		if(isset($LMS->CONFIG['phpui']['allow_mac_sharing']) && 
			$LMS->CONFIG['phpui']['allow_mac_sharing'] == FALSE
			)
			if($LMS->GetNodeIDByMAC($nodeedit['mac']) && $LMS->GetNodeMACByID($nodeedit['id'])!=$nodeedit['mac'])
				$error['mac'] = trans('Specified MAC address is in use!');
	}
	else
		$error['mac'] = trans('Incorrect MAC address!');

	if($nodeedit['name']=='')
		$error['name'] = trans('Node name is required!');
	elseif($LMS->GetNodeIDByName($nodeedit['name']) && $LMS->GetNodeIDByName($nodeedit['name']) != $nodeedit['id'])
		$error['name'] = trans('Specified name is in use!');
	elseif(!eregi('^[_a-z0-9-]+$',$nodeedit['name']))
		$error['name'] = trans('Specified name contains forbidden characters!');
	elseif(strlen($nodeedit['name'])>16)
		$error['name'] = trans('Node name is too long (max.16 characters)!');

	if(strlen($nodeedit['passwd'])>32)
		$error['passwd'] = trans('Password is too long (max.32 characters)!');

	if($nodeedit['access']!=1)
		$nodeedit['access'] = 0;
        if($nodeedit['warning']!=1)
                $nodeedit['warning'] = 0;	

	if($nodeinfo['netdev'] != $nodeedit['netdev'] && $nodeedit['netdev'] != 0)
	{
		$netdev = $LMS->GetNetDev($nodeedit['netdev']); 
		if($netdev['ports'] <= $netdev['takenports'])
		    $error['netdev'] = trans('It scants free ports in selected device!');
		$nodeinfo['netdev'] = $nodeedit['netdev'];
	}
	
	$nodeinfo['name'] = $nodeedit['name'];
	$nodeinfo['mac'] = $nodeedit['mac'];
	$nodeinfo['ip'] = $nodeedit['ipaddr'];
	$nodeinfo['ip_pub'] = $nodeedit['ipaddr_pub'];
	$nodeinfo['passwd'] = $nodeedit['passwd'];
	$nodeinfo['access'] = $nodeedit['access'];
	$nodeinfo['ownerid'] = $nodeedit['ownerid'];

	if(!$error)
	{
		$LMS->NodeUpdate($nodeedit);
		header('Location: ?m=nodeinfo&id='.$nodeedit['id']);
	}

	if($nodeedit['ipaddr_pub']=='0.0.0.0')
		$nodeinfo['ipaddr_pub'] = '';
}

if($userinfo['status']==3) $userinfo['shownodes'] = TRUE;
$users = $LMS->GetUserNames();
$tariffs = $LMS->GetTariffs();
$assignments = $LMS->GetUserAssignments($ownerid);
$balancelist = $LMS->GetUserBalanceList($ownerid);
$usergroups = $LMS->UsergroupGetForUser($ownerid);
$otherusergroups = $LMS->GetGroupNamesWithoutUser($ownerid);
$contractlist = $LMS->GetContractList();
$netdevices = $LMS->GetNetDevNames();

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
$SMARTY->assign('contractlist',$contractlist);
$SMARTY->assign('contractcount',sizeof($contractlist));
$SMARTY->display('nodeedit.html');

?>
