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
 *  Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 *  $Id$
 */

// LMS Class - contains internal LMS database functions used
// to fetch data like usernames, searching for mac's by ID, etc..

class LMS {

	var $db;
	var $session;
	var $version = '1.0.20';

	function LMS($db,$session)
	{
		$this->db=$db;
		$this->session=$session;
	}

	function DeleteUser($id)
	{
		$db=$this->db;
		$db->ExecSQL("DELETE FROM `nodes` WHERE `ownerid` = '".$id."'");
		return $db->ExecSQL("DELETE FROM `users` WHERE `id` = '".$id."' LIMIT 1");
	}

	function DeleteNode($id)
	{
		$db=$this->db;
		return $db->ExecSQL("DELETE FROM `nodes` WHERE `id` = '".$id."'");
	}

	function GetAdminName($id)
	{
		$db=$this->db;
		$db->FetchRow("SELECT `name` FROM `admins` WHERE `id` = '".$id."' LIMIT 1");
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
		`moddate` = '".time()."'
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
		$return = $db->FetchArray("SELECT `id`, `name`, `address`, `mask` FROM `networks` ORDER BY `address` ASC");
		if(sizeof($return[id]))
			foreach($return[id] as $i => $v)
			{
				$return[addresslong][$i] = ip_long($return[address][$i]);
				$return[prefix][$i] = mask2prefix($return[mask][$i]);
			}
		
		return $return;
	}

	function GetNetworkList()
	{
		$db=$this->db;
		$networks = $db->FetchArray("SELECT `id`, `name`, `address`, `mask`, `gateway`, `dns`, `domain`, `wins`, `dhcpstart`, `dhcpend` FROM `networks`");
		if(sizeof($networks[id]))
		{
			foreach($networks[id] as $key => $value)
			{
				$networks[addresslong][$key] = ip_long($networks[address][$key]);
				$networks[prefix][$key] = mask2prefix($networks[mask][$key]);
			}
			array_multisort($networks[name],$networks[id],$networks[address],$networks[mask],$networks[gateway],$networks[wins],$networks[domain],$networks[dns],$networks[dhcpstart],$networks[dhcpend],$networks[prefix],$networks[addresslong]);
		}
		return $networks;
	}

	function IsIPValid($ip)
	{
		$networks = $this->GetNetworks();
		foreach($networks[id] as $i => $v)
			if((ip_long($ip) > $networks[addresslong][$i])&&(ip_long($ip) < ip_long(getbraddr($networks[address][$i],$networks[mask][$i])))	)
				return TRUE;
		return FALSE;
	}

	function GetMACs()
	{
		$file=fopen("/proc/net/arp","r");
		while(!feof($file))
		{
			$line=fgets($file);
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

	function GetNetwork($id)
	{
		$db=$this->db;
		if($id=="ALL") $db->ExecSQL("SELECT `address`, `mask`, `name` FROM `networks`");
		else $db->ExecSQL("SELECT `address`, `mask`, `name` FROM `networks` WHERE `id` = '".$id."' LIMIT 1");
		while($db->FetchRow()){
			foreach($db->row as $key => $value)
				$$key = $value;

			$c=0;
			for($i=ip_long($address)+1;$i<ip_long(getbraddr($address,$mask));$i++)
			{
				$return[addresslong][] = $i;
				$return[address][] = long2ip($i);
				if($c == "0") $return[mark][] = $name;
				else $return[mark][] = "";
				$c++;
				
			}
		}
		if(sizeof($return[address]))
		{
			foreach($return[address] as $i => $v)
			{
				$db->ExecSQL("SELECT `name`, `id` FROM `nodes` WHERE `ipaddr` = '".$return[address][$i]."' LIMIT 1");
				$db->FetchRow();
				$return[nodeid][$i]= $db->row[id];
				$return[nodename][$i] = $db->row[name];
			}
			array_multisort($return[addresslong],$return[address],$return[nodeid],$return[nodename],$return[mark]);
		}
		return $return;
	}
			

	function GetUser($id)
	{
		$db=$this->db;
		$return = $db->FetchRow("SELECT * FROM `users` WHERE `id` = '".$id."' LIMIT 1");
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

		$usernames = $db->FetchArray("SELECT `id`, `name`, `lastname` FROM `users` WHERE `status` = '3'");

		if(sizeof($usernames[id]))
		{
			foreach ($usernames[id] as $key => $value)
				$usernames[username][$key] = strtoupper($usernames[lastname][$key])." ".ucwords($usernames[name][$key]);		
			array_multisort($usernames[username],4,$usernames[id],$usernames[name],$usernames[lastname]);
		}
		return $usernames;

	}

	function GetUserList($order=NULL,$state=NULL)
	{

		$db=$this->db;

		$sql="SELECT id, lastname, name, status, email, phone1, address, info, creationdate, moddate, creatorid, modid FROM users ";

		if(!isset($state)) $state="3";
		if(!isset($order)) $order="username,asc";
		
		switch ($state){
			case "3":
				$sql .= " WHERE status = 3 ";
			break;
			case "2":
				$sql .= " WHERE status = 2 ";
			break;
			case "1":
				$sql .= " WHERE status = 1 ";
			break;
		}
	
		$userlist = $db->FetchArray($sql);

//		while($db->FetchRow())
//
//			foreach($db->row as $key => $value)
//				$userlist[$key][] = $value;
		
		$userlist[crdate] = $userlist[creationdate];
		$userlist[crid] = $userlist[creatorid];

		if(sizeof($userlist[id]))
			foreach($userlist[id] as $i => $v)
			{
				$userlist[username][$i] = strtoupper($userlist[lastname][$i])." ".$userlist[name][$i];
				$userlist[balance][$i] = $this->GetUserBalance($userlist[id][$i]);
			}
			
		list($order,$direction)=explode(",",$order);
		
		if($direction != "desc") $direction = 4;
		else $direction = 3;

		if(sizeof($userlist[id])) switch($order){
			case "username":
				array_multisort($userlist[username],$direction,$userlist[id],$userlist[status],$userlist[email],$userlist[phone1],$userlist[address],$userlist[info],$userlist[balance],$userlist[crdate],$userlist[moddate],$userlist[crid],$userlist[modid]);
				break;
			case "id":
				array_multisort($userlist[id],$direction,SORT_NUMERIC,$userlist[username],$userlist[status],$userlist[email],$userlist[phone1],$userlist[address],$userlist[info],$userlist[balance],$userlist[crdate],$userlist[moddate],$userlist[crid],$userlist[modid]);
				break;
			case "email":
				array_multisort($userlist[email],$direction,$userlist[username],$userlist[id],$userlist[status],$userlist[phone1],$userlist[address],$userlist[info],$userlist[balance],$userlist[crdate],$userlist[moddate],$userlist[crid],$userlist[modid]);
				break;
			case "address":
				array_multisort($userlist[address],$direction,$userlist[id],$userlist[username],$userlist[status],$userlist[email],$userlist[phone1],$userlist[info],$userlist[balance],$userlist[crdate],$userlist[moddate],$userlist[crid],$userlist[modid]);
				break;
			case "balance":
				array_multisort($userlist[balance],$direction,SORT_NUMERIC,$userlist[address],$userlist[id],$userlist[username],$userlist[status],$userlist[email],$userlist[phone1],$userlist[info],$userlist[crdate],$userlist[moddate],$userlist[crid],$userlist[modid]);
				break;
			case "phone":
				array_multisort($userlist[phone1],$direction,$userlist[username],$userlist[id],$userlist[status],$userlist[email],$userlist[address],$userlist[info],$userlist[balance],$userlist[crdate],$userlist[moddate],$userlist[crid],$userlist[modid]);
				break;
		}

		$userlist[state]=$state;
		$userlist[order]=$order;
		$userlist[direction]=$direction;
		$userlist[total]=sizeof($userlist[id]);
		
		return $userlist;

	}
		
		

	function GetUserNodes($id)
	{
		$db=$this->db;
		$db->ExecSQL("SELECT * FROM `nodes` WHERE `ownerid` = '".$id."' ORDER BY `name` ASC");
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
		$nodelist = $db->FetchArray("SELECT `id`, `ipaddr`, `mac`, `name`, `ownerid`, `access` FROM `nodes`");
		if(sizeof($nodelist[id]))
			foreach($nodelist[id] as $key => $value){ 
				$nodelist[iplong][$key] = ip_long($nodelist[ipaddr][$key]);
				$nodelist[owner][$key] = $this->GetUserName($nodelist[ownerid][$key]);
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
	
	function NodeSet($id)
	{
		$db=$this->db;
		$db->FetchRow("SELECT `access` FROM `nodes` WHERE `id` = '".$id."' LIMIT 1");
		if($db->row[access]=="Y")
			return $db->ExecSQL("UPDATE `nodes` SET `access` = 'N' WHERE `id` = '".$id."' LIMIT 1");
		else
			return $db->ExecSQL("UPDATE `nodes` SET `access` = 'Y' WHERE `id` = '".$id."' LIMIT 1");
	}

	function GetOwner($id)
	{
		$db=$this->db;
		$db->FetchRow("SELECT `ownerid` FROM `nodes` WHERE `id` = '".$id."' LIMIT 1");
		return $db->row[ownerid];
	}

	function GetUserBalance($id)
	{
		$return = $this->GetUserBalanceList($id);
		return $return[balance];
	}

	function GetUserBalanceList($id)
	{
		$db=$this->db;

		$saldolist = $db->FetchArray("SELECT * FROM cash WHERE userid = '".$id."'");
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
		$session=$this->session;
		$db->ExecSQL("INSERT INTO `nodes` (`name`, `mac`, `ipaddr`, `ownerid`, `creatorid`, `creationdate`) VALUES ('".strtoupper($nodedata[name])."', '".strtoupper($nodedata[mac])."', '".$nodedata[ipaddr]."', '".$nodedata[ownerid]."', '".$session->id."', '".time()."')");
		$db->FetchRow("SELECT max(id) FROM `nodes`");
		return $db->row["max(id)"];
	}

	function UserAdd($useradd)
	{
		$db=$this->db;
		$session=$this->session;
		if(!isset($useradd[status]))
			$useradd[status] = 1;
		$db->ExecSQL("INSERT INTO `users` (`name`, `lastname`, `phone1`, `phone2`, `phone3`, `address`, `email`, `status`, `tariff`, `creationdate`, `moddate`, `creatorid`, `modid` ) VALUES ('".ucwords($useradd[name])."', '".strtoupper($useradd[lastname])."', '".$useradd[phone1]."', '".$useradd[phone2]."', '".$useradd[phone3]."', '".$useradd[address]."', '".$useradd[email]."', '".$useradd[status]."', '".$useradd[tariff]."', '".time()."', '".time()."', '".$session->id."', '".$session->id."')");
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
		$session=$this->session;
		return $db->ExecSQL("INSERT INTO `cash`	(time, adminid, type, value, userid, comment) VALUES ('".time()."','".$session->id."','".$addbalance[type]."','".$addbalance[value]."','".$addbalance[userid]."','".$addbalance[comment]."' )");
	}
}

?>
