<?php

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

// LMS Class - contains internal LMS database functions used
// to fetch data like usernames, searching for mac's by ID, etc..

class LMS 
{

	var $ADB;		// obiekt ADOdb
	var $SESSION;		// obiekt z Session.class.php (zarz±dzanie sesj±)
	var $CONFIG;		// tablica zawieraj±ca zmienne z lms.ini
	var $_version = NULL;	// wersja klasy

	function LMS($ADB,$SESSION) // ustawia zmienne klasy
	{
		$this->_version = eregi_replace('^.Revision: ([0-9.]+).*','\1','$Revision$');
		$this->SESSION=$SESSION;
		$this->ADB=$ADB;
	}
	
	/*
	 *  Funkcje bazodanowe (backupy, timestampy)
	 */

	function SetTS($table) // ustawia timestamp tabeli w tabeli 'timestamps'
	{
		if($this->ADB->GetOne('SELECT * FROM timestamps WHERE tablename=?',array($table)))
			$this->ADB->Execute('UPDATE timestamps SET time = ?NOW? WHERE tablename=?',array($table));
		else
			$this->ADB->Execute('INSERT INTO timestamps (tablename, time) VALUES (?, ?NOW?)',array($table));

		if($this->ADB->GetOne('SELECT * FROM timestamps WHERE tablename=?',array('_global')))
			$this->ADB->Execute('UPDATE timestamps SET time = ?NOW? WHERE tablename=?',array('_global'));
		else
			$this->ADB->Execute('INSERT INTO timestamps (tablename, time) VALUES (?, ?NOW?)',array('_global'));
	}

	function GetTS($table) // zwraca timestamp tabeli zapisany w tabeli 'timestamps'
	{
		return $this->ADB->GetOne("SELECT time FROM timestamps WHERE tablename=?",array($table));
	}

	function DeleteTS($table) // usuwa timestamp tabeli zapisany w tabeli 'timestamps'
	{
		return $this->ADB->Execute("DELETE FROM timestamps WHERE tablename=?",array($table));
	}
	
	function DatabaseList() // zwraca listê kopii baz danych w katalogu z backupami
	{
		if ($handle = opendir($this->CONFIG['backup_dir']))
		{
			while (false !== ($file = readdir($handle)))
			{
				if ($file != "." && $file != "..")
				{
					$path = pathinfo($file);
					if($path['extension'] = "sql")
					{
						if(substr($path['basename'],0,4)=="lms-")
						{
							$dblist['time'][] = substr(basename("$file",".sql"),4);
							$dblist['size'][] = filesize($this->CONFIG['backup_dir']."/".$file);
						}
					}
				}
			}
			closedir($handle);
		}
		if(sizeof($dblist['time']))
			array_multisort($dblist['time'],$dblist['size']);
		$dblist['total'] = sizeof($dblist['time']);
		return $dblist;
	}		

	function DatabaseRecover($dbtime) // wczytuje backup bazy danych o podanym timestampie
	{
		if(file_exists($this->CONFIG['backup_dir'].'/lms-'.$dbtime.'.sql'))
		{
			return $this->DBLoad($this->CONFIG['backup_dir'].'/lms-'.$dbtime.'.sql');
		}
		else
			return FALSE;
	}

	function DBLoad($filename=NULL) // wczytuje plik z backupem bazy danych
	{
		if(!$filename)
			return FALSE;
		$file = fopen($filename,"r");
		$this->ADB->BeginTrans(); // przyspieszmy dzia³anie je¿eli baza danych obs³uguje transakcje
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
		// Postgres sux ! (warden)
		// Tak, a ³y¿ka na to 'niemo¿liwe' i polecia³a za wann± potr±caj±c bannanem musztardê (lukasz)

		switch($this->ADB->databaseType)
		{
			case "postgres":
				// uaktualnijmy sequencery postgresa
				foreach($this->ADB->ListTables() as $tablename)
					$this->ADB->Execute("SELECT setval('".$tablename."_id_seq',max(id)) FROM ".$tablename);
			break;
		}
	}						

	function DBDump($filename=NULL) // zrzuca bazê danych do pliku
	{
		if(!$filename)
			return FALSE;
		if($dumpfile = fopen($filename,"w"))
		{
			foreach($this->ADB->ListTables() as $tablename)
			{
				fputs($dumpfile,"DELETE FROM $tablename;\n");
				if($dump = $this->ADB->GetAll("SELECT * FROM ".$tablename))
					foreach($dump as $row)
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
			
	function DatabaseCreate() // wykonuje zrzut kopii bazy danych
	{
		return $this->DBDump($this->CONFIG['backup_dir'].'/lms-'.time().'.sql');
	}

	function DatabaseDelete($dbtime) // usuwa plik ze zrzutem
	{
		if(@file_exists($this->CONFIG['backup_dir'].'/lms-'.$dbtime.'.sql'))
		{
			return @unlink($this->CONFIG['backup_dir'].'/lms-'.$dbtime.'.sql');
		}
		else
			return FALSE;
	}

	function DatabaseFetchContent($dbtime) // zwraca zawarto¶æ tekstow± kopii bazy danych
	{
		if(file_exists($this->CONFIG['backup_dir'].'/lms-'.$dbtime.'.sql'))
		{
			$content = file($this->CONFIG['backup_dir'].'/lms-'.$dbtime.'.sql');
			foreach($content as $value)
				$database['content'] .= $value;
			$database['size'] = filesize($this->CONFIG['backup_dir'].'/lms-'.$dbtime.'.sql');
			$database['time'] = $dbtime;
			return $database;
		}
		else
			return FALSE;
	}
	
	/*
	 *  Zarz±dzanie kontami administratorów
	 */
 	
	function SetAdminPassword($id,$passwd) // ustawia has³o admina o id równym $id na $passwd
	{
		$this->SetTS("admins");
		$this->ADB->Execute("UPDATE admins SET passwd=? WHERE id=?",array(crypt($passwd),$id));
	}

	function GetAdminName($id) // zwraca imiê admina
	{
		return $this->ADB->GetOne("SELECT name FROM admins WHERE id=?",array($id));
	}

	function GetAdminList() // zwraca listê administratorów
	{
	    
	    $query = "SELECT id, login, name, lastlogindate, lastloginip FROM admins ORDER BY login ASC";
	    if($adminslist = $this->ADB->GetAll($query))
		{
			foreach($adminslist as $idx => $row)
			{
				if($row['lastlogindate'])
					$adminslist[$idx]['lastlogin'] = date("Y/m/d H:i",$row['lastlogindate']);
				else
					$adminslist[$idx]['lastlogin'] = "-";

				if(check_ip($row['lastloginip']))
					$adminslist[$idx]['lastloginhost'] = gethostbyaddr($row['lastloginip']);
				else
				{
					$adminslist[$idx]['lastloginhost'] = "-";
					$adminslist[$idx]['lastloginip'] = "-";
				}
			}
		}
		
		$adminslist['total'] = sizeof($adminslist);		
		return $adminslist;
	}

	function GetAdminIDByLogin($login) // zwraca id admina na podstawie loginu
	{
		return $this->ADB->GetOne("SELECT id FROM admins WHERE login=?",array($login));
	}

	function AdminAdd($adminadd) // dodaje admina. wymaga tablicy zawieraj±cej dane admina
	{
		$this->SetTS("admins");
		if($this->ADB->Execute("INSERT INTO admins (login, name, email, passwd, rights) VALUES (?, ?, ?, ?, ?)",array($adminadd['login'], $adminadd['name'], $adminadd['email'], crypt($adminadd['password']),$adminadd['rights'])))
			return $this->ADB->GetOne("SELECT id FROM admins WHERE login=?",array($adminadd['login']));
		else
			return FALSE;
	}

	function AdminDelete($id) // usuwa admina o podanym id
	{
		return $this->ADB->Execute("DELETE FROM admins WHERE id=?",array($id));
	}
	
	function AdminExists($id) // zwraca TRUE/FALSE zale¿nie od tego czy admin istnieje czy nie
	{
		return ($this->ADB->GetOne("SELECT * FROM admins WHERE id=?",array($id))?TRUE:FALSE);
	}


	function GetAdminInfo($id) // zwraca pe³ne info o podanym adminie
	{
		if($admininfo = $this->ADB->GetRow("SELECT id, login, name, email, lastlogindate, lastloginip, failedlogindate, failedloginip FROM admins WHERE id=?",array($id)))
		{
			if($admininfo['lastlogindate'])
				$admininfo['lastlogin'] = date("Y/m/d H:i",$admininfo['lastlogindate']);
			else
				$admininfo['lastlogin'] = "-";

			if($admininfo['failedlogindate'])
				$admininfo['faillogin'] = date("Y/m/d H:i",$admininfo['failedlogindate']);
			else
				$admininfo['faillogin'] = "-";


			if(check_ip($admininfo['lastloginip']))
				$admininfo['lastloginhost'] = gethostbyaddr($admininfo['lastloginip']);
			else
			{
				$admininfo['lastloginhost'] = "-";
				$admininfo['lastloginip'] = "-";
			}

			if(check_ip($admininfo['failedloginip']))
				$admininfo['failloginhost'] = gethostbyaddr($admininfo['failedloginip']);
			else
			{
				$admininfo['failloginhost'] = "-";
				$admininfo['failloginip'] = "-";
			}
		}
		return $admininfo;
	}

	function AdminUpdate($admininfo) // uaktualnia rekord admina.
	{
		$this->SetTS("admins");
		return $this->ADB->Execute("UPDATE admins SET login=?, name=?, email=?, rights=? WHERE id=?",array($admininfo['login'],$admininfo['name'],$admininfo['email'],$admininfo['rights'],$admininfo['id']));
	}

	function GetAdminRights($id)
	{
		$mask = $this->ADB->GetOne("SELECT rights FROM admins WHERE id=?",array($id));
		if($mask == "")
			$mask = "1";
		$len = strlen($mask);
		for($cnt=$len; $cnt > 0; $cnt --)
			$bin = sprintf("%04b",hexdec($mask[$cnt-1])).$bin;
		for($cnt=strlen($bin)-1; $cnt >= 0; $cnt --)
			if($bin[$cnt] == "1")
				$result[] = strlen($bin) - $cnt -1;
		return $result;
	}

	/*
	 *  Funkcje do obs³ugi rekordów u¿ytkowników
	 */

	function GetUserName($id)
	{
		return $this->ADB->GetOne("SELECT ".$this->ADB->Concat("UPPER(lastname)","' '","name")." FROM users WHERE id=?",array($id));
	}

	function GetEmails($group)
	{
		return $this->ADB->GetAll("SELECT email, ".$this->ADB->Concat("lastname", "' '", "name")." AS username FROM users WHERE 1=1 ".($group !=0 ? " AND status='".$group."'" : "")." AND email != ''");
	}

	function GetUserEmail($id)
	{
		return $this->ADB->GetOne("SELECT email FROM users WHERE id=?",array($id));
	}

	function UserExists($id)
	{
		$got = $this->ADB->GetOne("SELECT deleted FROM users WHERE id=?",array($id));
		switch($this->ADB->GetOne("SELECT deleted FROM users WHERE id=?",array($id)))
		{
			case '0':
				return TRUE;
				break;
			case '1':
				return -1;
				break;
			case '':
			default:
				return FALSE;
				break;
		}
	}

	function RecoverUser($id)
	{
		return $this->ADB->Execute("UPDATE users SET deleted=0 WHERE id=?",array($id));
	}

	function GetUsersWithTariff($id)
	{
		return $this->ADB->GetOne("SELECT COUNT(id) FROM users WHERE tariff=? AND status=3 AND deleted=0",array($id));
	}

	function UserAdd($useradd)
	{
		$this->SetTS("users");
		
		if($this->ADB->Execute("INSERT INTO users (name, lastname, phone1, phone2, phone3, gguin, address, zip, city, email, nip, status, tariff, creationdate, creatorid, info, payday) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?NOW?, ?, ?, ?)",array(ucwords($useradd['name']), strtoupper($useradd['lastname']), $useradd['phone1'], $useradd['phone2'], $useradd['phone3'], $useradd['gguin'], $useradd['address'], $useradd['zip'], $useradd['city'], $useradd['email'], $useradd['nip'], $useradd['status'], $useradd['tariff'], $this->SESSION->id, $useradd['info'], $useradd['payday'])))
			return $this->ADB->GetOne("SELECT MAX(id) FROM users");
		else
			return FALSE;
	}

	function DeleteUser($id)
	{
		$this->SetTS("users");
		$this->SetTS("nodes");
		$res1=$this->ADB->Execute("DELETE FROM nodes WHERE ownerid=?",array($id));
		$res2=$this->ADB->Execute("UPDATE users SET deleted=1 WHERE id=?",array($id));
		return $res1 || $res2;
	}

	function UserUpdate($userdata)
	{
		$this->SetTS("users");
		return $this->ADB->Execute("UPDATE users SET status=?, phone1=?, phone2=?, phone3=?, address=?, zip=?, city=?, email=?, gguin=?, nip=?, tariff=?, moddate=?NOW?, modid=?, info=?, lastname=?, name=?, payday=?, deleted=0 WHERE id=?", array( $userdata['status'], $userdata['phone1'], $userdata['phone2'], $userdata['phone3'], $userdata['address'], $userdata['zip'], $userdata['city'], $userdata['email'], $userdata['gguin'], $userdata['nip'], $userdata['tariff'], $this->SESSION->id, $userdata['info'], strtoupper($userdata['lastname']), $userdata['name'], $userdata['payday'], $userdata['id'] ) );
	}

	function GetUserNodesNo($id)
	{
		return $this->ADB->GetOne("SELECT COUNT(*) FROM nodes WHERE ownerid=?",array($id));
	}

	function GetUserIDByIP($ipaddr)
	{
		return $this->ADB->GetOne("SELECT ownerid FROM nodes WHERE ipaddr=?",array(ip_long($ipaddr)));
	}

	function GetCashByID($id)
	{
		return $this->ADB->GetRow("SELECT time, adminid, type, value, userid, comment FROM `cash` WHERE id=?",array($id));
	}

	function GetUserStatus($id)
	{
		return $this->ADB->GetOne("SELECT status FROM users WHERE id=?",array($id));
	}

	function GetUser($id)
	{
		if($result = $this->ADB->GetRow("SELECT id, ".$this->ADB->Concat("UPPER(lastname)","' '","name")." AS username, lastname, name, status, email, gguin, phone1, phone2, phone3, address, zip, nip, city, tariff, info, creationdate, moddate, creatorid, modid, payday, deleted FROM users WHERE id=?",array($id)))
		{
			$result['createdby'] = $this->GetAdminName($result['creatorid']);
			$result['modifiedby'] = $this->GetAdminName($result['modid']);
			$result['creationdateh'] = date("Y-m-d, H:i",$result['creationdate']);
			$result['moddateh'] = date("Y-m-d, H:i",$result['moddate']);
			$result['tariffvalue'] = $this->GetTariffValue($result['tariff']);
			$result['tariffname'] = $this->GetTariffName($result['tariff']);
			$result['balance'] = $this->GetUserBalance($result['id']);
			return $result;
		}else
			return FALSE;
	}

	function GetUserNames()
	{
		return $this->ADB->GetAll("SELECT id, ".$this->ADB->Concat("UPPER(lastname)","' '","name")." AS username FROM users WHERE status=3 AND deleted = 0 ORDER BY username");
	}

	function GetUserNodesAC($id)
	{
		if($acl = $this->ADB->GetALL("SELECT access FROM nodes WHERE ownerid=?",array($id)))
		{
			foreach($acl as $value)
				if($value['access'])
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

	function SearchUserList($order=NULL,$state=NULL,$search=NULL)
	{
	
		list($order,$direction)=explode(",",$order);

		($direction != "desc") ? $direction = "asc" : $direction = "desc";
		
		switch($order){
			
			case "phone":
				$sqlord = "ORDER BY deleted ASC, phone1";
			break;
			
			case "id":
				$sqlord = "ORDER BY deleted ASC, id";
			break;
			
			case "address":
				$sqlord = "ORDER BY deleted ASC, address";
			break;
			
			case "email":
				$sqlord = "ORDER BY deleted ASC, email";
			break;
			
			case "balance":
				$sqlord = "";
			break;
			
			case "gg":
				$sqlord = "ORDER BY deleted ASC, gguin";
			break;
			
			case "nip":
				$sqlord = "ORDER BY deleted ASC, nip";
			break;
			
			default:
				$sqlord = "ORDER BY deleted ASC, ".$this->ADB->Concat("UPPER(lastname)","' '","name");
			break;																			
		}

		if(sizeof($search))
			foreach($search as $key => $value)
			{
				$value = str_replace(" ","%",trim($value));
				if($value!="")
				{
					$value = "'%".$value."%'";
					if($key=="phone")
						$searchargs[] = "(phone1 ?LIKE? $value OR phone2 ?LIKE? $value OR phone3 ?LIKE? $value)";
					elseif($key=="username")
						$searchargs[] = $this->ADB->Concat("UPPER(lastname)","' '","name")." ?LIKE? ".$value;
					elseif($key!="s")
						$searchargs[] = $key." ?LIKE? ".$value;
				}
			}
		
		if($searchargs)
			$sqlsarg = implode(" AND ",$searchargs);

		if(!isset($state))
			$state = 3;

		if($userlist = $this->ADB->GetAll("SELECT id, ".$this->ADB->Concat("UPPER(lastname)","' '","name")." AS username, status, email, phone1, address, info, tariff, nip, zip, city, gguin, deleted FROM users WHERE 1=1 ".($state !=0 ? " AND status = '".$state."'":"").($sqlsarg !="" ? " AND ".$sqlsarg :"")." ".($sqlord !="" ? $sqlord." ".$direction:"" )))
		{
			if($blst = $this->ADB->GetAll("SELECT userid AS id, SUM(value) AS value FROM cash WHERE type='3' GROUP BY userid"))
				foreach($blst as $row)
					$balance[$row['id']] = $row['value'];

			if($blst = $this->ADB->GetAll("SELECT userid AS id, SUM(value) AS value FROM cash WHERE type='4' GROUP BY userid"))
				foreach($blst as $row)
					$balance[$row['id']] = $balance[$row['id']] - $row['value'];


			foreach($this->ADB->GetAll("SELECT id, value FROM tariffs") as $key => $row)
				$tlist[$row['id']] = $row['value'];
			
			foreach($userlist as $key => $value)
			{
				$userlist[$key]['balance'] = $balance[$value['id']];
				if($balance[$value['id']] < 0)
					$below = $below + $balance[$value['id']];
				if($balance[$value['id']] > 0)
					$over = $over + $balance[$value['id']];
				
				$userlist[$key]['tariffvalue'] = $tlist[$value['tariff']];
				$userlist[$key]['nodeac'] = $this->GetUserNodesAC($value['id']);
			}
			
			if($order == "balance")
			{
				foreach($userlist as $key => $value)
				{
					$blst['key'][] = $key;
					$blst['value'][] = $value['balance'];
				}
				
				if($direction=="desc")
					array_multisort($blst['value'],SORT_NUMERIC,SORT_DESC,$blst['key']);
				else
					array_multisort($blst['value'],SORT_NUMERIC,SORT_ASC,$blst['key']);

				foreach($blst['key'] as $value)
				{
					$nuserlist[] = $userlist[$value];
				}

				$userlist = $nuserlist;
			}
			
			$userlist['total']=sizeof($userlist);
			$userlist['state']=$state;
			$userlist['order']=$order;
			$userlist['below']=$below;
			$userlist['over']=$over;
			$userlist['direction']=$direction;
		}
		return $userlist;
	}
			
	function GetUserList($order="username,asc",$state=NULL)
	{
	
		list($order,$direction)=explode(",",$order);

		($direction != "desc")  ? $direction = "asc" : $direction = "desc";
		
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

			case "gg":
			$sqlord = "ORDER BY gguin";
			break;

			case "nip":
			$sqlord = "ORDER BY nip";
			break;
			
			default:
				$sqlord = "ORDER BY ".$this->ADB->Concat("UPPER(lastname)","' '","name");
			break;
		}
		
		if(!isset($state))
			$state = 3;

		if($userlist = $this->ADB->GetAll("SELECT id, ".$this->ADB->Concat("UPPER(lastname)","' '","name")." AS username, status, email, phone1, address, gguin, nip, zip, city, info, tariff FROM users WHERE deleted = 0 ".($state !=0 ? " AND status = '".$state."'":"")." ".($sqlord !="" ? $sqlord." ".$direction:"" )))
		{
			if($blst = $this->ADB->GetAll("SELECT userid AS id, SUM(value) AS value FROM cash WHERE type='3' GROUP BY userid"))
				foreach($blst as $row)
					$balance[$row['id']] = $row['value'];

			if($blst = $this->ADB->GetAll("SELECT userid AS id, SUM(value) AS value FROM cash WHERE type='4' GROUP BY userid"))
					foreach($blst as $row)
							$balance[$row['id']] = $balance[$row['id']] - $row['value'];


			foreach($this->ADB->GetAll("SELECT id, value FROM tariffs") as $key => $row)
				$tlist[$row['id']] = $row['value'];

			foreach($userlist as $key => $value)
			{
				$userlist[$key]['balance'] = $balance[$value['id']];
				if($balance[$value['id']] < 0)
					$below = $below + $balance[$value['id']];
				if($balance[$value['id']] > 0)
					$over = $over + $balance[$value['id']];
				
				$userlist[$key]['tariffvalue'] = $tlist[$value['tariff']];
				$userlist[$key]['nodeac'] = $this->GetUserNodesAC($value['id']);
			}
			
			if($order == "balance")
			{
				foreach($userlist as $key => $value)
				{
					$blst['key'][] = $key;
					$blst['value'][] = $value['balance'];
				}
				
				($direction=="desc") ? array_multisort($blst['value'],SORT_NUMERIC,SORT_DESC,$blst['key']) : array_multisort($blst['value'],SORT_NUMERIC,SORT_ASC,$blst['key']);

				foreach($blst['key'] as $value)
				{
					$nuserlist[] = $userlist[$value];
				}

				$userlist = $nuserlist;
			}
			
		}
		
		$userlist['total']=sizeof($userlist);
		$userlist['state']=$state;
		$userlist['order']=$order;
		$userlist['below']=$below;
		$userlist['over']=$over;
		$userlist['direction']=$direction;
				
		return $userlist;
	}
			
	function GetUserNodes($id)
	{
		if($result = $this->ADB->GetAll("SELECT id, name, mac, ipaddr, access FROM nodes WHERE ownerid=? ORDER BY name ASC",array($id))){
			foreach($result as $idx => $row)
				$result[$idx]['ip'] = long2ip($row['ipaddr']);
			$result['total'] = sizeof($result);
			$result['ownerid'] = $id;
		}
		return $result;
	}

	function GetUserBalance($id)
	{
		$bin = $this->ADB->GetOne("SELECT SUM(value) FROM cash WHERE userid=? AND type='3'",array($id));
		$bou = $this->ADB->GetOne("SELECT SUM(value) FROM cash WHERE userid=? AND type='4'",array($id));
		return round($bin-$bou,2);
	}

	function GetUserBalanceList($id)
	{

		// wrapper do starego formatu
	
		if($talist = $this->ADB->GetAll("SELECT id, name FROM admins"))
			foreach($talist as $idx => $row)
				$adminslist[$row['id']] = $row['name'];

		// wrapper do starego formatu

		if($tslist = $this->ADB->GetAll("SELECT id, time, adminid, type, value, userid, comment FROM cash WHERE userid=? ORDER BY time",array($id)))
			foreach($tslist as $row)
				foreach($row as $column => $value)
					$saldolist[$column][] = $value;
					
				
		if(sizeof($saldolist['id']) > 0){
			foreach($saldolist['id'] as $i => $v)
			{
				($i>0) ? $saldolist['before'][$i] = $saldolist['after'][$i-1] : $saldolist['before'][$i] = 0;
			
				$saldolist['adminname'][$i] = $adminslist[$saldolist['adminid'][$i]];
				$saldolist['value'][$i] = round($saldolist['value'][$i],3);	

				(strlen($saldolist['comment'][$i])<3) ? $saldolist['comment'][$i] = $saldolist['name'][$i] : $saldolist['comment'][$i] =  $saldolist['comment'][$i];
	
				switch ($saldolist['type'][$i]){

					case "3":
						$saldolist['after'][$i] = round(($saldolist['before'][$i] + $saldolist['value'][$i]),4);
						$saldolist['name'][$i] = "Wp³ata";
//						$saldolist['comment'][$i] = "Abonament za".date("Y/m",$saldolist['time'][$i]) || $saldolist['comment'][$i];
					break;
						
					case "4":
						$saldolist['after'][$i] = round(($saldolist['before'][$i] - $saldolist['value'][$i]),4);
						$saldolist['name'][$i] = "Obci±¿enie";
					break;
						
				}
					
				$saldolist['date'][$i]=date("Y/m/d H:i",$saldolist['time'][$i]);
				// nie chce mi sie czytac, ale czy to nie jest pare linii wy¿ej ?
				(strlen($saldolist['comment'][$i])<3) ? $saldolist['comment'][$i] = $saldolist['name'][$i] : $saldolist['comment'][$i] =  $saldolist['comment'][$i];
			}
				
			$saldolist['balance'] = $saldolist['after'][sizeof($saldolist['id'])-1];
			$saldolist['total'] = sizeof($saldolist['id']);
			
		}else{
			$saldolist['balance'] = 0;
		}

		if($saldolist['total'])
		{
			foreach($saldolist['value'] as $key => $value)
				$saldolist['value'][$key] = $value;
			foreach($saldolist['after'] as $key => $value)
				$saldolist['after'][$key] = $value;
			foreach($saldolist['before'] as $key => $value)
				$saldolist['before'][$key] = $value;
		}

		$saldolist['userid'] = $id;
		return $saldolist;

	}

	function UserStats()
	{
		$result['total'] = $this->ADB->GetOne("SELECT COUNT(id) FROM users");
		$result['connected'] = $this->ADB->GetOne("SELECT COUNT(id) FROM users WHERE status=3");
		$result['awaiting'] = $this->ADB->GetOne("SELECT COUNT(id) FROM users WHERE status=2");
		$result['interested'] = $this->ADB->GetOne("SELECT COUNT(id) FROM users WHERE status=1");
		$result['debt'] = 0;
		$result['debtvalue'] = 0;
		if($users = $this->ADB->GetAll("SELECT id FROM users"))
			foreach($users as $idx => $row)
			{
				$row['balance'] = $this->GetUserBalance($row['id']);
				if($row['balance'] < 0)
				{
					$result['debt'] ++;
					$result['debtvalue'] -= $row['balance'];
				}
			}
		return $result;
	}

	/*
	 *  Funkcje do obs³ugi rekordów z komputerami
	 */

	function GetNodeOwner($id)
	{
		return $this->ADB->GetOne("SELECT ownerid FROM nodes WHERE id=?",array($id));
	}

	function NodeUpdate($nodedata)
	{
		$this->SetTS("nodes");
		return $this->ADB->Execute("UPDATE nodes SET name=?, ipaddr=?, mac=?, moddate=?NOW?, modid=?, access=?, ownerid=? WHERE id=?",array(strtoupper($nodedata['name']), ip_long($nodedata['ipaddr']), strtoupper($nodedata['mac']), $this->SESSION->id, $nodedata['access'], $nodedata['ownerid'], $nodedata['id']));
	}

	function DeleteNode($id)
	{
		return $this->ADB->Execute("DELETE FROM nodes WHERE id=?",array($id));
	}

	function GetNodeNameByMAC($mac)
	{
		return $this->ADB->GetOne("SELECT name FROM nodes WHERE mac=?",array($mac));
	}		

	function GetNodeIDByIP($ipaddr)
	{
		return $this->ADB->GetOne("SELECT id FROM nodes WHERE ipaddr=?",array(ip_long($ipaddr)));
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
		return long2ip($this->ADB->GetOne("SELECT ipaddr FROM nodes WHERE id=?",array($id)));
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
		return $this->ADB->GetOne("SELECT name FROM nodes WHERE ipaddr=?",array(ip_long($ipaddr)));
		
	}

	function GetNode($id)
	{
		if($result = $this->ADB->GetRow("SELECT id, name, ownerid, ipaddr, mac, access, creationdate, moddate, creatorid, modid FROM nodes WHERE id=?",array($id)))
		{
			$result['ip'] = long2ip($result['ipaddr']);
			$result['createdby'] = $this->GetAdminName($result['creatorid']);
			$result['modifiedby'] = $this->GetAdminName($result['modid']);
			$result['creationdateh'] = date("Y-m-d, H:i",$result['creationdate']);
			$result['moddateh'] = date("Y-m-d, H:i",$result['moddate']);
			$result['owner'] = $this->GetUsername($result['ownerid']);
			$result['netid'] = $this->GetNetIDByIP($result['ip']);
			$result['netname'] = $this->GetNetworkName($result['netid']);
			$result['producer'] = get_producer($result['mac']);
			return $result;
		}else
			return FALSE;
	}
	function GetNodeList($order="name,asc")
	{

		if($order=="")
			$order="name,asc";

		list($order,$direction) = explode(",",$order);

		($direction=="desc") ? $direction = "DESC" : $direction = "ASC";

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

			case "ip":
				$sqlord = " ORDER BY ipaddr";
			break;
		}

		if($username = $this->ADB->GetAll("SELECT id, ".$this->ADB->Concat("UPPER(lastname)","' '","name")." AS username FROM users"))
			foreach($username as $idx => $row)
				$usernames[$row['id']] = $row['username'];

		if($nodelist = $this->ADB->GetAll("SELECT id, ipaddr, mac, name, ownerid, access FROM nodes ".($sqlord != "" ? $sqlord." ".$direction : "")))
		{
			foreach($nodelist as $idx => $row)
			{
				$nodelist[$idx]['ip'] = long2ip($row['ipaddr']);
				$nodelist[$idx]['owner'] = $usernames[$row['ownerid']];
				($row['access']) ? $totalon++ : $totaloff++;
			}			
		}

		switch($order)
		{
			case "owner":
				foreach($nodelist as $idx => $row)
				{
					$ownertable['idx'][] = $idx;
					$ownertable['owner'][] = $row['owner'];
				}
				if(is_array($ownertable))
				{
					array_multisort($ownertable['owner'],($direction == "DESC" ? SORT_DESC : SORT_ASC),$ownertable['idx']);
					foreach($ownertable['idx'] as $idx)
						$nnodelist[] = $nodelist[$idx];
				}
				$nodelist = $nnodelist;
			break;
		}

		$nodelist['total'] = sizeof($nodelist);
		$nodelist['order'] = $order;
		$nodelist['direction'] = $direction;
		$nodelist['totalon'] = $totalon;
		$nodelist['totaloff'] = $totaloff;

		return $nodelist;
	}

	function SearchNodeList($args, $order="name,asc")
	{

		if($order=="")
			$order="name,asc";

		list($order,$direction) = explode(",",$order);

		($direction=="desc") ? $direction = "DESC" : $direction = "ASC";

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

			case "ip":
				$sqlord = " ORDER BY ipaddr";
			break;
		}

		foreach($args as $idx => $value)
		{
			if($value!="")
				$searchargs[] = $idx." LIKE '%".$value."%'";
		}

		if($searchargs)
			$searchargs = " WHERE 1=1 AND ".implode(" AND ",$searchargs);

		if($username = $this->ADB->GetAll("SELECT id, ".$this->ADB->Concat("UPPER(lastname)","' '","name")." AS username FROM users"))
			foreach($username as $idx => $row)
				$usernames[$row['id']] = $row['username'];

		if($nodelist = $this->ADB->GetAll("SELECT id, ipaddr, mac, name, ownerid, access FROM nodes ".$searchargs." ".($sqlord != "" ? $sqlord." ".$direction : "")))
		{
			foreach($nodelist as $idx => $row)
			{
				$nodelist[$idx]['ip'] = long2ip($row['ipaddr']);
				$nodelist[$idx]['owner'] = $usernames[$row['ownerid']];
				($row['access']) ? $totalon++ : $totaloff++;
			}			
		}

		switch($order)
		{
			case "ip":
				foreach($nodelist as $idx => $row)
				{
					$iptable['idx'][] = $idx;
					$iptable['iplong'][] = $row['iplong'];
				}
				array_multisort($iptable['iplong'],($direction == "DESC" ? SORT_DESC : SORT_ASC),SORT_NUMERIC,$iptable['idx']);
				foreach($iptable['idx'] as $idx)
					$nnodelist[] = $nodelist[$idx];
				$nodelist = $nnodelist;
			break;

			case "owner":
				foreach($nodelist as $idx => $row)
				{
					$ownertable['idx'][] = $idx;
					$ownertable['owner'][] = $row['owner'];
				}
				array_multisort($ownertable['owner'],($direction == "DESC" ? SORT_DESC : SORT_ASC),$ownertable['idx']);
				foreach($ownertable['idx'] as $idx)
					$nnodelist[] = $nodelist[$idx];
				$nodelist = $nnodelist;
			break;
		}

		$nodelist['total'] = sizeof($nodelist);
		$nodelist['order'] = $order;
		$nodelist['direction'] = $direction;
		$nodelist['totalon'] = $totalon;
		$nodelist['totaloff'] = $totaloff;

		return $nodelist;
	}

	function NodeSet($id)
	{
		$this->SetTS("nodes");
		if($this->ADB->GetOne("SELECT access FROM nodes WHERE id=?",array($id)) == 1 )
			return $this->ADB->Execute("UPDATE nodes SET access=0 WHERE id=?",array($id));
		else
			return $this->ADB->Execute("UPDATE nodes SET access=1 WHERE id=?",array($id));
	}

	function NodeSetU($id,$access=FALSE)
	{
		$this->SetTS("nodes");
		if($access)
			return $this->ADB->Execute("UPDATE nodes SET access=? WHERE ownerid=?",array(1,$id));
		else
			return $this->ADB->Execute("UPDATE nodes SET access=? WHERE ownerid=?",array(0,$id));
	}

	function NodeAdd($nodedata)
	{
		$this->SetTS("nodes");

		if($this->ADB->Execute("INSERT INTO nodes (name, mac, ipaddr, ownerid, creatorid, creationdate) VALUES (?, ?, ?, ?, ?, ?NOW?)",array(strtoupper($nodedata['name']),strtoupper($nodedata['mac']),ip_long($nodedata['ipaddr']),$nodedata['ownerid'],$this->SESSION->id)))
			return $this->ADB->GetOne("SELECT MAX(id) FROM nodes");
		else
			return FALSE;
	}

	function NodeExists($id)
	{
		return ($this->ADB->GetOne("SELECT * FROM nodes WHERE id=?",array($id))?TRUE:FALSE);
	}
	
	function NodeStats()
	{
		$result['connected'] = $this->ADB->GetOne("SELECT COUNT(id) FROM nodes WHERE access=1");
		$result['disconnected'] = $this->ADB->GetOne("SELECT COUNT(id) FROM nodes WHERE access=0");
		$result['total'] = $result['connected'] + $result['disconnected'];
		return $result;
	}

	/*
	 *  Obs³uga taryf
	 */
	
	function GetTariffList()
	{
		if($tarifflist = $this->ADB->GetAll("SELECT id, name, value, description, uprate, downrate FROM tariffs ORDER BY value DESC"))
			foreach($tarifflist as $idx => $row)
			{
				$tarifflist[$idx]['users'] = $this->GetUsersWithTariff($row['id']);
				$tarifflist[$idx]['income'] = $tarifflist[$idx]['users'] * $row['value'];
				$tarifflist['totalincome'] += $tarifflist[$idx]['income'];
				$tarifflist['totalusers'] += $tarifflist[$idx]['users'];
			}

		$tarifflist['total'] = sizeof($ttlist);
		return $tarifflist;
				
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
				$tariffdata['name'],
				$tariffdata['description'],
				$tariffdata['value'],
				$tariffdata['uprate'],
				$tariffdata['downrate']
			)
		))
			return $this->ADB->GetOne("SELECT id FROM tariffs WHERE name=?",array($tariffdata['name']));
		else
			return FALSE;
	}

	function TariffUpdate($tariff)
	{
		$this->SetTS("tariffs");
		return $this->ADB->Execute("UPDATE tariffs SET name=?, description=?, value=?, uprate=?, downrate=? WHERE id=?",array($tariff['name'], $tariff['description'], $tariff['value'], $tariff['uprate'], $tariff['downrate'], $tariff['id']));
	}
	
	function TariffDelete($id)
	{
		 if (!$this->GetUsersWithTariff($id)) 
		 return $this->ADB->Execute("DELETE FROM tariffs WHERE id=?",array($id));
		 else
		 return FALSE;
	}

	function GetTariffValue($id)
	{
		return $this->ADB->GetOne("SELECT value FROM tariffs WHERE id=?",array($id));
	}

	function GetTariffName($id)
	{
		return $this->ADB->GetOne("SELECT name FROM tariffs WHERE id=?",array($id));
	}

	function GetTariff($id)
	{
		$result = $this->ADB->GetRow("SELECT id, name, value, description, uprate, downrate FROM tariffs WHERE id=?",array($id));
		$result['count'] = $this->GetUsersWithTariff($id);
		$result['totalval'] = $result['value'] * $result['count'];
		$result['users'] = $this->ADB->GetAll("SELECT id, ".$this->ADB->Concat('upper(lastname)',"' '",'name')." AS username FROM users WHERE tariff=? AND status=3 ORDER BY username",array($id));
		$result['rows'] = ceil(sizeof($result['users'])/2);
		return $result;
	}

	function GetTariffs()
	{
		if($ttlist = $this->ADB->GetAll("SELECT id, name, value, uprate, downrate FROM tariffs ORDER BY value DESC"))
			foreach($ttlist as $row)
				foreach($row as $column => $value)
					$tarifflist[$column][] = $value;
		$tarifflist['common'] = $this->ADB->GetOne("SELECT tariff, COUNT(tariff) AS common FROM users WHERE tariff=tariff GROUP BY tariff ORDER BY common DESC");
		$tarifflist['commonpayday'] = $this->ADB->GetOne("SELECT payday, COUNT(payday) AS common FROM users WHERE payday=payday GROUP BY payday ORDER BY common DESC");
		return $tarifflist;
	}

	function TariffExists($id)
	{
		return ($this->ADB->GetOne("SELECT * FROM tariffs WHERE id=?",array($id))?TRUE:FALSE);
	}

	function SetBalanceZero($user_id)
	{
		$this->SetTS("cash");
		$stan=$this->GetUserBalance($user_id);
		$stan=-$stan;
		return $this->ADB->Execute("INSERT INTO cash (time, adminid, type, value, userid) VALUES (?NOW?, ?, ?, ?, ?)",array($this->SESSION->id, 3 , round("$stan",2) , $user_id));
	}
	function AddBalance($addbalance)
	{
		$this->SetTS("cash");
		return $this->ADB->Execute("INSERT INTO cash (time, adminid, type, value, userid, comment) VALUES (?NOW?, ?, ?, ?, ?, ?)",array($this->SESSION->id, $addbalance['type'], round($addbalance['value'],2) , $addbalance['userid'], $addbalance['comment']));	
	}
	function GetBalanceList()
	{
		$adminlist = $this->ADB->GetAllByKey('SELECT id, name FROM admins','id');
		$userslist = $this->ADB->GetAllByKey("SELECT id, ".$this->ADB->Concat("UPPER(lastname)","' '","name")." AS username FROM users","id");
		if($balancelist = $this->ADB->GetAll("SELECT id, time, adminid, type, value, userid, comment FROM cash ORDER BY time ASC"))
		{
			foreach($balancelist as $idx => $row)
			{
				$balancelist[$idx]['admin'] = $adminlist[$row['adminid']]['name'];
				$balancelist[$idx]['value'] = $row['value'];
				$balancelist[$idx]['username'] = $userslist[$row['userid']]['username'];
				if($idx)
					$balancelist[$idx]['before'] = $balancelist[$idx-1]['after'];
				else
					$balancelist[$idx]['before'] = 0;
					
				switch($row['type'])
				{
					case "1":
						$balancelist[$idx]['type'] = "przychód";
						$balancelist[$idx]['after'] = $balancelist[$idx]['before'] + $balancelist[$idx]['value'];
						$balancelist['income'] = $balancelist['income'] + $balancelist[$idx]['value'];
					break;

					case "2":
						$balancelist[$idx]['type'] = "rozchód";
						$balancelist[$idx]['after'] = $balancelist[$idx]['before'] - $balancelist[$idx]['value'];
						$balancelist['expense'] = $balancelist['expense'] + $balancelist[$idx]['value'];
					break;

					case "3":
						$balancelist[$idx]['type'] = "wp³ata u¿";
						$balancelist[$idx]['after'] = $balancelist[$idx]['before'] + $balancelist[$idx]['value'];
						$balancelist['incomeu'] = $balancelist['incomeu'] + $balancelist[$idx]['value'];
					break;
					case "4":
						$balancelist[$idx]['type'] = "obci±¿enie u¿";
						$balancelist[$idx]['after'] = $balancelist[$idx]['before'];
						$balancelist['uinvoice'] = $balancelist['uinvoice'] + $balancelist[$idx]['value'];
					break;
					default:
						$balancelist[$idx]['type'] = '<FONT COLOR="RED">???</FONT>';
						$balancelist[$idx]['after'] = $balancelist[$idx]['before'];
					break;
				}
				
			}
			
			$balancelist['total'] = $balancelist[$idx]['after'];

		}	

		return $balancelist;
	}
	
	function ScanNodes()
	{
		$networks = $this->GetNetworks();
		if($networks)
			foreach($networks as $idx => $network)
			{
				$out = split("\n",execute_program("nbtscan","-q -s: ".$network['address']."/".$network['prefix']));
				foreach($out as $line)
				{
					list($ipaddr,$name,$null,$login,$mac)=split(":",$line);
					$row['ipaddr'] = trim($ipaddr);
					$row['name'] = trim($name);
					$row['mac'] = str_replace("-",":",trim($mac));
					if(!$this->GetNodeIDByIP($row['ipaddr']) && $row['ipaddr'] && $row['mac'] != "00:00:00:00:00:00")
						$result[] = $row;
				}	
			}
		return $result;
	}								

	/*
	 *  Obs³uga rekordów z sieciami
	 */

	function NetworkExists($id)
	{
		return ($this->ADB->GetOne("SELECT * FROM networks WHERE id=?",array($id)) ? TRUE : FALSE);
	}	

	function IsIPFree($ip)
	{
		return !($this->ADB->GetOne("SELECT * FROM nodes WHERE ipaddr=?",array(ip_long($ip))) ? TRUE : FALSE);
	}

	function GetPrefixList()
	{
		for($i=30;$i>15;$i--)
		{
			$prefixlist['id'][] = $i;
			$prefixlist['value'][] = $i." (".pow(2,32-$i)." adresów)";
		}
		
		return $prefixlist;
	}

	function NetworkAdd($netadd)
	{
		if($netadd['prefix'] != "")
			$netadd['mask'] = prefix2mask($netadd['prefix']);
		$this->SetTS("networks");
		if($this->ADB->Execute("INSERT INTO networks (name, address, mask, interface, gateway, dns, dns2, domain, wins, dhcpstart, dhcpend) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array(strtoupper($netadd['name']),$netadd['address'],$netadd['mask'],strtolower($netadd['interface']),$netadd['gateway'],$netadd['dns'],$netadd['dns2'],$netadd['domain'],$netadd['wins'],$netadd['dhcpstart'],$netadd['dhcpend'])))
			return $this->ADB->GetOne("SELECT id FROM networks WHERE address=?",array($netadd['address']));
		else
			return FALSE;
	}

	function NetworkDelete($id)
	{
		$this->SetTS("networks");
		return $this->ADB->Execute("DELETE FROM networks WHERE id=?",array($id));
	}

	function GetNetworkName($id)
	{	
		return $this->ADB->GetOne("SELECT name FROM networks WHERE id=?",array($id));
	}


	function GetNetIDByIP($ipaddr)
	{
		if($networks = $this->ADB->GetAll("SELECT id, address, mask FROM networks"))
			foreach($networks as $idx => $row)
				if(isipin($ipaddr,$row['address'],$row['mask']))
					return $row['id'];
		return FALSE;
	}

	function GetNetworks()
	{
		if($netlist = $this->ADB->GetAll("SELECT id, name, address, mask FROM networks"))
			foreach($netlist as $idx => $row)
			{
				$netlist[$idx]['addresslong'] = ip_long($row['address']);
				$netlist[$idx]['prefix'] = mask2prefix($row['mask']);
			}
		
		return $netlist;
	}
	
	function GetNetworkParams($id)
	{
		if($params = $this->ADB->GetRow("SELECT * FROM networks WHERE id=?",array($id));
		{
			$params['address'] = ip_long($params['address']);
			$params['broadcast'] = ip_long(getbraddr($params['address'],$params['mask']));
		}
		return $params;
	}

	function GetNetworkList()
	{

		if($networks = $this->ADB->GetAll("SELECT id, name, address, mask, interface, gateway, dns, dns2, domain, wins, dhcpstart, dhcpend FROM networks"))
			foreach($networks as $idx => $row)
			{
				$row['prefix'] = mask2prefix($row['mask']);
				$row['size'] = pow(2,(32 - $row['prefix']));
				$row['addresslong'] = ip_long($row['address']);
				$row['broadcast'] = getbraddr($row['address'],$row['mask']);
				$row['broadcastlong'] = ip_long($row['broadcast']);
				$row['assigned'] = $this->ADB->GetOne("SELECT COUNT(*) FROM nodes WHERE ipaddr >= ? AND ipaddr <= ?",array($row['addresslong'], $row['broadcastlong']));
				$networks[$idx] = $row;
				$networks['size'] += $row['size'];
				$networks['assigned'] += $row['assigned'];
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
				if($row['id'] != $ignoreid)
					if($checkbroadcast)
					{
						if((ip_long($ip) > $row['addresslong'] - 1)&&(ip_long($ip) < ip_long(getbraddr($row['address'],$row['mask'])) + 1))
						{
							return TRUE;
						}
					}
					else
					{
						if((ip_long($ip) > $row['addresslong'])&&(ip_long($ip) < ip_long(getbraddr($row['address'],$row['mask']))))
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
				$broadcast = ip_long(getbraddr($row['address'],$row['mask']));
				$netaddr = $row['addresslong'];					
				if($row['id'] != $ignorenet)
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
	
	function NetworkShift($network="0.0.0.0",$mask="0.0.0.0",$shift=0)
	{
		$this->SetTS("nodes");
		$this->SetTS("networks");
		return $this->ADB->Execute("UPDATE nodes SET ipaddr = ipaddr + ? WHERE ipaddr >= ? AND ipaddr <= ?",array($shift,ip_long($network), ip_long(getbraddr($network,$mask))));
	}

	function NetworkUpdate($networkdata)
	{
		$this->SetTS("networks");
		return $this->ADB->Execute("UPDATE networks SET name=?, address=?, mask=?, interface=?, gateway=?, dns=?, dns2=?, domain=?, wins=?, dhcpstart=?, dhcpend=? WHERE id=?",array(strtoupper($networkdata['name']),$networkdata['address'],$networkdata['mask'],strtolower($networkdata['interface']),$networkdata['gateway'],$networkdata['dns'],$networkdata['dns2'],$networkdata['domain'],$networkdata['wins'],$networkdata['dhcpstart'],$networkdata['dhcpend'],$networkdata['id']));
	}
				
	
	function NetworkCompress($id,$shift=0)
	{
		$this->SetTS("nodes");
		$this->SetTS("networks");
		$network=$this->GetNetworkRecord($id);
		$address = $network['addresslong']+$shift;
		foreach($network['nodes']['id'] as $key => $value)
		{
			if($value)
			{
				$address ++;
				$this->ADB->Execute("UPDATE nodes SET ipaddr=? WHERE id=?",array($address,$value));
			}				
		}
	}

	function NetworkRemap($src,$dst)
	{
		$this->SetTS("nodes");
		$this->SetTS("networks");
		$network['source'] = $this->GetNetworkRecord($src);
		$network['dest'] = $this->GetNetworkRecord($dst);
		foreach($network['source']['nodes']['id'] as $key => $value)
			if($this->NodeExists($value))
				$nodelist[] = $value;
		$counter = 1;
		if(sizeof($nodelist))
			foreach($nodelist as $value)
			{
				while($this->NodeExists($network['dest']['nodes']['id'][$counter]))
					$counter++;
				$this->ADB->Execute("UPDATE nodes SET ipaddr=? WHERE id=?",array($network['dest']['nodes']['addresslong'][$counter],$value));
				$counter++;
			}
		return $counter;
	}

	function GetNetworkRecord($id,$page = 0, $plimit = 4294967296)
	{
		$network = $this->ADB->GetRow("SELECT id, name, address, mask, interface, gateway, dns, dns2, domain, wins, dhcpstart, dhcpend FROM networks WHERE id=?",array($id));
		$network['prefix'] = mask2prefix($network['mask']);
		$network['addresslong'] = ip_long($network['address']);
		$network['size'] = pow(2,32-$network['prefix']);
		$network['assigned'] = 0;
		$network['broadcast'] = getbraddr($network['address'],$network['mask']);
		$network['pagemax'] = ceil($network['size'] / $plimit);

		if($page > $network['pagemax'])
			$page = $network['pagemax'];
		if($page < 1)
			$page = 1;

		$page --;
		$start = $page * $plimit;
		$end = ($network['size'] > $plimit ? $start + $plimit : $network['size']);

		$nodes = $this->ADB->GetAllByKey("SELECT id, name, ipaddr, ownerid FROM nodes WHERE ipaddr >= ? AND ipaddr <= ?",'ipaddr',array(($network['addresslong'] + $start), ($network['addresslong'] + $end)));
		
		for($i = 0; $i < ($end - $start) ; $i ++)
		{
			
			$longip = $network['addresslong'] + $i + $start;
			$node = $nodes["".$longip.""];
			$network['nodes']['addresslong'][$i] = $longip;
			$network['nodes']['address'][$i] = long2ip($longip);;
			$network['nodes']['id'][$i] = $node['id'];

			if( $network['nodes']['addresslong'][$i] == $network['addresslong'] || $network['nodes']['addresslong'][$i] == $network['addresslong'] + $network['size'] - 1)
				$network['nodes']['name'][$i] = 'BROADCAST';
			elseif($network['nodes']['addresslong'][$i] >= ip_long($network['dhcpstart']) && $network['nodes']['addresslong'][$i] <= ip_long($network['dhcpend']))
				$network['nodes']['name'][$i] = 'DHCP';
			else
				$network['nodes']['name'][$i] = $node['name'];
			$network['nodes']['ownerid'][$i] = $node['ownerid'];
			if($node['id'])
				$network['pageassigned'] ++;
		}
		
		$network['assigned'] = $this->ADB->GetOne("SELECT COUNT(*) FROM nodes WHERE ipaddr >= ? AND ipaddr < ?",array($network['addresslong'], $network['addresslong'] + $network['size']));
		
		$network['rows'] = ceil(sizeof($network['nodes']['address']) / 4);
		$network['free'] = $network['size'] - $network['assigned'] - 2;
		$network['pages'] = ceil($network['size'] / $plimit);
		$network['page'] = $page + 1;

		return $network;
	}

	function GetNetwork($id)
	{
		if($row = $this->ADB->GetRow("SELECT address, mask, name FROM networks WHERE id=?",array($id)))
			foreach($row as $field => $value)
				$$field = $value;
	
		for($i=ip_long($address)+1;$i<ip_long(getbraddr($address,$mask));$i++)
		{
			$result['addresslong'][] = $i;
			$result['address'][] = long2ip($i);
			$result['nodeid'][] = 0;
			$result['nodename'][] = "";
			$result['ownerid'][] = 0;
		}
		
		if(sizeof($result['address']))
			if($nodes = $this->ADB->GetAll("SELECT name, id, ownerid, ipaddr FROM nodes WHERE ipaddr >= ? AND ipaddr <= ?",array(ip_long($address), ip_long(getbraddr($address,$mask)))))
				foreach($nodes as $node)
				{
					$pos = ($node['ipaddr'] - ip_long($address) - 1);
					$result['nodeid'][$pos] = $node['nodeid'];
					$result['nodename'][$pos] = $node['name'];
					$result['ownerid'][$pos] = $node['ownerid'];
				}
		return $result;
	}

	/*
	 * Pozosta³e funkcje...
	 */

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
					if(check_mac($mac) && $mac != '00:00:00:00:00:00')
					{
						$result['mac'][] = $mac;
						$result['ip'][] = $ip;
						$result['longip'][] = ip_long($ip);
						$result['nodename'][] = $this->GetNodeNameByMAC($mac);
					}
				}
				break;

			default:
				exec("arp -an|grep -v incompl",$result);
				foreach($result as $arpline)
				{
					list($empty,$ip,$empty,$mac) = explode(" ",$arpline);
					$ip = str_replace("(","",str_replace(")","",$ip));
					$result['mac'][] = $mac;
					$result['ip'][] = $ip;
					$result['longip'][] = ip_long($ip);
					$result['nodename'][] = $this->GetNodeNameByMAC($mac);
				}
				break;

		}
		array_multisort($result['longip'],$result['mac'],$result['ip'],$result['nodename']);
		return $result;
	}

	function Mailing($mailing)
	{
		$SESSION=$this->SESSION;
		$emails = $this->GetEmails($mailing['group']);

		if($emails = $this->GetEmails($mailing['group']))
		{
			if($this->CONFIG['debug_email'])
				echo "<B>Uwaga! Tryb debug (u¿ywam adresu ".$this->CONFIG['debug_email']."</B><BR>";
				
			foreach($emails as $key => $row)
			{
				if($this->CONFIG['debug_email'])
					$row['email'] = $this->CONFIG['debug_email'];

				mail (
					$row['username']." <".$row['email'].">",
					$mailing['subject'],
					$mailing['body'],
					"From: ".$mailing['sender']." <".$mailing['from'].">\r\n"."Content-type: text/plain; charset=\"iso-8858-2\"\r\n"."X-Mailer: LMS-".$this->_version."/PHP-".phpversion()."\r\n"."X-Remote-IP: ".$_SERVER['REMOTE_ADDR']."\r\n"."X-HTTP-User-Agent: ".$_SERVER['HTTP_USER_AGENT']."\r\n"
				);

				echo "<img src=\"img/mail.gif\" border=\"0\" align=\"absmiddle\" alt=\"\"> ".($key+1)." z ".sizeof($emails)." (".sprintf("%02.2f",round((100/sizeof($emails))*($key+1),2))."%): ".$row['username']." &lt;".$row['email']."&gt;<BR>\n";
				flush();

			}
		}
	}
}

/*
 * $Log$
 * Revision 1.209  2003/08/31 19:16:54  alec
 * added GetNetworkParams
 *
 * Revision 1.208  2003/08/30 01:11:21  lukasz
 * - nowe pole w li¶cie sieci: interfejs
 *
 * Revision 1.207  2003/08/29 22:53:19  lukasz
 * - lista taryf zlicza³a tak¿e u¿ytkowników usuniêtych
 *
 * Revision 1.206  2003/08/29 01:16:28  lukasz
 * - w³±czenie transakcji przy odczytywaniu backup bazy z dysku
 *
 * Revision 1.205  2003/08/28 13:02:05  lukasz
 * - podawanie dnia naliczania op³aty podczas dodawania usera nic dawa³o
 *
 * Revision 1.204  2003/08/27 21:38:13  alec
 * removed two ' :)
 *
 * Revision 1.203  2003/08/27 21:00:33  alec
 * Popr. RecoverUser()
 *
 * Revision 1.202  2003/08/27 20:32:54  lukasz
 * - changed another ENUM (users.deleted) to BOOL
 *
 * Revision 1.201  2003/08/27 20:18:42  lukasz
 * - changed nodes.access from ENUM to BOOL;
 *
 * Revision 1.200  2003/08/27 19:25:00  lukasz
 * - changed format of ipaddr storage in database
 * - propably improved performance
 *
 * Revision 1.199  2003/08/25 02:14:05  lukasz
 * - zmieniona obs³uga usuwania userów
 *
 * Revision 1.198  2003/08/24 13:12:54  lukasz
 * - massive attack: s/<?/<?php/g - that was causing problems on some fucked
 *   redhat's :>
 *
 * Revision 1.197  2003/08/24 00:59:29  lukasz
 * - LMSDB: GetAllByKey($query, $key, $inputarray)
 * - LMS: more fixes for new DAL
 *
 * Revision 1.196  2003/08/23 12:46:57  alec
 * literówka
 *
 * Revision 1.195  2003/08/22 13:15:00  lukasz
 * - fixed MetaTables
 *
 * Revision 1.194  2003/08/22 00:17:50  lukasz
 * - removed ADODB ;>
 *
 * Revision 1.193  2003/08/21 03:14:29  lukasz
 * - http://lists.rulez.pl/lms/0835.html
 *
 * Revision 1.192  2003/08/20 01:46:12  lukasz
 * - do not display MAC's '00:00:00:00:00:00'
 *
 * Revision 1.191  2003/08/18 16:57:00  lukasz
 * - more cvs tags :>
 *
 */

?>
