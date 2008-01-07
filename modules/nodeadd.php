<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2008 LMS Developers
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

$nodedata['access'] = 1;
$nodedata['ownerid'] = 0;

if(isset($_GET['ownerid']))
{
	if($LMS->CustomerExists($_GET['ownerid']) == true)
	{
		$nodedata['ownerid'] = $_GET['ownerid'];
		$customerinfo = $LMS->GetCustomer($_GET['ownerid']);
		$SMARTY->assign('customerinfo', $customerinfo);
	}
	else
		$SESSION->redirect('?m=customerinfo&id='.$_GET['ownerid']);
}

if(isset($_GET['preip']))
	$nodedata['ipaddr'] = $_GET['preip'];

if(isset($_GET['premac']))
	$nodedata['mac'] = $_GET['premac'];

if(isset($_GET['prename']))
	$nodedata['name'] = $_GET['prename'];

if(isset($_POST['nodedata']))
{
	$nodedata = $_POST['nodedata'];

	$nodedata['ipaddr'] = $_POST['nodedataipaddr'];
	$nodedata['ipaddr_pub'] = $_POST['nodedataipaddr_pub'];
	$nodedata['mac'] = $_POST['nodedatamac'];
	$nodedata['mac'] = str_replace('-',':',$nodedata['mac']);

	foreach($nodedata as $key => $value)
		$nodedata[$key] = trim($value);

	if($nodedata['ipaddr']=='' && $nodedata['ipaddr_pub'] && $nodedata['mac']=='' && $nodedata['name']=='')
		if($_GET['ownerid'])
		{
			$SESSION->redirect('?m=customerinfo&id='.$_GET['ownerid']);
		}else{
			$SESSION->redirect('?m=nodelist');
		}
	
	if($nodedata['name']=='')
		$error['name'] = trans('Node name is required!');
	elseif(strlen($nodedata['name']) > 32)
		$error['name'] = trans('Node name is too long (max.32 characters)!');
	elseif($LMS->GetNodeIDByName($nodedata['name']))
		$error['name'] = trans('Specified name is in use!');
	elseif(!eregi('^[_a-z0-9-]+$',$nodedata['name']))
		$error['name'] = trans('Specified name contains forbidden characters!');		

	if(!$nodedata['ipaddr'])
		$error['ipaddr'] = trans('Node IP address is required!');
	elseif(!check_ip($nodedata['ipaddr']))
		$error['ipaddr'] = trans('Incorrect node IP address!');
	elseif(!$LMS->IsIPValid($nodedata['ipaddr']))
		$error['ipaddr'] = trans('Specified IP address doesn\'t overlap with any network!');
	elseif(!$LMS->IsIPFree($nodedata['ipaddr']))
		$error['ipaddr'] = trans('Specified IP address is in use!');
	elseif($LMS->IsIPGateway($nodedata['ipaddr']))
		$error['ipaddr'] = trans('Specified IP address is network gateway!');

	if($nodedata['ipaddr_pub']!='0.0.0.0' && $nodedata['ipaddr_pub']!='')
	{
		if(!check_ip($nodedata['ipaddr_pub']))
                	$error['ipaddr_pub'] = trans('Incorrect node IP address!');
        	elseif(!$LMS->IsIPValid($nodedata['ipaddr_pub']))
                	$error['ipaddr_pub'] = trans('Specified IP address doesn\'t overlap with any network!');
		elseif(!$LMS->IsIPFree($nodedata['ipaddr_pub']))
			$error['ipaddr_pub'] = trans('Specified IP address is in use!');
		elseif($LMS->IsIPGateway($nodedata['ipaddr_pub']))
			$error['ipaddr_pub'] = trans('Specified IP address is network gateway!');
	}
	else
    		$nodedata['ipaddr_pub'] = '0.0.0.0';

	if(!$nodedata['mac'])
		$error['mac'] = trans('MAC address is required!');
	elseif(!check_mac($nodedata['mac']))
		$error['mac'] = trans('Incorrect MAC address!');
	elseif($nodedata['mac']!='00:00:00:00:00:00' && (!isset($CONFIG['phpui']['allow_mac_sharing']) || !chkconfig($CONFIG['phpui']['allow_mac_sharing'])))
		if($LMS->GetNodeIDByMAC($nodedata['mac']))
			$error['mac'] = trans('Specified MAC address is in use!');

	if(strlen($nodedata['passwd']) > 32)
		$error['passwd'] = trans('Password is too long (max.32 characters)!');

	if(! $LMS->CustomerExists($nodedata['ownerid']))
		$error['customer'] = trans('You have to select owner!');
	else
	{
		$status = $LMS->GetCustomerStatus($nodedata['ownerid']);
		if($status == 1) // unknown (interested)
			$error['customer'] = trans('Selected customer is not connected!');
		elseif($status == 2 && $nodedata['access']) // awaiting
	                $error['access'] = trans('Node owner is not connected!');
	}

	if($nodedata['netdev'])
	{
		$netdev = $LMS->GetNetDev($nodedata['netdev']); 
		if($netdev['ports'] <= $netdev['takenports']) 
			$error['netdev'] = trans('No free ports on device!');
	}
	else
		$nodedata['netdev'] = 0;

	if(!isset($nodedata['chkmac']))	$nodedata['chkmac'] = 0;
	if(!isset($nodedata['halfduplex'])) $nodedata['halfduplex'] = 0;

	if(!$error)
	{
		$nodeid = $LMS->NodeAdd($nodedata);
		if(!isset($nodedata['reuse']))
		{
			$SESSION->redirect('?m=nodeinfo&id='.$nodeid);
		}
		
		$ownerid = $nodedata['ownerid'];
		unset($nodedata);
		
		$nodedata['ownerid'] = $ownerid;
		$nodedata['reuse'] = '1';
	}
	else
		if($nodedata['ipaddr_pub']=='0.0.0.0')
			$nodedata['ipaddr_pub'] = '';
}

$layout['pagetitle'] = trans('New Node');

$customers = $LMS->GetCustomerNames();

if($nodedata['ownerid'])
{
	$SMARTY->assign('balancelist', $LMS->GetCustomerBalanceList($nodedata['ownerid']));
	$SMARTY->assign('assignments', $LMS->GetCustomerAssignments($nodedata['ownerid']));
	$SMARTY->assign('customergroups', $LMS->CustomergroupGetForCustomer($nodedata['ownerid']));
	$SMARTY->assign('customernodes', $LMS->GetCustomerNodes($nodedata['ownerid']));
	$SMARTY->assign('othercustomergroups', $LMS->GetGroupNamesWithoutCustomer($nodedata['ownerid']));
	$SMARTY->assign('allnodegroups', $LMS->GetNodeGroupNames());
	$SMARTY->assign('documents', $LMS->GetDocuments($nodedata['ownerid'], 10));
	$SMARTY->assign('taxeslist', $LMS->GetTaxes());
	$SMARTY->assign('tariffs', $LMS->GetTariffs());

	if(isset($CONFIG['phpui']['ewx_support']) && chkconfig($CONFIG['phpui']['ewx_support']))
	{
    		$SMARTY->assign('ewx_channelid', $DB->GetOne('SELECT MAX(channelid) FROM ewx_stm_nodes, nodes
	                                        WHERE nodeid = nodes.id AND ownerid = ?', array($nodedata['ownerid'])));
	}
}

$SMARTY->assign('netdevices', $LMS->GetNetDevNames());
$SMARTY->assign('customers', $customers);
$SMARTY->assign('error', $error);
$SMARTY->assign('nodedata', $nodedata);
$SMARTY->display('nodeadd.html');

?>
