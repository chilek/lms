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
		$db->ExecSQL("SELECT `name` FROM `admins` WHERE `id` = '".$id."' LIMIT 1");
		$db->FetchRow();
		return $db->row[0];
	}
	
	function GetTariffValue($id)
	{
		$db=$this->db;
		$db->ExecSQL("SELECT `value` FROM `tariffs` WHERE `id` = '".$id."' LIMIT 1");
		$db->FetchRow();
		return $db->row[0];
	}

	function GetTariffName($id)
	{
		$db=$this->db;
		$db->ExecSQL("SELECT `name` FROM `tariffs` WHERE `id` = '".$id."' LIMIT 1");
		$db->FetchRow();
		return $db->row[0];
	}

	function UserUpdate($userdata)
	{
		$session=$this->session;
		$db=$this->db;
		$result = $db->ExecSQL("UPDATE `users` SET 
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
		return $result;
	}

	function GetUserNodesNo($id)
	{
		$db=$this->db;
		return $db->CountRows("SELECT * FROM `nodes` WHERE `ownerid` = '".$id."'");		
	}

	function GetNetworks()
	{
		$db=$this->db;
		$db->ExecSQL("SELECT `id`, `name`, `address`, `mask` FROM `networks` ORDER BY `address` ASC");
		while($db->FetchRow())
			list(
				$return[id][],
				$return[name][],
				$return[address][],
				$return[mask][]
			) = $db->row;
		for($i=0;$i<sizeof($return[id]);$i++)
		{
			$return[addresslong][$i] = ip_long($return[address][$i]);
			$return[prefix][$i] = mask2prefix($return[mask][$i]);
		}
		return $return;
	}

	function IsIPValid($ip)
	{
		$networks = $this->GetNetworks();
		for($i=0;$i<sizeof($networks);$i++)
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
		$db->ExecSQL("SELECT `id` FROM `nodes` WHERE `ipaddr` = '".$ipaddr."' LIMIT 1");
		$db->FetchRow();
		return $db->row[0];
	}

	function GetNodeIDByMAC($mac)	
	{
		$db=$this->db;
		$db->ExecSQL("SELECT `id` FROM `nodes` WHERE `mac` = '".$mac."' LIMIT 1");
		$db->FetchRow();
		return $db->row[0];
	}

	function GetNodeIDByName($name)
	{
		$db=$this->db;
		$db->ExecSQL("SELECT `id` FROM `nodes` WHERE `name` = '".$name."' LIMIT 1");
		$db->FetchRow();
		return $db->row[0];
	}

	function GetNodeName($id)
	{
		$db=$this->db;
		$db->ExecSQL("SELECT `name` FROM `nodes` WHERE `id` = '".$id."' LIMIT 1");
		$db->FetchRow();
		return $db->row[0];
	}

	function GetNodeNameByIP($ipaddr)
	{
		$db=$this->db;
		$db->ExecSQL("SELECT `name` FROM `nodes` WHERE `ipaddr` = '".$ipaddr."' LIMIT 1");
		$db->FetchRow();
		return $db->row[0];
	}

	function GetUserStatus($id)
	{
		$db=$this->db;
		$db->ExecSQL("SELECT `status` FROM `users` WHERE `id` = '".$id."' LIMIT 1");
		$db->FetchRow();
		return $db->row[0];
	}

	function GetNetwork($id)
	{
		$db=$this->db;
		if($id=="ALL") $db->ExecSQL("SELECT `address`, `mask`, `name` FROM `networks`");
		else $db->ExecSQL("SELECT `address`, `mask`, `name` FROM `networks` WHERE `id` = '".$id."' LIMIT 1");
		while($db->FetchRow()){
			list($addr,$mask,$name) = $db->row;
			$c=0;
			for($i=ip_long($addr)+1;$i<ip_long(getbraddr($addr,$mask));$i++)
			{
				$return[addresslong][] = $i;
				$return[address][] = long2ip($i);
				if($c == "0") $return[mark][] = $name;
				else $return[mark][] = "";
				$c++;
				
			}
		}
		for($i=0;$i<sizeof($return[address]);$i++){
			$db->ExecSQL("SELECT `name`, `id` FROM `nodes` WHERE `ipaddr` = '".$return[address][$i]."' LIMIT 1");
			$db->FetchRow();
			$return[nodeid][$i]= $db->row[1];
			$return[nodename][$i] = $db->row[0];
		}
		array_multisort($return[addresslong],$return[address],$return[nodeid],$return[nodename],$return[mark]);
		return $return;
	}
			

	function GetUser($id)
	{
		$db=$this->db;
		$db->ExecSQL("SELECT * FROM `users` WHERE `id` = '".$id."' LIMIT 1");
		$db->FetchRow();
		list($return[id],$return[lastname],$return[name],$return[status],$return[email],$return[phone1],$return[phone2],$return[phone3],$return[address],$return[tariff],$return[info],$return[creationdate],$return[moddate],$return[creatorid],$return[modid])=$db->row;
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
	
		$db->ExecSQL($sql);

		while($db->FetchRow()){
			
			list(
				$userlist[id][],
				$userlist[lastname][],
				$userlist[name][],
				$userlist[status][],
				$userlist[email][],
				$userlist[phone1][],
				$userlist[address][],
				$userlist[info][],
				$userlist[crdate][],$userlist[moddate][],$userlist[crid][],$userlist[modid][]
			) = $db->row;

		}

		for($i=0;$i<sizeof($userlist[id]);$i++){

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
			list(
				$return[id][],
				$return[name][],
				$return[mac][],
				$return[ipaddr][],
				$return[ownerid][],
				$return[creationdate][],
				$return[moddate][],
				$return[creatorid][],
				$return[modid][],
				$return[access][]
			) = $db->row;
			$return[iplong][] = ip_long($db->row[3]);
		}
		$return[total] = sizeof($return[id]);
		return $return;
	}
	
	function GetUserName($id)
	{
		$db=$this->db;
		$db->ExecSQL("SELECT `username` FROM `users` WHERE `id` = '".$id."' LIMIT 1");
		$db->FetchRow();
		$username = explode(" ",$db->row[0]);
                $rusername = $username[0];
                for($i=1;$i<sizeof($username);$i++)
                        $rusername .= " " . $username[$i];
	
		return $rusername;
	}

	function NodeSet($id)
	{
		$db=$this->db;
		$db->ExecSQL("SELECT `access` FROM `nodes` WHERE `id` = '".$id."' LIMIT 1");
		$db->FetchRow();
		if($db->row[0]=="Y")
			return $db->ExecSQL("UPDATE `nodes` SET `access` = 'N' WHERE `id` = '".$id."' LIMIT 1");
		else
			return $db->ExecSQL("UPDATE `nodes` SET `access` = 'Y' WHERE `id` = '".$id."' LIMIT 1");
	}

	function GetOwner($id)
	{
		$db=$this->db;
		$db->ExecSQL("SELECT `ownerid` FROM `nodes` WHERE `id` = '".$id."' LIMIT 1");
		$db->FetchRow();
		return $db->row[0];
	}

	function GetUserBalance($id)
	{
		$return = $this->GetUserBalanceList($id);
		return $return[balance];
	}

	function GetUserBalanceList($id)
	{
		$db=$this->db;

		$db->ExecSQL("SELECT * FROM cash WHERE userid = '".$id."'");

		while($db->FetchRow())

		list(
			$saldolist[id][],
			$saldolist[time][],
			$saldolist[adminid][],
			$saldolist[type][],
			$saldolist[value][],
			$saldolist[userid][],
			$saldolist[comment][]
		) = $db->row;
		
		for($i=0;$i<sizeof($saldolist[id]);$i++)
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
//			echo "ID: $i / Przed: ".$saldolist[before][$i]." / Warto¶æ: ".$saldolist[value][$i]."/ Po: ".$saldolist[after][$i]." / Round: ".round($saldolist[value][$i],2)."<BR>";
			$saldolist[adminname][$i]=$this->GetAdminName($saldolist[adminid][$i]);
			$saldolist[date][$i]=date("Y/m/d H:i",$saldolist[time][$i]);

		}

		$saldolist[balance] = $saldolist[after][sizeof($saldolist[id])-1];
		$saldolist[total] = sizeof($saldolist[id]);
	
		return $saldolist;

	}

	function GetTariffs()
	{
		$db=$this->db;
		$db->ExecSQL("SELECT id, name, value FROM tariffs ORDER BY value DESC  ");
		while($db->FetchRow())
			list(
				$tariffs[id][],
				$tariffs[name][],
				$tariffs[value][]
			) = $db->row;
		return $tariffs;
	}

	function GetUserAddress($id)
	{
		$db=$this->db;
		$db->ExecSQL("SELECT `address` FROM `users` WHERE `id` = '".$id."' LIMIT 1");
		$db->Fetchrow();
		return $db->row[0];
	}

	function NodeAdd($nodedata)
	{
		$db=$this->db;
		$session=$this->session;
		return $db->ExecSQL("INSERT INTO `nodes` (`name`, `mac`, `ipaddr`, `ownerid`, `creatorid`, `creationdate`) VALUES ('".strtoupper($nodedata[name])."', '".strtoupper($nodedata[mac])."', '".$nodedata[ipaddr]."', '".$nodedata[ownerid]."', '".$session->id."', '".time()."')");
	}

	function UserAdd($useradd)
	{
		$db=$this->db;
		$session=$this->session;
		if(!isset($useradd[status]))
			$useradd[status] = 1;
		if(!isset($useradd[tariff]))
			$useradd[tariff] = 1;
		$db->ExecSQL("INSERT INTO `users` (`name`, `lastname`, `phone1`, `phone2`, `phone3`, `address`, `email`, `status`, `tariff`, `creationdate`, `moddate`, `creatorid`, `modid` ) VALUES ('".capitalize($useradd[name])."', '".strtoupper($useradd[lastname])."', '".$useradd[phone1]."', '".$useradd[phone2]."', '".$useradd[phone3]."', '".$useradd[address]."', '".$useradd[email]."', '".$useradd[status]."', '".$useradd[tariff]."', '".time()."', '".time()."', '".$session->id."', '".$session->id."')");
		$db->ExecSQL("SELECT max(id) FROM `users`");
		$db->FetchRow();
		return $db->row[0];
	}

	function GetUserEmail($id)
	{
		$db=$this->db;
		$db->ExecSQL("SELECT `email` FROM `users` WHERE `id` = '".$id."' LIMIT 1");
		$db->Fetchrow();
		return $db->row[0];
	}

	function GetUserPhones($id)
	{
		$db=$this->db;
		$db->ExecSQL("SELECT `phone1`, `phone2`, `phone3` FROM `users` WHERE `id` = '".$id."' LIMIT 1");
		$db->Fetchrow();
		return $db->row;
	}

	
	function UserExists($id)
	{
		$db=$this->db;
		return $db->CountRows("SELECT * FROM `users` WHERE `id` = '".$id."' LIMIT 1");
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
