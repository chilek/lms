<?

/*
 * LMS version 1.0
 *
 *  (C) Copyright 2002 Rulez.PL Development Team
 *  (C) Copyright 2001-2002 NetX ACN
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

// LMS Class - contains internal LMS database functions used
// to fetch data like usernames, searching for mac's by ID, etc..

class LMS {

	var $db;
	var $session;
	var $_BACKUP_DIR;
	var $_version = '1.0.61';

	function LMS($db,$session)
	{
		$this->db=$db;
		$this->session=$session;
	}

	function SetTS($table)
	{
		$db=$this->db;
		if($db->CountRows("SELECT * FROM `timestamps` WHERE `table` = '_'"))
			$db->ExecSQL("UPDATE `timestamps` SET `time` = UNIX_TIMESTAMP() WHERE `table` = '_'");
		else
			$db->ExecSQL("INSERT INTO `timestamps` VALUES (UNIX_TIMESTAMP(), '_')");
		if($db->CountRows("SELECT * FROM `timestamps` WHERE `table` = '".$table."'"))
			$db->ExecSQL("UPDATE `timestamps` SET `time` = UNIX_TIMESTAMP() WHERE `table` = '".$table."'");
		else
			$db->ExecSQL("INSERT INTO `timestamps` VALUES (UNIX_TIMESTAMP(), '".$table."')");
		return $this->GetTS($table);
	}

	function GetTS($table)
	{
		$db=$this->db;
		$db->FetchRow("SELECT `time` FROM `timestamps` WHERE `table` = '".$table."'");
		if(!isset($db->row[time]))
			return -1;
		else
			return $db->row[time];
	}

	function SetAdminPassword($id,$passwd)
	{
		$db=$this->db;
		$this->SetTS("admins");
		return $db->ExecSQL("UPDATE `admins` SET `passwd` = '".crypt($passwd)."' WHERE `id` = '".$id."' LIMIT 1");
	}

	function DeleteUser($id)
	{
		$db=$this->db;
		$this->SetTS("users");
		$this->SetTS("nodes");
		$db->ExecSQL("DELETE FROM `nodes` WHERE `ownerid` = '".$id."'");
		return $db->ExecSQL("DELETE FROM `users` WHERE `id` = '".$id."' LIMIT 1");
	}

	function DeleteNode($id)
	{
		$db=$this->db;
		$this->SetTS("nodes");
		return $db->ExecSQL("DELETE FROM `nodes` WHERE `id` = '".$id."'");
	}

	function GetAdminName($id)
	{
		$db=$this->db;
		$db->FetchRow("SELECT `name` FROM `admins` WHERE `id` = '".$id."' LIMIT 1") or $db->row[name] = "-";
		return $db->row[name];
	}

	function AdminExists($id)
	{
		$db=$this->db;
		return $db->FetchRow("SELECT * FROM `admins` WHERE `id` = '".$id."' LIMIT 1");
	}

	function GetNetworkName($id)
	{	
		$db=$this->db;
		$db->FetchRow("SELECT `name` FROM `networks` WHERE `id` = '".$id."' LIMIT 1");
		return $db->row[name];
	}

	function GetTariffValue($id)
	{
		$db=$this->db;
		$db->FetchRow("SELECT `value` FROM `tariffs` WHERE `id` = '".$id."' LIMIT 1");
		return $db->row[value];
	}

	function GetTariffName($id)
	{
		$db=$this->db;
		$db->FetchRow("SELECT `name` FROM `tariffs` WHERE `id` = '".$id."' LIMIT 1");
		return $db->row[name];
	}

	function UserUpdate($userdata)
	{
		$session=$this->session;
		$db=$this->db;
		$this->SetTS("users");
		return $db->ExecSQL("UPDATE `users` SET 
		`phone1` = '".$userdata[phone1]."',
		`phone2` = '".$userdata[phone2]."',
		`phone3` = '".$userdata[phone3]."',
		`address` = '".$userdata[address]."',
		`email` = '".$userdata[email]."',
		`tariff` = '".$userdata[tariff]."',
		`info` = '".trim($userdata[uwagi])."',
		`modid` = '".$session->id."',
		`status` = '".$userdata[status]."',
		`moddate` = UNIX_TIMESTAMP()
		WHERE `id` = '".$userdata[id]."' LIMIT 1");
	}

	function GetUserNodesNo($id)
	{
		$db=$this->db;
		return $db->CountRows("SELECT * FROM `nodes` WHERE `ownerid` = '".$id."'");		
	}

	function GetNetworks()
	{
		$db=$this->db;
		if($_SESSION[timestamps][getnetworks][networks] != $this->GetTS("networks"))
		{
			$return = $db->FetchArray("SELECT `id`, `name`, `address`, `mask` FROM `networks` ORDER BY `address` ASC");
			$return[total] = sizeof($return[id]);
			if($return[total])
				foreach($return[id] as $i => $v)
				{
					$return[addresslong][$i] = ip_long($return[address][$i]);
					$return[prefix][$i] = mask2prefix($return[mask][$i]);
				}
			$_SESSION[timestamps][getnetworks][networks] = $this->GetTS("networks");
			$_SESSION[cache][getnetworks] = $return;
		}else{
			$return = $_SESSION[cache][getnetworks];
		}
		
		return $return;
	}

	function GetNetworkList()
	{
		$db=$this->db;

		if(
			$_SESSION[timestamps][getnetworklist][networks] != $this->GetTS("networks")
			||
			$_SESSION[timestamps][getnetworklist][nodes] != $this->GetTS("nodes")
		)
		{
			$networks = $db->FetchArray("SELECT `id`, `name`, `address`, `mask`, `gateway`, `dns`, `domain`, `wins`, `dhcpstart`, `dhcpend` FROM `networks`");
			$nodes = $db->FetchArray("SELECT `ipaddr` FROM `nodes`");
			$networks[total] = sizeof($networks[id]);
			if($networks[total])
			{
				array_multisort($networks[name],$networks[id],$networks[address],$networks[mask],$networks[gateway],$networks[wins],$networks[domain],$networks[dns],$networks[dhcpstart],$networks[dhcpend]);
				foreach($networks[id] as $key => $value)
				{
					$networks[addresslong][$key] = ip_long($networks[address][$key]);
					$networks[prefix][$key] = mask2prefix($networks[mask][$key]);
					$networks[broadcast][$key] = getbraddr($networks[address][$key],$networks[mask][$key]);
					$networks[boradcastlong][$key] = ip_long($networks[broadcast][$key]);
					$networks[size][$key] = pow(2,(32-$networks[prefix][$key]));
					$networks[size][total] = $networks[size][total] + $networks[size][$key];
					if(sizeof($nodes[ipaddr]))
						foreach($nodes[ipaddr] as $ip)
							if(isipin($ip,$networks[address][$key],$networks[mask][$key]))
								$networks[assigned][$key] ++;
					$networks[assigned][total] = $networks[assigned][total] + $networks[assigned][$key];
				}
			}

			$_SESSION[timestamps][getnetworklist][networks] = $this->GetTS("networks");
			$_SESSION[timestamps][getnetworklist][nodes] = $this->GetTS("nodes");
			$_SESSION[cache][getnetworklist] = $networks;

		}
		else
		{
			$networks = $_SESSION[cache][getnetworklist];
		}
		
		return $networks;
	}

	function IsIPValid($ip,$checkbroadcast=FALSE,$ignoreid=0)
	{
		$networks = $this->GetNetworks();
		foreach($networks[id] as $i => $v)
		{
			if($v != $ignoreid)
				if($checkbroadcast)
				{
					if((ip_long($ip) > $networks[addresslong][$i] - 1)&&(ip_long($ip) < ip_long(getbraddr($networks[address][$i],$networks[mask][$i])) + 1))
					{
						return TRUE;
					}
				}
				else
				{
					if((ip_long($ip) > $networks[addresslong][$i])&&(ip_long($ip) < ip_long(getbraddr($networks[address][$i],$networks[mask][$i]))))
					{
						return TRUE;
					}
				}
		}
		return FALSE;
	}

	function NetworkOverlaps($network,$mask,$ignorenet=0)
	{
		$networks = $this->GetNetworks();
		$cnetaddr = ip_long($network);
		$cbroadcast = ip_long(getbraddr($network,$mask));
		
		if($networks[total])
			foreach($networks[id] as $i => $v)
			{
				$broadcast = ip_long(getbraddr($networks[address][$i],$networks[mask][$i]));
				$netaddr = $networks[addresslong][$i];					
				if($v != $ignorenet)
				{
					if(
							($cbroadcast == $broadcast)
							||
							($cnetaddr == $netaddr)
							||
							(
							 ($cnetaddr < $netaddr)
							 &&
							 ($cbroadcast > $broadcast)
							 )
							||
							(
							 ($cnetaddr > $netaddr)
							 &&
							 ($cbroadcast < $broadcast)
							 )
							)
						return TRUE;
					
				}
			}
		return FALSE;
	}
	
	function GetMACs()
	{
		if(is_readable("/proc/net/arp"))
			$file=fopen("/proc/net/arp","r");
		else
			return FALSE;
		while(!feof($file))
		{
			$line=fgets($file,4096);
			$mac=trim(substr($line,35,25));
			$ip=trim(substr($line,0,15));
			if(check_mac($mac))
			{
				$return[mac][] = $mac;
				$return[ip][] = $ip;
				$return[longip][] = ip_long($ip);
				$return[nodename][] = $this->GetNodeNameByIP($ip);
			}
		}
		array_multisort($return[longip],$return[mac],$return[ip],$return[nodename]);
		return $return;
	}

	function GetNodeIDByIP($ipaddr)
	{
		$db=$this->db;
		$db->FetchRow("SELECT `id` FROM `nodes` WHERE `ipaddr` = '".$ipaddr."' LIMIT 1");
		return $db->row[id];
	}

	function GetNodeIDByMAC($mac)	
	{
		$db=$this->db;
		$db->FetchRow("SELECT `id` FROM `nodes` WHERE `mac` = '".$mac."' LIMIT 1");
		return $db->row[id];
	}

	function GetNodeIDByName($name)
	{
		$db=$this->db;
		$db->FetchRow("SELECT `id` FROM `nodes` WHERE `name` = '".$name."' LIMIT 1");
		return $db->row[id];
	}

	function GetNodeIPByID($id)
	{
		$db=$this->db;
		$db->FetchRow("SELECT `ipaddr` FROM `nodes` WHERE `id` = '".$id."' LIMIT 1");
		return $db->row[ipaddr];
	}

	function GetNodeMACByID($id)
	{
		$db=$this->db;
		$db->FetchRow("SELECT `mac` FROM `nodes` WHERE `id` = '".$id."' LIMIT 1");
		return $db->row[mac];
	}

	function GetNodeName($id)
	{
		$db=$this->db;
		$db->FetchRow("SELECT `name` FROM `nodes` WHERE `id` = '".$id."' LIMIT 1");
		return $db->row[name];
	}

	function GetNodeNameByIP($ipaddr)
	{
		$db=$this->db;
		$db->FetchRow("SELECT `name` FROM `nodes` WHERE `ipaddr` = '".$ipaddr."' LIMIT 1");
		return $db->row[name];
	}

	function GetUserStatus($id)
	{
		$db=$this->db;
		$db->FetchRow("SELECT `status` FROM `users` WHERE `id` = '".$id."' LIMIT 1");
		return $db->row[status];
	}

	function NetworkShift($network="0.0.0.0",$mask="0.0.0.0",$shift=0)
	{
		$db=$this->db;
		$this->SetTS("nodes");
		$this->SetTS("networks");
		$nodes = $db->FetchArray("SELECT `ipaddr`, `id` FROM `nodes`");
		if(sizeof($nodes[ipaddr]))
			foreach($nodes[ipaddr] as $key => $value)
				if(isipin($value,$network,$mask))
					$db->ExecSQL("UPDATE `nodes` SET `ipaddr` = '".long2ip(ip_long($value) + $shift)."' WHERE `id` = '".$nodes[id][$key]."' LIMIT 1");
	}

	function NetworkUpdate($networkdata)
	{
		$db=$this->db;
		$this->SetTS("networks");
		return $db->ExecSQL("UPDATE `networks` SET `name` = '".strtoupper($networkdata[name])."', `address` = '".$networkdata[address]."', `mask` = '".$networkdata[mask]."', `gateway` = '".$networkdata[gateway]."', `dns` = '".$networkdata[dns]."', `domain` = '".$networkdata[domain]."', `wins` = '".$networkdata[wins]."', `dhcpstart` = '".$networkdata[dhcpstart]."', `dhcpend` = '".$networkdata[dhcpend]."' WHERE `id` = '".$networkdata[id]."' LIMIT 1");
	}
				
	
	function NetworkCompress($id,$shift=0)
	{
		$db=$this->db;
		$this->SetTS("nodes");
		$this->SetTS("networks");
		$network=$this->GetNetworkRecord($id);
		$address = $network[addresslong]+$shift;
		foreach($network[nodes][id] as $key => $value)
		{
			if($value)
			{
				$address ++;
				$db->ExecSQL("UPDATE `nodes` SET `ipaddr` = '".long2ip($address)."' WHERE `id` = '".$value."' LIMIT 1");
			}				
		}
	}

	function NetworkRemap($src,$dst)
	{
		$db=$this->db;
		$this->SetTS("nodes");
		$this->SetTS("networks");
		$network[source] = $this->GetNetworkRecord($src);
		$network[dest] = $this->GetNetworkRecord($dst);
		foreach($network[source][nodes][id] as $key => $value)
			if($this->NodeExists($value))
				$nodelist[] = $value;
		$counter = 0;
		if(sizeof($nodelist))
			foreach($nodelist as $value)
			{
				while($this->NodeExists($network[dest][nodes][id][$counter]))
					$counter++;
				$db->ExecSQL("UPDATE `nodes` SET `ipaddr` = '".$network[dest][nodes][address][$counter]."' WHERE `id` = '".$value."' LIMIT 1");
				$counter++;
			}
		return $counter;
	}

	function GetNetworkRecord($id)
	{
		$db=$this->db;
		$network = $db->FetchRow("SELECT `id`, `name`, `address`, `mask`, `gateway`, `dns`, `domain`, `wins`, `dhcpstart`, `dhcpend` FROM `networks` WHERE `id` = '".$id."'");
		$network[prefix] = mask2prefix($network[mask]);
		$network[addresslong] = ip_long($network[address]);
		$network[size] = pow(2,32-$network[prefix]);
		$network[rows] = ceil($network[size]/4);
		$network[assigned] = 0;	
		// wype³nijmy tabelê pustymi danymi - ¿eby nie by³o burdelu
		// i ¿eby rekordy by³y ³adnie pouk³adane :)
		// BTW - W LI¦CIE NIE PRZEKAZUJEMY ADRESU SIECI I BROADCASTA!!!
		
		for($i=0;$i<$network[size]-2;$i++)
		{
			$network[nodes][address][$i] = long2ip(ip_long($network[address])+$i+1);
			$network[nodes][id][$i] = "";
			$network[nodes][ownerid][$i] = "";
			$network[nodes][name][$i] = "";
		}
		$networknodes = $db->FetchArray("SELECT `id`, `name`, `ipaddr`, `ownerid` FROM `nodes`");
		if(sizeof($networknodes[id]))
			foreach($networknodes[id] as $key => $value)
			{
				$networknodes[addresslong][$key] = ip_long($networknodes[ipaddr][$key]);
				if(isipin($networknodes[ipaddr][$key],$network[address],$network[mask]))
				{
					$pos = $networknodes[addresslong][$key] - ip_long($network[address]) -1;
					$network[nodes][address][$pos] = $networknodes[ipaddr][$key];
					$network[nodes][id][$pos] = $networknodes[id][$key];
					$network[nodes][ownerid][$pos] = $networknodes[ownerid][$key];
					$network[nodes][name][$pos] = $networknodes[name][$key];
					$network[assigned] ++;
				}
			}
		$network[free] = $network[size] - $network[assigned] - 2;
		return $network;
	}

	function GetNetwork($id)
	{
		$db=$this->db;

		if(
				$_SESSION[timestamps][getnetwork][$id][networks] != $this->GetTS("networks")
				||
				$_SESSION[timestamps][getnetwork][$id][nodes] != $this->GetTS("nodes")
				|| TRUE
		  )
		{
			$db->row = "";
			$db->FetchRow("SELECT `address`, `mask`, `name` FROM `networks` WHERE `id` = '".$id."' LIMIT 1");
			if($db->row != "")
				foreach($db->row as $key => $value)
					$$key = $value;
				
			for($i=ip_long($address)+1;$i<ip_long(getbraddr($address,$mask));$i++)
			{
				$return[addresslong][] = $i;
				$return[address][] = long2ip($i);
				$return[nodeid][] = 0;
				$return[nodename][] = "";
				$return[ownerid][] = 0;
			}
			
			if(sizeof($return[address]))
			{
				$nodes = $db->FetchArray("SELECT `name`, `id`, `ownerid`, `ipaddr` FROM `nodes`");
				if(sizeof($nodes[id]))
					foreach($nodes[id] as $key => $value)
						if(isipin($nodes[ipaddr][$key],$address,$mask))
						{
							$pos = ip_long($nodes[ipaddr][$key])-ip_long($address)-1;
							$return[nodeid][$pos] = $value;
							$return[nodename][$pos] = $nodes[name][$key];
							$return[ownerid][$pos] = $nodes[ownerid][$key];
						}
			}

			$_SESSION[cache][getnetwork][$id] = $return;
			$_SESSION[timestamps][getnetwork][$id][nodes] = $this->GetTS("nodes");
			$_SESSION[timestamps][getnetwork][$id][networks] = $this->GetTS("networks");

		}else{
		
			$return = $_SESSION[cache][getnetwork][$id];

		}
			
		return $return;
	}
			

	function GetUser($id)
	{
		$db=$this->db;
		$return = $db->FetchRow("SELECT `id`, `lastname`, `name`, `status`, `email`, `phone1`, `phone2`, `phone3`, `address`, `tariff`, `info`, `creationdate`, `moddate`, `creatorid`, `modid` FROM `users` WHERE `id` = '".$id."' LIMIT 1");
		$return[username] = strtoupper($return[lastname])." ".$return[name];
		$return[createdby] = $this->GetAdminName($return[creatorid]);
		$return[modifiedby] = $this->GetAdminName($return[modid]);
		$return[creationdateh] = date("Y-m-d, H:i",$return[creationdate]);
		$return[moddateh] = date("Y-m-d, H:i",$return[moddate]);
		$return[tariffvalue] = $this->GetTariffValue($return[tariff]);
		$return[tariffname] = $this->GetTariffName($return[tariff]);
		$return[balance] = $this->GetUserBalance($return[id]);
		return $return;
	}

	function GetUserNames()
	{
	
		$db=$this->db;

		if($_SESSION[timestamps][getusernames] != $this->GetTS("users"))
		{
			$usernames = $db->FetchArray("SELECT `id`, `name`, `lastname` FROM `users` WHERE `status` = '3'");
			
			if(sizeof($usernames[id]))
			{
				foreach ($usernames[id] as $key => $value)
					$usernames[username][$key] = strtoupper($usernames[lastname][$key])." ".ucwords($usernames[name][$key]);		
				array_multisort($usernames[username],4,$usernames[id],$usernames[name],$usernames[lastname]);
			}
			$_SESSION[timestamps][getusernames] = $this->GetTS("users");
			$_SESSION[cache][getusernames] = $usernames;
			
		}else{

			$usernames = $_SESSION[cache][getusernames];

		}
		
		return $usernames;

	}

	function GetUserNodesAC($id)
	{
		$db=$this->db;
		$acl = $db->FetchArray("SELECT `access` FROM `nodes` WHERE `ownerid` = '".$id."'");
		if(sizeof($acl))
			foreach($acl[access] as $value)
				if(strtoupper($value) == "Y")
					$y ++;
				else
					$n ++;
		if($y && !$n) return TRUE;
		if($n && !$y) return FALSE;
		return 2;
	}
		
	function GetBalanceList()
	{
		$db=$this->db;

		if ($_SESSION[timestamps][getbalancelist] != $this->GetTS("cash"))
		{
			$balancelist = $db->FetchArray("SELECT `id`, `time`, `adminid`, `type`, `value`, `userid`, `comment` FROM `cash` ORDER BY `time` ASC");
			$balancelist[total] = sizeof($balancelist[id]);
			
			if($balancelist[total])
				foreach($balancelist[id] as $key => $value)
				{
					$balancelist[admin][$key] = $this->GetAdminName($balancelist[adminid][$key]);
					$balancelist[username][$key] = $this->GetUserName($balancelist[userid][$key]);
					$balancelist[value][$key]=str_replace(".",",",
						round( str_replace (".",",",$balancelist[value][$key]) , 4 )
					);
					if($key)
						$balancelist[before][$key] = $balancelist[after][$key-1];
					else
						$balancelist[before][$key] = 0;
					
					switch($balancelist[type][$key])
					{

						// Hm. Wykonywanie round(arg,4) daje o wiele lepszy rezultat ni¿
						// rzutowanie typów.
						
						case "1":
							$balancelist[type][$key] = "przychód";
							$balancelist[after][$key] = str_replace(".",",",round($balancelist[before][$key] + $balancelist[value][$key],4));
							$balancelist[income] = str_replace(".",",",round($balancelist[income] + $balancelist[value][$key],4));
						break;
						case "2":
							$balancelist[type][$key] = "rozchód";
							$balancelist[after][$key] = str_replace(".",",",round($balancelist[before][$key] - $balancelist[value][$key],4));
							$balancelist[expense] = str_replace(".",",",round($balancelist[expense] + $balancelist[value][$key],4));
						break;
						case "3":
							$balancelist[type][$key] = "wp³ata u¿";
							$balancelist[after][$key] = str_replace(".",",",round($balancelist[before][$key] + $balancelist[value][$key],4));
							$balancelist[incomeu] = str_replace(".",",",round($balancelist[incomeu] + $balancelist[value][$key],4));
						break;
						case "4":
							$balancelist[type][$key] = "obci±¿enie u¿";
							$balancelist[after][$key] = str_replace(".",",",round($balancelist[before][$key],4));
							$balancelist[uinvoice] = str_replace(".",",",round($balancelist[uinvoice] + $balancelist[value][$key],4));
						break;
						default:
							$balancelist[type][$key] = '<FONT COLOR="RED">???</FONT>';
							$balancelist[after][$key] = str_replace(".",",",round($balancelist[before][$key],4));
						break;
					}
					
				}
				
				$balancelist[total] = str_replace(".",",",$balancelist[after][$key]);

				$_SESSION[timestamps][getbalancelist] = $this->GetTS("cash");
				$_SESSION[cache][getbalancelist] = $balancelist;
				
			}else{
				$balancelist = $_SESSION[cache][getbalancelist];
				
			}
		return $balancelist;
	}

	function GetUserList($order=NULL,$state=NULL)
	{

		$db=$this->db;

		if(!isset($state)) $state="3";
		if(!isset($order)) $order="username,asc";
			
		$userlist = $db->FetchArray("SELECT id, lastname, name, status, email, phone1, address, info FROM users");
			
		if(sizeof($userlist[id]))
			foreach($userlist[id] as $i => $v)
				$userlist[username][$i] = strtoupper($userlist[lastname][$i])." ".$userlist[name][$i];

		if(sizeof($userlist[id]))
			foreach($userlist[id] as $i => $v)
			{
				$userlist[balance][$i] = $this->GetUserBalance($v);
				if($userlist[balance][$i] > 0)
					$userlist[over] = $userlist[over] + $userlist[balance][$i];
				if($userlist[balance][$i] < 0)
					$userlist[below] = $userlist[below] - $userlist[balance][$i];
			}
		
		list($order,$direction)=explode(",",$order);
		
		if($direction != "desc")
			$direction = SORT_ASC;
		else
			$direction = SORT_DESC;
		
		if(sizeof($userlist[id]))
			switch($order){
				case "username":
					array_multisort($userlist[username],$direction,$userlist[id],$userlist[status],$userlist[email],$userlist[phone1],$userlist[address],$userlist[info],$userlist[balance]);
				break;
				case "id":
					array_multisort($userlist[id],$direction,$userlist[username],$userlist[status],$userlist[email],$userlist[phone1],$userlist[address],$userlist[info],$userlist[balance]);
				break;
				case "email":
					array_multisort($userlist[email],$direction,$userlist[username],$userlist[id],$userlist[status],$userlist[phone1],$userlist[address],$userlist[info],$userlist[balance]);
				break;
				case "address":
					array_multisort($userlist[address],$direction,$userlist[id],$userlist[username],$userlist[status],$userlist[email],$userlist[phone1],$userlist[info],$userlist[balance]);
				break;
				case "balance":
					array_multisort($userlist[balance],$direction,SORT_NUMERIC,$userlist[address],$userlist[id],$userlist[username],$userlist[status],$userlist[email],$userlist[phone1],$userlist[info]);
				break;
				case "phone":
					array_multisort($userlist[phone1],$direction,$userlist[username],$userlist[id],$userlist[status],$userlist[email],$userlist[address],$userlist[info],$userlist[balance]);
				break;
			}
		
		if(sizeof($userlist[id]))
			foreach($userlist[id] as $i => $v)
				if($userlist[status][$i] == 3)
					$userlist[nodeac][$i] = $this->GetUserNodesAC($userlist[id][$i]);
				else
					$userlist[nodeac][$i] = FALSE;
		$userlist[state]=$state;
		$userlist[order]=$order;
		$userlist[direction]=$direction;
		$userlist[total]=sizeof($userlist[id]);
//		echo "<pre>";	
//		print_r($userlist);
		return $userlist;
	}
			
	function GetUserNodes($id)
	{
		$db=$this->db;
		$db->ExecSQL("SELECT `id`, `name`, `mac`, `ipaddr`, `ownerid`, `access` FROM `nodes` WHERE `ownerid` = '".$id."' ORDER BY `name` ASC");
		while($db->FetchRow()){
			foreach($db->row as $key => $value)
				$return[$key][] = $value;
			$return[iplong][] = ip_long($db->row[ipaddr]);
		}
		$return[total] = sizeof($return[id]);
		return $return;
	}

	function GetNodeList($order=NULL)
	{
		$db=$this->db;

		if(
				$_SESSION[timestamps][getnodelist][nodes] != $this->GetTS("nodes")
				||
				$_SESSION[timestamps][getnodelist][users] != $this->GetTS("users")
		  )
		{
			$nodelist[totalon]=0;
			$nodelist[totaloff]=0;
			$nodelist = $db->FetchArray("SELECT `id`, `ipaddr`, `mac`, `name`, `ownerid`, `access` FROM `nodes`");
			if(sizeof($nodelist[id]))
				foreach($nodelist[id] as $key => $value)
				{
					$nodelist[iplong][$key] = ip_long($nodelist[ipaddr][$key]);
					$nodelist[owner][$key] = $this->GetUserName($nodelist[ownerid][$key]);
					if(strtoupper($nodelist[access][$key])=="Y")
						$nodelist[totalon]++;
					else
						$nodelist[totaloff]++;						
				}

			$_SESSION[timestamps][getnodelist][nodes] = $this->GetTS("nodes");
			$_SESSION[timestamps][getnodelist][users] = $this->GetTS("users");
			$_SESSION[cache][getnodelist] = $nodelist;
			
		}else{
			$nodelist = $_SESSION[cache][getnodelist];
		}

		if(!isset($order)) $order="name,asc";

		list($order,$direction)=explode(",",$order);

		if($direction != "desc") 
			$direction = 4;
		else 
			$direction = 3;

		if(sizeof($nodelist[id])) switch($order){

			case "name":
				array_multisort($nodelist[name],$direction,$nodelist[id],$nodelist[ipaddr],$nodelist[mac],$nodelist[name],$nodelist[ownerid],$nodelist[access],$nodelist[iplong],$nodelist[owner]);
				break;

			case "mac":
				array_multisort($nodelist[mac],$direction,$nodelist[id],$nodelist[ipaddr],$nodelist[name],$nodelist[name],$nodelist[ownerid],$nodelist[access],$nodelist[iplong],$nodelist[owner]);
				break;
			
			case "ip":

				array_multisort($nodelist[iplong],$direction,$nodelist[id],$nodelist[ipaddr],$nodelist[name],$nodelist[name],$nodelist[ownerid],$nodelist[access],$nodelist[mac],$nodelist[owner]);
				break;

			case "id":

				array_multisort($nodelist[id],$direction,$nodelist[iplong],$nodelist[ipaddr],$nodelist[name],$nodelist[name],$nodelist[ownerid],$nodelist[access],$nodelist[mac],$nodelist[owner]);
				break;

			case "owner":

				array_multisort($nodelist[owner],$direction,$nodelist[iplong],$nodelist[ipaddr],$nodelist[name],$nodelist[name],$nodelist[ownerid],$nodelist[access],$nodelist[mac],$nodelist[id]);
				break;

		}
                
                $nodelist[order]=$order;
                $nodelist[direction]=$direction;
                $nodelist[total]=sizeof($nodelist[id]);
		
		return $nodelist;
	}

	function DatabaseList()
	{
		$_BACKUP_DIR = $this->_BACKUP_DIR;
		if ($handle = opendir($_BACKUP_DIR))
		{
			while (false !== ($file = readdir($handle)))
			{
				if ($file != "." && $file != "..")
				{
					$path = pathinfo($file);
					if($path[extension] = "sql")
					{
						if(substr($path[basename],0,4)=="lms-")
						{
							$dblist[time][] = substr(basename($file,".sql"),4);
							$dblist[size][] = filesize($_BACKUP_DIR."/".$file);
						}
					}
				}
			}
			closedir($handle);
		}
		if(sizeof($dblist[time]))
			array_multisort($dblist[time],$dblist[size]);
		$dblist[total] = sizeof($dblist[time]);
		return $dblist;
	}		

	function DatabaseRecover($dbtime)
	{
		$_BACKUP_DIR = $this->_BACKUP_DIR;
		$db=$this->db;
		if(file_exists($_BACKUP_DIR.'/lms-'.$dbtime.'.sql'))
		{
			return $db->Source($_BACKUP_DIR.'/lms-'.$dbtime.'.sql');
		}
		else
			return FALSE;
	}

	function DatabaseCreate()
	{
		$db=$this->db;
		$_BACKUP_DIR = $this->_BACKUP_DIR;
		return $db->Dump($_BACKUP_DIR.'/lms-'.time().'.sql');
	}

	function DatabaseDelete($dbtime)
	{
		$_BACKUP_DIR = $this->_BACKUP_DIR;
		if(file_exists($_BACKUP_DIR.'/lms-'.$dbtime.'.sql'))
		{
			return unlink($_BACKUP_DIR.'/lms-'.$dbtime.'.sql');
		}
		else
			return FALSE;
	}

	function DatabaseFetchContent($dbtime)
	{
		$_BACKUP_DIR = $this->_BACKUP_DIR;	
		if(file_exists($_BACKUP_DIR.'/lms-'.$dbtime.'.sql'))
		{
			$content = file($_BACKUP_DIR.'/lms-'.$dbtime.'.sql');
			foreach($content as $value)
				$database[content] .= $value;
			$database[size] = filesize($_BACKUP_DIR.'/lms-'.$dbtime.'.sql');
			$database[time] = $dbtime;
			return $database;
		}
		else
			return FALSE;
	}
		
	function NodeSet($id)
	{
		$db=$this->db;
		$this->SetTS("nodes");
		$db->FetchRow("SELECT `access` FROM `nodes` WHERE `id` = '".$id."' LIMIT 1");
		if($db->row[access]=="Y")
			return $db->ExecSQL("UPDATE `nodes` SET `access` = 'N' WHERE `id` = '".$id."' LIMIT 1");
		else
			return $db->ExecSQL("UPDATE `nodes` SET `access` = 'Y' WHERE `id` = '".$id."' LIMIT 1");
	}

	function NodeSetU($id,$access=FALSE)
	{
		$db=$this->db;
		$this->SetTS("nodes");
		if($access)
			return $db->ExecSQL("UPDATE `nodes` SET `access` = 'Y' WHERE `ownerid` = '".$id."'");
		else
			return $db->ExecSQL("UPDATE `nodes` SET `access` = 'N' WHERE `ownerid` = '".$id."'");
	}

	function GetOwner($id)
	{
		$db=$this->db;
		$db->FetchRow("SELECT `ownerid` FROM `nodes` WHERE `id` = '".$id."' LIMIT 1");
		return $db->row[ownerid];
	}

	function GetUserBalance($id)
	{
		$return = str_replace(".",",",$this->GetUserBalanceList($id));
		return $return[balance];
	}

	function GetUserBalanceList($id)
	{
		$db=$this->db;

		if($_SESSION[timestamps][getuserbalancelist][$id][cash] != $this->GetTS("cash"))
		{
			$saldolist = $db->FetchArray("SELECT `id`, `time`, `adminid`, `type`, `value`, `userid`, `comment` FROM cash WHERE userid = '".$id."'");
			if(sizeof($saldolist[id]) > 0){
				foreach($saldolist[id] as $i => $v)
				{
					if($i>0) $saldolist[before][$i] = $saldolist[after][$i-1];
					else $saldolist[before][$i] = 0;
					
					// zachcia³o mi siê kurwa pierdolonych locales :S
					// zak³adam siê ¿e w trakcie pisania wyjdzie jeszcze kwiatek
					// z zapisem do mysql'a :S
					$saldolist[value][$i]=str_replace(".",",",$saldolist[value][$i]);
					$saldolist[value][$i]=round($saldolist[value][$i],2);	
					switch ($saldolist[type][$i]){
						case "3":
							$saldolist[after][$i] = round(($saldolist[before][$i] + $saldolist[value][$i]),4);
							$saldolist[name][$i] = "wp³ata";
						break;
						
						case "4":
							$saldolist[after][$i] = round(($saldolist[before][$i] - $saldolist[value][$i]),4);
							$saldolist[name][$i] = "op³ata ab";
						break;
					}
					
					$saldolist[adminname][$i]=$this->GetAdminName($saldolist[adminid][$i]);
					$saldolist[date][$i]=date("Y/m/d H:i",$saldolist[time][$i]);
					
				}
				
				$saldolist[balance] = $saldolist[after][sizeof($saldolist[id])-1];
				$saldolist[total] = sizeof($saldolist[id]);
			
			}else{
				$saldolist[balance] = 0;
			}

			if($saldolist[total])
			{
				foreach($saldolist[value] as $key => $value)
					$saldolist[value][$key] = str_replace(".",",",$value);
				foreach($saldolist[after] as $key => $value)
					$saldolist[after][$key] = str_replace(".",",",$value);
				foreach($saldolist[before] as $key => $value)
					$saldolist[before][$key] = str_replace(".",",",$value);
			}
			$saldolist[balance] = str_replace(".",",",$saldolist[balance]);
			$_SESSION[timestamps][getuserbalancelist][$id][cash] = $this->GetTS("cash");
			$_SESSION[cache][getuserbalancelist][$id] = $saldolist;
		}else{
			$saldolist = $_SESSION[cache][getuserbalancelist][$id];
		}
			
		return $saldolist;

	}

	function GetTariffs()
	{
		$db=$this->db;
		return $db->FetchArray("SELECT id, name, value FROM tariffs ORDER BY value DESC  ");
	}

	function GetUserName($id)
	{
		$db=$this->db;
		$db->FetchRow("SELECT `lastname`, `name` FROM `users` WHERE `id` = '".$id."' LIMIT 1");
		return strtoupper($db->row[lastname])." ".$db->row[name];
	}

	function NodeAdd($nodedata)
	{
		$db=$this->db;
		$this->SetTS("nodes");
		$session=$this->session;
		$db->ExecSQL("INSERT INTO `nodes` (`name`, `mac`, `ipaddr`, `ownerid`, `creatorid`, `creationdate`) VALUES ('".strtoupper($nodedata[name])."', '".strtoupper($nodedata[mac])."', '".$nodedata[ipaddr]."', '".$nodedata[ownerid]."', '".$session->id."', UNIX_TIMESTAMP())");
		$db->FetchRow("SELECT max(id) FROM `nodes`");
		return $db->row["max(id)"];
	}

	function UserAdd($useradd)
	{
		$db=$this->db;
		$this->SetTS("users");
		$session=$this->session;
		if(!isset($useradd[status]))
			$useradd[status] = 1;
		$db->ExecSQL("INSERT INTO `users` (`name`, `lastname`, `phone1`, `phone2`, `phone3`, `address`, `email`, `status`, `tariff`, `creationdate`, `moddate`, `creatorid`, `modid` ) VALUES ('".ucwords($useradd[name])."', '".strtoupper($useradd[lastname])."', '".$useradd[phone1]."', '".$useradd[phone2]."', '".$useradd[phone3]."', '".$useradd[address]."', '".$useradd[email]."', '".$useradd[status]."', '".$useradd[tariff]."', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '".$session->id."', '".$session->id."')");
		$db->FetchRow("SELECT max(id) FROM `users`");
		return $db->row["max(id)"];
	}

	function GetUserEmail($id)
	{
		$db=$this->db;
		$db->FetchRow("SELECT `email` FROM `users` WHERE `id` = '".$id."' LIMIT 1");
		return $db->row[email];
	}

	function UserExists($id)
	{
		$db=$this->db;
		return $db->CountRows("SELECT * FROM `users` WHERE `id` = '".$id."' LIMIT 1");
	}

	function NetworkExists($id)
	{
		$db=$this->db;
		return $db->CountRows("SELECT * FROM `networks` WHERE `id` = '".$id."' LIMIT 1");
	}	

	function TariffExists($id)
	{
		$db=$this->db;
		return $db->CountRows("SELECT * FROM `tariffs` WHERE `id` = '".$id."' LIMIT 1");
	}


	function IsIPFree($ip)
	{
		$db=$this->db;
		if($db->CountRows("SELECT * FROM `nodes` WHERE `ipaddr` = '".$ip."' LIMIT 1"))
			return FALSE;
		else
			return TRUE;
	}

	function NodeExists($id)
	{
		$db=$this->db;
		return $db->CountRows("SELECT * FROM `nodes` WHERE `id` = '".$id."' LIMIT 1");
	}

	function AddBalance($addbalance)
	{
		$db=$this->db;
		$this->SetTS("cash");
		$session=$this->session;
		return $db->ExecSQL("INSERT INTO `cash`	(time, adminid, type, value, userid, comment) VALUES (UNIX_TIMESTAMP(),'".$session->id."','".$addbalance[type]."','".$addbalance[value]."','".$addbalance[userid]."','".$addbalance[comment]."' )");
	}

	function GetEmails($group)
	{
		$db=$this->db;
		if($group == 0)
			$emails = $db->FetchArray("SELECT `id`, `email` FROM `users`");
		else
			$emails = $db->FetchArray("SELECT `id`, `email` FROM `users` WHERE `status` = '".$group."'");
		$emails[total]=sizeof($emails[id]);
		if($emails[total])
			foreach($emails[id] as $key => $value)
				$emails[username][$key] = ucwords(strtolower($this->GetUserName($value)));
			
		return $emails;
	}

	function Mailing($mailing)
	{
		$db=$this->db;
		$session=$this->session;
		$emails = $this->GetEmails($mailing[group]);
		
		if(sizeof($emails[id]))
			foreach($emails[id] as $key => $value)
			{
				
				if($emails[email][$key] != "")
				{
					mail(
						$emails[username][$key]." <".$emails[email][$key].">",
						$mailing[subject],
						$mailing[body],
						"From: ".$mailing[sender]." <".$mailing[from].">\r\n".
						"Content-type: text/plain; charset=\"iso-8859-2\"\r\n".
						"X-Mailer: LMS-".$this->version."/PHP-".phpversion()."\r\n".
						"X-Remote-IP: ".$_SERVER['REMOTE_ADDR']."\r\n".
						"X-HTTP-User-Agent: ".$_SERVER['HTTP_USER_AGENT']."\r\n");
						echo "<img src=\"img/mail.gif\" border=\"0\" align=\"absmiddle\" alt=\"\"> ".($key+1)." z ".$emails[total]." (".sprintf("%02.2f",round((100/$emails[total])*($key+1),2))."%): ".$emails[username][$key]." &lt;".$emails[email][$key]."&gt;<BR>\n";
						flush();
				}
				
			}
		return $return;
	}

	function GetPrefixList()
	{
		for($i=30;$i>15;$i--)
		{
			$prefixlist[id][] = $i;
			$prefixlist[value][] = $i." (".pow(2,32-$i)." adresów)";
		}
		
		return $prefixlist;
	}

	function NetworkAdd($netadd)
	{
		$db=$this->db;

		if($netadd[prefix] != "")
			$netadd[mask] = prefix2mask($netadd[prefix]);
		$this->SetTS("networks");
		$db->ExecSQL("INSERT INTO `networks` (`name`, `address`, `mask`, `gateway`, `dns`, `domain`, `wins`, `dhcpstart`, `dhcpend`) VALUES ( '".strtoupper($netadd[name])."','".$netadd[address]."','".$netadd[mask]."','".$netadd[gateway]."', '".$netadd[dns]."', '".$netadd[domain]."', '".$netadd[wins]."', '".$netadd[dhcpstart]."', '".$netadd[dhcpend]."' )");
		$db->FetchRow("SELECT id FROM `networks` WHERE `address` = '".$netadd[address]."'");
		return $db->row[id];
	}

	function NetworkDelete($id)
	{
		$db=$this->db;
		$this->SetTS("networks");
		return $db->ExecSQL("DELETE FROM `networks` WHERE `id` = '".$id."'");
	}

	function GetAdminList()
	{
		$db=$this->db;
		$admins=$db->FetchArray("SELECT `id`, `login`, `name`, `lastlogindate`, `lastloginip` FROM `admins` ORDER BY `login` ASC");
		$admins[total] = sizeof($admins[id]);
		if($admins[total])
			foreach($admins[id] as $key => $value)
			{
				if($admins[lastlogindate][$key])
					$admins[lastlogin][$key] = date("Y/m/d H:i",$admins[lastlogindate][$key]);
				else
					$admins[lastlogin][$key] = "-";
				if(check_ip($admins[lastloginip][$key]))
					$admins[lastloginhost][$key] = gethostbyaddr($admins[lastloginip][$key]);
				else
				{
					$admins[lastloginhost][$key] = "-";
					$admins[lastloginip][$key] = "-";
				}
					
			}
		return $admins;
	}

	function GetAdminIDByLogin($login)
	{
		$db=$this->db;
		$db->FetchRow("SELECT `id` FROM `admins` WHERE `login` = '".$login."' LIMIT 1");
			return $db->row[id];
	}

	function AdminAdd($adminadd)
	{
		$db=$this->db;
		$db->ExecSQL("INSERT INTO `admins` (`login`, `name`, `passwd`) VALUES ('".$adminadd[login]."', '".$adminadd[name]."', '".crypt($adminadd[password])."')");
		$db->FetchRow("SELECT max(id) FROM `admins`");
		return $db->row["max(id)"];
	}

	function AdminDelete($id)
	{
		$db=$this->db;
		return $db->ExecSQL("DELETE FROM `admins` WHERE `id` = '".$id."' LIMIT 1");
	}
	
	function AdminExists($id)
	{
		$db=$this->db;
		return $db->CountRows("SELECT * FROM `admins` WHERE `id` = '".$id."' LIMIT 1");
	}

	function GetNodeOwner($id)
	{
		$db=$this->db;
		$db->FetchRow("SELECT `ownerid` FROM `nodes` WHERE `id` = '".$id."' LIMIT 1");
		return $db->row[ownerid];
	}

	function NodeUpdate($nodedata)
	{
		$db=$this->db;
		$session=$this->session;
		$this->SetTS("nodes");
		return $db->ExecSQL("UPDATE `nodes` SET `name` = '".strtoupper($nodedata[name])."',
		`ipaddr` = '".$nodedata[ipaddr]."',
		`mac` = '".$nodedata[mac]."',
		`moddate` = UNIX_TIMESTAMP(),
		`modid` = '".$session->id."'
		WHERE `id` = '".$nodedata[id]."' LIMIT 1");
	}

	function GetUsersWithTariff($id)
	{
		$db=$this->db;
		return $db->CountRows("SELECT * FROM `users` WHERE `tariff` = '".$id."' AND `status` = '3'");
	}
	
	function GetTariffList()
	{
		$db=$this->db;
		$tarifflist = $db->FetchArray("SELECT `id`, `name`, `value`, `description` FROM `tariffs` ORDER BY `value` DESC");
		$tarifflist[total] = sizeof($tarifflist[id]);
		if($tarifflist[total])
			foreach($tarifflist[id] as $key => $value)
			{
				$tarifflist[users][$key] = $this->GetUsersWithTariff($value);
				$tarifflist[totalusers] = $tarifflist[totalusers] + $tarifflist[users][$key];
				$tarifflist[income][$key] = $tarifflist[users][$key] * $tarifflist[value][$key];
				$tarifflist[totalincome] = $tarifflist[totalincome] + $tarifflist[income][$key];
			}
		return $tarifflist;
	}

	function GetAdminInfo($id)
	{
		$db=$this->db;
		$admins = $db->FetchRow("SELECT `id`, `login`, `name`, `email`, `lastlogindate`, `lastloginip`, `failedlogindate`, `failedloginip` FROM `admins` WHERE `id` = '".$id."' LIMIT 1");
		if($admins[id])
		{
			if($admins[lastlogindate])
				$admins[lastlogin] = date("Y/m/d H:i",$admins[lastlogindate]);
			else
				$admins[lastlogin] = "-";
			
			if($admins[failedlogindate])
				$admins[faillogin] = date("Y/m/d H:i",$admins[failedlogindate]);
			else
				$admins[faillogin] = "-";
			
			
			if(check_ip($admins[lastloginip]))
				$admins[lastloginhost] = gethostbyaddr($admins[lastloginip]);
			else
			{
				$admins[lastloginhost] = "-";
				$admins[lastloginip] = "-";
			}

			if(check_ip($admins[failedloginip]))
				$admins[failloginhost] = gethostbyaddr($admins[failedloginip]);
			else
			{
				$admins[failloginhost] = "-";
				$admins[failloginip] = "-";
			}
		}
		return $admins;
	}
}
?>
