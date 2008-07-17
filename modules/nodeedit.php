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

$action = isset($_GET['action']) ? $_GET['action'] : '';

if(!empty($_POST['marks']) && !empty($_GET['groupid']))
{
	foreach($_POST['marks'] as $mark)
		if($action == 'unsetgroup')
			$DB->Execute('DELETE FROM nodegroupassignments
					WHERE nodegroupid = ? AND nodeid = ?',
					array($_GET['groupid'], $mark));
		elseif($action == 'setgroup')
			if(!$DB->GetOne('SELECT 1 FROM nodegroupassignments
					WHERE nodegroupid = ? AND nodeid = ?',
					array($_GET['groupid'], $mark)))
				$DB->Execute('INSERT INTO nodegroupassignments 
					(nodegroupid, nodeid) VALUES (?, ?)',
					array($_GET['groupid'], $mark));

	$SESSION->redirect('?'.$SESSION->get('backto'));
}

if(!$LMS->NodeExists($_GET['id']))
	if(isset($_GET['ownerid']))
		header('Location: ?m=customerinfo&id='.$_GET['ownerid']);
	else
		header('Location: ?m=nodelist');

switch($action)
{
	case 'link':
		$netdev = $LMS->GetNetDev($_GET['devid']); 

		if($netdev['ports'] > $netdev['takenports']) 
		{
			$LMS->NetDevLinkNode($_GET['id'],$_GET['devid'],
				empty($_GET['linktype']) ? 0 : 1, intval($_GET['port']));
			$SESSION->redirect('?m=nodeinfo&id='.$_GET['id']);
		}
		else
		{
			$SESSION->redirect('?m=nodeinfo&id='.$_GET['id'].'&devid='.$_GET['devid']);
		}
	break;
	case 'chkmac':
		$DB->Execute('UPDATE nodes SET chkmac=? WHERE id=?', array($_GET['chkmac'], $_GET['id']));
		$SESSION->redirect('?m=nodeinfo&id='.$_GET['id']);
	break;
	case 'duplex':
		$DB->Execute('UPDATE nodes SET halfduplex=? WHERE id=?', array($_GET['duplex'], $_GET['id']));
		$SESSION->redirect('?m=nodeinfo&id='.$_GET['id']);
	break;
	case 'nodegroupdelete':
		$DB->Execute('DELETE FROM nodegroupassignments WHERE nodeid=? AND nodegroupid=?',
				array(intval($_GET['id']), intval($_GET['nodegroupid'])));
		$SESSION->redirect('?'.$SESSION->get('backto'));
	break;
	case 'nodegroupadd':
		if(!empty($_POST['nodegroupid']))
			$DB->Execute('INSERT INTO nodegroupassignments (nodeid, nodegroupid)
				VALUES (?, ?)', array(intval($_GET['id']), intval($_POST['nodegroupid'])));
		$SESSION->redirect('?'.$SESSION->get('backto'));
	break;
}

$nodeid = intval($_GET['id']);
$ownerid = $LMS->GetNodeOwner($nodeid);
$nodeinfo = $LMS->GetNode($nodeid);

if(!isset($_GET['ownerid']))
	$SESSION->save('backto', $SESSION->get('backto') . '&ownerid='.$ownerid);
else
	$SESSION->save('backto', $_SERVER['QUERY_STRING']);
							
$layout['pagetitle'] = trans('Node Edit: $0', $nodeinfo['name']);

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
			$error['ipaddr'] = trans('Specified IP address doesn\'t overlap with any network!');
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
        	                $error['ipaddr_pub'] = trans('Specified IP address doesn\'t overlap with any network!');
        	}
        	else
        	        $error['ipaddr_pub'] = trans('Incorrect IP address!');
	}
	else
		$nodeedit['ipaddr_pub'] = '0.0.0.0';

	if(check_mac($nodeedit['mac']))
	{
		if($nodeedit['mac']!='00:00:00:00:00:00' && (!isset($CONFIG['phpui']['allow_mac_sharing']) || !chkconfig($CONFIG['phpui']['allow_mac_sharing'])))
		{
			if($nodeinfo['mac'] != $nodeedit['mac'] && $LMS->GetNodeIDByMAC($nodeedit['mac']))
				$error['mac'] = trans('Specified MAC address is in use!');
		}
	}
	else
		$error['mac'] = trans('Incorrect MAC address!');

	if($nodeedit['name']=='')
		$error['name'] = trans('Node name is required!');
	elseif($LMS->GetNodeIDByName($nodeedit['name']) && $LMS->GetNodeIDByName($nodeedit['name']) != $nodeedit['id'])
		$error['name'] = trans('Specified name is in use!');
	elseif(!eregi('^[_a-z0-9-]+$',$nodeedit['name']))
		$error['name'] = trans('Specified name contains forbidden characters!');
	elseif(strlen($nodeedit['name'])>32)
		$error['name'] = trans('Node name is too long (max.32 characters)!');

	if(strlen($nodeedit['passwd'])>32)
		$error['passwd'] = trans('Password is too long (max.32 characters)!');

	if(!isset($nodeedit['access']))	$nodeedit['access'] = 0;
        if(!isset($nodeedit['warning'])) $nodeedit['warning'] = 0;	
	if(!isset($nodeedit['chkmac']))	$nodeedit['chkmac'] = 0;
	if(!isset($nodeedit['halfduplex'])) $nodeedit['halfduplex'] = 0;

	if($nodeinfo['netdev'] != $nodeedit['netdev'] && $nodeedit['netdev'] != 0)
	{
		$ports = $DB->GetOne('SELECT ports FROM netdevices WHERE id = ?', array($nodeedit['netdev']));
	        $takenports = $LMS->CountNetDevLinks($nodeedit['netdev']);

		if($ports <= $takenports)
		    $error['netdev'] = trans('It scans for free ports in selected device!');
		$nodeinfo['netdev'] = $nodeedit['netdev'];
	}

	if($nodeedit['netdev'] && ($nodeedit['port'] != $nodeinfo['port'] || $nodeinfo['netdev'] != $nodeedit['netdev']))
    	{
		if($nodeedit['port'])
		{
			if(!isset($ports))
				$ports = $DB->GetOne('SELECT ports FROM netdevices WHERE id = ?', array($nodeedit['netdev']));
		
		        if(!ereg('^[0-9]+$', $nodeedit['port']) || $nodeedit['port'] > $ports)
		        {
		                $error['port'] = trans('Incorrect port number!');
		        }
		        elseif($DB->GetOne('SELECT id FROM nodes WHERE netdev=? AND port=? AND ownerid>0',
		        		array($nodeedit['netdev'], $nodeedit['port']))
			        || $DB->GetOne('SELECT 1 FROM netlinks WHERE (src = ? OR dst = ?)
			                AND (CASE src WHEN ? THEN srcport ELSE dstport END) = ?',
			                array($nodeedit['netdev'], $nodeedit['netdev'], $nodeedit['netdev'], $nodeedit['port'])))
			{
			        $error['port'] = trans('Selected port number is taken by other device or node!');
			}
		}
	}
	
	if($nodeedit['access'] && $LMS->GetCustomerStatus($nodeedit['ownerid']) < 3)
		$error['access'] = trans('Node owner is not connected!');

	if(!$error)
	{
		$LMS->NodeUpdate($nodeedit, ($ownerid != $nodeedit['ownerid']));
		$SESSION->redirect('?m=nodeinfo&id='.$nodeedit['id']);
		die;
	}

	$nodeinfo['name'] = $nodeedit['name'];
	$nodeinfo['mac'] = $nodeedit['mac'];
	$nodeinfo['ip'] = $nodeedit['ipaddr'];
	$nodeinfo['ip_pub'] = $nodeedit['ipaddr_pub'];
	$nodeinfo['passwd'] = $nodeedit['passwd'];
	$nodeinfo['access'] = $nodeedit['access'];
	$nodeinfo['ownerid'] = $nodeedit['ownerid'];
	$nodeinfo['chkmac'] = $nodeedit['chkmac'];
	$nodeinfo['halfduplex'] = $nodeedit['halfduplex'];
	$nodeinfo['port'] = $nodeedit['port'];

	if($nodeedit['ipaddr_pub']=='0.0.0.0')
		$nodeinfo['ipaddr_pub'] = '';
}

$customerinfo = $LMS->GetCustomer($ownerid);
$customers = $LMS->GetCustomerNames();
$tariffs = $LMS->GetTariffs();
$assignments = $LMS->GetCustomerAssignments($ownerid);
$balancelist = $LMS->GetCustomerBalanceList($ownerid);
$customergroups = $LMS->CustomergroupGetForCustomer($ownerid);
$othercustomergroups = $LMS->GetGroupNamesWithoutCustomer($ownerid);
$documents = $LMS->GetDocuments($ownerid, 10);
$netdevices = $LMS->GetNetDevNames();
$taxeslist = $LMS->GetTaxes();
$customernodes = $LMS->GetCustomerNodes($ownerid);
$nodegroups = $LMS->GetNodeGroupNamesByNode($nodeid);
$othernodegroups = $LMS->GetNodeGroupNamesWithoutNode($nodeid);
$allnodegroups = $LMS->GetNodeGroupNames();

if(isset($CONFIG['phpui']['ewx_support']) && chkconfig($CONFIG['phpui']['ewx_support']))
{
        $SMARTY->assign('ewx_channelid', $DB->GetOne('SELECT MAX(channelid) FROM ewx_stm_nodes, nodes
                                        WHERE nodeid = nodes.id AND ownerid = ?', array($ownerid)));
}

$SMARTY->assign('netdevices',$netdevices);
$SMARTY->assign('balancelist',$balancelist);
$SMARTY->assign('assignments',$assignments);
$SMARTY->assign('customernodes',$customernodes);
$SMARTY->assign('customergroups',$customergroups);
$SMARTY->assign('othercustomergroups',$othercustomergroups);
$SMARTY->assign('allnodegroups',$allnodegroups);
$SMARTY->assign('nodegroups',$nodegroups);
$SMARTY->assign('othernodegroups',$othernodegroups);
$SMARTY->assign('tariffs',$tariffs);
$SMARTY->assign('error',$error);
$SMARTY->assign('customerinfo',$customerinfo);
$SMARTY->assign('nodeinfo',$nodeinfo);
$SMARTY->assign('customers',$customers);
$SMARTY->assign('documents', $documents);
$SMARTY->assign('taxeslist', $taxeslist);
$SMARTY->display('nodeedit.html');

?>
