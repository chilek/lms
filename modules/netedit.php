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
	die;
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
		$error[address] = "Podany adres IP jest nieprawid�owy!";
	else
	{
		if(getnetaddr($networkdata[address],prefix2mask($networkdata[prefix]))!=$networkdata[address])
		{
			$error[address] = "Podany adres nie jest pocz�tkowym adresem sieci,<BR> ustawiam na ".getnetaddr($networkdata[address],prefix2mask($networkdata[prefix]));
			$networkdata[address] = getnetaddr($networkdata[address],prefix2mask($networkdata[prefix]));
		}
		else
		{
			if($LMS->NetworkOverlaps($networkdata[address],prefix2mask($networkdata[prefix]),$networkdata[id]))
				$error[address] = "Podana sie� pokrywa si� z inn� sieci�!";
			else
			{
				if($network[assigned] > ($networkdata[size]-2))
					$error[address] = "Nowa sie� jest za ma�a!";
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
		$error[name] = "Musisz poda� nazw� sieci!";
	elseif(!eregi("^[.a-z0-9-]+$",$networkdata[name]))
		$error[name] = "Podana nazwa zawiera nieprawid�owe znaki!";

	if($networkdata[domain]!="" && !eregi("^[.a-z0-9-]+$",$networkdata[domain]))
		$error[domain] = "Podana domena zawiera nieprawid�ow znaki";

	if($networkdata[dns]!="" && !check_ip($networkdata[dns]))
		$error[dns] = "Podany adres IP jest nie prawid�owy!";

	if($networkdata[dns2]!="" && !check_ip($networkdata[dns2]))
		$error[dns2] = "Podany adres IP jest nie prawid�owy!";

	if($networkdata[wins]!="" && !check_ip($networkdata[wins]))
		$error[wins] = "Podany adres IP jest nie prawid�owy!";

	if($networkdata[gateway]!="")
		if(!check_ip($networkdata[gateway]))
			$error[gateway] = "Podany adres IP jest nie prawid�owy!";
		else
			if(!isipin($networkdata[gateway],getnetaddr($networkdata[address],prefix2mask($networkdata[prefix])),prefix2mask($networkdata[prefix])))
				$error[gateway] = "Podany adres gateway'a nie pasuje do adresu sieci!";

	if($networkdata[dhcpstart]!="")
		if(!check_ip($networkdata[dhcpstart]))
			$error[dhcpstart] = "Podany adres IP jest nieprawid�owy!";
		else
			if(!isipin($networkdata[dhcpstart],getnetaddr($networkdata[address],prefix2mask($networkdata[prefix])),prefix2mask($networkdata[prefix])) && $networkdata[address]!="")
				$error[dhcpstart] = "Podany adres IP nie nale�y do tej sieci!";

	if($networkdata[dhcpend]!="")
		if(!check_ip($networkdata[dhcpend]))
			$error[dhcpend] = "Podany adres IP jest nieprawid�owy!";
		else
			if(!isipin($networkdata[dhcpend],getnetaddr($networkdata[address],prefix2mask($networkdata[prefix])),prefix2mask($networkdata[prefix])) && $networkdata[address]!="")
				$error[dhcpend] = "Podany adres IP nie nale�y do tej sieci!";
	
	if(!$error[dhcpstart] && !$error[dhcpend])
	{
		if(($networkdata[dhcpstart]!="" && $networkdata[dhcpend]=="")||($networkdata[dhcpstart]=="" && $networkdata[dhcpend]!=""))
			$error[dhcp] = "Musisz poda� obydwa zakresy IP dla DHCP!";
		if($networkdata[dhcpstart]!="" && $networkdata[dhcpend]!="" && !(ip_long($networkdata[dhcpend]) > ip_long($networkdata[dhcpstart])))
			$error[dhcp] = "Koniec zakresu DHCP musi by� wi�kszy ni� start!";
	}
	
	if(!$error)
	{
		if($networkdata[needcmp])
			$LMS->NetworkCompress($networkdata[id]);
		if($networkdata[needshft])
			$LMS->NetworkShift($network[address],$network[mask],($networkdata[addresslong] - $network[addresslong]));
		$LMS->NetworkUpdate($networkdata);
		header("Location: ?m=netinfo&id=".$networkdata[id]);
		die;
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
	$network[dns2] = $networkdata[dns2];

}

$prefixlist = $LMS->GetPrefixList();
$networks = $LMS->GetNetworks();
$layout[pagetitle]="Edytowanie sieci";
$SMARTY->assign("unlockedit",TRUE);
$SMARTY->assign("layout",$layout);
$SMARTY->assign("network",$network);
$SMARTY->assign("networks",$networks);
$SMARTY->assign("networkdata",$networkdata);
$SMARTY->assign("prefixlist",$prefixlist);
$SMARTY->assign("error",$error);
$SMARTY->display("netinfo.html");
/*
 * $Log$
 * Revision 1.20  2003/08/18 16:52:19  lukasz
 * - added CVS Log tags
 *
 */
?>