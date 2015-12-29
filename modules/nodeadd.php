<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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
$nodedata['authtype'] = 0;

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
	$nodedata['macs'][] = $_GET['premac'];

if(isset($_GET['prename']))
	$nodedata['name'] = $_GET['prename'];

if (isset($_POST['nodedata']))
{
	$nodedata = $_POST['nodedata'];

	$nodedata['netid'] = $_POST['nodedatanetid'];
	$nodedata['ipaddr'] = $_POST['nodedataipaddr'];
	$nodedata['ipaddr_pub'] = $_POST['nodedataipaddr_pub'];
	foreach($nodedata['macs'] as $key => $value)
		$nodedata['macs'][$key] = str_replace('-',':',$value);

	foreach($nodedata as $key => $value)
		if($key != 'macs' && $key != 'authtype')
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
	elseif(!preg_match('/^[_a-z0-9-.]+$/i', $nodedata['name']))
		$error['name'] = trans('Specified name contains forbidden characters!');
	elseif($LMS->GetNodeIDByName($nodedata['name']))
		$error['name'] = trans('Specified name is in use!');

	if(!$nodedata['ipaddr'])
		$error['ipaddr'] = trans('Node IP address is required!');
	elseif(!check_ip($nodedata['ipaddr']))
		$error['ipaddr'] = trans('Incorrect node IP address!');
	elseif(!$LMS->IsIPValid($nodedata['ipaddr']))
		$error['ipaddr'] = trans('Specified IP address doesn\'t overlap with any network!');
	else {
		if (empty($nodedata['netid']))
			$nodedata['netid'] = $DB->GetOne('SELECT id FROM networks WHERE INET_ATON(?) & INET_ATON(mask) = address ORDER BY id LIMIT 1',
				array($nodedata['ipaddr']));
		if (!$LMS->IsIPInNetwork($nodedata['ipaddr'], $nodedata['netid']))
			$error['ipaddr'] = trans('Specified IP address doesn\'t belong to selected network!');
		elseif (!$LMS->IsIPFree($nodedata['ipaddr'], $nodedata['netid']))
			$error['ipaddr'] = trans('Specified IP address is in use!');
		elseif($LMS->IsIPGateway($nodedata['ipaddr']))
			$error['ipaddr'] = trans('Specified IP address is network gateway!');
	}

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

	$macs = array();
	foreach($nodedata['macs'] as $key => $value)
		if(check_mac($value))
		{
			if($value!='00:00:00:00:00:00' && !ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.allow_mac_sharing', false)))
			{
				if($LMS->GetNodeIDByMAC($value))
					$error['mac'.$key] = trans('Specified MAC address is in use!');
			}
			$macs[] = $value;
		}
		elseif($value!='')
			$error['mac'.$key] = trans('Incorrect MAC address!');
	if(empty($macs))
		$error['mac0'] = trans('MAC address is required!');
	$nodedata['macs'] = $macs;

	if(strlen($nodedata['passwd']) > 32)
		$error['passwd'] = trans('Password is too long (max.32 characters)!');

    if (!$nodedata['ownerid'])
        $error['ownerid'] = trans('Customer not selected!');
	else if(! $LMS->CustomerExists($nodedata['ownerid']))
		$error['ownerid'] = trans('You have to select owner!');
	else
	{
		$status = $LMS->GetCustomerStatus($nodedata['ownerid']);
		if($status == 1) // unknown (interested)
			$error['ownerid'] = trans('Selected customer is not connected!');
		elseif($status == 2 && $nodedata['access']) // awaiting
	                $error['access'] = trans('Node owner is not connected!');
	}

	if($nodedata['netdev'])
	{
		$ports = $DB->GetOne('SELECT ports FROM netdevices WHERE id = ?', array($nodedata['netdev']));
	        $takenports = $LMS->CountNetDevLinks($nodedata['netdev']);

		if($ports <= $takenports) 
			$error['netdev'] = trans('No free ports on device!');
		elseif($nodedata['port'])
		{
		        if(!preg_match('/^[0-9]+$/', $nodedata['port']) || $nodedata['port'] > $ports)
		        {
		                $error['port'] = trans('Incorrect port number!');
		        }
		        elseif($DB->GetOne('SELECT id FROM vnodes WHERE netdev=? AND port=? AND ownerid>0',
		        		array($nodedata['netdev'], $nodedata['port']))
			        || $DB->GetOne('SELECT 1 FROM netlinks WHERE (src = ? OR dst = ?)
			                AND (CASE src WHEN ? THEN srcport ELSE dstport END) = ?',
			                array($nodedata['netdev'], $nodedata['netdev'], $nodedata['netdev'], $nodedata['port'])))
			{
			        $error['port'] = trans('Selected port number is taken by other device or node!');
			}
		}
	}
	else
		$nodedata['netdev'] = 0;

	if(!isset($nodedata['chkmac']))	$nodedata['chkmac'] = 0;
	if(!isset($nodedata['halfduplex'])) $nodedata['halfduplex'] = 0;
	

	if ($nodedata['invprojectid'] == '-1') { // nowy projekt
		if (!strlen(trim($nodedata['projectname']))) {
		 $error['projectname'] = trans('Project name is required');
		}
		if ($DB->GetOne("SELECT * FROM invprojects WHERE name=? AND type<>?",
			array($nodedata['projectname'], INV_PROJECT_SYSTEM)))
			$error['projectname'] = trans('Project with that name already exists');
	}

	$authtype = 0;
	if (isset($nodedata['authtype']))
		foreach ($nodedata['authtype'] as $val)
			$authtype |= intval($val);
	$nodedata['authtype'] = $authtype;

	$hook_data = $LMS->executeHook('nodeadd_validation_before_submit',
		array(
			'nodeadd' => $nodedata,
			'error' => $error,
		)
	);
	$nodedata = $hook_data['nodeadd'];
	$error = $hook_data['error'];

	if(!$error)
	{
        if (empty($nodedata['teryt'])) {
            $nodedata['location_city'] = null;
            $nodedata['location_street'] = null;
            $nodedata['location_house'] = null;
            $nodedata['location_flat'] = null;
        }
        if (empty($nodedata['location']) && !empty($nodedata['ownerid'])) {
            $location = $LMS->GetCustomer($nodedata['ownerid']);
            $nodedata['location'] = $location['address'] . ', ' . $location['zip'] . ' ' . $location['city'];
        }



        $nodedata = $LMS->ExecHook('node_add_before', $nodedata);

	$ipi = $nodedata['invprojectid'];
	if ($ipi == '-1') {
		$DB->BeginTrans();
		$DB->Execute("INSERT INTO invprojects (name, type) VALUES (?, ?)",
			array($nodedata['projectname'], INV_PROJECT_REGULAR));
		$ipi = $DB->GetLastInsertID('invprojects');
		$DB->CommitTrans();
	} 
	if ($nodedata['invprojectid'] == '-1' || intval($ipi)>0)
		$nodedata['invprojectid'] = intval($ipi);
	else
		$nodedata['invprojectid'] = NULL;

		$nodeid = $LMS->NodeAdd($nodedata);

		if($nodedata['nodegroup'] != '0')
		{
			$DB->Execute('INSERT INTO nodegroupassignments (nodeid, nodegroupid)
				VALUES (?, ?)', array($nodeid, intval($nodedata['nodegroup'])));
		}

        $nodedata['id'] = $nodeid;
        $nodedata = $LMS->ExecHook('node_add_after', $nodedata);

		$hook_data = $LMS->executeHook('nodeadd_after_submit',
			array(
				'nodeadd' => $nodedata,
			)
		);
		$nodedata = $hook_data['nodeadd'];

		if(!isset($nodedata['reuse']))
		{
			$SESSION->redirect('?m=nodeinfo&id='.$nodeid);
		}

		$ownerid = $nodedata['ownerid'];
		unset($nodedata);

		$nodedata['ownerid'] = $ownerid;
		$nodedata['reuse'] = '1';
	}
	else {
		if($nodedata['ipaddr_pub']=='0.0.0.0')
			$nodedata['ipaddr_pub'] = '';
    }
}

if(empty($nodedata['macs']))
    $nodedata['macs'][] = '';

$layout['pagetitle'] = trans('New Node');

if($customerid = $nodedata['ownerid'])
{
	include(MODULES_DIR.'/customer.inc.php');
}
else
	$SMARTY->assign('allnodegroups', $LMS->GetNodeGroupNames());

if (!ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.big_networks', false)))
{
    $SMARTY->assign('customers', $LMS->GetCustomerNames());
}

$nprojects = $DB->GetAll("SELECT * FROM invprojects WHERE type<>? ORDER BY name",
	array(INV_PROJECT_SYSTEM));
$SMARTY->assign('NNprojects',$nprojects);

include(MODULES_DIR . '/nodexajax.inc.php');

$nodedata = $LMS->ExecHook('node_add_init', $nodedata);

$hook_data = $LMS->executeHook('nodeadd_before_display',
	array(
		'nodeadd' => $nodedata,
		'smarty' => $SMARTY,
	)
);
$nodedata = $hook_data['nodeadd'];

$SMARTY->assign('xajax', $LMS->RunXajax());

$SMARTY->assign('networks', $LMS->GetNetworks(true));
$SMARTY->assign('netdevices', $LMS->GetNetDevNames());
$SMARTY->assign('error', $error);
$SMARTY->assign('nodedata', $nodedata);
$SMARTY->display('node/nodeadd.html');

?>
