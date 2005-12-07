<?php

/*
 * LMS version 1.8-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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
	$SESSION->redirect('?m=netlist');
}

if($SESSION->is_set('ntlp.'.$_GET['id']) && ! isset($_GET['page']))
	$SESSION->restore('ntlp.'.$_GET['id'], $_GET['page']);

$SESSION->save('ntlp.'.$_GET['id'], $_GET['page']);
	
$network = $LMS->GetNetworkRecord($_GET['id'],$_GET['page'], $LMS->CONFIG['phpui']['networkhosts_pagelimit']);

if(isset($_POST['networkdata']))
{
	$networkdata = $_POST['networkdata'];

	foreach($networkdata as $key => $value)
		$networkdata[$key] = trim($value);
		
	$networkdata['id'] = $_GET['id'];
	$networkdata['size'] = pow(2,32-$networkdata['prefix']);
	$networkdata['addresslong'] = ip_long($networkdata['address']);
	$networkdata['mask'] = prefix2mask($networkdata['prefix']);
	
	if(!check_ip($networkdata['address']))
		$error['address'] = trans('Incorrect network IP address!');
	else
	{
		if(getnetaddr($networkdata['address'],prefix2mask($networkdata['prefix']))!=$networkdata['address'])
		{
			$error['address'] = trans('Specified address is not a network address, setting $0',getnetaddr($networkdata['address'],prefix2mask($networkdata['prefix'])));
			$networkdata['address'] = getnetaddr($networkdata['address'],prefix2mask($networkdata['prefix']));
		}
		else
		{
			if($LMS->NetworkOverlaps($networkdata['address'],prefix2mask($networkdata['prefix']),$networkdata['id']))
				$error['address'] = trans('Specified IP address overlaps with other network!');
			else
			{
				if($network['assigned'] > ($networkdata['size']-2))
					$error['address'] = trans('New network is too small!');
				else
				{
					$node = $DB->GetRow('SELECT MAX(CASE WHEN ipaddr_pub != 0 THEN ipaddr_pub ELSE ipaddr END) AS last,
								    MIN(CASE WHEN ipaddr_pub != 0 THEN ipaddr_pub ELSE ipaddr END) AS first
							    FROM nodes 
							    WHERE (ipaddr>? AND ipaddr<?) OR (ipaddr_pub>? AND ipaddr_pub<?)',
							    array($network['addresslong'],ip_long($network['broadcast']),$network['addresslong'],ip_long($network['broadcast'])));
				
					if($node['first'] < $networkdata['addresslong'] ||
					    $node['last'] >= ip_long(getbraddr($networkdata['address'],prefix2mask($networkdata['prefix']))))
					{
						$shift = $networkdata['addresslong'] - $network['addresslong'];
						if($node['first'] + $shift < $networkdata['addresslong'] ||
						    $node['last'] + $shift >= ip_long(getbraddr($networkdata['address'],prefix2mask($networkdata['prefix'])))
						)
							$error['address'] = trans('New network is too small. Put in order IP addresses first!');
						else
							$networkdata['needshft'] = TRUE;
					}
				}
			}
		}
	}

	if($networkdata['interface'] != '' && !eregi('^[a-z0-9:.]+$',$networkdata['interface']))
		$error['interface'] = trans('Incorrect interface name!');

	if($networkdata['name']=='')
		$error['name'] = trans('Network name is required!');
	elseif(!eregi('^[._a-z0-9-]+$',$networkdata['name']))
		$error['name'] = trans('Network name contains forbidden characters!');

	if($networkdata['domain']!='' && !eregi('^[.a-z0-9-]+$',$networkdata['domain']))
		$error['domain'] = trans('Specified domain contains forbidden characters!');

	if($networkdata['dns']!='' && !check_ip($networkdata['dns']))
		$error['dns'] = trans('Incorrect DNS server IP address!');

	if($networkdata['dns2']!='' && !check_ip($networkdata['dns2']))
		$error['dns2'] = trans('Incorrect DNS server IP address!');

	if($networkdata['wins']!='' && !check_ip($networkdata['wins']))
		$error['wins'] =  trans('Incorrect WINS server IP address!');

	if($networkdata['gateway']!='')
		if(!check_ip($networkdata['gateway']))
			$error['gateway'] = trans('Incorrect gateway IP address!');
		else
			if(!isipin($networkdata['gateway'],getnetaddr($networkdata['address'],prefix2mask($networkdata['prefix'])),prefix2mask($networkdata['prefix'])))
				$error['gateway'] =  trans('Specified gateway address does not match with network address!');

	if($networkdata['dhcpstart']!='')
		if(!check_ip($networkdata['dhcpstart']))
			$error['dhcpstart'] = trans('Incorrect IP address for DHCP range start!');
		else
			if(!isipin($networkdata['dhcpstart'],getnetaddr($networkdata['address'],prefix2mask($networkdata['prefix'])),prefix2mask($networkdata['prefix'])) && $networkdata['address']!="")
				$error['dhcpstart'] = trans('IP address for DHCP range start does not match with network address!');

	if($networkdata['dhcpend']!='')
		if(!check_ip($networkdata['dhcpend']))
			$error['dhcpend'] =  trans('Incorrect IP address for DHCP range end!');
		else
			if(!isipin($networkdata['dhcpend'],getnetaddr($networkdata['address'],prefix2mask($networkdata['prefix'])),prefix2mask($networkdata['prefix'])) && $networkdata['address']!="")
				$error['dhcpend'] = trans('IP address for DHCP range end does not match with network address!');
	
	if(!isset($error['dhcpstart']) && !isset($error['dhcpend']))
	{
		if(($networkdata['dhcpstart']!='' && $networkdata['dhcpend']=='')||($networkdata['dhcpstart']=='' && $networkdata['dhcpend']!=''))
			$error['dhcp'] = trans('Both IP addresses for DHCP range are required!');
		if($networkdata['dhcpstart']!="" && $networkdata['dhcpend']!="" && !(ip_long($networkdata['dhcpend']) > ip_long($networkdata['dhcpstart'])))
			$error['dhcp'] = trans('End of DHCP range has to be greater than start!');
	}
	
	if(!$error)
	{
	        if(isset($networkdata['needshft']) && $networkdata['needshft'])
		        $LMS->NetworkShift($network['address'],$network['mask'],$networkdata['addresslong'] - $network['addresslong']);

		$LMS->NetworkUpdate($networkdata);
		$SESSION->redirect('?m=netinfo&id='.$networkdata['id']);
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
	$network['dns2'] = $networkdata['dns2'];
}

$prefixlist = $LMS->GetPrefixList();
$networks = $LMS->GetNetworks();
$layout['pagetitle'] = trans('Network Edit: $0',$network['name']);
$SMARTY->assign('unlockedit',TRUE);
$SMARTY->assign('network',$network);
$SMARTY->assign('networks',$networks);
$SMARTY->assign('netlistsize',sizeof($networks));
$SMARTY->assign('prefixlist',$prefixlist);
$SMARTY->assign('error',$error);
$SMARTY->display('netinfo.html');

?>
