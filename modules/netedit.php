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

if(!$LMS->NetworkExists($_GET['id']))
{
	header("Location: ?m=netlist");
	die;
}


if (isset($_SESSION['ntlp'][$_GET['id']]) && !isset($_GET['page']))
	$_GET['page'] = $_SESSION['ntlp'][$_GET['id']];
	
$_SESSION['ntlp'][$_GET['id']] = $_GET['page'];

$networkdata = $_POST['networkdata'];
$network = $LMS->GetNetworkRecord($_GET['id'],$_GET['page'],1024);


if(isset($networkdata))
{
	foreach($networkdata as $key => $value)
		$networkdata[$key] = trim($value);
	$networkdata['id'] = $_GET['id'];
	$networkdata['size'] = pow(2,32-$networkdata['prefix']);
	$networkdata['addresslong'] = ip_long($networkdata['address']);
	$networkdata['mask'] = prefix2mask($networkdata['prefix']);
	if(!check_ip($networkdata['address']))
		$error['address'] = _("Incorrect network IP address!");
	else
	{
		if(getnetaddr($networkdata['address'],prefix2mask($networkdata['prefix']))!=$networkdata['address'])
		{
			$error['address'] = _("IP address isn't a correct network address,<BR> setting on ").getnetaddr($networkdata['address'],prefix2mask($networkdata['prefix']));
			$networkdata['address'] = getnetaddr($networkdata['address'],prefix2mask($networkdata['prefix']));
		}
		else
		{
			if($LMS->NetworkOverlaps($networkdata['address'],prefix2mask($networkdata['prefix']),$networkdata['id']))
				$error['address'] = _("Network overlaps with other network!");
			else
			{
				if($network['assigned'] > ($networkdata['size']-2))
					$error['address'] = _("New network is too small!");
				else
				{

					if($network['addresslong'] != $networkdata['addresslong'])
						$networkdata['needshft'] = TRUE;

					if($network['prefix'] < $networkdata['prefix'])
					{
						foreach($network['nodes']['address'] as $key => $value)
							if($network['nodes']['id'][$key])
								$lastval = $value;
						if(ip_long($lastval) >= ip_long(getbraddr($network['address'],prefix2mask($networkdata['prefix']))))
							$networkdata['needcmp'] = TRUE;
					}
				}
			}
		}
	}

	if($networkdata['interface'] != "" && !eregi('^[a-z0-9]+$',$networkdata['interface']))
		$error['interface'] = _("Incorrect interface name!");

	if($networkdata['name']=="")
		$error['name'] = _("Network name is required!");
	elseif(!eregi("^[.a-z0-9-]+$",$networkdata['name']))
		$error['name'] = _("Network name consists forbidden characters!");

	if($networkdata['domain']!="" && !eregi("^[.a-z0-9-]+$",$networkdata['domain']))
		$error['domain'] = _("Domain name consists forbidden characters!");

	if($networkdata['dns']!="" && !check_ip($networkdata['dns']))
		$error['dns'] = _("Incorrect DNS server IP address!");

	if($networkdata[dns2]!="" && !check_ip($networkdata[dns2]))
		$error[dns2] = _("Incorrect DNS server IP address!");

	if($networkdata['wins']!="" && !check_ip($networkdata['wins']))
		$error['wins'] = _("Incorrect WINS IP address!");

	if($networkdata['gateway']!="")
		if(!check_ip($networkdata['gateway']))
			$error['gateway'] = _("Incorrect gateway IP address!");
		else
			if(!isipin($networkdata['gateway'],getnetaddr($networkdata['address'],prefix2mask($networkdata['prefix'])),prefix2mask($networkdata['prefix'])))
				$error['gateway'] = _("Gateway IP address mismatches for network address class!");

	if($networkdata['dhcpstart']!="")
		if(!check_ip($networkdata['dhcpstart']))
			$error['dhcpstart'] = _("Incorrect IP address for begin of DHCP range!");
		else
			if(!isipin($networkdata['dhcpstart'],getnetaddr($networkdata['address'],prefix2mask($networkdata['prefix'])),prefix2mask($networkdata['prefix'])) && $networkdata['address']!="")
				$error['dhcpstart'] = _("IP address for begin of DHCP range missmatches for network address class!");

	if($networkdata['dhcpend']!="")
		if(!check_ip($networkdata['dhcpend']))
			$error['dhcpend'] = _("Incorrect IP address for end of DHCP range!");
		else
			if(!isipin($networkdata['dhcpend'],getnetaddr($networkdata['address'],prefix2mask($networkdata['prefix'])),prefix2mask($networkdata['prefix'])) && $networkdata['address']!="")
				$error['dhcpend'] = _("IP address for end of DHCP range mismatches for network address class!");
	
	if(!$error['dhcpstart'] && !$error['dhcpend'])
	{
		if(($networkdata['dhcpstart']!="" && $networkdata['dhcpend']=="")||($networkdata['dhcpstart']=="" && $networkdata['dhcpend']!=""))
			$error['dhcp'] = _("Both IP address for DHCP range are required!");
		if($networkdata['dhcpstart']!="" && $networkdata['dhcpend']!="" && !(ip_long($networkdata['dhcpend']) > ip_long($networkdata['dhcpstart'])))
			$error['dhcp'] = _("End of DHCP range must be greater than begin!");
	}
	
	if(!$error)
	{
		if($networkdata['needcmp'])
			$LMS->NetworkCompress($networkdata['id']);
		if($networkdata['needshft'])
			$LMS->NetworkShift($network['address'],$network['mask'],($networkdata['addresslong'] - $network['addresslong']));
		$LMS->NetworkUpdate($networkdata);
		header("Location: ?m=netinfo&id=".$networkdata['id']);
		die;
	}	

	$network['interface'] = $networkdata['interface'];
	$network['prefix'] = $networkdata['prefix'];
	$network['address'] = $networkdata['address'];
	$network['size'] = $networkdata['size'];
	$network['dhcpstart'] = $networkdata['dhcpstart'];
	$network['dhcpend'] = $networkdata['dhcpend'];
	$network['domain'] = $networkdata['domain'];
	$network['gateway'] = $networkdata['gateway'];
	$network['wins'] = $networkdata['wins'];
	$network['dns'] = $networkdata['dns'];
	$network[dns2] = $networkdata[dns2];

}

$prefixlist = $LMS->GetPrefixList();
$networks = $LMS->GetNetworks();
$layout['pagetitle'] = _("Edit Network: ").$network['name'];
$SMARTY->assign('unlockedit',TRUE);
$SMARTY->assign('layout',$layout);
$SMARTY->assign('network',$network);
$SMARTY->assign('networks',$networks);
$SMARTY->assign('networkdata',$networkdata);
$SMARTY->assign('prefixlist',$prefixlist);
$SMARTY->assign('error',$error);
$SMARTY->display('netinfo.html');

?>
