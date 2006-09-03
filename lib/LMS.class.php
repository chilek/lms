<?php

/*
 * LMS version 1.9-cvs
 *
 *  (C) Copyright 2001-2006 LMS Developers
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
// to fetch data like customer names, searching for mac's by ID, etc..

class LMS
{
	var $DB;			// database object
	var $AUTH;			// object from Session.class.php (session management)
	var $CONFIG;			// table including lms.ini options
	var $_version = '1.9-cvs';	// class version
	var $_revision = '$Revision$';

	function LMS(&$DB, &$AUTH, &$CONFIG) // class variables setting
	{
		$this->DB = &$DB;
		$this->AUTH = &$AUTH;
		$this->CONFIG = &$CONFIG;

		$this->_revision = eregi_replace('^.Revision: ([0-9.]+).*','\1', $this->_revision);
		$this->_version = $this->_version.' ('.$this->_revision.')';
	}

	function _postinit()
	{
		return TRUE;
	}

	/*
	 *  Logging
	 *	0 - disabled
	 *	1 - system log in and modules calls without access privileges
	 *	2 - as above, addition and deletion
	 *	3 - as above, and changes
	 *	4 - as above, and all modules calls (paranoid)
	 */
/*
	function Log($loglevel=0, $message=NULL)
	{
		if( $loglevel <= $this->CONFIG['phpui']['loglevel'] && $message )
		{
			$this->DB->Execute('INSERT INTO syslog (time, userid, level, message)
					    VALUES (?NOW?, ?, ?, ?)', array($this->AUTH->id, $loglevel, $message));
			//I think, we can ommit SetTS('syslog')
		}
	}
*/
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
				// skip sessions table for security 
				if($tablename == 'sessions')
					continue;
					
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
	 *  Users (Useristrators)
	 */

	function SetUserPassword($id,$passwd) // ustawia has�o usera o id r�wnym $id na $passwd
	{
		$this->SetTS('users');
		$this->DB->Execute('UPDATE users SET passwd=? WHERE id=?', array(crypt($passwd),$id));
	}

	function GetUserName($id) // zwraca imi� usera
	{
		return $this->DB->GetOne('SELECT name FROM users WHERE id=?', array($id));
	}

	function GetUserNames() // zwraca skr�con� list� user�w
	{
		return $this->DB->GetAll('SELECT id, name FROM users WHERE deleted=0 ORDER BY login ASC');
	}

	function GetUserList() // zwraca list� useristrator�w
	{
		if($userslist = $this->DB->GetAll('SELECT id, login, name, lastlogindate, lastloginip FROM users WHERE deleted=0 ORDER BY login ASC'))
		{
			foreach($userslist as $idx => $row)
			{
				if($row['id']==$this->AUTH->id)
				{
					$row['lastlogindate'] = $this->AUTH->last;
					$userslist[$idx]['lastlogindate'] = $this->AUTH->last;
					$row['lastloginip'] = $this->AUTH->lastip;
					$userslist[$idx]['lastloginip'] = $this->AUTH->lastip;
				}

				if($row['lastlogindate'])
					$userslist[$idx]['lastlogin'] = date('Y/m/d H:i',$row['lastlogindate']);
				else
					$userslist[$idx]['lastlogin'] = '-';

				if(check_ip($row['lastloginip']))
					$userslist[$idx]['lastloginhost'] = gethostbyaddr($row['lastloginip']);
				else
				{
					$userslist[$idx]['lastloginhost'] = '-';
					$userslist[$idx]['lastloginip'] = '-';
				}
			}
		}

		$userslist['total'] = sizeof($userslist);
		return $userslist;
	}

	function GetUserIDByLogin($login) // zwraca id usera na podstawie loginu
	{
		return $this->DB->GetOne('SELECT id FROM users WHERE login=?', array($login));
	}

	function UserAdd($useradd) // dodaje usera. wymaga tablicy zawieraj�cej dane usera
	{
		$this->SetTS('users');
		if($this->DB->Execute('INSERT INTO users (login, name, email, passwd, rights, hosts) VALUES (?, ?, ?, ?, ?, ?)', array($useradd['login'], $useradd['name'], $useradd['email'], crypt($useradd['password']),$useradd['rights'], $useradd['hosts'])))
			return $this->DB->GetOne('SELECT id FROM users WHERE login=?', array($useradd['login']));
		else
			return FALSE;
	}

	function UserDelete($id) // usuwa usera o podanym id
	{
		$this->SetTS('users');
		return $this->DB->Execute('UPDATE users SET deleted=1 WHERE id=?', array($id));
	}

	function UserExists($id) // zwraca TRUE/FALSE zale�nie od tego czy user istnieje czy nie
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

	function GetUserInfo($id) // zwraca pe�ne info o podanym userie
	{
		if($userinfo = $this->DB->GetRow('SELECT id, login, name, email, hosts, lastlogindate, lastloginip, failedlogindate, failedloginip, deleted FROM users WHERE id=?', array($id)))
		{
			if($userinfo['id']==$this->AUTH->id)
			{
				$userinfo['lastlogindate'] = $this->AUTH->last;
				$userinfo['lastloginip'] = $this->AUTH->lastip;
			}

			if($userinfo['lastlogindate'])
				$userinfo['lastlogin'] = date('Y/m/d H:i',$userinfo['lastlogindate']);
			else
				$userinfo['lastlogin'] = '-';

			if($userinfo['failedlogindate'])
				$userinfo['faillogin'] = date('Y/m/d H:i',$userinfo['failedlogindate']);
			else
				$userinfo['faillogin'] = '-';

			if(check_ip($userinfo['lastloginip']))
				$userinfo['lastloginhost'] = gethostbyaddr($userinfo['lastloginip']);
			else
			{
				$userinfo['lastloginhost'] = '-';
				$userinfo['lastloginip'] = '-';
			}

			if(check_ip($userinfo['failedloginip']))
				$userinfo['failedloginhost'] = gethostbyaddr($userinfo['failedloginip']);
			else
			{
				$userinfo['failedloginhost'] = '-';
				$userinfo['failedloginip'] = '-';
			}
		}
		return $userinfo;
	}

	function UserUpdate($userinfo) // uaktualnia rekord usera.
	{
		$this->SetTS('users');
		return $this->DB->Execute('UPDATE users SET login=?, name=?, email=?, rights=?, hosts=? WHERE id=?', array($userinfo['login'],$userinfo['name'],$userinfo['email'],$userinfo['rights'],$userinfo['hosts'],$userinfo['id']));
	}

	function GetUserRights($id)
	{
		$mask = $this->DB->GetOne('SELECT rights FROM users WHERE id=?', array($id));
		if($mask == '')
			$mask = '1';
		$len = strlen($mask);
		$bin = '';
		for($cnt=$len; $cnt > 0; $cnt --)
			$bin = sprintf('%04b',hexdec($mask[$cnt-1])).$bin;
		for($cnt=strlen($bin)-1; $cnt >= 0; $cnt --)
			if($bin[$cnt] == '1')
				$result[] = strlen($bin) - $cnt -1;
		return $result;
	}

	/*
	 *  Customers functions
	 */

	function GetCustomerName($id)
	{
		return $this->DB->GetOne('SELECT '.$this->DB->Concat('UPPER(lastname)',"' '",'name').' FROM customers WHERE id=?', array($id));
	}

	function GetCustomerEmail($id)
	{
		return $this->DB->GetOne('SELECT email FROM customers WHERE id=?', array($id));
	}

	function GetCustomerServiceAddress($id)
	{
		return $this->DB->GetOne('SELECT serviceaddr FROM customers WHERE id=?', array($id));
	}

	function CustomerExists($id)
	{
		switch($this->DB->GetOne('SELECT deleted FROM customers WHERE id=?', array($id)))
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

	function RecoverCustomer($id)
	{
		$this->SetTS('customers');
		return $this->DB->Execute('UPDATE customers SET deleted=0 WHERE id=?', array($id));
	}

	// confusing function name, gets number of tariff assignments, not number of customers with this tariff
	function GetCustomersWithTariff($id)
	{
		return $this->DB->GetOne('SELECT COUNT(customerid) FROM assignments WHERE tariffid = ?', array($id));
	}

	function CustomerAdd($customeradd)
	{
		if($this->DB->Execute('INSERT INTO customers (name, lastname, phone1, phone2, phone3, im, address, zip, city, 
				    email, ten, ssn, status, creationdate, creatorid, info, serviceaddr, message, pin, regon, rbe, icn) 
				    VALUES (?, UPPER(?), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?NOW?, ?, ?, ?, ?, ?, ?, ?, ?)', 
				    array(ucwords($customeradd['name']),  
					    $customeradd['lastname'], 
					    $customeradd['phone1'], 
					    $customeradd['phone2'], 
					    $customeradd['phone3'], 
					    $customeradd['im'], 
					    $customeradd['address'], 
					    $customeradd['zip'], 
					    $customeradd['city'], 
					    $customeradd['email'], 
					    $customeradd['ten'], 
					    $customeradd['ssn'], 
					    $customeradd['status'], 
					    $this->AUTH->id, 
					    $customeradd['info'], 
					    $customeradd['serviceaddr'], 
					    $customeradd['message'], 
					    $customeradd['pin'],
					    $customeradd['regon'],
					    $customeradd['rbe'],
					    $customeradd['icn']
					    )))
		{
			$this->SetTS('customers');
			return $this->DB->GetLastInsertID('customers');
		} else
			return FALSE;
	}

	function DeleteCustomer($id)
	{
		$this->SetTS('customers');
		$this->SetTS('nodes');
		$this->SetTS('customerassignments');
		$this->SetTS('assignments');
		$this->DB->Execute('DELETE FROM nodes WHERE ownerid=?', array($id));
		$this->DB->Execute('DELETE FROM customerassignments WHERE customerid=?', array($id));
		$this->DB->Execute('UPDATE customers SET deleted=1, moddate=?NOW?, modid=? WHERE id=?', array($this->AUTH->id, $id));
		$this->DB->Execute('DELETE FROM assignments WHERE customerid=?', array($id));
		$this->DB->Execute('UPDATE passwd SET ownerid=0 WHERE ownerid=?', array($id));
		$this->DB->Execute('UPDATE domains SET ownerid=0 WHERE ownerid=?', array($id));
		// Remove Userpanel rights
		if($this->CONFIG['directories']['userpanel_dir'])
			$this->DB->Execute('DELETE FROM up_rights_assignments WHERE customerid=?', array($id));
	}

	function CustomerUpdate($customerdata)
	{
		$this->SetTS('customers');

		return $this->DB->Execute('UPDATE customers SET status=?, phone1=?, phone2=?, phone3=?, address=?, 
					    zip=?, city=?, email=?, im=?, ten=?, ssn=?, moddate=?NOW?, modid=?, 
					    info=?, serviceaddr=?, lastname=UPPER(?), name=?, deleted=0, message=?, 
					    pin=?, regon=?, icn=?, rbe=? WHERE id=?', 
			array( $customerdata['status'], 
				$customerdata['phone1'], 
				$customerdata['phone2'], 
				$customerdata['phone3'], 
				$customerdata['address'], 
				$customerdata['zip'], 
				$customerdata['city'], 
				$customerdata['email'], 
				$customerdata['im'], 
				$customerdata['ten'], 
				$customerdata['ssn'], 
				isset($this->AUTH->id) ? $this->AUTH->id : 0,
				$customerdata['info'], 
				$customerdata['serviceaddr'], 
				$customerdata['lastname'], 
				ucwords($customerdata['name']), 
				$customerdata['message'],
				$customerdata['pin'],
				$customerdata['regon'], 
				$customerdata['icn'], 
				$customerdata['rbe'], 
				$customerdata['id']
				));
	}

	function GetCustomerNodesNo($id)
	{
		return $this->DB->GetOne('SELECT COUNT(*) FROM nodes WHERE ownerid=?', array($id));
	}

	function GetCustomerIDByIP($ipaddr)
	{
		return $this->DB->GetOne('SELECT ownerid FROM nodes WHERE ipaddr=inet_aton(?) OR ipaddr_pub=inet_aton(?)', array($ipaddr, $ipaddr));
	}

	function GetCashByID($id)
	{
		return $this->DB->GetRow('SELECT time, userid, value, taxid, customerid, comment FROM cash WHERE id=?', array($id));
	}

	function GetCustomerStatus($id)
	{
		return $this->DB->GetOne('SELECT status FROM customers WHERE id=?', array($id));
	}

	function GetCustomer($id)
	{
		if($result = $this->DB->GetRow('SELECT id, '.$this->DB->Concat('UPPER(lastname)',"' '",'name').' AS customername, 
					    lastname, name, status, email, im, phone1, phone2, phone3, address, zip, ten, ssn, 
					    city, info, serviceaddr, creationdate, moddate, creatorid, modid, deleted, message, 
					    pin, regon, icn, rbe 
					    FROM customers WHERE id = ?', array($id)))
		{
			$result['createdby'] = $this->GetUserName($result['creatorid']);
			$result['modifiedby'] = $this->GetUserName($result['modid']);
			$result['creationdateh'] = date('Y/m/d, H:i',$result['creationdate']);
			$result['moddateh'] = date('Y/m/d, H:i',$result['moddate']);
			$result['balance'] = $this->GetCustomerBalance($result['id']);
			$result['tariffsvalue'] = $this->GetCustomerTariffsValue($result['id']);
			$result['bankaccount'] = bankaccount($result['id']);
			return $result;
		}else
			return FALSE;
	}

	function GetCustomerNames()
	{
		return $this->DB->GetAll('SELECT id, '.$this->DB->Concat('UPPER(lastname)',"' '",'name').' AS customername FROM customers WHERE status > 1 AND deleted = 0 ORDER BY customername');
	}

	function GetAllCustomerNames()
	{
		return $this->DB->GetAll('SELECT id, '.$this->DB->Concat('UPPER(lastname)',"' '",'name').' AS customername FROM customers WHERE deleted = 0 ORDER BY customername');
	}

	function GetCustomerNodesAC($id)
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

	function GetCustomerList($order='customername,asc', $state=NULL, $network=NULL, $customergroup=NULL, $search=NULL, $time=NULL, $sqlskey='AND')
	{
		list($order,$direction) = sscanf($order, '%[^,],%s');

		($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

		switch($order)
		{
			case 'id':
				$sqlord = 'ORDER BY customers.id';
			break;
			case 'address':
				$sqlord = 'ORDER BY address';
			break;
			case 'balance':
				$sqlord = 'ORDER BY balance';
			break;
			default:
				$sqlord = 'ORDER BY customername';
			break;
		}

		if($state == 4) {
			$deleted = 1;
			// don't use customergroup and network filtering
			// when customer is deleted because we drop group assignments and nodes
			// in DeleteCustomer()
			$network=NULL;
			$customergroup=NULL;
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

		$over = 0; $below = 0;

		if(sizeof($search))
			foreach($search as $key => $value)
			{
				$value = str_replace(' ','%',trim($value));
				if($value!='')
				{
					switch($key)
					{
						case 'phone':
							$searchargs[] = "(phone1 ?LIKE? '%$value%' OR phone2 ?LIKE? '%$value%' OR phone3 ?LIKE? '%$value%')";
						break;
						case 'zip':
						case 'city':
						case 'address':
							// UPPER here is a workaround for postgresql ILIKE bug
							$searchargs[] = "(UPPER($key) ?LIKE? UPPER('%$value%') OR UPPER(serviceaddr) ?LIKE? UPPER('%$value%'))";
						break;
						case 'customername':
							// UPPER here is a workaround for postgresql ILIKE bug
							$searchargs[] = $this->DB->Concat('UPPER(customers.lastname)',"' '",'UPPER(customers.name)')." ?LIKE? UPPER('%$value%')";
						break;
						case 'createdfrom':
							if($search['createdto'])
							{
								$searchargs['createdfrom'] = "(creationdate >= $value AND creationdate <= ".$search['createdto'].')';
								unset($search['createdto']);
							}
							else
								$searchargs[] = "creationdate >= $value";
						break;
						case 'createdto':
							if(!isset($searchargs['createdfrom']))
								$searchargs[] = "creationdate <= $value";
						break;
						case 'deletedfrom':
							if($search['deletedto'])
							{
								$searchargs['deletedfrom'] = "(moddate >= $value AND moddate <= ".$search['deletedto'].')';
								unset($search['deletedto']);
							}
							else
								$searchargs[] = "moddate >= $value";
							$deleted = 1;
						break;
						case 'deletedto':
							if(!isset($searchargs['deletedfrom']))
								$searchargs[] = "moddate <= $value";
							$deleted = 1;
						break;
						default:
							$searchargs[] = "$key ?LIKE? '%$value%'";
					}
				}
			}

		if(isset($searchargs))
			$sqlsarg = implode(' '.$sqlskey.' ',$searchargs);

		$suspension_percentage = $this->CONFIG['finances']['suspension_percentage'];

		if($customerlist = $this->DB->GetAll(
				'SELECT customers.id AS id, '.$this->DB->Concat('UPPER(lastname)',"' '",'customers.name').' AS customername, status, address, zip, city, email, phone1, ten, ssn, customers.info AS info, message, '
				.($network ? 'COALESCE(SUM(value), 0.00)/(CASE COUNT(DISTINCT nodes.id) WHEN 0 THEN 1 ELSE COUNT(DISTINCT nodes.id) END) AS balance ' : 'COALESCE(SUM(value), 0.00) AS balance ')
				.'FROM customers LEFT JOIN cash ON (customers.id=cash.customerid) '
				.($network ? 'LEFT JOIN nodes ON (customers.id=ownerid) ' : '')
				.($customergroup ? 'LEFT JOIN customerassignments ON (customers.id=customerassignments.customerid) ' : '')
				.'WHERE deleted = '.$deleted
				.($state !=0 ? ' AND status = '.$state :'')
				.($network ? ' AND ((ipaddr > '.$net['address'].' AND ipaddr < '.$net['broadcast'].') OR (ipaddr_pub > '.$net['address'].' AND ipaddr_pub < '.$net['broadcast'].'))' : '')
				.($customergroup ? ' AND customergroupid='.$customergroup : '')
				.($time ? ' AND time < '.$time : '')
				.(isset($sqlsarg) ? ' AND ('.$sqlsarg.')' :'')
				.' GROUP BY customers.id, lastname, customers.name, status, address, zip, city, email, phone1, ten, ssn, customers.info, message '
		// ten fragment nie chcial dzialac na mysqlu
		//		.($indebted ? ' HAVING SUM(value) < 0 ' : '')
				.($sqlord !='' ? $sqlord.' '.$direction:'')
				))
		{
			$day = $this->DB->GetAllByKey('SELECT customers.id AS id, SUM(CASE suspended WHEN 0 THEN (CASE discount WHEN 0 THEN tariffs.value ELSE ((100 - discount) * tariffs.value) / 100 END) ELSE (CASE discount WHEN 0 THEN tariffs.value * '.$suspension_percentage.' / 100 ELSE tariffs.value * discount * '.$suspension_percentage.' / 10000 END) END)*30 AS value FROM assignments, tariffs, customers WHERE customerid = customers.id AND tariffid = tariffs.id AND deleted = 0 AND period = '.DAILY.' AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY customers.id', 'id');
			$week = $this->DB->GetAllByKey('SELECT customers.id AS id, SUM(CASE suspended WHEN 0 THEN (CASE discount WHEN 0 THEN tariffs.value ELSE ((100 - discount) * tariffs.value) / 100 END) ELSE (CASE discount WHEN 0 THEN tariffs.value * '.$suspension_percentage.' / 100 ELSE tariffs.value * discount * '.$suspension_percentage.' / 10000 END) END)*4 AS value FROM assignments, tariffs, customers WHERE customerid = customers.id AND tariffid = tariffs.id AND deleted = 0 AND period = '.WEEKLY.' AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY customers.id', 'id');
			$month = $this->DB->GetAllByKey('SELECT customers.id AS id, SUM(CASE suspended WHEN 0 THEN (CASE discount WHEN 0 THEN tariffs.value ELSE ((100 - discount) * tariffs.value) / 100 END) ELSE (CASE discount WHEN 0 THEN tariffs.value * '.$suspension_percentage.' / 100 ELSE tariffs.value * discount * '.$suspension_percentage.' / 10000 END) END) AS value FROM assignments, tariffs, customers WHERE customerid = customers.id AND tariffid = tariffs.id AND deleted = 0 AND period = '.MONTHLY.' AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY customers.id', 'id');
			$quarter = $this->DB->GetAllByKey('SELECT customers.id AS id, SUM(CASE suspended WHEN 0 THEN (CASE discount WHEN 0 THEN tariffs.value ELSE ((100 - discount) * tariffs.value) / 100 END) ELSE (CASE discount WHEN 0 THEN tariffs.value * '.$suspension_percentage.' / 100 ELSE tariffs.value * discount * '.$suspension_percentage.' / 10000 END) END)/3 AS value FROM assignments, tariffs, customers WHERE customerid = customers.id AND tariffid = tariffs.id AND deleted = 0 AND period = '.QUARTERLY.' AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY customers.id', 'id');
			$year = $this->DB->GetAllByKey('SELECT customers.id AS id, SUM(CASE suspended WHEN 0 THEN (CASE discount WHEN 0 THEN tariffs.value ELSE ((100 - discount) * tariffs.value) / 100 END) ELSE (CASE discount WHEN 0 THEN tariffs.value * '.$suspension_percentage.' / 100 ELSE tariffs.value * discount * '.$suspension_percentage.' / 10000 END) END)/12 AS value FROM assignments, tariffs, customers WHERE customerid = customers.id AND tariffid = tariffs.id AND deleted = 0 AND period = '.YEARLY.' AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY customers.id', 'id');

			$access = $this->DB->GetAllByKey('SELECT ownerid AS id, SUM(access) AS acsum, COUNT(access) AS account FROM nodes GROUP BY ownerid','id');
			$warning = $this->DB->GetAllByKey('SELECT ownerid AS id, SUM(warning) AS warnsum, COUNT(warning) AS warncount FROM nodes GROUP BY ownerid','id');
			$onlines = $this->DB->GetAllByKey('SELECT MAX(lastonline) AS online, ownerid AS id FROM nodes GROUP BY ownerid','id');

			$customerlist2 = NULL;

			foreach($customerlist as $idx => $row)
			{
				$customerlist[$idx]['tariffvalue'] = round($day[$row['id']]['value']+$week[$row['id']]['value']+$month[$row['id']]['value']+$quarter[$row['id']]['value']+$year[$row['id']]['value'], 2);
				$customerlist[$idx]['account'] = isset($access[$row['id']]['account']) ? $access[$row['id']]['account'] : 0;
				$customerlist[$idx]['warncount'] = isset($warning[$row['id']]['warncount']) ? $warning[$row['id']]['warncount'] : 0;

				if($customerlist[$idx]['account']) // if customer have some nodes
				{
					if($access[$row['id']]['account'] == $access[$row['id']]['acsum'])
						$customerlist[$idx]['nodeac'] = 1; // connected all nodes
					elseif($access[$row['id']]['acsum'] == 0)
						$customerlist[$idx]['nodeac'] = 0; // disconected all nodes
					else
						$customerlist[$idx]['nodeac'] = 2; // some nodes disconneted
				}
				
				if($customerlist[$idx]['warncount'])
				{
					if($warning[$row['id']]['warncount'] == $warning[$row['id']]['warnsum'])
						$customerlist[$idx]['nodewarn'] = 1;
					elseif($warning[$row['id']]['warnsum'] == 0)
						$customerlist[$idx]['nodewarn'] = 0;
					else
						$customerlist[$idx]['nodewarn'] = 2;
				}
				
				if(isset($onlines[$row['id']]['online']) && $onlines[$row['id']]['online'] > time()-$this->CONFIG['phpui']['lastonline_limit'])
					$customerlist[$idx]['online'] = 1;
				else
					$customerlist[$idx]['online'] = 0;

				if($disabled)
				{
					if($customerlist[$idx]['nodeac'] != 1)
						$customerlist2[] = $customerlist[$idx];
					else
						continue; // skip summary
				}
				elseif($online)
				{
					if($customerlist[$idx]['online'])
						$customerlist2[] = $customerlist[$idx];
					else
						continue; // skip summary
				}
				elseif($indebted)
				{
					if($customerlist[$idx]['balance'] < 0)
						$customerlist2[] = $customerlist[$idx];
					else
						continue; // skip summary
				}
				
				// summary
				if($customerlist[$idx]['balance'] > 0)
					$over += $customerlist[$idx]['balance'];
				elseif($customerlist[$idx]['balance'] < 0)
					$below += $customerlist[$idx]['balance'];
			}
			
			if($customerlist2)
				$customerlist = $customerlist2;
		}

		switch($order)
		{
			case 'tariff':
				foreach($customerlist as $idx => $row)
				{
					$tarifftable['idx'][] = $idx;
					$tarifftable['tariffvalue'][] = $row['tariffvalue'];
				}
				if(is_array($tarifftable))
				{
					array_multisort($tarifftable['tariffvalue'],($direction == "desc" ? SORT_DESC : SORT_ASC),$tarifftable['idx']);
					foreach($tarifftable['idx'] as $idx)
						$ncustomerelist[] = $customerlist[$idx];
				}
				$customerlist = $ncustomerelist;
			break;
		}
		$customerlist['total'] = sizeof($customerlist);
		$customerlist['state'] = $state;
		$customerlist['network'] = $network;
		$customerlist['customergroup'] = $customergroup;
		$customerlist['order'] = $order;
		$customerlist['direction'] = $direction;
		$customerlist['below']= $below;
		$customerlist['over']= $over;

		return $customerlist;
	}

	function GetCustomerNodes($id)
	{
		if($result = $this->DB->GetAll('SELECT id, name, mac, ipaddr, inet_ntoa(ipaddr) AS ip, ipaddr_pub, inet_ntoa(ipaddr_pub) AS ip_pub, passwd, access, warning, info, ownerid, location FROM nodes WHERE ownerid=? ORDER BY name ASC', array($id)))
		{
			// assign network(s) to node record
			if($networks = $this->GetNetworks())
			{
				foreach($result as $idx => $node)
					foreach($networks as $net)
						if(isipin($node['ip'], $net['address'], $net['mask']))
						{
							$result[$idx]['network'] = $net;
							break;
						}
				foreach($result as $idx => $node)
					if($node['ipaddr_pub'])
						foreach($networks as $net)
							if(isipin($node['ip_pub'], $net['address'], $net['mask']))
							{
								$result[$idx]['network_pub'] = $net;
								break;
							}
			}
			
			$result['total'] = sizeof($result);
		}
		return $result;
	}

	function GetCustomerBalance($id)
	{
		return $this->DB->GetOne('SELECT SUM(value) FROM cash WHERE customerid=?', array($id));
	}

	function GetCustomerBalanceList($id)
	{
		$saldolist = array();
		
		if($tslist = $this->DB->GetAll('SELECT cash.id AS id, time, cash.type AS type, 
					cash.value AS value, taxes.label AS tax, cash.customerid AS customerid, 
					comment, docid, users.name AS username,
					documents.type AS doctype, documents.closed AS closed
					FROM cash
					LEFT JOIN users ON users.id = cash.userid
					LEFT JOIN documents ON documents.id = docid
					LEFT JOIN taxes ON cash.taxid = taxes.id
					WHERE cash.customerid=? ORDER BY time', array($id)))
		{
			$saldolist['balance'] = 0;
			$saldolist['total'] = 0;
			$i = 0;

			foreach($tslist as $row)
			{
				// old format wrapper
				foreach($row as $column => $value)
					$saldolist[$column][$i] = $value;
				
				$saldolist['after'][$i] = round($saldolist['balance'] + $row['value'], 2);
				$saldolist['balance'] += $row['value'];
				$saldolist['date'][$i] = date('Y/m/d H:i', $row['time']);
				
				$i++;
			}
			
			$saldolist['total'] = sizeof($tslist);
		}

		$saldolist['customerid'] = $id;
		return $saldolist;
	}

	function CustomerStats()
	{
		$result['total'] = $this->DB->GetOne('SELECT COUNT(id) FROM customers WHERE deleted=0');
		$result['connected'] = $this->DB->GetOne('SELECT COUNT(id) FROM customers WHERE status=3 AND deleted=0');
		$result['awaiting'] = $this->DB->GetOne('SELECT COUNT(id) FROM customers WHERE status=2 AND deleted=0');
		$result['interested'] = $this->DB->GetOne('SELECT COUNT(id) FROM customers WHERE status=1 AND deleted=0');
		$result['debt'] = 0;
		$result['debtvalue'] = 0;
		if($balances = $this->DB->GetCol('SELECT SUM(value) FROM cash LEFT JOIN customers ON customerid = customers.id WHERE deleted = 0 GROUP BY customerid HAVING SUM(value) < 0'))
		{
			foreach($balances as $idx)
				if($idx < 0)
				{
					$result['debtvalue'] -= $idx;
					$result['debt']++;
				}
		}
		return $result;
	}

	/*
	 * Customer groups
	*/

	function CustomergroupWithCustomerGet($id)
	{
		return $this->DB->GetOne('SELECT COUNT(customerid) FROM customerassignments, customers WHERE customers.id = customerid AND customergroupid = ?', array($id));
	}

	function CustomergroupAdd($customergroupdata)
	{
		$this->SetTS('customergroups');
		if($this->DB->Execute('INSERT INTO customergroups (name, description) VALUES (?, ?)', array($customergroupdata['name'], $customergroupdata['description'])))
			return $this->DB->GetOne('SELECT id FROM customergroups WHERE name=?', array($customergroupdata['name']));
		else
			return FALSE;
	}

	function CustomergroupUpdate($customergroupdata)
	{
		$this->SetTS('customergroups');
		return $this->DB->Execute('UPDATE customergroups SET name=?, description=? WHERE id=?', array($customergroupdata['name'], $customergroupdata['description'], $customergroupdata['id']));
	}

	function CustomergroupDelete($id)
	{
		 if (!$this->CustomergroupWithCustomerGet($id))
		 {
			$this->SetTS('customergroups');
			return $this->DB->Execute('DELETE FROM customergroups WHERE id=?', array($id));
		 } else
			return FALSE;
	}

	function CustomergroupExists($id)
	{
		return ($this->DB->GetOne('SELECT id FROM customergroups WHERE id=?', array($id)) ? TRUE : FALSE);
	}

	function CustomergroupMove($from, $to)
	{
		if ($ids = $this->DB->GetCol('SELECT customerassignments.id AS id FROM customerassignments, customers WHERE customerid = customers.id AND customergroupid = ?', array($from)))
		{
			$this->SetTS('customerassignments');
			foreach($ids as $id)
				$this->DB->Execute('UPDATE customerassignments SET customergroupid=? WHERE id=? AND customergroupid=?', array($to, $id, $from));
		}
	}

	function CustomergroupGetId($name)
	{
		return $this->DB->GetOne('SELECT id FROM customergroups WHERE name=?', array($name));
	}

	function CustomergroupGetName($id)
	{
		return $this->DB->GetOne('SELECT name FROM customergroups WHERE id=?', array($id));
	}

	function CustomergroupGetAll()
	{
		return $this->DB->GetAll('SELECT id, name, description FROM customergroups ORDER BY name ASC');
	}

	function CustomergroupGet($id)
	{
		$result = $this->DB->GetRow('SELECT id, name, description FROM customergroups WHERE id=?', array($id));
		$result['customers'] = $this->DB->GetAll('SELECT customers.id AS id, COUNT(customers.id) AS cnt, '.$this->DB->Concat('UPPER(lastname)',"' '",'name').' AS customername FROM customerassignments, customers WHERE customers.id = customerid AND customergroupid = ? GROUP BY customers.id, customername ORDER BY customername', array($id));
		$result['customerscount'] = sizeof($result['customers']);
		$result['count'] = $this->CustomergroupWithCustomerGet($id);
		return $result;
	}

	function CustomergroupGetList()
	{
		if($customergrouplist = $this->DB->GetAll('SELECT id, name, description FROM customergroups ORDER BY name ASC'))
		{
			$totalcustomers = 0;
			$totalcount = 0;

			foreach($customergrouplist as $idx => $row)
			{
				$customergrouplist[$idx]['customers'] = $this->CustomergroupWithCustomerGet($row['id']);
				$customergrouplist[$idx]['customerscount'] = sizeof($this->DB->GetCol('SELECT customerid FROM customerassignments, customers WHERE customers.id = customerid AND customergroupid = ? GROUP BY customerid', array($row['id'])));
				$totalcustomers += $customergrouplist[$idx]['customers'];
				$totalcount += $customergrouplist[$idx]['customerscount'];
			}

			$customergrouplist['total'] = sizeof($customergrouplist);
			$customergrouplist['totalcustomers'] = $totalcustomers;
			$customergrouplist['totalcount'] = $totalcount;
		}

		return $customergrouplist;
	}

	function CustomergroupGetForCustomer($id)
	{
		return $this->DB->GetAll('SELECT customergroups.id AS id, name, description FROM customergroups, customerassignments WHERE customergroups.id=customergroupid AND customerid=? ORDER BY name ASC', array($id));
	}

	function GetGroupNamesWithoutCustomer($customerid)
	{
		return $this->DB->GetAll('SELECT customergroups.id AS id, name, customerid
			FROM customergroups LEFT JOIN customerassignments ON (customergroups.id=customergroupid AND customerid = ?)
			GROUP BY customergroups.id, name, customerid HAVING customerid IS NULL ORDER BY name', array($customerid));
	}

	function CustomerassignmentGetForCustomer($id)
	{
		return $this->DB->GetAll('SELECT customerassignments.id AS id, customergroupid, customerid FROM customerassignments, customergroups WHERE customerid=? AND customergroups.id = customergroupid ORDER BY customergroupid ASC', array($id));
	}

	function CustomerassignmentDelete($customerassignmentdata)
	{
		$this->SetTS('customerassignments');
		return $this->DB->Execute('DELETE FROM customerassignments WHERE customergroupid=? AND customerid=?', array($customerassignmentdata['customergroupid'], $customerassignmentdata['customerid']));
	}

	function CustomerassignmentAdd($customerassignmentdata)
	{
		$this->SetTS('customerassignments');
		return $this->DB->Execute('INSERT INTO customerassignments (customergroupid, customerid) VALUES (?, ?)',
			array($customerassignmentdata['customergroupid'], $customerassignmentdata['customerid']));
	}

	function CustomerassignmentExist($groupid, $customerid)
	{
		return $this->DB->GetOne('SELECT 1 FROM customerassignments WHERE customergroupid=? AND customerid=?', array($groupid, $customerid));
	}

	function GetCustomerWithoutGroupNames($groupid)
	{
		return $this->DB->GetAll('SELECT customers.id AS id, '.$this->DB->Concat('UPPER(lastname)',"' '",'name').' AS customername, customerid
			FROM customers LEFT JOIN customerassignments ON (customers.id = customerid AND customerassignments.customergroupid = ?) WHERE deleted = 0
			GROUP BY customers.id, customerid, lastname, name
			HAVING customerid IS NULL ORDER BY customername', array($groupid));
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
		return $this->DB->Execute('UPDATE nodes SET name=UPPER(?), ipaddr_pub=inet_aton(?), ipaddr=inet_aton(?), mac=UPPER(?), passwd=?, netdev=?, moddate=?NOW?, modid=?, access=?, warning=?, ownerid=?, info=?, location=? WHERE id=?', 
			    array($nodedata['name'], 
				    $nodedata['ipaddr_pub'], 
				    $nodedata['ipaddr'], 
				    $nodedata['mac'], 
				    $nodedata['passwd'], 
				    $nodedata['netdev'], 
				    $this->AUTH->id, 
				    $nodedata['access'], 
				    $nodedata['warning'], 
				    $nodedata['ownerid'], 
				    $nodedata['info'], 
				    $nodedata['location'],
				    $nodedata['id']));
	}

	function DeleteNode($id)
	{
		$this->SetTS('nodes');
		$this->DB->Execute('DELETE FROM nodes WHERE id = ?', array($id));
		$this->DB->Execute('DELETE FROM nodeassignments WHERE nodeid = ?', array($id));
	}

	function GetNodeNameByMAC($mac)
	{
		return $this->DB->GetOne('SELECT name FROM nodes WHERE mac=?', array($mac));
	}

	function GetNodeIDByIP($ipaddr)
	{
		return $this->DB->GetOne('SELECT id FROM nodes WHERE ipaddr=inet_aton(?) OR ipaddr_pub=inet_aton(?)', array($ipaddr,$ipaddr));
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

	function GetNodePubIPByID($id)
	{
		return $this->DB->GetOne('SELECT inet_ntoa(ipaddr_pub) FROM nodes WHERE id=?', array($id));
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
		return $this->DB->GetOne('SELECT name FROM nodes WHERE ipaddr=inet_aton(?) OR ipaddr_pub=inet_aton(?)', array($ipaddr, $ipaddr));
	}

	function GetNode($id)
	{
		if($result = $this->DB->GetRow('SELECT id, name, ownerid, ipaddr, inet_ntoa(ipaddr) AS ip, ipaddr_pub, inet_ntoa(ipaddr_pub) AS ip_pub, mac, passwd, access, warning, creationdate, moddate, creatorid, modid, netdev, lastonline, info, location FROM nodes WHERE id=?', array($id)))
		{
			$result['createdby'] = $this->GetUserName($result['creatorid']);
			$result['modifiedby'] = $this->GetUserName($result['modid']);
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
			$result['owner'] = $this->GetCustomerName($result['ownerid']);
			$result['netid'] = $this->GetNetIDByIP($result['ip']);
			$result['netname'] = $this->GetNetworkName($result['netid']);
			$result['producer'] = get_producer($result['mac']);
			return $result;
		}else
			return FALSE;
	}

	function GetNodeList($order='name,asc', $search=NULL, $sqlskey='AND', $network=NULL, $status=NULL)
	{
		if($order=='')
			$order='name,asc';

		list($order,$direction) = sscanf($order, '%[^,],%s');

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
			case 'ip_pub':
				$sqlord = ' ORDER BY ipaddr_pub';
			break;
			case 'ownerid':
				$sqlord = ' ORDER BY ownerid';
			break;
			case 'owner':
				$sqlord = ' ORDER BY owner';
			break;
		}

		if(sizeof($search))
		foreach($search as $idx => $value)
		{
			if($value!='')
			{
				switch($idx)
				{
					case 'ipaddr' :
						$searchargs[] = "(inet_ntoa(ipaddr) ?LIKE? '%".trim($value)."%'"." OR "."inet_ntoa(ipaddr_pub) ?LIKE? '%".trim($value)."%')";
					break;
					case 'name' :
						$searchargs[] = "nodes.name ?LIKE? '%".$value."%'";
					break;
					case 'info' :
						// UPPER here is a postgresql ILIKE bug workaround
						$searchargs[] = "UPPER(nodes.info) ?LIKE? UPPER('%".$value."%')";
					break;
					default :
						$searchargs[] = $idx." ?LIKE? '%".$value."%'";
				}
			}
		}

		if(isset($searchargs))
			$searchargs = ' AND '.implode(' '.$sqlskey.' ',$searchargs);

		$totalon = 0; $totaloff = 0;

		if($network)
			$net = $this->GetNetworkParams($network);

		if($nodelist = $this->DB->GetAll('SELECT nodes.id AS id, ipaddr, inet_ntoa(ipaddr) AS ip, ipaddr_pub, inet_ntoa(ipaddr_pub) AS ip_pub, mac, nodes.name AS name, ownerid, access, warning, netdev, lastonline, nodes.info AS info, '
					.$this->DB->Concat('UPPER(lastname)',"' '",'customers.name').' AS owner
					FROM nodes LEFT JOIN customers ON ownerid = customers.id WHERE ownerid > 0'
					.($network ? ' AND ((ipaddr > '.$net['address'].' AND ipaddr < '.$net['broadcast'].') OR ( ipaddr_pub > '.$net['address'].' AND ipaddr_pub < '.$net['broadcast'].'))' : '')
					.($status==1 ? ' AND access = 1' : '') //connected
					.($status==2 ? ' AND access = 0' : '') //disconnected
					.($status==3 ? ' AND lastonline > ?NOW? - '.$this->CONFIG['phpui']['lastonline_limit'] : '') //online
					.(isset($searchargs) ? $searchargs : '')
					.($sqlord != '' ? $sqlord.' '.$direction : '')))
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

	function NodeSet($id)
	{
		$this->SetTS('nodes');
		if($this->DB->GetOne('SELECT access FROM nodes WHERE id=?', array($id)) == 1 )
			return $this->DB->Execute('UPDATE nodes SET access=0 WHERE id=?', array($id));
		else
		{
			if($this->DB->GetOne('SELECT status FROM nodes, customers 
					    WHERE ownerid = customers.id AND nodes.id = ?', array($id)) == 3)
			{
				return $this->DB->Execute('UPDATE nodes SET access=1 WHERE id=?', array($id));
			}
		}
	}

	function NodeSetU($id,$access=FALSE)
	{
		$this->SetTS('nodes');
		if($access)
		{
			if($this->DB->GetOne('SELECT status FROM customers WHERE id = ?', array($id)) == 3)
			{
				return $this->DB->Execute('UPDATE nodes SET access=1 WHERE ownerid=?', array($id));
			}
		}
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
		if($this->DB->Execute('INSERT INTO nodes (name, mac, ipaddr, ipaddr_pub, ownerid, passwd, creatorid, creationdate, access, warning, info, netdev, location) VALUES (?, ?, inet_aton(?),inet_aton(?), ?, ?, ?, ?NOW?, ?, ?, ?, ?, ?)',
				array(strtoupper($nodedata['name']),
				    strtoupper($nodedata['mac']),
				    $nodedata['ipaddr'],
				    $nodedata['ipaddr_pub'],
				    $nodedata['ownerid'],
				    $nodedata['passwd'],
				    $this->AUTH->id,
				    $nodedata['access'],
				    $nodedata['warning'],
				    $nodedata['info'],
				    $nodedata['netdev'],
				    $nodedata['location'])))
			return $this->DB->GetLastInsertID('nodes');
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
		return $this->DB->GetAll('SELECT nodes.id AS id, nodes.name AS name, linktype, ipaddr, inet_ntoa(ipaddr) AS ip,ipaddr_pub, inet_ntoa(ipaddr_pub) AS ip_pub, netdev, '.$this->DB->Concat('UPPER(lastname)',"' '",'customers.name').' AS owner, ownerid FROM nodes, customers WHERE ownerid = customers.id AND netdev=? AND ownerid > 0 ORDER BY nodes.name ASC', array($id));
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

	function GetCustomerTariffsValue($id)
	{
		return $this->DB->GetOne('SELECT sum(tariffs.value) FROM assignments, tariffs WHERE tariffid = tariffs.id AND customerid=? AND suspended = 0 AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0)', array($id));
	}

	function GetCustomerAssignments($id)
	{
		if($assignments = $this->DB->GetAll('SELECT assignments.id AS id, tariffid, assignments.customerid, period, at, suspended,  
						    uprate, upceil, downceil, downrate, invoice, settlement, datefrom, dateto, discount, liabilityid, 
						    (CASE WHEN tariffs.value IS NULL THEN liabilities.value ELSE tariffs.value END) AS value,
						    (CASE WHEN tariffs.name IS NULL THEN liabilities.name ELSE tariffs.name END) AS name
						    FROM assignments 
						    LEFT JOIN tariffs ON (tariffid=tariffs.id) 
						    LEFT JOIN liabilities ON (liabilityid=liabilities.id) 
						    WHERE assignments.customerid=? 
						    ORDER BY datefrom, value', array($id)))
		{
			foreach($assignments as $idx => $row)
			{
				switch($row['period'])
				{
					case DISPOSABLE:
						$row['payday'] = date('Y/m/d', $row['at']);
						$row['period'] = trans('disposable');
					break;
					case DAILY:
						$row['period'] = trans('daily');
						$row['payday'] = trans('daily');
					break;
					case WEEKLY:
						$row['at'] = strftime("%a",mktime(0,0,0,0,$row['at']+5,0));
						$row['payday'] = trans('weekly ($0)', $row['at']);
						$row['period'] = trans('weekly');
					break;
					case MONTHLY:
						$row['payday'] = trans('monthly ($0)', $row['at']);
						$row['period'] = trans('monthly');
					break;
					case QUARTERLY:
						$row['at'] = sprintf('%02d/%02d', $row['at']%100, $row['at']/100+1);
						$row['payday'] = trans('quarterly ($0)', $row['at']);
						$row['period'] = trans('quarterly');
					break;
					case YEARLY:
						$row['at'] = date('d/m',($row['at']-1)*86400);
						$row['payday'] = trans('yearly ($0)', $row['at']);
						$row['period'] = trans('yearly');
					break;
				}

				$assignments[$idx] = $row;

				// assigned nodes
				$assignments[$idx]['nodes'] = $this->DB->GetAll('SELECT nodes.name, nodes.id FROM nodeassignments, nodes
						    WHERE nodeid = nodes.id AND assignmentid = ?', array($row['id']));
				
				if ($row['discount'] == 0)
					$assignments[$idx]['discounted_value'] = $row['value'];
				else
					$assignments[$idx]['discounted_value'] = ((100 - $row['discount']) * $row['value']) / 100;
				
				if ($row['suspended'] == 1)
				{
					$assignments[$idx]['discounted_value'] = $assignments[$idx]['discounted_value'] * $this->CONFIG['finances']['suspension_percentage'] / 100;
				}
				
				$assignments[$idx]['discounted_value'] = round($assignments[$idx]['discounted_value'], 2);
				
				$now = time();
				
				if ($row['suspended'] == 0 && 
				    (($row['datefrom'] == 0 || $row['datefrom'] < $now) &&
				    ($row['dateto'] == 0 || $row['dateto'] > $now)))
				{
					// for proper summary
					$assignments[$idx]['real_value'] = $row['value'];
					$assignments[$idx]['real_disc_value'] = $assignments[$idx]['discounted_value'];
					$assignments[$idx]['real_downrate'] = $row['downrate'];
					$assignments[$idx]['real_downceil'] = $row['downceil'];
					$assignments[$idx]['real_uprate'] = $row['uprate'];
					$assignments[$idx]['real_upceil'] = $row['upceil'];
				}
			}
		}

		return $assignments;
	}

	function DeleteAssignment($id)
	{
		$this->SetTS('assignments');
		if($lid = $this->DB->GetOne('SELECT liabilityid FROM assignments WHERE id=?', array($id)))
		{
			$this->DB->Execute('DELETE FROM liabilities WHERE id=?', array($lid));
		}
		$this->DB->Execute('DELETE FROM nodeassignments WHERE assignmentid=?', array($id));
		return $this->DB->Execute('DELETE FROM assignments WHERE id=?', array($id));
	}

	function AddAssignment($assignmentdata)
	{
		$this->SetTS('assignments');
		
		if(isset($assignmentdata['value']) && $assignmentdata['value']>0)
		{
			$this->DB->Execute('INSERT INTO liabilities (name, value, taxid, prodid) VALUES (?, ?, ?, ?)', 
					    array($assignmentdata['name'],
						    $assignmentdata['value'],
						    $assignmentdata['taxid'],
						    $assignmentdata['prodid']
					    ));
			$lid = $this->DB->GetLastInsertID('liabilities');
			$this->SetTS('liabilities');
		}
		
		$this->DB->Execute('INSERT INTO assignments (tariffid, customerid, period, at, invoice, settlement, datefrom, dateto, discount, liabilityid) 
					    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', 
					    array($assignmentdata['tariffid'], 
						    $assignmentdata['customerid'], 
						    $assignmentdata['period'], 
						    $assignmentdata['at'], 
						    $assignmentdata['invoice'], 
						    $assignmentdata['settlement'], 
						    $assignmentdata['datefrom'], 
						    $assignmentdata['dateto'], 
						    $assignmentdata['discount'],
						    isset($lid) ? $lid : 0,
						    ));

		$result = $this->DB->GetLastInsertID('assignments');

		if(sizeof($assignmentdata['nodes']))
			foreach($assignmentdata['nodes'] as $node)
				$this->DB->Execute('INSERT INTO nodeassignments (nodeid, assignmentid) VALUES (?,?)',
					array($node, $result));
		
		return $result;
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
		$number = $invoice['invoice']['number'];
		$type = $invoice['invoice']['type'];

		$this->DB->Execute('INSERT INTO documents (number, numberplanid, type, cdate, paytime, paytype, userid, customerid, name, address, ten, ssn, zip, city)
				    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
				    array($number, 
					    $invoice['invoice']['numberplanid'] ? $invoice['invoice']['numberplanid'] : 0, 
					    $type, 
					    $cdate, 
					    $invoice['invoice']['paytime'], 
					    $invoice['invoice']['paytype'], 
					    $this->AUTH->id, 
					    $invoice['customer']['id'], 
					    $invoice['customer']['customername'], 
					    $invoice['customer']['address'], 
					    $invoice['customer']['ten'], 
					    $invoice['customer']['ssn'], 
					    $invoice['customer']['zip'], 
					    $invoice['customer']['city']
					));
		$iid = $this->DB->GetLastInsertID('documents');

		$itemid=0;
		foreach($invoice['contents'] as $idx => $item)
		{
			$itemid++;
			$item['valuebrutto'] = str_replace(',','.',$item['valuebrutto']);
			$item['count'] = str_replace(',','.',$item['count']);
			$item['discount'] = str_replace(',','.',$item['discount']);

			$this->DB->Execute('INSERT INTO invoicecontents (docid, itemid, value, taxid, prodid, content, count, discount, description, tariffid) 
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array(
					$iid,
					$itemid,
					$item['valuebrutto'],
					$item['taxid'],
					$item['prodid'],
					$item['jm'],
					$item['count'],
					$item['discount'],
					$item['name'],
					$item['tariffid']));

			$this->AddBalance(array('value' => $item['valuebrutto']*$item['count']*-1, 'taxid' => $item['taxid'], 'customerid' => $invoice['customer']['id'], 'comment' => $item['name'], 'docid' => $iid, 'itemid'=>$itemid));
		}

		$this->SetTS('documents');
		$this->SetTS('invoicecontents');

		return $iid;
	}

	function InvoiceUpdate($invoice)
	{
		$cdate = $invoice['invoice']['cdate'] ? $invoice['invoice']['cdate'] : time();
		$iid = $invoice['invoice']['id'];

		$this->DB->Execute('UPDATE documents SET cdate = ?, paytime = ?, paytype = ?, customerid = ?, name = ?, address = ?, ten = ?, ssn = ?, zip = ?, city = ? WHERE id = ?', array($cdate, $invoice['invoice']['paytime'], $invoice['invoice']['paytype'], $invoice['customer']['id'], $invoice['customer']['customername'], $invoice['customer']['address'], $invoice['customer']['ten'], $invoice['customer']['ssn'], $invoice['customer']['zip'], $invoice['customer']['city'], $iid));
		$this->DB->Execute('DELETE FROM invoicecontents WHERE docid = ?', array($iid));
		$this->DB->Execute('DELETE FROM cash WHERE docid = ?', array($iid));
		$this->DB->Execute('UPDATE cash SET docid = 0, itemid = 0, customerid = ? WHERE docid = ?', array($invoice['customer']['id'], $iid));

		$itemid=0;
		foreach($invoice['contents'] as $idx => $item)
		{
			$itemid++;

			$this->DB->Execute('INSERT INTO invoicecontents (docid, itemid, value, taxid, prodid, content, count, discount, description, tariffid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
				array(
					$iid,
					$itemid,
					$item['valuebrutto'],
					$item['taxid'],
					$item['prodid'],
					$item['jm'],
					$item['count'],
					$item['discount'],
					$item['name'],
					$item['tariffid']));
			$this->AddBalance(array('time' => $cdate, 'value' => $item['valuebrutto']*$item['count']*-1, 'taxid' => $item['taxid'], 'customerid' => $invoice['customer']['id'], 'comment' => $item['name'], 'docid' => $iid, 'itemid'=>$itemid));
		}

		$this->SetTS('documents');
		$this->SetTS('invoicecontents');
	}

	function InvoiceDelete($invoiceid)
	{
		$this->DB->Execute('DELETE FROM documents WHERE id = ?', array($invoiceid));
		$this->DB->Execute('DELETE FROM invoicecontents WHERE docid = ?', array($invoiceid));
		$this->DB->Execute('DELETE FROM cash WHERE docid = ?', array($invoiceid));
		$this->SetTS('documents');
		$this->SetTS('invoicecontents');
	}

	function InvoiceContentDelete($invoiceid, $itemid=0)
	{
		if($itemid)
		{
			$this->DB->Execute('DELETE FROM invoicecontents WHERE docid=? AND itemid=?', array($invoiceid, $itemid));

			if(!$this->DB->GetOne('SELECT COUNT(*) FROM invoicecontents WHERE docid=?', array($invoiceid)))
			{
				// if that was the last item of invoice contents
				$this->DB->Execute('DELETE FROM documents WHERE id = ?', array($invoiceid));
			}
			$this->DB->Execute('DELETE FROM cash WHERE docid = ? AND itemid = ?', array($invoiceid, $itemid));
			$this->SetTS('documents');
			$this->SetTS('invoicecontents');
		}
		else
			$this->InvoiceDelete($invoiceid);
	}

	function GetInvoiceContent($invoiceid)
	{
		if($result = $this->DB->GetRow('SELECT documents.id AS id, number, name, customerid, userid, address, zip, city, ten, ssn, cdate, paytime, paytype, template, numberplanid, closed, reference
					    FROM documents 
					    LEFT JOIN numberplans ON (numberplanid = numberplans.id)
					    WHERE documents.id=? AND (type = ? OR type = ?)', array($invoiceid, DOC_INVOICE, DOC_CNOTE)))
		{
			if($result['reference'])
				$result['invoice'] = $this->GetInvoiceContent($result['reference']);
			
			if($result['userid'])
				$result['user'] = $this->GetUserName($result['userid']);

			if($result['content'] = $this->DB->GetAll('SELECT invoicecontents.value AS value, itemid, taxid, taxes.value AS taxvalue, taxes.label AS taxlabel, prodid, content, count, invoicecontents.description AS description, tariffid, itemid, discount
						FROM invoicecontents 
						LEFT JOIN taxes ON taxid = taxes.id 
						WHERE docid=? 
						ORDER BY itemid', array($invoiceid))
			)
				foreach($result['content'] as $idx => $row)
				{
					if($result['invoice'])
					{
						$row['value'] += $result['invoice']['content'][$idx]['value'];
						$row['count'] += $result['invoice']['content'][$idx]['count'];
					}
					
					$result['content'][$idx]['basevalue'] = round(($row['value'] / (100 + $row['taxvalue']) * 100),2);
					$result['content'][$idx]['totalbase'] = $result['content'][$idx]['basevalue'] * $row['count'];
					$result['content'][$idx]['totaltax'] = ($row['value'] - $result['content'][$idx]['basevalue']) * $row['count'];
					$result['content'][$idx]['total'] = $row['value'] * $row['count'];
					$result['content'][$idx]['value'] = $row['value'];
					$result['content'][$idx]['count'] = $row['count'];

					$result['taxest'][$row['taxvalue']]['base'] += $result['content'][$idx]['totalbase'];
					$result['taxest'][$row['taxvalue']]['total'] += $result['content'][$idx]['total'];
					$result['taxest'][$row['taxvalue']]['taxlabel'] = $row['taxlabel'];
					$result['taxest'][$row['taxvalue']]['tax'] += $result['content'][$idx]['totaltax'];

					$result['totalbase'] += $result['content'][$idx]['totalbase'];
					$result['totaltax'] += $result['content'][$idx]['totaltax'];
					$result['total'] += $result['content'][$idx]['total'];
    
					// for backward compatybility
					$result['taxest'][$row['taxvalue']]['taxvalue'] = $row['taxvalue'];
					$result['content'][$idx]['pkwiu'] = $row['prodid'];
					
					$result['discount'] += $row['discount'];
				}

			$result['pdate'] = $result['cdate'] + ($result['paytime'] * 86400);
			$result['value'] = $result['total'] - $result['invoice']['value'];
			
			if($result['value'] < 0)
			{
				$result['value'] = abs($result['value']);
				$result['rebate'] = true;
			}
			$result['valuep'] = round( ($result['value'] - floor($result['value'])) * 100);

			$result['customerpin'] = $this->DB->GetOne('SELECT pin FROM customers WHERE id=?', array($result['customerid']));
			
			// NOTE: don't waste CPU/mem when printing history is not set:
			if(chkconfig($this->CONFIG['invoices']['print_balance_history']))
			{
				$result['customerbalancelist'] = $this->GetCustomerBalanceList($result['customerid']);
				$result['customerbalancelistlimit'] = $this->CONFIG['invoices']['print_balance_history_limit'];
			}

			// for backward compat.
			$result['totalg'] = round( ($result['value'] - floor($result['value'])) * 100);
			$result['year'] = date('Y',$result['cdate']);
			$result['month'] = date('m',$result['cdate']);
			$result['pesel'] = $result['ssn'];
			$result['nip'] = $result['ten'];
			
			return $result;
		}
		else
			return FALSE;
	}

	function GetTariffList()
	{
		if($tarifflist = $this->DB->GetAll('SELECT tariffs.id AS id, name, tariffs.value AS value, taxes.label AS tax, taxes.value AS taxvalue, prodid, tariffs.description AS description, uprate, downrate, upceil, downceil, climit, plimit
				FROM tariffs LEFT JOIN taxes ON taxid = taxes.id ORDER BY name ASC'))
		{
			$assigned = $this->DB->GetAllByKey('SELECT tariffid, COUNT(*) AS count, 
							SUM(CASE period 
							    WHEN '.DAILY.' THEN tariffs.value*30 
							    WHEN '.WEEKLY.' THEN tariffs.value*4 
							    WHEN '.MONTHLY.' THEN tariffs.value 
							    WHEN '.QUARTERLY.' THEN tariffs.value/3 
							    WHEN '.YEARLY.' THEN tariffs.value/12 END) AS value
						FROM assignments, tariffs
						WHERE tariffid = tariffs.id AND suspended = 0
						AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0)
						GROUP BY tariffid', 'tariffid');

			foreach($tarifflist as $idx => $row)
			{
				$suspended = $this->DB->GetRow('SELECT COUNT(*) AS count, 
							SUM(CASE a.period 
							    WHEN '.DAILY.' THEN t.value*30 
							    WHEN '.WEEKLY.' THEN t.value*4 
							    WHEN '.MONTHLY.' THEN t.value 
							    WHEN '.QUARTERLY.' THEN t.value/3 
							    WHEN '.YEARLY.' THEN t.value/12 END) AS value
						FROM assignments a LEFT JOIN tariffs t ON (t.id = a.tariffid), assignments b
						WHERE a.customerid = b.customerid AND a.tariffid = ? AND b.tariffid = 0 AND a.suspended = 0
						AND (b.datefrom <= ?NOW? OR b.datefrom = 0) AND (b.dateto > ?NOW? OR b.dateto = 0)', array($row['id']));

				$tarifflist[$idx]['customers'] = $this->GetCustomersWithTariff($row['id']);
				$tarifflist[$idx]['customerscount'] = $this->DB->GetOne("SELECT COUNT(DISTINCT customerid) FROM assignments WHERE tariffid = ?", array($row['id']));
				// count of 'active' assignments
				$tarifflist[$idx]['assignmentcount'] =  $assigned[$row['id']]['count'] - $suspended['count'];
				// avg monthly income
				$tarifflist[$idx]['income'] = $assigned[$row['id']]['value'] - $suspended['value'];
				$totalincome += $tarifflist[$idx]['income'];
				$totalcustomers += $tarifflist[$idx]['customers'];
				$totalcount += $tarifflist[$idx]['customerscount'];
				$totalassignmentcount += $tarifflist[$idx]['assignmentcount'];
			}
		}
		$tarifflist['total'] = sizeof($tarifflist);
		$tarifflist['totalincome'] = $totalincome;
		$tarifflist['totalcustomers'] = $totalcustomers;
		$tarifflist['totalcount'] = $totalcount;
		$tarifflist['totalassignmentcount'] = $totalassignmentcount;

		return $tarifflist;
	}

	function TariffMove($from, $to)
	{
		$this->SetTS('assignments');
		$ids = $this->DB->GetCol('SELECT assignments.id AS id FROM assignments, customers WHERE customerid = customers.id AND deleted = 0 AND tariffid = ?', array($from));
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
		$result = $this->DB->Execute('INSERT INTO tariffs (name, description, value, taxid, prodid, uprate, downrate, upceil, downceil, climit, plimit)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
				array(
					$tariffdata['name'],
					$tariffdata['description'],
					$tariffdata['value'],
					$tariffdata['taxid'],
					$tariffdata['prodid'],
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
		return $this->DB->Execute('UPDATE tariffs SET name=?, description=?, value=?, taxid=?, prodid=?, uprate=?, downrate=?, upceil=?, downceil=?, climit=?, plimit=? WHERE id=?', array($tariff['name'], $tariff['description'], $tariff['value'], $tariff['taxid'], $tariff['prodid'], $tariff['uprate'], $tariff['downrate'], $tariff['upceil'], $tariff['downceil'], $tariff['climit'], $tariff['plimit'], $tariff['id']));
	}

	function TariffDelete($id)
	{
		 if (!$this->GetCustomersWithTariff($id))
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
		$result = $this->DB->GetRow('SELECT tariffs.id AS id, name, tariffs.value AS value, taxid, taxes.label AS tax, taxes.value AS taxvalue, prodid, tariffs.description AS description, uprate, downrate, upceil, downceil, climit, plimit
					FROM tariffs LEFT JOIN taxes ON taxid = taxes.id WHERE tariffs.id=?', array($id));
		$result['customers'] = $this->DB->GetAll('SELECT customers.id AS id, COUNT(customers.id) AS cnt, '.$this->DB->Concat('upper(lastname)',"' '",'name').' AS customername FROM assignments, customers WHERE customers.id = customerid AND deleted = 0 AND tariffid = ? GROUP BY customers.id, customername ORDER BY customername', array($id));

		$assigned = $this->DB->GetRow('SELECT COUNT(*) AS count, 
						    SUM(CASE period 
							WHEN '.DAILY.' THEN tariffs.value*30 
							WHEN '.WEEKLY.' THEN tariffs.value*4 
							WHEN '.MONTHLY.' THEN tariffs.value 
							WHEN '.QUARTERLY.' THEN tariffs.value/3 
							WHEN '.YEARLY.' THEN tariffs.value/12 END) AS value
						FROM assignments, tariffs
						WHERE tariffid = tariffs.id AND tariffid = ? AND suspended = 0
						AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0)', array($id));

		$suspended = $this->DB->GetRow('SELECT COUNT(*) AS count, 
						    SUM(CASE a.period 
							WHEN '.DAILY.' THEN t.value*30 
							WHEN '.WEEKLY.' THEN t.value*4 
							WHEN '.MONTHLY.' THEN t.value 
							WHEN '.QUARTERLY.' THEN t.value/3 
							WHEN '.YEARLY.' THEN t.value/12 END) AS value
						FROM assignments a LEFT JOIN tariffs t ON (t.id = a.tariffid), assignments b
						WHERE a.customerid = b.customerid AND a.tariffid = ? AND b.tariffid = 0 AND a.suspended = 0
						AND (b.datefrom <= ?NOW? OR b.datefrom = 0) AND (b.dateto > ?NOW? OR b.dateto = 0)', array($id));

		// count of all customers with that tariff
		$result['customerscount'] = sizeof($result['customers']);
		// count of all assignments
		$result['count'] = $this->GetCustomersWithTariff($id);
		// count of 'active' assignments
		$result['assignmentcount'] =  $assigned['count'] - $suspended['count'];
		// avg monthly income (without unactive assignments)
		$result['totalval'] = $assigned['value'] - $suspended['value'];

		$result['rows'] = ceil($result['customerscount']/2);
		return $result;
	}

	function GetTariffs()
	{
		return $this->DB->GetAll('SELECT tariffs.id AS id, name, tariffs.value AS value, uprate, downrate, upceil, downceil, climit, plimit, taxid, taxes.value AS taxvalue, taxes.label AS tax, prodid
					FROM tariffs LEFT JOIN taxes ON taxid = taxes.id ORDER BY tariffs.value DESC');
	}

	function TariffExists($id)
	{
		return ($this->DB->GetOne('SELECT id FROM tariffs WHERE id=?', array($id))?TRUE:FALSE);
	}

	function ReceiptContentDelete($docid, $itemid=0)
	{
		if($itemid)
		{
			$this->DB->Execute('DELETE FROM receiptcontents WHERE docid=? AND itemid=?', array($docid, $itemid));

			if(!$this->DB->GetOne('SELECT COUNT(*) FROM receiptcontents WHERE docid=?', array($docid)))
			{
				// if that was the last item of invoice contents
				$this->DB->Execute('DELETE FROM documents WHERE id = ?', array($docid));
			}
			$this->DB->Execute('DELETE FROM cash WHERE docid = ? AND itemid = ?', array($docid, $itemid));
		}
		else
		{
			$this->DB->Execute('DELETE FROM receiptcontents WHERE docid=? AND itemid=?', array($docid, $itemid));
			$this->DB->Execute('DELETE FROM documents WHERE id = ?', array($docid));
			$this->DB->Execute('DELETE FROM cash WHERE docid = ? AND itemid = ?', array($docid, $itemid));
		}
	}

	function AddBalance($addbalance)
	{
		$this->SetTS('cash');
		$addbalance['value'] = str_replace(',','.',round($addbalance['value'],2));

		return $this->DB->Execute('INSERT INTO cash (time, userid, value, type, taxid, customerid, comment, docid, itemid)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
					array(isset($addbalance['time']) ? $addbalance['time'] : time(),
					    isset($addbalance['userid']) ? $addbalance['userid'] : $this->AUTH->id,
					    $addbalance['value'],
					    isset($addbalance['type']) ? $addbalance['type'] : 0,
					    isset($addbalance['taxid']) ? $addbalance['taxid'] : 0,
					    $addbalance['customerid'],
					    $addbalance['comment'],
					    isset($addbalance['docid']) ? $addbalance['docid'] : 0,
					    isset($addbalance['itemid']) ? $addbalance['itemid'] : 0
					    ));
	}

	function DelBalance($id)
	{
		$row = $this->DB->GetRow('SELECT docid, itemid, documents.type AS doctype
					FROM cash
					LEFT JOIN documents ON (docid = documents.id)
					WHERE cash.id=?', array($id));

		if(($row['doctype']==DOC_INVOICE || $row['doctype']==DOC_CNOTE) && $row['cashtype'] = '4' && $row['docid'] && $row['itemid'])
			$this->InvoiceContentDelete($row['docid'], $row['itemid']);
		elseif($row['doctype']=='2' && $row['docid'] && $row['itemid'])
			$this->ReceiptContentDelete($row['docid'], $row['itemid']);
		else
			$this->DB->Execute('DELETE FROM cash WHERE id=?', array($id));

		$this->SetTS('cash');
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
					case DAILY:
						$row['payday'] = trans('daily');
					break;
					case WEEKLY:
						$row['payday'] = trans('weekly ($0)', strftime("%a",mktime(0,0,0,0,$row['at']+5,0)));
					break;
					case MONTHLY:
						$row['payday'] = trans('monthly ($0)',$row['at']);
					break;
					case QUARTERLY:
						$row['payday'] = trans('quarterly ($0)', sprintf('%02d/%02d', $row['at']%100, $row['at']/100+1));
					break;
					case YEARLY:
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
			case DAILY:
				$payment['payday'] = trans('daily');
			break;
			case WEEKLY:
				$payment['payday'] = trans('weekly ($0)', strftime("%a",mktime(0,0,0,0,$payment['at']+5,0)));
			break;
			case MONTHLY:
				$payment['payday'] = trans('monthly ($0)',$payment['at']);
			break;
			case QUARTERLY:
				$payment['payday'] = trans('quarterly ($0)', sprintf('%02d/%02d', $payment['at']%100, $payment['at']/100+1));
			break;
			case YEARLY:
				$payment['payday'] = trans('yearly ($0)', date('d/m',($payment['at']-1)*86400));
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
			return $this->DB->GetLastInsertID('payments');
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
		return !($this->DB->GetOne('SELECT id FROM nodes WHERE ipaddr=inet_aton(?) OR ipaddr_pub=inet_aton(?)', array($ip, $ip)) ? TRUE : FALSE);
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
		if($netlist = $this->DB->GetAll('SELECT id, name, inet_ntoa(address) AS address, address AS addresslong, mask FROM networks ORDER BY name'))
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
		{
			$size = 0; $assigned = 0; $online = 0;

			foreach($networks as $idx => $row)
			{
				$row['prefix'] = mask2prefix($row['mask']);
				$row['size'] = pow(2,(32 - $row['prefix']));
				$row['broadcast'] = getbraddr($row['address'],$row['mask']);
				$row['broadcastlong'] = ip_long($row['broadcast']);
				$row['assigned'] = $this->DB->GetOne('SELECT COUNT(*) FROM nodes WHERE (ipaddr >= ? AND ipaddr <= ?) OR (ipaddr_pub >= ? AND ipaddr_pub <= ?)', array($row['addresslong'], $row['broadcastlong'], $row['addresslong'], $row['broadcastlong']));
            			$row['online'] = $this->DB->GetOne('SELECT COUNT(*) FROM nodes WHERE ((ipaddr >= ? AND ipaddr <= ?)  OR (ipaddr_pub >= ? AND ipaddr_pub <= ?)) AND (?NOW? - lastonline < ?)', array($row['addresslong'], $row['broadcastlong'], $row['addresslong'], $row['broadcastlong'], $this->CONFIG['phpui']['lastonline_limit']));
				$networks[$idx] = $row;
				$size += $row['size'];
				$assigned += $row['assigned'];
				$online += $row['online'];
			}
			$networks['size'] = $size;
			$networks['assigned'] = $assigned;
			$networks['online'] = $online;
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
		return ($this->DB->Execute('UPDATE nodes SET ipaddr = ipaddr + ? WHERE ipaddr >= inet_aton(?) AND ipaddr <= inet_aton(?)', array($shift, $network, getbraddr($network,$mask)))
			+ $this->DB->Execute('UPDATE nodes SET ipaddr_pub = ipaddr_pub + ? WHERE ipaddr_pub >= inet_aton(?) AND ipaddr_pub <= inet_aton(?)', array($shift, $network, getbraddr($network,$mask))));
	}

	function NetworkUpdate($networkdata)
	{
		$this->SetTS('networks');
		return $this->DB->Execute('UPDATE networks SET name=?, address=inet_aton(?), mask=?, interface=?, gateway=?, dns=?, dns2=?, domain=?, wins=?, dhcpstart=?, dhcpend=? WHERE id=?', array(strtoupper($networkdata['name']),$networkdata['address'],$networkdata['mask'],strtolower($networkdata['interface']),$networkdata['gateway'],$networkdata['dns'],$networkdata['dns2'],$networkdata['domain'],$networkdata['wins'],$networkdata['dhcpstart'],$networkdata['dhcpend'],$networkdata['id']));
	}

	function NetworkCompress($id,$shift=0)
	{
		$nodes = array();
		$network = $this->GetNetworkRecord($id);
		$address = $network['addresslong'] + $shift;
		$broadcast = $network['addresslong'] + $network['size'];
		foreach($network['nodes']['id'] as $idx => $value)
			if($value)
				$nodes[] = $network['nodes']['addresslong'][$idx];
		rsort($nodes);

		for($i = $address+1; $i < $broadcast; $i++)
		{
			if(!sizeof($nodes)) break;
			$ip = array_pop($nodes);
			if($i==$ip)
				continue;
			else
			{
				if(!$this->DB->Execute('UPDATE nodes SET ipaddr=? WHERE ipaddr=?', array($i,$ip)))
					$this->DB->Execute('UPDATE nodes SET ipaddr_pub=? WHERE ipaddr_pub=?', array($i,$ip));
			}
		}

		$this->SetTS('nodes');
	}

	function NetworkRemap($src,$dst)
	{
		$this->SetTS('nodes');
		$network['source'] = $this->GetNetworkRecord($src);
		$network['dest'] = $this->GetNetworkRecord($dst);
		$address = $network['dest']['addresslong']+1;
		$broadcast = $network['dest']['addresslong'] + $network['dest']['size'];
		foreach($network['source']['nodes']['id'] as $idx => $value)
			if($value)
				$nodes[] = $network['source']['nodes']['addresslong'][$idx];
		foreach($network['dest']['nodes']['id'] as $idx => $value)
			if($value)
				$destnodes[] = $network['dest']['nodes']['addresslong'][$idx];

		for($i = $address; $i < $broadcast; $i++)
		{
			if(!sizeof($nodes)) break;
			$ip = array_pop($nodes);

			while(in_array($i, (array)$destnodes))
				$i++;

			if(!$this->DB->Execute('UPDATE nodes SET ipaddr=? WHERE ipaddr=?', array($i,$ip)))
				$this->DB->Execute('UPDATE nodes SET ipaddr_pub=? WHERE ipaddr_pub=?', array($i,$ip));

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

		$network['assigned'] = $this->DB->GetOne('SELECT COUNT(*) FROM nodes WHERE (ipaddr >= ? AND ipaddr < ?) OR (ipaddr_pub >= ? AND ipaddr_pub < ?)', array($network['addresslong'], $network['addresslong'] + $network['size'], $network['addresslong'], $network['addresslong'] + $network['size']));

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

		$network['pageassigned'] = 0;

		$nodes = $this->DB->GetAllByKey('SELECT id, name, ipaddr, ownerid, netdev FROM nodes WHERE ipaddr >= ? AND ipaddr <= ?','ipaddr', array(($network['addresslong'] + $start), ($network['addresslong'] + $end)));
		if($nodespub = $this->DB->GetAllByKey('SELECT id, name, ipaddr_pub, ownerid, netdev FROM nodes WHERE ipaddr_pub >= ? AND ipaddr_pub <= ?','ipaddr_pub', array(($network['addresslong'] + $start), ($network['addresslong'] + $end))))
			foreach($nodespub as $idx => $row)
				$nodes["".$idx.""] = $row;

		for($i = 0; $i < ($end - $start) ; $i ++)
		{
			$longip = $network['addresslong'] + $i + $start;

			$node = isset($nodes["".$longip.""]) ? $nodes["".$longip.""] : NULL;
			$network['nodes']['id'][$i] = isset($node['id']) ? $node['id'] : 0;
			$network['nodes']['netdev'][$i] = isset($node['netdev']) ? $node['netdev'] : 0;
			$network['nodes']['ownerid'][$i] = isset($node['ownerid']) ? $node['ownerid'] : 0;

			$network['nodes']['addresslong'][$i] = $longip;
			$network['nodes']['address'][$i] = long2ip($longip);

			if( $network['nodes']['addresslong'][$i] >= ip_long($network['dhcpstart']) && $network['nodes']['addresslong'][$i] <= ip_long($network['dhcpend']) )
				$network['nodes']['name'][$i] = 'DHCP';
			elseif(isset($node['name']))
				$network['nodes']['name'][$i] = $node['name'];

			if( isset($node['id']) )
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
			if( $network['nodes']['address'][$i] == $network['gateway'] && !isset($node['name']))
				$network['nodes']['name'][$i] = '*** GATEWAY ***';
		}
		$network['rows'] = ceil(sizeof($network['nodes']['address']) / 4);
		$network['pages'] = ceil($network['size'] / $plimit);
		$network['page'] = $page + 1;

		return $network;
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
		// To powinno by� lepiej zrobione...
		$list = $this->GetNetDevConnected($id);
		$i = 0;
		$names = array();
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
		list($order,$direction) = sscanf($order, '%[^,],%s');

		($direction=='desc') ? $direction = 'desc' : $direction = 'asc';

		switch($order)
		{
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
			default:
				$sqlord = ' ORDER BY name';
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

	function GetNetDevNames()
	{
		return $this->DB->GetAll('SELECT id, name, location, producer FROM netdevices ORDER BY name');
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
			return $this->DB->GetLastInsertID('netdevices');
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
		return $this->DB->GetAll('SELECT id, name, mac, ipaddr, inet_ntoa(ipaddr) AS ip, ipaddr_pub, inet_ntoa(ipaddr_pub) AS ip_pub, access, info FROM nodes WHERE ownerid=0 AND netdev=?', array($id));
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
		    $users = $this->DB->GetAll('SELECT id, name FROM users WHERE deleted=0');
		    foreach($users as $user)
		    {
			    $user['rights'] = $this->GetUserRightsRT($user['id'],$id);
			    $queue['rights'][] = $user;
		    }
		    return $queue;
		}
		else
		    return NULL;
	}

	function GetUserRightsRT($user, $queue, $ticket=NULL)
	{
		if($queue==0)
			$queue = $this->DB->GetOne('SELECT queueid FROM rttickets WHERE id=?', array($ticket));

		$rights = $this->DB->GetOne('SELECT rights FROM rtrights WHERE userid=? AND queueid=?', array($user, $queue));
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

	function RightsRTAdd($queueid, $userid, $rights)
	{
		$this->DB->Execute('INSERT INTO rtrights(queueid, userid, rights) VALUES(?, ?, ?)', array($queueid, $userid, $rights));
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

		list($order,$direction) = sscanf($order, '%[^,],%s');

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

		if($result = $this->DB->GetAll('SELECT rttickets.id AS id, rttickets.customerid AS customerid, requestor, rttickets.subject AS subject, state, owner AS ownerid, users.name AS ownername, '.$this->DB->Concat('UPPER(customers.lastname)',"' '",'customers.name').' AS customername, rttickets.createtime AS createtime, MAX(rtmessages.createtime) AS lastmodified
		    FROM rttickets LEFT JOIN rtmessages ON (rttickets.id = rtmessages.ticketid)
		    LEFT JOIN users ON (owner = users.id)
		    LEFT JOIN customers ON (rttickets.customerid = customers.id)
		    WHERE queueid = ? '.$statefilter
		    .' GROUP BY rttickets.id, requestor, rttickets.createtime, rttickets.subject, state, owner, users.name, rttickets.customerid, customers.lastname, customers.name '
		    .($sqlord !='' ? $sqlord.' '.$direction:''), array($id)))
		{
			foreach($result as $idx => $ticket)
			{
				//$ticket['requestoremail'] = ereg_replace('^.*<(.*@.*)>$','\1',$ticket['requestor']);
				//$ticket['requestor'] = str_replace(' <'.$ticket['requestoremail'].'>','',$ticket['requestor']);
				if(!$ticket['customerid'])
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
		$this->DB->Execute('INSERT INTO rttickets (queueid, customerid, requestor, subject, state, owner, createtime)
				    VALUES (?, ?, ?, ?, 0, 0, ?)', array($ticket['queue'], $ticket['customerid'], $ticket['requestor'], $ticket['subject'], $ts));
		
		$id = $this->DB->GetLastInsertID('rttickets');
		
		$this->DB->Execute('INSERT INTO rtmessages (ticketid, customerid, createtime, subject, body, mailfrom)
				    VALUES (?, ?, ?, ?, ?, ?)', array($id, $ticket['customerid'], $ts, $ticket['subject'], $ticket['body'], $ticket['mailfrom']));
		
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
			return $this->DB->Execute('UPDATE rttickets SET queueid=?, subject=?, state=?, owner=?, customerid=?, resolvetime=?NOW? WHERE id=?', array($ticket['queueid'], $ticket['subject'], $ticket['state'], $ticket['owner'], $ticket['customerid'], $ticket['ticketid']));
		else
		{
			// check if ticket was resolved, then set resolvetime=0
			if($this->GetTicketState($ticket['ticketid'])==2)
				return $this->DB->Execute('UPDATE rttickets SET queueid=?, subject=?, state=?, owner=?, customerid=?, resolvetime=0 WHERE id=?', array($ticket['queueid'], $ticket['subject'], $ticket['state'], $ticket['owner'], $ticket['customerid'], $ticket['ticketid']));
			else
				return $this->DB->Execute('UPDATE rttickets SET queueid=?, subject=?, state=?, owner=?, customerid=? WHERE id=?', array($ticket['queueid'], $ticket['subject'], $ticket['state'], $ticket['owner'], $ticket['customerid'], $ticket['ticketid']));
		}
	}

	function GetTicketContents($id)
	{
		$ticket = $this->DB->GetRow('
			SELECT rttickets.id AS ticketid, queueid, rtqueues.name AS queuename, requestor, state, owner, customerid, '.$this->DB->Concat('UPPER(customers.lastname)',"' '",'customers.name').' AS customername, users.name AS ownername, createtime, resolvetime, subject
			FROM rttickets
			LEFT JOIN rtqueues ON (queueid = rtqueues.id)
			LEFT JOIN users ON (owner = users.id)
			LEFT JOIN customers ON (customers.id = customerid)
			WHERE rttickets.id = ?', array($id));
		$ticket['messages'] = $this->DB->GetAll('
			SELECT rtmessages.id AS id, mailfrom, subject, body, createtime, customerid, '.$this->DB->Concat('UPPER(customers.lastname)',"' '",'customers.name').' AS customername, userid, users.name AS username
			FROM rtmessages
			LEFT JOIN customers ON (customers.id = customerid)
			LEFT JOIN users ON (users.id = userid)
			WHERE ticketid = ? ORDER BY createtime ASC', array($id));
		if(!$ticket['customerid'])
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

	function SetTicketOwner($ticket, $user=NULL)
	{
		if(!$user) $user = $this->AUTH->id;
		$this->SetTS('rttickets');
		return $this->DB->Execute('UPDATE rttickets SET owner=? WHERE id = ?', array($user, $ticket));
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
			case 'documentlist_pagelimit':
			case 'timeout':
			case 'timetable_days_forward':
			case 'nodepassword_length':
			case 'check_for_updates_period':
			case 'print_balance_list_limit':
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
			case 'helpdesk_reply_body':
			case 'to_words_short_format':
			case 'disable_devel_warning':
			case 'newticket_notify':
			case 'print_balance_list':
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

	function GetAccountId($login, $domainid=0)
	{
		return $this->DB->GetOne('SELECT id FROM passwd WHERE login = ? AND domainid = ?', array($login, $domainid));
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
				$result['nodename'][] = $this->GetNodeNameByMAC($hwaddr);
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
						$result['nodename'][] = $this->GetNodeNameByMAC($hwaddr);
					}
				}
				fclose($file);
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

	function CheckUpdates($force = FALSE)
	{
		$uiid = $this->GetUniqueInstallationID();
		$time = $this->DB->GetOne('SELECT ?NOW?');
		$content = FALSE;
		if($force == TRUE)
			$lastcheck = 0;
		elseif(!($lastcheck = $this->DB->GetOne('SELECT keyvalue FROM dbinfo WHERE keytype=?', array('last_check_for_updates_timestamp'))))
			$lastcheck = 0;
		if($lastcheck + $this->CONFIG['phpui']['check_for_updates_period'] < $time)
		{
			list($v, ) = split(' ', $this->_version);
			
			if($content = fetch_url('http://lms.rulez.pl/update.php?uiid='.$uiid.'&v='.$v))
			{
				if($lastcheck == 0)
					$this->DB->Execute('INSERT INTO dbinfo (keyvalue, keytype) VALUES (?NOW?, ?)', array('last_check_for_updates_timestamp'));
				else
					$this->DB->Execute('UPDATE dbinfo SET keyvalue=?NOW? WHERE keytype=?', array('last_check_for_updates_timestamp'));
			}

			$content = unserialize($content);
			$content['regdata'] = unserialize($content['regdata']);
			
			$this->DB->Execute('DELETE FROM dbinfo WHERE keytype LIKE ?', array('regdata_%'));
			
			if(is_array($content['regdata']))
			{
				foreach(array('id', 'name', 'url', 'hidden') as $key)
					$this->DB->Execute('INSERT INTO dbinfo (keytype, keyvalue) VALUES (?, ?)', array('regdata_'.$key, $content['regdata'][$key]));
			}
		}

		return $content;
	}

	function GetRegisterData()
	{
		if($regdata = $this->DB->GetAll('SELECT * FROM dbinfo WHERE keytype LIKE ?', array('regdata_%')))
		{
			foreach($regdata as $regline)
				$registerdata[str_replace('regdata_', '', $regline['keytype'])] = $regline['keyvalue'];
			return $registerdata;
		}
		return NULL;
	}

	function UpdateRegisterData($name, $url, $hidden)
	{
		$name = rawurlencode($name);
		$url = rawurlencode($url);
		$uiid = $this->GetUniqueInstallationID();
		$url = 'http://lms.rulez.pl/register.php?uiid='.$uiid.'&name='.$name.'&url='.$url.($hidden == TRUE ? '&hidden=1' : '');

		if(fetch_url($url)!==FALSE)
		{
			// ok, update done, so, let we fall asleep for at least 2 seconds, let's viper put our
			// registration data into database. in future we should read info from register.php,
			// ie. 'Password' incorrect if we protect each installation with password (but then
			// we should use https)

			sleep(5);
			$this->DB->Execute('DELETE FROM dbinfo WHERE keytype = ?', array('last_check_for_updates_timestamp'));
			$this->CheckUpdates(TRUE);
			return TRUE;
		}

		return FALSE;
	}

	function SendMail($recipients, $headers, $body, $files=NULL)
	{
		@include_once('Mail.php');
		if(!class_exists('Mail'))
			return trans('Can\'t send message. PEAR::Mail not found!');

		$params['host'] = $this->CONFIG['phpui']['smtp_host'];
		$params['port'] = $this->CONFIG['phpui']['smtp_port'];

		if ($this->CONFIG['phpui']['smtp_username'])
		{
			$params['auth'] = ($this->CONFIG['phpui']['smtp_auth_type'] ? $this->CONFIG['phpui']['smtp_auth_type'] : true);
			$params['username'] = $this->CONFIG['phpui']['smtp_username'];
			$params['password'] = $this->CONFIG['phpui']['smtp_password'];
		}
		else
			$params['auth'] = false;

		$headers['X-Mailer'] = 'LMS-'.$this->_version;
		$headers['X-Remote-IP'] = $_SERVER['REMOTE_ADDR'];
		$headers['X-HTTP-User-Agent'] = $_SERVER['HTTP_USER_AGENT'];
		$headers['Mime-Version'] = '1.0';

		if ($files)
		{
			$boundary = '-LMS-'.str_replace(' ', '.', microtime());
			$headers['Content-Type'] = "multipart/mixed;\n  boundary=\"".$boundary.'"';
			$buf = "\nThis is a multi-part message in MIME format.\n\n";
			$buf .= '--'.$boundary."\n";
			$buf .= "Content-Type: text/plain; charset=UTF-8\n\n";
			$buf .= $body."\n";
			while (list(, $chunk) = each($files))
			{
				$buf .= '--'.$boundary."\n";
				$buf .= "Content-Transfer-Encoding: base64\n";
				$buf .= "Content-Type: ".$chunk['content_type']."; name=\"".$chunk['filename']."\"\n";
				$buf .= "Content-Description:\n";
				$buf .= "Content-Disposition: attachment; filename=\"".$chunk['filename']."\"\n\n";
				$buf .= chunk_split(base64_encode($chunk['data']), 60, "\n");
			}
			$buf .= '--'.$boundary.'--';
		}
		else
		{
			$headers['Content-Type'] = 'text/plain; charset=UTF-8';
			$buf = $body;
		}

		$error = $mail_object =& Mail::factory('smtp', $params);
		if(PEAR::isError($error))
			return $error->getMessage();

		$error = $mail_object->send($recipients, $headers, $buf);
		if(PEAR::isError($error))
			return $error->getMessage();
		else
			return "";
	}

	function GetDocuments($customerid=NULL, $limit=NULL)
	{
		if(!$customerid) return NULL;
		
		if($list = $this->DB->GetAll('SELECT docid, number, type, title, fromdate, todate, description, filename, md5sum, contenttype, template, closed
				    FROM documentcontents, documents
				    LEFT JOIN numberplans ON(numberplanid = numberplans.id)
				    WHERE documents.id = documentcontents.docid
				    AND customerid = ?
				    ORDER BY cdate', array($customerid)))
		{
			if($limit)
			{
				$index = (sizeof($list) - $limit) > 0 ? sizeof($list) - $limit : 0;
				for($i = $index; $i < sizeof($list); $i++)
					$result[] = $list[$i];
			
				return $result;
			}
			else
				return $list;
		}
	}

	function GetTaxes($from=NULL, $to=NULL)
	{
		$from = $from ? $from : mktime(0,0,0);
		$to = $to ? $to : mktime(23,59,59);

		return $this->DB->GetAllByKey('SELECT id, value, label FROM taxes
			WHERE (validfrom = 0 OR validfrom <= ?)
			    AND (validto = 0 OR validto >= ?)
			ORDER BY value', 'id', array($from, $to));
	}
	
	function GetNumberPlans($doctype=NULL)
	{
		if(is_array($doctype))
			$list = $this->DB->GetAllByKey('
				SELECT id, template, isdefault, period 
				FROM numberplans WHERE doctype IN ('.implode(',',$doctype).') 
				ORDER BY id', 'id');
		elseif($doctype)
			$list = $this->DB->GetAllByKey('
				SELECT id, template, isdefault, period 
				FROM numberplans WHERE doctype = ? ORDER BY id', 
				'id', array($doctype));
		else
			$list = $this->DB->GetAllByKey('
				SELECT id, template, isdefault, period, doctype 
				FROM numberplans ORDER BY id', 'id');
		
		if($list)
		{
			$currmonth = date('n');
			switch($currmonth)
			{
				case 1: case 2: case 3: $startq = 1; break;
				case 4: case 5: case 6: $startq = 4; break;
				case 7: case 8: case 9: $startq = 7; break;
				case 10: case 11: case 12: $startq = 10; break;
			}
	
			$yearstart = mktime(0,0,0,1,1);
			$yearend = mktime(0,0,0,1,1,date('Y')+1);
			$quarterstart = mktime(0,0,0,$startq,1);
			$quarterend = mktime(0,0,0,$startq+3,1);
			$monthstart = mktime(0,0,0,$currmonth,1);
			$monthend = mktime(0,0,0,$currmonth+1,1);
			$weekstart = mktime(0,0,0,$currmonth,date('j')-strftime('%u')+1);
			$weekend = mktime(0,0,0,$currmonth,date('j')-strftime('%u')+1+7);
			$daystart = mktime(0,0,0);
			$dayend = mktime(0,0,0,date('n'),date('j')+1);

			$max = $this->DB->GetAllByKey('SELECT numberplanid AS id, MAX(number) AS max 
					    FROM documents LEFT JOIN numberplans ON (numberplanid = numberplans.id)
					    WHERE cdate >= (CASE period
						WHEN '.YEARLY.' THEN '.$yearstart.'
						WHEN '.QUARTERLY.' THEN '.$quarterstart.'
						WHEN '.MONTHLY.' THEN '.$monthstart.'
						WHEN '.WEEKLY.' THEN '.$weekstart.'
						WHEN '.DAILY.' THEN '.$daystart.' ELSE 0 END)
					    AND cdate < (CASE period
						WHEN '.YEARLY.' THEN '.$yearend.'
						WHEN '.QUARTERLY.' THEN '.$quarterend.'
						WHEN '.MONTHLY.' THEN '.$monthend.'
						WHEN '.WEEKLY.' THEN '.$weekend.'
						WHEN '.DAILY.' THEN '.$dayend.' ELSE 4294967296 END)
					    GROUP BY numberplanid','id');
					    
			foreach ($list as $idx => $item)
				if(isset($max[$item['id']]['max']))
					$list[$idx]['next'] = $max[$item['id']]['max']+1;
				else
					$list[$idx]['next'] = 1;
		}
		
		return $list;
	}
	
	function GetNewDocumentNumber($doctype=NULL, $planid=NULL, $cdate=NULL)
	{
		if($planid)
			$period = $this->DB->GetOne('SELECT period FROM numberplans WHERE id=?', array($planid));
		else
			$planid = 0;
		
		$period = $period ? $period : YEARLY;
		$cdate = $cdate ? $cdate : time();
		
		switch($period)
		{
			case DAILY:
				$start = mktime(0, 0, 0, date('n',$cdate), date('j',$cdate), date('Y',$cdate));
				$end = mktime(0, 0, 0, date('n',$cdate), date('j',$cdate)+1, date('Y',$cdate));
			break;
			case WEEKLY:
				$weekstart = date('j',$cdate)-strftime('%u',$cdate)+1;
				$start = mktime(0, 0, 0, date('n',$cdate), $weekstart, date('Y',$cdate));
				$end = mktime(0, 0, 0, date('n',$cdate), $weekstart+7, date('Y',$cdate));
			break;
			case MONTHLY:
				$start = mktime(0, 0, 0, date('n',$cdate), 1, date('Y',$cdate));
				$end = mktime(0, 0, 0, date('n',$cdate)+1, 1, date('Y',$cdate));
			break;
			case QUARTERLY:
				$currmonth = date('n');
				switch(date('n'))
				{
					case 1: case 2: case 3: $startq = 1; break;
					case 4: case 5: case 6: $startq = 4; break;
					case 7: case 8: case 9: $startq = 7; break;
					case 10: case 11: case 12: $startq = 10; break;
				}
				$start = mktime(0, 0, 0, $startq, 1, date('Y',$cdate));
				$end = mktime(0, 0, 0, $startq+3, 1, date('Y',$cdate));
			break;
			case YEARLY:
				$start = mktime(0, 0, 0, 1, 1, date('Y',$cdate));
				$end = mktime(0, 0, 0, 1, 1, date('Y', $cdate)+1);
			break;
			case CONTINUOUS:
				$number = $this->DB->GetOne('SELECT MAX(number) FROM documents 
						WHERE type = ? AND numberplanid = ?', array($doctype, $planid));
						
				return $number ? ++$number : 1;
			break;
		}
	
		$number = $this->DB->GetOne('
				SELECT MAX(number) 
				FROM documents 
				WHERE cdate >= ? AND cdate < ? AND type = ? AND numberplanid = ?', 
				array($start, $end, $doctype, $planid));
				
		return $number ? ++$number : 1;
	}

	function DocumentExists($number, $doctype=NULL, $planid=0, $cdate=NULL)
	{
		if($planid)
			$period = $this->DB->GetOne('SELECT period FROM numberplans WHERE id=?', array($planid));
		
		$period = $period ? $period : YEARLY;
		$cdate = $cdate ? $cdate : time();
		
		switch($period)
		{
			case DAILY:
				$start = mktime(0, 0, 0, date('n',$cdate), date('j',$cdate), date('Y',$cdate));
				$end = mktime(0, 0, 0, date('n',$cdate), date('j',$cdate)+1, date('Y',$cdate));
			break;
			case WEEKLY:
				$weekstart = date('j',$cdate)-strftime('%u',$cdate)+1;
				$start = mktime(0, 0, 0, date('n',$cdate), $weekstart, date('Y',$cdate));
				$end = mktime(0, 0, 0, date('n',$cdate), $weekstart+7, date('Y',$cdate));
			break;
			case MONTHLY:
				$start = mktime(0, 0, 0, date('n',$cdate), 1, date('Y',$cdate));
				$end = mktime(0, 0, 0, date('n',$cdate)+1, 1, date('Y',$cdate));
			break;
			case QUARTERLY:
				$currmonth = date('n');
				switch(date('n'))
				{
					case 1: case 2: case 3: $startq = 1; break;
					case 4: case 5: case 6: $startq = 4; break;
					case 7: case 8: case 9: $startq = 7; break;
					case 10: case 11: case 12: $startq = 10; break;
				}
				$start = mktime(0, 0, 0, $startq, 1, date('Y',$cdate));
				$end = mktime(0, 0, 0, $startq+3, 1, date('Y',$cdate));
			break;
			case YEARLY:
				$start = mktime(0, 0, 0, 1, 1, date('Y',$cdate));
				$end = mktime(0, 0, 0, 1, 1, date('Y', $cdate)+1);
			break;
			case CONTINUOUS:
				return $this->DB->GetOne('SELECT number FROM documents 
						WHERE type = ? AND number = ? AND numberplanid = ?', 
						array($doctype, $number, $planid)) ? TRUE : FALSE;
			break;
		}
	
		return $this->DB->GetOne('SELECT number FROM documents 
				WHERE cdate >= ? AND cdate < ? AND type = ? AND number = ? AND numberplanid = ?', 
				array($start, $end, $doctype, $number, $planid)) ? TRUE : FALSE;
	}
	
}

?>
