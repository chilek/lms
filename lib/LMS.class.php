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

	function LMS($db)
	{
		$this->db=$db;
	}

	function DeleteUser($id)
	{
		$db=$this->db;
		$db->ExecSQL("DELETE FROM `nodes` WHERE `ownerid` = '".$id."'");
		return $db->ExecSQL("DELETE FROM `users` WHERE `id` = '".$id."' LIMIT 1");
	}

	function GetAdminName($id)
	{
		$db=$this->db;
		$db->ExecSQL("SELECT `name` FROM `admins` WHERE `id` = '".$id."' LIMIT 1");
		$db->FetchRow();
		return $db->row[0];
	}
	
	function GetNodeArp($id)
	{
		$db=$this->db;
		return $db->SExecSQL("SELECT `mac` FROM `nodes` WHERE `id` = '".$id."' LIMIT 1");
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

	function GetUserRecord($id)
	{
		$db=$this->db;
		$db->ExecSQL("SELECT * FROM `users` WHERE `id` = '".$id."' LIMIT 1");
		$db->FetchRow();
		list($return[id],$return[lastname],$return[name],$return[status],$return[email],$return[phone1],$return[phone2],$return[phone3],$return[address],$return[tariff],$return[info],$return[creationdate],$return[moddate],$return[creatorid],$return[modid])=$db->row;
		$return[username] = strtoupper($return[lastname])." ".($return[name]);
		$return[createdby] = $this->GetAdminName($return[creatorid]);
		$return[modifiedby] = $this->GetAdminName($return[modid]);
		$return[creationdateh] = date("Y-m-d, H:i",$return[creationdate]);
		$return[moddateh] = date("Y-m-d, H:i",$return[moddate]);
		$return[tariffvalue] = $this->GetTariffValue($return[tariff]);
		$return[tariffname] = $this->GetTariffName($return[tariff]);
		$return[balance] = $this->GetUserBalance($return[id]);
		return $return;
	}
	
	function GetUserName($id)
	{
		$db=$this->db;
		$db->ExecSQL("SELECT `lastname`, `name` FROM `users` WHERE `id` = '".$id."' LIMIT 1");
		$db->FetchRow();
		return strtoupper($db->row[0])." ".($db->row[1]);
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
		
	function GetUserAddress($id)
	{
		$db=$this->db;
		$db->ExecSQL("SELECT `address` FROM `users` WHERE `id` = '".$id."' LIMIT 1");
		$db->Fetchrow();
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
}

?>
