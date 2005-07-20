<?php

/*
 * LMS version 1.7-cvs
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

if(!eregi('^[0-9]+$',$_GET['id']))
{
	$SESSION->redirect('?m=nodelist');
}

if(!$LMS->NodeExists($_GET['id']))
	if(isset($_GET['ownerid']))
	{
		$SESSION->redirect('?m=customerinfo&id='.$_GET['ownerid']);
	}
	else
	{
		$SESSION->redirect('?m=nodelist');
	}
elseif($LMS->GetNodeOwner($_GET['id']) == 0)
{
	$SESSION->redirect('?m=netdevinfo&id='.$LMS->GetNetDevIDByNode($_GET['id']));
}

if(isset($_GET['devid']))
{
	$error['netdev'] = trans('It scans for free ports in selected device!');
	$SMARTY->assign('error', $error);
	$SMARTY->assign('netdevice', $_GET['devid']);
}

$nodeid = $_GET['id'];
$ownerid = $LMS->GetNodeOwner($nodeid);
$tariffs = $LMS->GetTariffs();
$customerinfo = $LMS->GetCustomer($ownerid);
$nodeinfo = $LMS->GetNode($nodeid);
$balancelist = $LMS->GetCustomerBalanceList($ownerid);
$assignments = $LMS->GetCustomerAssignments($ownerid);
$documents = $LMS->GetDocuments($ownerid);
$customergroups = $LMS->CustomergroupGetForCustomer($ownerid);
$othercustomergroups = $LMS->GetGroupNamesWithoutCustomer($ownerid);
$contractlist = $LMS->GetContractList();

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if(!isset($_GET['ownerid']))
	$SESSION->save('backto', $SESSION->get('backto').'&ownerid='.$ownerid);

if($nodeinfo['netdev'] == 0) 
	$netdevices = $LMS->GetNetDevNames();
else
	$netdevices = $LMS->GetNetDev($nodeinfo['netdev']);

$layout['pagetitle'] = trans('Node Info: $0',$nodeinfo['name']);

$SMARTY->assign('netdevices',$netdevices);
$SMARTY->assign('balancelist',$balancelist);
$SMARTY->assign('customerinfo',$customerinfo);
$SMARTY->assign('nodeinfo',$nodeinfo);
$SMARTY->assign('assignments',$assignments);
$SMARTY->assign('customergroups',$customergroups);
$SMARTY->assign('othercustomergroups',$othercustomergroups);
$SMARTY->assign('documents', $documents);
$SMARTY->assign('tariffs',$tariffs);
$SMARTY->assign('contractlist',$contractlist);
$SMARTY->assign('contractcount',sizeof($contractlist));
$SMARTY->display('nodeinfo.html');

?>
