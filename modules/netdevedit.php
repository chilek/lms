<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2007 LMS Developers
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

if(! $LMS->NetDevExists($_GET['id']))
{
	$SESSION->redirect('?m=netdevlist');
}		

$action = isset($_GET['action']) ? $_GET['action'] : '';
$edit = 'data';

switch($action)
{
case 'replace':

	$dev1 = $LMS->GetNetDev($_GET['id']);
	$dev2 = $LMS->GetNetDev($_GET['netdev']);
	if ($dev1['ports'] < $dev2['takenports']) 
	{
	    $error['replace'] = trans('It scans for ports in source device!');
	    $edit = FALSE;
	} 
	elseif ($dev2['ports'] < $dev1['takenports']) 
	{
	    $error['replace'] = trans('It scans for ports in destination device!');
	    $edit = FALSE;
	} 
	else 
	{
	    $LMS->NetDevReplace($_GET['id'],$_GET['netdev']);
	    $SESSION->redirect('?m=netdevinfo&id='.$_GET['id']);
	}
	break;
	
case 'disconnect':

	$LMS->NetDevUnLink($_GET['id'],$_GET['devid']);
	$SESSION->redirect('?m=netdevinfo&id='.$_GET['id']);

case 'disconnectnode':

	$LMS->NetDevLinkNode($_GET['nodeid'],0);
	$SESSION->redirect('?m=netdevinfo&id='.$_GET['id']);

case 'chkmac':

        $DB->Execute('UPDATE nodes SET chkmac=? WHERE id=?', array($_GET['chkmac'], $_GET['ip']));
	$SESSION->redirect('?m=netdevinfo&id='.$_GET['id'].'&ip='.$_GET['ip']);

case 'duplex':

        $DB->Execute('UPDATE nodes SET halfduplex=? WHERE id=?', array($_GET['duplex'], $_GET['ip']));
	$SESSION->redirect('?m=netdevinfo&id='.$_GET['id'].'&ip='.$_GET['ip']);
	
case 'connect':

	$linktype = isset($_GET['linktype']) ? $_GET['linktype'] : '0';
	$SESSION->save('devlinktype', $linktype);
	if(! $LMS->NetDevLink($_GET['netdev'], $_GET['id'], $linktype) )
	{
		$edit = FALSE;
		$error['link'] = trans('No free ports on device!');
	} else
		header('Location: ?m=netdevinfo&id='.$_GET['id']);
	break;
    
case 'connectnode':

	$linktype = isset($_GET['linktype']) ? $_GET['linktype'] : '0';
	$SESSION->save('nodelinktype', $linktype);
	if(! $LMS->NetDevLinkNode($_GET['nodeid'], $_GET['id'], $linktype) )
	{
		$error['linknode'] = trans('No free ports on device!');
		$edit = FALSE;
	} else
		header('Location: ?m=netdevinfo&id='.$_GET['id']);
	break;

case 'addip':

	$edit = 'addip';
	break;

case 'editip':

	$nodeipdata = $LMS->GetNode($_GET['ip']);
	$nodeipdata['ipaddr'] = $nodeipdata['ip'];
	$SMARTY->assign('nodeipdata',$nodeipdata);
	$edit = 'ip';
	break;

case 'switchlinktype':

	$LMS->SetNetDevLinkType($_GET['devid'], $_GET['id'], $_GET['linktype']);
	header('Location: ?m=netdevinfo&id='.$_GET['id']);
	break;

case 'switchnodelinktype':

	$LMS->SetNodeLinkType($_GET['nodeid'], $_GET['linktype']);
	header('Location: ?m=netdevinfo&id='.$_GET['id']);
	break;

case 'formaddip':

	$nodeipdata = $_POST['ipadd'];
	$nodeipdata['ownerid'] = 0;
	$nodeipdata['mac'] = str_replace('-',':',$nodeipdata['mac']);

	foreach($nodeipdata as $key => $value)
		$nodeipdata[$key] = trim($value);
	
	if($nodeipdata['ipaddr']=='' && $nodeipdata['mac']=='' && $nodeipdata['name']=='' && $nodeipdata['passwd']=='')
	{
		$SESSION->redirect('?m=netdevedit&action=addip&id='.$_GET['id']);
        }
	
	if($nodeipdata['name']=='')
		$error['ipname'] = trans('Address field is required!');
	elseif(strlen($nodeipdata['name']) > 32)
		$error['ipname'] = trans('Specified name is too long (max.$0 characters)!','32');
	elseif($LMS->GetNodeIDByName($nodeipdata['name']))
		$error['ipname'] = trans('Specified name is in use!');
	elseif(!eregi('^[_a-z0-9-]+$',$nodeipdata['name']))
		$error['ipname'] = trans('Name contains forbidden characters!');

	if($nodeipdata['ipaddr']=='')
		$error['ipaddr'] = trans('IP address is required!');
	elseif(!check_ip($nodeipdata['ipaddr']))
		$error['ipaddr'] = trans('Incorrect IP address!');
	elseif(!$LMS->IsIPValid($nodeipdata['ipaddr']))
		$error['ipaddr'] = trans('Specified address does not belongs to any network!');
	elseif(!$LMS->IsIPFree($nodeipdata['ipaddr']))
		$error['ipaddr'] = trans('Specified IP address is in use!');
	
	if($nodeipdata['ipaddr_pub']!='0.0.0.0' && $nodeipdata['ipaddr_pub']!='')
	{
		if(!check_ip($nodeipdata['ipaddr_pub']))
	            	$error['ipaddr_pub'] = trans('Incorrect IP address!');
	    	elseif(!$LMS->IsIPValid($nodeipdata['ipaddr_pub']))
	            	$error['ipaddr_pub'] = trans('Specified address does not belongs to any network!');
		elseif(!$LMS->IsIPFree($nodeipdata['ipaddr_pub']))
			$error['ipaddr_pub'] = trans('Specified IP address is in use!');
	}
	else
		$nodeipdata['ipaddr_pub'] = '0.0.0.0';

	if($nodeipdata['mac']=='')
		$error['mac'] = trans('MAC address is required!');
	elseif(!check_mac($nodeipdata['mac']))
		$error['mac'] = trans('Incorrect MAC address!');
	elseif($nodeipdata['mac']!='00:00:00:00:00:00' && !chkconfig($CONFIG['phpui']['allow_mac_sharing']))
		if($LMS->GetNodeIDByMAC($nodeipdata['mac']))
			$error['mac'] = trans('MAC address is in use!');

	if(strlen($nodeipdata['passwd']) > 32)
                $error['passwd'] = trans('Password is too long (max.32 characters)!');

	if(!isset($nodeipdata['chkmac'])) $nodeipdata['chkmac'] = 0;
	if(!isset($nodeipdata['halfduplex'])) $nodeipdata['halfduplex'] = 0;

	if(!$error)
	{
		$nodeipdata['warning'] = 0;
		$nodeipdata['location'] = '';
		$nodeipdata['netdev'] = $_GET['id'];
		
		$LMS->NodeAdd($nodeipdata);
		$SESSION->redirect('?m=netdevinfo&id='.$_GET['id']);
	}
	
	$SMARTY->assign('nodeipdata',$nodeipdata); 
	$edit='addip';
	break;
		
case 'formeditip':

	$nodeipdata = $_POST['ipadd'];
	$nodeipdata['ownerid']=0;
	$nodeipdata['mac'] = str_replace('-',':',$nodeipdata['mac']);

	foreach($nodeipdata as $key => $value)
		$nodeipdata[$key] = trim($value);
	
	if($nodeipdata['ipaddr']=='' && $nodeipdata['mac']=='' && $nodeipdata['name']=='' && $nodeipdata['passwd']=='')
	{
		$SESSION->redirect('?m=netdevedit&action=editip&id='.$_GET['id'].'&ip='.$_GET['ip']);
        }
	
	if($nodeipdata['name']=='')
		$error['ipname'] = trans('Address field is required!');
	elseif(strlen($nodeipdata['name']) > 32)
		$error['ipname'] = trans('Specified name is too long (max.$0 characters)!','32');
	elseif(
		$LMS->GetNodeIDByName($nodeipdata['name']) &&
		$LMS->GetNodeName($_GET['ip'])!=$nodeipdata['name']
		)
		$error['ipname'] = trans('Specified name is in use!');
	elseif(!eregi('^[_a-z0-9-]+$',$nodeipdata['name']))
		$error['ipname'] = trans('Name contains forbidden characters!');	

	if($nodeipdata['ipaddr']=='')
		$error['ipaddr'] = trans('IP address is required!');
	elseif(!check_ip($nodeipdata['ipaddr']))
		$error['ipaddr'] = trans('Incorrect IP address!');
	elseif(!$LMS->IsIPValid($nodeipdata['ipaddr']))
		$error['ipaddr'] =  trans('Specified address does not belongs to any network!');
	elseif(
		!$LMS->IsIPFree($nodeipdata['ipaddr']) &&
		$LMS->GetNodeIPByID($_GET['ip'])!=$nodeipdata['ipaddr']
		)
		$error['ipaddr'] = trans('IP address is in use!');

	if($nodeipdata['ipaddr_pub']!='0.0.0.0' && $nodeipdata['ipaddr_pub']!='')
	{
		if(check_ip($nodeipdata['ipaddr_pub']))
		{
		        if($LMS->IsIPValid($nodeipdata['ipaddr_pub']))
		        {
		                $ip = $LMS->GetNodePubIPByID($nodeipdata['id']);
		                if($ip!=$nodeipdata['ipaddr_pub'] && !$LMS->IsIPFree($nodeipdata['ipaddr_pub']))
		                        $error['ipaddr_pub'] = trans('Specified IP address is in use!');
		        }
		        else
		                $error['ipaddr_pub'] = trans('Specified IP address doesn\'t overlap with any network!');
		}
		else
	    		$error['ipaddr_pub'] = trans('Incorrect IP address!');
	}
	else
		$nodeipdata['ipaddr_pub'] = '0.0.0.0';
	
	if($nodeipdata['mac']=='')
		$error['mac'] =  trans('MAC address is required!');
	elseif(!check_mac($nodeipdata['mac']))
		$error['mac'] = trans('Incorrect MAC address!');
	elseif($nodeipdata['mac']!='00:00:00:00:00:00' && isset($CONFIG['phpui']['allow_mac_sharing']) &&
		!chkconfig($CONFIG['phpui']['allow_mac_sharing']))
		if($LMS->GetNodeIDByMAC($nodeipdata['mac']) && $LMS->GetNodeMACByID($_GET['ip'])!=$nodeipdata['mac'])
			$error['mac'] = trans('MAC address is in use!');

	if(strlen($nodeipdata['passwd']) > 32)
                $error['passwd'] = trans('Password is too long (max.32 characters)!');
		
	if(!isset($nodeipdata['chkmac'])) $nodeipdata['chkmac'] = 0;
	if(!isset($nodeipdata['halfduplex'])) $nodeipdata['halfduplex'] = 0;
	
	if(!$error)
	{
		$nodeipdata['warning'] = 0;
		$nodeipdata['location'] = '';
		$nodeipdata['netdev'] = $_GET['id'];

		$LMS->NodeUpdate($nodeipdata);	
		$SESSION->redirect('?m=netdevinfo&id='.$_GET['id']);
	}

	$nodeipdata['ip_pub'] = $nodeipdata['ipaddr_pub'];
	$SMARTY->assign('nodeipdata',$nodeipdata); 
	$edit='ip';
	break;
}

if(isset($_POST['netdev']))
{
	$netdevdata = $_POST['netdev'];
	$netdevdata['id'] = $_GET['id'];

	if($netdevdata['name'] == '')
		$error['name'] = trans('Device name is required!');
	elseif(strlen($netdevdata['name']) > 32)
		$error['name'] =  trans('Specified name is too long (max.$0 characters)!','32');

	if($netdevdata['ports'] < $LMS->CountNetDevLinks($_GET['id']))
		$error['ports'] = trans('Connected devices number exceeds number of ports!');
	
	// date format 'yyyy/mm/dd'
	// FIXME: check if purchasedate isn't in the future
	$netdevdata['purchasetime'] = NULL;
	if($netdevdata['purchasedate'] != '')
	{
		if(!ereg('^[0-9]{4}/[0-9]{2}/[0-9]{2}$', $netdevdata['purchasedate'])) 
		{
			$error['purchasedate'] = trans('Invalid date format!');
		}
		else
		{
			$date = explode('/', $netdevdata['purchasedate']);
			if(checkdate($date[1], $date[2], (int)$date[0]))
				$netdevdata['purchasetime'] = mktime(0, 0, 0, $date[1], $date[2], $date[0]);
			else
				$error['purchasedate'] = trans('Invalid date format!');
		}
	}

	if($netdevdata['guaranteeperiod'] != 0 && $netdevdata['purchasedate'] == '')
	{
		$error['purchasedate'] = trans('Purchase date cannot be empty when guarantee period is set!');
	}

	if(!$error)
	{
		if($netdevdata['guaranteeperiod'] == -1)
			$netdevdata['guaranteeperiod'] = NULL;
		
		$LMS->NetDevUpdate($netdevdata);
		$SESSION->redirect('?m=netdevinfo&id='.$_GET['id']);
	}
}
else
	$netdevdata = $LMS->GetNetDev($_GET['id']);

$netdevdata['id'] = $_GET['id'];

$netdevconnected = $LMS->GetNetDevConnectedNames($_GET['id']);
$netcomplist = $LMS->GetNetDevLinkedNodes($_GET['id']);
$netdevlist = $LMS->GetNotConnectedDevices($_GET['id']);

unset($netdevlist['total']);
unset($netdevlist['order']);
unset($netdevlist['direction']);

$nodelist = $LMS->GetUnlinkedNodes();
unset($nodelist['totaloff']);
unset($nodelist['totalon']);
unset($nodelist['total']);
unset($nodelist['order']);
unset($nodelist['direction']);

$replacelist = $LMS->GetNetDevList();
$replacelisttotal = $replacelist['total'];
unset($replacelist['order']);
unset($replacelist['total']);
unset($replacelist['direction']);

$netdevips = $LMS->GetNetDevIPs($_GET['id']);

$layout['pagetitle'] = trans('Device Edit: $0 ($1)', $netdevdata['name'], $netdevdata['producer']);

$SMARTY->assign('error',$error);
$SMARTY->assign('netdevinfo',$netdevdata);
$SMARTY->assign('netdevlist',$netdevconnected);
$SMARTY->assign('netcomplist',$netcomplist);
$SMARTY->assign('nodelist',$nodelist);
$SMARTY->assign('netdevips',$netdevips);
$SMARTY->assign('restnetdevlist',$netdevlist);
$SMARTY->assign('replacelist',$replacelist);
$SMARTY->assign('replacelisttotal',$replacelisttotal);
$SMARTY->assign('devlinktype',$SESSION->get('devlinktype'));
$SMARTY->assign('nodelinktype',$SESSION->get('nodelinktype'));

switch($edit)
{
    case 'data':
	$SMARTY->display('netdevedit.html');
    break;
    case 'ip':
	$SMARTY->display('netdevipedit.html');
    break;
    case 'addip':
	$SMARTY->display('netdevipadd.html');
    break;
    default:
	$SMARTY->display('netdevinfo.html');
    break;
}
?>
