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

	function LMS($db){
		$this->db=$db;
	}

	function DeleteUser($id){
		$db=$this->db;
		$db->ExecSQL("DELETE FROM `nodes` WHERE `ownerid` = '".$id."'");
		return $db->ExecSQL("DELETE FROM `users` WHERE `id` = '".$id."' LIMIT 1");
	}

	function GetNodeArp($id){
		$db=$this->db;
		return $db->SExecSQL("SELECT `mac` FROM `nodes` WHERE `id` = '".$id."' LIMIT 1");
	}

	function GetUserName($id){
		$db=$this->db;
		$db->ExecSQL("SELECT `lastname`, `name` FROM `users` WHERE `id` = '".$id."' LIMIT 1");
		$db->FetchRow();
		return strtoupper($db->row[0])." ".($db->row[1]);
	}
	function UserExists($id){
		$db=$this->db;
		return $db->CountRows("SELECT * FROM `users` WHERE `id` = '".$id."' LIMIT 1");
	}
}

?>
