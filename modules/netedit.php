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

if(!$LMS->NetworkExists($_GET[id]))
{
	header("Location: ?m=netlist");
	exit(0);
}


$networkdata = $_POST[networkdata];
$network = $LMS->GetNetworkRecord($_GET[id]);


if(isset($networkdata))
{
	foreach($networkdata as $key => $value)
		$networkdata[$key] = trim($value);
	$networkdata[id] = $_GET[id];
	$networkdata[size] = pow(2,32-$networkdata[prefix]);
	$networkdata[addresslong] = ip_long($networkdata[address]);
	$networkdata[mask] = prefix2mask($networkdata[prefix]);
	if(!check_ip($networkdata[address]))
		$error[address] = $lang[error_ip_address_invalid];
	else
	{
		if(getnetaddr($networkdata[address],prefix2mask($networkdata[prefix]))!=$networkdata[address])
		{
			$error[address] = $lang[error_ip_address_is_not_netaddr];
			$networkdata[address] = getnetaddr($networkdata[address],prefix2mask($networkdata[prefix]));
		}
		else
		{
			if($LMS->NetworkOverlaps($networkdata[address],prefix2mask($networkdata[prefix]),$networkdata[id]))
				$error[address] = $lang[error_network_overlaps];
			else
			{
				if($network[assigned] > ($networkdata[size]-2))
					$error[address] = $lang[error_network_too_small];
				else
				{

					if($network[addresslong] != $networkdata[addresslong])
						$networkdata[needshft] = TRUE;

					if($network[prefix] < $networkdata[prefix])
					{
						foreach($network[nodes][address] as $key => $value)
							if($network[nodes][id][$key])
								$lastval = $value;
						if(ip_long($lastval) >= ip_long(getbraddr($network[address],prefix2mask($networkdata[prefix]))))
							$networkdata[needcmp] = TRUE;
					}
				}
			}
		}
	}

	if($networkdata[name]=="")
		$error[name] = $lang[error_no_empty_field];
	elseif(!eregi("^[.a-z0-9-]+$",$networkdata[name]))
		$error[name] = $lang[error_field_contains_incorrect_characters];

	if($networkdata[domain]!="" && !eregi("^[.a-z0-9-]+$",$networkdata[domain]))
		$error[domain] = $lang[error_field_contains_incorrect_characters];

	if($networkdata[dns]!="" && !check_ip($networkdata[dns]))
		$error[dns] = $lang[error_ip_address_invalid];

	if($networkdata[wins]!="" && !check_ip($networkdata[wins]))
		$error[wins] = $lang[error_ip_address_invalid];

	if($networkdata[gateway]!="")
		if(!check_ip($networkdata[gateway]))
			$error[gateway] = $lang[error_ip_address_invalid];

	if($networkdata[dhcpstart]!="")
		if(!check_ip($networkdata[dhcpstart]))
			$error[dhcpstart] = $lang[error_ip_address_invalid];
		else
			if(!isipin($networkdata[dhcpstart],getnetaddr($networkdata[address],prefix2mask($networkdata[prefix])),prefix2mask($networkdata[prefix])) && $networkdata[address]!="")
				$error[dhcpstart] = $lang[error_ip_address_is_not_in_network];

	if($networkdata[dhcpend]!="")
		if(!check_ip($networkdata[dhcpend]))
			$error[dhcpend] = $lang[error_ip_address_invalid];
		else
			if(!isipin($networkdata[dhcpend],getnetaddr($networkdata[address],prefix2mask($networkdata[prefix])),prefix2mask($networkdata[prefix])) && $networkdata[address]!="")
				$error[dhcpend] = $lang[error_ip_address_is_not_in_network];
	
	if(!$error[dhcpstart] && !$error[dhcpend])
	{
		if(($networkdata[dhcpstart]!="" && $networkdata[dhcpend]=="")||($networkdata[dhcpstart]=="" && $networkdata[dhcpend]!=""))
			$error[dhcp] = $lang[error_dhcp1];
		if($networkdata[dhcpstart]!="" && $networkdata[dhcpend]!="" && ip_long($networkdata[dhcpend]) > ip_long($networkdata[dhcpstart]))
			$error[dhcp] = $lang[error_dhcp2];
	}
	
	if(!$error)
	{
		if($networkdata[needcmp])
			$LMS->NetworkCompress($networkdata[id]);
		if($networkdata[needshft])
			$LMS->NetworkShift($network[address],$network[mask],($networkdata[addresslong] - $network[addresslong]));
		$LMS->NetworkUpdate($networkdata);
		header("Location: ?m=netinfo&id=".$networkdata[id]);
		exit(0);
	}	
	
	$network[prefix] = $networkdata[prefix];
	$network[address] = $networkdata[address];
	$network[size] = $networkdata[size];
	$network[dhcpstart] = $networkdata[dhcpstart];
	$network[dhcpend] = $networkdata[dhcpend];
	$network[domain] = $networkdata[domain];
	$network[gateway] = $networkdata[gateway];
	$network[wins] = $networkdata[wins];
	$network[dns] = $networkdata[dns];

}

$prefixlist = $LMS->GetPrefixList();
$networks = $LMS->GetNetworks();
$layout[pagetitle] = $lang[pagetitle_netedit];
$SMARTY->assign("unlockedit",TRUE);
$SMARTY->assign("layout",$layout);
$SMARTY->assign("network",$network);
$SMARTY->assign("networks",$networks);
$SMARTY->assign("networkdata",$networkdata);
$SMARTY->assign("prefixlist",$prefixlist);
$SMARTY->assign("error",$error);
$SMARTY->display("netinfo.html");
?>
