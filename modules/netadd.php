<?php

/*
 * LMS version 1.2-cvs
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

$netadd = $_POST['netadd'];

if(isset($netadd))
{
	foreach($netadd as $key=>$value)
	{
		$netadd[$key] = trim($value);
	}

	if(
			$netadd['name']=="" &&
			$netadd['address']=="" &&
			$netadd['dns']=="" &&
			$netadd[dns2]=="" &&
			$netadd['domain']=="" &&
			$netadd['gateway']=="" &&
			$netadd['wins']=="" &&
			$netadd['dhcpstart']=="" &&
			$netadd['dhcpend']==""
	)
		
	header("Location: ?m=netadd");


	if($netadd['name']=="")
		$error['name'] = _("You must enter network name!");
	elseif(!eregi("^[.a-z0-9-]+$",$netadd['name']))
		$error['name'] = _("Network name contains forbidden characters!");
	
	if($netadd['domain'] != "" && !eregi("^[.a-z0-9-]+$",$netadd['domain']))
		$error['domain'] = _("Domain name contains forbidden characters!");
	
	if(!check_ip($netadd['address']))
		$error['address'] = _("Network IP address isn't correct!");
	else
	{
		if(getnetaddr($netadd['address'],prefix2mask($netadd['prefix']))!=$netadd['address'])
		{
			$error['address'] = _("That address isn't network address, setting to ").getnetaddr($netadd['address'],prefix2mask($netadd['prefix']));
			$netadd['address'] = getnetaddr($netadd['address'],prefix2mask($netadd['prefix']));
		}
		else
		{
			if($LMS->NetworkOverlaps($netadd['address'],prefix2mask($netadd['prefix'])))
				$error['address'] = _("That address overlaps with other network!");
		}
	}

	if($netadd['interface'] != '' && !eregi('^[a-z0-9]+$',$netadd['interface']))
		$error['interface'] = _("Incorrect interface name!");

	if($netadd['dns']!="" && !check_ip($netadd['dns']))
		$error['dns'] = _("Incorrect DNS server IP address!");
	
	if($netadd[dns2]!="" && !check_ip($netadd[dns2]))
		$error[dns2] = _("Incorrect DNS server IP address!");
	
	if($netadd['wins']!="" && !check_ip($netadd['wins']))
		$error['wins'] = _("Incorrect WINS server IP address!";
	
	if($netadd['gateway']!="")
		if(!check_ip($netadd['gateway']))
			$error['gateway'] = _("Incorrect Gateway IP address!");
	elseif(!isipin($netadd['gateway'],getnetaddr($netadd['address'],prefix2mask($netadd['prefix'])),prefix2mask($netadd['prefix'])))
		$error['gateway'] = _("Gateway IP address don't match with network address class!");
	
	if($netadd['dhcpstart']!="")
		if(!check_ip($netadd['dhcpstart']))
			$error['dhcpstart'] = _("Incorrect IP address for start of DHCP range!");
	elseif(!isipin($netadd['dhcpstart'],getnetaddr($netadd['address'],prefix2mask($netadd['prefix'])),prefix2mask($netadd['prefix'])) && $netadd['address']!="")
		$error['dhcpstart'] = _("IP address for start of DHCP range not overlaps with this network!");
	
	if($netadd['dhcpend']!="")
		if(!check_ip($netadd['dhcpend']))
			$error['dhcpend'] = _("Incorrect IP address for end of DHCP range!");
	elseif(!isipin($netadd['dhcpend'],getnetaddr($netadd['address'],prefix2mask($netadd['prefix'])),prefix2mask($netadd['prefix'])) && $netadd['address']!="")
		$error['dhcpend'] = _("IP address for end of DHCP range not overlaps with this network!";
	
	if(!$error['dhcpstart'] && !$error['dhcpend'])
	{
		if(($netadd['dhcpstart']!="" && $netadd['dhcpend']=="")||($netadd['dhcpstart']=="" && $netadd['dhcpend']!=""))
			$error['dhcp'] = _("Required both IP addresses for DHCP range!");
		if($netadd['dhcpstart']!="" && $netadd['dhcpend']!="" && !(ip_long($netadd['dhcpend']) > ip_long($netadd['dhcpstart'])))
			$error['dhcp'] = _("End of DHCP range must be greater than start!");
	}
	
	if(!$error)
	{
		header("Location: ?m=netinfo&id=".$LMS->NetworkAdd($netadd));
		die;
	}

}

$layout['pagetitle'] = _("New network");

$prefixlist = $LMS->GetPrefixList();
$netlist = $LMS->GetNetworkList();
$SMARTY->assign('layout',$layout);
$SMARTY->assign('error',$error);
$SMARTY->assign('netadd',$netadd);
$SMARTY->assign('prefixlist',$prefixlist);
$SMARTY->assign('netlist',$netlist);
$SMARTY->display('netadd.html');

?>
