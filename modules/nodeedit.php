<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

switch ($action) {
	case 'link':
		if (empty($_GET['devid']) || !($netdev = $LMS->GetNetDev($_GET['devid']))) {
			$SESSION->redirect('?m=nodeinfo&id=' . $_GET['id']);
		} else if ($netdev['ports'] > $netdev['takenports']) {
			$LMS->NetDevLinkNode($_GET['id'], $_GET['devid'], isset($_GET['linktype']) ? intval($_GET['linktype']) : 0, isset($_GET['linkspeed']) ? intval($_GET['linkspeed']) : 100000, intval($_GET['port']));
			$SESSION->redirect('?m=nodeinfo&id=' . $_GET['id']);
		} else {
			$SESSION->redirect('?m=nodeinfo&id=' . $_GET['id'] . '&devid=' . $_GET['devid']);
		}
		break;
	case 'chkmac':
		$DB->Execute('UPDATE nodes SET chkmac=? WHERE id=?', array($_GET['chkmac'], $_GET['id']));
		$SESSION->redirect('?m=nodeinfo&id=' . $_GET['id']);
		break;
	case 'duplex':
		$DB->Execute('UPDATE nodes SET halfduplex=? WHERE id=?', array($_GET['duplex'], $_GET['id']));
		$SESSION->redirect('?m=nodeinfo&id=' . $_GET['id']);
		break;
}

$nodeid = intval($_GET['id']);
$customerid = $LMS->GetNodeOwner($nodeid);
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

	$nodeedit['ipaddr'] = $_POST['nodeeditipaddr'];
	$nodeedit['ipaddr_pub'] = $_POST['nodeeditipaddrpub'];
	foreach ($nodeedit['macs'] as $key => $value)
		$nodeedit['macs'][$key] = str_replace('-', ':', $value);

	foreach ($nodeedit as $key => $value)
		if ($key != 'macs')
			$nodeedit[$key] = trim($value);

	if ($nodeedit['ipaddr'] == '' && $nodeedit['ipaddr_pub'] == '' && empty($nodeedit['macs']) && $nodeedit['name'] == '' && $nodeedit['info'] == '' && $nodeedit['passwd'] == '') {
		$SESSION->redirect('?m=nodeinfo&id=' . $nodeedit['id']);
	}

	if (check_ip($nodeedit['ipaddr'])) {
		if ($LMS->IsIPValid($nodeedit['ipaddr'])) {
			$ip = $LMS->GetNodeIPByID($nodeedit['id']);
			if ($ip != $nodeedit['ipaddr'] && !$LMS->IsIPFree($nodeedit['ipaddr']))
				$error['ipaddr'] = trans('Specified IP address is in use!');
			elseif ($ip != $nodeedit['ipaddr'] && $LMS->IsIPGateway($nodeedit['ipaddr']))
				$error['ipaddr'] = trans('Specified IP address is network gateway!');
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
			if ($value != '00:00:00:00:00:00' && (!isset($CONFIG['phpui']['allow_mac_sharing']) || !chkconfig($CONFIG['phpui']['allow_mac_sharing']))) {
				if (($nodeid = $LMS->GetNodeIDByMAC($value)) != NULL && $nodeid != $nodeinfo['id'])
					$error['mac' . $key] = trans('Specified MAC address is in use!');
			}
			$macs[] = $value;
		}
		elseif ($value != '')
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
			} elseif ($DB->GetOne('SELECT id FROM nodes WHERE netdev=? AND port=? AND ownerid>0', array($nodeedit['netdev'], $nodeedit['port']))
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

	if (!$error) {
		if (empty($nodeedit['teryt'])) {
			$nodeedit['location_city'] = null;
			$nodeedit['location_street'] = null;
			$nodeedit['location_house'] = null;
			$nodeedit['location_flat'] = null;
		}

		$nodeedit = $LMS->ExecHook('node_edit_before', $nodeedit);

		$LMS->NodeUpdate($nodeedit, ($customerid != $nodeedit['ownerid']));

		$nodeedit = $LMS->ExecHook('node_edit_after', $nodeedit);

		$SESSION->redirect('?m=nodeinfo&id=' . $nodeedit['id']);
	}

	$nodeinfo['name'] = $nodeedit['name'];
	$nodeinfo['macs'] = $nodeedit['macs'];
	$nodeinfo['ip'] = $nodeedit['ipaddr'];
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

	if ($nodeedit['ipaddr_pub'] == '0.0.0.0')
		$nodeinfo['ipaddr_pub'] = '';
}
else {
	if ($nodeinfo['city_name'] || $nodeinfo['street_name']) {
		$nodeinfo['teryt'] = true;
		$nodeinfo['location'] = location_str($nodeinfo);
	}
}

if (empty($nodeinfo['macs']))
	$nodeinfo['macs'][] = '';

include(MODULES_DIR . '/customer.inc.php');

if (!isset($CONFIG['phpui']['big_networks']) || !chkconfig($CONFIG['phpui']['big_networks'])) {
	$SMARTY->assign('customers', $LMS->GetCustomerNames());
}

$nodeinfo = $LMS->ExecHook('node_edit_init', $nodeinfo);

include(MODULES_DIR . '/nodexajax.inc.php');

$SMARTY->assign('xajax', $LMS->RunXajax());

$SMARTY->assign('netdevices', $LMS->GetNetDevNames());
$SMARTY->assign('nodegroups', $LMS->GetNodeGroupNamesByNode($nodeid));
$SMARTY->assign('othernodegroups', $LMS->GetNodeGroupNamesWithoutNode($nodeid));
$SMARTY->assign('error', $error);
$SMARTY->assign('nodeinfo', $nodeinfo);
$SMARTY->display('nodeedit.html');
?>
