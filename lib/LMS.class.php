<?

/*
 * LMS version 1.0-cvs
 *
 *  (C) Copyright 2002-2003 Rulez Development Team
 *  (C) Copyright 2001-2003 ASK NetX
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

class LMS 
{

	var $ADB;
	var $SESSION;
	var $CONFIG;
	var $_version = '1.0.65';

	function LMS($ADB,$SESSION)
	{
		$this->SESSION=$SESSION;
		$this->ADB=$ADB;
	}

	function sqlTSfmt()
	{
		switch($this->ADB->databaseType)
		{
			case "mysql":
				return "UNIX_TIMESTAMP()";
				break;
			case "postgres":
				return "EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))";
				break;
			default:
				// fall back to local timestamp instead of remote.
				// it's dangerous if we use remote database where
				// time can (and propably will) differ from local.
				return "'".time()."'";
				break;
		}
	}

	function sqlLIKE()
	{
		switch($this->ADB->databaseType)
		{
			case "postgres":
				return "ILIKE";
				break;

			default:
				return "LIKE";
				break;
		}
	}

	function SetTS($table)
	{
		$this->ADB->Replace("timestamps",array("tablename" => "'_global'","time" => $this->sqlTSfmt() ),"tablename");
		return $this->ADB->Replace("timestamps",array("tablename" => "'".$table."'","time" => $this->sqlTSfmt() ),"tablename");
	}

	function GetTS($table)
	{
		return $this->ADB->GetOne("SELECT time FROM timestamps WHERE tablename=?",array($table));
	}

	function DeleteTS($table)
	{
		return $this->ADB->Execute("DELETE FROM timestamps WHERE tablename=?",array($table));
	}

	function SetAdminPassword($id,$passwd)
	{
		$this->SetTS("admins");
		$this->ADB->Execute("UPDATE admins SET passwd=? WHERE id=?",array(crypt($passwd),$id));
	}

	function DeleteUser($id)
	{
		$this->SetTS("users");
		$this->SetTS("nodes");
		$res1=$this->ADB->Execute("DELETE FROM nodes WHERE ownerid=?",array($id));
		$res2=$this->ADB->Execute("DELETE FROM users WHERE id=?",array($id));
		return $res1 || $res2;
	}

	function DeleteNode($id)
	{
		return $this->ADB->Execute("DELETE FROM nodes WHERE id=?",array($id));
	}

	function GetAdminName($id)
	{
		return $this->ADB->GetOne("SELECT name FROM admins WHERE id=?",array($id));
	}

	function GetNetworkName($id)
	{	
		return $this->ADB->GetOne("SELECT name FROM networks WHERE id=?",array($id));
	}

	function GetTariffValue($id)
	{
		return str_replace(".",",",$this->ADB->GetOne("SELECT value FROM tariffs WHERE id=?",array($id)));
	}

	function GetTariffName($id)
	{
		return $this->ADB->GetOne("SELECT name FROM tariffs WHERE id=?",array($id));
	}

	function GetNetIDByIP($ipaddr)
	{
		if($networks = $this->ADB->GetAll("SELECT id, address, mask FROM networks"))
			foreach($networks as $idx => $row)
				if(isipin($ipaddr,$row[address],$row[mask]))
					return $row[id];
		return FALSE;
	}

	function UserUpdate($userdata)
	{
		$this->SetTS("users");
		return $this->ADB->Execute(
		"UPDATE users SET 
		phone1=?, phone2=?, phone3=?, address=?, email=?, tariff=?, info=?, modid=?, status=?, moddate=".$this->sqlTSfmt()." WHERE id=?",
		array(
		$userdata[phone1],$userdata[phone2],$userdata[phone3],$userdata[address],$userdata[email],$userdata[tariff],$userdata[uwagi],$this->SESSION->id,$userdata[status],$userdata[id]));	
	}

	function GetUserNodesNo($id)
	{
		return $this->ADB->GetOne("SELECT COUNT(*) FROM nodes WHERE ownerid=?",array($id));
	}

	function GetNetworks()
	{
		if($netlist = $this->ADB->GetAll("SELECT id, name, address, mask FROM networks"))
			foreach($netlist as $idx => $row)
			{
				$netlist[$idx][addresslong] = ip_long($row[address]);
				$netlist[$idx][prefix] = mask2prefix($row[mask]);
			}
		
		return $netlist;
	}

	function GetNetworkList()
	{
		$tnetworks = $this->ADB->GetAll("SELECT id, name, address, mask, gateway, dns, domain, wins, dhcpstart, dhcpend FROM networks");
		foreach($tnetworks as $idx => $row)
			foreach($row as $field => $value)
			$networks[$field][] = $value;
		
		$nodes = $this->ADB->GetAll("SELECT ipaddr FROM nodes");
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
				if(sizeof($nodes))
					foreach($nodes as $row)
						if(isipin($row[ipaddr],$networks[address][$key],$networks[mask][$key]))
							$networks[assigned][$key] ++;
				$networks[assigned][total] = $networks[assigned][total] + $networks[assigned][$key];
			}
		}
		
		return $networks;
	}

	function IsIPValid($ip,$checkbroadcast=FALSE,$ignoreid=0)
	{
		$networks = $this->GetNetworks();
		if($networks = $this->GetNetworks())
		{
			foreach($networks as $idx => $row)
			{
				if($row[id] != $ignoreid)
					if($checkbroadcast)
					{
						if((ip_long($ip) > $row[addresslong] - 1)&&(ip_long($ip) < ip_long(getbraddr($row[address],$row[mask])) + 1))
						{
							return TRUE;
						}
					}
					else
					{
						if((ip_long($ip) > $row[addresslong])&&(ip_long($ip) < ip_long(getbraddr($row[address],$row[mask]))))
						{
							return TRUE;
						}
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
		
		if($networks = $this->GetNetworks())
			foreach($networks as $idx => $row)
			{
				$broadcast = ip_long(getbraddr($row[address],$row[mask]));
				$netaddr = $row[addresslong];					
				if($row[id] != $ignorenet)
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
		switch(PHP_OS)
		{
			case "Linux":
				if(@is_readable("/proc/net/arp"))
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
						$return[nodename][] = $this->GetNodeNameByMAC($mac);
					}
				}
				break;

			default:
				exec("arp -an|grep -v incompl",$return);
				foreach($return as $arpline)
				{
					list($empty,$ip,$empty,$mac) = explode(" ",$arpline);
					$ip = str_replace("(","",str_replace(")","",$ip));
					$return[mac][] = $mac;
					$return[ip][] = $ip;
					$return[longip][] = ip_long($ip);
					$return[nodename][] = $this->GetNodeNameByMAC($mac);
				}
				break;

		}
		array_multisort($return[longip],$return[mac],$return[ip],$return[nodename]);
		return $return;
	}

	function GetNodeNameByMAC($mac)
	{
		return $this->ADB->GetOne("SELECT name FROM nodes WHERE mac=?",array($mac));
	}		

	function GetNodeIDByIP($ipaddr)
	{
		return $this->ADB->GetOne("SELECT id FROM nodes WHERE ipaddr=?",array($ipaddr));
	}

	function GetNodeIDByMAC($mac)	
	{
		return $this->ADB->GetOne("SELECT id FROM nodes WHERE mac=?",array($mac));
	}

	function GetNodeIDByName($name)
	{
		return $this->ADB->GetOne("SELECT id FROM nodes WHERE name=?",array($name));
	}

	function GetNodeIPByID($id)
	{
		return $this->ADB->GetOne("SELECT ipaddr FROM nodes WHERE id=?",array($id));
	}

	function GetNodeMACByID($id)
	{
		return $this->ADB->GetOne("SELECT mac FROM nodes WHERE id=?",array($id));
	}

	function GetNodeName($id)
	{
		return $this->ADB->GetOne("SELECT name FROM nodes WHERE id=?",array($id));
	}

	function GetNodeNameByIP($ipaddr)
	{
		return $this->ADB->GetOne("SELECT name FROM nodes WHERE ipaddr=?",array($ipaddr));
	}

	function GetUserStatus($id)
	{
		return $this->ADB->GetOne("SELECT status FROM users WHERE id=?",array($id));
	}

	function NetworkShift($network="0.0.0.0",$mask="0.0.0.0",$shift=0)
	{
		$this->SetTS("nodes");
		$this->SetTS("networks");
		if($nodes = $this->ADB->GetAll("SELECT ipaddr, id FROM nodes"))
			foreach($nodes as $value)
				if(isipin($value[ipaddr],$network,$mask))
					$this->ADB->Execute("UPDATE nodes SET ipaddr=? WHERE id=?",array(long2ip(ip_long($value[ipaddr]) + $shift),$value[id]));
	}

	function NetworkUpdate($networkdata)
	{
		$this->SetTS("networks");
		return $this->ADB->Execute("UPDATE networks SET name=?, address=?, mask=?, gateway=?, dns=?, domain=?, wins=?, dhcpstart=?, dhcpend=? WHERE id=?",array(strtoupper($networkdata[name]),$networkdata[address],$networkdata[mask],$networkdata[gateway],$networkdata[dns],$networkdata[domain],$networkdata[wins],$networkdata[dhcpstart],$networkdata[dhcpend],$networkdata[id]));
	}
				
	
	function NetworkCompress($id,$shift=0)
	{
		$this->SetTS("nodes");
		$this->SetTS("networks");
		$network=$this->GetNetworkRecord($id);
		$address = $network[addresslong]+$shift;
		foreach($network[nodes][id] as $key => $value)
		{
			if($value)
			{
				$address ++;
				$this->ADB->Execute("UPDATE nodes SET ipaddr=? WHERE id=?",array(long2ip($address),$value));
			}				
		}
	}

	function NetworkRemap($src,$dst)
	{
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
				$this->ADB->Execute("UPDATE nodes SET ipaddr=? WHERE id=?",array($network[dest][nodes][address][$counter],$value));
				$counter++;
			}
		return $counter;
	}

	function GetNetworkRecord($id)
	{
		$network = $this->ADB->GetRow("SELECT id, name, address, mask, gateway, dns, domain, wins, dhcpstart, dhcpend FROM networks WHERE id=?",array($id));
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

		// wrapper do starego formatu
		$tnnodes = $this->ADB->GetAll("SELECT id, name, ipaddr, ownerid FROM nodes");
		foreach($tnnodes as $row)
			foreach($row as $column => $value)
				$networknodes[$column][] = $value;
		
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
		if($row = $this->ADB->GetRow("SELECT address, mask, name FROM networks WHERE id=?",array($id)))
			foreach($row as $field => $value)
				$$field = $value;
	
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
			// wrapper do starego formatu
			
			if($tnodes = $this->ADB->GetAll("SELECT name, id, ownerid, ipaddr FROM nodes"))
				foreach($tnodes as $row)
					foreach($row as $column => $value)
						$nodes[$column][] = $value;
			
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

		return $return;
	}
			

	function GetUser($id)
	{
		if($return = $this->ADB->GetRow("SELECT id, lastname, name, status, email, phone1, phone2, phone3, address, tariff, info, creationdate, moddate, creatorid, modid FROM users WHERE id=?",array($id)))
		{
			$return[username] = strtoupper($return[lastname])." ".$return[name];	
			$return[createdby] = $this->GetAdminName($return[creatorid]);
			$return[modifiedby] = $this->GetAdminName($return[modid]);
			$return[creationdateh] = date("Y-m-d, H:i",$return[creationdate]);
			$return[moddateh] = date("Y-m-d, H:i",$return[moddate]);
			$return[tariffvalue] = $this->GetTariffValue($return[tariff]);
			$return[tariffname] = $this->GetTariffName($return[tariff]);
			$return[balance] = $this->GetUserBalance($return[id]);
			return $return;
		}else
			return FALSE;
	}

	function GetNode($id)
	{
		if($return = $this->ADB->GetRow("SELECT id, name, ownerid, ipaddr, mac, access, creationdate, moddate, creatorid, modid FROM nodes WHERE id=?",array($id)))
		{
			$return[createdby] = $this->GetAdminName($return[creatorid]);
			$return[modifiedby] = $this->GetAdminName($return[modid]);
			$return[creationdateh] = date("Y-m-d, H:i",$return[creationdate]);
			$return[moddateh] = date("Y-m-d, H:i",$return[moddate]);
			$return[owner] = $this->GetUsername($return[ownerid]);
			$return[netid] = $this->GetNetIDByIP($return[ipaddr]);
			$return[netname] = $this->GetNetworkName($return[netid]);
			return $return;
		}else
			return FALSE;
	}

	function GetUserNames()
	{
		return $this->ADB->GetAll("SELECT id, ".$this->ADB->Concat("UPPER(lastname)","' '","name")." AS username FROM users WHERE status=3 ORDER BY username");
	}

	function GetUserNodesAC($id)
	{
		if($acl = $this->ADB->GetALL("SELECT access FROM nodes WHERE ownerid=?",array($id)))
		{
			foreach($acl as $value)
				if(strtoupper($value[access]) == "Y")
					$y++;
				else
					$n++;

			if($y && !$n) return TRUE;
			if($n && !$y) return FALSE;
		}
		if($this->ADB->GetOne("SELECT COUNT(*) FROM nodes WHERE ownerid=?",array($id)))
			return 2;
		else
			return FALSE;
	}
	
	function GetBalanceList()
	{
		if ($_SESSION[timestamps][getbalancelist][cash] != $this->GetTS("cash") || TRUE ||
			$_SESSION[timestamps][getbalancelist][admins] != $this->GetTS("admins") ||
			$_SESSION[timestamps][getbalancelist][users] != $this->GetTS("users")
			
			)
		{

			if($rs = $this->ADB->Execute("SELECT id, name FROM admins"))
				while(!$rs->EOF)
				{
					$adminlist[$rs->fields[id]] = $rs->fields[name];
					$rs->MoveNext();
				}

			if($rs = $this->ADB->Execute("SELECT id, ".$this->ADB->Concat("UPPER(lastname)","' '","name")." AS username FROM users"))
				while(!$rs->EOF)
				{
					$userslist[$rs->fields[id]] = $rs->fields[username];
					$rs->MoveNext();
				}

			if($balancelist = $this->ADB->GetAll("SELECT id, time, adminid, type, value, userid, comment FROM cash ORDER BY time ASC"))
			{
				foreach($balancelist as $idx => $row)
				{
					$balancelist[$idx][admin] = $adminlist[$row[adminid]];
					$balancelist[$idx][value] = str_replace(".",",",$row[value]);
					$balancelist[$idx][username] = $userslist[$row[userid]];
					if($idx)
						$balancelist[$idx][before] = $balancelist[$idx-1][after];
					else
						$balancelist[$idx][before] = 0;
						
					switch($row[type])
					{
						case "1":
							$balancelist[$idx][type] = "przychód";
							$balancelist[$idx][after] = $balancelist[$idx][before] + $balancelist[$idx][value];
							$balancelist[income] = $balancelist[income] + $balancelist[$idx][value];
						break;

						case "2":
							$balancelist[$idx][type] = "rozchód";
							$balancelist[$idx][after] = $balancelist[$idx][before] - $balancelist[$idx][value];
							$balancelist[expense] = $balancelist[expense] + $balancelist[$idx][value];
						break;

						case "3":
							$balancelist[$idx][type] = "wp³ata u¿";
							$balancelist[$idx][after] = $balancelist[$idx][before] + $balancelist[$idx][value];
							$balancelist[incomeu] = $balancelist[incomeu] + $balancelist[$idx][value];
						break;
						case "4":
							$balancelist[$idx][type] = "obci±¿enie u¿";
							$balancelist[$idx][after] = $balancelist[$idx][before];
							$balancelist[uinvoice] = $balancelist[uinvoice] + $balancelist[$idx][value];
						break;
						default:
							$balancelist[$idx][type] = '<FONT COLOR="RED">???</FONT>';
							$balancelist[$idx][after] = $balancelist[$idx][before];
						break;
					}
					
				}
				
				$balancelist[total] = $balancelist[$idx][after];

				$_SESSION[timestamps][getbalancelist][cash] = $this->GetTS("cash");
				$_SESSION[timestamps][getbalancelist][admins] = $this->GetTS("admins");
				$_SESSION[timestamps][getbalancelist][users] = $this->GetTS("users");
				$_SESSION[cache][getbalancelist] = $balancelist;
			}
				
		}else{
			$balancelist = $_SESSION[cache][getbalancelist];	
		}

		return $balancelist;
	}

	function SearchUserList($order=NULL,$state=NULL,$search=NULL)
	{
	
		list($order,$direction)=explode(",",$order);

		if($direction != "desc")
			$direction = "asc";
		else
			$direction = "desc";
		
		switch($order){
			
			case "phone":
				$sqlord = "ORDER BY phone1";
			break;
			
			case "id":
				$sqlord = "ORDER BY id";
			break;
			
			case "address":
				$sqlord = "ORDER BY address";
			break;
			
			case "email":
				$sqlord = "ORDER BY email";
			break;
			
			case "balance":
				$sqlord = "";
			break;
			
			default:
				$sqlord = "ORDER BY lastname, name";
			break;
		}

		$like = $this->sqlLIKE();

		if(sizeof($search))
		foreach($search as $key => $value)
		{
			$value = str_replace(" ","%",trim($value));
			if($value!="")
			{
				$value = "'%".$value."%'";
				if($key=="phone")
					$searchargs[] = "(phone1 $like $value OR phone2 $like $value OR phone3 $like $value)";
				elseif($key=="username")
					$searchargs[] = $this->ADB->Concat("UPPER(lastname)","' '","name")." $like ".$value;
				elseif($key!="s")
					$searchargs[] = $key." $like ".$value;
			}
		}
		
		if($searchargs)
			$sqlsarg = implode(" AND ",$searchargs);

		if(!isset($state))
			$state = 3;

		if($userlist = $this->ADB->GetAll("SELECT id, ".$this->ADB->Concat("UPPER(lastname)","' '","name")." AS username, status, email, phone1, address, info, tariff FROM users WHERE 1=1 ".($state !=0 ? " AND status = '".$state."'":"").($sqlsarg !="" ? " AND ".$sqlsarg :"")." ".($sqlord !="" ? $sqlord." ".$direction:"" )))
		{
			if($blst = $this->ADB->GetAll("SELECT userid AS id, SUM(value) AS value FROM cash WHERE type='3' GROUP BY userid"))
				foreach($blst as $row)
					$balance[$row[id]] = str_replace(".",",",$row[value]);

			if($blst = $this->ADB->GetAll("SELECT userid AS id, SUM(value) AS value FROM cash WHERE type='4' GROUP BY userid"))
				foreach($blst as $row)
					$balance[$row[id]] = $balance[$row[id]] - str_replace(".",",",$row[value]);
			
			foreach($this->ADB->GetAll("SELECT id, value FROM tariffs") as $key => $row)
				$tlist[$value[id]] = $row[value];
			
			foreach($userlist as $key => $value)
			{
				$userlist[$key][balance] = $balance[$value[id]];
				if($balance[$value[id]] < 0)
					$below = $below + $balance[$value[id]];
				if($balance[$value[id]] > 0)
					$over = $over + $balance[$value[id]];
				
				$userlist[$key][tariffvalue] = str_replace(".",",",$tlist[$value[tariff]]);
				$userlist[$key][nodeac] = $this->GetUserNodesAC($value[id]);
			}
			
			if($order == "balance")
			{
				foreach($userlist as $key => $value)
				{
					$blst[key][] = $key;
					$blst[value][] = $value[balance];
				}
				
				if($direction=="desc")
					array_multisort($blst[value],SORT_NUMERIC,SORT_DESC,$blst[key]);
				else
					array_multisort($blst[value],SORT_NUMERIC,SORT_ASC,$blst[key]);

				foreach($blst[key] as $value)
				{
					$nuserlist[] = $userlist[$value];
				}

				$userlist = $nuserlist;
			}
			
			$userlist[total]=sizeof($userlist);
			$userlist[state]=$state;
			$userlist[order]=$order;
			$userlist[below]=$below;
			$userlist[over]=$over;
			$userlist[direction]=$direction;
		}
		return $userlist;
	}
			
	function GetUserList($order="username,asc",$state=NULL)
	{
	
		list($order,$direction)=explode(",",$order);

		if($direction != "desc")
			$direction = "asc";
		else
			$direction = "desc";
		
		switch($order){
			
			case "phone":
				$sqlord = "ORDER BY phone1";
			break;
			
			case "id":
				$sqlord = "ORDER BY id";
			break;
			
			case "address":
				$sqlord = "ORDER BY address";
			break;
			
			case "email":
				$sqlord = "ORDER BY email";
			break;
			
			case "balance":
				$sqlord = "";
			break;
			
			default:
				$sqlord = "ORDER BY lastname, name";
			break;
		}
		
		if(!isset($state))
			$state = 3;

		if($userlist = $this->ADB->GetAll("SELECT id, ".$this->ADB->Concat("UPPER(lastname)","' '","name")." AS username, status, email, phone1, address, info, tariff FROM users WHERE 1=1 ".($state !=0 ? " AND status = '".$state."'":"")." ".($sqlord !="" ? $sqlord." ".$direction:"" )))
		{
			if($blst = $this->ADB->GetAll("SELECT userid AS id, SUM(value) AS value FROM cash WHERE type='3' GROUP BY userid"))
				foreach($blst as $row)
					$balance[$row[id]] = str_replace(".",",",$row[value]);

			if($blst = $this->ADB->GetAll("SELECT userid AS id, SUM(value) AS value FROM cash WHERE type='4' GROUP BY userid"))
				foreach($blst as $row)
					$balance[$row[id]] = $balance[$row[id]] - str_replace(".",",",$row[value]);
			
			foreach($this->ADB->GetAll("SELECT id, value FROM tariffs") as $key => $row)
				$tlist[$value[id]] = $row[value];
			
			foreach($userlist as $key => $value)
			{
				$userlist[$key][balance] = $balance[$value[id]];
				if($balance[$value[id]] < 0)
					$below = $below + $balance[$value[id]];
				if($balance[$value[id]] > 0)
					$over = $over + $balance[$value[id]];
				
				$userlist[$key][tariffvalue] = str_replace(".",",",$tlist[$value[tariff]]);
				$userlist[$key][nodeac] = $this->GetUserNodesAC($value[id]);
			}
			
			if($order == "balance")
			{
				foreach($userlist as $key => $value)
				{
					$blst[key][] = $key;
					$blst[value][] = $value[balance];
				}
				
				if($direction=="desc")
					array_multisort($blst[value],SORT_NUMERIC,SORT_DESC,$blst[key]);
				else
					array_multisort($blst[value],SORT_NUMERIC,SORT_ASC,$blst[key]);

				foreach($blst[key] as $value)
				{
					$nuserlist[] = $userlist[$value];
				}

				$userlist = $nuserlist;
			}
			
		}
		
		$userlist[total]=sizeof($userlist);
		$userlist[state]=$state;
		$userlist[order]=$order;
		$userlist[below]=$below;
		$userlist[over]=$over;
		$userlist[direction]=$direction;
				
		return $userlist;
	}
			
	function GetUserNodes($id)
	{
		if($return = $this->ADB->GetAll("SELECT id, name, mac, ipaddr, access FROM nodes WHERE ownerid=?",array($id))){
			$return[total] = sizeof($return);
			$return[ownerid] = $id;
		}
		return $return;
	}

	function GetNodeList($order="name,asc")
	{

		if($order=="")
			$order="name,asc";

		list($order,$direction) = explode(",",$order);

		if($direction=="desc")
			$direction = "DESC";
		else
			$direction = "ASC";

		switch($order)
		{
			case "name":
				$sqlord = " ORDER BY name";
			break;

			case "id":
				$sqlord = " ORDER BY id";
			break;

			case "mac":
				$sqlord = " ORDER BY mac";
			break;
		}

		if($username = $this->ADB->GetAll("SELECT id, ".$this->ADB->Concat("UPPER(lastname)","' '","name")." AS username FROM users"))
			foreach($username as $idx => $row)
				$usernames[$row[id]] = $row[username];

		if($nodelist = $this->ADB->GetAll("SELECT id, ipaddr, mac, name, ownerid, access FROM nodes ".($sqlord != "" ? $sqlord." ".$direction : "")))
		{
			foreach($nodelist as $idx => $row)
			{
				$nodelist[$idx][iplong] = ip_long($row[ipaddr]);
				$nodelist[$idx][owner] = $usernames[$row[ownerid]];
				if($row[access]=="Y")
					$totalon++;
				else
					$totaloff++;
			}			
		}

		switch($order)
		{
			case "ip":
				foreach($nodelist as $idx => $row)
				{
					$iptable[idx][] = $idx;
					$iptable[iplong][] = $row[iplong];
				}
				array_multisort($iptable[iplong],($direction == "DESC" ? SORT_DESC : SORT_ASC),SORT_NUMERIC,$iptable[idx]);
				foreach($iptable[idx] as $idx)
					$nnodelist[] = $nodelist[$idx];
				$nodelist = $nnodelist;
			break;

			case "owner":
				foreach($nodelist as $idx => $row)
				{
					$ownertable[idx][] = $idx;
					$ownertable[owner][] = $row[owner];
				}
				array_multisort($ownertable[owner],($direction == "DESC" ? SORT_DESC : SORT_ASC),$ownertable[idx]);
				foreach($ownertable[idx] as $idx)
					$nnodelist[] = $nodelist[$idx];
				$nodelist = $nnodelist;
			break;
		}

		$nodelist[total] = sizeof($nodelist);
		$nodelist[order] = $order;
		$nodelist[direction] = $direction;
		$nodelist[totalon] = $totalon;
		$nodelist[totaloff] = $totaloff;

		return $nodelist;
	}

	function DatabaseList()
	{
		if ($handle = opendir($this->CONFIG[backup_dir]))
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
							$dblist[time][] = substr(basename("$file",".sql"),4);
							$dblist[size][] = filesize($this->CONFIG[backup_dir]."/".$file);
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
		if(file_exists($this->CONFIG[backup_dir].'/lms-'.$dbtime.'.sql'))
		{
			return $this->DBLoad($this->CONFIG[backup_dir].'/lms-'.$dbtime.'.sql');
		}
		else
			return FALSE;
	}

	function DBLoad($filename=NULL)
	{
		if(!$filename)
			return FALSE;
		$file = fopen($filename,"r");
		$this->ADB->BeginTrans();
		
		while(!feof($file))
		{
			$line = fgets($file,4096);
			if($line!="")
			{
				$line=str_replace(";\n","",$line);
				$this->ADB->Execute($line);
			}
		}
		$this->ADB->CommitTrans();		
		fclose($file);

		// Okej, zróbmy parê bzdurek db depend :S

		switch($this->ADB->databaseType)
		{
			case "postgres":
				// update sequencers :S
				foreach($this->ADB->MetaTables() as $tablename)
					$this->ADB->Execute("SELECT setval('".$tablename."_id_seq',max(id)) FROM ".$tablename);
			break;
		}
	}						

	function DBDump($filename=NULL)
	{
		if(!$filename)
			return FALSE;
		if($dumpfile = fopen($filename,"w"))
		{
			foreach($this->ADB->MetaTables() as $tablename)
			{
				fputs($dumpfile,"DELETE FROM $tablename;\n");
				foreach($this->ADB->GetAll("SELECT * FROM ".$tablename) as $row)
				{
					fputs($dumpfile,"INSERT INTO $tablename (");
					foreach($row as $field => $value)
					{
						$fields[] = $field;
						$values[] = "'".addcslashes($value,"\r\n\'\"\\")."'";
					}
					fputs($dumpfile,implode(", ",$fields));
					fputs($dumpfile,") VALUES (");
					fputs($dumpfile,implode(", ",$values));
					fputs($dumpfile,");\n");
					unset($fields);
					unset($values);
				}
			}
			fclose($dumpfile);
		}
		else
			return FALSE;
	}
			
	function DatabaseCreate()
	{
	
		return $this->DBDump($this->CONFIG[backup_dir].'/lms-'.time().'.sql');
	}

	function DatabaseDelete($dbtime)
	{
		if(file_exists($this->CONFIG[backup_dir].'/lms-'.$dbtime.'.sql'))
		{
			return unlink($this->CONFIG[backup_dir].'/lms-'.$dbtime.'.sql');
		}
		else
			return FALSE;
	}

	function DatabaseFetchContent($dbtime)
	{
		if(file_exists($this->CONFIG[backup_dir].'/lms-'.$dbtime.'.sql'))
		{
			$content = file($this->CONFIG[backup_dir].'/lms-'.$dbtime.'.sql');
			foreach($content as $value)
				$database[content] .= $value;
			$database[size] = filesize($this->CONFIG[backup_dir].'/lms-'.$dbtime.'.sql');
			$database[time] = $dbtime;
			return $database;
		}
		else
			return FALSE;
	}
		
	function NodeSet($id)
	{
		$this->SetTS("nodes");
		if($this->ADB->GetOne("SELECT access FROM nodes WHERE id=?",array($id))=="Y")
			return $this->ADB->Execute("UPDATE nodes SET access='N' WHERE id=?",array($id));
		else
			return $this->ADB->Execute("UPDATE nodes SET access='Y' WHERE id=?",array($id));
	}

	function NodeSetU($id,$access=FALSE)
	{
		$this->SetTS("nodes");
		if($access)
			return $this->ADB->Execute("UPDATE nodes SET access=? WHERE ownerid=?",array("Y",$id));
		else
			return $this->ADB->Execute("UPDATE nodes SET access=? WHERE ownerid=?",array("N",$id));
	}

	function GetOwner($id)
	{
		return $this->ADB->GetOne("SELECT ownerid FROM nodes WHERE id=?",array($id));
	}

	function GetUserBalance($id)
	{
		$bin = $this->ADB->GetOne("SELECT SUM(value) FROM cash WHERE userid=? AND type='3'",array($id));
		$bab = $this->ADB->GetOne("SELECT SUM(value) FROM cash WHERE userid=? AND type='4'",array($id));
		return round(str_replace(".",",",$bin) - str_replace(".",",",$bab),2);
	}

	function GetUserBalanceList($id)
	{

	
		if($_SESSION[timestamps][getuserbalancelist][$id][cash] != $this->GetTS("cash") || 
		$_SESSION[timestamps][getuserbalancelist][$id][admins] != $this->GetTS("admins")) 
		{

			// wrapper do starego formatu
		
			if($talist = $this->ADB->GetAll("SELECT id, name FROM admins"))
				foreach($talist as $idx => $row)
					$adminslist[$row[id]] = $row[name];

			// wrapper do starego formatu

			if($tslist = $this->ADB->GetAll("SELECT id, time, adminid, type, value, userid, comment FROM cash WHERE userid=?",array($id)))
				foreach($tslist as $row)
					foreach($row as $column => $value)
						$saldolist[$column][] = $value;
					
				
			if(sizeof($saldolist[id]) > 0){
				foreach($saldolist[id] as $i => $v)
				{
					if($i>0) $saldolist[before][$i] = $saldolist[after][$i-1];
					else $saldolist[before][$i] = 0;
				
					$saldolist[adminname][$i] = $adminslist[$saldolist[adminid][$i]];
					// zachcia³o mi siê kurwa pierdolonych locales :S
					// zak³adam siê ¿e w trakcie pisania wyjdzie jeszcze kwiatek
					// z zapisem do mysql'a :S
					$saldolist[value][$i]=str_replace(".",",",$saldolist[value][$i]);
					$saldolist[value][$i]=round($saldolist[value][$i],3);	
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
			$_SESSION[timestamps][getuserbalancelist][$id][admins] = $this->GetTS("admins");
			$_SESSION[cache][getuserbalancelist][$id] = $saldolist;
		}else{
			$saldolist = $_SESSION[cache][getuserbalancelist][$id];
		}

		$saldolist[userid] = $id;
		return $saldolist;

	}

	function GetTariffs()
	{
		if($ttlist = $this->ADB->GetAll("SELECT id, name, value, uprate, downrate FROM tariffs ORDER BY value DESC"))
			foreach($ttlist as $row)
				foreach($row as $column => $value)
					$tarifflist[$column][] = $value;
		return $tarifflist;
	}

	function GetUserName($id)
	{
		return $this->ADB->GetOne("SELECT ".$this->ADB->Concat("UPPER(lastname)","' '","name")." FROM users WHERE id=?",array($id));
	}

	function NodeAdd($nodedata)
	{
		$this->SetTS("nodes");

		if($this->ADB->Execute("INSERT INTO nodes (name, mac, ipaddr, ownerid, creatorid, creationdate) VALUES (?, ?, ?, ?, ?, ".$this->sqlTSfmt().")",array(strtoupper($nodedata[name]),strtoupper($nodedata[mac]),$nodedata[ipaddr],$nodedata[ownerid],$this->SESSION->id)))
			return $this->ADB->GetOne("SELECT MAX(id) FROM nodes");
		else
			return FALSE;
	}

	function UserAdd($useradd)
	{
		$this->SetTS("users");
		
		if($this->ADB->Execute("INSERT INTO users (name, lastname, phone1, phone2, phone3, address, email, status, tariff, creationdate, creatorid, info) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ".$this->sqlTSfmt().", ?, ?)",array(ucwords($useradd[name]), strtoupper($useradd[lastname]), $useradd[phone1], $useradd[phone2], $useradd[phone3], $useradd[address], $useradd[email], $useradd[status], $useradd[tariff], $this->SESSION->id, $useradd[info])))
			return $this->ADB->GetOne("SELECT MAX(id) FROM users");
		else
			return FALSE;
	}

	function GetUserEmail($id)
	{
		return $this->ADB->GetOne("SELECT email FROM users WHERE id=?",array($id));
	}

	function UserExists($id)
	{
		return ($this->ADB->GetOne("SELECT * FROM users WHERE id=?",array($id))?TRUE:FALSE);
	}

	function NetworkExists($id)
	{
		return ($this->ADB->GetOne("SELECT * FROM networks WHERE id=?",array($id))?TRUE:FALSE);
	}	

	function TariffExists($id)
	{
		return ($this->ADB->GetOne("SELECT * FROM tariffs WHERE id=?",array($id))?TRUE:FALSE);
	}


	function IsIPFree($ip)
	{
		return !($this->ADB->GetOne("SELECT * FROM nodes WHERE ipaddr=?",array($ip))?TRUE:FALSE);
	}

	function NodeExists($id)
	{
		return ($this->ADB->GetOne("SELECT * FROM nodes WHERE id=?",array($id))?TRUE:FALSE);
	}

	function AddBalance($addbalance)
	{
		$this->SetTS("cash");
		return $this->ADB->Execute("INSERT INTO cash (time, adminid, type, value, userid, comment) VALUES (".$this->sqlTSfmt().", ?, ?, ?, ?, ?)",array($this->SESSION->id, $addbalance[type], $addbalance[value], $addbalance[userid], $addbalance[comment]));	
	}

	function GetEmails($group)
	{
		return $this->ADB->GetAll("SELECT email, ".$this->ADB->Concat("lastname", "' '", "name")." AS username FROM users WHERE 1=1 ".($group !=0 ? " AND status='".$group."'" : "")." AND email != ''");
	}

	function Mailing($mailing)
	{
		$SESSION=$this->SESSION;
		$emails = $this->GetEmails($mailing[group]);

		if($emails = $this->GetEmails($mailing[group]))
		{
			if($this->CONFIG[debug_email])
				echo "<B>Uwaga! Tryb debug (u¿ywam adresu ".$this->CONFIG[debug_email]."</B><BR>";
				
			foreach($emails as $key => $row)
			{
				if($this->CONFIG[debug_email])
					$row[email] = $this->CONFIG[debug_email];

				mail (
					$row[username]." <".$row[email].">",
					$mailing[subject],
					$mailing[body],
					"From: ".$mailing[sender]." <".$mailing[from].">\r\n"."Content-type: text/plain; charset=\"iso-8858-2\"\r\n"."X-Mailer: LMS-".$this->_version."/PHP-".phpversion()."\r\n"."X-Remote-IP: ".$_SERVER['REMOTE_ADDR']."\r\n"."X-HTTP-User-Agent: ".$_SERVER['HTTP_USER_AGENT']."\r\n"
				);

				echo "<img src=\"img/mail.gif\" border=\"0\" align=\"absmiddle\" alt=\"\"> ".($key+1)." z ".sizeof($emails)." (".sprintf("%02.2f",round((100/sizeof($emails))*($key+1),2))."%): ".$row[username]." &lt;".$row[email]."&gt;<BR>\n";
				flush();

			}
		}
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
		if($netadd[prefix] != "")
			$netadd[mask] = prefix2mask($netadd[prefix]);
		$this->SetTS("networks");
		if($this->ADB->Execute("INSERT INTO networks (name, address, mask, gateway, dns, domain, wins, dhcpstart, dhcpend) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",array(strtoupper($netadd[name]),$netadd[address],$netadd[mask],$netadd[gateway],$netadd[dns],$netadd[domain],$netadd[wins],$netadd[dhcpstart],$netadd[dhcpend])))
			return $this->ADB->GetOne("SELECT id FROM networks WHERE address=?",array($netadd[address]));
		else
			return FALSE;
	}

	function NetworkDelete($id)
	{
		$this->SetTS("networks");
		return $this->ADB->Execute("DELETE FROM networks WHERE id=?",array($id));
	}

	function GetAdminList()
	{
		if($adminslist = $this->ADB->GetAll("SELECT id, login, name, lastlogindate, lastloginip FROM admins ORDER BY login ASC"))
		{
			foreach($adminslist as $idx => $row)
			{
				if($row[lastlogindate])
					$adminslist[$idx][lastlogin] = date("Y/m/d H:i",$row[lastlogindate]);
				else
					$adminslist[$idx][lastlogin] = "-";

				if(check_ip($row[lastloginip]))
					$adminslist[$idx][lastloginhost] = gethostbyaddr($row[lastloginip]);
				else
				{
					$adminslist[$idx][lastloginhost] = "-";
					$adminslist[$idx][lastloginip] = "-";
				}
			}
		}
		
		$adminslist[total] = sizeof($adminslist);		
		return $adminslist;
	}

	function GetAdminIDByLogin($login)
	{
		return $this->ADB->GetOne("SELECT id FROM admins WHERE login=?",array($login));
	}

	function AdminAdd($adminadd)
	{
		$this->SetTS("admins");
		if($this->ADB->Execute("INSERT INTO admins (login, name, passwd) VALUES (?, ?, ?)",array($adminadd[login], $adminadd[name], crypt($adminadd[password]))))
			return $this->ADB->GetOne("SELECT id FROM admins WHERE login=?",array($adminadd[login]));
		else
			return FALSE;
	}

	function AdminDelete($id)
	{
		return $this->ADB->Execute("DELETE FROM admins WHERE id=?",array($id));
	}
	
	function AdminExists($id)
	{
		return ($this->ADB->GetOne("SELECT * FROM admins WHERE id=?",array($id))?TRUE:FALSE);
	}

	function GetNodeOwner($id)
	{
		return $this->ADB->GetOne("SELECT ownerid FROM nodes WHERE id=?",array($id));
	}

	function NodeUpdate($nodedata)
	{
		$this->SetTS("nodes");
		return $this->ADB->Execute("UPDATE nodes SET name=?, ipaddr=?, mac=?, moddate=".$this->sqlTSfmt().", modid=?, access=?, ownerid=? WHERE id=?",array(strtoupper($nodedata[name]), $nodedata[ipaddr], $nodedata[mac], $this->SESSION->id, $nodedata[access], $nodedata[ownerid], $nodedata[id]));
	}

	function GetUsersWithTariff($id)
	{
		return $this->ADB->GetOne("SELECT COUNT(id) FROM users WHERE tariff=? AND status=3",array($id));
	}
	
	function GetTariffList()
	{
		// wrapper do starego formatu
		if($ttlist = $this->ADB->GetAll("SELECT id, name, value, description, uprate, downrate FROM tariffs ORDER BY value DESC"))
		{
			foreach($ttlist as $idx => $row)
			{
				foreach($row as $column => $value)
					$tarifflist[$column][] = $value;

			}

			foreach($tarifflist[id] as $idx => $id)
			{
				$tarifflist[users][$idx] = $this->GetUsersWithTariff($id);
				$tarifflist[value][$idx] = str_replace(".",",",$tarifflist[value][$idx]);
				$tarifflist[totalusers] = $tarifflist[totalusers] + $tarifflist[users][$idx];
				$tarifflist[income][$idx] = $tarifflist[users][$idx] * $tarifflist[value][$idx];
				$tarifflist[totalincome] = $tarifflist[totalincome] + $tarifflist[income][$idx];
			}

		}

		$tarifflist[total] = sizeof($ttlist);
		return $tarifflist;
				
	}

	function GetAdminInfo($id)
	{
		if($admininfo = $this->ADB->GetRow("SELECT id, login, name, email, lastlogindate, lastloginip, failedlogindate, failedloginip FROM admins WHERE id=?",array($id)))
		{
			if($admininfo[lastlogindate])
				$admininfo[lastlogin] = date("Y/m/d H:i",$admininfo[lastlogindate]);
			else
				$admininfo[lastlogin] = "-";

			if($admininfo[failedlogindate])
				$admininfo[faillogin] = date("Y/m/d H:i",$admininfo[failedlogindate]);
			else
				$admininfo[faillogin] = "-";


			if(check_ip($admininfo[lastloginip]))
				$admininfo[lastloginhost] = gethostbyaddr($admininfo[lastloginip]);
			else
			{
				$admininfo[lastloginhost] = "-";
				$admininfo[lastloginip] = "-";
			}

			if(check_ip($admininfo[failedloginip]))
				$admininfo[failloginhost] = gethostbyaddr($admininfo[failedloginip]);
			else
			{
				$admininfo[failloginhost] = "-";
				$admininfo[failloginip] = "-";
			}
		}
		return $admininfo;
	}

	function AdminUpdate($admininfo)
	{
		$this->SetTS("admins");
		return $this->ADB->Execute("
				UPDATE admins SET login=?, name=?, email=? WHERE id=?",array($admininfo[login],$admininfo[name],$admininfo[email],$admininfo[id]));
	}

	function GetTariffIDByName($name)
	{
		return $this->ADB->GetOne("SELECT id FROM tariffs WHERE name=?",array($name));
	}

	function TariffAdd($tariffdata)
	{
		$this->SetTS("tariffs");
		if($this->ADB->Execute("INSERT INTO tariffs (name, description, value, uprate, downrate)
			VALUES (?, ?, ?, ?, ?)",
			array(
				$tariffdata[name],
				$tariffdata[description],
				$tariffdata[value],
				$tariffdata[uprate],
				$tariffdata[downrate]
			)
		))
			return $this->ADB->GetOne("SELECT id FROM tariffs WHERE name=?",array($tariffdata[name]));
		else
			return FALSE;
	}
	
	function TariffDelete($id)
	{
		if(!$this->GetUsersWithTariff($id))
			return $this->ADB->Execute("DELETE FROM tariffs WHERE id=?",array($id));
		else
			return FALSE;
	}
}
?>
