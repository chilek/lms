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

if (isset($_POST['netadd']))
{
	$netadd = array_map('trim', $_POST['netadd']);

	if (
			$netadd['name'] == '' &&
			$netadd['address'] == '' &&
			$netadd['dns'] == '' &&
			$netadd['dns2'] == '' &&
			$netadd['domain'] == '' &&
			$netadd['gateway'] == '' &&
			$netadd['wins'] == '' &&
			$netadd['dhcpstart'] == '' &&
			$netadd['dhcpend'] == '' &&
			$netadd['ownerid'] == ''
	)
		header('Location: ?m=netadd');

	if ($netadd['name'] == '')
		$error['name'] = trans('Network name is required!');
	elseif (!preg_match('/^[._a-z0-9-]+$/i', $netadd['name']))
		$error['name'] = trans('Network name contains forbidden characters!');
	
	if ($netadd['domain'] != '' && !preg_match('/^[.a-z0-9-]+$/i', $netadd['domain']))
		$error['domain'] = trans('Specified domain contains forbidden characters!');

	if (empty($netadd['hostid']))
		$error['hostid'] = trans('Host should be selected!');

	if (!check_ip($netadd['address']))
		$error['address'] = trans('Incorrect network IP address!');
	else
	{
		if (getnetaddr($netadd['address'], prefix2mask($netadd['prefix'])) != $netadd['address'])
		{
			$error['address'] = trans('Specified address is not a network address, setting $a',getnetaddr($netadd['address'], prefix2mask($netadd['prefix'])));
			$netadd['address'] = getnetaddr($netadd['address'], prefix2mask($netadd['prefix']));
		}
		else
		{
			if ($LMS->NetworkOverlaps($netadd['address'], prefix2mask($netadd['prefix']), $netadd['hostid']))
				$error['address'] = trans('Specified IP address overlaps with other network!');
		}
	}

	if ($netadd['interface'] != '' && !preg_match('/^[a-z0-9:.]+$/i', $netadd['interface']))
		$error['interface'] = trans('Incorrect interface name!');

	if ($netadd['vlanid'] != '')
		if (!is_numeric($netadd['vlanid']))
			$error['vlanid'] = trans('Vlan ID must be integer!');
		elseif ($netadd['vlanid'] < 1 || $netadd['vlanid'] > 4094)
			$error['vlanid'] = trans('Vlan ID must be between 1 and 4094!');

	if ($netadd['dns'] != '' && !check_ip($netadd['dns']))
		$error['dns'] = trans('Incorrect DNS server IP address!');
	
	if ($netadd['dns2'] != '' && !check_ip($netadd['dns2']))
		$error['dns2'] = trans('Incorrect DNS server IP address!');
	
	if ($netadd['wins'] != '' && !check_ip($netadd['wins']))
		$error['wins'] = trans('Incorrect WINS server IP address!');
	
	if ($netadd['gateway'] != '')
		if (!check_ip($netadd['gateway']))
			$error['gateway'] = trans('Incorrect gateway IP address!');
	elseif (!isipin($netadd['gateway'], getnetaddr($netadd['address'], prefix2mask($netadd['prefix'])), prefix2mask($netadd['prefix'])))
		$error['gateway'] = trans('Specified gateway address does not match with network address!');
	
	if ($netadd['dhcpstart'] != '')
		if (!check_ip($netadd['dhcpstart']))
			$error['dhcpstart'] = trans('Incorrect IP address for DHCP range start!');
	elseif (!isipin($netadd['dhcpstart'], getnetaddr($netadd['address'], prefix2mask($netadd['prefix'])), prefix2mask($netadd['prefix'])) && $netadd['address'] != '')
		$error['dhcpstart'] = trans('IP address for DHCP range start does not match with network address!');
	
	if ($netadd['dhcpend'] != '')
		if (!check_ip($netadd['dhcpend']))
			$error['dhcpend'] = trans('Incorrect IP address for DHCP range end!');
	elseif (!isipin($netadd['dhcpend'], getnetaddr($netadd['address'], prefix2mask($netadd['prefix'])), prefix2mask($netadd['prefix'])) && $netadd['address'] != '')
		$error['dhcpend'] = trans('IP address for DHCP range end does not match with network address!');
	
	if (!isset($error['dhcpstart']) && !isset($error['dhcpend']))
	{
		if (($netadd['dhcpstart'] != '' && $netadd['dhcpend'] == '') || ($netadd['dhcpstart'] == '' && $netadd['dhcpend'] != ''))
			$error['dhcpend'] = trans('Both IP addresses for DHCP range are required!');
		if ($netadd['dhcpstart'] != '' && $netadd['dhcpend'] != '' && !(ip_long($netadd['dhcpend']) >= ip_long($netadd['dhcpstart'])))
			$error['dhcpend'] = trans('End of DHCP range has to be equal or greater than start!');
	}

	if (!empty($netadd['ownerid']) && !$LMS->CustomerExists($netadd['ownerid']))
		$error['ownerid'] = trans('Customer with the specified ID does not exist');

	if (!$error)
	{
		$SESSION->redirect('?m=netinfo&id='.$LMS->NetworkAdd($netadd));
	}

	$SMARTY->assign('error', $error);
	$SMARTY->assign('netadd', $netadd);
} elseif (isset($_GET['ownerid'])) {
	if ($LMS->CustomerExists($_GET['ownerid']) == true) {
		$netadd['ownerid'] = $_GET['ownerid'];
		$SMARTY->assign('netadd', $netadd);
	}
}

$layout['pagetitle'] = trans('New Network');

if (!ConfigHelper::checkConfig('phpui.big_networks'))
	$SMARTY->assign('customers', $LMS->GetCustomerNames());

$SMARTY->assign('prefixlist', $LMS->GetPrefixList());
$SMARTY->assign('hostlist', $LMS->DB->GetAll('SELECT id, name FROM hosts ORDER BY name'));
$SMARTY->display('net/netadd.html');

?>
