<?php

/*
 * LMS version 1.4-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

	var $DB;			// obiekt bazy danych
	var $SESSION;			// obiekt z Session.class.php (zarz±dzanie sesj±)
	var $CONFIG;			// tablica zawieraj±ca zmienne z lms.ini
	var $_version = '1.4-cvs';	// wersja klasy
	var $_revision = '$Revision$';
	var $MENU = array();

	function LMS(&$DB, &$SESSION, &$CONFIG) // ustawia zmienne klasy
	{
		if($SESSION !== NULL)
		{
			$this->SESSION = &$SESSION;
			$this->modules[] = 'SESSION';
		}
		$this->DB = &$DB;
		$this->CONFIG = &$CONFIG;
		$this->modules[] = 'CORE';
		$this->CORE = &$this;

		// za³aduj ekstra klasy:

		if($dirhandle = @opendir($this->CONFIG['directories']['lib_dir'].'/modules/'))
		{
			while(FALSE !== ($file = readdir($dirhandle)))
			{
				if(ereg('^[0-9a-z]+\.class.php$',$file))
				{
					$classname = ereg_replace('\.class.php$','',$file);
					@require_once($this->CONFIG['directories']['lib_dir'].'/modules/'.$classname.'.class.php');
					$this->$classname = new $classname($this);
					$this->modules[] = $classname;
				}
			}
		}

		// poustawiajmy ->version

		foreach($this->modules as $module)
		{
			$this->$module->_revision = eregi_replace('^.Revision: ([0-9.]+).*','\1',$this->$module->_revision);
			$this->$module->version = $this->$module->_version.' ('.$this->$module->_revision.')';
		}

		// a teraz postinit

		foreach($this->modules as $module)
			if(! ($this->$module != NULL ? $this->$module->_postinit() : TRUE))
				trigger_error('Wyst±pi³y problemy z inicjalizacj± modu³u '.$module.'.');

		// to siê rozejdzie po modu³ach:

		$this->AddMenu('Helpdesk', 'ticket.gif', '?m=rtqueuelist', 'Obs³uga zg³oszeñ (RT)', 'r', 60);
		$this->AddMenu('Witamy !', 'l.gif', '?', '', '', 0);
		$this->AddMenu('U¿ytkownicy', 'user.gif', '?m=userlist', 'U¿ytkownicy: lista, wyszukiwanie, dodanie nowego', 'u', 10);
		$this->AddMenu('Komputery', 'node.gif', '?m=nodelist', 'Komputery: lista, wyszukiwanie, dodawanie', 'k', 15);
		$this->AddMenu('Osprzêt sieciowy', 'netdev.gif', '?m=netdevlist', 'Ewidencja sprzêtu sieciowego', 'o', 20);
		$this->AddMenu('Sieci IP', 'network.gif', '?m=netlist', 'Zarz±dzanie klasami adresowymi IP', 's', 25);
		$this->AddMenu('Taryfy i finanse', 'money.gif', '?m=tarifflist', 'Zarz±dzanie taryfami oraz finansami sieci', 't', 30);
		$this->AddMenu('Mailing', 'mail.gif', '?m=mailing', 'Korespondencja seryjna', 'm', 35);
		$this->AddMenu('Prze³adowanie', 'reload.gif', '?m=reload', '', 'r', 40);
		$this->AddMenu('Bazy danych', 'db.gif', '?m=dblist', 'Zarz±dzanie kopiami zapasowymi bazy danych', 'b', 45);
		$this->AddMenu('Administratorzy', 'admins.gif', '?m=adminlist', 'Konta administratorów systemu', 'd', 50);
		$this->AddMenu('Statystyki', 'traffic.gif', '?m=traffic', 'Statystyki wykorzystania ³±cza', 'x', 55);
	}

	function _postinit()
	{
		return TRUE;
	}

	/*
	 *  Funkcje podstawowe (ró¿ne)
	 */

	function AddMenu($name = '', $img = '', $link = '', $tip = '', $accesskey = '', $prio = 99)
	{
		if($name != '')
		{
			foreach(array('name', 'img', 'link', 'tip', 'accesskey', 'prio') as $key)
				$this->MENU[$key][] = $$key;
			array_multisort($this->MENU['prio'], SORT_NUMERIC, SORT_ASC, $this->MENU['name'], SORT_STRING, SORT_ASC, $this->MENU['img'], $this->MENU['link'], $this->MENU['accesskey'], $this->MENU['tip']);
			return TRUE;
		}
		return FALSE;
	}
		

	/*
	 *  Funkcje bazodanowe (backupy, timestampy)
	 */

	function SetTS($table) // ustawia timestamp tabeli w tabeli 'timestamps'
	{
		if($this->DB->GetOne('SELECT * FROM timestamps WHERE tablename=?', array($table)))
			$this->DB->Execute('UPDATE timestamps SET time = ?NOW? WHERE tablename=?', array($table));
		else
			$this->DB->Execute('INSERT INTO timestamps (tablename, time) VALUES (?, ?NOW?)', array($table));

		if($this->DB->GetOne('SELECT * FROM timestamps WHERE tablename=?', array('_global')))
			$this->DB->Execute('UPDATE timestamps SET time = ?NOW? WHERE tablename=?', array('_global'));
		else
			$this->DB->Execute('INSERT INTO timestamps (tablename, time) VALUES (?, ?NOW?)', array('_global'));
	}

	function GetTS($table) // zwraca timestamp tabeli zapisany w tabeli 'timestamps'
	{
		return $this->DB->GetOne('SELECT time FROM timestamps WHERE tablename=?', array($table));
	}

	function DeleteTS($table) // usuwa timestamp tabeli zapisany w tabeli 'timestamps'
	{
		return $this->DB->Execute('DELETE FROM timestamps WHERE tablename=?', array($table));
	}

	function DatabaseList() // zwraca listê kopii baz danych w katalogu z backupami
	{
		if ($handle = opendir($this->CONFIG['directories']['backup_dir']))
		{
			while (false !== ($file = readdir($handle)))
			{
				if ($file != '.' && $file != '..')
				{
					$path = pathinfo($file);
					if($path['extension'] = 'sql')
					{
						if(substr($path['basename'],0,4)=='lms-')
						{
							$dblist['time'][] = substr(basename("$file",'.sql'),4);
							$dblist['size'][] = filesize($this->CONFIG['directories']['backup_dir'].'/'.$file);
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
		if(file_exists($this->CONFIG['directories']['backup_dir'].'/lms-'.$dbtime.'.sql'))
		{
			return $this->DBLoad($this->CONFIG['directories']['backup_dir'].'/lms-'.$dbtime.'.sql');
		}
		else
			return FALSE;
	}

	function DBLoad($filename=NULL) // wczytuje plik z backupem bazy danych
	{
		if(!$filename)
			return FALSE;
		$file = fopen($filename,'r');
		$this->DB->BeginTrans(); // przyspieszmy dzia³anie je¿eli baza danych obs³uguje transakcje
		while(!feof($file))
		{
			$line = fgets($file,4096);
			if($line!='')
			{
				$line=str_replace(';\n','',$line);
				$this->DB->Execute($line);
			}
		}
		$this->DB->CommitTrans();
		fclose($file);

		// Okej, zróbmy parê bzdurek db depend :S
		// Postgres sux ! (warden)
		// Tak, a ³y¿ka na to 'niemo¿liwe' i polecia³a za wann± potr±caj±c bannanem musztardê (lukasz)

		switch($this->CONFIG['database']['type'])
		{
			case 'postgres':
				// uaktualnijmy sequencery postgresa
				foreach($this->DB->ListTables() as $tablename)
					if(!in_array($tablename, array('rtattachments','dbinfo','invoicecontents','stats','timestamps')))
						$this->DB->Execute("SELECT setval('".$tablename."_id_seq',max(id)) FROM ".$tablename);
			break;
		}
	}

	function DBDump($filename=NULL) // zrzuca bazê danych do pliku
	{
		if(! $filename)
			return FALSE;
		if($dumpfile = fopen($filename,'w'))
		{
			foreach($this->DB->ListTables() as $tablename)
			{
				fputs($dumpfile,"DELETE FROM $tablename;\n");
				if($dump = $this->DB->GetAll('SELECT * FROM '.$tablename))
					foreach($dump as $row)
					{
						fputs($dumpfile,"INSERT INTO $tablename (");
						foreach($row as $field => $value)
						{
							$fields[] = $field;
							if(isset($value))
								$values[] = "'".addcslashes($value,"\r\n\'\"\\")."'";
							else
								$values[] = 'NULL';
						}
						fputs($dumpfile,implode(', ',$fields));
						fputs($dumpfile,') VALUES (');
						fputs($dumpfile,implode(', ',$values));
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
		return $this->DBDump($this->CONFIG['directories']['backup_dir'].'/lms-'.time().'.sql');
	}

	function DatabaseDelete($dbtime) // usuwa plik ze zrzutem
	{
		if(@file_exists($this->CONFIG['directories']['backup_dir'].'/lms-'.$dbtime.'.sql'))
		{
			return @unlink($this->CONFIG['directories']['backup_dir'].'/lms-'.$dbtime.'.sql');
		}
		else
			return FALSE;
	}

	function DatabaseFetchContent($dbtime) // zwraca zawarto¶æ tekstow± kopii bazy danych
	{
		if(file_exists($this->CONFIG['directories']['backup_dir'].'/lms-'.$dbtime.'.sql'))
		{
			$content = file($this->CONFIG['directories']['backup_dir'].'/lms-'.$dbtime.'.sql');
			foreach($content as $value)
				$database['content'] .= $value;
			$database['size'] = filesize($this->CONFIG['directories']['backup_dir'].'/lms-'.$dbtime.'.sql');
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
		$this->SetTS('admins');
		$this->DB->Execute('UPDATE admins SET passwd=? WHERE id=?', array(crypt($passwd),$id));
	}

	function GetAdminName($id) // zwraca imiê admina
	{
		return $this->DB->GetOne('SELECT name FROM admins WHERE id=?', array($id));
	}

	function GetAdminNames() // zwraca skrócon± listê adminów
	{
		return $this->DB->GetAll('SELECT id, name FROM admins WHERE deleted=0 ORDER BY name ASC');
	}

	function GetAdminList() // zwraca listê administratorów
	{
		if($adminslist = $this->DB->GetAll('SELECT id, login, name, lastlogindate, lastloginip FROM admins WHERE deleted=0 ORDER BY login ASC'))
		{
			foreach($adminslist as $idx => $row)
			{
				if($row['lastlogindate'])
					$adminslist[$idx]['lastlogin'] = date('Y/m/d H:i',$row['lastlogindate']);
				else
					$adminslist[$idx]['lastlogin'] = '-';

				if(check_ip($row['lastloginip']))
					$adminslist[$idx]['lastloginhost'] = gethostbyaddr($row['lastloginip']);
				else
				{
					$adminslist[$idx]['lastloginhost'] = '-';
					$adminslist[$idx]['lastloginip'] = '-';
				}
			}
		}

		$adminslist['total'] = sizeof($adminslist);
		return $adminslist;
	}

	function GetAdminIDByLogin($login) // zwraca id admina na podstawie loginu
	{
		return $this->DB->GetOne('SELECT id FROM admins WHERE login=?', array($login));
	}

	function AdminAdd($adminadd) // dodaje admina. wymaga tablicy zawieraj±cej dane admina
	{
		$this->SetTS('admins');
		if($this->DB->Execute('INSERT INTO admins (login, name, email, passwd, rights) VALUES (?, ?, ?, ?, ?)', array($adminadd['login'], $adminadd['name'], $adminadd['email'], crypt($adminadd['password']),$adminadd['rights'])))
			return $this->DB->GetOne('SELECT id FROM admins WHERE login=?', array($adminadd['login']));
		else
			return FALSE;
	}

	function AdminDelete($id) // usuwa admina o podanym id
	{
		$this->SetTS('admins');
		return $this->DB->Execute('UPDATE admins SET deleted=1 WHERE id=?', array($id));
	}

	function AdminExists($id) // zwraca TRUE/FALSE zale¿nie od tego czy admin istnieje czy nie
	{
		switch($this->DB->GetOne('SELECT deleted FROM admins WHERE id=?', array($id)))
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

	function GetAdminInfo($id) // zwraca pe³ne info o podanym adminie
	{
		if($admininfo = $this->DB->GetRow('SELECT id, login, name, email, lastlogindate, lastloginip, failedlogindate, failedloginip, deleted FROM admins WHERE id=?', array($id)))
		{
			if($admininfo['lastlogindate'])
				$admininfo['lastlogin'] = date('Y/m/d H:i',$admininfo['lastlogindate']);
			else
				$admininfo['lastlogin'] = '-';

			if($admininfo['failedlogindate'])
				$admininfo['faillogin'] = date('Y/m/d H:i',$admininfo['failedlogindate']);
			else
				$admininfo['faillogin'] = '-';


			if(check_ip($admininfo['lastloginip']))
				$admininfo['lastloginhost'] = gethostbyaddr($admininfo['lastloginip']);
			else
			{
				$admininfo['lastloginhost'] = '-';
				$admininfo['lastloginip'] = '-';
			}

			if(check_ip($admininfo['failedloginip']))
				$admininfo['failloginhost'] = gethostbyaddr($admininfo['failedloginip']);
			else
			{
				$admininfo['failloginhost'] = '-';
				$admininfo['failloginip'] = '-';
			}
		}
		return $admininfo;
	}

	function AdminUpdate($admininfo) // uaktualnia rekord admina.
	{
		$this->SetTS('admins');
		return $this->DB->Execute('UPDATE admins SET login=?, name=?, email=?, rights=? WHERE id=?', array($admininfo['login'],$admininfo['name'],$admininfo['email'],$admininfo['rights'],$admininfo['id']));
	}

	function GetAdminRights($id)
	{
		$mask = $this->DB->GetOne('SELECT rights FROM admins WHERE id=?', array($id));
		if($mask == '')
			$mask = '1';
		$len = strlen($mask);
		for($cnt=$len; $cnt > 0; $cnt --)
			$bin = sprintf('%04b',hexdec($mask[$cnt-1])).$bin;
		for($cnt=strlen($bin)-1; $cnt >= 0; $cnt --)
			if($bin[$cnt] == '1')
				$result[] = strlen($bin) - $cnt -1;
		return $result;
	}

	/*
	 *  Funkcje do obs³ugi rekordów u¿ytkowników
	 */

	function GetUserName($id)
	{
		return $this->DB->GetOne('SELECT '.$this->DB->Concat('UPPER(lastname)',"' '",'name').' FROM users WHERE id=?', array($id));
	}

	function GetEmails($group, $network=NULL, $usergroup=NULL)
	{
		if($network) 
			$net = $this->GetNetworkParams($network);
		
		return $this->DB->GetAll('SELECT email, '.$this->DB->Concat('lastname', "' '", 'users.name').' AS username FROM users ' 
			.($network ? ', nodes' : '')
			.($usergroup ? ', userassignments' : '')
			." WHERE deleted = 0 AND email != '' ".($state ? ' AND status = '.$state : '')
			.($network ? ' AND users.id=nodes.ownerid AND (ipaddr > '.$net['address'].' AND ipaddr < '.$net['broadcast'].')' : '')
			.($usergroup ? ' AND users.id=userassignments.userid AND usergroupid='.$usergroup : '')
			.' GROUP BY email, lastname, users.name ORDER BY username'); 
	}

	function GetUserEmail($id)
	{
		return $this->DB->GetOne('SELECT email FROM users WHERE id=?', array($id));
	}

	function GetUserServiceAddress($id)
	{
		return $this->DB->GetOne('SELECT serviceaddr FROM users WHERE id=?', array($id));
	}

	function UserExists($id)
	{
		switch($this->DB->GetOne('SELECT deleted FROM users WHERE id=?', array($id)))
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
		$this->SetTS('users');
		return $this->DB->Execute('UPDATE users SET deleted=0 WHERE id=?', array($id));
	}

	// confusing function name, gets number of tariff assignments, not number of users with this tariff
	function GetUsersWithTariff($id)
	{
		return $this->DB->GetOne('SELECT COUNT(userid) FROM assignments, users WHERE users.id = userid AND deleted = 0 AND tariffid = ?', array($id));
	}

	function UserAdd($useradd)
	{
		if($this->DB->Execute('INSERT INTO users (name, lastname, phone1, phone2, phone3, gguin, address, zip, city, email, nip, pesel, status, creationdate, creatorid, info, serviceaddr, message, pin) VALUES (?, UPPER(?), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?NOW?, ?, ?, ?, ?, ?)', array(ucwords($useradd['name']),  $useradd['lastname'], $useradd['phone1'], $useradd['phone2'], $useradd['phone3'], $useradd['gguin'], $useradd['address'], $useradd['zip'], $useradd['city'], $useradd['email'], $useradd['nip'], $useradd['pesel'], $useradd['status'], $this->SESSION->id, $useradd['info'], $useradd['serviceaddr'], $useradd['message'], $useradd['pin']))) {
			$this->SetTS('users');
			return $this->DB->GetOne('SELECT MAX(id) FROM users');
		} else
			return FALSE;
	}

	function DeleteUser($id)
	{
		$this->SetTS('users');
		$this->SetTS('nodes');
		$this->SetTS('userassignments');
		$res1=$this->DB->Execute('DELETE FROM nodes WHERE ownerid=?', array($id));
		$res2=$this->DB->Execute('DELETE FROM userassignments WHERE userid=?', array($id));
		$res3=$this->DB->Execute('UPDATE users SET deleted=1 WHERE id=?', array($id));
		return $res1 || $res2 || res3;
	}

	function UserUpdate($userdata)
	{
		$this->SetTS('users');
		return $this->DB->Execute('UPDATE users SET status=?, phone1=?, phone2=?, phone3=?, address=?, zip=?, city=?, email=?, gguin=?, nip=?, pesel=?, moddate=?NOW?, modid=?, info=?, serviceaddr=?, lastname=UPPER(?), name=?, deleted=0, message=?, pin=? WHERE id=?', array( $userdata['status'], $userdata['phone1'], $userdata['phone2'], $userdata['phone3'], $userdata['address'], $userdata['zip'], $userdata['city'], $userdata['email'], $userdata['gguin'], $userdata['nip'], $userdata['pesel'], $this->SESSION->id, $userdata['info'], $userdata['serviceaddr'], $userdata['lastname'], ucwords($userdata['name']), $userdata['message'], $userdata['pin'], $userdata['id'] ) );
	}

	function GetUserNodesNo($id)
	{
		return $this->DB->GetOne('SELECT COUNT(*) FROM nodes WHERE ownerid=?', array($id));
	}

	function GetUserIDByIP($ipaddr)
	{
		return $this->DB->GetOne('SELECT ownerid FROM nodes WHERE ipaddr=inet_aton(?)', array($ipaddr));
	}

	function GetCashByID($id)
	{
		return $this->DB->GetRow('SELECT time, adminid, type, value, taxvalue, userid, comment FROM cash WHERE id=?', array($id));
	}

	function GetUserStatus($id)
	{
		return $this->DB->GetOne('SELECT status FROM users WHERE id=?', array($id));
	}

	function GetUser($id)
	{
		if($result = $this->DB->GetRow('SELECT id, '.$this->DB->Concat('UPPER(lastname)',"' '",'name').' AS username, lastname, name, status, email, gguin, phone1, phone2, phone3, address, zip, nip, pesel, city, info, serviceaddr, creationdate, moddate, creatorid, modid, deleted, message, pin FROM users WHERE id=?', array($id)))
		{
			$result['createdby'] = $this->GetAdminName($result['creatorid']);
			$result['modifiedby'] = $this->GetAdminName($result['modid']);
			$result['creationdateh'] = date("Y-m-d, H:i",$result['creationdate']);
			$result['moddateh'] = date('Y-m-d, H:i',$result['moddate']);
			$result['balance'] = $this->GetUserBalance($result['id']);
			$result['tariffsvalue'] = $this->GetUserTariffsValue($result['id']);
			return $result;
		}else
			return FALSE;
	}

	function GetUserNames()
	{
		return $this->DB->GetAll('SELECT id, '.$this->DB->Concat('UPPER(lastname)',"' '",'name').' AS username FROM users WHERE status=3 AND deleted = 0 ORDER BY username');
	}

	function GetUserNodesAC($id)
	{
		if($acl = $this->DB->GetALL('SELECT access FROM nodes WHERE ownerid=?', array($id)))
		{
			foreach($acl as $value)
				if($value['access'])
					$y++;
				else
					$n++;

			if($y && !$n) return TRUE;
			if($n && !$y) return FALSE;
		}
		if($this->DB->GetOne('SELECT COUNT(*) FROM nodes WHERE ownerid=?', array($id)))
			return 2;
		else
			return FALSE;
	}

	function SearchUserList($order=NULL,$state=NULL,$search=NULL)
	{
		list($order,$direction)=explode(',',$order);

		($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

		switch($order){

			case 'phone':
				$sqlord = 'ORDER BY deleted ASC, phone1';
			break;
			case 'id':
				$sqlord = 'ORDER BY deleted ASC, id';
			break;
			case 'address':
				$sqlord = 'ORDER BY deleted ASC, address';
			break;
			case 'email':
				$sqlord = 'ORDER BY deleted ASC, email';
			break;
			case 'balance':
				$sqlord = 'ORDER BY deleted ASC, balance';
			break;
			case 'gg':
				$sqlord = 'ORDER BY deleted ASC, gguin';
			break;
			case 'nip':
				$sqlord = 'ORDER BY deleted ASC, nip, pesel';
			break;
			default:
				$sqlord = 'ORDER BY deleted ASC, '.$this->DB->Concat('UPPER(lastname)',"' '",'name');
			break;
		}

		if(sizeof($search))
			foreach($search as $key => $value)
			{
				$value = str_replace(' ','%',trim($value));
				if($value!='')
				{
					$value = "'%".$value."%'";
					if($key=='phone')
						$searchargs[] = "(phone1 ?LIKE? $value OR phone2 ?LIKE? $value OR phone3 ?LIKE? $value)";
					elseif($key=='username')
						$searchargs[] = $this->DB->Concat('UPPER(lastname)',"' '",'name').' ?LIKE? '.$value;
					elseif($key!='s')
						$searchargs[] = $key.' ?LIKE? '.$value;
				}
			}

		if($searchargs)
			$sqlsarg = implode(' AND ',$searchargs);

		if($state>3)
			$state = 0;

		if($userlist = $this->DB->GetAll('SELECT users.id AS id, '.$this->DB->Concat('UPPER(lastname)',"' '",'users.name').' AS username, deleted, status, email, phone1, address, gguin, nip, pesel, zip, city, info, COALESCE(SUM((type * -2 + 7) * value), 0.00) AS balance FROM users LEFT JOIN cash ON users.id = cash.userid AND (cash.type = 3 OR cash.type = 4) WHERE 1=1 '.($state !=0 ? " AND status = '".$state."'":'').($sqlsarg !='' ? ' AND '.$sqlsarg :'').' GROUP BY users.id, deleted, lastname, users.name, status, email, phone1, phone2, phone3, address, gguin, nip, pesel, zip, city, info '.($sqlord !='' ? $sqlord.' '.$direction:'')))
		{
			$week = $this->DB->GetAllByKey('SELECT users.id AS id, SUM(value)*4 AS value FROM assignments, tariffs, users WHERE userid = users.id AND tariffid = tariffs.id AND deleted = 0 AND period = 0 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY users.id', 'id');
			$month = $this->DB->GetAllByKey('SELECT users.id AS id, SUM(value) AS value FROM assignments, tariffs, users WHERE userid = users.id AND tariffid = tariffs.id AND deleted = 0 AND period = 1 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY users.id', 'id');
			$quarter = $this->DB->GetAllByKey('SELECT users.id AS id, SUM(value)/3 AS value FROM assignments, tariffs, users WHERE userid = users.id AND tariffid = tariffs.id AND deleted = 0 AND period = 2 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY users.id', 'id');
			$year = $this->DB->GetAllByKey('SELECT users.id AS id, SUM(value)/12 AS value FROM assignments, tariffs, users WHERE userid = users.id AND tariffid = tariffs.id AND deleted = 0 AND period = 3 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY users.id', 'id');

			$access = $this->DB->GetAllByKey('SELECT ownerid AS id, SUM(access) AS acsum, COUNT(access) AS account FROM nodes GROUP BY ownerid','id');
			$warning = $this->DB->GetAllByKey('SELECT ownerid AS id, SUM(warning) AS warnsum, COUNT(warning) AS warncount FROM nodes GROUP BY ownerid','id');

			foreach($userlist as $idx => $row)
			{
				$userlist[$idx]['tariffvalue'] = $week[$row['id']]['value']+$month[$row['id']]['value']+$quarter[$row['id']]['value']+$year[$row['id']]['value'];
				$userlist[$idx]['account'] = $access[$row['id']]['account'];
				$userlist[$idx]['warncount'] = $warning[$row['id']]['warncount'];

				if($access[$row['id']]['account'] == $access[$row['id']]['acsum'])
					$userlist[$idx]['nodeac'] = 1;
				elseif($access[$row['id']]['acsum'] == 0)
					$userlist[$idx]['nodeac'] = 0;
				else
					$userlist[$idx]['nodeac'] = 2;
				if($warning[$row['id']]['warncount'] == $warning[$row['id']]['warnsum'])
					$userlist[$idx]['nodewarn'] = 1;
				elseif($warning[$row['id']]['warnsum'] == 0)
					$userlist[$idx]['nodewarn'] = 0;
				else
					$userlist[$idx]['nodewarn'] = 2;
				if($userlist[$idx]['balance'] > 0)
					$over += $userlist[$idx]['balance'];
				elseif($userlist[$idx]['balance'] < 0)
					$below += $userlist[$idx]['balance'];
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

	function GetUserList($order='username,asc', $state=NULL, $network=NULL, $usergroup=NULL)
	{
		list($order,$direction)=explode(',',$order);

		($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

		switch($order)
		{
			case 'phone':
				$sqlord = 'ORDER BY phone1';
			break;
			case 'id':
				$sqlord = 'ORDER BY users.id';
			break;
			case 'address':
				$sqlord = 'ORDER BY address';
			break;
			case 'email':
				$sqlord = 'ORDER BY email';
			break;
			case 'balance':
				$sqlord = 'ORDER BY balance';
			break;
			case 'gg':
				$sqlord = 'ORDER BY gguin';
			break;
			case 'nip':
				$sqlord = 'ORDER BY nip, pesel';
			break;
			default:
				$sqlord = 'ORDER BY username';
			break;
		}
		
		if($state == 4) {
			$deleted = 1;
			// don't use usergroup and network filtering
			// when user is deleted because we drop group assignments and nodes
			// in DeleteUser()
			$network=NULL;
			$usergroup=NULL;
		}
		else
			$deleted = 0;
			
		$disabled = ($state == 5) ? 1 : 0;
		$indebted = ($state == 6) ? 1 : 0;
		
		if($state>3)
			$state = 0;

		if($network) 
			$net = $this->GetNetworkParams($network);
		
		if($userlist = $this->DB->GetAll( 
				'SELECT users.id AS id, '.$this->DB->Concat('UPPER(lastname)',"' '",'users.name').' AS username, status, email, phone1, users.address, gguin, nip, pesel, zip, city, info, '
				.($network ? 'COALESCE(SUM((type * -2 + 7) * value), 0.00)/(CASE COUNT(DISTINCT nodes.id) WHEN 0 THEN 1 ELSE COUNT(DISTINCT nodes.id) END) AS balance ' : 'COALESCE(SUM((type * -2 + 7) * value), 0.00) AS balance ')
				.'FROM users LEFT JOIN cash ON (users.id=cash.userid AND (type = 3 OR type = 4)) '
				.($network ? 'LEFT JOIN nodes ON (users.id=ownerid) ' : '')
				.($usergroup ? 'LEFT JOIN userassignments ON (users.id=userassignments.userid) ':'')
				.'WHERE deleted = '.$deleted
				.($state !=0 ? ' AND status = '.$state :'') 
				.($network ? ' AND (ipaddr > '.$net['address'].' AND ipaddr < '.$net['broadcast'].')' : '')
				.($usergroup ? ' AND usergroupid='.$usergroup : '') 
				.' GROUP BY users.id, lastname, users.name, status, email, phone1, users.address, gguin, nip, pesel, zip, city, info '
				.($indebted ? ' HAVING SUM((type * -2 + 7) * value) < 0 ' : '')
				.($sqlord !='' ? $sqlord.' '.$direction:'')
				))
		{
			$week = $this->DB->GetAllByKey('SELECT users.id AS id, SUM(value)*4 AS value FROM assignments, tariffs, users WHERE userid = users.id AND tariffid = tariffs.id AND deleted = 0 AND period = 0 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY users.id', 'id');
			$month = $this->DB->GetAllByKey('SELECT users.id AS id, SUM(value) AS value FROM assignments, tariffs, users WHERE userid = users.id AND tariffid = tariffs.id AND deleted = 0 AND period = 1 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY users.id', 'id');
			$quarter = $this->DB->GetAllByKey('SELECT users.id AS id, SUM(value)/3 AS value FROM assignments, tariffs, users WHERE userid = users.id AND tariffid = tariffs.id AND deleted = 0 AND period = 2 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY users.id', 'id');
			$year = $this->DB->GetAllByKey('SELECT users.id AS id, SUM(value)/12 AS value FROM assignments, tariffs, users WHERE userid = users.id AND tariffid = tariffs.id AND deleted = 0 AND period = 3 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY users.id', 'id');

			$access = $this->DB->GetAllByKey('SELECT ownerid AS id, SUM(access) AS acsum, COUNT(access) AS account FROM nodes GROUP BY ownerid','id');
			$warning = $this->DB->GetAllByKey('SELECT ownerid AS id, SUM(warning) AS warnsum, COUNT(warning) AS warncount FROM nodes GROUP BY ownerid','id');
			$userlist2 = NULL;
			foreach($userlist as $idx => $row)
			{
				$userlist[$idx]['tariffvalue'] = $week[$row['id']]['value']+$month[$row['id']]['value']+$quarter[$row['id']]['value']+$year[$row['id']]['value'];
				$userlist[$idx]['account'] = $access[$row['id']]['account'];
				$userlist[$idx]['warncount'] = $warning[$row['id']]['warncount'];

				if($access[$row['id']]['account'] == $access[$row['id']]['acsum'])
					$userlist[$idx]['nodeac'] = 1;
				elseif($access[$row['id']]['acsum'] == 0)
					$userlist[$idx]['nodeac'] = 0;
				else
					$userlist[$idx]['nodeac'] = 2;
				if($warning[$row['id']]['warncount'] == $warning[$row['id']]['warnsum'])
					$userlist[$idx]['nodewarn'] = 1;
				elseif($warning[$row['id']]['warnsum'] == 0)
					$userlist[$idx]['nodewarn'] = 0;
				else
					$userlist[$idx]['nodewarn'] = 2;
					
				if (($disabled && $userlist[$idx]['nodeac'] != 1) || !$disabled)
					if($userlist[$idx]['balance'] > 0)
						$over += $userlist[$idx]['balance'];
					elseif($userlist[$idx]['balance'] < 0)
						$below += $userlist[$idx]['balance'];
				if ($disabled && $userlist[$idx]['nodeac'] != 1)
					$userlist2[] = $userlist[$idx];
			}
			if ($disabled)
				$userlist = $userlist2;
		}

		switch($order)
		{
			case 'tariff':
				foreach($userlist as $idx => $row)
				{
					$tarifftable['idx'][] = $idx;
					$tarifftable['tariffvalue'][] = $row['tariffvalue'];
				}
				if(is_array($tarifftable))
				{
					array_multisort($tarifftable['tariffvalue'],($direction == "desc" ? SORT_DESC : SORT_ASC),$tarifftable['idx']);
					foreach($tarifftable['idx'] as $idx)
						$nuserelist[] = $userlist[$idx];
				}
				$userlist = $nuserelist;
			break;
		}
		$userlist['total']=sizeof($userlist);
		$userlist['state']=$state;
		$userlist['network']=$network;
		$userlist['usergroup']=$usergroup;
		$userlist['order']=$order;
		$userlist['below']=$below;
		$userlist['over']=$over;
		$userlist['direction']=$direction;

		return $userlist;
	}

	function GetUserNodes($id)
	{
		if($result = $this->DB->GetAll('SELECT id, name, mac, ipaddr, inet_ntoa(ipaddr) AS ip, access, warning FROM nodes WHERE ownerid=? ORDER BY name ASC', array($id))){
			$result['total'] = sizeof($result);
			$result['ownerid'] = $id;
		}
		return $result;
	}

	function GetUserBalance($id, $taxvalue='-1')
	{
		if ($taxvalue == '-1')
		{
			$bin = $this->DB->GetOne('SELECT SUM(value) FROM cash WHERE userid=? AND type=3', array($id));
			$bou = $this->DB->GetOne('SELECT SUM(value) FROM cash WHERE userid=? AND type=4', array($id));
		}
		else
			if ($taxvalue == 'zw.')
			{
				$bin = $this->DB->GetOne('SELECT SUM(value) FROM cash WHERE userid=? AND taxvalue IS NULL AND type=3', array($id, $taxvalue));
				$bou = $this->DB->GetOne('SELECT SUM(value) FROM cash WHERE userid=? AND taxvalue IS NULL AND type=4', array($id, $taxvalue));
			}
			else
			{
				$bin = $this->DB->GetOne('SELECT SUM(value) FROM cash WHERE userid=? AND taxvalue=? AND type=3', array($id, $taxvalue));
				$bou = $this->DB->GetOne('SELECT SUM(value) FROM cash WHERE userid=? AND taxvalue=? AND type=4', array($id, $taxvalue));
			}
		return round($bin-$bou,2);
	}

	function GetUserBalanceList($id)
	{
		// wrapper do starego formatu
		if($tslist = $this->DB->GetAll('SELECT cash.id AS id, time, type, value, taxvalue, userid, comment, invoiceid, name AS adminname FROM cash LEFT JOIN admins ON admins.id=adminid WHERE userid=? ORDER BY time', array($id)))
			foreach($tslist as $row)
				foreach($row as $column => $value)
					$saldolist[$column][] = $value;

		if(sizeof($saldolist['id']) > 0)
		{
			foreach($saldolist['id'] as $i => $v)
			{
				($i>0) ? $saldolist['before'][$i] = $saldolist['after'][$i-1] : $saldolist['before'][$i] = 0;

//				$saldolist['adminname'][$i] = $adminslist[$saldolist['adminid'][$i]];
				$saldolist['value'][$i] = round($saldolist['value'][$i],3);

				switch ($saldolist['type'][$i]){

					case '3':
						$saldolist['after'][$i] = round(($saldolist['before'][$i] + $saldolist['value'][$i]),4);
						$saldolist['name'][$i] = 'Wp³ata';
					break;

					case '4':
						$saldolist['after'][$i] = round(($saldolist['before'][$i] - $saldolist['value'][$i]),4);
						$saldolist['name'][$i] = 'Obci±¿enie';
					break;
				}

				$saldolist['date'][$i]=date('Y/m/d H:i',$saldolist['time'][$i]);

				(strlen($saldolist['comment'][$i])<3) ? $saldolist['comment'][$i] = $saldolist['name'][$i] : $saldolist['comment'][$i] = $saldolist['comment'][$i];
			}

			$saldolist['balance'] = $saldolist['after'][sizeof($saldolist['id'])-1];
			$saldolist['total'] = sizeof($saldolist['id']);

		} else 
			$saldolist['balance'] = 0;

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

	function GetUserBalanceListByDate($id, $date=NULL)
	{
		if($tslist = $this->DB->GetAll('SELECT cash.id AS id, time, type, value, taxvalue, userid, comment, invoiceid, name AS adminname FROM cash LEFT JOIN admins ON admins.id=adminid WHERE userid=? ORDER BY time', array($id)))
			foreach($tslist as $row)
				foreach($row as $column => $value)
					$saldolist[$column][] = $value;

		if(sizeof($saldolist['id']) > 0)
		{
			foreach($saldolist['id'] as $i => $v)
			{
				($i>0) ? $saldolist['before'][$i] = $saldolist['after'][$i-1] : $saldolist['before'][$i] = 0;

				$saldolist['value'][$i] = round($saldolist['value'][$i],3);

				switch ($saldolist['type'][$i]){

					case '3':
						$saldolist['after'][$i] = round(($saldolist['before'][$i] + $saldolist['value'][$i]),4);
						$saldolist['name'][$i] = 'wp³ata';
					break;

					case '4':
						$saldolist['after'][$i] = round(($saldolist['before'][$i] - $saldolist['value'][$i]),4);
						$saldolist['name'][$i] = 'obci±¿enie';
					break;
				}

				
				if($saldolist['time'][$i]>=$date['from'] && $saldolist['time'][$i]<=$date['to'])
				{
					$list['id'][] = $saldolist['id'][$i];
					$list['after'][] = $saldolist['after'][$i];
					$list['before'][] = $saldolist['before'][$i];
					$list['value'][] = $saldolist['value'][$i];
					$list['taxvalue'][] = $saldolist['taxvalue'][$i];
					$list['name'][] = $saldolist['name'][$i];
					switch($saldolist['name'][$i])
					{ 
						case 'wp³ata':	$list['summary'] += $saldolist['value'][$i]; break;
						case 'obci±¿enie': $list['summary'] -= $saldolist['value'][$i]; break;
					}	
					$list['date'][] = date('Y/m/d H:i',$saldolist['time'][$i]);
					$list['adminname'][] = $saldolist['adminname'][$i];
					(strlen($saldolist['comment'][$i])<3) ? $list['comment'][] = $saldolist['name'][$i] : $list['comment'][] = $saldolist['comment'][$i];
				}
			}

			$list['balance'] = $saldolist['after'][sizeof($saldolist['id'])-1];
			$list['total'] = sizeof($list['id']);

		} else
			$list['balance'] = 0;

		if($list['total'])
		{
			foreach($list['value'] as $key => $value)
				$list['value'][$key] = $value;
			foreach($list['after'] as $key => $value)
				$list['after'][$key] = $value;
			foreach($list['before'] as $key => $value)
				$list['before'][$key] = $value;
		}

		$list['userid'] = $id;
		return $list;
	}
	
	function UserStats()
	{
		$result['total'] = $this->DB->GetOne('SELECT COUNT(id) FROM users WHERE deleted=0');
		$result['connected'] = $this->DB->GetOne('SELECT COUNT(id) FROM users WHERE status=3 AND deleted=0');
		$result['awaiting'] = $this->DB->GetOne('SELECT COUNT(id) FROM users WHERE status=2 AND deleted=0');
		$result['interested'] = $this->DB->GetOne('SELECT COUNT(id) FROM users WHERE status=1 AND deleted=0');
		$result['debt'] = 0;
		$result['debtvalue'] = 0;
		if($balances = $this->DB->GetCol('SELECT SUM((type * -2 + 7)*value) FROM cash LEFT JOIN users ON userid = users.id WHERE deleted = 0 GROUP BY userid HAVING SUM((type * -2 + 7)*value) < 0'))
		{
			foreach($balances as $idx)
				$result['debtvalue'] -= $idx;
			$result['debt'] = sizeof($balances);
		}	
		return $result;
	}

	/*
	 * Obs³uga grup u¿ytkowników
	*/
	 
	function UsergroupWithUserGet($id)
	{
		return $this->DB->GetOne('SELECT COUNT(userid) FROM userassignments, users WHERE users.id = userid AND usergroupid = ?', array($id));
	}

	function UsergroupAdd($usergroupdata)
	{
		$this->SetTS('usergroups');
		if($this->DB->Execute('INSERT INTO usergroups (name, description) VALUES (?, ?)', array($usergroupdata['name'], $usergroupdata['description'])))
			return $this->DB->GetOne('SELECT id FROM usergroups WHERE name=?', array($usergroupdata['name']));
		else
			return FALSE;
	}

	function UsergroupUpdate($usergroupdata)
	{
		$this->SetTS('usergroups');
		return $this->DB->Execute('UPDATE usergroups SET name=?, description=? WHERE id=?', array($usergroupdata['name'], $usergroupdata['description'], $usergroupdata['id']));
	}

	function UsergroupDelete($id)
	{
		 if (!$this->UsergroupWithUserGet($id))
		 {
			$this->SetTS('usergroups');
			return $this->DB->Execute('DELETE FROM usergroups WHERE id=?', array($id));
		 } else
			return FALSE;
	}

	function UsergroupExists($id)
	{
		return ($this->DB->GetOne('SELECT id FROM usergroups WHERE id=?', array($id)) ? TRUE : FALSE);
	}

	function UsergroupMove($from, $to)
	{
		if ($ids = $this->DB->GetCol('SELECT userassignments.id AS id FROM userassignments, users WHERE userid = users.id AND usergroupid = ?', array($from))) 
		{	
			$this->SetTS('userassignments');
			foreach($ids as $id)
				$this->DB->Execute('UPDATE userassignments SET usergroupid=? WHERE id=? AND usergroupid=?', array($to, $id, $from));
		}
	}

	function UsergroupGetId($name)
	{
		return $this->DB->GetOne('SELECT id FROM usergroups WHERE name=?', array($name));
	}

	function UsergroupGetName($id)
	{
		return $this->DB->GetOne('SELECT name FROM usergroups WHERE id=?', array($id));
	}

	function UsergroupGetAll()
	{
		return $this->DB->GetAll('SELECT id, name, description FROM usergroups ORDER BY name ASC');
	}

	function UsergroupGet($id)
	{
		$result = $this->DB->GetRow('SELECT id, name, description FROM usergroups WHERE id=?', array($id));
		$result['users'] = $this->DB->GetAll('SELECT users.id AS id, COUNT(users.id) AS cnt, '.$this->DB->Concat('UPPER(lastname)',"' '",'name').' AS username FROM userassignments, users WHERE users.id = userid AND usergroupid = ? GROUP BY users.id, username ORDER BY username', array($id));
		$result['userscount'] = sizeof($result['users']);
		$result['count'] = $this->UsergroupWithUserGet($id);
		return $result;
	}

	function UsergroupGetList()
	{
		if($usergrouplist = $this->DB->GetAll('SELECT id, name, description FROM usergroups ORDER BY name ASC'))
		{
			foreach($usergrouplist as $idx => $row)
			{
				$usergrouplist[$idx]['users'] = $this->UsergroupWithUserGet($row['id']);
				$usergrouplist[$idx]['userscount'] = sizeof($this->DB->GetCol('SELECT userid FROM userassignments, users WHERE users.id = userid AND usergroupid = ? GROUP BY userid', array($row['id'])));
				$totalusers += $usergrouplist[$idx]['users'];
				$totalcount += $usergrouplist[$idx]['userscount'];
			}
		}
		$usergrouplist['total'] = sizeof($usergrouplist);
		$usergrouplist['totalusers'] = $totalusers;
		$usergrouplist['totalcount'] = $totalcount;
		
		return $usergrouplist;
	}

	function UsergroupGetForUser($id)
	{
		return $this->DB->GetAll('SELECT usergroups.id AS id, name, description FROM usergroups, userassignments WHERE usergroups.id=usergroupid AND userid=? ORDER BY name ASC', array($id));
	}

	function GetGroupNamesWithoutUser($userid)
	{
		return $this->DB->GetAll('SELECT usergroups.id AS id, name, userid
			FROM usergroups LEFT JOIN userassignments ON (usergroups.id=usergroupid AND userid = ?) 
			GROUP BY usergroups.id, name, userid HAVING userid IS NULL ORDER BY name', array($userid));
	}

	function UserassignmentGetForUser($id)
	{
		return $this->DB->GetAll('SELECT userassignments.id AS id, usergroupid, userid FROM userassignments, usergroups WHERE userid=? AND usergroups.id = usergroupid ORDER BY usergroupid ASC', array($id));
	}

	function UserassignmentDelete($userassignmentdata)
	{
		$this->SetTS('userassignments');
		return $this->DB->Execute('DELETE FROM userassignments WHERE usergroupid=? AND userid=?', array($userassignmentdata['usergroupid'], $userassignmentdata['userid']));
	}

	function UserassignmentAdd($userassignmentdata)
	{
		$this->SetTS('userassignments');
		return $this->DB->Execute('INSERT INTO userassignments (usergroupid, userid) VALUES (?, ?)',
			array($userassignmentdata['usergroupid'], $userassignmentdata['userid']));
	}

	function UserassignmentExist($groupid, $userid)
	{
		return $this->DB->GetOne('SELECT 1 FROM userassignments WHERE usergroupid=? AND userid=?', array($groupid, $userid)); 
	}

	function GetUserWithoutGroupNames($groupid)
	{
		return $this->DB->GetAll('SELECT users.id AS id, '.$this->DB->Concat('UPPER(lastname)',"' '",'name').' AS username, userid
			FROM users LEFT JOIN userassignments ON (users.id = userid AND userassignments.usergroupid = ?) WHERE deleted = 0 
			GROUP BY users.id, userid, lastname, name 
			HAVING userid IS NULL ORDER BY username', array($groupid));
	}

	/*
	 *  Funkcje do obs³ugi rekordów z komputerami
	 */

	function GetNodeOwner($id)
	{
		return $this->DB->GetOne('SELECT ownerid FROM nodes WHERE id=?', array($id));
	}

	function NodeUpdate($nodedata)
	{
		$this->SetTS('nodes');
		return $this->DB->Execute('UPDATE nodes SET name=UPPER(?), ipaddr=inet_aton(?), mac=UPPER(?), netdev=?, moddate=?NOW?, modid=?, access=?, warning=?, ownerid=? WHERE id=?', array($nodedata['name'], $nodedata['ipaddr'], $nodedata['mac'], $nodedata['netdev'], $this->SESSION->id, $nodedata['access'], $nodedata['warning'], $nodedata['ownerid'], $nodedata['id']));
	}

	function DeleteNode($id)
	{
		$this->SetTS('nodes');
		return $this->DB->Execute('DELETE FROM nodes WHERE id=?', array($id));
	}

	function GetNodeNameByMAC($mac)
	{
		return $this->DB->GetOne('SELECT name FROM nodes WHERE mac=?', array($mac));
	}

	function GetNodeIDByIP($ipaddr)
	{
		return $this->DB->GetOne('SELECT id FROM nodes WHERE ipaddr=inet_aton(?)', array($ipaddr));
	}

	function GetNodeIDByMAC($mac)
	{
		return $this->DB->GetOne('SELECT id FROM nodes WHERE mac=?', array($mac));
	}

	function GetNodeIDByName($name)
	{
		return $this->DB->GetOne('SELECT id FROM nodes WHERE name=?', array($name));
	}

	function GetNodeIPByID($id)
	{
		return $this->DB->GetOne('SELECT inet_ntoa(ipaddr) FROM nodes WHERE id=?', array($id));
	}

	function GetNodeMACByID($id)
	{
		return $this->DB->GetOne('SELECT mac FROM nodes WHERE id=?', array($id));
	}

	function GetNodeName($id)
	{
		return $this->DB->GetOne('SELECT name FROM nodes WHERE id=?', array($id));
	}

	function GetNodeNameByIP($ipaddr)
	{
		return $this->DB->GetOne('SELECT name FROM nodes WHERE ipaddr=inet_aton(?)', array($ipaddr));
	}

	function GetNode($id)
	{
		if($result = $this->DB->GetRow('SELECT id, name, ownerid, ipaddr, inet_ntoa(ipaddr) AS ip, mac, access, warning, creationdate, moddate, creatorid, modid, netdev, lastonline FROM nodes WHERE id=?', array($id)))
		{
			$result['createdby'] = $this->GetAdminName($result['creatorid']);
			$result['modifiedby'] = $this->GetAdminName($result['modid']);
			$result['creationdateh'] = date('Y-m-d, H:i',$result['creationdate']);
			$delta = time()-$result['lastonline'];
			if($delta>$this->CONFIG['phpui']['lastonline_limit'])
				$result['lastonlinedate'] .= uptimef($delta).($delta>60 ? ' temu ' : '').'('.date('Y-m-d, H:i',$result['lastonline']).')';
			else
				$result['lastonlinedate'] .= 'aktualnie w³±czony';
			$result['moddateh'] = date('Y-m-d, H:i',$result['moddate']);
			$result['owner'] = $this->GetUsername($result['ownerid']);
			$result['netid'] = $this->GetNetIDByIP($result['ip']);
			$result['netname'] = $this->GetNetworkName($result['netid']);
			$result['producer'] = get_producer($result['mac']);
			$result['devicename'] = $this->GetNetDevName($result['netdevid']);
			return $result;
		}else
			return FALSE;
	}

	function GetNodeList($order='name,asc')
	{
		if($order=='')
			$order='name,asc';

		list($order,$direction) = explode(',',$order);

		($direction=='desc') ? $direction = 'desc' : $direction = 'asc';

		switch($order)
		{
			case 'name':
				$sqlord = ' ORDER BY nodes.name';
			break;
			case 'id':
				$sqlord = ' ORDER BY id';
			break;
			case 'mac':
				$sqlord = ' ORDER BY mac';
			break;
			case 'ip':
				$sqlord = ' ORDER BY ipaddr';
			break;
			case 'ownerid':
				$sqlord = ' ORDER BY ownerid';
			break;
			case 'owner':
				$sqlord = ' ORDER BY owner';
			break;
		}

		if($nodelist = $this->DB->GetAll('SELECT nodes.id AS id, ipaddr, inet_ntoa(ipaddr) AS ip, mac, nodes.name AS name, ownerid, access, warning, netdev, '.$this->DB->Concat('UPPER(lastname)',"' '",'users.name').' AS owner, lastonline FROM nodes, users WHERE ownerid = users.id AND ownerid > 0'.($sqlord != '' ? $sqlord.' '.$direction : '')))
		{
			foreach($nodelist as $idx => $row)
			{
				($row['access']) ? $totalon++ : $totaloff++;
			}
		}

		$nodelist['total'] = sizeof($nodelist);
		$nodelist['order'] = $order;
		$nodelist['direction'] = $direction;
		$nodelist['totalon'] = $totalon;
		$nodelist['totaloff'] = $totaloff;

		return $nodelist;
	}

	function SearchNodeList($args, $order='name,asc')
	{
		if($order=='')
			$order='name,asc';
		
		list($order,$direction) = explode(',',$order);
		
		($direction=='desc') ? $direction = 'desc' : $direction = 'asc';
		
		switch($order)
		{
			case 'name':
				$sqlord = ' ORDER BY name';
			break;
			case 'id':
				$sqlord = ' ORDER BY id';
			break;
			case 'mac':
				$sqlord = ' ORDER BY mac';
			break;
			case 'ip':		
				$sqlord = ' ORDER BY ipaddr';
			break;
			case 'ownerid':
				$sqlord = ' ORDER BY ownerid';
			break;
		}
		
		foreach($args as $idx => $value)
		{
			if($value!='')	
			{
				switch($idx)
				{
					case 'ipaddr' :
						$searchargs[] = "inet_ntoa(ipaddr) ?LIKE? '%".trim($value)."%'";
					break;
					default :
						$searchargs[] = $idx." ?LIKE? '%".$value."%'";
				}
			}
		}
		
		if($searchargs)
			$searchargs = ' WHERE ownerid > 0 AND '.implode(' AND ',$searchargs);
		
		if($username = $this->DB->GetAll('SELECT id, '.$this->DB->Concat('UPPER(lastname)',"' '",'name').' AS username FROM users'))
			foreach($username as $idx => $row)
				$usernames[$row['id']] = $row['username'];
		
		if($nodelist = $this->DB->GetAll('SELECT id, ipaddr, inet_ntoa(ipaddr) AS ip, mac, name, ownerid, access, warning FROM nodes '.$searchargs.' '.($sqlord != '' ? $sqlord.' '.$direction : '')))
		{
			foreach($nodelist as $idx => $row)
			{
				$nodelist[$idx]['owner'] = $usernames[$row['ownerid']];
				($row['access']) ? $totalon++ : $totaloff++;
			}

			switch($order)
			{
				case 'owner':
					foreach($nodelist as $idx => $row)					
					{
						$ownertable['idx'][] = $idx;
						$ownertable['owner'][] = $row['owner'];
					}
					array_multisort($ownertable['owner'],($direction == "desc" ? SORT_DESC : SORT_ASC),$ownertable['idx']);
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

		return FALSE;
	}

	function NodeSet($id)
	{
		$this->SetTS('nodes');
		if($this->DB->GetOne('SELECT access FROM nodes WHERE id=?', array($id)) == 1 )
			return $this->DB->Execute('UPDATE nodes SET access=0 WHERE id=?', array($id));
		else
			return $this->DB->Execute('UPDATE nodes SET access=1 WHERE id=?', array($id));
	}

	function NodeSetU($id,$access=FALSE)
	{
		$this->SetTS('nodes');
		if($access)
			return $this->DB->Execute('UPDATE nodes SET access=1 WHERE ownerid=?', array($id));
		else
			return $this->DB->Execute('UPDATE nodes SET access=0 WHERE ownerid=?', array($id));
	}		

	function NodeSetWarn($id,$warning=FALSE)
	{
		$this->SetTS('nodes');
		if($warning)
			return $this->DB->Execute('UPDATE nodes SET warning=1 WHERE id=?', array($id));
		else
			return $this->DB->Execute('UPDATE nodes SET warning=0 WHERE id=?', array($id));
	}

	function NodeSwitchWarn($id)
	{
		$this->SetTS('nodes');
		if($this->DB->GetOne('SELECT warning FROM nodes WHERE id=?', array($id)) == 1 )
			return $this->DB->Execute('UPDATE nodes SET warning=0 WHERE id=?', array($id));
		else
			return $this->DB->Execute('UPDATE nodes SET warning=1 WHERE id=?', array($id));
	}

	function NodeSetWarnU($id,$warning=FALSE)
	{
		$this->SetTS('nodes');
		if($warning)
			return $this->DB->Execute('UPDATE nodes SET warning=1 WHERE ownerid=?', array($id));
		else
			return $this->DB->Execute('UPDATE nodes SET warning=0 WHERE ownerid=?', array($id));
	}		

	function IPSetU($netdev, $access=FALSE)
	{
		$this->SetTS('nodes');
		if($access)
			return $this->DB->Execute('UPDATE nodes SET access=1 WHERE netdev=? AND ownerid=0', array($netdev));
		else
			return $this->DB->Execute('UPDATE nodes SET access=0 WHERE netdev=? AND ownerid=0', array($netdev));
	}
	
	function NodeAdd($nodedata)
	{
		$this->SetTS('nodes');
		if($this->DB->Execute('INSERT INTO nodes (name, mac, ipaddr, ownerid, creatorid, creationdate, access, warning) VALUES (?, ?, inet_aton(?), ?, ?, ?NOW?, ?, ?)', array(strtoupper($nodedata['name']),strtoupper($nodedata['mac']),$nodedata['ipaddr'],$nodedata['ownerid'],$this->SESSION->id, $nodedata['access'], $nodedata['warning'])))
			return $this->DB->GetOne('SELECT MAX(id) FROM nodes');
		else
			return FALSE;
	}

	function NodeExists($id)
	{
		return ($this->DB->GetOne('SELECT id FROM nodes WHERE id=?', array($id))?TRUE:FALSE);
	}

	function NodeStats()
	{
		$result['connected'] = $this->DB->GetOne('SELECT COUNT(id) FROM nodes WHERE access=1 AND ownerid>0');
		$result['disconnected'] = $this->DB->GetOne('SELECT COUNT(id) FROM nodes WHERE access=0 AND ownerid>0');
		$result['online'] = $this->DB->GetOne('SELECT COUNT(id) FROM nodes WHERE ?NOW?-lastonline < ? AND ownerid>0', array($this->CONFIG['phpui']['lastonline_limit']));
		$result['total'] = $result['connected'] + $result['disconnected'];
		return $result;
	}

	function GetNetdevLinkedNodes($id)
	{
		return $this->DB->GetAll('SELECT nodes.id AS id, nodes.name AS name, ipaddr, inet_ntoa(ipaddr) AS ip, netdev, '.$this->DB->Concat('UPPER(lastname)',"' '",'users.name').' AS owner FROM nodes, users WHERE ownerid = users.id AND netdev=? AND ownerid > 0 ORDER BY nodes.name ASC', array($id));
	}

	function NetDevLinkNode($id,$netid)
	{
		if($netid != 0)
		{
			$netdev = $this->GetNetDev($netid);
			if( $this->GetNodeOwner($id) )
				if( $netdev['takenports'] >= $netdev['ports'])
					return FALSE;
		}
		
		$this->DB->Execute('UPDATE nodes SET netdev='.$netid.' WHERE id='.$id);
		$this->SetTS('nodes');
		return TRUE;
	}

	/*
	 *  Obs³uga taryf i finansów
	 */

	function GetUserTariffsValue($id)
	{
		return $this->DB->GetOne('SELECT sum(value) FROM assignments, tariffs WHERE tariffid = tariffs.id AND userid=? AND suspended=0 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0)', array($id));
	}

	function GetUserAssignments($id)
	{
		if($assignments = $this->DB->GetAll('SELECT assignments.id AS id, tariffid, userid, period, at, suspended, value, uprate, upceil, downceil, downrate, name, invoice, datefrom, dateto FROM assignments LEFT JOIN tariffs ON (tariffid=tariffs.id) WHERE userid=? ORDER BY datefrom ASC', array($id)))
		{
			foreach($assignments as $idx => $row)
			{
				switch($row['period'])
				{
					case 0:
						$row['period'] = 'co tydzieñ';
						$dni = array('poniedzia³ek', 'wtorek', '¶roda', 'czwartek', 'pi±tek', 'sobota', 'niedziela');
						$row['at'] = $dni[$row['at'] - 1];
					break;
					
					case 1:
						$row['period'] = 'co miesi±c';
					break;
					
					case 2:
						$row['period'] = 'co kwarta³';
						$row['at'] = sprintf("%02d/%02d", $row['at']%100, $row['at']/100+1);
					break;
					
					case 3:
						$row['period'] = 'co rok';
						$row['at'] = date('d/m',($row['at']-1)*86400);
					break;
				}

				$assignments[$idx] = $row;
			}
		}

		return $assignments;
	}

	function DeleteAssignment($id,$balance = FALSE)
	{
		$this->SetTS('assignments');
		return $this->DB->Execute('DELETE FROM assignments WHERE id=?', array($id));
	}

	function AddAssignment($assignmentdata)
	{
		$this->SetTS('assignments');
		return $this->DB->Execute('INSERT INTO assignments (tariffid, userid, period, at, invoice, datefrom, dateto) VALUES (?, ?, ?, ?, ?, ?, ?)', array($assignmentdata['tariffid'], $assignmentdata['userid'], $assignmentdata['period'], $assignmentdata['at'], $assignmentdata['invoice'], $assignmentdata['datefrom'], $assignmentdata['dateto']));
	}

	function SuspendAssignment($id,$suspend = TRUE)
	{
		$this->SetTS('assignments');
		if($suspend)
			return $this->DB->Execute('UPDATE assignments SET suspended=1 WHERE id=?', array($id));
		else
			return $this->DB->Execute('UPDATE assignments SET suspended=0 WHERE id=?', array($id));
	}

	function AddInvoice($invoice)
	{
		$cdate = time();
		$this->SetTS('invoices');
		$this->SetTS('invoicecontents');
		$number = $this->DB->GetOne('SELECT MAX(number) FROM invoices WHERE cdate >= ? AND cdate <= ?', array(mktime(0, 0, 0, 1, 1, date('Y',$cdate)), mktime(23, 59, 59, 12, 31, date('Y',$cdate))));
		$number++;
		$this->DB->Execute('INSERT INTO invoices (number, cdate, paytime, paytype, customerid, name, address, nip, pesel, zip, city, phone, finished) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)', array($number, $cdate, $invoice['invoice']['paytime'], $invoice['invoice']['paytype'], $invoice['customer']['id'], $invoice['customer']['username'], $invoice['customer']['address'], $invoice['customer']['nip'], $invoice['customer']['pesel'], $invoice['customer']['zip'], $invoice['customer']['city'], $invoice['customer']['phone1']));
		$iid = $this->DB->GetOne('SELECT id FROM invoices WHERE number = ? AND cdate = ?', array($number,$cdate));
		foreach($invoice['contents'] as $idx => $item)
		{
			$item['valuebrutto'] = str_replace(',','.',$item['valuebrutto']);
			$item['count'] = str_replace(',','.',$item['count']);
			if ($item['taxvalue'] == 'zw.')
				$item['taxvalue'] = '';
			else
				$item['taxvalue'] = str_replace(',','.',$item['taxvalue']);
			
			if ($item['taxvalue'] == '')
				$this->DB->Execute('INSERT INTO invoicecontents (invoiceid, value, taxvalue, pkwiu, content, count, description, tariffid) VALUES (?, ?, NULL, ?, ?, ?, ?, ?)', array($iid, $item['valuebrutto'], $item['pkwiu'], $item['jm'], $item['count'], $item['name'], $item['tariffid']));
			else
				$this->DB->Execute('INSERT INTO invoicecontents (invoiceid, value, taxvalue, pkwiu, content, count, description, tariffid) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', array($iid, $item['valuebrutto'], $item['taxvalue'], $item['pkwiu'], $item['jm'], $item['count'], $item['name'], $item['tariffid']));
			$this->AddBalance(array('type' => 4, 'value' => $item['valuebrutto']*$item['count'], 'taxvalue' => $item['taxvalue'], 'userid' => $invoice['customer']['id'], 'comment' => $item['name'], 'invoiceid' => $iid));
		}
		return $iid;
	}

	function InvoicesReport($from, $to)
	{
		if($result = $this->DB->GetAll('SELECT id, number, cdate, customerid, name, address, zip, city, nip, pesel, taxvalue, SUM(value*count) AS value FROM invoices LEFT JOIN invoicecontents ON invoiceid = id WHERE finished = 1 AND (cdate BETWEEN ? AND ?) GROUP BY id, number, taxvalue, cdate, customerid, name, address, zip, city, nip, pesel, finished ORDER BY cdate ASC', array($from, $to)))
		{
			foreach($result as $idx => $row)
			{
				$id = $row['id'];
				$value = sprintf('%0.2f', $row['value']);
				$list[$id]['custname'] = $row['name'];
				$list[$id]['custaddress'] = $row['zip']." ".$row['city'].', '.$row['address'];
				$list[$id]['nip'] = ($row['nip'] ? 'NIP '.$row['nip'] : ($row['pesel'] ? 'PESEL '.$row['pesel'] : ''));
				$list[$id]['number'] = $row['number'];
				$list[$id]['cdate'] = $row['cdate'];
				$list[$id]['year'] = date('Y',$row['cdate']);
				$list[$id]['month'] = date('m',$row['cdate']);
				$list[$id]['brutto'] += $value;
				$list['sum']['brutto'] += $value;
				if ($row['taxvalue'] == '')
				{
					$list[$id]['valfree'] += $value;
					$list['sum']['valfree'] += $value;
				}
				else
					switch(round($row['taxvalue'],1))
					{
					    case '0.0':
						    $list[$id]['val0'] += $value;
						    $list['sum']['val0'] += $value;
					    break;
					    case '7.0':
						    $val = sprintf('%0.2f', $value/1.07);
						    $list[$id]['val7'] += $val;
						    $list[$id]['tax7'] += $value-$val;
						    $list[$id]['tax'] += $value-$val;
						    $list['sum']['val7'] += $val;
						    $list['sum']['tax7'] += $value-$val;
						    $list['sum']['tax'] += $value-$val;
					    break;
					    case '22.0':
					    	    $val = sprintf('%0.2f', $value/1.22);
						    $list[$id]['val22'] += $val;
						    $list[$id]['tax22'] += $value-$val;
						    $list[$id]['tax'] += $value-$val;
						    $list['sum']['val22'] += $val;
						    $list['sum']['tax22'] += $value-$val;
						    $list['sum']['tax'] += $value-$val;
					    break;
					}
			}
		}
		return $list;
	}

	function IsInvoicePaid($invoiceid)
	{
		return $this->DB->GetOne('SELECT SUM(CASE type WHEN 3 THEN value ELSE -value END) FROM cash WHERE invoiceid=?', array($invoiceid)) >= 0 ? TRUE : FALSE;
	}

	function GetInvoicesList()
	{
		if($result = $this->DB->GetAll('SELECT id, number, cdate, customerid, name, address, zip, city, finished, SUM(value*count) AS value, COUNT(invoiceid) AS count FROM invoices, invoicecontents WHERE invoiceid = id AND finished = 1 GROUP BY id, number, cdate, customerid, name, address, zip, city, finished ORDER BY cdate ASC'))
		{
			$inv_paid = $this->DB->GetAllByKey('SELECT invoiceid AS id, SUM(CASE type WHEN 3 THEN value ELSE -value END) AS sum FROM cash WHERE invoiceid!=0 GROUP BY invoiceid','id');
			foreach($result as $idx => $row)
			{
				$result[$idx]['year'] = date('Y',$row['cdate']);
				$result[$idx]['month'] = date('m',$row['cdate']);
				$result[$idx]['paid'] = ( $inv_paid[$row['id']]['sum'] >=0 ? TRUE : FALSE );
			}
			$result['startdate'] = $this->DB->GetOne('SELECT MIN(cdate) FROM invoices');
			$result['enddate'] = $this->DB->GetOne('SELECT MAX(cdate) FROM invoices');
		}
		return $result;
	}

	function GetUnfishedInvoices()
	{
		if($result = $this->DB->GetAll('SELECT id, number, cdate, customerid, name, address, zip, city, finished, SUM(value) AS value, COUNT(invoiceid) AS count FROM invoices LEFT JOIN invoicecontents ON invoiceid = id WHERE finished = 0 GROUP BY id, number, cdate, customerid, name, address, zip, city, finished ORDER BY cdate ASC'))
		{
			foreach($result as $idx => $row)
			{
				$result[$idx]['year'] = date('Y',$row['cdate']);
				$result[$idx]['month'] = date('m',$row['cdate']);
				$result[$idx]['paid'] = FALSE;
			}
		}
		return $result;
	}

	function GetInvoiceContent($invoiceid)
	{
		if($result = $this->DB->GetRow('SELECT id, number, name, customerid, address, zip, city, phone, nip, pesel, cdate, paytime, paytype, finished FROM invoices WHERE id=?', array($invoiceid)))
		{
			if($result['content'] = $this->DB->GetAll('SELECT value, taxvalue, pkwiu, content, count, description, tariffid FROM invoicecontents WHERE invoiceid=?', array($invoiceid)))
				foreach($result['content'] as $idx => $row)
				{
					$result['content'][$idx]['basevalue'] = sprintf("%0.2f",($row['value'] / (100 + $row['taxvalue']) * 100));
					$result['content'][$idx]['totalbase'] = $result['content'][$idx]['basevalue'] * $row['count'];
					$result['content'][$idx]['totaltax'] = ($row['value'] - $result['content'][$idx]['basevalue']) * $row['count'];
					$result['content'][$idx]['total'] = $row['value'] * $row['count'];
					$result['totalbase'] += $result['content'][$idx]['totalbase'];
					$result['totaltax'] += $result['content'][$idx]['totaltax'];
					$result['taxest'][$row['taxvalue']]['base'] += $result['content'][$idx]['totalbase'];
					$result['taxest'][$row['taxvalue']]['total'] += $result['content'][$idx]['total'];
					$result['taxest'][$row['taxvalue']]['tax'] += $result['content'][$idx]['totaltax'];
					$result['taxest'][$row['taxvalue']]['taxvalue'] = $row['taxvalue'];
					$result['total'] += $result['content'][$idx]['total'];
					
				}
			$result['pdate'] = $result['cdate'] + ($result['paytime'] * 86400);
			$result['totalg'] = ($result['total'] - floor($result['total'])) * 100;
			$result['year'] = date('Y',$result['cdate']);
			$result['month'] = date('m',$result['cdate']);
			$result['paid'] = $this->IsInvoicePaid($invoiceid);
			return $result;
		}
		else
			return FALSE;
	}

	function GetTariffList()
	{
		if($tarifflist = $this->DB->GetAll('SELECT id, name, value, taxvalue, pkwiu, description, uprate, downrate, upceil, downceil, climit, plimit FROM tariffs ORDER BY name ASC'))
		{
			$week = $this->DB->GetAllByKey('SELECT COUNT(userid) AS count, tariffid, SUM(value)*4 AS value FROM assignments, tariffs, users WHERE userid = users.id AND tariffid = tariffs.id AND deleted = 0 AND status = 3 AND period = 0 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY tariffid', 'tariffid');
			$month = $this->DB->GetAllByKey('SELECT COUNT(userid) AS count, tariffid, SUM(value) AS value FROM assignments, tariffs, users WHERE userid = users.id AND tariffid = tariffs.id AND deleted = 0 AND status = 3 AND period = 1 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY tariffid', 'tariffid');
			$quarter = $this->DB->GetAllByKey('SELECT COUNT(userid) AS count, tariffid, SUM(value)/3 AS value FROM assignments, tariffs, users WHERE userid = users.id AND tariffid = tariffs.id AND deleted = 0 AND status = 3 AND period = 2 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY tariffid', 'tariffid');
			$year = $this->DB->GetAllByKey('SELECT COUNT(userid) AS count, tariffid, SUM(value)/12 AS value FROM assignments, tariffs, users WHERE userid = users.id AND tariffid = tariffs.id AND deleted = 0 AND status = 3 AND period = 3 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY tariffid', 'tariffid');

			foreach($tarifflist as $idx => $row)
			{
				$tarifflist[$idx]['users'] = $this->GetUsersWithTariff($row['id']);
				$tarifflist[$idx]['userscount'] = sizeof($this->DB->GetCol("SELECT userid FROM assignments, users WHERE users.id = userid AND deleted = 0 AND tariffid = ? GROUP BY userid", array($row['id'])));
				// count of users with 'active' assignment
				$tarifflist[$idx]['assignmentcount'] =  $week[$row['id']]['count'] + $month[$row['id']]['count'] + $quarter[$row['id']]['count'] + $year[$row['id']]['count'];
				// avg monthly income
				$tarifflist[$idx]['income'] = $week[$row['id']]['value'] + $month[$row['id']]['value'] + $quarter[$row['id']]['value'] + $year[$row['id']]['value'];
				$totalincome += $tarifflist[$idx]['income'];
				$totalusers += $tarifflist[$idx]['users'];
				$totalcount += $tarifflist[$idx]['userscount'];
				$totalassignmentcount += $tarifflist[$idx]['assignmentcount'];
			}
		}
		$tarifflist['total'] = sizeof($tarifflist);
		$tarifflist['totalincome'] = $totalincome;
		$tarifflist['totalusers'] = $totalusers;
		$tarifflist['totalcount'] = $totalcount;
		$tarifflist['totalassignmentcount'] = $totalassignmentcount;
		
		return $tarifflist;
	}

	function TariffMove($from, $to)
	{
		$this->SetTS('assignments');
		$ids = $this->DB->GetCol('SELECT assignments.id AS id FROM assignments, users WHERE userid = users.id AND deleted = 0 AND tariffid = ?', array($from));
		foreach($ids as $id)
			$this->DB->Execute('UPDATE assignments SET tariffid=? WHERE id=? AND tariffid=?', array($to, $id, $from));
	}

	function GetTariffIDByName($name)
	{
		return $this->DB->GetOne('SELECT id FROM tariffs WHERE name=?', array($name));
	}

	function TariffAdd($tariffdata)
	{
		$this->SetTS('tariffs');
		if($tariffdata['taxvalue'] == '')
			$result = $this->DB->Execute('INSERT INTO tariffs (name, description, value, taxvalue, pkwiu, uprate, downrate, upceil, downceil, climit, plimit)
				VALUES (?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?)',
				array(
					$tariffdata['name'],
					$tariffdata['description'],
					$tariffdata['value'],
					$tariffdata['pkwiu'],
					$tariffdata['uprate'],
					$tariffdata['downrate'],
					$tariffdata['upceil'],
					$tariffdata['downceil'],
					$tariffdata['climit'],
					$tariffdata['plimit']
				)
			);
		else
			$result = $this->DB->Execute('INSERT INTO tariffs (name, description, value, taxvalue, pkwiu, uprate, downrate, upceil, downceil, climit, plimit)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
				array(
					$tariffdata['name'],
					$tariffdata['description'],
					$tariffdata['value'],
					$tariffdata['taxvalue'],
					$tariffdata['pkwiu'],
					$tariffdata['uprate'],
					$tariffdata['downrate'],
					$tariffdata['upceil'],
					$tariffdata['downceil'],
					$tariffdata['climit'],
					$tariffdata['plimit']
				)
			);
		if ($result)
			return $this->GetTariffIDByName($tariffdata['name']);
		else
			return FALSE;
	}

	function TariffUpdate($tariff)
	{
		$this->SetTS('tariffs');
		if ($tariff['taxvalue'] == '')
			return $this->DB->Execute('UPDATE tariffs SET name=?, description=?, value=?, taxvalue=NULL, pkwiu=?, uprate=?, downrate=?, upceil=?, downceil=?, climit=?, plimit=? WHERE id=?', array($tariff['name'], $tariff['description'], $tariff['value'], $tariff['pkwiu'], $tariff['uprate'], $tariff['downrate'], $tariff['upceil'], $tariff['downceil'], $tariff['climit'], $tariff['plimit'], $tariff['id']));
		else
			return $this->DB->Execute('UPDATE tariffs SET name=?, description=?, value=?, taxvalue=?, pkwiu=?, uprate=?, downrate=?, upceil=?, downceil=?, climit=?, plimit=? WHERE id=?', array($tariff['name'], $tariff['description'], $tariff['value'], $tariff['taxvalue'], $tariff['pkwiu'], $tariff['uprate'], $tariff['downrate'], $tariff['upceil'], $tariff['downceil'], $tariff['climit'], $tariff['plimit'], $tariff['id']));
	}

	function TariffDelete($id)
	{
		 if (!$this->GetUsersWithTariff($id))
		 {
			$this->SetTS('tariffs');
			return $this->DB->Execute('DELETE FROM tariffs WHERE id=?', array($id));
		 } else
			return FALSE;
	}

	function GetTariffValue($id)
	{
		return $this->DB->GetOne('SELECT value FROM tariffs WHERE id=?', array($id));
	}

	function GetTariffName($id)
	{
		return $this->DB->GetOne('SELECT name FROM tariffs WHERE id=?', array($id));
	}

	function GetTariff($id)
	{
		$result = $this->DB->GetRow('SELECT id, name, value, taxvalue, pkwiu, description, uprate, downrate, upceil, downceil, climit, plimit FROM tariffs WHERE id=?', array($id));
		$result['users'] = $this->DB->GetAll('SELECT users.id AS id, COUNT(users.id) AS cnt, '.$this->DB->Concat('upper(lastname)',"' '",'name').' AS username FROM assignments, users WHERE users.id = userid AND deleted = 0 AND tariffid = ? GROUP BY users.id, username', array($id));
		
		$week = $this->DB->GetRow('SELECT COUNT(userid) AS count, SUM(value)*4 AS value FROM assignments, tariffs, users WHERE userid = users.id AND tariffid = tariffs.id AND deleted = 0 AND status = 3 AND period = 0 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) AND tariffid = ?', array($id));
		$month = $this->DB->GetRow('SELECT COUNT(userid) AS count, SUM(value) AS value FROM assignments, tariffs, users WHERE userid = users.id AND tariffid = tariffs.id AND deleted = 0 AND status = 3 AND period = 1 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) AND tariffid = ?', array($id));
		$quarter = $this->DB->GetRow('SELECT COUNT(userid) AS count, SUM(value)/3 AS value FROM assignments, tariffs, users WHERE userid = users.id AND tariffid = tariffs.id AND deleted = 0 AND status = 3 AND period = 2 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) AND tariffid = ?', array($id));
		$year = $this->DB->GetRow('SELECT COUNT(userid) AS count, SUM(value)/12 AS value FROM assignments, tariffs, users WHERE userid = users.id AND tariffid = tariffs.id AND deleted = 0 AND status = 3 AND period = 3 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) AND tariffid = ?', array($id));
		
		$result['userscount'] = sizeof($result['users']);
		$result['count'] = $this->GetUsersWithTariff($id);
		// count of users with 'active' assignment
		$result['assignmentcount'] =  $week['count'] + $month['count'] + $quarter['count'] + $year['count'];
		// avg monthly income
		$result['totalval'] = $week['value'] + $month['value'] + $quarter['value'] + $year['value'];

		$result['rows'] = ceil(sizeof($result['users'])/2);
		return $result;
	}

	function GetTariffs()
	{
		return $this->DB->GetAll('SELECT id, name, value, uprate, downrate, upceil, downceil, climit, plimit, taxvalue, pkwiu FROM tariffs ORDER BY value DESC');
	}

	function TariffExists($id)
	{
		return ($this->DB->GetOne('SELECT id FROM tariffs WHERE id=?', array($id))?TRUE:FALSE);
	}

	function SetBalanceZero($user_id)
	{
		$this->SetTS('cash');
		
		$stan = array(
				'22.0' => $this->GetUserBalance($user_id, '22.0'),
				'7.0' => $this->GetUserBalance($user_id, '7.0'),
				'0.0' => $this->GetUserBalance($user_id, '0.0'),
				'zw.' => $this->GetUserBalance($user_id, 'zw.')
		);
		asort($stan);
		
		foreach($stan as $key => $val)
		{
			if(($balance = $this->GetUserBalance($user_id)) >= 0)
				break;
		
			if($balance > $val)
				$val = -($balance);
			else		
				$val = -$val;
	
			if ($key == 'zw.')
				$ret[$key] = $this->DB->Execute('INSERT INTO cash (time, adminid, type, value, taxvalue, userid, comment) VALUES (?NOW?, ?, ?, ?, NULL, ?, ?)', array($this->SESSION->id, 3 , round($val,2) , $user_id, 'Rozliczono'));
			else
				$ret[$key] = $this->DB->Execute('INSERT INTO cash (time, adminid, type, value, taxvalue, userid, comment) VALUES (?NOW?, ?, ?, ?, ?, ?, ?)', array($this->SESSION->id, 3 , round($val,2) , $key, $user_id, 'Rozliczono'));
		}
		return $ret;
	}

	function AddBalance($addbalance)
	{
		$this->SetTS('cash');
		if($addbalance['time'])
			if($addbalance['taxvalue'] == '')
				return $this->DB->Execute('INSERT INTO cash (time, adminid, type, value, taxvalue, userid, comment, invoiceid) VALUES (?, ?, ?, ?, NULL, ?, ?, ?)', array($addbalance['time'],$this->SESSION->id, $addbalance['type'], round($addbalance['value'],2), $addbalance['userid'], $addbalance['comment'], ($addbalance['invoiceid'] ? $addbalance['invoiceid'] : 0)));
			else
				return $this->DB->Execute('INSERT INTO cash (time, adminid, type, value, taxvalue, userid, comment, invoiceid) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', array($addbalance['time'],$this->SESSION->id, $addbalance['type'], round($addbalance['value'],2), round($addbalance['taxvalue'],2), $addbalance['userid'], $addbalance['comment'], ($addbalance['invoiceid'] ? $addbalance['invoiceid'] : 0)));
		else
			if($addbalance['taxvalue'] == '')
				return $this->DB->Execute('INSERT INTO cash (time, adminid, type, value, taxvalue, userid, comment, invoiceid) VALUES (?NOW?, ?, ?, ?, NULL, ?, ?, ?)', array($this->SESSION->id, $addbalance['type'], round($addbalance['value'],2), $addbalance['userid'], $addbalance['comment'], ($addbalance['invoiceid'] ? $addbalance['invoiceid'] : 0)));
			else
				return $this->DB->Execute('INSERT INTO cash (time, adminid, type, value, taxvalue, userid, comment, invoiceid) VALUES (?NOW?, ?, ?, ?, ?, ?, ?, ?)', array($this->SESSION->id, $addbalance['type'], round($addbalance['value'],2), round($addbalance['taxvalue'],2), $addbalance['userid'], $addbalance['comment'], ($addbalance['invoiceid'] ? $addbalance['invoiceid'] : 0)));
	}

	function DelBalance($id)
	{
		$this->SetTS('cash');
		return $this->DB->Execute('DELETE FROM cash WHERE id=?', array($id));
	}
	
	function GetBalanceList()
	{
		$adminlist = $this->DB->GetAllByKey('SELECT id, name FROM admins','id');
		$userslist = $this->DB->GetAllByKey('SELECT id, '.$this->DB->Concat('UPPER(lastname)',"' '",'name').' AS username FROM users','id');
		if($balancelist = $this->DB->GetAll('SELECT id, time, adminid, type, value, taxvalue, userid, comment, invoiceid FROM cash ORDER BY time ASC'))
		{
			foreach($balancelist as $idx => $row)
			{
				$balancelist[$idx]['admin'] = $adminlist[$row['adminid']]['name'];
				$balancelist[$idx]['value'] = $row['value'];
				$balancelist[$idx]['taxvalue'] = $row['taxvalue'];
				$balancelist[$idx]['username'] = $userslist[$row['userid']]['username'];
				$balancelist[$idx]['before'] = $balancelist[$idx-1]['after'];

				switch($row['type'])
				{
					case 1:
						$balancelist[$idx]['type'] = 'przychód';
						$balancelist[$idx]['after'] = $balancelist[$idx]['before'] + $balancelist[$idx]['value'];
						$balancelist['income'] = $balancelist['income'] + $balancelist[$idx]['value'];
					break;
					case 2:
						$balancelist[$idx]['type'] = 'rozchód';
						$balancelist[$idx]['after'] = $balancelist[$idx]['before'] - $balancelist[$idx]['value'];
						$balancelist['expense'] = $balancelist['expense'] + $balancelist[$idx]['value'];
					break;
					case 3:
						$balancelist[$idx]['type'] = 'wp³ata u¿';
						$balancelist[$idx]['after'] = $balancelist[$idx]['before'] + $balancelist[$idx]['value'];
						$balancelist['incomeu'] = $balancelist['incomeu'] + $balancelist[$idx]['value'];
					break;
					case 4:
						$balancelist[$idx]['type'] = 'obci±¿enie u¿';
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

	function GetIncomeList($date)
	{
		return $this->DB->GetAll('SELECT floor(time/86400)*86400 AS date,
			SUM(CASE taxvalue WHEN 22.00 THEN value ELSE 0 END) AS tax22,
			SUM(CASE taxvalue WHEN 7.00 THEN value ELSE 0 END) AS tax7,
			SUM(CASE taxvalue WHEN 0.00 THEN value ELSE 0 END) AS tax0,
			SUM(CASE WHEN taxvalue IS NULL THEN value ELSE 0 END) AS taxfree 
			FROM cash WHERE (type=1 OR type=3) AND time>=? AND time<=? AND invoiceid=0
			GROUP BY date ORDER BY date ASC',
			array($date['from'], $date['to']));
	}

	function GetTotalIncomeList($date)
	{
		return $this->DB->GetRow('SELECT
			SUM(CASE taxvalue WHEN 22.00 THEN value ELSE 0 END) AS totaltax22,
			SUM(CASE taxvalue WHEN 7.00 THEN value ELSE 0 END) AS totaltax7,
			SUM(CASE taxvalue WHEN 0.00 THEN value ELSE 0 END) AS totaltax0,
			SUM(CASE WHEN taxvalue IS NULL THEN value ELSE 0 END) AS totaltaxfree FROM cash
			WHERE (type=1 OR type=3) AND time>=? AND time<=? AND invoiceid=0',
			array($date['from'], $date['to']));
	}

	/*
	*	Obs³uga op³at sta³ych
	*/

	function GetPaymentList()
	{
		if ($paymentlist = $this->DB->GetAll('SELECT id, name, creditor, value, period, at, description FROM payments ORDER BY name ASC'))
			foreach($paymentlist as $idx => $row)
			{
				switch($row['period'])
				{
					case 0:
				    		switch($row['at'])
						{
							case 1: $row['payday'] = 'co tydzieñ (pon)'; break;
							case 2: $row['payday'] = 'co tydzieñ (wt)'; break;
							case 3: $row['payday'] = 'co tydzieñ (¶r)'; break;
							case 4: $row['payday'] = 'co tydzieñ (czw)'; break;
							case 5: $row['payday'] = 'co tydzieñ (pt)'; break;
							case 6: $row['payday'] = 'co tydzieñ (sob)'; break;
							case 7: $row['payday'] = 'co tydzieñ (nie)'; break;
							default : $row['payday'] = "brak"; break;
					        }
					break;
					case 1:
					        $row['payday'] = 'co miesi±c ('.$row['at'].')'; 
					break;
					case 2:
						$at = sprintf('%02d/%02d', $row['at']%100,$row['at']/100+1);
						$row['payday'] = 'co kwarta³ ('.$at.')';
					break;
					case 3:
						$at = date('d/m',($row['at']-1)*86400);
						$row['payday'] = 'co rok ('.$at.')';
					break;
				}
				
				$paymentlist[$idx] = $row;
			}	
			
			$paymentlist['total'] = sizeof($paymentlist);
		
			return $paymentlist;
	}

	function GetPayment($id)
	{
		$payment = $this->DB->GetRow('SELECT id, name, creditor, value, period, at, description FROM payments WHERE id=?', array($id));

		switch($payment['period'])
		{
		    case 0:
			    switch($payment['at'])
			    {
				case 1: $payment['payday'] = 'co tydzieñ (pon)'; break;
				case 2: $payment['payday'] = 'co tydzieñ (wt)'; break;
				case 3: $payment['payday'] = 'co tydzieñ (¶r)'; break;
				case 4: $payment['payday'] = 'co tydzieñ (czw)'; break;
				case 5: $payment['payday'] = 'co tydzieñ (pt)'; break;
				case 6: $payment['payday'] = 'co tydzieñ (sob)'; break;
				case 7: $payment['payday'] = 'co tydzieñ (nie)'; break;
				default : $payment['payday'] = 'brak'; break;
			    }
		    break;
		    case 1:
			    $payment['payday'] = 'co miesi±c ('.$payment['at'].')'; 
		    break;
		    case 2:
			    $at = sprintf('%02d/%02d', $payment['at']%100,$payment['at']/100+1);
			    $payment['payday'] = 'co kwarta³ ('.$at.')';
		    break;
		    case 3:
			    $at = date('d/m',($payment['at']-1)*86400);
			    $payment['payday'] = 'co rok ('.$at.')';
		    break;
		}
		return $payment;
	}
	
	function GetPaymentName($id)
	{
		return $this->DB->GetOne('SELECT name FROM payments WHERE id=?', array($id));
	}
	
	function GetPaymentIDByName($name)
	{
		return $this->DB->GetOne('SELECT id FROM payments WHERE name=?', array($name));
	}	

	function PaymentExists($id)
	{
		return ($this->DB->GetOne('SELECT id FROM payments WHERE id=?', array($id))?TRUE:FALSE);
	}

	function PaymentAdd($paymentdata)
	{
		$this->SetTS('payments');
		if($this->DB->Execute('INSERT INTO payments (name, creditor, description, value, period, at)
			VALUES (?, ?, ?, ?, ?, ?)',
			array(
				$paymentdata['name'],
				$paymentdata['creditor'],
				$paymentdata['description'],
				$paymentdata['value'],
				$paymentdata['period'],
				$paymentdata['at'],
			)
		))
			return $this->DB->GetOne('SELECT id FROM payments WHERE name=?', array($paymentdata['name']));
		else
			return FALSE;
	}
	
	function PaymentDelete($id)
	{
		$this->SetTS('payments');		
		return $this->DB->Execute('DELETE FROM payments WHERE id=?', array($id));
	}
	
	function PaymentUpdate($paymentdata)
	{
		$this->SetTS('payments');
		return $this->DB->Execute('UPDATE payments SET name=?, creditor=?, description=?, value=?, period=?, at=? WHERE id=?',
			array(
				$paymentdata['name'],
				$paymentdata['creditor'],
				$paymentdata['description'],
				$paymentdata['value'],
				$paymentdata['period'],
				$paymentdata['at'],
				$paymentdata['id']
			)
		);
	}

	function ScanNodes()
	{
		$networks = $this->GetNetworks();
		if($networks)
			foreach($networks as $idx => $network)
			{
				$out = split("\n",execute_program('nbtscan','-q -s: '.$network['address'].'/'.$network['prefix']));
				foreach($out as $line)
				{
					list($ipaddr,$name,$null,$login,$mac)=split(':',$line);
					$row['ipaddr'] = trim($ipaddr);
					if($row['ipaddr'])
					{
						$row['name'] = trim($name);
						$row['mac'] = str_replace('-',':',trim($mac));
						if(!$this->GetNodeIDByIP($row['ipaddr']) && $row['ipaddr'] && $row['mac'] != "00:00:00:00:00:00")
							$result[] = $row;
					}
				}
			}
		return $result;
	}

	/*
	 *  Obs³uga rekordów z sieciami
	 */

	function NetworkExists($id)
	{
		return ($this->DB->GetOne('SELECT * FROM networks WHERE id=?', array($id)) ? TRUE : FALSE);
	}

	function IsIPFree($ip)
	{
		return !($this->DB->GetOne('SELECT * FROM nodes WHERE ipaddr=inet_aton(?)', array($ip)) ? TRUE : FALSE);
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
		if($netadd['prefix'] != '')
			$netadd['mask'] = prefix2mask($netadd['prefix']);
		$this->SetTS('networks');
		if($this->DB->Execute('INSERT INTO networks (name, address, mask, interface, gateway, dns, dns2, domain, wins, dhcpstart, dhcpend) VALUES (?, inet_aton(?), ?, ?, ?, ?, ?, ?, ?, ?, ?)', array(strtoupper($netadd['name']),$netadd['address'],$netadd['mask'],strtolower($netadd['interface']),$netadd['gateway'],$netadd['dns'],$netadd['dns2'],$netadd['domain'],$netadd['wins'],$netadd['dhcpstart'],$netadd['dhcpend'])))
			return $this->DB->GetOne('SELECT id FROM networks WHERE address=inet_aton(?)', array($netadd['address']));
		else
			return FALSE;
	}

	function NetworkDelete($id)
	{
		$this->SetTS('networks');
		return $this->DB->Execute('DELETE FROM networks WHERE id=?', array($id));
	}

	function GetNetworkName($id)
	{
		return $this->DB->GetOne('SELECT name FROM networks WHERE id=?', array($id));
	}

	function GetNetIDByIP($ipaddr)
	{
		if($networks = $this->DB->GetAll('SELECT id, inet_ntoa(address) AS address, mask FROM networks'))
			foreach($networks as $idx => $row)
				if(isipin($ipaddr,$row['address'],$row['mask']))
					return $row['id'];
		return FALSE;
	}

	function GetNetworks()
	{
		if($netlist = $this->DB->GetAll('SELECT id, name, inet_ntoa(address) AS address, address AS addresslong, mask FROM networks'))
			foreach($netlist as $idx => $row)
				$netlist[$idx]['prefix'] = mask2prefix($row['mask']);

		return $netlist;
	}

	function GetNetworkParams($id)
	{
		if($params = $this->DB->GetRow('SELECT *, inet_ntoa(address) AS netip FROM networks WHERE id=?', array($id)))
			$params['broadcast'] = ip_long(getbraddr($params['netip'],$params['mask']));
		return $params;
	}

	function GetNetworkList()
	{
		if($networks = $this->DB->GetAll('SELECT id, name, inet_ntoa(address) AS address, address AS addresslong, mask, interface, gateway, dns, dns2, domain, wins, dhcpstart, dhcpend FROM networks ORDER BY name'))
			foreach($networks as $idx => $row)
			{
				$row['prefix'] = mask2prefix($row['mask']);
				$row['size'] = pow(2,(32 - $row['prefix']));
				$row['broadcast'] = getbraddr($row['address'],$row['mask']);
				$row['broadcastlong'] = ip_long($row['broadcast']);
				$row['assigned'] = $this->DB->GetOne('SELECT COUNT(*) FROM nodes WHERE ipaddr >= ? AND ipaddr <= ?', array($row['addresslong'], $row['broadcastlong']));
				$row['online'] = $this->DB->GetOne('SELECT COUNT(*) FROM nodes WHERE ipaddr >= ? AND ipaddr <= ? AND (?NOW? - lastonline < ?)', array($row['addresslong'], $row['broadcastlong'], $this->CONFIG['phpui']['lastonline_limit']));
				$networks[$idx] = $row;
				$networks['size'] += $row['size'];
				$networks['assigned'] += $row['assigned'];
			}

		return $networks;
	}

	function IsIPValid($ip,$checkbroadcast=FALSE,$ignoreid=0)
	{
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

	function NetworkShift($network='0.0.0.0',$mask='0.0.0.0',$shift=0)
	{
		$this->SetTS('nodes');
		$this->SetTS('networks');
		return $this->DB->Execute('UPDATE nodes SET ipaddr = ipaddr + ? WHERE ipaddr >= inet_aton(?) AND ipaddr <= inet_aton(?)', array($shift, $network, getbraddr($network,$mask)));
	}

	function NetworkUpdate($networkdata)
	{
		$this->SetTS('networks');
		return $this->DB->Execute('UPDATE networks SET name=?, address=inet_aton(?), mask=?, interface=?, gateway=?, dns=?, dns2=?, domain=?, wins=?, dhcpstart=?, dhcpend=? WHERE id=?', array(strtoupper($networkdata['name']),$networkdata['address'],$networkdata['mask'],strtolower($networkdata['interface']),$networkdata['gateway'],$networkdata['dns'],$networkdata['dns2'],$networkdata['domain'],$networkdata['wins'],$networkdata['dhcpstart'],$networkdata['dhcpend'],$networkdata['id']));
	}

	function NetworkCompress($id,$shift=0)
	{
		$this->SetTS('nodes');
		$this->SetTS('networks');
		$network = $this->GetNetworkRecord($id);
		$address = $network['addresslong']+$shift;
		foreach($network['nodes']['id'] as $key => $value)
		{
			if($value)
			{
				$address ++;
				$this->DB->Execute('UPDATE nodes SET ipaddr=? WHERE id=?', array($address,$value));
			}
		}
	}

	function NetworkRemap($src,$dst)
	{
		$this->SetTS('nodes');
		$this->SetTS('networks');
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
				$this->DB->Execute('UPDATE nodes SET ipaddr=? WHERE id=?', array($network['dest']['nodes']['addresslong'][$counter],$value));
				$counter++;
			}
		return $counter;
	}

	function GetNetworkRecord($id, $page = 0, $plimit = 4294967296)
	{
		$network = $this->DB->GetRow('SELECT id, name, inet_ntoa(address) AS address, address AS addresslong, mask, interface, gateway, dns, dns2, domain, wins, dhcpstart, dhcpend FROM networks WHERE id=?', array($id));
		$network['prefix'] = mask2prefix($network['mask']);
		$network['size'] = pow(2,32-$network['prefix']);
		$network['assigned'] = 0;
		$network['broadcast'] = getbraddr($network['address'],$network['mask']);

		$network['assigned'] = $this->DB->GetOne("SELECT COUNT(*) FROM nodes WHERE ipaddr >= ? AND ipaddr < ?", array($network['addresslong'], $network['addresslong'] + $network['size']));
		$network['free'] = $network['size'] - $network['assigned'] - 2;
		if ($network['dhcpstart']) 
			$network['free']=$network['free'] - (ip_long($network['dhcpend']) - ip_long($network['dhcpstart']) + 1);
		
		if(!$plimit)
			return $network;
		
		$network['pagemax'] = ceil($network['size'] / $plimit);

		if($page > $network['pagemax'])
			$page = $network['pagemax'];
		if($page < 1)
			$page = 1;

		$page --;
		$start = $page * $plimit;
		$end = ($network['size'] > $plimit ? $start + $plimit : $network['size']);

		$nodes = $this->DB->GetAllByKey('SELECT id, name, ipaddr, ownerid, netdev FROM nodes WHERE ipaddr >= ? AND ipaddr <= ?','ipaddr', array(($network['addresslong'] + $start), ($network['addresslong'] + $end)));

	
		for($i = 0; $i < ($end - $start) ; $i ++)
		{
			$longip = $network['addresslong'] + $i + $start;
			$node = $nodes["".$longip.""];
			$network['nodes']['addresslong'][$i] = $longip;
			$network['nodes']['address'][$i] = long2ip($longip);
			$network['nodes']['id'][$i] = $node['id'];
			$network['nodes']['netdev'][$i] = $node['netdev'];

			if( $network['nodes']['addresslong'][$i] >= ip_long($network['dhcpstart']) && $network['nodes']['addresslong'][$i] <= ip_long($network['dhcpend']) )
				$network['nodes']['name'][$i] = 'DHCP';
			else
				$network['nodes']['name'][$i] = $node['name'];
				
			$network['nodes']['ownerid'][$i] = $node['ownerid'];
			
			if( $node['id'] )
				$network['pageassigned'] ++;
			if( $network['nodes']['ownerid'][$i] == 0 && $network['nodes']['netdev'][$i] > 0) 
			{
				$netdev = $this->GetNetDevName($network['nodes']['netdev'][$i]);
				$network['nodes']['name'][$i] = $network['nodes']['name'][$i]." (".$netdev['name'].")";
			}

		}
		$network['nodes']['name'][0] = '*** NETWORK ***';
		$network['nodes']['name'][$i-1] = '*** BROADCAST ***';
		$network['rows'] = ceil(sizeof($network['nodes']['address']) / 4);
		$network['pages'] = ceil($network['size'] / $plimit);
		$network['page'] = $page + 1;

		return $network;
	}

	function GetNetwork($id)
	{
		if($row = $this->DB->GetRow('SELECT inet_ntoa(address) AS address, address AS addresslong, mask, name, dhcpstart, dhcpend FROM networks WHERE id=?', array($id)))
			foreach($row as $field => $value)
				$$field = $value;

		for($i=$addresslong+1;$i<ip_long(getbraddr($address,$mask));$i++)
		{
			$result['addresslong'][] = $i;
			$result['address'][] = long2ip($i);
			$result['nodeid'][] = 0;
			$result['nodename'][] = '';
			$result['ownerid'][] = 0;
		}
		
		if(sizeof($result['address']))
			if($nodes = $this->DB->GetAll('SELECT name, id, ownerid, ipaddr FROM nodes WHERE ipaddr >= inet_aton(?) AND ipaddr <= inet_aton(?)', array($address, getbraddr($address,$mask))))
				foreach($nodes as $node)
				{
					$pos = ($node['ipaddr'] - $addresslong - 1);
					$result['nodeid'][$pos] = $node['nodeid'];
					$result['nodename'][$pos] = $node['name'];
					$result['ownerid'][$pos] = $node['ownerid'];
				}
		
		for($pos=(ip_long($dhcpstart) - $addresslong - 1);$pos<=(ip_long($dhcpend) - $addresslong - 1);$pos++)
			$result['nodename'][$pos] = "DHCP";
		
		return $result;
	}

	/*
	 * Ewidencja sprzêtu sieciowego
	 */

	function NetDevExists($id)
	{
		return ($this->DB->GetOne('SELECT * FROM netdevices WHERE id=?', array($id)) ? TRUE : FALSE);
	}

	function GetNetDevName($id)
	{
		return $this->DB->GetRow('SELECT name, model, location FROM netdevices WHERE id=?', array($id));
	}

	function GetNetDevIDByNode($id)
	{
		return $this->DB->GetOne('SELECT netdev FROM nodes WHERE id=?', array($id));
	}

	function CountNetDevLinks($id)
	{
		return $this->DB->GetOne('SELECT COUNT(*) FROM netlinks WHERE src = ? OR dst = ?', array($id,$id)) + $this->DB->GetOne("SELECT COUNT(*) FROM nodes WHERE netdev = ? AND ownerid > 0", array($id));
	}

	function GetNetDevConnected($id)
	{
		return $this->DB->GetAll('SELECT (CASE src WHEN '.$id.' THEN src ELSE dst END) AS src, (CASE src WHEN '.$id.' THEN dst ELSE src END) AS dst FROM netlinks WHERE src = '.$id.' OR dst = '.$id);
	}

	function GetNetDevConnectedNames($id)
	{
		// To powinno byæ lepiej zrobione...
		$list = $this->GetNetDevConnected($id);
		$id = 0;
		if ($list) 
		{
			foreach($list as $row)
			{
				$names[$id]= $this->GetNetDev($row['dst']);
				$id++;
			}
		}
		return $names;
	}

	function GetNetDevList($order='name,asc')
	{
		list($order,$direction) = explode(',',$order);

		($direction=='desc') ? $direction = 'desc' : $direction = 'asc';

		switch($order)
		{
			case 'name':
				$sqlord = ' ORDER BY name';
			break;
			case 'id':
				$sqlord = ' ORDER BY id';
			break;
			case 'producer':
				$sqlord = ' ORDER BY producer';
			break;
			case 'model':
				$sqlord = ' ORDER BY model';
			break;
			case 'ports':
				$sqlord = ' ORDER BY ports';
			break;
			case 'serialnumber':
				$sqlord = ' ORDER BY serialnumber';
			break;
			case 'location':
				$sqlord = ' ORDER BY location';
			break;
		}

		if($netdevlist = $this->DB->GetAll('SELECT id, name, location, description, producer, model, serialnumber, ports FROM netdevices '.($sqlord != '' ? $sqlord.' '.$direction : '')))
			foreach($netdevlist as $idx => $row)
				$netdevlist[$idx]['takenports'] = $this->CountNetDevLinks($row['id']);

		switch($order)
		{
			case 'takenports':
				foreach($netdevlist as $idx => $row)					
				{
					$tptable['idx'][] = $idx;
					$tptable['takenports'][] = $row['takenports'];
				}
				array_multisort($tptable['takenports'],($direction == "desc" ? SORT_DESC : SORT_ASC),$tptable['idx']);
				foreach($tptable['idx'] as $idx)
					$nnetdevlist[] = $netdevlist[$idx];
				$netdevlist = $nnetdevlist;
			break;
		}
		
		$netdevlist['total'] = sizeof($netdevlist);
		$netdevlist['order'] = $order;
		$netdevlist['direction'] = $direction;
		return $netdevlist;
	}

	function GetNotConnectedDevices($id)
	{
		$query = 'SELECT id, name, location, description, producer, model, serialnumber, ports FROM netdevices WHERE id!='.$id;
		if ($lista = $this->GetNetDevConnected($id))
			foreach($lista as $row)
				$query = $query.' and id!='.$row['dst'];
		return $this->DB->GetAll($query.' ORDER BY name');
	}

	function GetNetDev($id)
	{
		$result = $this->DB->GetRow('SELECT id, name, location, description, producer, model, serialnumber, ports FROM netdevices WHERE id=?', array($id));
		$result['takenports'] = $this->CountNetDevLinks($id);
		return $result;
	}

	function NetDevDelLinks($id)
	{
		return $this->DB->Execute('DELETE FROM netlinks WHERE src=? OR dst=?', array($id,$id));
		$nodes = GetNetdevLinkedNodes($id);
		if ($nodes) foreach($nodes as $node) {
			$this->NetDevLinkNode($node['id'],0);
		}
	}
	
	function NetDevReplace($sid, $did)
	{
		$dev1=$this -> GetNetDev($sid);
		$dev2=$this -> GetNetDev($did);
		$location = $dev1['location'];
		$dev1['location'] = $dev2['location'];
		$dev2['location'] = $location;
		$links1 = $this -> GetNetDevConnected($sid);
		$links2 = $this -> GetNetDevConnected($did);
		$nodes1 = $this -> GetNetdevLinkedNodes($sid);
		$nodes2 = $this -> GetNetdevLinkedNodes($did);
		$this -> NetDevDelLinks($sid);
		$this -> NetDevDelLinks($did);
		if ($links1) foreach($links1 as $row) {
			$this -> NetDevLink($did,$row['dst']);
		}
		if ($links2) foreach($links2 as $row) {
			$this -> NetDevLink($sid,$row['dst']);
		}
		if ($nodes1) foreach($nodes1 as $row) {
			$this->NetDevLinkNode($row['id'],$did);
		}
		if ($nodes2) foreach($nodes2 as $row) {
			$this->NetDevLinkNode($row['id'],$sid);
		}
		$this -> NetDevUpdate($dev1);
		$this -> NetDevUpdate($dev2);
	}

	function DeleteNetDev($id)
	{
		$this->DB->Execute('DELETE FROM netlinks WHERE src=? OR dst=?', array($id));
		$this->DB->Execute('DELETE FROM nodes WHERE ownerid=0 AND netdev=?', array($id));
		$this->DB->Execute('UPDATE nodes SET netdev=0 WHERE netdev=?', array($id));
		$this->SetTS('nodes');
		$this->SetTS('netlinks');
		$this->SetTS('netdevices');
		return $this->DB->Execute('DELETE FROM netdevices WHERE id=?', array($id));
	}

	function NetDevAdd($netdevdata)
	{
		$this->SetTS('netdevices');
		if($this->DB->Execute('INSERT INTO netdevices (name, location, description, producer, model, serialnumber, ports) VALUES (?, ?, ?, ?, ?, ?, ?)', array($netdevdata['name'],$netdevdata['location'],$netdevdata['description'],$netdevdata['producer'],$netdevdata['model'],$netdevdata['serialnumber'],$netdevdata['ports'])))
			return $this->DB->GetOne('SELECT MAX(id) FROM netdevices');
		else
			return FALSE;
	}

	function NetDevUpdate($netdevdata)
	{
		$this->SetTS('netdevices');
		$this->DB->Execute('UPDATE netdevices SET name=?, location=?, description=?, producer=?, model=?, serialnumber=?, ports=? WHERE id=?', array( $netdevdata['name'], $netdevdata['location'], $netdevdata['description'], $netdevdata['producer'], $netdevdata['model'], $netdevdata['serialnumber'], $netdevdata['ports'], $netdevdata['id'] ) );
	}

	function IsNetDevLink($dev1, $dev2)
	{
		return $this->DB->GetOne('SELECT COUNT(id) FROM netlinks WHERE (src=? AND dst=?) OR (dst=? AND src=?)', array($dev1, $dev2, $dev1, $dev2));
	} 

	function NetDevLink($dev1, $dev2)
	{
		if($dev1 != $dev2)
		{
			if($this->IsNetDevLink($dev1,$dev2))
				return FALSE;
			
			$netdev1 = $this->GetNetDev($dev1);
			$netdev2 = $this->GetNetDev($dev2);
			
			if( $netdev1['takenports'] >= $netdev1['ports'] || $netdev2['takenports'] >= $netdev2['ports'])
				return FALSE;
			
			$this->DB->Execute('INSERT INTO netlinks (src, dst) VALUES (?, ?)', array($dev1, $dev2)); 
			$this->SetTS('netlinks');
		}
		return TRUE;
	}	
	
	function NetDevUnLink($dev1, $dev2)
	{
		$this->SetTS('netlinks');
		$this->DB->Execute('DELETE FROM netlinks WHERE (src=? AND dst=?) OR (dst=? AND src=?)', array($dev1, $dev2, $dev1, $dev2));
	}

	function GetUnlinkedNodes()
	{
		return $this->DB->GetAll('SELECT *, inet_ntoa(ipaddr) AS ip FROM nodes WHERE netdev=0 ORDER BY name ASC');
	}

	function GetNetDevIPs($id)
	{
		return $this->DB->GetAll('SELECT id, name, ipaddr, inet_ntoa(ipaddr) AS ip, mac, access FROM nodes WHERE ownerid=0 AND netdev=?', array($id));
	}
	
	/*
	 * Pozosta³e funkcje...
	 */
	
	function GetRemoteMACs($host = '127.0.0.1', $port = 1029)
	{
		if($socket = socket_create (AF_INET, SOCK_STREAM, 0))
			if(@socket_connect ($socket, $host, $port))
			{
				while ($input = socket_read ($socket, 2048))
					$inputbuf .= $input;
				socket_close ($socket);
			}
		foreach(split("\n",$inputbuf) as $line)
		{
			list($ip,$hwaddr) = split(' ',$line);
			if(check_mac($hwaddr))
			{
				$result['mac'][] = $hwaddr;
				$result['ip'][] = $ip;
				$result['longip'][] = ip_long($ip);
				$result['nodename'][] = $this->GetNodeNameByMAC($mac);
			}
		}
		return $result;
	}

	function GetMACs()
	{
		switch(PHP_OS)
		{
			case 'Linux':
				if(@is_readable('/proc/net/arp'))
					$file=fopen('/proc/net/arp','r');
				else
					return FALSE;
				while(!feof($file))
				{
					$line=fgets($file,4096);
					$line=eregi_replace("[\t ]+"," ",$line);
					list($ip, $hwtype, $flags, $hwaddr, $mask, $device) = split(' ',$line);
					if($flags != '0x6' && $hwaddr != '00:00:00:00:00:00')
					{
						$result['mac'][] = $hwaddr;
						$result['ip'][] = $ip;
						$result['longip'][] = ip_long($ip);
						$result['nodename'][] = $this->GetNodeNameByMAC($mac);
					}
				}
				break;

			default:
				exec('arp -an|grep -v incompl',$result);
				foreach($result as $arpline)
				{
					list($fqdn,$ip,$at,$mac,$hwtype,$perm) = explode(' ',$arpline);
					$ip = str_replace('(','',str_replace(')','',$ip));
					if($perm != "PERM")
					{
						$result['mac'][] = $mac;
						$result['ip'][] = $ip;
						$result['longip'][] = ip_long($ip);
						$result['nodename'][] = $this->GetNodeNameByMAC($mac);
					}
				}
				break;

		}
		array_multisort($result['longip'],$result['mac'],$result['ip'],$result['nodename']);
		return $result;
	}
	
	
	function GetNodeByMAC($ip)
	{
		exec("arp -an | grep -v incompl | grep $ip" ,$result);
		foreach ($result as $arpline)
		{
		    list($fqdn,$ip,$at,$mac,$hwtype,$perm) = explode(' ',$arpline);
		    $ip = str_replace('(','',str_replace(')','',$ip));

		    $result['mac'] = $mac;
		    $result['ip'] = $ip;
		    $result['longip'] = ip_long($ip);
		    $result['nodename'] = $this->GetNodeNameByMAC($mac);
		}		
		return $result;
	}

	function Mailing($mailing)
	{
		if($emails = $this->GetEmails($mailing['group'], $mailing['network'], $mailing['usergroup']))
		{
			if($this->CONFIG['phpui']['debug_email'])
				echo '<B>Uwaga! Tryb debug (u¿ywam adresu '.$this->CONFIG['phpui']['debug_email'].')</B><BR>';

			foreach($emails as $key => $row)
			{
				if($this->CONFIG['phpui']['debug_email'])
					$row['email'] = $this->CONFIG['phpui']['debug_email'];

				mail (
					$row['username'].' <'.$row['email'].'>',
					$mailing['subject'],
					$mailing['body'],
					'From: '.$mailing['from'].' <'.$mailing['sender'].">\n"."Content-Type: text/plain; charset=ISO-8859-2;\n".'X-Mailer: LMS-'.$this->_version.'/PHP-'.phpversion()."\n".'X-Remote-IP: '.$_SERVER['REMOTE_ADDR']."\n".'X-HTTP-User-Agent: '.$_SERVER['HTTP_USER_AGENT']."\n"
				);
				
				echo '<img src="img/mail.gif" border="0" align="absmiddle" alt=""> '.($key+1).' z '.sizeof($emails).' ('.sprintf('%02.2f',round((100/sizeof($emails))*($key+1),2))."%): ".$row['username'].' &lt;'.$row['email']."&gt;<BR>\n";
				flush();
			}
		}
	}

	function LiabilityReport($date, $order='brutto,asc', $userid=NULL)
	{
		$yearday = date('z', $date);
		$month = date('n', $date);
		$monthday = date('j', $date);
		$weekday = date('w', $date);
		switch($month) 
		{
		    case 1:
		    case 4:
		    case 7:
		    case 10: $quarterday = $monthday; break;
		    case 2:
		    case 5:
		    case 8:
		    case 11: $quarterday = $monthday + 100; break;
		    default: $quarterday = $monthday + 200; break;
		}
		
		list($order,$direction)=explode(',', $order);

		($direction != 'desc') ? $direction = 'ASC' : $direction = 'DESC';

		switch($order){

			case 'username':
				$sqlord = 'ORDER BY username';
			break;
			default:
				$sqlord = 'ORDER BY brutto';
			break;
		}
		
		return $this->DB->GetAll('SELECT '.$this->DB->Concat('UPPER(lastname)',"' '",'users.name').' AS username, '
			    .$this->DB->Concat('city',"' '",'address').' AS address, nip, 
			    SUM(CASE taxvalue WHEN 22.00 THEN value ELSE 0 END) AS val22,  
			    SUM(CASE taxvalue WHEN 7.00 THEN value ELSE 0 END) AS val7, 
			    SUM(CASE taxvalue WHEN 0.00 THEN value ELSE 0 END) AS val0, 
			    SUM(CASE WHEN taxvalue IS NULL THEN value ELSE 0 END) AS valfree,
			    SUM(value) AS brutto  
			    FROM assignments, tariffs, users  
			    WHERE userid = users.id AND tariffid = tariffs.id 
			    AND deleted=0 AND (datefrom<=?) AND ((dateto>=?) OR dateto=0) 
			    AND ((period=0 AND at=?) OR (period=1 AND at=?) OR (period=2 AND at=?) OR (period=3 AND at=?)) '
			    .($userid ? "AND userid=$userid" : ''). 
			    'GROUP BY userid, lastname, users.name, city, address, nip '
			    .($sqlord != '' ? $sqlord.' '.$direction : ''),
			    array($date, $date, $weekday, $monthday, $quarterday, $yearday));
	}

	/*
	 *  Statystyki
	 */

	function Traffic($from = 0, $to = '?NOW?', $net = 0, $order = '', $limit = 0)
	{
		// period
		if (is_array($from))
			$fromdate = mktime($from['Hour'],$from['Minute'],0,$from['Month'],$from['Day'],$from['Year']);
		else
			$fromdate = $from;
		if (is_array($to))
			$todate = mktime($to['Hour'],$to['Minute'],59,$to['Month'],$to['Day'],$to['Year']);
		else
			$todate = $to;

		$dt = "( dt >= $fromdate AND dt <= $todate )";

		// nets
		if ($net != "allnets")
		{
			$params = $this->GetNetworkParams($net);
			$params['address']++;
			$params['broadcast']--;
			$net = ' AND ( ipaddr > '.$params['address'].' AND ipaddr < '.$params['broadcast'].' )';
		}
		else
			$net = '';

		// order
		switch ($order)
		{
			case 'nodeid':
				$order = ' ORDER BY nodeid';
			break;
			case 'download':
				$order = ' ORDER BY download DESC';
			break;
			case 'upload':
				$order = ' ORDER BY upload DESC';
			break;
			case 'name':
				$order = ' ORDER BY name';
			break;
			case 'ip':
				$order = ' ORDER BY ipaddr';
			break;
		}

		// limits
		if($limit > 0)
			$limit = ' LIMIT '.$limit;
		else
			$limit = '';

		// join query from parts
		$query = 'SELECT nodeid, name, inet_ntoa(ipaddr) AS ip, sum(upload) as upload, sum(download) as download FROM stats LEFT JOIN nodes ON stats.nodeid=nodes.id WHERE 1=1 AND '.$dt.' '.$net.' GROUP BY nodeid, name, ipaddr '.$order.' '.$limit;

		// get results
		if ($traffic = $this->DB->GetAll($query))
		{
			foreach ($traffic as $idx => $row)
			{
				$traffic['upload']['data'][] = $row['upload'];
				$traffic['download']['data'][] = $row['download'];
				$traffic['upload']['name'][] = ($row['name'] ? $row['name'] : 'nieznany (ID: '.$row['nodeid'].')');
				$traffic['download']['name'][] = ($row['name'] ? $row['name'] : 'nieznany (ID: '.$row['nodeid'].')');
				$traffic['upload']['ipaddr'][] = $row['ip'];
				$traffic['download']['nodeid'][] = $row['nodeid'];
				$traffic['upload']['nodeid'][] = $row['nodeid'];
				$traffic['download']['ipaddr'][] = $row['ip'];
				$traffic['download']['sum']['data'] += $row['download'];
				$traffic['upload']['sum']['data'] += $row['upload'];
			}

			// get maximum data from array

			$maximum = max($traffic['download']['data']);
			if($maximum < max($traffic['upload']['data']))
				$maximum = max($traffic['upload']['data']);

			if($maximum == 0)		// do not need divide by zero
				$maximum = 1;

			// make data for bars drawing
			$x = 0;

			foreach ($traffic['download']['data'] as $data)
			{
				$traffic['download']['bar'][] = round($data * 150 / $maximum);
				list($traffic['download']['data'][$x], $traffic['download']['unit'][$x]) = setunits($data);
				$x++;
			}
			$x = 0;

			foreach ($traffic['upload']['data'] as $data)
			{
				$traffic['upload']['bar'][] = round($data * 150 / $maximum);
				list($traffic['upload']['data'][$x], $traffic['upload']['unit'][$x]) = setunits($data);
				$x++;
			}

			//set units for data
			list($traffic['download']['sum']['data'], $traffic['download']['sum']['unit']) = setunits($traffic['download']['sum']['data']);
			list($traffic['upload']['sum']['data'], $traffic['upload']['sum']['unit']) = setunits($traffic['upload']['sum']['data']);
		}

		return $traffic;
	}

	function TrafficHost($from, $to, $host)
	{
	    return $this->DB->GetRow('SELECT sum(upload) as upload, sum(download) as download FROM stats WHERE dt >='.$from.' AND dt <'.$to.' AND nodeid='.$host);
	}

	function TrafficFirstRecord($host)
	{
	    return $this->DB->GetOne('SELECT MIN(dt) FROM stats WHERE nodeid='.$host);
	}

	/*
	 * Obs³uga RT
	 *
         * Statusy ticketów:
	 *
	 * 0 - new
	 * 1 - open
	 * 2 - resolved
	 * 3 - dead (podobny do resolved, ale nie rozwi±zany)
	 *
	 */

	var $rtstates = array( 0 => 'nowy', 1 => 'otwarty', 2 => 'rozwi±zany', 3 => 'martwy' );

	function GetQueue($id)
	{
		if($queue = $this->DB->GetRow('SELECT * FROM rtqueues WHERE id=?', array($id)))
		{
		    $admins = $this->DB->GetAll('SELECT id, name FROM admins WHERE deleted=0');
		    foreach($admins as $admin)
		    {
			    $admin['rights'] = $this->GetAdminRightsRT($admin['id'],$id);
			    $queue['rights'][] = $admin; 
		    }
		    return $queue;
		}
		else
		    return NULL;
	}
	
	function GetAdminRightsRT($admin, $queue, $ticket=NULL)
	{
		if($queue==0)
			$queue = $this->DB->GetOne('SELECT queueid FROM rttickets WHERE id=?', array($ticket));

		$rights = $this->DB->GetOne('SELECT rights FROM rtrights WHERE adminid=? AND queueid=?', array($admin, $queue));
		return ($rights ? $rights : 0);
	}

	function GetQueueList()
	{
		if($result = $this->DB->GetAll('SELECT id, name, email, description FROM rtqueues ORDER BY name'))
		{
			foreach($result as $idx => $row)
				foreach($this->GetQueueStats($row['id']) as $sidx => $row)
					$result[$idx][$sidx] = $row;
		}
		return $result;
	}

	function GetQueueNames()
	{
		return $this->DB->GetAll('SELECT id, name FROM rtqueues ORDER BY name');
	}

	function QueueExists($id)
	{
		return ($this->DB->GetOne('SELECT * FROM rtqueues WHERE id=?', array($id)) ? TRUE : FALSE);
	}

	function GetQueueIdByName($queue)
	{
		return $this->DB->GetOne('SELECT id FROM rtqueues WHERE name=?', array($queue));
	}

	function QueueAdd($queue)
	{
		if($this->DB->Execute('INSERT INTO rtqueues (name, email, description) VALUES (?, ?, ?)', array($queue['name'], $queue['email'], $queue['description'])))
		{
			$this->SetTS('rtqueues');
			$id = $this->DB->GetOne('SELECT id FROM rtqueues WHERE name=?', array($queue['name']));
			if($queue['rights'])
			{
				$this->SetTS('rtrights');
				foreach($queue['rights'] as $right)
					if($right['rights'])
						$this->RightsRTAdd($id, $right['id'], $right['rights']);
			}
			return $id;
		}
		else
			return FALSE;
	}

    	function QueueDelete($queue)
	{
		if($this->DB->Execute('DELETE FROM rtqueues WHERE id=?', array($queue)))
			$this->SetTS('rtqueues');
		if($this->DB->Execute('DELETE FROM rtrights WHERE queueid=?', array($queue)))
			$this->SetTS('rtrights');
		if($tickets = $this->DB->GetCol('SELECT id FROM rttickets WHERE queueid=?', array($queue)))
		{
			foreach($tickets as $id)
				$this->DB->Execute('DELETE FROM rtmessages WHERE ticketid=?', array($id));
			$this->SetTS('rtmessages');
			$this->DB->Execute('DELETE FROM rttickets WHERE queueid=?', array($queue));
			$this->SetTS('rttickets');
		}
	}

	function RightsRTAdd($queueid, $adminid, $rights)
	{
		$this->DB->Execute('INSERT INTO rtrights(queueid, adminid, rights) VALUES(?, ?, ?)', array($queueid, $adminid, $rights));
		$this->SetTS('rtrights');
	}

	function QueueUpdate($queue)
	{
		$this->DB->Execute('UPDATE rtqueues SET name=?, email=?, description=? WHERE id=?', array($queue['name'], $queue['email'], $queue['description'], $queue['id']));
		$this->SetTS('rtqueues');
		$this->DB->Execute('DELETE FROM rtrights WHERE queueid=?', array($queue['id']));
		$this->SetTS('rtrights');
		if($queue['rights'])
			foreach($queue['rights'] as $right)
				if($right['rights'])
					$this->RightsRTAdd($queue['id'], $right['id'], $right['rights']);
	}
	
	function GetQueueName($id)
	{
		return $this->DB->GetOne('SELECT name FROM rtqueues WHERE id=?', array($id));
	}

	function GetQueueEmail($id)
	{
		return $this->DB->GetOne('SELECT email FROM rtqueues WHERE id=?', array($id));
	}

	function GetQueueContents($id, $order='createtime,desc', $state=NULL)
	{
		if(!$order)
			$order = 'createtime,desc';
	
		list($order,$direction)=explode(',',$order);

		($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

		switch($order)
		{
			case 'ticketid':
				$sqlord = 'ORDER BY rttickets.id';
			break;
			case 'subject':
				$sqlord = 'ORDER BY rttickets.subject';
			break;
			case 'requestor':
				$sqlord = 'ORDER BY requestor';
			break;
			case 'owner':
				$sqlord = 'ORDER BY ownername';
			break;
			case 'lastmodified':
				$sqlord = 'ORDER BY lastmodified';
			break;
			default:
				$sqlord = 'ORDER BY rttickets.createtime';
			break;
		}

		if($result = $this->DB->GetAll('SELECT rttickets.id AS id, rttickets.userid AS userid, requestor, rttickets.subject AS subject, state, owner AS ownerid, admins.name AS ownername, '.$this->DB->Concat('UPPER(users.lastname)',"' '",'users.name').' AS username, rttickets.createtime AS createtime, MAX(rtmessages.createtime) AS lastmodified 
		    FROM rttickets LEFT JOIN rtmessages ON (rttickets.id = rtmessages.ticketid)
		    LEFT JOIN admins ON (owner = admins.id) 
		    LEFT JOIN users ON (rttickets.userid = users.id)
		    WHERE queueid = ? 
		    GROUP BY rttickets.id, requestor, rttickets.createtime, rttickets.subject, state, owner, admins.name, rttickets.userid, users.lastname, users.name '
		    .($sqlord !='' ? $sqlord.' '.$direction:''), array($id)))
		{
			foreach($result as $idx => $ticket)
			{
				//$ticket['requestoremail'] = ereg_replace('^.*<(.*@.*)>$','\1',$ticket['requestor']);
				//$ticket['requestor'] = str_replace(' <'.$ticket['requestoremail'].'>','',$ticket['requestor']);
				if(!$ticket['userid'])
					list($ticket['requestor'], $ticket['requestoremail']) = sscanf($ticket['requestor'], "%[^<]<%[^>]");
				else
					list($ticket['requestoremail']) = sscanf($ticket['requestor'], "<%[^>]");
				$result[$idx] = $ticket;
				$result['total']++;
			}
		}
		
		$result['state'] = $state;
		$result['order'] = $order;
		$result['direction'] = $direction;
		
		return $result;
	}

	function GetQueueStats($id)
	{
		if($result = $this->DB->GetAll('SELECT state, COUNT(state) AS scount FROM rttickets WHERE queueid = ? GROUP BY state ORDER BY state ASC', array($id)))
		{
			foreach($result as $row)
				$stats[$row['state']] = $row['scount'];
			foreach(array('new', 'open', 'resolved', 'dead') as $idx => $value)
				$stats[$value] = $stats[$idx];
		}
		$stats['lastticket'] = $this->DB->GetOne('SELECT createtime FROM rttickets WHERE queueid = ? ORDER BY createtime DESC', array($id));
		return $stats;
	}

	function RTStats()
	{
		return $this->DB->GetRow('SELECT COUNT(CASE state WHEN 0 THEN 1 END) AS new,
						COUNT(CASE state WHEN 1 THEN state END) AS open,
						COUNT(CASE state WHEN 2 THEN state END) AS resolved,
						COUNT(CASE state WHEN 3 THEN state END) AS dead
					 FROM rttickets');
	}
	
	function GetQueueByTicketId($id)
	{
		if($queueid = $this->DB->GetOne('SELECT queueid FROM rttickets WHERE id=?', array($id)))
			return $this->DB->GetRow('SELECT * FROM rtqueues WHERE id=?', array($queueid));
		else
			return NULL;
	}

	function TicketExists($id)
	{
		return $this->DB->GetOne('SELECT * FROM rttickets WHERE id = ?', array($id));
	}

	function TicketAdd($ticket)
	{
		$ts = time();
		$this->DB->Execute('INSERT INTO rttickets (queueid, userid, requestor, subject, state, owner, createtime) 
				    VALUES (?, ?, ?, ?, 0, 0, ?)', array($ticket['queue'], $ticket['userid'], $ticket['requestor'], $ticket['subject'], $ts));
		$id = $this->DB->GetOne('SELECT id FROM rttickets WHERE createtime=? AND subject=?', array($ts, $ticket['subject']));
		$this->DB->Execute('INSERT INTO rtmessages (ticketid, userid, createtime, subject, body, mailfrom)
				    VALUES (?, ?, ?, ?, ?, ?)', array($id, $ticket['userid'], $ts, $ticket['subject'], $ticket['body'], $ticket['mailfrom']));
		$this->SetTS('rttickets');	
		$this->SetTS('rtmessages');
		
		return $id;
	}

	function TicketUpdate($ticket)
	{
		$this->SetTS('rttickets');
		if($ticket['state']==2)
			return $this->DB->Execute('UPDATE rttickets SET queueid=?, subject=?, state=?, owner=?, resolvetime=?NOW? WHERE id=?', array($ticket['queueid'], $ticket['subject'], $ticket['state'], $ticket['owner'], $ticket['ticketid']));
		else
		{
			// check if ticket was resolved, then set resolvetime=0
			if($this->GetTicketState($ticket['ticketid'])==2)
				return $this->DB->Execute('UPDATE rttickets SET queueid=?, subject=?, state=?, owner=?, resolvetime=0 WHERE id=?', array($ticket['queueid'], $ticket['subject'], $ticket['state'], $ticket['owner'], $ticket['ticketid']));
			else
				return $this->DB->Execute('UPDATE rttickets SET queueid=?, subject=?, state=?, owner=? WHERE id=?', array($ticket['queueid'], $ticket['subject'], $ticket['state'], $ticket['owner'], $ticket['ticketid']));
		}
	}

	function GetTicketContents($id)
	{
		$ticket = $this->DB->GetRow('
			SELECT rttickets.id AS ticketid, queueid, rtqueues.name AS queuename, requestor, state, owner, userid, '.$this->DB->Concat('UPPER(users.lastname)',"' '",'users.name').' AS username, admins.name AS ownername, createtime, resolvetime, subject 
			FROM rttickets 
			LEFT JOIN rtqueues ON (queueid = rtqueues.id) 
			LEFT JOIN admins ON (owner = admins.id)
			LEFT JOIN users ON (users.id = userid)
			WHERE rttickets.id = ?', array($id));
		$ticket['messages'] = $this->DB->GetAll('
			SELECT rtmessages.id AS id, mailfrom, subject, body, createtime, userid, '.$this->DB->Concat('UPPER(users.lastname)',"' '",'users.name').' AS username, adminid, admins.name AS adminname
			FROM rtmessages 
			LEFT JOIN users ON (users.id = userid)
			LEFT JOIN admins ON (admins.id = adminid)
			WHERE ticketid = ? ORDER BY createtime ASC', array($id));
		if(!$ticket['userid'])
			list($ticket['requestor'], $ticket['requestoremail']) = sscanf($ticket['requestor'], "%[^<]<%[^>]");
		else
			list($ticket['requetoremail']) = sscanf($ticket['requestor'], "<%[^>]");
//		$ticket['requestoremail'] = ereg_replace('^.* <(.+@.+)>$','\1',$ticket['requestor']);
//		$ticket['requestor'] = str_replace(' <'.$ticket['requestoremail'].'>','',$ticket['requestor']);
		$ticket['status'] = $this->rtstates[$ticket['state']];
		$ticket['uptime'] = uptimef($ticket['resolvetime'] ? $ticket['resolvetime'] - $ticket['createtime'] : time() - $ticket['createtime']);
		return $ticket;
	}

	function GetTicketState($id)
	{
		return $this->DB->GetOne('SELECT state FROM rttickets WHERE id = ?', array($id));
	}

	function GetTicketOwner($id)
	{
		return $this->DB->GetOne('SELECT owner FROM rttickets WHERE id = ?', array($id));
	}

	function SetTicketOwner($ticket, $admin=NULL)
	{
		if(!$admin) $admin = $this->SESSION->id;
		$this->SetTS('rttickets');
		return $this->DB->Execute('UPDATE rttickets SET owner=? WHERE id = ?', array($admin, $ticket));
	}

	function SetTicketState($ticket, $state)
	{
		($state==2 ? $resolvetime = time() : $resolvetime = 0);
			
		if($this->DB->GetOne('SELECT owner FROM rttickets WHERE id=?', array($ticket))) 
			$this->DB->Execute('UPDATE rttickets SET state=?, resolvetime=? WHERE id=?', array($state, $resolvetime, $ticket));
		else
			$this->DB->Execute('UPDATE rttickets SET state=?, owner=?, resolvetime=? WHERE id=?', array($state, $this->SESSION->id, $resolvetime, $ticket));
		$this->SetTS('rttickets');
	}

	function GetAttachment($msgid, $filename)
	{
		return $this->DB->GetRow('SELECT * FROM rtattachments WHERE messageid = ? AND filename = ?', array($msgid, $filename));
	}

	function GetMessage($id)
	{
		if($message = $this->DB->GetRow('SELECT * FROM rtmessages WHERE id=?', array($id)))
			$message['attachments'] = $this->DB->GetAll('SELECT * FROM rtattachments WHERE messageid = ?', array($id));
		return $message;
	}

	function MessageAdd($msg)
	{
		$this->SetTS('rtmessages');
		return $this->DB->Execute('INSERT INTO rtmessages (ticketid, createtime, subject, body, adminid, userid, mailfrom, inreplyto, messageid, replyto, headers)
				    VALUES (?, ?NOW?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($msg['ticketid'], $msg['subject'], $msg['body'], $msg['adminid'], $msg['userid'], $msg['mailfrom'], $msg['inreplyto'], $msg['messageid'], $msg['replyto'], $msg['headers']));
	}

	function RTSearch($search, $order='createtime,desc')
	{
		if(!$order)
			$order = 'createtime,desc';
	
		list($order,$direction)=explode(',',$order);

		($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

		switch($order)
		{
			case 'ticketid':
				$sqlord = 'ORDER BY rttickets.id';
			break;
			case 'subject':
				$sqlord = 'ORDER BY rttickets.subject';
			break;
			case 'requestor':
				$sqlord = 'ORDER BY requestor';
			break;
			case 'owner':
				$sqlord = 'ORDER BY ownername';
			break;
			case 'lastmodified':
				$sqlord = 'ORDER BY lastmodified';
			break;
			default:
				$sqlord = 'ORDER BY rttickets.createtime';
			break;
		}

		$where  = ($search['queue']     ? 'AND queueid='.$search['queue'].' '          : '');
		$where .= ($search['owner']     ? 'AND owner='.$search['owner'].' '            : '');
		$where .= ($search['userid']    ? 'AND rttickets.userid='.$search['userid'].' '   : '');
		$where .= ($search['subject']   ? 'AND rttickets.subject=\''.$search['subject'].'\' '       : '');
		$where .= ($search['state']!='' ? 'AND state='.$search['state'].' '            : '');
		$where .= ($search['name']!=''  ? 'AND requestor LIKE \''.$search['name'].'\' '  : '');
		$where .= ($search['email']!='' ? 'AND requestor LIKE \''.$search['email'].'\' ' : '');
		$where .= ($search['uptime']!='' ? 'AND (resolvetime-rttickets.createtime > '.$search['uptime'].' OR ('.time().'-rttickets.createtime > '.$search['uptime'].' AND resolvetime = 0) ) ' : '');
		
		if($search['username'])
			$where = 'AND users.lastname ?LIKE? \'%'.$search['username'].'%\' OR requestor ?LIKE? \'%'.$search['username'].'%\' ';

		if($result = $this->DB->GetAll('SELECT rttickets.id AS id, rttickets.userid AS userid, requestor, rttickets.subject AS subject, state, owner AS ownerid, admins.name AS ownername, '.$this->DB->Concat('UPPER(users.lastname)',"' '",'users.name').' AS username, rttickets.createtime AS createtime, MAX(rtmessages.createtime) AS lastmodified 
		    FROM rttickets 
		    LEFT JOIN rtmessages ON (rttickets.id = rtmessages.ticketid)
		    LEFT JOIN admins ON (owner = admins.id) 
		    LEFT JOIN users ON (rttickets.userid = users.id)
		    WHERE 1=1 '
		    .$where 
		    .'GROUP BY rttickets.id, requestor, rttickets.createtime, rttickets.subject, state, owner, admins.name, rttickets.userid, users.lastname, users.name '
		    .($sqlord !='' ? $sqlord.' '.$direction:'')))
		{
			foreach($result as $idx => $ticket)
			{
				//$ticket['requestoremail'] = ereg_replace('^.*<(.*@.*)>$','\1',$ticket['requestor']);
				//$ticket['requestor'] = str_replace(' <'.$ticket['requestoremail'].'>','',$ticket['requestor']);
				if(!$ticket['userid'])
					list($ticket['requestor'], $ticket['requestoremail']) = sscanf($ticket['requestor'], "%[^<]<%[^>]");
				else
					list($ticket['requestoremail']) = sscanf($ticket['requestor'], "<%[^>]");
				$result[$idx] = $ticket;
				$result['total']++;
			}
		}
		
		$result['order'] = $order;
		$result['direction'] = $direction;
		
		return $result;
	}

}
?>
