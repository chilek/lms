<?

/*
 * LMS version 1.0-cvs
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
		$error[name] = "Musisz podaæ nazwê sieci!";
	elseif(!eregi("^[.a-z0-9-]+$",$netadd[name]))
		$error[name] = "Podana nazwa zawiera niepoprawne znaki!";
	
	if($netadd[domain] != "" && !eregi("^[.a-z0-9-]+$",$netadd[domain]))
		$error[domain] = "Podana nazwa zawiera niepoprawne znaki!";
	
	if(!check_ip($netadd[address]))
		$error[address] = "Podany adres IP jest nieprawid³owy!";
	else
	{
		if(getnetaddr($netadd[address],prefix2mask($netadd[prefix]))!=$netadd[address])
		{
			$error[address] = "Podany adres nie jest pocz±tkowym adresem sieci, ustawiam na ".getnetaddr($netadd[address],prefix2mask($netadd[prefix]));
			$netadd[address] = getnetaddr($netadd[address],prefix2mask($netadd[prefix]));
		}
		else
		{
			if($LMS->NetworkOverlaps($netadd[address],prefix2mask($netadd[prefix])))
				$error[address] = "Podana sieæ pokrywa siê z inn± sieci±!";
		}
	}
	
	if($netadd[dns]!="" && !check_ip($netadd[dns]))
		$error[dns] = "Podany adres IP jest nie prawid³owy!";
	
	if($netadd[wins]!="" && !check_ip($netadd[wins]))
		$error[wins] = "Podany adres IP jest nie prawid³owy!";
	
	if($netadd[gateway]!="")
		if(!check_ip($netadd[gateway]))
			$error[gateway] = "Podany adres IP jest nie prawid³owy!";
	elseif(!isipin($netadd[gateway],getnetaddr($netadd[address],prefix2mask($netadd[prefix])),prefix2mask($netadd[prefix])))
		$error[gateway] = "Podany adres gateway'a nie pasuje do adresu sieci!";
	
	if($netadd[dhcpstart]!="")
		if(!check_ip($netadd[dhcpstart]))
			$error[dhcpstart] = "Podany adres IP jest nieprawid³owy!";
	elseif(!isipin($netadd[dhcpstart],getnetaddr($netadd[address],prefix2mask($netadd[prefix])),prefix2mask($netadd[prefix])) && $netadd[address]!="")
		$error[dhcpstart] = "Podany adres IP nie nale¿y do tej sieci!";
	
	if($netadd[dhcpend]!="")
		if(!check_ip($netadd[dhcpend]))
			$error[dhcpend] = "Podany adres IP jest nieprawid³owy!";
	elseif(!isipin($netadd[dhcpend],getnetaddr($netadd[address],prefix2mask($netadd[prefix])),prefix2mask($netadd[prefix])) && $netadd[address]!="")
		$error[dhcpend] = "Podany adres IP nie nale¿y do tej sieci!";
	
	if(!$error[dhcpstart] && !$error[dhcpend])
	{
		if(($netadd[dhcpstart]!="" && $netadd[dhcpend]=="")||($netadd[dhcpstart]=="" && $netadd[dhcpend]!=""))
			$error[dhcp] = "Musisz podaæ obydwa zakresy IP dla DHCP!";
		if($netadd[dhcpstart]!="" && $netadd[dhcpend]!="" && !(ip_long($netadd[dhcpend]) > ip_long($netadd[dhcpstart])))
			$error[dhcp] = "Koniec zakresu DHCP musi byæ wiêkszy ni¿ start!";
	}
	
	if(!$error)
	{
		header("Location: ?m=netinfo&id=".$LMS->NetworkAdd($netadd));
		exit(0);
	}

}

$layout[pagetitle]="Dodaj sieæ";

$prefixlist = $LMS->GetPrefixList();
$netlist = $LMS->GetNetworkList();
$SMARTY->assign("layout",$layout);
$SMARTY->assign("error",$error);
$SMARTY->assign("netadd",$netadd);
$SMARTY->assign("prefixlist",$prefixlist);
$SMARTY->assign("netlist",$netlist);
$SMARTY->display("netadd.html");

?>
