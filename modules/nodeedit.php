<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

if (!$LMS->NodeExists($_GET['id']))
	if (isset($_GET['ownerid']))
		header('Location: ?m=customerinfo&id=' . $_GET['ownerid']);
	else
		header('Location: ?m=nodelist');

$nodeid = intval($_GET['id']);
$customerid = $LMS->GetNodeOwner($nodeid);

switch ($action) {
	case 'link':
		if (empty($_GET['devid']) || !($netdev = $LMS->GetNetDev($_GET['devid']))) {
			$SESSION->redirect('?m=nodeinfo&id=' . $nodeid);
		} else if ($netdev['ports'] > $netdev['takenports']) {
			$LMS->NetDevLinkNode($nodeid, $_GET['devid'], array(
				'type' => isset($_GET['linktype']) ? intval($_GET['linktype']) : 0,
				'technology' => isset($_GET['linktechnology']) ? intval($_GET['linktechnology']) : 0,
				'speed' => isset($_GET['linkspeed']) ? intval($_GET['linkspeed']) : 100000,
				'port' => intval($_GET['port']),
			));
			$SESSION->redirect('?m=nodeinfo&id=' . $nodeid);
		} else {
			$SESSION->redirect('?m=nodeinfo&id=' . $nodeid . '&devid=' . $_GET['devid']);
		}
		break;
	case 'chkmac':
		$DB->Execute('UPDATE nodes SET chkmac=? WHERE id=?', array($_GET['chkmac'], $nodeid));
		if ($SYSLOG) {
			$args = array(
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $nodeid,
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
				'chkmac' => $_GET['chkmac']
			);
			$SYSLOG->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
		}
		$SESSION->redirect('?m=nodeinfo&id=' . $nodeid);
		break;
	case 'duplex':
		$DB->Execute('UPDATE nodes SET halfduplex=? WHERE id=?', array($_GET['duplex'], $nodeid));
		if ($SYSLOG) {
			$args = array(
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $nodeid,
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
				'halfduplex' => $_GET['duplex']
			);
			$SYSLOG->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
		}
		$SESSION->redirect('?m=nodeinfo&id=' . $nodeid);
		break;
	case 'authtype':
		$DB->Execute('UPDATE nodes SET authtype=? WHERE id=?', array(intval($_GET['authtype']), $nodeid));
		if ($SYSLOG) {
			$args = array(
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $nodeid,
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
				'authtype' => intval($_GET['authtype']),
			);
			$SYSLOG->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
		}
		$SESSION->redirect('?m=nodeinfo&id=' . $nodeid);
		break;
}

$nodeinfo = $LMS->GetNode($nodeid);

$macs = array();
foreach ($nodeinfo['macs'] as $key => $value)
	$macs[] = $nodeinfo['macs'][$key]['mac'];
$nodeinfo['macs'] = $macs;

if (!isset($_GET['ownerid']))
	$SESSION->save('backto', $SESSION->get('backto') . '&ownerid=' . $customerid);
else
	$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Node Edit: $a', $nodeinfo['name']);

if (isset($_POST['nodeedit'])) {
	$nodeedit = $_POST['nodeedit'];

	$nodeedit['netid'] = $_POST['nodeeditnetid'];
	$nodeedit['ipaddr'] = $_POST['nodeeditipaddr'];
	$nodeedit['ipaddr_pub'] = $_POST['nodeeditipaddrpub'];
	foreach ($nodeedit['macs'] as $key => $value)
		$nodeedit['macs'][$key] = str_replace('-', ':', $value);

	foreach ($nodeedit as $key => $value)
		if ($key != 'macs' && $key != 'authtype')
			$nodeedit[$key] = trim($value);

	if ($nodeedit['ipaddr'] == '' && $nodeedit['ipaddr_pub'] == '' && empty($nodeedit['macs']) && $nodeedit['name'] == '' && $nodeedit['info'] == '' && $nodeedit['passwd'] == '' && !isset($nodeedit['wholenetwork'])) {
		$SESSION->redirect('?m=nodeinfo&id=' . $nodeedit['id']);
	}

	if(isset($nodeedit['wholenetwork'])) {
		$nodeedit['ipaddr'] = '0.0.0.0';
		$nodeedit['ipaddr_pub'] = '0.0.0.0';
	} elseif (check_ip($nodeedit['ipaddr'])) {
		if ($LMS->IsIPValid($nodeedit['ipaddr'])) {
			if (empty($nodeedit['netid']))
				$nodeedit['netid'] = $DB->GetOne('SELECT id FROM networks WHERE INET_ATON(?) & INET_ATON(mask) = address ORDER BY id LIMIT 1',
					array($nodeedit['ipaddr']));
			if (!$LMS->IsIPInNetwork($nodeedit['ipaddr'], $nodeedit['netid']))
				$error['ipaddr'] = trans('Specified IP address doesn\'t belong to selected network!');
			else {
				$ip = $LMS->GetNodeIPByID($nodeedit['id']);
				if ($ip != $nodeedit['ipaddr'])
					if (!$LMS->IsIPFree($nodeedit['ipaddr'], $nodeedit['netid']))
						$error['ipaddr'] = trans('Specified IP address is in use!');
					elseif ($LMS->IsIPGateway($nodeedit['ipaddr']))
						$error['ipaddr'] = trans('Specified IP address is network gateway!');
			}
		}
		else
			$error['ipaddr'] = trans('Specified IP address doesn\'t overlap with any network!');
	}
	else
		$error['ipaddr'] = trans('Incorrect IP address!');

	if ($nodeedit['ipaddr_pub'] != '0.0.0.0' && $nodeedit['ipaddr_pub'] != '') {
		if (check_ip($nodeedit['ipaddr_pub'])) {
			if ($LMS->IsIPValid($nodeedit['ipaddr_pub'])) {
				$ip = $LMS->GetNodePubIPByID($nodeedit['id']);
				if ($ip != $nodeedit['ipaddr_pub'] && !$LMS->IsIPFree($nodeedit['ipaddr_pub']))
					$error['ipaddr_pub'] = trans('Specified IP address is in use!');
				elseif ($ip != $nodeedit['ipaddr_pub'] && $LMS->IsIPGateway($nodeedit['ipaddr_pub']))
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

	$macs = array();
	foreach ($nodeedit['macs'] as $key => $value)
		if (check_mac($value)) {
			if ($value != '00:00:00:00:00:00' && !ConfigHelper::checkConfig('phpui.allow_mac_sharing')) {
				if (($nodeid = $LMS->GetNodeIDByMAC($value)) != NULL && $nodeid != $nodeinfo['id'])
					$error['mac' . $key] = trans('Specified MAC address is in use!');
			}
			$macs[] = $value;
		} elseif ($value != '')
			$error['mac' . $key] = trans('Incorrect MAC address!');
	if (empty($macs))
		$error['mac0'] = trans('MAC address is required!');
	$nodeedit['macs'] = $macs;

	if ($nodeedit['name'] == '')
		$error['name'] = trans('Node name is required!');
	elseif (!preg_match('/^[_a-z0-9-.]+$/i', $nodeedit['name']))
		$error['name'] = trans('Specified name contains forbidden characters!');
	elseif (strlen($nodeedit['name']) > 32)
		$error['name'] = trans('Node name is too long (max.32 characters)!');
	elseif (($tmp_nodeid = $LMS->GetNodeIDByName($nodeedit['name'])) && $tmp_nodeid != $nodeedit['id'])
		$error['name'] = trans('Specified name is in use!');

	if (strlen($nodeedit['passwd']) > 32)
		$error['passwd'] = trans('Password is too long (max.32 characters)!');

	if (!isset($nodeedit['access']))
		$nodeedit['access'] = 0;
	if (!isset($nodeedit['warning']))
		$nodeedit['warning'] = 0;
	if (!isset($nodeedit['chkmac']))
		$nodeedit['chkmac'] = 0;
	if (!isset($nodeedit['halfduplex']))
		$nodeedit['halfduplex'] = 0;
	if (!isset($nodeedit['netdev']))
		$nodeedit['netdev'] = 0;

	if ($nodeinfo['netdev'] != $nodeedit['netdev'] && $nodeedit['netdev'] != 0) {
		$ports = $DB->GetOne('SELECT ports FROM netdevices WHERE id = ?', array($nodeedit['netdev']));
		$takenports = $LMS->CountNetDevLinks($nodeedit['netdev']);

		if ($ports <= $takenports)
			$error['netdev'] = trans('It scans for free ports in selected device!');
		$nodeinfo['netdev'] = $nodeedit['netdev'];
	}

	if ($nodeedit['netdev'] && ($nodeedit['port'] != $nodeinfo['port'] || $nodeinfo['netdev'] != $nodeedit['netdev'])) {
		if ($nodeedit['port']) {
			if (!isset($ports))
				$ports = $DB->GetOne('SELECT ports FROM netdevices WHERE id = ?', array($nodeedit['netdev']));

			if (!preg_match('/^[0-9]+$/', $nodeedit['port']) || $nodeedit['port'] > $ports) {
				$error['port'] = trans('Incorrect port number!');
			} elseif ($DB->GetOne('SELECT id FROM vnodes WHERE netdev=? AND port=? AND ownerid>0', array($nodeedit['netdev'], $nodeedit['port']))
					|| $DB->GetOne('SELECT 1 FROM netlinks WHERE (src = ? OR dst = ?)
			                AND (CASE src WHEN ? THEN srcport ELSE dstport END) = ?', array($nodeedit['netdev'], $nodeedit['netdev'], $nodeedit['netdev'], $nodeedit['port']))) {
				$error['port'] = trans('Selected port number is taken by other device or node!');
			}
		}
	}

	if (!$nodeedit['ownerid'])
		$error['ownerid'] = trans('Customer not selected!');
	else if ($nodeedit['access'] && $LMS->GetCustomerStatus($nodeedit['ownerid']) < 3)
		$error['access'] = trans('Node owner is not connected!');

	if ($nodeedit['invprojectid'] == '-1') { // nowy projekt
		if (!strlen(trim($nodeedit['projectname']))) {
		 $error['projectname'] = trans('Project name is required');
		}
		if ($DB->GetOne("SELECT * FROM invprojects WHERE name=? AND type<>?",
			array($nodeedit['projectname'], INV_PROJECT_SYSTEM)))
			$error['projectname'] = trans('Project with that name already exists');
	}
	$authtype = 0;
	if (isset($nodeedit['authtype']))
		foreach ($nodeedit['authtype'] as $idx)
			$authtype |= intval($idx);
	$nodeedit['authtype'] = $authtype;

	$hook_data = $LMS->executeHook('nodeedit_validation_before_submit',
		array(
			'nodeedit' => $nodeedit,
			'error' => $error,
		)
	);
	$nodeedit = $hook_data['nodeedit'];
	$error = $hook_data['error'];

	if (!$error) {
		if (empty($nodeedit['teryt'])) {
			$nodeedit['location_city'] = null;
			$nodeedit['location_street'] = null;
			$nodeedit['location_house'] = null;
			$nodeedit['location_flat'] = null;
		}
		if (empty($nodeedit['location']) && !empty($nodeedit['ownerid'])) {
                    $location = $LMS->GetCustomer($nodeedit['ownerid']);
                    $nodeedit['location'] = $location['address'] . ', ' . $location['zip'] . ' ' . $location['city'];
                }

		$nodeedit = $LMS->ExecHook('node_edit_before', $nodeedit);

		$ipi = $nodeedit['invprojectid'];
		if ($ipi == '-1') {
			$DB->BeginTrans();
			$DB->Execute("INSERT INTO invprojects (name, type) VALUES (?, ?)",
				array($nodeedit['projectname'], INV_PROJECT_REGULAR));
			$ipi = $DB->GetLastInsertID('invprojects');
			$DB->CommitTrans();
		}
		if ($nodeedit['invprojectid'] == '-1' || intval($ipi)>0) {
			$nodeedit['invprojectid'] = intval($ipi);
		} else {
			$nodeedit['invprojectid'] = NULL;
		}
		$LMS->NodeUpdate($nodeedit, ($customerid != $nodeedit['ownerid']));
		$LMS->CleanupInvprojects();

		$nodeedit = $LMS->ExecHook('node_edit_after', $nodeedit);

		$hook_data = $LMS->executeHook('nodeedit_after_submit',
			array(
				'nodeedit' => $nodeedit,
			)
		);
		$nodeedit = $hook_data['nodeedit'];

		$SESSION->redirect('?m=nodeinfo&id=' . $nodeedit['id']);
	}

	$nodeinfo['name'] = $nodeedit['name'];
	$nodeinfo['macs'] = $nodeedit['macs'];
	$nodeinfo['ip'] = $nodeedit['ipaddr'];
	$nodeinfo['netid'] = $nodeedit['netid'];
	$nodeinfo['wholenetwork'] = $nodeedit['wholenetwork'];
	$nodeinfo['ip_pub'] = $nodeedit['ipaddr_pub'];
	$nodeinfo['passwd'] = $nodeedit['passwd'];
	$nodeinfo['access'] = $nodeedit['access'];
	$nodeinfo['ownerid'] = $nodeedit['ownerid'];
	$nodeinfo['chkmac'] = $nodeedit['chkmac'];
	$nodeinfo['halfduplex'] = $nodeedit['halfduplex'];
	$nodeinfo['port'] = $nodeedit['port'];
	$nodeinfo['zipwarning'] = empty($zipwarning) ? 0 : 1;
	$nodeinfo['location'] = $nodeedit['location'];
	$nodeinfo['location_city'] = $nodeedit['location_city'];
	$nodeinfo['location_street'] = $nodeedit['location_street'];
	$nodeinfo['location_house'] = $nodeedit['location_house'];
	$nodeinfo['location_flat'] = $nodeedit['location_flat'];
	$nodeinfo['teryt'] = empty($nodeedit['teryt']) ? 0 : 1;
	$nodeinfo['stateid'] = $nodeedit['stateid'];
	$nodeinfo['latitude'] = $nodeedit['latitude'];
	$nodeinfo['longitude'] = $nodeedit['longitude'];
	$nodeinfo['invprojectid'] = $nodeedit['invprojectid'];

	if ($nodeedit['ipaddr_pub'] == '0.0.0.0')
		$nodeinfo['ipaddr_pub'] = '';
} else {
	if ($nodeinfo['city_name'] || $nodeinfo['street_name']) {
		$nodeinfo['teryt'] = true;
		$nodeinfo['location'] = location_str($nodeinfo);
	}
}

if (empty($nodeinfo['macs']))
	$nodeinfo['macs'][] = '';

include(MODULES_DIR . '/customer.inc.php');

if (!ConfigHelper::checkConfig('phpui.big_networks'))
	$SMARTY->assign('customers', $LMS->GetCustomerNames());

include(MODULES_DIR . '/nodexajax.inc.php');

$nodeinfo = $LMS->ExecHook('node_edit_init', $nodeinfo);

$hook_data = $LMS->executeHook('nodeedit_before_display',
	array(
		'nodeedit' => $nodeinfo,
		'smarty' => $SMARTY,
	)
);
$nodeinfo = $hook_data['nodeedit'];

$SMARTY->assign('xajax', $LMS->RunXajax());

$nprojects = $DB->GetAll("SELECT * FROM invprojects WHERE type<>? ORDER BY name",
	array(INV_PROJECT_SYSTEM));
$SMARTY->assign('NNprojects',$nprojects);

$SMARTY->assign('nodesessions', $LMS->GetNodeSessions($nodeid));
$SMARTY->assign('networks', $LMS->GetNetworks(true));
$SMARTY->assign('netdevices', $LMS->GetNetDevNames());
$SMARTY->assign('nodegroups', $LMS->GetNodeGroupNamesByNode($nodeid));
$SMARTY->assign('othernodegroups', $LMS->GetNodeGroupNamesWithoutNode($nodeid));
$SMARTY->assign('error', $error);
$SMARTY->assign('nodeinfo', $nodeinfo);
$SMARTY->assign('objectid', $nodeinfo['id']);
$SMARTY->assign('nodeauthtype', $nodeauthtype);
$SMARTY->display('node/nodeedit.html');

?>
