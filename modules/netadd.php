<?

/*
 * LMS version 1.1-cvs
 *
 *  (C) Copyright 2001-2003 LMS Developers
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

$netadd = $_POST[netadd];

if(isset($netadd))
{
	foreach($netadd as $key=>$value)
	{
		$netadd[$key] = trim($value);
	}

	if(
			$netadd[name]=="" &&
			$netadd[address]=="" &&
			$netadd[dns]=="" &&
			$netadd[domain]=="" &&
			$netadd[gateway]=="" &&
			$netadd[wins]=="" &&
			$netadd[dhcpstart]=="" &&
			$netadd[dhcpend]==""
	)
		
	header("Location: ?m=netadd");


	if($netadd[name]=="")
		$error[name] = $lang[error_no_empty_field];
	elseif(!eregi("^[.a-z0-9-]+$",$netadd[name]))
		$error[name] = $lang[error_field_contains_incorrect_characters];
	
	if($netadd[domain] != "" && !eregi("^[.a-z0-9-]+$",$netadd[domain]))
		$error[domain] = $lang[error_field_contains_incorrect_characters];
	
	if(!check_ip($netadd[address]))
		$error[address] = $lang[error_ip_address_invalid];
	else
	{
		if(getnetaddr($netadd[address],prefix2mask($netadd[prefix]))!=$netadd[address])
		{
			$error[address] = $lang[error_ip_address_is_not_netaddr];
			$netadd[address] = getnetaddr($netadd[address],prefix2mask($netadd[prefix]));
		}
		else
		{
			if($LMS->NetworkOverlaps($netadd[address],prefix2mask($netadd[prefix])))
				$error[address] = $lang[error_network_overlaps];
		}
	}
	
	if($netadd[dns]!="" && !check_ip($netadd[dns]))
		$error[dns] = $lang[error_ip_address_invalid];
	
	if($netadd[wins]!="" && !check_ip($netadd[wins]))
		$error[wins] = $lang[error_ip_address_invalid];
	
	if($netadd[gateway]!="")
		if(!check_ip($netadd[gateway]))
			$error[gateway] = $lang[error_ip_address_invalid];
	
	if($netadd[dhcpstart]!="")
		if(!check_ip($netadd[dhcpstart]))
			$error[dhcpstart] = $lang[error_ip_address_invalid];
	elseif(!isipin($netadd[dhcpstart],getnetaddr($netadd[address],prefix2mask($netadd[prefix])),prefix2mask($netadd[prefix])) && $netadd[address]!="")
		$error[dhcpstart] = $lang[error_ip_address_is_not_in_network];
	
	if($netadd[dhcpend]!="")
		if(!check_ip($netadd[dhcpend]))
			$error[dhcpend] = $lang[error_ip_address_invalid];
	elseif(!isipin($netadd[dhcpend],getnetaddr($netadd[address],prefix2mask($netadd[prefix])),prefix2mask($netadd[prefix])) && $netadd[address]!="")
		$error[dhcpend] = $error[error_ip_address_is_not_in_network];
	
	if(!$error[dhcpstart] && !$error[dhcpend])
	{
		if(($netadd[dhcpstart]!="" && $netadd[dhcpend]=="")||($netadd[dhcpstart]=="" && $netadd[dhcpend]!=""))
			$error[dhcp] = $lang[error_dhcp];
		if($netadd[dhcpstart]!="" && $netadd[dhcpend]!="" && !(ip_long($netadd[dhcpend]) > ip_long($netadd[dhcpstart])))
			$error[dhcp] = $lang[error_dhcp2];
	}
	
	if(!$error)
	{
		header("Location: ?m=netinfo&id=".$LMS->NetworkAdd($netadd));
		exit(0);
	}

}

$layout[pagetitle] = $lang[pagetitle_netadd];

$prefixlist = $LMS->GetPrefixList();
$netlist = $LMS->GetNetworkList();
$SMARTY->assign("layout",$layout);
$SMARTY->assign("error",$error);
$SMARTY->assign("netadd",$netadd);
$SMARTY->assign("prefixlist",$prefixlist);
$SMARTY->assign("netlist",$netlist);
$SMARTY->display("netadd.html");

?>
