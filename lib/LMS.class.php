<?php

/*
 * LMS version 1.5-cvs
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

// we need this defines, and we need to place them here, see below why
define('RT_NEW', trans('new'));
define('RT_OPEN', trans('opened'));
define('RT_RESOLVED', trans('resolved'));
define('RT_DEAD', trans('dead'));

// LMS Class - contains internal LMS database functions used
// to fetch data like usernames, searching for mac's by ID, etc..

class LMS
{

	var $DB;			// database object
	var $AUTH;			// object from Session.class.php (session management)
	var $CONFIG;			// table including lms.ini options
	var $_version = '1.5-cvs';	// class version
	var $_revision = '$Revision$';
	var $MENU = array();

	function LMS(&$DB, &$AUTH, &$CONFIG) // ustawia zmienne klasy
	{
		if($AUTH !== NULL)
		{
			$this->AUTH = &$AUTH;
			$this->modules[] = 'AUTH';
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

		$this->AddMenu(trans('Administration'), 'settings.gif', '?m=welcome', trans('System informations and management'), 'i', 0);
		$this->AddMenu(trans('Customers'), 'user.gif', '?m=userlist', trans('Customers: list, searching, adding, groups'), 'u', 5);
		$this->AddMenu(trans('Nodes'), 'node.gif', '?m=nodelist', trans('Nodes: list, searching, adding'), 'k', 10);
		$this->AddMenu(trans('Net Devices'), 'netdev.gif', '?m=netdevlist', trans('Record of Network Devices'), 'o', 15);
		$this->AddMenu(trans('IP Networks'), 'network.gif', '?m=netlist', trans('IP Address Classes Management'), 't', 20);
		$this->AddMenu(trans('Finances'), 'money.gif', '?m=tarifflist', trans('Tariffs and Network Finances Management'), 'f', 25);
		$this->AddMenu(trans('Accounts'), 'account.gif', '?m=accountlist', trans('Accounts, Domains, Aliases Management'), 'a', 30);
		$this->AddMenu(trans('Mailing'), 'mail.gif', '?m=mailing', trans('Serial Mail'), 'm', 35);
		$this->AddMenu(trans('Reload'), 'reload.gif', '?m=reload', '', 'r', 40);
		$this->AddMenu(trans('Stats'), 'traffic.gif', '?m=traffic', trans('Statistics of Internet Link Usage'), 'x', 45);
		$this->AddMenu(trans('Helpdesk'), 'ticket.gif', '?m=rtqueuelist', trans('Requests Tracking'), 'h', 50);
		$this->AddMenu(trans('Timetable'), 'calendar.gif', '?m=eventlist', trans('Events Tracking'), 'v', 55);
	}

	function _postinit()
	{
		return TRUE;
	}

	/*
	 *  Basic functions (various)
	 */

	function AddMenu($name = '', $img = '', $link = '', $tip = '', $accesskey = '', $prio = 99)
	{
		if($name != '')
		{
			foreach(array('name', 'img', 'link', 'tip', 'accesskey', 'prio') as $key)
				$this->MENU[$key][] = $$key;
			array_multisort($this->MENU[prio], SORT_NUMERIC, SORT_ASC, $this->MENU[name], SORT_STRING, SORT_ASC, $this->MENU[img], $this->MENU[link], $this->MENU[accesskey], $this->MENU[tip]);
			return TRUE;
		}
		return FALSE;
	}

	/*
	 *  Logging
	 *	0 - disabled
	 *	1 - system log in and modules calls without access privileges 
	 *	2 - as above, addition and deletion
	 *	3 - as above, and changes
	 *	4 - paranoid, id est all above and all modules calls
	 */

	function Log($loglevel=0, $message=NULL)
	{
		if( $loglevel <= $this->CONFIG['phpui']['loglevel'] && $message )
		{
			$this->DB->Execute('INSERT INTO syslog (time, adminid, level, message)
					    VALUES (?NOW?, ?, ?, ?)', array($this->AUTH->id, $loglevel, $message));
			//I think, we can ommit SetTS('syslog')
		}
	}
	
	/*
	 *  Database functions (backups, timestamps)
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

	function DBDump($filename=NULL,$gzipped=FALSE) // dump database to file
	{
		if(! $filename)
			return FALSE;
		if (($gzipped)&&(extension_loaded('zlib')))
			$dumpfile = gzopen($filename,'w'); 
		else
			$dumpfile = fopen($filename,'w');

		if($dumpfile)
		{
			foreach($this->DB->ListTables() as $tablename)
			{
				fputs($dumpfile,"DELETE FROM $tablename;\n");
				$this->DB->Execute('SELECT * FROM '.$tablename);
				while($row = $this->DB->_driver_fetchrow_assoc())
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
			if (($gzipped)&&(extension_loaded('zlib')))
				gzclose($dumpfile); 
			else
				fclose($dumpfile); 
		}
		else
			return FALSE;
	}

	function DatabaseCreate($gzipped=FALSE) // create database backup
	{
		$basename = 'lms-'.time().'-'.DBVERSION;
		if (($gzipped)&&(extension_loaded('zlib')))
			return $this->DBDump($this->CONFIG['directories']['backup_dir'].'/'.$basename.'.sql.gz',TRUE);
		else
			return $this->DBDump($this->CONFIG['directories']['backup_dir'].'/'.$basename.'.sql');
	}

	/*
	 *  Users (Administrators)
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
		return $this->DB->GetAll('SELECT id, name FROM admins WHERE deleted=0 ORDER BY login ASC');
	}

	function GetAdminList() // zwraca listê administratorów
	{
		if($adminslist = $this->DB->GetAll('SELECT id, login, name, lastlogindate, lastloginip FROM admins WHERE deleted=0 ORDER BY login ASC'))
		{
			foreach($adminslist as $idx => $row)
			{
				if($row['id']==$this->AUTH->id)
				{
					$row['lastlogindate'] = $this->AUTH->last;
					$adminslist[$idx]['lastlogindate'] = $this->AUTH->last;
					$row['lastloginip'] = $this->AUTH->lastip;
					$adminslist[$idx]['lastloginip'] = $this->AUTH->lastip;
				}
				
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
			if($admininfo['id']==$this->AUTH->id)
			{
				$admininfo['lastlogindate'] = $this->AUTH->last;
				$admininfo['lastloginip'] = $this->AUTH->lastip;
			}

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
	 *  Customers (formerly Users) functions
	 */

	function GetUserName($id)
	{
		return $this->DB->GetOne('SELECT '.$this->DB->Concat('UPPER(lastname)',"' '",'name').' FROM users WHERE id=?', array($id));
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
		return $this->DB->GetOne('SELECT COUNT(userid) FROM assignments WHERE tariffid = ?', array($id));
	}

	function UserAdd($useradd)
	{
		if($this->DB->Execute('INSERT INTO users (name, lastname, phone1, phone2, phone3, gguin, address, zip, city, email, nip, pesel, status, creationdate, creatorid, info, serviceaddr, message, pin) VALUES (?, UPPER(?), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?NOW?, ?, ?, ?, ?, ?)', array(ucwords($useradd['name']),  $useradd['lastname'], $useradd['phone1'], $useradd['phone2'], $useradd['phone3'], $useradd['gguin'], $useradd['address'], $useradd['zip'], $useradd['city'], $useradd['email'], $useradd['nip'], $useradd['pesel'], $useradd['status'], $this->AUTH->id, $useradd['info'], $useradd['serviceaddr'], $useradd['message'], $useradd['pin']))) {
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
		$this->SetTS('assignments');
		$res1=$this->DB->Execute('DELETE FROM nodes WHERE ownerid=?', array($id));
		$res2=$this->DB->Execute('DELETE FROM userassignments WHERE userid=?', array($id));
		$res3=$this->DB->Execute('UPDATE users SET deleted=1 WHERE id=?', array($id));
		$res4=$this->DB->Execute('DELETE FROM assignments WHERE userid=?', array($id));
		return $res1 || $res2 || $res3 || $res4;
	}

	function UserUpdate($userdata)
	{
		$this->SetTS('users');
		return $this->DB->Execute('UPDATE users SET status=?, phone1=?, phone2=?, phone3=?, address=?, zip=?, city=?, email=?, gguin=?, nip=?, pesel=?, moddate=?NOW?, modid=?, info=?, serviceaddr=?, lastname=UPPER(?), name=?, deleted=0, message=?, pin=? WHERE id=?', array( $userdata['status'], $userdata['phone1'], $userdata['phone2'], $userdata['phone3'], $userdata['address'], $userdata['zip'], $userdata['city'], $userdata['email'], $userdata['gguin'], $userdata['nip'], $userdata['pesel'], $this->AUTH->id, $userdata['info'], $userdata['serviceaddr'], $userdata['lastname'], ucwords($userdata['name']), $userdata['message'], $userdata['pin'], $userdata['id'] ) );
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
			$result['creationdateh'] = date('Y/m/d, H:i',$result['creationdate']);
			$result['moddateh'] = date('Y/m/d, H:i',$result['moddate']);
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
			$week = $this->DB->GetAllByKey('SELECT users.id AS id, SUM(value)*4 AS value FROM assignments, tariffs, users WHERE userid = users.id AND tariffid = tariffs.id AND deleted = 0 AND period = 0 AND suspended = 0 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY users.id', 'id');
			$month = $this->DB->GetAllByKey('SELECT users.id AS id, SUM(value) AS value FROM assignments, tariffs, users WHERE userid = users.id AND tariffid = tariffs.id AND deleted = 0 AND period = 1 AND suspended = 0 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY users.id', 'id');
			$quarter = $this->DB->GetAllByKey('SELECT users.id AS id, SUM(value)/3 AS value FROM assignments, tariffs, users WHERE userid = users.id AND tariffid = tariffs.id AND deleted = 0 AND period = 2 AND suspended = 0 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY users.id', 'id');
			$year = $this->DB->GetAllByKey('SELECT users.id AS id, SUM(value)/12 AS value FROM assignments, tariffs, users WHERE userid = users.id AND tariffid = tariffs.id AND deleted = 0 AND period = 3 AND suspended = 0 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY users.id', 'id');

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

	function GetUserList($order='username,asc', $state=NULL, $network=NULL, $usergroup=NULL, $time=NULL)
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
		$online = ($state == 7) ? 1 : 0;
		
		if($state>3)
			$state = 0;

		if($network) 
			$net = $this->GetNetworkParams($network);
		
		if($userlist = $this->DB->GetAll( 
				'SELECT users.id AS id, '.$this->DB->Concat('UPPER(lastname)',"' '",'users.name').' AS username, status, email, phone1, users.address, gguin, nip, pesel, zip, city, users.info AS info, '
				.($network ? 'COALESCE(SUM((type * -2 + 7) * value), 0.00)/(CASE COUNT(DISTINCT nodes.id) WHEN 0 THEN 1 ELSE COUNT(DISTINCT nodes.id) END) AS balance ' : 'COALESCE(SUM((type * -2 + 7) * value), 0.00) AS balance ')
				.'FROM users LEFT JOIN cash ON (users.id=cash.userid AND (type = 3 OR type = 4)) '
				.($network ? 'LEFT JOIN nodes ON (users.id=ownerid) ' : '')
				.($usergroup ? 'LEFT JOIN userassignments ON (users.id=userassignments.userid) ':'')
				.'WHERE deleted = '.$deleted
				.($state !=0 ? ' AND status = '.$state :'') 
				.($network ? ' AND (ipaddr > '.$net['address'].' AND ipaddr < '.$net['broadcast'].')' : '')
				.($usergroup ? ' AND usergroupid='.$usergroup : '')
				.($time ? ' AND time < '.$time : '')
				.' GROUP BY users.id, lastname, users.name, status, email, phone1, users.address, gguin, nip, pesel, zip, city, users.info '
		// ten fragment nie chcial dzialac na mysqlu		
		//		.($indebted ? ' HAVING SUM((type * -2 + 7) * value) < 0 ' : '')
				.($sqlord !='' ? $sqlord.' '.$direction:'')
				))
		{
			$week = $this->DB->GetAllByKey('SELECT users.id AS id, SUM(value)*4 AS value FROM assignments, tariffs, users WHERE userid = users.id AND tariffid = tariffs.id AND deleted = 0 AND period = 0 AND suspended = 0 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY users.id', 'id');
			$month = $this->DB->GetAllByKey('SELECT users.id AS id, SUM(value) AS value FROM assignments, tariffs, users WHERE userid = users.id AND tariffid = tariffs.id AND deleted = 0 AND period = 1 AND suspended = 0 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY users.id', 'id');
			$quarter = $this->DB->GetAllByKey('SELECT users.id AS id, SUM(value)/3 AS value FROM assignments, tariffs, users WHERE userid = users.id AND tariffid = tariffs.id AND deleted = 0 AND period = 2 AND suspended = 0 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY users.id', 'id');
			$year = $this->DB->GetAllByKey('SELECT users.id AS id, SUM(value)/12 AS value FROM assignments, tariffs, users WHERE userid = users.id AND tariffid = tariffs.id AND deleted = 0 AND period = 3 AND suspended = 0 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY users.id', 'id');

			$access = $this->DB->GetAllByKey('SELECT ownerid AS id, SUM(access) AS acsum, COUNT(access) AS account FROM nodes GROUP BY ownerid','id');
			$warning = $this->DB->GetAllByKey('SELECT ownerid AS id, SUM(warning) AS warnsum, COUNT(warning) AS warncount FROM nodes GROUP BY ownerid','id');
			if($online)
				$onlines = $this->DB->GetAllByKey('SELECT MAX(lastonline) AS online, ownerid AS id FROM nodes GROUP BY ownerid','id');
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
					
				if($online)
					if($onlines[$row['id']]['online'] > time()-$this->CONFIG['phpui']['lastonline_limit'])
						$userlist2[] = $userlist[$idx];
				
				if($indebted)
					if($userlist[$idx]['balance'] < 0)
						$userlist2[] = $userlist[$idx];
			}
			if ($disabled || $online || $indebted)
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
		if($result = $this->DB->GetAll('SELECT id, name, mac, ipaddr, inet_ntoa(ipaddr) AS ip, passwd, access, warning, info FROM nodes WHERE ownerid=? ORDER BY name ASC', array($id)))
		{
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
			if ($taxvalue == trans('tax-free'))
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
				$saldolist['invoicepaid'][$i] = 1;

				switch ($saldolist['type'][$i]){

					case '3':
						$saldolist['after'][$i] = round(($saldolist['before'][$i] + $saldolist['value'][$i]),4);
						$saldolist['name'][$i] = trans('payment');
					break;

					case '4':
						$saldolist['after'][$i] = round(($saldolist['before'][$i] - $saldolist['value'][$i]),4);
						$saldolist['name'][$i] = trans('covenant');
						if ($saldolist['invoiceid'][$i])
							$saldolist['invoicepaid'][$i] = $this->IsInvoicePaid($saldolist['invoiceid'][$i]);
					break;
				}

				$saldolist['date'][$i] = date('Y/m/d H:i',$saldolist['time'][$i]);

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
	 * Customer groups
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
	 *  Nodes functions
	 */

	function GetNodeOwner($id)
	{
		return $this->DB->GetOne('SELECT ownerid FROM nodes WHERE id=?', array($id));
	}

	function NodeUpdate($nodedata)
	{
		$this->SetTS('nodes');
		return $this->DB->Execute('UPDATE nodes SET name=UPPER(?), ipaddr=inet_aton(?), mac=UPPER(?), passwd=?, netdev=?, moddate=?NOW?, modid=?, access=?, warning=?, ownerid=?, info=? WHERE id=?', array($nodedata['name'], $nodedata['ipaddr'], $nodedata['mac'], $nodedata['passwd'], $nodedata['netdev'], $this->AUTH->id, $nodedata['access'], $nodedata['warning'], $nodedata['ownerid'], $nodedata['info'], $nodedata['id']));
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
		return $this->DB->GetOne('SELECT id FROM nodes WHERE name=UPPER(?)', array($name));
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
		if($result = $this->DB->GetRow('SELECT id, name, ownerid, ipaddr, inet_ntoa(ipaddr) AS ip, mac, passwd, access, warning, creationdate, moddate, creatorid, modid, netdev, lastonline, info FROM nodes WHERE id=?', array($id)))
		{
			$result['createdby'] = $this->GetAdminName($result['creatorid']);
			$result['modifiedby'] = $this->GetAdminName($result['modid']);
			$result['creationdateh'] = date('Y/m/d, H:i',$result['creationdate']);
			$delta = time()-$result['lastonline'];
			if($delta>$this->CONFIG['phpui']['lastonline_limit'])
			{
				if($delta>59)
					$result['lastonlinedate'] = trans('$0 ago ($1)', uptimef($delta), date('Y/m/d, H:i',$result['lastonline']));
				else
					$result['lastonlinedate'] = '('.date('Y/m/d, H:i',$result['lastonline']).')';
			}
			else
				$result['lastonlinedate'] = trans('online');
			$result['moddateh'] = date('Y/m/d, H:i',$result['moddate']);
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

		if($nodelist = $this->DB->GetAll('SELECT nodes.id AS id, ipaddr, inet_ntoa(ipaddr) AS ip, mac, nodes.name AS name, ownerid, access, warning, netdev, '.$this->DB->Concat('UPPER(lastname)',"' '",'users.name').' AS owner, lastonline, nodes.info AS info FROM nodes, users WHERE ownerid = users.id AND ownerid > 0'.($sqlord != '' ? $sqlord.' '.$direction : '')))
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
		
		if($nodelist = $this->DB->GetAll('SELECT id, ipaddr, inet_ntoa(ipaddr) AS ip, mac, name, ownerid, access, warning, info FROM nodes '.$searchargs.' '.($sqlord != '' ? $sqlord.' '.$direction : '')))
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
					array_multisort($ownertable['owner'],($direction == 'desc' ? SORT_DESC : SORT_ASC),$ownertable['idx']);
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
		if($this->DB->Execute('INSERT INTO nodes (name, mac, ipaddr, ownerid, passwd, creatorid, creationdate, access, warning, info) VALUES (?, ?, inet_aton(?), ?, ?, ?, ?NOW?, ?, ?, ?)', array(strtoupper($nodedata['name']),strtoupper($nodedata['mac']),$nodedata['ipaddr'],$nodedata['ownerid'],$nodedata['passwd'],$this->AUTH->id, $nodedata['access'], $nodedata['warning'], $nodedata['info'])))
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

	function GetNetDevLinkedNodes($id)
	{
		return $this->DB->GetAll('SELECT nodes.id AS id, nodes.name AS name, linktype, ipaddr, inet_ntoa(ipaddr) AS ip, netdev, '.$this->DB->Concat('UPPER(lastname)',"' '",'users.name').' AS owner FROM nodes, users WHERE ownerid = users.id AND netdev=? AND ownerid > 0 ORDER BY nodes.name ASC', array($id));
	}

	function NetDevLinkNode($id, $netid, $type=NULL)
	{
		if($netid != 0)
		{
			$netdev = $this->GetNetDev($netid);
			if( $this->GetNodeOwner($id) )
				if( $netdev['takenports'] >= $netdev['ports'])
					return FALSE;
		}
		if($type==NULL)
			$this->DB->Execute('UPDATE nodes SET netdev=? WHERE id=?', array($netid, $id));
		else
			$this->DB->Execute('UPDATE nodes SET netdev=?, linktype=? WHERE id=?', array($netid,$type,$id));
		$this->SetTS('nodes');
		return TRUE;
	}

	function SetNetDevLinkType($dev1, $dev2, $type=0)
	{
		$this->SetTS('netlinks');
		return $this->DB->Execute('UPDATE netlinks SET type=? WHERE (src=? AND dst=?) OR (dst=? AND src=?)', array($type, $dev1, $dev2, $dev1, $dev2));
	}

	function SetNodeLinkType($node, $type=0)
	{
		$this->SetTS('nodes');
		return $this->DB->Execute('UPDATE nodes SET linktype=? WHERE id=?', array($type, $node));
	}
	
	/*
	 *  Tarrifs and finances
	 */

	function GetUserTariffsValue($id)
	{
		return $this->DB->GetOne('SELECT sum(value) FROM assignments, tariffs WHERE tariffid = tariffs.id AND userid=? AND suspended = 0 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0)', array($id));
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
						$row['period'] = trans('weekly');
						$row['at'] = strftime("%a",mktime(0,0,0,0,$row['at']+5,0));
					break;
					
					case 1:
						$row['period'] = trans('monthly');
					break;
					
					case 2:
						$row['period'] = trans('quarterly');
						$row['at'] = sprintf('%02d/%02d', $row['at']%100, $row['at']/100+1);
					break;
					
					case 3:
						$row['period'] = trans('yearly');
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
		$cdate = $invoice['invoice']['cdate'] ? $invoice['invoice']['cdate'] : time();
		
		if($this->CONFIG['invoices']['monthly_numbering'])
		{
			$start = mktime(0, 0, 0, date('n',$cdate), 1, date('Y',$cdate));
			$end = mktime(0, 0, 0, date('n',$cdate)+1, 1, date('Y',$cdate));
		}
		else
		{
			$start = mktime(0, 0, 0, 1, 1, date('Y',$cdate));
			$end = mktime(0, 0, 0, 1, 1, date('Y',$cdate)+1);
		}
		
		$number = $invoice['invoice']['number'];
		$this->DB->Execute('INSERT INTO invoices (number, cdate, paytime, paytype, customerid, name, address, nip, pesel, zip, city, phone, finished) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)', array($number, $cdate, $invoice['invoice']['paytime'], $invoice['invoice']['paytype'], $invoice['customer']['id'], $invoice['customer']['username'], $invoice['customer']['address'], $invoice['customer']['nip'], $invoice['customer']['pesel'], $invoice['customer']['zip'], $invoice['customer']['city'], $invoice['customer']['phone1']));
		$iid = $this->DB->GetOne('SELECT id FROM invoices WHERE number = ? AND cdate = ?', array($number,$cdate));
		
		$itemid=0;
		foreach($invoice['contents'] as $idx => $item)
		{
			$itemid++;
			$item['valuebrutto'] = str_replace(',','.',$item['valuebrutto']);
			$item['count'] = str_replace(',','.',$item['count']);

			if($item['taxvalue'] || $item['taxvalue'] == '0')
				$this->DB->Execute('INSERT INTO invoicecontents (invoiceid, itemid, value, taxvalue, pkwiu, content, count, description, tariffid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', array(
					$iid, 
					$itemid, 
					$item['valuebrutto'], 
					$item['taxvalue'], 
					$item['pkwiu'],
					$item['jm'], 
					$item['count'], 
					$item['name'], 
					$item['tariffid']));
				
			else
				$this->DB->Execute('INSERT INTO invoicecontents (invoiceid, itemid, value, taxvalue, pkwiu, content, count, description, tariffid) VALUES (?, ?, ?, NULL, ?, ?, ?, ?, ?)', 
				array(
					$iid,
					$itemid ,
					$item['valuebrutto'], 
					$item['pkwiu'], 
					$item['jm'], 
					$item['count'], 
					$item['name'], 
					$item['tariffid']));
			$this->AddBalance(array('type' => 4, 'value' => $item['valuebrutto']*$item['count'], 'taxvalue' => $item['taxvalue'], 'userid' => $invoice['customer']['id'], 'comment' => $item['name'], 'invoiceid' => $iid, 'itemid'=>$itemid));
		}
		
		$this->SetTS('invoices');
		$this->SetTS('invoicecontents');

		return $iid;
	}

	function InvoiceUpdate($invoice)
	{
		$cdate = $invoice['invoice']['cdate'] ? $invoice['invoice']['cdate'] : time();
		
		$iid = $invoice['invoice']['id'];

		$this->DB->Execute('UPDATE invoices SET cdate = ?, paytime = ?, paytype = ?, customerid = ?, name = ?, address = ?, nip = ?, pesel = ?, zip = ?, city = ?, phone = ? WHERE id = ?', array($cdate, $invoice['invoice']['paytime'], $invoice['invoice']['paytype'], $invoice['customer']['id'], $invoice['customer']['username'], $invoice['customer']['address'], $invoice['customer']['nip'], $invoice['customer']['pesel'], $invoice['customer']['zip'], $invoice['customer']['city'], $invoice['customer']['phone1'], $iid));
		$this->DB->Execute('DELETE FROM invoicecontents WHERE invoiceid = ?', array($iid));
		$this->DB->Execute('DELETE FROM cash WHERE invoiceid = ? AND type = 4', array($iid));
		//if invoice was paid (then you need to manual bind orphant payments with covenants)
		$this->DB->Execute('UPDATE cash SET invoiceid = 0, itemid = 0, userid = ? WHERE invoiceid = ?', array($invoice['customer']['id'], $iid));
		
		$itemid=0;
		foreach($invoice['contents'] as $idx => $item)
		{
			$itemid++;
			
			if($item['taxvalue'] || $item['taxvalue'] == '0')
				$this->DB->Execute('INSERT INTO invoicecontents (invoiceid, itemid, value, taxvalue, pkwiu, content, count, description, tariffid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', 
				array(
					$iid, 
					$itemid, 
					$item['valuebrutto'], 
					$item['taxvalue'], 
					$item['pkwiu'],
					$item['jm'], 
					$item['count'], 
					$item['name'], 
					$item['tariffid']));
			else
				$this->DB->Execute('INSERT INTO invoicecontents (invoiceid, itemid, value, taxvalue, pkwiu, content, count, description, tariffid) VALUES (?, ?, ?, NULL, ?, ?, ?, ?, ?)', 
				array(
					$iid,
					$itemid,
					$item['valuebrutto'], 
					$item['pkwiu'], 
					$item['jm'], 
					$item['count'], 
					$item['name'], 
					$item['tariffid']));
			$this->AddBalance(array('type' => 4, 'time' => $cdate, 'value' => $item['valuebrutto']*$item['count'], 'taxvalue' => $item['taxvalue'], 'userid' => $invoice['customer']['id'], 'comment' => $item['name'], 'invoiceid' => $iid, 'itemid'=>$itemid));
		}
		
		$this->SetTS('invoices');
		$this->SetTS('invoicecontents');
	}

	function InvoiceDelete($invoiceid)
	{
		$this->DB->Execute('DELETE FROM invoices WHERE id = ?', array($invoiceid));
		$this->DB->Execute('DELETE FROM invoicecontents WHERE invoiceid = ?', array($invoiceid));
		$this->DB->Execute('DELETE FROM cash WHERE invoiceid = ? AND type = 4', array($invoiceid));
		$this->DB->Execute('UPDATE cash SET invoiceid = 0, itemid = 0 WHERE invoiceid = ?', array($invoiceid));
		$this->SetTS('invoices');
		$this->SetTS('invoicecontents');
	}

	function InvoiceContentDelete($invoiceid, $itemid=0)
	{
		if($itemid)
		{
			$this->DB->Execute('DELETE FROM invoicecontents WHERE invoiceid=? AND itemid=?', array($invoiceid, $itemid));
			
			if(!$this->DB->GetOne('SELECT COUNT(*) FROM invoicecontents WHERE invoiceid=?', array($invoiceid)))
			{
				// if that was the last item of invoice contents
				$this->DB->Execute('DELETE FROM invoices WHERE id = ?', array($invoiceid));
			}
			$this->DB->Execute('DELETE FROM cash WHERE invoiceid = ? AND itemid = ? AND type = 4', array($invoiceid, $itemid));
			$this->DB->Execute('UPDATE cash SET invoiceid=0, itemid=0 WHERE invoiceid=? AND itemid=?', array($invoiceid, $itemid));
			$this->SetTS('invoices');
			$this->SetTS('invoicecontents');
		}
		else
			$this->InvoiceDelete($invoiceid);
	}

	function InvoicesReport($from, $to)
	{
		if($result = $this->DB->GetAll('SELECT id, number, cdate, customerid, name, address, zip, city, nip, pesel, taxvalue, SUM(value*count) AS value FROM invoices LEFT JOIN invoicecontents ON invoiceid = id WHERE finished = 1 AND (cdate BETWEEN ? AND ?) GROUP BY id, number, taxvalue, cdate, customerid, name, address, zip, city, nip, pesel, finished ORDER BY cdate ASC', array($from, $to)))
		{
			foreach($result as $idx => $row)
			{
				$id = $row['id'];
				$value = round($row['value'], 2);
				$list[$id]['custname'] = $row['name'];
				$list[$id]['custaddress'] = $row['zip'].' '.$row['city'].', '.$row['address'];
				$list[$id]['nip'] = ($row['nip'] ? trans('TEN').' '.$row['nip'] : ($row['pesel'] ? trans('SSN').' '.$row['pesel'] : ''));
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
						     $list[$id]['tax7'] += round($value - ($value/1.07), 2);
						     $list[$id]['val7'] += $value - $list[$id]['tax7'];
					    	     $list[$id]['tax']   += $list[$id]['tax7'];
						     $list['sum']['tax7'] += $list[$id]['tax7'];
						     $list['sum']['val7'] += $list[$id]['val7'];
						     $list['sum']['tax']   += $list[$id]['tax'];

					    break;
					    case '22.0':
						     $list[$id]['tax22'] += round($value - ($value/1.22), 2);
						     $list[$id]['val22'] += $value - $list[$id]['tax22'];
					    	     $list[$id]['tax']   += $list[$id]['tax22'];
						     $list['sum']['tax22'] += $list[$id]['tax22'];
						     $list['sum']['val22'] += $list[$id]['val22'];
						     $list['sum']['tax']   += $list[$id]['tax'];
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

	function GetInvoicesList($search=NULL, $cat=NULL, $group=NULL, $order)
	{
		if($order=='')
			$order='id,asc';

		list($order,$direction) = explode(',',$order);

		($direction=='desc') ? $direction = 'desc' : $direction = 'asc';

		switch($order)
		{
			case 'id':
				$sqlord = ' ORDER BY invoices.id';
			break;
			case 'cdate':
				$sqlord = ' ORDER BY invoices.cdate';
			break;
			case 'number':
				$sqlord = ' ORDER BY number';
			break;
			case 'value':
				$sqlord = ' ORDER BY value';
			break;
			case 'count':
				$sqlord = ' ORDER BY count';
			break;
			case 'name':
				$sqlord = ' ORDER BY name';
			break;
		}
		if($search && $cat)
		{
			switch($cat)
			{
				case 'value':
					$where = ' AND value*count = '.intval($search);
					break;
				case 'number':
					$where = ' AND number = '.intval($search);
					break;
				case 'cdate':
					$where = ' AND cdate >= '.$search.' AND cdate < '.($search+86400);
					break;
				case 'nip':
					$where = ' AND nip = \''.$search.'\'';
					break;
				case 'customerid':
					$where = ' AND customerid = '.intval($search);
					break;
				case 'name':
					$where = ' AND name ?LIKE? \'%'.$search.'%\'';
					break;
				case 'address':
					$where = ' AND address ?LIKE? \'%'.$search.'%\'';
					break;
			}
		}

		if($result = $this->DB->GetAll('SELECT id, number, cdate, customerid, name, address, zip, city, finished, 
						SUM(value*count) AS value, COUNT(invoiceid) AS count 
						FROM invoices, invoicecontents 
						WHERE invoiceid = id AND finished = 1 '
						.$where
						.' GROUP BY id, number, cdate, customerid, name, address, zip, city, finished '
						.$sqlord))
		{
			$inv_paid = $this->DB->GetAllByKey('SELECT invoiceid AS id, SUM(CASE type WHEN 3 THEN value ELSE -value END) AS sum FROM cash WHERE invoiceid!=0 GROUP BY invoiceid','id');
			
			if($group['group'])
				$users = $this->DB->GetAllByKey('SELECT userid AS id FROM userassignments WHERE usergroupid=?', 'id', array($group['group']));

			foreach($result as $idx => $row)
			{
				$result[$idx]['year'] = date('Y',$row['cdate']);
				$result[$idx]['month'] = date('m',$row['cdate']);
				$result[$idx]['paid'] = ( $inv_paid[$row['id']]['sum'] >=0 ? TRUE : FALSE );
				
				if($group['group'])
					if(!$group['exclude'] && $users[$result[$idx]['customerid']])
						$result1[] = $result[$idx]; 
					elseif($group['exclude'] && !$users[$result[$idx]['customerid']])
						$result1[] = $result[$idx];
			}
			
			if($group['group'])
				$result = $result1;
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
			if($result['content'] = $this->DB->GetAll('SELECT value, taxvalue, pkwiu, content, count, description, tariffid, itemid FROM invoicecontents WHERE invoiceid=?', array($invoiceid)))
				foreach($result['content'] as $idx => $row)
				{
					$result['content'][$idx]['basevalue'] = round(($row['value'] / (100 + $row['taxvalue']) * 100),2);
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
			$result['totalg'] = round( ($result['total'] - floor($result['total'])) * 100);
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
			$assigned = $this->DB->GetAllByKey('SELECT tariffid, COUNT(*) AS count, SUM(CASE period WHEN 0 THEN value*4 WHEN 1 THEN value WHEN 2 THEN value/3 WHEN 3 THEN value/12 END) AS value 
						FROM assignments, tariffs 
						WHERE tariffid = tariffs.id AND suspended = 0 
						AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0)
						GROUP BY tariffid', 'tariffid');
			
			foreach($tarifflist as $idx => $row)
			{
				$suspended = $this->DB->GetRow('SELECT COUNT(*) AS count, SUM(CASE a.period WHEN 0 THEN t.value*4 WHEN 1 THEN t.value WHEN 2 THEN t.value/3 WHEN 3 THEN t.value/12 END) AS value
						FROM assignments a LEFT JOIN tariffs t ON (t.id = a.tariffid), assignments b
						WHERE a.userid = b.userid AND a.tariffid = ? AND b.tariffid = 0 AND a.suspended = 0
						AND (b.datefrom <= ?NOW? OR b.datefrom = 0) AND (b.dateto > ?NOW? OR b.dateto = 0)', array($row['id']));
			
				$tarifflist[$idx]['users'] = $this->GetUsersWithTariff($row['id']);
				$tarifflist[$idx]['userscount'] = $this->DB->GetOne("SELECT COUNT(DISTINCT userid) FROM assignments WHERE tariffid = ?", array($row['id']));
				// count of 'active' assignments
				$tarifflist[$idx]['assignmentcount'] =  $assigned[$row['id']]['count'] - $suspended['count'];
				// avg monthly income
				$tarifflist[$idx]['income'] = $assigned[$row['id']]['value'] - $suspended['value'];
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
		$result['users'] = $this->DB->GetAll('SELECT users.id AS id, COUNT(users.id) AS cnt, '.$this->DB->Concat('upper(lastname)',"' '",'name').' AS username FROM assignments, users WHERE users.id = userid AND deleted = 0 AND tariffid = ? GROUP BY users.id, username ORDER BY username', array($id));
		
		$assigned = $this->DB->GetRow('SELECT COUNT(*) AS count, SUM(CASE period WHEN 0 THEN value*4 WHEN 1 THEN value WHEN 2 THEN value/3 WHEN 3 THEN value/12 END) AS value 
						FROM assignments, tariffs 
						WHERE tariffid = tariffs.id AND tariffid = ? AND suspended = 0 
						AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0)', array($id));
		
		$suspended = $this->DB->GetRow('SELECT COUNT(*) AS count, SUM(CASE a.period WHEN 0 THEN t.value*4 WHEN 1 THEN t.value WHEN 2 THEN t.value/3 WHEN 3 THEN t.value/12 END) AS value
						FROM assignments a LEFT JOIN tariffs t ON (t.id = a.tariffid), assignments b
						WHERE a.userid = b.userid AND a.tariffid = ? AND b.tariffid = 0 AND a.suspended = 0
						AND (b.datefrom <= ?NOW? OR b.datefrom = 0) AND (b.dateto > ?NOW? OR b.dateto = 0)', array($id));
		
		// count of all users with that tariff
		$result['userscount'] = sizeof($result['users']);
		// count of all assignments
		$result['count'] = $this->GetUsersWithTariff($id);
		// count of 'active' assignments
		$result['assignmentcount'] =  $assigned['count'] - $suspended['count'];
		// avg monthly income (without unactive assignments)
		$result['totalval'] = $assigned['value'] - $suspended['value'];

		$result['rows'] = ceil($result['userscount']/2);
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
				trans('tax-free') => $this->GetUserBalance($user_id, trans('tax-free'))
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
	
			if ($key == trans('tax-free'))
				$ret[$key] = $this->DB->Execute('INSERT INTO cash (time, adminid, type, value, taxvalue, userid, comment) VALUES (?NOW?, ?, ?, ?, NULL, ?, ?)', array($this->AUTH->id, 3 , round($val,2) , $user_id, trans('Accounted')));
			else
				$ret[$key] = $this->DB->Execute('INSERT INTO cash (time, adminid, type, value, taxvalue, userid, comment) VALUES (?NOW?, ?, ?, ?, ?, ?, ?)', array($this->AUTH->id, 3 , round($val,2) , $key, $user_id, trans('Accounted')));
		}
		return $ret;
	}

	function AddBalance($addbalance)
	{
		$this->SetTS('cash');
		$addbalance['value'] = str_replace(',','.',round($addbalance['value'],2));
		$addbalance['taxvalue'] = $addbalance['taxvalue']!='' ? str_replace(',','.',round($addbalance['taxvalue'],2)) : '';
		if($addbalance['time'])
			if($addbalance['taxvalue'] == '')
				return $this->DB->Execute('INSERT INTO cash (time, adminid, type, value, taxvalue, userid, comment, invoiceid, itemid) VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?)', array($addbalance['time'], ($addbalance['adminid'] ? $addbalance['adminid'] : $this->AUTH->id), $addbalance['type'], $addbalance['value'], $addbalance['userid'], $addbalance['comment'], ($addbalance['invoiceid'] ? $addbalance['invoiceid'] : 0 ), ($addbalance['itemid'] ? $addbalance['itemid'] : 0) ));
			else
				return $this->DB->Execute('INSERT INTO cash (time, adminid, type, value, taxvalue, userid, comment, invoiceid, itemid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', array($addbalance['time'], ($addbalance['adminid'] ? $addbalance['adminid'] : $this->AUTH->id), $addbalance['type'], $addbalance['value'], $addbalance['taxvalue'], $addbalance['userid'], $addbalance['comment'], ($addbalance['invoiceid'] ? $addbalance['invoiceid'] : 0), ($addbalance['itemid'] ? $addbalance['itemid'] : 0) ));
		else
			if($addbalance['taxvalue'] == '')
				return $this->DB->Execute('INSERT INTO cash (time, adminid, type, value, taxvalue, userid, comment, invoiceid, itemid) VALUES (?NOW?, ?, ?, ?, NULL, ?, ?, ?, ?)', array( ($addbalance['adminid'] ? $addbalance['adminid'] : $this->AUTH->id), $addbalance['type'], $addbalance['value'], $addbalance['userid'], $addbalance['comment'], ($addbalance['invoiceid'] ? $addbalance['invoiceid'] : 0), ($addbalance['itemid'] ? $addbalance['itemid'] : 0) ));
			else
				return $this->DB->Execute('INSERT INTO cash (time, adminid, type, value, taxvalue, userid, comment, invoiceid, itemid) VALUES (?NOW?, ?, ?, ?, ?, ?, ?, ?, ?)', array( ($addbalance['adminid'] ? $addbalance['adminid'] : $this->AUTH->id), $addbalance['type'], $addbalance['value'], $addbalance['taxvalue'], $addbalance['userid'], $addbalance['comment'], ($addbalance['invoiceid'] ? $addbalance['invoiceid'] : 0), ($addbalance['itemid'] ? $addbalance['itemid'] : 0)  ));
	}

	function DelBalance($id)
	{
		$row = $this->DB->GetRow('SELECT invoiceid, itemid, type FROM cash WHERE id=?', array($id));
		
		if($row['type']=='4' && $row['invoiceid'] && $row['itemid'])
			$this->InvoiceContentDelete($row['invoiceid'], $row['itemid']);
		else
			$this->DB->Execute('DELETE FROM cash WHERE id=?', array($id));
		
		$this->SetTS('cash');
	}
	
	function GetBalanceList()
	{
		$adminlist = $this->DB->GetAllByKey('SELECT id, name FROM admins','id');
		$userslist = $this->DB->GetAllByKey('SELECT id, '.$this->DB->Concat('UPPER(lastname)',"' '",'name').' AS username FROM users','id');
		if($balancelist = $this->DB->GetAll('SELECT id, time, adminid, type, value, taxvalue, userid, comment, invoiceid FROM cash ORDER BY time, id'))
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
						$balancelist[$idx]['type'] = trans('income');
						$balancelist[$idx]['after'] = $balancelist[$idx]['before'] + $balancelist[$idx]['value'];
						$balancelist['income'] = $balancelist['income'] + $balancelist[$idx]['value'];
					break;
					case 2:
						$balancelist[$idx]['type'] = trans('expense');
						$balancelist[$idx]['after'] = $balancelist[$idx]['before'] - $balancelist[$idx]['value'];
						$balancelist['expense'] = $balancelist['expense'] + $balancelist[$idx]['value'];
					break;
					case 3:
						$balancelist[$idx]['type'] = trans('cust. payment');
						$balancelist[$idx]['after'] = $balancelist[$idx]['before'] + $balancelist[$idx]['value'];
						$balancelist['incomeu'] = $balancelist['incomeu'] + $balancelist[$idx]['value'];
					break;
					case 4:
						$balancelist[$idx]['type'] = trans('cust. covenant');
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

	function GetItemUnpaidValue($invoiceid, $itemid)
	{
		return $this->DB->GetOne('SELECT SUM(CASE type WHEN 3 THEN value ELSE value*-1 END)*-1 FROM cash WHERE invoiceid=? AND itemid=?', array($invoiceid, $itemid));
	}

	/*
	*   Payments
	*/

	function GetPaymentList()
	{
		if ($paymentlist = $this->DB->GetAll('SELECT id, name, creditor, value, period, at, description FROM payments ORDER BY name ASC'))
			foreach($paymentlist as $idx => $row)
			{
				switch($row['period'])
				{
					case 0:
						$row['payday'] = trans('weekly ($0)', strftime("%a",mktime(0,0,0,0,$row['at']+5,0)));
					break;
					
					case 1:
						$row['payday'] = trans('monthly $0',$row['at']);
					break;
					
					case 2:
						$row['payday'] = trans('quarterly ($0)', sprintf('%02d/%02d', $row['at']%100, $row['at']/100+1));
					break;
					
					case 3:
						$row['payday'] = trans('yearly ($0)', date('d/m',($row['at']-1)*86400));
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
				$row['payday'] = trans('weekly ($0)', strftime("%a",mktime(0,0,0,0,$row['at']+5,0)));
			break;
				
			case 1:
				$row['payday'] = trans('monthly $0',$row['at']);
			break;
				
			case 2:
				$row['payday'] = trans('quarterly ($0)', sprintf('%02d/%02d', $row['at']%100, $row['at']/100+1));
			break;
			
			case 3:
				$row['payday'] = trans('yearly ($0)', date('d/m',($row['at']-1)*86400));
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
	 *  IP Networks
	 */

	function NetworkExists($id)
	{
		return ($this->DB->GetOne('SELECT * FROM networks WHERE id=?', array($id)) ? TRUE : FALSE);
	}

	function IsIPFree($ip)
	{
		return !($this->DB->GetOne('SELECT * FROM nodes WHERE ipaddr=inet_aton(?)', array($ip)) ? TRUE : FALSE);
	}

	function IsIPGateway($ip)
	{
		return ($this->DB->GetOne('SELECT gateway FROM networks WHERE gateway = ?', array($ip)) ? TRUE : FALSE);
	}

	function GetPrefixList()
	{
		for($i=30;$i>15;$i--)
		{
			$prefixlist['id'][] = $i;
			$prefixlist['value'][] = trans('$0 ($1 addresses)', $i, pow(2,32-$i));
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
		$network=$this->GetNetworkRecord($id);
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
			$network['free'] = $network['free'] - (ip_long($network['dhcpend']) - ip_long($network['dhcpstart']) + 1); 

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
			if( $longip == $network['addresslong'])
				$network['nodes']['name'][$i] = '*** NETWORK ***';
			if( $network['nodes']['address'][$i] == $network['broadcast'])
				$network['nodes']['name'][$i] = '*** BROADCAST ***';
			if( $network['nodes']['address'][$i] == $network['gateway'] && $node['name']=='')
				$network['nodes']['name'][$i] = '*** GATEWAY ***';
		}
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
			$result['nodename'][$pos] = 'DHCP';
		
		return $result;
	}

	/*
	 *   Network Devices
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
		return $this->DB->GetOne('SELECT COUNT(*) FROM netlinks WHERE src = ? OR dst = ?', array($id,$id)) + $this->DB->GetOne('SELECT COUNT(*) FROM nodes WHERE netdev = ? AND ownerid > 0', array($id));
	}

	function GetNetDevConnected($id)
	{
		return $this->DB->GetAll('SELECT type, (CASE src WHEN '.$id.' THEN src ELSE dst END) AS src, (CASE src WHEN '.$id.' THEN dst ELSE src END) AS dst FROM netlinks WHERE src = '.$id.' OR dst = '.$id);
	}

	function GetNetDevLinkType($dev1,$dev2)
	{
		return $this->DB->GetOne('SELECT type FROM netlinks WHERE (src=? AND dst=?) OR (dst=? AND src=?)', array($dev1,$dev2,$dev1,$dev2));
	}

	function GetNetDevConnectedNames($id)
	{
		// To powinno byæ lepiej zrobione...
		$list = $this->GetNetDevConnected($id);
		$i = 0;
		if ($list) 
		{
			foreach($list as $row)
			{
				$names[$i] = $this->GetNetDev($row['dst']);
				$names[$i]['linktype'] = $this->GetNetDevLinkType($row['dst'],$id);
				$i++;
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
				$query = $query.' AND id!='.$row['dst'];
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
		$nodes = GetNetDevLinkedNodes($id);
		if ($nodes) foreach($nodes as $node) {
			$this->NetDevLinkNode($node['id'],0);
		}
	}
	
	function NetDevReplace($sid, $did)
	{
		$dev1 = $this->GetNetDev($sid);
		$dev2 = $this->GetNetDev($did);
		$location = $dev1['location'];
		$dev1['location'] = $dev2['location'];
		$dev2['location'] = $location;
		$links1 = $this->GetNetDevConnected($sid);
		$links2 = $this->GetNetDevConnected($did);
		$nodes1 = $this->GetNetDevLinkedNodes($sid);
		$nodes2 = $this->GetNetDevLinkedNodes($did);
		$this->NetDevDelLinks($sid);
		$this->NetDevDelLinks($did);
		if ($links1) foreach($links1 as $row) {
			$this->NetDevLink($did,$row['dst'],$row['type']);
		}
		if ($links2) foreach($links2 as $row) {
			$this->NetDevLink($sid,$row['dst'], $row['type']);
		}
		if ($nodes1) foreach($nodes1 as $row) {
			$this->NetDevLinkNode($row['id'],$did);
		}
		if ($nodes2) foreach($nodes2 as $row) {
			$this->NetDevLinkNode($row['id'],$sid);
		}
		$this->NetDevUpdate($dev1);
		$this->NetDevUpdate($dev2);
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

	function NetDevLink($dev1, $dev2, $type=0)
	{
		if($dev1 != $dev2)
		{
			if($this->IsNetDevLink($dev1,$dev2))
				return FALSE;
			
			$netdev1 = $this->GetNetDev($dev1);
			$netdev2 = $this->GetNetDev($dev2);
			
			if( $netdev1['takenports'] >= $netdev1['ports'] || $netdev2['takenports'] >= $netdev2['ports'])
				return FALSE;
			
			$this->DB->Execute('INSERT INTO netlinks (src, dst, type) VALUES (?, ?, ?)', array($dev1, $dev2, $type)); 
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
		return $this->DB->GetAll('SELECT id, name, ipaddr, inet_ntoa(ipaddr) AS ip, mac, access, info FROM nodes WHERE ownerid=0 AND netdev=?', array($id));
	}
	
	/*
	 * Helpdesk
	 *
         * Ticket States:
	 *
	 * 0 - new
	 * 1 - open
	 * 2 - resolved
	 * 3 - dead (similiar to resolved, but not resolved)
	 *
	 */
	 
	var $rtstates = array(0 => RT_NEW, 1 => RT_OPEN, 2 => RT_RESOLVED, 3 => RT_DEAD);

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
	
		list($order,$direction) = explode(',',$order);

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

		switch($state)
		{
			case '0':
			case '1':
			case '2':
			case '3':
				$statefilter = 'AND state = '.$state;
				break;
			case '-1':
				$statefilter = 'AND state != 2';
				break;
		}

		if($result = $this->DB->GetAll('SELECT rttickets.id AS id, rttickets.userid AS userid, requestor, rttickets.subject AS subject, state, owner AS ownerid, admins.name AS ownername, '.$this->DB->Concat('UPPER(users.lastname)',"' '",'users.name').' AS username, rttickets.createtime AS createtime, MAX(rtmessages.createtime) AS lastmodified 
		    FROM rttickets LEFT JOIN rtmessages ON (rttickets.id = rtmessages.ticketid)
		    LEFT JOIN admins ON (owner = admins.id) 
		    LEFT JOIN users ON (rttickets.userid = users.id)
		    WHERE queueid = ? '.$statefilter 
		    .' GROUP BY rttickets.id, requestor, rttickets.createtime, rttickets.subject, state, owner, admins.name, rttickets.userid, users.lastname, users.name '
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
						    COUNT(CASE state WHEN 1 THEN 1 END) AS opened,
						    COUNT(CASE state WHEN 2 THEN 1 END) AS resolved,
						    COUNT(CASE state WHEN 3 THEN 1 END) AS dead
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

	function TicketDelete($ticketid)
	{
		$ts = time();
		$this->DB->Execute('DELETE FROM rtmessages WHERE ticketid=?', array($ticketid));
		$this->DB->Execute('DELETE FROM rttickets WHERE id=?', array($ticketid));
		$this->SetTS('rtqueues');	
		$this->SetTS('rttickets');
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
		if(!$admin) $admin = $this->AUTH->id;
		$this->SetTS('rttickets');
		return $this->DB->Execute('UPDATE rttickets SET owner=? WHERE id = ?', array($admin, $ticket));
	}

	function SetTicketState($ticket, $state)
	{
		($state==2 ? $resolvetime = time() : $resolvetime = 0);
			
		if($this->DB->GetOne('SELECT owner FROM rttickets WHERE id=?', array($ticket))) 
			$this->DB->Execute('UPDATE rttickets SET state=?, resolvetime=? WHERE id=?', array($state, $resolvetime, $ticket));
		else
			$this->DB->Execute('UPDATE rttickets SET state=?, owner=?, resolvetime=? WHERE id=?', array($state, $this->AUTH->id, $resolvetime, $ticket));
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

	function FirstMessage($ticketid)
	{
		return $this->DB->GetOne('SELECT min(id) FROM rtmessages WHERE ticketid = ?', array($ticketid));
	}
	
	function MessageDel($id)
	{
		$this->SetTS('rtmessages');
		return $this->DB->Execute('DELETE FROM rtmessages WHERE id = ?', array($id));
	}

	/*
	 * Konfiguracja LMS-UI
	 */

	function GetConfigOptionId($var, $section) 
	{
		return $this->DB->GetOne('SELECT id FROM uiconfig WHERE section = ? AND var = ?', array($section, $var));
	}
	
	function CheckOption($var, $value)
	{
		switch($var)
		{
			case 'accountlist_pagelimit':
			case 'ticketlist_pagelimit':
			case 'balancelist_pagelimit':
			case 'invoicelist_pagelimit':
			case 'aliaslist_pagelimit':
			case 'domainlist_pagelimit':
			case 'timeout':
			case 'timetable_days_forward':
			case 'nodepassword_length':
				if($value<=0)
					return trans('Value of option "$0" must be a number grater than zero!' ,$var);
			break;
		        case 'reload_type':
				if($value != 'sql' && $value != 'exec')
					return trans('Incorrect reload type. Valid types are: sql, exec!');
			break;
			case 'force_ssl':
			case 'allow_mac_sharing':
			case 'smarty_debug':
			case 'use_current_payday':
			case 'helpdesk_backend_mode':
			case 'to_words_short_format':
			case 'disable_devel_warning':
			case 'monthly_numbering':
				if(!isboolean($value))
					return trans('Incorrect value! Valid values are: 1|t|true|y|yes|on and 0|n|no|off|false'); 
			break;
			case 'debug_email':
				if(!check_email($value))
					return trans('Incorrect email address!');
			break;
		}
		return NULL;
	}

	/*
	 *   Hosting: Accounts, Aliases, Domains
	 */

	function GetAccountIdByLogin($login) 
	{
		return $this->DB->GetOne('SELECT id FROM passwd WHERE login = ?', array($login));
	}

	/*
	 *  Miscalenous
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

	function GetContractList()
	{
		$contractlist = explode(',', $this->CONFIG['phpui']['contract_template']);
		
		foreach($contractlist as $idx => $row)
		{
			list($file, $name) = explode(':', $row);
			$clist[$idx]['file'] = trim($file);
			$clist[$idx]['name'] = trim($name);
		}
		
		return $clist;
	}

	function GetUniqueInstallationID()
	{
		if(!($uiid = $this->DB->GetOne('SELECT keyvalue FROM dbinfo WHERE keytype=?', array('unique_installation_id'))))
		{
			list($usec, $sec) = split(' ', microtime());
			$uiid = md5(uniqid(rand(), true)).sprintf('%09x', $sec).sprintf('%07x', ($usec * 10000000));
			$this->DB->Execute('INSERT INTO dbinfo (keytype, keyvalue) VALUES (?, ?)', array('unique_installation_id', $uiid));
		}
		return $uiid;
	}

	function CheckUpdates()
	{
		$uiid = $this->GetUniqueInstallationID();
		$time = $this->DB->GetOne('SELECT ?NOW?');
		$content = FALSE;
		if(!($lastcheck = $this->DB->GetOne('SELECT keyvalue FROM dbinfo WHERE keytype=?', array('last_check_for_updates_timestamp'))))
			$lastcheck = 0;
		if($lastcheck + $this->CONFIG['phpui']['check_for_updates_period'] < $time)
		{
			list($v, $codename) = split(' ', $this->_version);
			ini_set('default_socket_timeout', 5);
			if($updatefile = fopen('http://lms.rulez.pl/update.php?uiid='.$uiid.'&v='.$v, 'r'))
			{
				while(! feof($updatefile))
					$content .= fgets($updatefile, 4096);
				fclose($updatefile);
				if($lastcheck == 0)
					$this->DB->Execute('INSERT INTO dbinfo (keyvalue, keytype) VALUES (?NOW?, ?)', array('last_check_for_updates_timestamp'));
				else
					$this->DB->Execute('UPDATE dbinfo SET keyvalue=?NOW? WHERE keytype=?', array('last_check_for_updates_timestamp'));
			}
			ini_restore('default_socket_timeout');
		}
		return $content;
	}

	function SendMail($recipients, $headers, $body)
	{
		include('Mail.php');

		$params['host'] = $this->CONFIG['phpui']['smtp_host'];
		$params['port'] = $this->CONFIG['phpui']['smtp_port'];
		if ($this->CONFIG['phpui']['username'])
		{
			$params['auth'] = true;
			$params['username'] = $this->CONFIG['phpui']['smtp_username'];
			$params['password'] = $this->CONFIG['phpui']['smtp_password'];
		}
		else
			$params['auth'] = false;

		$mail_object =& Mail::factory('smtp', $params);
		$mail_object->send($recipients, $headers, $body);

		return TRUE;
	}
}
?>
