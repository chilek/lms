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

	function SetTS($table)
	{
		$db=$this->db;
		$db->ExecSQL("DELETE FROM `timestamps` WHERE `tablename` = '".$table."'");
		$db->ExecSQL("INSERT INTO `timestamps` (`tablename`, `timestamp`) VALUES ('".$table."', '".time()."')");
	}

	function GetTS($table)
	{
		$db=$this->db;
		$db->ExecSQL("SELECT `timestamp` FROM `timestamps` WHERE `tablename` = '".$table."'");
		$db->FetchRow();
		return $db->row[0];
	}

	function DeleteUser($id)
	{
		$db=$this->db;
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

	function UpdateUser($userdata)
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
		$this->SetTS("users");
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
			

	function GetUserRecord($id)
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

		$sql="SELECT id, lastname, name, status, email, phone1, address, info FROM users ";

		if(!isset($state)) $state="3";
		if(!isset($order)) $order="username,asc";
		
		$db->ExecSQL($sql);

		if($this->GetTS("users") > $_SESSION[ts][users])
		{
			echo "<BR>DEBUG: Fetching userlist from DB<BR>";
			while($db->FetchRow()){
				list(
					$userlist[id][],
					$userlist[lastname][],
					$userlist[name][],
					$userlist[status][],
					$userlist[email][],
					$userlist[phone1][],
					$userlist[address][],
					$userlist[info][]
				) = $db->row;
				$userlist[username][] = strtoupper($db->row[1])." ".$db->row[2];
			}


			$_SESSION[cache][userlist] = $userlist;
			$_SESSION[ts][users] = $this->GetTS("users");
		}else{
			$userlist = $_SESSION[cache][userlist];
		}
		
		if($this->GetTS("cash") > $_SESSION[ts][cash])
		{
			echo "<BR>DEBUG: Fetching user's balance info from DB<BR>";
			for($i=0;$i<sizeof($userlist[id]);$i++)
				$userlist[balance][$i] = $this->GetUserBalance($userlist[id][$i]);
			$_SESSION[cache][userlist] = $userlist;
			$_SESSION[ts][cash] = $this->GetTS("cash");
		}else{
			$userlist = $_SESSION[cache][userlist];
		}
		
		list($order,$direction)=explode(",",$order);
		
		if($direction != "desc") $direction = 4;
		else $direction = 3;

		if(sizeof($userlist[id])) switch($order){
			case "username":
				array_multisort($userlist[username],$direction,$userlist[id],$userlist[status],$userlist[email],$userlist[phone1],$userlist[address],$userlist[info],$userlist[balance]);
				break;
			case "id":
				array_multisort($userlist[id],$direction,SORT_NUMERIC,$userlist[username],$userlist[status],$userlist[email],$userlist[phone1],$userlist[address],$userlist[info],$userlist[balance]);
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
		$this->SetTS("nodes");
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
		$db=$this->db;
		$db->ExecSQL("SELECT type, value FROM `cash` WHERE `userid` = '".$id."'");
		$return = 0;
		while($db->FetchRow()){
			switch($db->row[0]){
				case "3":
					$return=$return+$db->row[1];
				break;
				case "4":
					$return=$return-$db->row[1];
				break;
			}
		}
		return $return;
	}

	function GetUserBalanceList($id)
	{
		$db=$this->db;

		$db->ExecSQL("SELECT * FROM cash WHERE userid = '".$id."'");

		while($db->FetchRow())

		list(
			$tsaldolist[id][],
			$tsaldolist[time][],
			$tsaldolist[adminid][],
			$tsaldolist[type][],
			$tsaldolist[value][],
			$tsaldolist[userid][],
			$tsaldolist[comment][]
		) = $db->row;

		for($i=0;$i<sizeof($tsaldolist[id]);$i++)
		{
			if($i>0) $tsaldolist[before][$i] = $tsaldolist[after][$i-1];
			else $tsaldolist[before][$i] = 0;

			switch ($tsaldolist[type][$i]){

				case "3":
					$tsaldolist[after][$i] = $tsaldolist[before][$i] + $tsaldolist[value][$i];
					$tsaldolist[name][$i] = "wp³ata";
				break;
				case "4":
					$tsaldolist[after][$i] = $tsaldolist[before][$i] - $tsaldolist[value][$i];
					$tsaldolist[name][$i] = "op³ata";
				break;
			}

			$tsaldolist[adminname][$i]=$this->GetAdminName($tsaldolist[adminid][$i]);
			$tsaldolist[date][$i]=date("Y/m/d H:i",$tsaldolist[time][$i]);

		}

		$tsaldolist[balance] = $tsaldolist[after][sizeof($tsaldolist[id])-1];
		$tsaldolist[total] = sizeof($tsaldolist[id]);
	
		return $tsaldolist;

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

	function AddNode($nodedata)
	{
		$db=$this->db;
		$session=$this->session;
		$this->SetTS("nodes");
		return $db->ExecSQL("INSERT INTO `nodes` (`name`, `mac`, `ipaddr`, `ownerid`, `creatorid`, `creationdate`) VALUES ('".strtoupper($nodedata[name])."', '".strtoupper($nodedata[mac])."', '".$nodedata[ipaddr]."', '".$nodedata[ownerid]."', '".$session->id."', '".time()."')");
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
}

?>
