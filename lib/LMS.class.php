<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2009 LMS Developers
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
	var $cache = array();		// internal cache
	var $_version = '1.11-cvs';	// class version
	var $_revision = '$Revision$';

	function LMS(&$DB, &$AUTH, &$CONFIG) // class variables setting
	{
		$this->DB = &$DB;
		$this->AUTH = &$AUTH;
		$this->CONFIG = &$CONFIG;

		$this->_revision = preg_replace('/^.Revision: ([0-9.]+).*/', '\1', $this->_revision);
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
		}
	}
*/
	/*
	 *  Database functions (backups)
	 */

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
	 *  Internal cache
	 */

	function GetCache($key, $idx=null, $name=null)
	{
		if(array_key_exists($key, $this->cache))
		{
			if(!$idx)
				return $this->cache[$key];
			elseif(is_array($this->cache[$key]) && array_key_exists($idx, $this->cache[$key]))
			{
				if(!$name)
					return $this->cache[$key][$idx];
				elseif(is_array($this->cache[$key][$idx]) && array_key_exists($name, $this->cache[$key][$idx]))
					return $this->cache[$key][$idx][$name];
			}
		}
		return NULL;
	}

	/*
	 *  Users (Useristrators)
	 */

	function SetUserPassword($id, $passwd)
	{
		$this->DB->Execute('UPDATE users SET passwd=? WHERE id=?', array(crypt($passwd), $id));
	}

	function GetUserName($id) // returns user name
	{
		if (!$id)
			return NULL;

		if(!($name = $this->GetCache('users', $id, 'name')))
		{
			if($this->AUTH->id == $id)
				$name = $this->AUTH->logname;
			else
				$name = $this->DB->GetOne('SELECT name FROM users WHERE id=?', array($id));
			$this->cache['users'][$id]['name'] = $name;
		}
		return $name;
	}

	function GetUserNames() // returns short list of users
	{
		return $this->DB->GetAll('SELECT id, name FROM users WHERE deleted=0 ORDER BY login ASC');
	}

	function GetUserList() // returns list of users
	{
		if($userslist = $this->DB->GetAll('SELECT id, login, name, lastlogindate, lastloginip 
				FROM users WHERE deleted=0 ORDER BY login ASC'))
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

	function GetUserIDByLogin($login) 
	{
		return $this->DB->GetOne('SELECT id FROM users WHERE login=?', array($login));
	}

	function UserAdd($useradd) 
	{
		if($this->DB->Execute('INSERT INTO users (login, name, email, passwd, rights, 
				hosts, position) VALUES (?, ?, ?, ?, ?, ?, ?)', 
				array($useradd['login'], 
					$useradd['name'], 
					$useradd['email'], 
					crypt($useradd['password']),
					$useradd['rights'], 
					$useradd['hosts'],
					$useradd['position']
		)))
			return $this->DB->GetOne('SELECT id FROM users WHERE login=?', array($useradd['login']));
		else
			return FALSE;
	}

	function UserDelete($id) 
	{
		return $this->DB->Execute('UPDATE users SET deleted=1 WHERE id=?', array($id));
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

	function GetUserInfo($id) // zwraca pe�ne info o podanym userie
	{
		if($userinfo = $this->DB->GetRow('SELECT id, login, name, email, hosts, lastlogindate, 
				lastloginip, failedlogindate, failedloginip, deleted, position 
				FROM users WHERE id=?', array($id)))
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

	function UserUpdate($userinfo) 
	{
		return $this->DB->Execute('UPDATE users SET login=?, name=?, email=?, rights=?, 
				hosts=?, position=? WHERE id=?', 
				array($userinfo['login'],
					$userinfo['name'],
					$userinfo['email'],
					$userinfo['rights'],
					$userinfo['hosts'],
					$userinfo['position'],
					$userinfo['id']
				));
	}

	function GetUserRights($id)
	{
		$mask = $this->DB->GetOne('SELECT rights FROM users WHERE id = ?', array($id));

		$len = strlen($mask);
		$bin = '';
		$result = array();

		for($cnt=$len; $cnt > 0; $cnt --)
			$bin = sprintf('%04b',hexdec($mask[$cnt-1])).$bin;

		$len = strlen($bin);
		for($cnt=$len-1; $cnt >= 0; $cnt --)
			if($bin[$cnt] == '1')
				$result[] = $len - $cnt -1;

		return $result;
	}

	/*
	 *  Customers functions
	 */

	function GetCustomerName($id)
	{
		return $this->DB->GetOne('SELECT '.$this->DB->Concat('lastname',"' '",'name').' 
			    FROM customers WHERE id=?', array($id));
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
		switch($this->DB->GetOne('SELECT deleted FROM customersview WHERE id=?', array($id)))
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

	function CustomerAdd($customeradd)
	{
		if($this->DB->Execute('INSERT INTO customers (name, lastname, type,  
				    address, zip, city, countryid, email, ten, ssn, status, creationdate, 
				    creatorid, info, notes, serviceaddr, message, pin, regon, rbe, 
				    icn, cutoffstop, consentdate, divisionid, paytime, paytype) 
				    VALUES (?, UPPER(?), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?NOW?, 
				    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', 
				    array(lms_ucwords($customeradd['name']),  
					    $customeradd['lastname'], 
					    empty($customeradd['type']) ? 0 : 1,
					    $customeradd['address'], 
					    $customeradd['zip'], 
					    $customeradd['city'], 
					    $customeradd['countryid'], 
					    $customeradd['email'], 
					    $customeradd['ten'], 
					    $customeradd['ssn'], 
					    $customeradd['status'], 
					    $this->AUTH->id, 
					    $customeradd['info'], 
					    $customeradd['notes'],
					    $customeradd['serviceaddr'], 
					    $customeradd['message'], 
					    $customeradd['pin'],
					    $customeradd['regon'],
					    $customeradd['rbe'],
					    $customeradd['icn'],
					    $customeradd['cutoffstop'],
					    $customeradd['consentdate'],
					    $customeradd['divisionid'],
					    $customeradd['paytime'],
					    isset($customeradd['paytype']) ? $customeradd['paytype'] : NULL,
					    )))
		{
			return $this->DB->GetLastInsertID('customers');
		} else
			return FALSE;
	}

	function DeleteCustomer($id)
	{
		$this->DB->BeginTrans();
		
		$this->DB->Execute('UPDATE customers SET deleted=1, moddate=?NOW?, modid=? 
				WHERE id=?', array($this->AUTH->id, $id));
		$this->DB->Execute('DELETE FROM customerassignments WHERE customerid=?', array($id));
		$this->DB->Execute('DELETE FROM assignments WHERE customerid=?', array($id));
		// nodes
		$this->DB->Execute('DELETE FROM nodegroupassignments WHERE nodeid IN (
				SELECT id FROM nodes WHERE ownerid=?)', array($id));
		$this->DB->Execute('DELETE FROM nodes WHERE ownerid=?', array($id));
		// hosting
		$this->DB->Execute('UPDATE passwd SET ownerid=0 WHERE ownerid=?', array($id));
		$this->DB->Execute('UPDATE domains SET ownerid=0 WHERE ownerid=?', array($id));
		// Remove Userpanel rights
		if(!empty($this->CONFIG['directories']['userpanel_dir']))
			$this->DB->Execute('DELETE FROM up_rights_assignments WHERE customerid=?', array($id));
		
		$this->DB->CommitTrans();
	}

	function CustomerUpdate($customerdata)
	{
		return $this->DB->Execute('UPDATE customers SET status=?, type=?, address=?, 
				zip=?, city=?, countryid=?, email=?, ten=?, ssn=?, moddate=?NOW?, modid=?, 
				info=?, notes=?, serviceaddr=?, lastname=UPPER(?), name=?, 
				deleted=0, message=?, pin=?, regon=?, icn=?, rbe=?, 
				cutoffstop=?, consentdate=?, divisionid=?, paytime=?, paytype=? 
				WHERE id=?', 
			array( $customerdata['status'], 
				empty($customerdata['type']) ? 0 : 1,
				$customerdata['address'], 
				$customerdata['zip'], 
				$customerdata['city'], 
				$customerdata['countryid'],
				$customerdata['email'], 
				$customerdata['ten'], 
				$customerdata['ssn'], 
				isset($this->AUTH->id) ? $this->AUTH->id : 0,
				$customerdata['info'], 
				$customerdata['notes'],
				$customerdata['serviceaddr'], 
				$customerdata['lastname'], 
				lms_ucwords($customerdata['name']), 
				$customerdata['message'],
				$customerdata['pin'],
				$customerdata['regon'], 
				$customerdata['icn'], 
				$customerdata['rbe'], 
				$customerdata['cutoffstop'],
				$customerdata['consentdate'],
				$customerdata['divisionid'],
				$customerdata['paytime'],
				$customerdata['paytype'],
				$customerdata['id'],
				));
	}

	function GetCustomerNodesNo($id)
	{
		return $this->DB->GetOne('SELECT COUNT(*) FROM nodes WHERE ownerid=?', array($id));
	}

	function GetCustomerIDByIP($ipaddr)
	{
		return $this->DB->GetOne('SELECT ownerid FROM nodes 
			    WHERE ipaddr=inet_aton(?) OR ipaddr_pub=inet_aton(?)', 
			    array($ipaddr, $ipaddr));
	}

	function GetCashByID($id)
	{
		return $this->DB->GetRow('SELECT time, userid, value, taxid, customerid, comment 
			    FROM cash WHERE id=?', array($id));
	}

	function GetCustomerStatus($id)
	{
		return $this->DB->GetOne('SELECT status FROM customers WHERE id=?', array($id));
	}

	function GetCustomer($id, $short=false)
	{
		if($result = $this->DB->GetRow('SELECT c.*, '
			.$this->DB->Concat('UPPER(c.lastname)',"' '",'c.name').' AS customername,
			d.shortname AS division, d.account, co.name AS country
			FROM customers'.(defined('LMS-UI') ? 'view' : '').' c 
			LEFT JOIN divisions d ON (d.id = c.divisionid)
			LEFT JOIN countries co ON (co.id = c.countryid)
			WHERE c.id = ?', array($id)))
		{
			if(!$short)
			{
				$result['createdby'] = $this->GetUserName($result['creatorid']);
				$result['modifiedby'] = $this->GetUserName($result['modid']);
				$result['creationdateh'] = date('Y/m/d, H:i',$result['creationdate']);
				$result['moddateh'] = date('Y/m/d, H:i',$result['moddate']);
				$result['consentdate'] = $result['consentdate'] ? date('Y/m/d',$result['consentdate']) : '';
				$result['up_logins'] = $this->DB->GetRow('SELECT lastlogindate, lastloginip, 
					failedlogindate, failedloginip
		                        FROM up_customers WHERE customerid = ?', array($result['id']));
			
				if($cstate = $this->DB->GetRow('SELECT s.id, s.name FROM states s, zipcodes
					WHERE zip = ? AND stateid = s.id', array($result['zip'])))
				{
					$result['stateid'] = $cstate['id'];
					$result['cstate'] = $cstate['name'];
				}
			}
			$result['balance'] = $this->GetCustomerBalance($result['id']);
			$result['bankaccount'] = bankaccount($result['id'], $result['account']);

			$result['messengers'] = $this->DB->GetAllByKey('SELECT uid, type 
					FROM imessengers WHERE customerid = ? ORDER BY type', 'type',
					array($result['id']));
			$result['contacts'] = $this->DB->GetAll('SELECT phone, name
					FROM customercontacts WHERE customerid = ? ORDER BY id',
					array($result['id']));
				     
			return $result;
		}
		else
			return FALSE;
	}

	function GetCustomerNames()
	{
		return $this->DB->GetAllByKey('SELECT id, '.$this->DB->Concat('lastname',"' '",'name').' AS customername 
				FROM customersview WHERE status > 1 AND deleted = 0 
				ORDER BY lastname, name', 'id');
	}

	function GetAllCustomerNames()
	{
		return $this->DB->GetAllByKey('SELECT id, '.$this->DB->Concat('lastname',"' '",'name').' AS customername 
				FROM customersview WHERE deleted = 0
				ORDER BY lastname, name', 'id');
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

	function GetCustomerList($order='customername,asc', $state=NULL, $network=NULL, $customergroup=NULL, $search=NULL, $time=NULL, $sqlskey='AND', $nodegroup=NULL, $division=NULL)
	{
		list($order,$direction) = sscanf($order, '%[^,],%s');

		($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

		switch($order)
		{
			case 'id':
				$sqlord = ' ORDER BY c.id';
			break;
			case 'address':
				$sqlord = ' ORDER BY address';
			break;
			case 'balance':
				$sqlord = ' ORDER BY balance';
			break;
			case 'tariff':
				$sqlord = ' ORDER BY tariffvalue';
			break;
			default:
				$sqlord = ' ORDER BY customername';
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
		$groupless = ($state == 8) ? 1 : 0;
		$tariffless = ($state == 9) ? 1 : 0;
		$suspended = ($state == 10) ? 1 : 0;
		
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
							$searchargs[] = 'EXISTS (SELECT 1 FROM customercontacts
								WHERE customerid = c.id AND phone ?LIKE? '.$this->DB->Escape("%$value%").')';
						break;
						case 'zip':
						case 'city':
						case 'address':
							// UPPER here is a workaround for postgresql ILIKE bug
							$searchargs[] = "(UPPER($key) ?LIKE? UPPER(".$this->DB->Escape("%$value%").')
								OR UPPER(serviceaddr) ?LIKE? UPPER('.$this->DB->Escape("%$value%").'))';
						break;
						case 'customername':
							// UPPER here is a workaround for postgresql ILIKE bug
							$searchargs[] = $this->DB->Concat('UPPER(c.lastname)',"' '",'UPPER(c.name)').' ?LIKE? UPPER('.$this->DB->Escape("%$value%").')';
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
						case 'type':
							$searchargs[] = 'type = '.intval($value);
						break;
						case 'linktype':
							$searchargs[] = 'EXISTS (SELECT 1 FROM nodes
								WHERE ownerid = c.id AND linktype = '.intval($value).')';
						break;
						case 'doctype':
							$val = explode(':', $value); // <doctype>:<fromdate>:<todate>
							$searchargs[] = 'EXISTS (SELECT 1 FROM documents
								WHERE customerid = c.id'
								.(!empty($val[0]) ? ' AND type = '.intval($val[0]) : '')
								.(!empty($val[1]) ? ' AND cdate >= '.intval($val[1]) : '')
								.(!empty($val[2]) ? ' AND cdate <= '.intval($val[2]) : '')
								.')';
						break;
						case 'stateid':
							$searchargs[] = 'EXISTS (SELECT 1 FROM zipcodes z
								WHERE z.zip = c.zip AND z.stateid = '.intval($value).')';
						break;
						default:
							$searchargs[] = "$key ?LIKE? ".$this->DB->Escape("%$value%");
					}
				}
			}

		if(isset($searchargs))
			$sqlsarg = implode(' '.$sqlskey.' ',$searchargs);

		$suspension_percentage = $this->CONFIG['finances']['suspension_percentage'];

		if($customerlist = $this->DB->GetAll(
				'SELECT c.id AS id, '.$this->DB->Concat('UPPER(lastname)',"' '",'c.name').' AS customername, 
				status, address, zip, city, countryid, countries.name AS country, email, ten, ssn, c.info AS info, 
				message, c.divisionid, c.paytime AS paytime, COALESCE(b.value, 0) AS balance,
				COALESCE(t.value, 0) AS tariffvalue, s.account, s.warncount, s.online,
				(CASE WHEN s.account = s.acsum THEN 1
					WHEN s.acsum > 0 THEN 2	ELSE 0 END) AS nodeac,
				(CASE WHEN s.warncount = s.warnsum THEN 1
					WHEN s.warnsum > 0 THEN 2 ELSE 0 END) AS nodewarn
				FROM customersview c
				LEFT JOIN countries ON (c.countryid = countries.id) '
				.($customergroup ? 'LEFT JOIN customerassignments ON (c.id = customerassignments.customerid) ' : '')
				.'LEFT JOIN (SELECT
					SUM(value) AS value, customerid
					FROM cash'
					.($time ? ' WHERE time < '.$time : '').'
					GROUP BY customerid
				) b ON (b.customerid = c.id)
				LEFT JOIN (SELECT customerid, 
					SUM((CASE suspended
						WHEN 0 THEN (CASE discount WHEN 0 THEN (CASE WHEN t.value IS NULL THEN l.value ELSE t.value END)
							ELSE ((100 - discount) * (CASE WHEN t.value IS NULL THEN l.value ELSE t.value END)) / 100 END) 
						ELSE (CASE discount WHEN 0 THEN (CASE WHEN t.value IS NULL THEN l.value ELSE t.value END) * '.$suspension_percentage.' / 100 
							ELSE (CASE WHEN t.value IS NULL THEN l.value ELSE t.value END) * discount * '.$suspension_percentage.' / 10000 END) END)
					* (CASE period
						WHEN '.MONTHLY.' THEN 1
						WHEN '.YEARLY.' THEN 1/12.0
						WHEN '.HALFYEARLY.' THEN 1/6.0
						WHEN '.QUARTERLY.' THEN 1/3.0
						WHEN '.WEEKLY.' THEN 4
						WHEN '.DAILY.' THEN 30
						ELSE 0 END)
					) AS value 
					FROM assignments
					LEFT JOIN tariffs t ON (t.id = tariffid)
					LEFT JOIN liabilities l ON (l.id = liabilityid AND period != '.DISPOSABLE.')
					WHERE (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) 
					GROUP BY customerid
				) t ON (t.customerid = c.id)
				LEFT JOIN (SELECT ownerid,
					SUM(access) AS acsum, COUNT(access) AS account,
					SUM(warning) AS warnsum, COUNT(warning) AS warncount, 
					(CASE WHEN MAX(lastonline) > ?NOW? - '.intval($this->CONFIG['phpui']['lastonline_limit']).'
						THEN 1 ELSE 0 END) AS online
					FROM nodes
					WHERE ownerid > 0
					GROUP BY ownerid
				) s ON (s.ownerid = c.id)
				WHERE deleted = '.$deleted
				.($state ? ' AND c.status = '.intval($state) : '')
				.($division ? ' AND c.divisionid = '.intval($division) : '')
				.($online ? ' AND s.online = 1' : '')
				.($indebted ? ' AND b.value < 0' : '')
				.($disabled ? ' AND s.ownerid IS NOT NULL AND s.account > s.acsum' : '')
				.($network ? ' AND EXISTS (SELECT 1 FROM nodes WHERE ownerid = c.id AND 
							((ipaddr > '.$net['address'].' AND ipaddr < '.$net['broadcast'].') 
							OR (ipaddr_pub > '.$net['address'].' AND ipaddr_pub < '.$net['broadcast'].')))' : '')
				.($customergroup ? ' AND customergroupid='.$customergroup : '')
				.($nodegroup ? ' AND EXISTS (SELECT 1 FROM nodegroupassignments na
							JOIN nodes n ON (n.id = na.nodeid) 
							WHERE n.ownerid = c.id AND na.nodegroupid = '.intval($nodegroup).')' : '')
				.($groupless ? ' AND NOT EXISTS (SELECT 1 FROM customerassignments a 
							WHERE c.id = a.customerid)' : '')
				.($tariffless ? ' AND NOT EXISTS (SELECT 1 FROM assignments a 
							WHERE a.customerid = c.id
								AND (datefrom <= ?NOW? OR datefrom = 0) 
								AND (dateto >= ?NOW? OR dateto = 0)
								AND (tariffid != 0 OR liabilityid != 0))' : '')
				.($suspended ? ' AND EXISTS (SELECT 1 FROM assignments a
							WHERE a.customerid = c.id AND (
								(tariffid = 0 AND liabilityid = 0
								    AND (datefrom <= ?NOW? OR datefrom = 0)
								    AND (dateto >= ?NOW? OR dateto = 0)) 
								OR ((datefrom <= ?NOW? OR datefrom = 0)
								    AND (dateto >= ?NOW? OR dateto = 0)
								    AND suspended = 1)
								))' : '')
				.(isset($sqlsarg) ? ' AND ('.$sqlsarg.')' :'')
				.($sqlord !='' ? $sqlord.' '.$direction:'')
				))
		{
			foreach($customerlist as $idx => $row)
			{
				// summary
				if($row['balance'] > 0)
					$over += $row['balance'];
				elseif($row['balance'] < 0)
					$below += $row['balance'];
			}
		}

		$customerlist['total'] = sizeof($customerlist);
		$customerlist['state'] = $state;
		$customerlist['order'] = $order;
		$customerlist['direction'] = $direction;
		$customerlist['below']= $below;
		$customerlist['over']= $over;

		return $customerlist;
	}

	function GetCustomerNodes($id, $count=NULL)
	{
		if($result = $this->DB->GetAll('SELECT id, name, mac, ipaddr, 
				inet_ntoa(ipaddr) AS ip, ipaddr_pub, 
				inet_ntoa(ipaddr_pub) AS ip_pub, passwd, access, 
				warning, info, ownerid, location, lastonline,
				(SELECT COUNT(*) FROM nodegroupassignments
					WHERE nodeid = nodes.id) AS gcount 
				FROM nodes WHERE ownerid=?
				ORDER BY name ASC '.($count ? 'LIMIT '.$count : ''), array($id)))
		{
			// assign network(s) to node record
			$networks = (array) $this->GetNetworks();
			
			foreach($result as $idx => $node)
			{
				$ids[$node['id']] = $idx;
				$delta = time()-$node['lastonline'];
				if($delta>$this->CONFIG['phpui']['lastonline_limit'])
				{
					if($delta>59)
						$result[$idx]['lastonlinedate'] = trans('$0 ago ($1)', uptimef($delta), date('Y/m/d, H:i',$node['lastonline']));
					else
						$result[$idx]['lastonlinedate'] = '('.date('Y/m/d, H:i',$node['lastonline']).')';
				}
				else
					$result[$idx]['lastonlinedate'] = trans('online');
					
				foreach($networks as $net)
					if(isipin($node['ip'], $net['address'], $net['mask']))
					{
						$result[$idx]['network'] = $net;
						break;
					}

				if($node['ipaddr_pub'])
					foreach($networks as $net)
						if(isipin($node['ip_pub'], $net['address'], $net['mask']))
						{
							$result[$idx]['network_pub'] = $net;
							break;
						}
			}

			// get EtherWerX channels
			if (chkconfig($this->CONFIG['phpui']['ewx_support']))
			{
				$channels = $this->DB->GetAllByKey('SELECT nodeid, channelid, c.name
					FROM ewx_stm_nodes
					LEFT JOIN ewx_channels c ON (c.id = channelid)
					WHERE channelid != 0
						AND nodeid IN ('.implode(',', $ids).')', 'nodeid');

				if ($channels) foreach($channels as $channel) {
					$result[$ids[$channel['nodeid']]]['channelid'] = $channel['channelid'];
					$result[$ids[$channel['nodeid']]]['channelname'] = $channel['name'];
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

	function GetCustomerBalanceList($id, $totime=NULL, $direction='ASC')
	{
		($direction == 'ASC' || $direction == 'asc') ? $direction == 'ASC' : $direction == 'DESC';

		$saldolist = array();
		
		if($tslist = $this->DB->GetAll('SELECT cash.id AS id, time, cash.type AS type, 
					cash.value AS value, taxes.label AS tax, cash.customerid AS customerid, 
					comment, docid, users.name AS username,
					documents.type AS doctype, documents.closed AS closed
					FROM cash
					LEFT JOIN users ON users.id = cash.userid
					LEFT JOIN documents ON documents.id = docid
					LEFT JOIN taxes ON cash.taxid = taxes.id
					WHERE cash.customerid=? '
					.($totime ? ' AND time <= '.$totime : '')
					.' ORDER BY time ' . $direction, array($id)))
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
		$result = $this->DB->GetRow('SELECT COUNT(id) AS total,
				COUNT(CASE WHEN status = 3 THEN 1 END) AS connected,
				COUNT(CASE WHEN status = 2 THEN 1 END) AS awaiting,
				COUNT(CASE WHEN status = 1 THEN 1 END) AS interested
				FROM customersview WHERE deleted=0');

		$tmp = $this->DB->GetRow('SELECT SUM(a.value)*-1 AS debtvalue, COUNT(*) AS debt 
				FROM (SELECT SUM(value) AS value 
				    FROM cash 
				    LEFT JOIN customersview ON (customerid = customersview.id) 
				    WHERE deleted = 0 
				    GROUP BY customerid 
				    HAVING SUM(value) < 0
				) a');

		if (is_array($tmp))
			$result = array_merge($result, $tmp);

		return $result;
	}

	/*
	 * Customer groups
	*/

	function CustomergroupWithCustomerGet($id)
	{
		return $this->DB->GetOne('SELECT COUNT(*) FROM customerassignments
				WHERE customergroupid = ?', array($id));
	}

	function CustomergroupAdd($customergroupdata)
	{
		if($this->DB->Execute('INSERT INTO customergroups (name, description) VALUES (?, ?)', 
				array($customergroupdata['name'], $customergroupdata['description'])))
			return $this->DB->GetOne('SELECT id FROM customergroups WHERE name=?', 
				array($customergroupdata['name']));
		else
			return FALSE;
	}

	function CustomergroupUpdate($customergroupdata)
	{
		return $this->DB->Execute('UPDATE customergroups SET name=?, description=? 
				WHERE id=?', 
				array($customergroupdata['name'], 
					$customergroupdata['description'], 
					$customergroupdata['id']
				));
	}

	function CustomergroupDelete($id)
	{
		 if (!$this->CustomergroupWithCustomerGet($id))
		 {
			$this->DB->BeginTrans();
			$this->DB->Execute('DELETE FROM customergroups WHERE id=?', array($id));
			$this->DB->Execute('DELETE FROM excludedgroups WHERE customergroupid=?', array($id));
			$this->DB->CommitTrans();
			return TRUE;
		 } 
		 else
			return FALSE;
	}

	function CustomergroupExists($id)
	{
		return ($this->DB->GetOne('SELECT id FROM customergroups WHERE id=?', array($id)) ? TRUE : FALSE);
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
		return $this->DB->GetAll('SELECT g.id, g.name, g.description 
				FROM customergroups g
				WHERE NOT EXISTS (
					SELECT 1 FROM excludedgroups 
					WHERE userid = lms_current_user() AND customergroupid = g.id) 
				ORDER BY g.name ASC');
	}

	function CustomergroupGet($id, $network=NULL)
	{
		if($network)
			$net = $this->GetNetworkParams($network);

		$result = $this->DB->GetRow('SELECT id, name, description 
			FROM customergroups WHERE id=?', array($id));

		$result['customers'] = $this->DB->GetAll('SELECT c.id AS id,'
			.$this->DB->Concat('c.lastname',"' '",'c.name').' AS customername 
			FROM customerassignments, customers c '
			.($network ? 'LEFT JOIN nodes ON c.id = nodes.ownerid ' : '')
			.'WHERE c.id = customerid AND customergroupid = ? '
			.($network ? 'AND ((ipaddr > '.$net['address'].' AND ipaddr < '.$net['broadcast'].') OR
			(ipaddr_pub > '.$net['address'].' AND ipaddr_pub < '.$net['broadcast'].')) ' : '')
			.' GROUP BY c.id, c.lastname, c.name ORDER BY c.lastname, c.name', array($id));

		$result['customerscount'] = sizeof($result['customers']);
		$result['count'] = $network ? $this->CustomergroupWithCustomerGet($id) : $result['customerscount'];

		return $result;
	}

	function CustomergroupGetList()
	{
		if($customergrouplist = $this->DB->GetAll('SELECT id, name, description,
				(SELECT COUNT(*)
					FROM customerassignments 
					WHERE customergroupid = customergroups.id
				) AS customerscount
				FROM customergroups ORDER BY name ASC'))
		{
			$totalcount = 0;

			foreach($customergrouplist as $idx => $row)
			{
				$totalcount += $row['customerscount'];
			}

			$customergrouplist['total'] = sizeof($customergrouplist);
			$customergrouplist['totalcount'] = $totalcount;
		}

		return $customergrouplist;
	}

	function CustomergroupGetForCustomer($id)
	{
		return $this->DB->GetAll('SELECT customergroups.id AS id, name, description 
			    FROM customergroups, customerassignments 
			    WHERE customergroups.id=customergroupid AND customerid=? 
			    ORDER BY name ASC', array($id));
	}

	function GetGroupNamesWithoutCustomer($customerid)
	{
		return $this->DB->GetAll('SELECT customergroups.id AS id, name, customerid
			FROM customergroups 
			LEFT JOIN customerassignments ON (customergroups.id=customergroupid AND customerid = ?)
			GROUP BY customergroups.id, name, customerid 
			HAVING customerid IS NULL ORDER BY name', 
			array($customerid));
	}

	function CustomerassignmentGetForCustomer($id)
	{
		return $this->DB->GetAll('SELECT customerassignments.id AS id, customergroupid, customerid 
			FROM customerassignments, customergroups 
			WHERE customerid=? AND customergroups.id = customergroupid 
			ORDER BY customergroupid ASC', array($id));
	}

	function CustomerassignmentDelete($customerassignmentdata)
	{
		return $this->DB->Execute('DELETE FROM customerassignments 
			WHERE customergroupid=? AND customerid=?', 
			array($customerassignmentdata['customergroupid'], 
				$customerassignmentdata['customerid']));
	}

	function CustomerassignmentAdd($customerassignmentdata)
	{
		return $this->DB->Execute('INSERT INTO customerassignments (customergroupid, customerid) VALUES (?, ?)',
			array($customerassignmentdata['customergroupid'], 
				$customerassignmentdata['customerid']));
	}

	function CustomerassignmentExist($groupid, $customerid)
	{
		return $this->DB->GetOne('SELECT 1 FROM customerassignments WHERE customergroupid=? AND customerid=?', 
			array($groupid, $customerid));
	}

	function GetCustomerWithoutGroupNames($groupid, $network=NULL)
	{
		if($network)
			$net = $this->GetNetworkParams($network);

		return $this->DB->GetAll('SELECT c.id AS id, '.$this->DB->Concat('c.lastname',"' '",'c.name').' AS customername
			FROM customersview c '
			.($network ? 'LEFT JOIN nodes ON (c.id = nodes.ownerid) ' : '')
			.'WHERE c.deleted = 0 AND c.id NOT IN (
				SELECT customerid FROM customerassignments WHERE customergroupid = ?) '
			.($network ? 'AND ((ipaddr > '.$net['address'].' AND ipaddr < '.$net['broadcast'].') OR (ipaddr_pub > '
				.$net['address'].' AND ipaddr_pub < '.$net['broadcast'].')) ' : '')
			.'GROUP BY c.id, c.lastname, c.name
			ORDER BY c.lastname, c.name', array($groupid));
	}

	/*
	 *  Nodes functions
	 */

	function GetNodeOwner($id)
	{
		return $this->DB->GetOne('SELECT ownerid FROM nodes WHERE id=?', array($id));
	}

	function NodeUpdate($nodedata, $deleteassignments=FALSE)
	{
		$this->DB->Execute('UPDATE nodes SET name=UPPER(?), ipaddr_pub=inet_aton(?), 
				ipaddr=inet_aton(?), mac=UPPER(?), passwd=?, netdev=?, moddate=?NOW?, 
				modid=?, access=?, warning=?, ownerid=?, info=?, 
				location=?, chkmac=?, halfduplex=?, linktype=?, port=?, nas=? 
				WHERE id=?', 
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
				    $nodedata['chkmac'],
				    $nodedata['halfduplex'],
				    isset($nodedata['linktype']) ? 1 : 0,
				    isset($nodedata['port']) && $nodedata['netdev'] ? intval($nodedata['port']) : 0,
				    isset($nodedata['nas']) ? $nodedata['nas'] : 0,
				    $nodedata['id']
			    ));
		
		if($deleteassignments)
			$this->DB->Execute('DELETE FROM nodeassignments WHERE nodeid = ?', array($nodedata['id']));
	}

	function DeleteNode($id)
	{
		$this->DB->BeginTrans();
		$this->DB->Execute('DELETE FROM nodes WHERE id = ?', array($id));
		$this->DB->Execute('DELETE FROM nodegroupassignments WHERE nodeid = ?', array($id));
		$this->DB->CommitTrans();
	}

	function GetNodeNameByMAC($mac)
	{
		return $this->DB->GetOne('SELECT name FROM nodes WHERE mac=UPPER(?)', array($mac));
	}

	function GetNodeIDByIP($ipaddr)
	{
		return $this->DB->GetOne('SELECT id FROM nodes WHERE ipaddr=inet_aton(?) OR ipaddr_pub=inet_aton(?)', 
				array($ipaddr,$ipaddr));
	}

	function GetNodeIDByMAC($mac)
	{
		return $this->DB->GetOne('SELECT id FROM nodes WHERE mac=UPPER(?)', array($mac));
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
		if($result = $this->DB->GetRow('SELECT id, name, ownerid, ipaddr, inet_ntoa(ipaddr) AS ip, 
			ipaddr_pub, inet_ntoa(ipaddr_pub) AS ip_pub, mac, passwd, access, 
			warning, creationdate, moddate, creatorid, modid, netdev, lastonline, 
			info, location, chkmac, halfduplex, linktype, port, nas
			FROM nodes WHERE id = ?', array($id)))
		{
			$result['owner'] = $this->GetCustomerName($result['ownerid']);
			$result['createdby'] = $this->GetUserName($result['creatorid']);
			$result['modifiedby'] = $this->GetUserName($result['modid']);
			$result['creationdateh'] = date('Y/m/d, H:i',$result['creationdate']);
			$result['moddateh'] = date('Y/m/d, H:i',$result['moddate']);
			$result['producer'] = get_producer($result['mac']);

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
			
			if($net = $this->DB->GetRow('SELECT id, name FROM networks
				WHERE address = (inet_aton(?) & inet_aton(mask))', array($result['ip'])))
			{
				$result['netid'] = $net['id'];
				$result['netname'] = $net['name'];
			}
			
			return $result;
		} else
			return FALSE;
	}

	function GetNodeList($order='name,asc', $search=NULL, $sqlskey='AND', $network=NULL, $status=NULL, $customergroup=NULL, $nodegroup=NULL)
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
						$searchargs[] = '(inet_ntoa(ipaddr) ?LIKE? '.$this->DB->Escape('%'.trim($value).'%')
							.' OR inet_ntoa(ipaddr_pub) ?LIKE? '.$this->DB->Escape('%'.trim($value).'%').')';
					break;
					case 'name' :
						$searchargs[] = 'nodes.name ?LIKE? '.$this->DB->Escape("%$value%");
					break;
					case 'info' :
						// UPPER here is a postgresql ILIKE bug workaround
						$searchargs[] = 'UPPER(nodes.info) ?LIKE? UPPER('.$this->DB->Escape("%$value%").')';
					break;
					default :
						$searchargs[] = $idx.' ?LIKE? '.$this->DB->Escape("%$value%");
				}
			}
		}

		if(isset($searchargs))
			$searchargs = ' AND ('.implode(' '.$sqlskey.' ',$searchargs).')';

		$totalon = 0; $totaloff = 0;

		if($network)
			$net = $this->GetNetworkParams($network);

		if($nodelist = $this->DB->GetAll('SELECT nodes.id AS id, ipaddr, inet_ntoa(ipaddr) AS ip, ipaddr_pub, 
				inet_ntoa(ipaddr_pub) AS ip_pub, mac, nodes.name AS name, ownerid, access, warning, 
				netdev, lastonline, nodes.info AS info, '
				.$this->DB->Concat('c.lastname',"' '",'c.name').' AS owner
				FROM nodes 
				JOIN customersview c ON (nodes.ownerid = c.id) '
				.($customergroup ? 'JOIN customerassignments ON (customerid = c.id) ' : '')
				.($nodegroup ? 'JOIN nodegroupassignments ON (nodeid = nodes.id) ' : '')
				.' WHERE 1=1 '
				.($network ? ' AND ((ipaddr > '.$net['address'].' AND ipaddr < '.$net['broadcast'].') OR ( ipaddr_pub > '.$net['address'].' AND ipaddr_pub < '.$net['broadcast'].'))' : '')
				.($status==1 ? ' AND access = 1' : '') //connected
				.($status==2 ? ' AND access = 0' : '') //disconnected
				.($status==3 ? ' AND lastonline > ?NOW? - '.$this->CONFIG['phpui']['lastonline_limit'] : '') //online
				.($customergroup ? ' AND customergroupid = '.$customergroup : '')
				.($nodegroup ? ' AND nodegroupid = '.$nodegroup : '')
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

	function NodeSet($id, $access=-1)
	{
		if($access != -1)
		{
			if($access)
				return $this->DB->Execute('UPDATE nodes SET access = 1 WHERE id = ?
					AND EXISTS (SELECT 1 FROM customers WHERE id = ownerid 
						AND status = 3)', array($id));
			else
				return $this->DB->Execute('UPDATE nodes SET access = 0 WHERE id = ?',
					array($id));
		}
		elseif($this->DB->GetOne('SELECT access FROM nodes WHERE id = ?', array($id)) == 1 )
			return $this->DB->Execute('UPDATE nodes SET access=0 WHERE id = ?', array($id));
		else
			return $this->DB->Execute('UPDATE nodes SET access = 1 WHERE id = ?
					AND EXISTS (SELECT 1 FROM customers WHERE id = ownerid 
						AND status = 3)', array($id));
	}

	function NodeSetU($id,$access=FALSE)
	{
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
		if($warning)
			return $this->DB->Execute('UPDATE nodes SET warning=1 WHERE id=?', array($id));
		else
			return $this->DB->Execute('UPDATE nodes SET warning=0 WHERE id=?', array($id));
	}

	function NodeSwitchWarn($id)
	{
		return $this->DB->Execute('UPDATE nodes 
			SET warning = (CASE warning WHEN 0 THEN 1 ELSE 0 END)
			WHERE id = ?', array($id));
	}

	function NodeSetWarnU($id,$warning=FALSE)
	{
		if($warning)
			return $this->DB->Execute('UPDATE nodes SET warning=1 WHERE ownerid=?', array($id));
		else
			return $this->DB->Execute('UPDATE nodes SET warning=0 WHERE ownerid=?', array($id));
	}

	function IPSetU($netdev, $access=FALSE)
	{
		if($access)
			return $this->DB->Execute('UPDATE nodes SET access=1 WHERE netdev=? AND ownerid=0', array($netdev));
		else
			return $this->DB->Execute('UPDATE nodes SET access=0 WHERE netdev=? AND ownerid=0', array($netdev));
	}

	function NodeAdd($nodedata)
	{
		if($this->DB->Execute('INSERT INTO nodes (name, mac, ipaddr, ipaddr_pub, ownerid, 
			passwd, creatorid, creationdate, access, warning, info, netdev, 
			linktype, port, location, chkmac, halfduplex, nas) 
			VALUES (?, ?, inet_aton(?),inet_aton(?), ?, ?, ?, 
			?NOW?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
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
				isset($nodedata['linktype']) ? 1 : 0,
				isset($nodedata['port']) && $nodedata['netdev'] ? intval($nodedata['port']) : 0,
				$nodedata['location'],
				$nodedata['chkmac'],
				$nodedata['halfduplex'],
				isset($nodedata['nas']) ? $nodedata['nas'] : 0,
				)))
		{
			$id = $this->DB->GetLastInsertID('nodes');
			
			// EtherWerX support (devices have some limits)
			// We must to replace big ID with smaller (first free)
			if($id > 99999 && chkconfig($this->CONFIG['phpui']['ewx_support']))
			{
				$this->DB->BeginTrans();
				$this->DB->LockTables('nodes');
				
				if($newid = $this->DB->GetOne('SELECT n.id + 1 FROM nodes n 
						LEFT OUTER JOIN nodes n2 ON n.id + 1 = n2.id
						WHERE n2.id IS NULL AND n.id <= 99999
						ORDER BY n.id ASC LIMIT 1'))
				{
					$this->DB->Execute('UPDATE nodes SET id = ? WHERE id = ?', array($newid, $id));
					$id = $newid;
				}
				
				$this->DB->UnLockTables();
				$this->DB->CommitTrans();
			}

			return $id;
		}

		return FALSE;
	}

	function NodeExists($id)
	{
		return ($this->DB->GetOne('SELECT n.id FROM nodes n
			WHERE n.id = ? AND n.ownerid > 0 AND NOT EXISTS (
		        	SELECT 1 FROM customerassignments a
			        JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
				WHERE e.userid = lms_current_user() AND a.customerid = n.ownerid)'
			, array($id)) ? TRUE : FALSE);
	}

	function NodeStats()
	{
		$result = $this->DB->GetRow('SELECT COUNT(CASE WHEN access=1 THEN 1 END) AS connected, 
				COUNT(CASE WHEN access=0 THEN 1 END) AS disconnected,
				COUNT(CASE WHEN ?NOW?-lastonline < ? THEN 1 END) AS online
				FROM nodes WHERE ownerid > 0',
				array($this->CONFIG['phpui']['lastonline_limit']));
		
		$result['total'] = $result['connected'] + $result['disconnected'];
		return $result;
	}

	function GetNodeGroupNames()
	{
		return $this->DB->GetAllByKey('SELECT id, name, description FROM nodegroups
				ORDER BY name ASC', 'id');
	}

	function GetNodeGroupNamesByNode($nodeid)
	{
		return $this->DB->GetAllByKey('SELECT id, name, description FROM nodegroups
				WHERE id IN (SELECT nodegroupid FROM nodegroupassignments
					WHERE nodeid = ?)
				ORDER BY name', 'id', array($nodeid));
	}

	function GetNodeGroupNamesWithoutNode($nodeid)
	{
		return $this->DB->GetAllByKey('SELECT id, name FROM nodegroups
				WHERE id NOT IN (SELECT nodegroupid FROM nodegroupassignments
					WHERE nodeid = ?)
				ORDER BY name', 'id', array($nodeid));
	}

	function GetNodesWithoutGroup($groupid, $network=NULL)
	{
		if($network)
			$net = $this->GetNetworkParams($network);

		return $this->DB->GetAll('SELECT n.id AS id, n.name AS nodename, a.nodeid
			FROM nodes n
			JOIN customersview c ON (n.ownerid = c.id)
			LEFT JOIN nodegroupassignments a ON (n.id = a.nodeid AND a.nodegroupid = ?) 
			WHERE a.nodeid IS NULL '
			.($network ? 
				' AND ((ipaddr > '.$net['address'].' AND ipaddr < '.$net['broadcast'].') 
					OR (ipaddr_pub > '.$net['address'].' AND ipaddr_pub < '.$net['broadcast'].')) ' : '')
			.' ORDER BY nodename', array($groupid));
	}

	function GetNodesWithGroup($groupid, $network=NULL)
	{
		if($network)
			$net = $this->GetNetworkParams($network);

		return $this->DB->GetAll('SELECT n.id AS id, n.name AS nodename, a.nodeid
			FROM nodes n
			JOIN customersview c ON (n.ownerid = c.id)
			JOIN nodegroupassignments a ON (n.id = a.nodeid) 
			WHERE a.nodegroupid = ?'
			.($network ? 
				' AND ((ipaddr > '.$net['address'].' AND ipaddr < '.$net['broadcast'].') 
					OR (ipaddr_pub > '.$net['address'].' AND ipaddr_pub < '.$net['broadcast'].')) ' : '')
			.' ORDER BY nodename', array($groupid));
	}

	function GetNodeGroup($id, $network=NULL)
	{
		$result = $this->DB->GetRow('SELECT id, name, description, prio,
				(SELECT COUNT(*) FROM nodegroupassignments 
					WHERE nodegroupid = nodegroups.id) AS count
				FROM nodegroups WHERE id = ?', array($id));

		$result['nodes'] = $this->GetNodesWithGroup($id, $network);
		$result['nodescount'] = sizeof($result['nodes']);

		return $result;
	}

	function CompactNodeGroups()
	{
	        $this->DB->BeginTrans();
		$this->DB->LockTables('nodegroups');
	        if($nodegroups = $this->DB->GetAll('SELECT id, prio FROM nodegroups ORDER BY prio ASC'))
	        {
			$prio = 1;
			foreach($nodegroups as $idx => $row)
			{
				$this->DB->Execute('UPDATE nodegroups SET prio=? WHERE id=?', array($prio, $row['id']));
				$prio++;
			}
		}
		$this->DB->UnLockTables();
		$this->DB->CommitTrans();
	}

	function GetNetDevLinkedNodes($id)
	{
		return $this->DB->GetAll('SELECT nodes.id AS id, nodes.name AS name, linktype, ipaddr, 
			inet_ntoa(ipaddr) AS ip, ipaddr_pub, inet_ntoa(ipaddr_pub) AS ip_pub, 
			netdev, port, ownerid,
			'.$this->DB->Concat('c.lastname',"' '",'c.name').' AS owner 
			FROM nodes, customersview c 
			WHERE ownerid = c.id AND netdev = ? AND ownerid > 0 
			ORDER BY nodes.name ASC', array($id));
	}

	function NetDevLinkNode($id, $devid, $type=0, $port=0)
	{
		return $this->DB->Execute('UPDATE nodes SET netdev=?, linktype=?, port=?
			 WHERE id=?', 
			 array($devid,
				intval($type),
				intval($port),
				$id
			));
	}

	function SetNetDevLinkType($dev1, $dev2, $type=0)
	{
		return $this->DB->Execute('UPDATE netlinks SET type=? WHERE (src=? AND dst=?) OR (dst=? AND src=?)', array($type, $dev1, $dev2, $dev1, $dev2));
	}

	function SetNodeLinkType($node, $type=0)
	{
		return $this->DB->Execute('UPDATE nodes SET linktype=? WHERE id=?', array($type, $node));
	}

	/*
	 *  Tarrifs and finances
	*/

	function GetCustomerTariffsValue($id)
	{
		return $this->DB->GetOne('SELECT sum(tariffs.value) FROM assignments, tariffs 
				WHERE tariffid = tariffs.id AND customerid=? AND suspended = 0 
				AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0)', 
				array($id));
	}

	function GetCustomerAssignments($id, $show_expired=false)
	{
		$now = mktime(0, 0, 0, date('n'), date('d'), date('Y'));
		
		if($assignments = $this->DB->GetAll('SELECT assignments.id AS id, tariffid,
			assignments.customerid, period, at, suspended, uprate, upceil, downceil, downrate,
			invoice, settlement, datefrom, dateto, discount, liabilityid, 
			(CASE WHEN tariffs.value IS NULL THEN liabilities.value ELSE tariffs.value END) AS value,
			(CASE WHEN tariffs.name IS NULL THEN liabilities.name ELSE tariffs.name END) AS name
			FROM assignments 
			LEFT JOIN tariffs ON (tariffid=tariffs.id) 
			LEFT JOIN liabilities ON (liabilityid=liabilities.id) 
			WHERE assignments.customerid=? '
			.(!$show_expired ? 'AND (dateto > '.$now.' OR dateto = 0) AND (liabilityid = 0 OR (liabilityid != 0 AND (at >= '.$now.' OR at < 365)))' : '')
			.' ORDER BY datefrom, value', array($id)))
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
					case HALFYEARLY:
						$row['at'] = sprintf('%02d/%02d', $row['at']%100, $row['at']/100+1);
						$row['payday'] = trans('half-yearly ($0)', $row['at']);
						$row['period'] = trans('half-yearly');
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
		$this->DB->BeginTrans();
		
		if($lid = $this->DB->GetOne('SELECT liabilityid FROM assignments WHERE id=?', array($id)))
		{
			$this->DB->Execute('DELETE FROM liabilities WHERE id=?', array($lid));
		}
		$this->DB->Execute('DELETE FROM assignments WHERE id=?', array($id));
		
		$this->DB->CommitTrans();
	}

	function AddAssignment($assignmentdata)
	{
		if(isset($assignmentdata['value']) && $assignmentdata['value']!=0)
		{
			$this->DB->Execute('INSERT INTO liabilities (name, value, taxid, prodid) 
					    VALUES (?, ?, ?, ?)', 
					    array($assignmentdata['name'],
						    $assignmentdata['value'],
						    intval($assignmentdata['taxid']),
						    $assignmentdata['prodid']
					    ));
			$lid = $this->DB->GetLastInsertID('liabilities');
		}
		
		$this->DB->Execute('INSERT INTO assignments (tariffid, customerid, period, at, invoice, 
					    settlement, datefrom, dateto, discount, liabilityid) 
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
		return $this->DB->Execute('UPDATE assignments SET suspended=? WHERE id=?', array($suspend ? 1 : 0, $id));
	}

	function AddInvoice($invoice)
	{
		$cdate = $invoice['invoice']['cdate'] ? $invoice['invoice']['cdate'] : time();
		$number = $invoice['invoice']['number'];
		$type = $invoice['invoice']['type'];
		
		$this->DB->Execute('INSERT INTO documents (number, numberplanid, type,
			cdate, paytime, paytype, userid, customerid, name, address, 
			ten, ssn, zip, city, countryid, divisionid)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
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
				$invoice['customer']['city'],
				$invoice['customer']['countryid'],
				$invoice['customer']['divisionid'],
			));
		$iid = $this->DB->GetLastInsertID('documents');

		$itemid=0;
		foreach($invoice['contents'] as $idx => $item)
		{
			$itemid++;
			$item['valuebrutto'] = str_replace(',','.',$item['valuebrutto']);
			$item['count'] = str_replace(',','.',$item['count']);
			$item['discount'] = str_replace(',','.',$item['discount']);
			$item['taxid'] = isset($item['taxid']) ? $item['taxid'] : 0;

			$this->DB->Execute('INSERT INTO invoicecontents (docid, itemid,
				value, taxid, prodid, content, count, discount, description, tariffid) 
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
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
					$item['tariffid']
			));

			$this->AddBalance(array(
				'time' => $cdate,
				'value' => $item['valuebrutto']*$item['count']*-1,
				'taxid' => $item['taxid'],
				'customerid' => $invoice['customer']['id'],
				'comment' => $item['name'],
				'docid' => $iid,
				'itemid'=>$itemid
			));
		}

		return $iid;
	}

	function InvoiceUpdate($invoice)
	{
		$cdate = $invoice['invoice']['cdate'] ? $invoice['invoice']['cdate'] : time();
		$iid = $invoice['invoice']['id'];

		$this->DB->BeginTrans();
		
		$this->DB->Execute('UPDATE documents SET cdate = ?, paytime = ?, paytype = ?, customerid = ?,
				name = ?, address = ?, ten = ?, ssn = ?, zip = ?, city = ?, divisionid = ?
				WHERE id = ?',
				array($cdate, 
					$invoice['invoice']['paytime'],
					$invoice['invoice']['paytype'],
					$invoice['customer']['id'],
					$invoice['customer']['customername'],
					$invoice['customer']['address'],
					$invoice['customer']['ten'],
					$invoice['customer']['ssn'],
					$invoice['customer']['zip'],
					$invoice['customer']['city'],
					$invoice['customer']['divisionid'],
					$iid
				));

		$this->DB->Execute('DELETE FROM invoicecontents WHERE docid = ?', array($iid));
		$this->DB->Execute('DELETE FROM cash WHERE docid = ?', array($iid));

		$itemid=0;
		foreach($invoice['contents'] as $idx => $item)
		{
			$itemid++;

			$this->DB->Execute('INSERT INTO invoicecontents (docid, itemid, value, 
				taxid, prodid, content, count, discount, description, tariffid) 
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
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
					$item['tariffid']
			));
			
			$this->AddBalance(array(
				'time' => $cdate, 
				'value' => $item['valuebrutto']*$item['count']*-1, 
				'taxid' => $item['taxid'], 
				'customerid' => $invoice['customer']['id'], 
				'comment' => $item['name'], 
				'docid' => $iid, 
				'itemid'=>$itemid
			));
		}
		
		$this->DB->CommitTrans();
	}

	function InvoiceDelete($invoiceid)
	{
		$this->DB->BeginTrans();
		$this->DB->Execute('DELETE FROM documents WHERE id = ?', array($invoiceid));
		$this->DB->Execute('DELETE FROM invoicecontents WHERE docid = ?', array($invoiceid));
		$this->DB->Execute('DELETE FROM cash WHERE docid = ?', array($invoiceid));
		$this->DB->CommitTrans();
	}

	function InvoiceContentDelete($invoiceid, $itemid=0)
	{
		if($itemid)
		{
			$this->DB->BeginTrans();
			$this->DB->Execute('DELETE FROM invoicecontents WHERE docid=? AND itemid=?', array($invoiceid, $itemid));

			if(!$this->DB->GetOne('SELECT COUNT(*) FROM invoicecontents WHERE docid=?', array($invoiceid)))
			{
				// if that was the last item of invoice contents
				$this->DB->Execute('DELETE FROM documents WHERE id = ?', array($invoiceid));
			}

			$this->DB->Execute('DELETE FROM cash WHERE docid = ? AND itemid = ?', array($invoiceid, $itemid));
			$this->DB->CommitTrans();
		}
		else
			$this->InvoiceDelete($invoiceid);
	}

	function GetInvoiceContent($invoiceid)
	{
		if($result = $this->DB->GetRow('SELECT d.id, d.number, d.name, d.customerid,
				d.userid, d.address, d.zip, d.city, d.countryid, cn.name AS country,
				d.ten, d.ssn, d.cdate, d.paytime, d.paytype, d.numberplanid,
				d.closed, d.reference, d.reason, d.divisionid, 
				(SELECT name FROM users WHERE id = d.userid) AS user, n.template,
				ds.name AS division_name, ds.shortname AS division_shortname,
				ds.address AS division_address, ds.zip AS division_zip,
				ds.city AS division_city, ds.countryid AS division_countryid, 
				ds.ten AS division_ten, ds.regon AS division_regon, ds.account AS account,
				ds.inv_header AS division_header, ds.inv_footer AS division_footer,
				ds.inv_author AS division_author, ds.inv_cplace AS division_cplace,
				c.pin AS customerpin, c.divisionid AS current_divisionid
				FROM documents d
				JOIN customers c ON (c.id = d.customerid)
				LEFT JOIN countries cn ON (cn.id = d.countryid)
				LEFT JOIN divisions ds ON (ds.id = d.divisionid)
				LEFT JOIN numberplans n ON (d.numberplanid = n.id)
				WHERE d.id = ? AND (d.type = ? OR d.type = ?)', 
				array($invoiceid, DOC_INVOICE, DOC_CNOTE)))
		{
			$result['discount'] = 0;
			$result['totalbase'] = 0;
			$result['totaltax'] = 0;
			$result['total'] = 0;
			
			if($result['reference'])
				$result['invoice'] = $this->GetInvoiceContent($result['reference']);
			
			if(!$result['division_header'])
				$result['division_header'] = $result['division_name']."\n"
					.$result['division_address']."\n".$result['division_zip'].' '.$result['division_city']
					.($result['division_countryid'] && $result['countryid']
						&& $result['division_countryid'] != $result['countryid']
						? "\n".trans($this->GetCountryName($result['division_countryid'])) : '')
					.($result['division_ten'] != '' ? "\n".trans('TEN').' '.$result['division_ten'] : '');
			
			if($result['content'] = $this->DB->GetAll('SELECT invoicecontents.value AS value, 
						itemid, taxid, taxes.value AS taxvalue, taxes.label AS taxlabel, 
						prodid, content, count, invoicecontents.description AS description, 
						tariffid, itemid, discount
						FROM invoicecontents 
						LEFT JOIN taxes ON taxid = taxes.id 
						WHERE docid=? 
						ORDER BY itemid', array($invoiceid))
			)
				foreach($result['content'] as $idx => $row)
				{
					if(isset($result['invoice']))
					{
						$row['value'] += $result['invoice']['content'][$idx]['value'];
						$row['count'] += $result['invoice']['content'][$idx]['count'];
					}
					
					$result['content'][$idx]['basevalue'] = round(($row['value'] / (100 + $row['taxvalue']) * 100),2);
					$result['content'][$idx]['total'] = round($row['value'] * $row['count'], 2);
					$result['content'][$idx]['totalbase'] = round($result['content'][$idx]['total'] / (100 + $row['taxvalue']) * 100, 2);
					$result['content'][$idx]['totaltax'] = round($result['content'][$idx]['total'] - $result['content'][$idx]['totalbase'],2); 
					$result['content'][$idx]['value'] = $row['value'];
					$result['content'][$idx]['count'] = $row['count'];

					if(isset($result['taxest'][$row['taxvalue']]))
					{
						$result['taxest'][$row['taxvalue']]['base'] += $result['content'][$idx]['totalbase'];
						$result['taxest'][$row['taxvalue']]['total'] += $result['content'][$idx]['total'];
						$result['taxest'][$row['taxvalue']]['tax'] += $result['content'][$idx]['totaltax'];
					}
					else
					{
						$result['taxest'][$row['taxvalue']]['base'] = $result['content'][$idx]['totalbase'];
						$result['taxest'][$row['taxvalue']]['total'] = $result['content'][$idx]['total'];
						$result['taxest'][$row['taxvalue']]['tax'] = $result['content'][$idx]['totaltax'];
						$result['taxest'][$row['taxvalue']]['taxlabel'] = $row['taxlabel'];
					}

					$result['totalbase'] += $result['content'][$idx]['totalbase'];
					$result['totaltax'] += $result['content'][$idx]['totaltax'];
					$result['total'] += $result['content'][$idx]['total'];
    
					// for backward compatybility
					$result['taxest'][$row['taxvalue']]['taxvalue'] = $row['taxvalue'];
					$result['content'][$idx]['pkwiu'] = $row['prodid'];
					
					$result['discount'] += $row['discount'];
				}

			$result['pdate'] = $result['cdate'] + ($result['paytime'] * 86400);
			$result['value'] = $result['total'] - (isset($result['invoice']) ? $result['invoice']['value'] : 0);
			
			if($result['value'] < 0)
			{
				$result['value'] = abs($result['value']);
				$result['rebate'] = true;
			}
			$result['valuep'] = round( ($result['value'] - floor($result['value'])) * 100);

			// NOTE: don't waste CPU/mem when printing history is not set:
			if(chkconfig($this->CONFIG['invoices']['print_balance_history']))
			{
				if(isset($this->CONFIG['invoices']['print_balance_history_save']) && chkconfig($this->CONFIG['invoices']['print_balance_history_save']))
					$result['customerbalancelist'] = $this->GetCustomerBalanceList($result['customerid'], $result['cdate']);
				else
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

	function GetNoteContent($id)
	{
		if($result = $this->DB->GetRow('SELECT d.id, d.number, d.name, d.customerid,
				d.userid, d.address, d.zip, d.city, d.countryid, cn.name AS country,
				d.ten, d.ssn, d.cdate, d.numberplanid, d.closed, d.divisionid, d.paytime, 
				(SELECT name FROM users WHERE id = d.userid) AS user, n.template,
				ds.name AS division_name, ds.shortname AS division_shortname,
				ds.address AS division_address, ds.zip AS division_zip,
				ds.city AS division_city, ds.countryid AS division_countryid, 
				ds.ten AS division_ten, ds.regon AS division_regon, ds.account AS account,
				ds.inv_header AS division_header, ds.inv_footer AS division_footer,
				ds.inv_author AS division_author, ds.inv_cplace AS division_cplace,
				c.pin AS customerpin, c.divisionid AS current_divisionid
				FROM documents d
				JOIN customers c ON (c.id = d.customerid)
				LEFT JOIN countries cn ON (cn.id = d.countryid)
				LEFT JOIN divisions ds ON (ds.id = d.divisionid)
				LEFT JOIN numberplans n ON (d.numberplanid = n.id)
				WHERE d.id = ? AND d.type = ?', 
				array($id, DOC_DNOTE)))
		{
			$result['value'] = 0;
			
			if(!$result['division_header'])
				$result['division_header'] = $result['division_name']."\n"
					.$result['division_address']."\n".$result['division_zip'].' '.$result['division_city']
					.($result['division_countryid'] && $result['countryid']
						&& $result['division_countryid'] != $result['countryid']
						? "\n".trans($this->GetCountryName($result['division_countryid'])) : '')
					.($result['division_ten'] != '' ? "\n".trans('TEN').' '.$result['division_ten'] : '');
			
			if($result['content'] = $this->DB->GetAll('SELECT
				value, itemid, description 
				FROM debitnotecontents 
				WHERE docid=? 
				ORDER BY itemid', array($id))
			)
				foreach($result['content'] as $idx => $row)
				{
					$result['content'][$idx]['value'] = $row['value'];
					$result['value'] += $row['value'];
				}

			$result['valuep'] = round( ($result['value'] - floor($result['value'])) * 100);
			$result['pdate'] = $result['cdate'] + ($result['paytime'] * 86400);

			// NOTE: don't waste CPU/mem when printing history is not set:
			if(!empty($this->CONFIG['notes']['print_balance_history']) && chkconfig($this->CONFIG['notes']['print_balance_history']))
			{
				if(isset($this->CONFIG['notes']['print_balance_history_save']) && chkconfig($this->CONFIG['notes']['print_balance_history_save']))
					$result['customerbalancelist'] = $this->GetCustomerBalanceList($result['customerid'], $result['cdate']);
				else
					$result['customerbalancelist'] = $this->GetCustomerBalanceList($result['customerid']);
				$result['customerbalancelistlimit'] = $this->CONFIG['notes']['print_balance_history_limit'];
			}

			return $result;
		}
		else
			return FALSE;
	}

	function GetTariffIDByName($name)
	{
		return $this->DB->GetOne('SELECT id FROM tariffs WHERE name=?', array($name));
	}

	function TariffAdd($tariff)
	{
		$result = $this->DB->Execute('INSERT INTO tariffs (name, description, value, 
				taxid, prodid, uprate, downrate, upceil, downceil, climit, 
				plimit, uprate_n, downrate_n, upceil_n, downceil_n, climit_n, 
				plimit_n, dlimit, type, sh_limit, www_limit, mail_limit, sql_limit,
				ftp_limit, quota_sh_limit, quota_www_limit, quota_mail_limit,
				quota_sql_limit, quota_ftp_limit, domain_limit, alias_limit)
				VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
				array(
					$tariff['name'],
					$tariff['description'],
					$tariff['value'],
					$tariff['taxid'],
					$tariff['prodid'],
					$tariff['uprate'],
					$tariff['downrate'],
					$tariff['upceil'],
					$tariff['downceil'],
					$tariff['climit'],
					$tariff['plimit'],
					$tariff['uprate_n'],
					$tariff['downrate_n'],
					$tariff['upceil_n'],
					$tariff['downceil_n'],
					$tariff['climit_n'],
					$tariff['plimit_n'],
					$tariff['dlimit'],
					$tariff['type'],
					$tariff['sh_limit'],
					$tariff['www_limit'],
					$tariff['mail_limit'],
					$tariff['sql_limit'],
					$tariff['ftp_limit'],
					$tariff['quota_sh_limit'],
					$tariff['quota_www_limit'],
					$tariff['quota_mail_limit'],
					$tariff['quota_sql_limit'],
					$tariff['quota_ftp_limit'],
					$tariff['domain_limit'],
					$tariff['alias_limit'],
				));
		if ($result)
			return $this->DB->GetLastInsertID('tariffs');
		else
			return FALSE;
	}

	function TariffUpdate($tariff)
	{
		return $this->DB->Execute('UPDATE tariffs SET name=?, description=?, value=?, 
				taxid=?, prodid=?, uprate=?, downrate=?, upceil=?, downceil=?, 
				climit=?, plimit=?, uprate_n=?, downrate_n=?, upceil_n=?, downceil_n=?, 
				climit_n=?, plimit_n=?, dlimit=?, sh_limit=?, www_limit=?, mail_limit=?,
				sql_limit=?, ftp_limit=?, quota_sh_limit=?, quota_www_limit=?, 
				quota_mail_limit=?, quota_sql_limit=?, quota_ftp_limit=?, 
				domain_limit=?, alias_limit=?, type=? WHERE id=?', 
				array($tariff['name'], 
					$tariff['description'],
					$tariff['value'],
					$tariff['taxid'],
					$tariff['prodid'],
					$tariff['uprate'],
					$tariff['downrate'],
					$tariff['upceil'],
					$tariff['downceil'],
					$tariff['climit'],
					$tariff['plimit'],
					$tariff['uprate_n'],
					$tariff['downrate_n'],
					$tariff['upceil_n'],
					$tariff['downceil_n'],
					$tariff['climit_n'],
					$tariff['plimit_n'],
					$tariff['dlimit'],
					$tariff['sh_limit'],
					$tariff['www_limit'],
					$tariff['mail_limit'],
					$tariff['sql_limit'],
					$tariff['ftp_limit'],
					$tariff['quota_sh_limit'],
					$tariff['quota_www_limit'],
					$tariff['quota_mail_limit'],
					$tariff['quota_sql_limit'],
					$tariff['quota_ftp_limit'],
					$tariff['domain_limit'],
					$tariff['alias_limit'],
					$tariff['type'],
					$tariff['id']
				));
	}

	function TariffDelete($id)
	{
		return $this->DB->Execute('DELETE FROM tariffs WHERE id=?', array($id));
	}

	function GetTariff($id, $network=NULL)
	{
		if ($network)
			$net = $this->GetNetworkParams($network);

		$result = $this->DB->GetRow('SELECT t.*, taxes.label AS tax, taxes.value AS taxvalue
			FROM tariffs t
			LEFT JOIN taxes ON (t.taxid = taxes.id)
			WHERE t.id=?', array($id));

		$result['customers'] = $this->DB->GetAll('SELECT c.id AS id, COUNT(c.id) AS cnt, '
			.$this->DB->Concat('c.lastname',"' '",'c.name').' AS customername '
			.($network ? ', COUNT(nodes.id) AS nodescount ' : '')
			.'FROM assignments, customersview c '
			.($network ? 'LEFT JOIN nodes ON (c.id = nodes.ownerid) ' : '')
			.'WHERE c.id = customerid AND deleted = 0 AND tariffid = ? '
			.($network ? 'AND ((ipaddr > '.$net['address'].' AND ipaddr < '.$net['broadcast'].') OR (ipaddr_pub > '
			.$net['address'].' AND ipaddr_pub < '.$net['broadcast'].')) ' : '')
			.'GROUP BY c.id, c.lastname, c.name ORDER BY c.lastname, c.name', array($id));

		$unactive = $this->DB->GetRow('SELECT COUNT(*) AS count,
                        SUM(CASE a.period
				WHEN '.DAILY.' THEN t.value*30
				WHEN '.WEEKLY.' THEN t.value*4
				WHEN '.MONTHLY.' THEN t.value
				WHEN '.QUARTERLY.' THEN t.value/3
				WHEN '.HALFYEARLY.' THEN t.value/6
				WHEN '.YEARLY.' THEN t.value/12 END) AS value
			FROM assignments a
			JOIN tariffs t ON (t.id = a.tariffid)
			WHERE t.id = ? AND (
			            a.suspended = 1
			            OR a.datefrom > ?NOW?
			            OR (a.dateto <= ?NOW? AND a.dateto != 0)
			            OR EXISTS (
			                    SELECT 1 FROM assignments b
					    WHERE b.customerid = a.customerid
						    AND liabilityid = 0 AND tariffid = 0
						    AND (b.datefrom <= ?NOW? OR b.datefrom = 0)
						    AND (b.dateto > ?NOW? OR b.dateto = 0)
				    )
			)', array($id));
		
		$all = $this->DB->GetRow('SELECT COUNT(*) AS count, 
			SUM(CASE a.period 
				WHEN '.DAILY.' THEN t.value*30 
				WHEN '.WEEKLY.' THEN t.value*4 
				WHEN '.MONTHLY.' THEN t.value 
				WHEN '.QUARTERLY.' THEN t.value/3 
				WHEN '.HALFYEARLY.' THEN t.value/6 
				WHEN '.YEARLY.' THEN t.value/12 END) AS value
			FROM assignments a
			JOIN tariffs t ON (t.id = a.tariffid)
			WHERE tariffid = ?', array($id));

		// count of all customers with that tariff
		$result['customerscount'] = sizeof($result['customers']);
		// count of all assignments
		$result['count'] = $all['count'];
		// count of 'active' assignments
		$result['activecount'] =  $all['count'] - $unactive['count'];
		// avg monthly income (without unactive assignments)
		$result['totalval'] = $all['value'] - $unactive['value'];

		$result['rows'] = ceil($result['customerscount']/2);
		return $result;
	}

	function GetTariffs()
	{
		return $this->DB->GetAll('SELECT t.id, t.name, t.value, uprate, taxid, prodid,
				downrate, upceil, downceil, climit, plimit, taxes.value AS taxvalue, 
				taxes.label AS tax
				FROM tariffs t 
				LEFT JOIN taxes ON t.taxid = taxes.id
				ORDER BY t.value DESC');
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
			$this->DB->Execute('DELETE FROM receiptcontents WHERE docid=?', array($docid));
			$this->DB->Execute('DELETE FROM documents WHERE id = ?', array($docid));
			$this->DB->Execute('DELETE FROM cash WHERE docid = ?', array($docid));
		}
	}

	function DebitNoteContentDelete($docid, $itemid=0)
	{
		if($itemid)
		{
			$this->DB->Execute('DELETE FROM debitnotecontents WHERE docid=? AND itemid=?', array($docid, $itemid));

			if(!$this->DB->GetOne('SELECT COUNT(*) FROM debitnotecontents WHERE docid=?', array($docid)))
			{
				// if that was the last item of debit note contents
				$this->DB->Execute('DELETE FROM documents WHERE id = ?', array($docid));
			}
			$this->DB->Execute('DELETE FROM cash WHERE docid = ? AND itemid = ?', array($docid, $itemid));
		}
		else
		{
			$this->DB->Execute('DELETE FROM debitnotecontents WHERE docid=?', array($docid));
			$this->DB->Execute('DELETE FROM documents WHERE id = ?', array($docid));
			$this->DB->Execute('DELETE FROM cash WHERE docid = ?', array($docid));
		}
	}

	function AddBalance($addbalance)
	{
		$addbalance['value'] = str_replace(',','.',round($addbalance['value'],2));

		return $this->DB->Execute('INSERT INTO cash (time, userid, value, type, taxid,
			customerid, comment, docid, itemid, importid, sourceid)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(isset($addbalance['time']) ? $addbalance['time'] : time(),
				isset($addbalance['userid']) ? $addbalance['userid'] : $this->AUTH->id,
				$addbalance['value'],
				isset($addbalance['type']) ? $addbalance['type'] : 0,
				isset($addbalance['taxid']) ? $addbalance['taxid'] : 0,
				$addbalance['customerid'],
				$addbalance['comment'],
				isset($addbalance['docid']) ? $addbalance['docid'] : 0,
				isset($addbalance['itemid']) ? $addbalance['itemid'] : 0,
				!empty($addbalance['importid']) ? $addbalance['importid'] : NULL,
				!empty($addbalance['sourceid']) ? $addbalance['sourceid'] : NULL,
			));
	}

	function DelBalance($id)
	{
		$row = $this->DB->GetRow('SELECT docid, itemid, documents.type AS doctype
					FROM cash
					LEFT JOIN documents ON (docid = documents.id)
					WHERE cash.id=?', array($id));

		if($row['doctype']==DOC_INVOICE || $row['doctype']==DOC_CNOTE)
			$this->InvoiceContentDelete($row['docid'], $row['itemid']);
		elseif($row['doctype']==DOC_RECEIPT)
			$this->ReceiptContentDelete($row['docid'], $row['itemid']);
		elseif($row['doctype']==DOC_DNOTE)
			$this->DebitNoteContentDelete($row['docid'], $row['itemid']);
		else
			$this->DB->Execute('DELETE FROM cash WHERE id=?', array($id));
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
					case HALFYEARLY:
						$row['payday'] = trans('half-yearly ($0)', sprintf('%02d/%02d', $row['at']%100, $row['at']/100+1));
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
			case HALFYEARLY:
				$payment['payday'] = trans('half-yearly ($0)', sprintf('%02d/%02d', $payment['at']%100, $payment['at']/100+1));
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
		return $this->DB->Execute('DELETE FROM payments WHERE id=?', array($id));
	}

	function PaymentUpdate($paymentdata)
	{
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
		$result = array();
		$networks = $this->GetNetworks();
		if($networks)
			foreach($networks as $idx => $network)
			{
				if($res = execute_program('nbtscan','-q -s: '.$network['address'].'/'.$network['prefix']))
				{
					$out = explode("\n", $res);
					foreach($out as $line)
					{
						list($ipaddr,$name,$null,$login,$mac) = explode(':', $line, 5);
						$row['ipaddr'] = trim($ipaddr);
						if($row['ipaddr'])
						{
							$row['name'] = trim($name);
							$row['mac'] = strtoupper(str_replace('-', ':', trim($mac)));
							if($row['mac'] != "00:00:00:00:00:00" && !$this->GetNodeIDByIP($row['ipaddr']))
								$result[] = $row;
						}
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

	function NetworkSet($id, $disabled=-1)
	{
		if($disabled != -1)
		{
			if($disabled == 1)
				return $this->DB->Execute('UPDATE networks SET disabled = 1 WHERE id = ?', 
					array($id));
			else
				return $this->DB->Execute('UPDATE networks SET disabled = 0 WHERE id = ?',
					array($id));
		}
		elseif($this->DB->GetOne('SELECT disabled FROM networks WHERE id = ?', array($id)) == 1 )
			return $this->DB->Execute('UPDATE networks SET disabled = 0 WHERE id = ?', 
					array($id));
		else
			return $this->DB->Execute('UPDATE networks SET disabled = 1 WHERE id = ?', 
					array($id));
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

		if($this->DB->Execute('INSERT INTO networks (name, address, mask, interface, gateway, 
				dns, dns2, domain, wins, dhcpstart, dhcpend, notes) 
				VALUES (?, inet_aton(?), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
				array(strtoupper($netadd['name']),
					$netadd['address'],
					$netadd['mask'],
					strtolower($netadd['interface']),
					$netadd['gateway'],
					$netadd['dns'],
					$netadd['dns2'],
					$netadd['domain'],
					$netadd['wins'],
					$netadd['dhcpstart'],
					$netadd['dhcpend'],
					$netadd['notes']
		)))
			return $this->DB->GetOne('SELECT id FROM networks WHERE address = inet_aton(?)', array($netadd['address']));
		else
			return FALSE;
	}

	function NetworkDelete($id)
	{
		return $this->DB->Execute('DELETE FROM networks WHERE id=?', array($id));
	}

	function GetNetworkName($id)
	{
		return $this->DB->GetOne('SELECT name FROM networks WHERE id=?', array($id));
	}

	function GetNetIDByIP($ipaddr)
	{
		return $this->DB->GetOne('SELECT id FROM networks 
				WHERE address = (inet_aton(?) & inet_aton(mask))', array($ipaddr));
	}

	function GetNetworks($with_disabled=true)
	{
		if ($with_disabled == false)
			return $this->DB->GetAll('SELECT id, name, inet_ntoa(address) AS address, 
				address AS addresslong, mask, mask2prefix(inet_aton(mask)) AS prefix, disabled 
				FROM networks WHERE disabled=0 ORDER BY name');
		else
			return $this->DB->GetAll('SELECT id, name, inet_ntoa(address) AS address, 
				address AS addresslong, mask, mask2prefix(inet_aton(mask)) AS prefix, disabled 
				FROM networks ORDER BY name');
	}

	function GetNetworkParams($id)
	{
		return $this->DB->GetRow('SELECT *, inet_ntoa(address) AS netip, 
			broadcast(address, inet_aton(mask)) AS broadcast
			FROM networks WHERE id = ?', array($id));
	}

	function GetNetworkList()
	{
		if($networks = $this->DB->GetAll('SELECT id, name, inet_ntoa(address) AS address, 
				address AS addresslong, mask, interface, gateway, dns, dns2, 
				domain, wins, dhcpstart, dhcpend,
				mask2prefix(inet_aton(mask)) AS prefix,
				broadcast(address, inet_aton(mask)) AS broadcastlong,
				inet_ntoa(broadcast(address, inet_aton(mask))) AS broadcast,
				pow(2,(32 - mask2prefix(inet_aton(mask)))) AS size, disabled,
				(SELECT COUNT(*) 
					FROM nodes 
					WHERE (ipaddr >= address AND ipaddr <= broadcast(address, inet_aton(mask))) 
						OR (ipaddr_pub >= address AND ipaddr_pub <= broadcast(address, inet_aton(mask)))
				) AS assigned,
				(SELECT COUNT(*) 
					FROM nodes 
					WHERE ((ipaddr >= address AND ipaddr <= broadcast(address, inet_aton(mask))) 
						OR (ipaddr_pub >= address AND ipaddr_pub <= broadcast(address, inet_aton(mask))))
						AND (?NOW? - lastonline < ?)
				) AS online
				FROM networks ORDER BY name', 
				array(intval($this->CONFIG['phpui']['lastonline_limit']))))
		{
			$size = 0; $assigned = 0; $online = 0;

			foreach($networks as $idx => $row)
			{
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
		$ip = ip_long($ip);
		return $this->DB->GetOne('SELECT 1 FROM networks
			WHERE id != ? AND address < ?
			AND broadcast(address, inet_aton(mask)) >'.($checkbroadcast ? '=' : '').' ?', 
			array(intval($ignoreid), $ip, $ip));
	}

	function NetworkOverlaps($network,$mask,$ignorenet=0)
	{
		$cnetaddr = ip_long($network);
		$cbroadcast = ip_long(getbraddr($network, $mask));

		return $this->DB->GetOne('SELECT 1 FROM networks
			WHERE id != ? AND (
				address = ? OR broadcast(address, inet_aton(mask)) = ?
				OR (address > ? AND broadcast(address, inet_aton(mask)) < ?) 
				OR (address < ? AND broadcast(address, inet_aton(mask)) > ?) 
			)', array(intval($ignorenet), 
				$cnetaddr, $cbroadcast,
				$cnetaddr, $cbroadcast,
				$cnetaddr, $cbroadcast
			));
	}

	function NetworkShift($network='0.0.0.0',$mask='0.0.0.0',$shift=0)
	{
		return ($this->DB->Execute('UPDATE nodes SET ipaddr = ipaddr + ? 
				WHERE ipaddr >= inet_aton(?) AND ipaddr <= inet_aton(?)', 
				array($shift, $network, getbraddr($network,$mask)))
			+ $this->DB->Execute('UPDATE nodes SET ipaddr_pub = ipaddr_pub + ? 
				WHERE ipaddr_pub >= inet_aton(?) AND ipaddr_pub <= inet_aton(?)',
				array($shift, $network, getbraddr($network,$mask))));
	}

	function NetworkUpdate($networkdata)
	{
		return $this->DB->Execute('UPDATE networks SET name=?, address=inet_aton(?), 
			mask=?, interface=?, gateway=?, dns=?, dns2=?, domain=?, wins=?, 
			dhcpstart=?, dhcpend=?, notes=? WHERE id=?', 
			array(strtoupper($networkdata['name']),
				$networkdata['address'],
				$networkdata['mask'],
				strtolower($networkdata['interface']),
				$networkdata['gateway'],
				$networkdata['dns'],
				$networkdata['dns2'],
				$networkdata['domain'],
				$networkdata['wins'],
				$networkdata['dhcpstart'],
				$networkdata['dhcpend'],
				$networkdata['notes'],
				$networkdata['id']
			));
	}

	function NetworkCompress($id,$shift=0)
	{
		$nodes = array();
		$network = $this->GetNetworkRecord($id);
		$address = $network['addresslong'] + $shift;
		$broadcast = $network['addresslong'] + $network['size'];
		$dhcpstart = ip2long($network['dhcpstart']);
		$dhcpend = ip2long($network['dhcpend']);

		$specials = array(ip2long($network['gateway']),
//				ip2long($network['wins']),
//				ip2long($network['dns']),
//				ip2long($network['dns2'])
		);

		foreach($network['nodes']['id'] as $idx => $value)
			if($value)
				$nodes[] = $network['nodes']['addresslong'][$idx];

		for($i = $address+1; $i < $broadcast; $i++)
		{
			if(!sizeof($nodes)) break;

			// skip special and dhcp range addresses
			if(in_array($i, $specials) || ($i >= $dhcpstart && $i <= $dhcpend))
				continue;

			$ip = array_shift($nodes);
	
			if($i == $ip) continue;
			
			// don't change assigned special addresses
			if(in_array($ip, $specials))
			{
				array_unshift($nodes, $ip);
				$size = sizeof($nodes);

				foreach($nodes as $idx => $ip)
					if(!in_array($ip, $specials))
					{
						unset($nodes[$idx]);
						break;
					}
				
				if($size == sizeof($nodes))
					break;
			}
			
			if(!$this->DB->Execute('UPDATE nodes SET ipaddr=? WHERE ipaddr=?', array($i,$ip)))
				$this->DB->Execute('UPDATE nodes SET ipaddr_pub=? WHERE ipaddr_pub=?', array($i,$ip));
		}
	}

	function NetworkRemap($src,$dst)
	{
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

	function GetNetworkRecord($id, $page = 0, $plimit = 4294967296, $firstfree=false)
	{
		$network = $this->DB->GetRow('SELECT id, name, inet_ntoa(address) AS address, 
				address AS addresslong, mask, interface, gateway, dns, dns2, 
				domain, wins, dhcpstart, dhcpend, 
				mask2prefix(inet_aton(mask)) AS prefix,
				inet_ntoa(broadcast(address, inet_aton(mask))) AS broadcast, 
				notes 
				FROM networks WHERE id = ?', array($id));

		$nodes = $this->DB->GetAllByKey('
				SELECT id, name, ipaddr, ownerid, netdev 
				FROM nodes WHERE ipaddr > ? AND ipaddr < ?
				UNION ALL
				SELECT id, name, ipaddr_pub AS ipaddr, ownerid, netdev 
				FROM nodes WHERE ipaddr_pub > ? AND ipaddr_pub < ?',
				'ipaddr',
				array($network['addresslong'], ip_long($network['broadcast']),
				$network['addresslong'], ip_long($network['broadcast'])));

		$network['size'] = pow(2,32-$network['prefix']);
		$network['assigned'] = sizeof($nodes);
		$network['free'] = $network['size'] - $network['assigned'] - 2;
		if ($network['dhcpstart'])
			$network['free'] = $network['free'] - (ip_long($network['dhcpend']) - ip_long($network['dhcpstart']) + 1);

		if(!$plimit) $plimit = 256;
		$network['pages'] = ceil($network['size'] / $plimit);

		if($page > $network['pages'])
			$page = $network['pages'];
		if($page < 1)
			$page = 1;
    		$page --;

		while(1)
		{
		    $start = $page * $plimit;
		    $end = ($network['size'] > $plimit ? $start + $plimit : $network['size']);
		    $network['pageassigned'] = 0;
		    unset($network['nodes']);
		
		    for($i = 0; $i < ($end - $start) ; $i ++)
		    {
			$longip = (string) ($network['addresslong'] + $i + $start);

			$network['nodes']['addresslong'][$i] = $longip;
			$network['nodes']['address'][$i] = long2ip($longip);

			if(isset($nodes[$longip]))
			{
				$network['nodes']['id'][$i] = $nodes[$longip]['id'];
				$network['nodes']['netdev'][$i] = $nodes[$longip]['netdev'];
				$network['nodes']['ownerid'][$i] = $nodes[$longip]['ownerid'];
				$network['nodes']['name'][$i] = $nodes[$longip]['name'];
				$network['pageassigned']++;
			}
			else
			{
				$network['nodes']['id'][$i] = 0;
				
				if($longip == $network['addresslong'])
					$network['nodes']['name'][$i] = '<b>NETWORK</b>';
				elseif($network['nodes']['address'][$i] == $network['broadcast'])
					$network['nodes']['name'][$i] = '<b>BROADCAST</b>';
				elseif($network['nodes']['address'][$i] == $network['gateway'])
					$network['nodes']['name'][$i] = '<b>GATEWAY</b>';
				elseif($longip >= ip_long($network['dhcpstart']) && $longip <= ip_long($network['dhcpend']) )
					$network['nodes']['name'][$i] = '<b>DHCP</b>';
				else
					$freenode = true;
			}
		    }

		    if($firstfree && !isset($freenode))
		    {
			    if($page+1 >= $network['pages']) break;
			    $page++;
		    } else
			    break;
		}

		$network['rows'] = ceil(sizeof($network['nodes']['address']) / 4);
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

	function GetNetDevIDByNode($id)
	{
		return $this->DB->GetOne('SELECT netdev FROM nodes WHERE id=?', array($id));
	}

	function CountNetDevLinks($id)
	{
		return $this->DB->GetOne('SELECT COUNT(*) FROM netlinks WHERE src = ? OR dst = ?', 
				array($id,$id)) 
			+ $this->DB->GetOne('SELECT COUNT(*) FROM nodes WHERE netdev = ? AND ownerid > 0', 
				array($id));
	}

	function GetNetDevLinkType($dev1,$dev2)
	{
		return $this->DB->GetOne('SELECT type FROM netlinks 
			WHERE (src=? AND dst=?) OR (dst=? AND src=?)', 
			array($dev1,$dev2,$dev1,$dev2));
	}

	function GetNetDevConnectedNames($id)
	{
		return $this->DB->GetAll('SELECT d.id, d.name, d.description,
			d.location, d.producer, d.ports, l.type AS linktype,
			l.srcport, l.dstport,
			(SELECT COUNT(*) FROM netlinks WHERE src = d.id OR dst = d.id) 
			+ (SELECT COUNT(*) FROM nodes WHERE netdev = d.id AND ownerid > 0)
			AS takenports 
			FROM netdevices d
			JOIN (SELECT DISTINCT type,
				(CASE src WHEN ? THEN dst ELSE src END) AS dev, 
				(CASE src WHEN ? THEN dstport ELSE srcport END) AS srcport, 
				(CASE src WHEN ? THEN srcport ELSE dstport END) AS dstport 
				FROM netlinks WHERE src = ? OR dst = ?
			) l ON (d.id = l.dev)
			ORDER BY name', array($id, $id, $id, $id, $id));
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
			case 'takenports':
				$sqlord = ' ORDER BY takenports';
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
		
		$netdevlist = $this->DB->GetAll('SELECT d.id, d.name, d.location, 
			d.description, d.producer, d.model, d.serialnumber, d.ports, 
			(SELECT COUNT(*) FROM nodes WHERE netdev=d.id AND ownerid > 0)
			+ (SELECT COUNT(*) FROM netlinks WHERE src = d.id OR dst = d.id) 
			AS takenports
			FROM netdevices d '
			.($sqlord != '' ? $sqlord.' '.$direction : ''));

		$netdevlist['total'] = sizeof($netdevlist);
		$netdevlist['order'] = $order;
		$netdevlist['direction'] = $direction;

		return $netdevlist;
	}

	function GetNetDevNames()
	{
		return $this->DB->GetAll('SELECT id, name, location, producer 
			FROM netdevices ORDER BY name');
	}

	function GetNotConnectedDevices($id)
	{
		return $this->DB->GetAll('SELECT d.id, d.name, d.description,
			d.location, d.producer, d.ports
			FROM netdevices d
			LEFT JOIN (SELECT DISTINCT 
				(CASE src WHEN ? THEN dst ELSE src END) AS dev 
				FROM netlinks WHERE src = ? OR dst = ?
			) l ON (d.id = l.dev)
			WHERE l.dev IS NULL AND d.id != ?
			ORDER BY name', array($id, $id, $id, $id));
	}

	function GetNetDev($id)
	{
		$result = $this->DB->GetRow('SELECT d.*, t.name AS nastypename, c.name AS channel
			FROM netdevices d
			LEFT JOIN nastypes t ON (t.id = d.nastype)
			LEFT JOIN ewx_channels c ON (d.channelid = c.id)
			WHERE d.id = ?', array($id));

		$result['takenports'] = $this->CountNetDevLinks($id);

		if($result['guaranteeperiod'] != NULL && $result['guaranteeperiod'] != 0)
			$result['guaranteetime'] = strtotime('+'.$result['guaranteeperiod'].' month', $result['purchasetime'] ); // transform to UNIX timestamp
		elseif($result['guaranteeperiod'] == NULL)
			$result['guaranteeperiod'] = -1;

		return $result;
	}

	function NetDevDelLinks($id)
	{
		$this->DB->Execute('DELETE FROM netlinks WHERE src=? OR dst=?', array($id,$id));
		$this->DB->Execute('UPDATE nodes SET netdev=0, port=0 
				WHERE netdev=? AND ownerid>0', array($id));
	}

	function DeleteNetDev($id)
	{
		$this->DB->BeginTrans();
		$this->DB->Execute('DELETE FROM netlinks WHERE src=? OR dst=?', array($id));
		$this->DB->Execute('DELETE FROM nodes WHERE ownerid=0 AND netdev=?', array($id));
		$this->DB->Execute('UPDATE nodes SET netdev=0 WHERE netdev=?', array($id));
		$this->DB->Execute('DELETE FROM netdevices WHERE id=?', array($id));
		$this->DB->CommitTrans();
	}
	function NetDevAdd($netdevdata)
	{
		if ($this->DB->Execute('INSERT INTO netdevices (name, location, 
				description, producer, model, serialnumber, 
				ports, purchasetime, guaranteeperiod, shortname,
				nastype, clients, secret, community, channelid) 
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', 
				array($netdevdata['name'],
					$netdevdata['location'],
					$netdevdata['description'],
					$netdevdata['producer'],
					$netdevdata['model'],
					$netdevdata['serialnumber'],
					$netdevdata['ports'],
					$netdevdata['purchasetime'],
					$netdevdata['guaranteeperiod'],
					$netdevdata['shortname'],
					$netdevdata['nastype'],
					$netdevdata['clients'],
					$netdevdata['secret'],
					$netdevdata['community'],
					!empty($netdevdata['channelid']) ? $netdevdata['channelid'] : NULL,
		))) {
		
			$id = $this->DB->GetLastInsertID('netdevices');

			// EtherWerX support (devices have some limits)
			// We must to replace big ID with smaller (first free)
			if($id > 99999 && chkconfig($this->CONFIG['phpui']['ewx_support']))
			{
				$this->DB->BeginTrans();
				$this->DB->LockTables('ewx_channels');
				
				if($newid = $this->DB->GetOne('SELECT n.id + 1 FROM ewx_channels n 
						LEFT OUTER JOIN ewx_channels n2 ON n.id + 1 = n2.id
						WHERE n2.id IS NULL AND n.id <= 99999
						ORDER BY n.id ASC LIMIT 1'))
				{
					$this->DB->Execute('UPDATE ewx_channels SET id = ? WHERE id = ?', array($newid, $id));
					$id = $newid;
				}
				
				$this->DB->UnLockTables();
				$this->DB->CommitTrans();
			}
			
			return $id;
		}
		else
			return FALSE;
	}

	function NetDevUpdate($netdevdata)
	{
		$this->DB->Execute('UPDATE netdevices SET name=?, location=?, description=?, producer=?, 
				model=?, serialnumber=?, ports=?, purchasetime=?, guaranteeperiod=?, shortname=?,
				nastype=?, clients=?, secret=?, community=?, channelid=?
				WHERE id=?', 
				array( $netdevdata['name'], 
					$netdevdata['location'], 
					$netdevdata['description'], 
					$netdevdata['producer'], 
					$netdevdata['model'], 
					$netdevdata['serialnumber'], 
					$netdevdata['ports'], 
					$netdevdata['purchasetime'], 
					$netdevdata['guaranteeperiod'], 
					$netdevdata['shortname'],
					$netdevdata['nastype'],
					$netdevdata['clients'],
					$netdevdata['secret'],
					$netdevdata['community'],
					!empty($netdevdata['channelid']) ? $netdevdata['channelid'] : NULL,
					$netdevdata['id']
				));
	}

	function IsNetDevLink($dev1, $dev2)
	{
		return $this->DB->GetOne('SELECT COUNT(id) FROM netlinks 
			WHERE (src=? AND dst=?) OR (dst=? AND src=?)',
			array($dev1, $dev2, $dev1, $dev2));
	}

	function NetDevLink($dev1, $dev2, $type=0, $sport=0, $dport=0)
	{
		if($dev1 != $dev2)
			if(!$this->IsNetDevLink($dev1,$dev2))
				return $this->DB->Execute('INSERT INTO netlinks 
					(src, dst, type, srcport, dstport) 
					VALUES (?, ?, ?, ?, ?)', 
					array($dev1, $dev2, $type, 
						intval($sport), intval($dport)));

		return FALSE;
	}

	function NetDevUnLink($dev1, $dev2)
	{
		$this->DB->Execute('DELETE FROM netlinks WHERE (src=? AND dst=?) OR (dst=? AND src=?)',
			array($dev1, $dev2, $dev1, $dev2));
	}

	function GetUnlinkedNodes()
	{
		return $this->DB->GetAll('SELECT *, inet_ntoa(ipaddr) AS ip 
			FROM nodes WHERE netdev=0 ORDER BY name ASC');
	}

	function GetNetDevIPs($id)
	{
		return $this->DB->GetAll('SELECT id, name, mac, ipaddr, inet_ntoa(ipaddr) AS ip, 
			ipaddr_pub, inet_ntoa(ipaddr_pub) AS ip_pub, access, info, port 
			FROM nodes WHERE ownerid = 0 AND netdev = ?', array($id));
	}

	/*
	 *   Request Tracker (Helpdesk)
	 */

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

	function GetQueueContents($ids, $order='createtime,desc', $state=NULL, $owner=0)
	{
		if(!$order)
			$order = 'createtime,desc';

		list($order,$direction) = sscanf($order, '%[^,],%s');

		($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

		switch($order)
		{
			case 'ticketid':
				$sqlord = ' ORDER BY t.id';
			break;
			case 'subject':
				$sqlord = ' ORDER BY t.subject';
			break;
			case 'requestor':
				$sqlord = ' ORDER BY requestor';
			break;
			case 'owner':
				$sqlord = ' ORDER BY ownername';
			break;
			case 'lastmodified':
				$sqlord = ' ORDER BY lastmodified';
			break;
			case 'creator':
				$sqlord = ' ORDER BY creatorname';
			break;
			default:
				$sqlord = ' ORDER BY t.createtime';
			break;
		}

		switch($state)
		{
			case '0':
			case '1':
			case '2':
			case '3':
				$statefilter = ' AND state = '.$state;
			break;
			case '-1':
				$statefilter = ' AND state != '.RT_RESOLVED;
			break;
			default:
				$statefilter = '';
			break;
		}

		if($result = $this->DB->GetAll(
		    'SELECT t.id, t.customerid, c.address, users.name AS ownername,
			    t.subject, state, owner AS ownerid, t.requestor AS req,
			    CASE WHEN customerid = 0 THEN t.requestor ELSE '
			    .$this->DB->Concat('c.lastname',"' '",'c.name').' END AS requestor, 
			    t.createtime AS createtime, u.name AS creatorname,
			    (SELECT MAX(createtime) FROM rtmessages WHERE ticketid = t.id) AS lastmodified
		    FROM rttickets t 
		    LEFT JOIN users ON (owner = users.id)
		    LEFT JOIN customers c ON (t.customerid = c.id)
		    LEFT JOIN users u ON (t.creatorid = u.id)
		    WHERE 1=1 '
		    .(is_array($ids) ? ' AND queueid IN ('.implode(',', $ids).')' : ($ids != 0 ? ' AND queueid = '.$ids : ''))
		    .$statefilter
		    .($owner ? ' AND t.owner = '.intval($owner) : '')
		    .($sqlord !='' ? $sqlord.' '.$direction:'')))
		{
			foreach($result as $idx => $ticket)
			{
				//$ticket['requestoremail'] = preg_replace('/^.*<(.*@.*)>$/', '\1',$ticket['requestor']);
				//$ticket['requestor'] = str_replace(' <'.$ticket['requestoremail'].'>','',$ticket['requestor']);
				if(!$ticket['customerid'])
					list($ticket['requestor'], $ticket['requestoremail']) = sscanf($ticket['req'], "%[^<]<%[^>]");
				else
					list($ticket['requestoremail']) = sscanf($ticket['req'], "<%[^>]");
				$result[$idx] = $ticket;
			}
		}

		$result['total'] = sizeof($result);
		$result['state'] = $state;
		$result['order'] = $order;
		$result['direction'] = $direction;
		$result['owner'] = $owner;

		return $result;
	}

	function GetUserRightsRT($user, $queue, $ticket=NULL)
	{
		if(!$queue && $ticket)
		{
			if(!($queue = $this->GetCache('rttickets', $ticket, 'queueid')))
				$queue = $this->DB->GetOne('SELECT queueid FROM rttickets WHERE id=?', array($ticket));
		}
		
		if (!$queue)
			return 0;
		
		$rights = $this->DB->GetOne('SELECT rights FROM rtrights WHERE userid=? AND queueid=?',
			array($user, $queue));

		return ($rights ? $rights : 0);
	}

	function GetQueueList($stats=true)
	{
		if($result = $this->DB->GetAll('SELECT id, name, email, description 
				FROM rtqueues ORDER BY name'))
		{
			if($stats)
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

	function GetQueueName($id)
	{
		return $this->DB->GetOne('SELECT name FROM rtqueues WHERE id=?', array($id));
	}

	function GetQueueEmail($id)
	{
		return $this->DB->GetOne('SELECT email FROM rtqueues WHERE id=?', array($id));
	}

	function GetQueueStats($id)
	{
		if($result = $this->DB->GetAll('SELECT state, COUNT(state) AS scount 
			FROM rttickets WHERE queueid = ? GROUP BY state ORDER BY state ASC', array($id)))
		{
			foreach($result as $row)
				$stats[$row['state']] = $row['scount'];
			foreach(array('new', 'open', 'resolved', 'dead') as $idx => $value)
				$stats[$value] = isset($stats[$idx]) ? $stats[$idx] : 0;
		}
		$stats['lastticket'] = $this->DB->GetOne('SELECT createtime FROM rttickets 
			WHERE queueid = ? ORDER BY createtime DESC', array($id));
		
		return $stats;
	}

	function RTStats()
	{
		return $this->DB->GetRow('SELECT COUNT(CASE state WHEN '.RT_NEW.' THEN 1 END) AS new,
				    COUNT(CASE state WHEN '.RT_OPEN.' THEN 1 END) AS opened,
				    COUNT(CASE state WHEN '.RT_RESOLVED.' THEN 1 END) AS resolved,
				    COUNT(CASE state WHEN '.RT_DEAD.' THEN 1 END) AS dead,
				    COUNT(CASE WHEN state != '.RT_RESOLVED.' THEN 1 END) AS unresolved
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
		$ticket = $this->DB->GetOne('SELECT * FROM rttickets WHERE id = ?', array($id));
		$this->cache['rttickets'][$id] = $ticket;
		return $ticket;
	}

	function TicketAdd($ticket)
	{
		$ts = time();
		$this->DB->Execute('INSERT INTO rttickets (queueid, customerid, requestor, subject, 
				state, owner, createtime, cause, creatorid)
				VALUES (?, ?, ?, ?, 0, 0, ?, ?, ?)', 
				array($ticket['queue'], 
					$ticket['customerid'],
					$ticket['requestor'],
					$ticket['subject'],
					$ts, 
					isset($ticket['cause']) ? $ticket['cause'] : 0,
					isset($this->AUTH->id) ? $this->AUTH->id : 0
					));
		
		$id = $this->DB->GetLastInsertID('rttickets');
		
		$this->DB->Execute('INSERT INTO rtmessages (ticketid, customerid, createtime, 
				subject, body, mailfrom)
				VALUES (?, ?, ?, ?, ?, ?)', 
				array($id, 
					$ticket['customerid'],
					$ts,
					$ticket['subject'],
					$ticket['body'],
					$ticket['mailfrom']));
		
		return $id;
	}

	function GetTicketContents($id)
	{
 		global $RT_STATES;
		
		$ticket = $this->DB->GetRow('SELECT t.id AS ticketid, t.queueid, rtqueues.name AS queuename, 
				    t.requestor, t.state, t.owner, t.customerid, t.cause, t.creatorid, c.name AS creator, '
				    .$this->DB->Concat('customers.lastname',"' '",'customers.name').' AS customername, 
				    o.name AS ownername, t.createtime, t.resolvetime, t.subject
				FROM rttickets t
				LEFT JOIN rtqueues ON (t.queueid = rtqueues.id)
				LEFT JOIN users o ON (t.owner = o.id)
				LEFT JOIN users c ON (t.creatorid = c.id)
				LEFT JOIN customers ON (customers.id = t.customerid)
				WHERE t.id = ?', array($id));
		
		$ticket['messages'] = $this->DB->GetAll(
				'(SELECT rtmessages.id AS id, mailfrom, subject, body, createtime, '
				    .$this->DB->Concat('customers.lastname',"' '",'customers.name').' AS customername, 
				    userid, users.name AS username, customerid, rtattachments.filename AS attachment
				FROM rtmessages
				LEFT JOIN customers ON (customers.id = customerid)
				LEFT JOIN users ON (users.id = userid)
				LEFT JOIN rtattachments ON (rtmessages.id = rtattachments.messageid)
				WHERE ticketid = ?)
				UNION
				(SELECT rtnotes.id AS id, NULL, NULL, body, createtime, NULL,
				    userid, users.name AS username, NULL, NULL
				FROM rtnotes
				LEFT JOIN users ON (users.id = userid)
				WHERE ticketid = ?)
				ORDER BY createtime ASC', array($id, $id)); 

		if(!$ticket['customerid'])
			list($ticket['requestor'], $ticket['requestoremail']) = sscanf($ticket['requestor'], "%[^<]<%[^>]");
		else
			list($ticket['requestoremail']) = sscanf($ticket['requestor'], "<%[^>]");
//		$ticket['requestoremail'] = preg_replace('/^.* <(.+@.+)>$/', '\1',$ticket['requestor']);
//		$ticket['requestor'] = str_replace(' <'.$ticket['requestoremail'].'>','',$ticket['requestor']);
		$ticket['status'] = $RT_STATES[$ticket['state']];
		$ticket['uptime'] = uptimef($ticket['resolvetime'] ? $ticket['resolvetime'] - $ticket['createtime'] : time() - $ticket['createtime']);
		
		return $ticket;
	}

	function SetTicketState($ticket, $state)
	{
		($state==2 ? $resolvetime = time() : $resolvetime = 0);

		if($this->DB->GetOne('SELECT owner FROM rttickets WHERE id=?', array($ticket)))
			$this->DB->Execute('UPDATE rttickets SET state=?, resolvetime=? WHERE id=?', array($state, $resolvetime, $ticket));
		else
			$this->DB->Execute('UPDATE rttickets SET state=?, owner=?, resolvetime=? WHERE id=?', array($state, $this->AUTH->id, $resolvetime, $ticket));
	}

	function GetMessage($id)
	{
		if($message = $this->DB->GetRow('SELECT * FROM rtmessages WHERE id=?', array($id)))
			$message['attachments'] = $this->DB->GetAll('SELECT * FROM rtattachments WHERE messageid = ?', array($id));
		return $message;
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
			case 'networkhosts_pagelimit':
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
			case 'to_words_short_version':
			case 'disable_devel_warning':
			case 'newticket_notify':
			case 'print_balance_list':
			case 'short_pagescroller':
			case 'big_networks':
			case 'ewx_support':
			case 'helpdesk_stats':
			case 'helpdesk_customerinfo':
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
	 *  Miscalenous
	 */

	function GetHostingLimits($customerid)
	{
		$result = array('alias_limit' => 0,
			'domain_limit' => 0,
			'sh_limit' => 0,
			'www_limit' => 0,
			'ftp_limit' => 0,
			'mail_limit' => 0,
			'sql_limit' => 0,
			'quota_sh_limit' => 0,
			'quota_www_limit' => 0,	
			'quota_ftp_limit' => 0,	
			'quota_mail_limit' => 0,
			'quota_sql_limit' => 0,	
		);
		
		if($limits = $this->DB->GetAll('SELECT alias_limit, domain_limit, sh_limit,
			www_limit, mail_limit, sql_limit, ftp_limit, quota_sh_limit,
			quota_www_limit, quota_mail_limit, quota_sql_limit, quota_ftp_limit
	                FROM tariffs WHERE id IN (SELECT tariffid FROM assignments
				WHERE customerid = ? AND tariffid != 0
				AND (dateto > ?NOW? OR dateto = 0)
				AND (datefrom < ?NOW? OR datefrom = 0))',
			array($customerid)))
		{
		        foreach($limits as $row)
				foreach($row as $idx => $val)
		            		if($val === NULL || $result[$idx] === NULL) { 
						$result[$idx] = NULL; // no limit
					} 
		                        else {
						$result[$idx] += $val;
					}
		}
		
		return $result;
	}

	function GetRemoteMACs($host = '127.0.0.1', $port = 1029)
	{
		$inputbuf = '';
		$result = array();
		
		if($socket = socket_create (AF_INET, SOCK_STREAM, 0))
			if(@socket_connect ($socket, $host, $port))
			{
				while ($input = socket_read ($socket, 2048))
					$inputbuf .= $input;
				socket_close ($socket);
			}
		if($inputbuf)
		{
			foreach(explode("\n",$inputbuf) as $line)
			{
				list($ip,$hwaddr) = explode(' ',$line);
				if(check_mac($hwaddr))
				{
					$result['mac'][] = $hwaddr;
					$result['ip'][] = $ip;
					$result['longip'][] = ip_long($ip);
					$result['nodename'][] = $this->GetNodeNameByMAC($hwaddr);
				}
			}
		}

		return $result;
	}

	function GetMACs()
	{
		$result = array();
		if ($this->CONFIG['phpui']['arp_table_backend'] != '')
		{
			exec($this->CONFIG['phpui']['arp_table_backend'], $result);
			foreach($result as $arpline)
			{
				list($ip,$mac) = explode(' ',$arpline);
				$result['mac'][] = $mac;
				$result['ip'][] = $ip;
				$result['longip'][] = ip_long($ip);
				$result['nodename'][] = $this->GetNodeNameByMAC($mac);
			}
		}
		else
			switch(PHP_OS)
			{
				case 'Linux':
					if(@is_readable('/proc/net/arp'))
						$file = fopen('/proc/net/arp','r');
					else
						break;
					while(!feof($file))
					{
						$line = fgets($file, 4096);
						$line = preg_replace('/[\t ]+/', ' ', $line);
						if(preg_match('/[0-9]/', $line)) // skip header line
						{
							list($ip, $hwtype, $flags, $hwaddr, $mask, $device) = explode(' ',$line);
							if($flags != '0x6' && $hwaddr != '00:00:00:00:00:00' && check_mac($hwaddr))
							{
								$result['mac'][] = $hwaddr;
								$result['ip'][] = $ip;
								$result['longip'][] = ip_long($ip);
								$result['nodename'][] = $this->GetNodeNameByMAC($hwaddr);
							}
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
		
		return $result;
	}

	function GetUniqueInstallationID()
	{
		if(!($uiid = $this->DB->GetOne('SELECT keyvalue FROM dbinfo WHERE keytype=?', array('unique_installation_id'))))
		{
			list($usec, $sec) = explode(' ', microtime());
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
			list($v, ) = explode(' ', $this->_version);
			
			if($content = fetch_url('http://register.lms.org.pl/update.php?uiid='.$uiid.'&v='.$v))
			{
				if($lastcheck == 0)
					$this->DB->Execute('INSERT INTO dbinfo (keyvalue, keytype) VALUES (?NOW?, ?)', 
						array('last_check_for_updates_timestamp'));
				else
					$this->DB->Execute('UPDATE dbinfo SET keyvalue=?NOW? WHERE keytype=?',
						array('last_check_for_updates_timestamp'));

				$content = unserialize((string)$content);
				$content['regdata'] = unserialize((string)$content['regdata']);
			
				if(is_array($content['regdata']))
				{
					$this->DB->Execute('DELETE FROM dbinfo WHERE keytype LIKE ?', array('regdata_%'));
			
					foreach(array('id', 'name', 'url', 'hidden') as $key)
						$this->DB->Execute('INSERT INTO dbinfo (keytype, keyvalue) VALUES (?, ?)', 
							array('regdata_'.$key, $content['regdata'][$key]));
				}
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
		$url = 'http://register.lms.org.pl/register.php?uiid='.$uiid.'&name='.$name.'&url='.$url.($hidden == TRUE ? '&hidden=1' : '');

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

		$params['host'] = $this->CONFIG['mail']['smtp_host'];
		$params['port'] = $this->CONFIG['mail']['smtp_port'];

		if (!empty($this->CONFIG['mail']['smtp_username']))
		{
			$params['auth'] = !empty($this->CONFIG['mail']['smtp_auth_type']) ? $this->CONFIG['mail']['smtp_auth_type'] : true;
			$params['username'] = $this->CONFIG['mail']['smtp_username'];
			$params['password'] = $this->CONFIG['mail']['smtp_password'];
		}
		else
			$params['auth'] = false;

		$headers['X-Mailer'] = 'LMS-'.$this->_version;
		$headers['X-Remote-IP'] = $_SERVER['REMOTE_ADDR'];
		$headers['X-HTTP-User-Agent'] = $_SERVER['HTTP_USER_AGENT'];
		$headers['Mime-Version'] = '1.0';
		$headers['Subject'] = qp_encode($headers['Subject']);

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
			return MSG_SENT;
	}

	function SendSMS($number, $message, $messageid=0)
	{
		if(empty($this->CONFIG['sms']['service']))
			return trans('SMS "service" not set!');
		else
			$service = $this->CONFIG['sms']['service'];

		$prefix = !empty($this->CONFIG['sms']['prefix']) ? $this->CONFIG['sms']['prefix'] : '';

		$number = preg_replace('/[^0-9]/', '', $number);
		$number = preg_replace('/^0+/', '', $number);

		if ($prefix && substr($number, 0, strlen($prefix)) != $prefix)
			$number = $prefix . $number;
		
		switch($service)
		{
			case 'smscenter':
				if(!function_exists('curl_init'))
					return trans('Curl extension not loaded!');
				if(empty($this->CONFIG['sms']['username']))
					return trans('SMSCenter username not set!');
				if(empty($this->CONFIG['sms']['password']))
					return trans('SMSCenter username not set!');
				if(empty($this->CONFIG['sms']['from']))
					return trans('SMS "from" not set!');
				else
					$from = $this->CONFIG['sms']['from'];

				if(strlen($message) > 159 || strlen($message) == 0)
					return trans('SMS Message too long!');
				if(strlen($number) > 16 || strlen($number) < 4)
					return trans('Wrong phone number format!');
				
				$type = !empty($this->CONFIG['sms']['smscenter_type']) ? $this->CONFIG['sms']['smscenter_type'] : 'dynamic';
				$message .= ($type == 'static') ? "\n\n" . $from : '';

				$args = array (
					'user'      => $this->CONFIG['sms']['username'],
					'pass'      => $this->CONFIG['sms']['password'],
					'type'      => 'sms',
					'number'    => $number,
					'text'      => $message,
					'from'      => $from
				);
			    		
				$encodedargs = array();
				foreach (array_keys($args) as $thiskey)
		    			array_push($encodedargs, urlencode($thiskey) ."=". urlencode($args[$thiskey]));
				$encodedargs = implode('&', $encodedargs);

		    		$curl = curl_init();
		    		curl_setopt($curl, CURLOPT_URL, 'http://api.statsms.net/send.php');
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $encodedargs);
				curl_setopt($curl, CURLOPT_TIMEOUT, 10);
			    
				$page = curl_exec($curl);
				if(curl_error($curl))
					return 'SMS communication error. ' . curl_error($curl);

				$info = curl_getinfo($curl);
				if($info['http_code'] != '200')
					return 'SMS communication error. Http code: ' . $info['http_code'];

				curl_close($curl);
				$smsc = explode(', ', $page);
				$smsc_result = array();

				foreach ($smsc as $element)
				{
					$tmp = explode(': ', $element);
					array_push($smsc_result, $tmp[1]);
				}

				switch ($smsc_result[0])
				{
					case '002':
					case '003':
					case '004':
					case '008':
					case '011':
						return MSG_SENT;
					case '001':
					        return 'Smscenter error 001, Incorrect login or password';
    					case '009':
					        return 'Smscenter error 009, GSM network error (probably wrong prefix number)';
					case '012':
					        return 'Smscenter error 012, System error please contact smscenter administrator';
					case '104':
					        return 'Smscenter error 104, Incorrect sender field or field empty';
					case '201':
					        return 'Smscenter error 201, System error please contact smscenter administrator';
					case '202':
					        return 'Smscenter error 202, Unsufficient funds on account to send this text';
					case '204':
					        return 'Smscenter error 204, Account blocked';
					default:
					        return 'Smscenter error '. $smsc_result[0] . '. Please contact smscenter administrator';
				}
			break;
			case 'smstools':
				$dir = !empty($this->CONFIG['sms']['smstools_outdir']) ? $this->CONFIG['sms']['smstools_outdir'] : '/var/spool/sms/outgoing';

				if(!file_exists($dir))
					return trans('SMSTools outgoing directory not exists ($0)!', $dir);
				if(!is_writable($dir))
					return trans('Unable to write to SMSTools outgoing directory ($0)!', $dir);
				
				$filename = $dir.'/lms-'.$messageid.'-'.$number;
				$message = clear_utf($message);
				$file = sprintf("To: %s\n\n%s", $number, $message);
				
				if($fp = fopen($filename, 'w'))
				{
					fwrite($fp, $file);
					fclose($fp);
				}
				else
					return trans('Unable to create file $0!', $filename);
		
				return MSG_NEW;
			break;
			default:
				return trans('Unknown SMS service!');
		}
	}
	
	function GetMessages($customerid, $limit=NULL)
	{
		return $this->DB->GetAll('SELECT i.messageid AS id, i.status, i.error,
		        i.destination, m.subject, m.type, m.cdate
			FROM messageitems i
			JOIN messages m ON (m.id = i.messageid)
			WHERE i.customerid = ?
			ORDER BY m.cdate DESC'
			.($limit ? ' LIMIT '.$limit : ''), array($customerid));
	}

	function GetDocuments($customerid=NULL, $limit=NULL)
	{
		if(!$customerid) return NULL;
		
		if($list = $this->DB->GetAll('SELECT c.docid, d.number, d.type, c.title, c.fromdate, c.todate, 
			c.description, c.filename, c.md5sum, c.contenttype, n.template, d.closed, d.cdate
			FROM documentcontents c
			JOIN documents d ON (c.docid = d.id)
			JOIN docrights r ON (d.type = r.doctype AND r.userid = ? AND (r.rights & 1) = 1)
			LEFT JOIN numberplans n ON (d.numberplanid = n.id)
			WHERE d.customerid = ?
			ORDER BY cdate', array($this->AUTH->id, $customerid)))
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

		return $this->DB->GetAllByKey('SELECT id, value, label, taxed FROM taxes
			WHERE (validfrom = 0 OR validfrom <= ?)
			    AND (validto = 0 OR validto >= ?)
			ORDER BY value', 'id', array($from, $to));
	}

	function EventSearch($search, $order='date,asc', $simple=false)
	{
		list($order,$direction) = sscanf($order, '%[^,],%s');

		(strtolower($direction) != 'desc') ? $direction = 'ASC' : $direction = 'DESC';

		switch($order)
		{
			default:
				$sqlord = ' ORDER BY date '.$direction.', begintime '.$direction;
			break;
		}

		$list = $this->DB->GetAll(
			'SELECT events.id AS id, title, description, date, begintime, endtime, customerid, closed, '
			.$this->DB->Concat('customers.lastname',"' '",'customers.name').' AS customername
			FROM events
			LEFT JOIN customers ON (customerid = customers.id)
			WHERE (private = 0 OR (private = 1 AND userid = ?)) '
			.(!empty($search['datefrom']) ? ' AND date >= '.intval($search['datefrom']) : '')
			.(!empty($search['dateto']) ? ' AND date <= '.intval($search['dateto']) : '')
			.(!empty($search['customerid']) ? ' AND customerid = '.intval($search['customerid']) : '')
			.(!empty($search['title']) ? ' AND title ?LIKE? '.$this->DB->Escape('%'.$search['title'].'%') : '')
			.(!empty($search['description']) ? ' AND description ?LIKE? '.$this->DB->Escape('%'.$search['description'].'%') : '')
			.(!empty($search['note']) ? ' AND note ?LIKE? '.$this->DB->Escape('%'.$search['note'].'%') : '')
			.$sqlord, array($this->AUTH->id));

		if($list)
		{
			foreach($list as $idx => $row)
			{
				if(!$simple)
					$list[$idx]['userlist'] = $this->DB->GetAll('SELECT userid AS id, users.name
						FROM eventassignments, users
						WHERE userid = users.id AND eventid = ? ',
						array($row['id']));

				if($search['userid'] && !empty($list[$idx]['userlist']))
					foreach($list[$idx]['userlist'] as $user)
						if($user['id'] == $search['userid'])
						{
							$list2[] = $list[$idx];
							break;
						}
			}

			if($search['userid'])
				return $list2;
			else
				return $list;
		}
	}
	
	function GetNumberPlans($doctype=NULL, $cdate=NULL)
	{
		if(is_array($doctype))
			$list = $this->DB->GetAllByKey('
				SELECT id, template, isdefault, period, doctype 
				FROM numberplans WHERE doctype IN ('.implode(',',$doctype).') 
				ORDER BY id', 'id');
		elseif($doctype)
			$list = $this->DB->GetAllByKey('
				SELECT id, template, isdefault, period, doctype 
				FROM numberplans WHERE doctype = ? ORDER BY id', 
				'id', array($doctype));
		else
			$list = $this->DB->GetAllByKey('
				SELECT id, template, isdefault, period, doctype 
				FROM numberplans ORDER BY id', 'id');
		
		if($list)
		{
			if($cdate)
				list($curryear, $currmonth) = explode('/', $cdate);
			else
			{
				$curryear = date('Y');
				$currmonth = date('n');
			}
			switch($currmonth)
			{
				case 1: case 2: case 3: $startq = 1; $starthy = 1; break;
				case 4: case 5: case 6: $startq = 4; $starthy = 1; break;
				case 7: case 8: case 9: $startq = 7; $starthy = 7; break;
				case 10: case 11: case 12: $startq = 10; $starthy = 7; break;
			}
	
			$yearstart = mktime(0,0,0,1,1,$curryear);
			$yearend = mktime(0,0,0,1,1,$curryear+1);
			$halfyearstart = mktime(0,0,0,$starthy,1);
			$halfyearend = mktime(0,0,0,$starthy+3,1);
			$quarterstart = mktime(0,0,0,$startq,1);
			$quarterend = mktime(0,0,0,$startq+3,1);
			$monthstart = mktime(0,0,0,$currmonth,1,$curryear);
			$monthend = mktime(0,0,0,$currmonth+1,1,$curryear);
			$weekstart = mktime(0,0,0,$currmonth,date('j')-strftime('%u')+1);
			$weekend = mktime(0,0,0,$currmonth,date('j')-strftime('%u')+1+7);
			$daystart = mktime(0,0,0);
			$dayend = mktime(0,0,0,date('n'),date('j')+1);

			$max = $this->DB->GetAllByKey('SELECT numberplanid AS id, MAX(number) AS max 
					    FROM documents LEFT JOIN numberplans ON (numberplanid = numberplans.id)
					    WHERE '
					    .($doctype ? 'numberplanid IN ('.implode(',', array_keys($list)).') AND ' : '')
					    .' cdate >= (CASE period
						WHEN '.YEARLY.' THEN '.$yearstart.'
						WHEN '.HALFYEARLY.' THEN '.$halfyearstart.'
						WHEN '.QUARTERLY.' THEN '.$quarterstart.'
						WHEN '.MONTHLY.' THEN '.$monthstart.'
						WHEN '.WEEKLY.' THEN '.$weekstart.'
						WHEN '.DAILY.' THEN '.$daystart.' ELSE 0 END)
					    AND cdate < (CASE period
						WHEN '.YEARLY.' THEN '.$yearend.'
						WHEN '.HALFYEARLY.' THEN '.$halfyearend.'
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
		
		$period = isset($period) ? $period : YEARLY;
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
			case HALFYEARLY:
				$currmonth = date('n');
				switch(date('n'))
				{
					case 1: case 2: case 3: case 4: case 5: case 6: $startq = 1; break;
					case 7: case 8: case 9: case 10: case 11: case 12: $startq = 7; break;
				}
				$start = mktime(0, 0, 0, $starthy, 1, date('Y',$cdate));
				$end = mktime(0, 0, 0, $starthy+6, 1, date('Y',$cdate));
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
		
		$period = isset($period) ? $period : YEARLY;
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
			case HALFYEARLY:
				$currmonth = date('n');
				switch(date('n'))
				{
					case 1: case 2: case 3: case 4: case 5: case 6: $startq = 1; break;
					case 7: case 8: case 9: case 10: case 11: case 12: $startq = 7; break;
				}
				$start = mktime(0, 0, 0, $starthy, 1, date('Y',$cdate));
				$end = mktime(0, 0, 0, $starthy+6, 1, date('Y',$cdate));
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

	function GetCountryStates()
	{
		return $this->DB->GetAllByKey('SELECT id, name FROM states ORDER BY name', 'id');
	}

	function GetCountries()
	{
		return $this->DB->GetAllByKey('SELECT id, name FROM countries ORDER BY name', 'id');
	}

	function GetCountryName($id)
	{
		return $this->DB->GetOne('SELECT name FROM countries WHERE id = ?', array($id));
	}

	function GetNAStypes()
	{
		return $this->DB->GetAllByKey('SELECT id, name FROM nastypes ORDER BY name', 'id');
	}
	
	//VoIP functions
	function GetVoipAccountList($order='login,asc', $search=NULL, $sqlskey='AND')
	{
		if($order=='')
			$order='login,asc';

		list($order,$direction) = sscanf($order, '%[^,],%s');

		($direction=='desc') ? $direction = 'desc' : $direction = 'asc';

		switch($order)
		{
			case 'login':
				$sqlord = ' ORDER BY v.login';
			break;
			case 'passwd':
				$sqlord = ' ORDER BY v.passwd';
			break;
			case 'phone':
				$sqlord = ' ORDER BY v.phone';
			break;
			case 'id':
				$sqlord = ' ORDER BY v.id';
			break;
			case 'ownerid':
				$sqlord = ' ORDER BY v.ownerid';
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
					case 'login' :
						$searchargs[] = 'v.login ?LIKE? '.$this->DB->Escape("%$value%");
					break;
					case 'phone' :
						$searchargs[] = 'v.phone ?LIKE? '.$this->DB->Escape("%$value%");
					break;
					case 'password' :
						$searchargs[] = 'v.passwd ?LIKE? '.$this->DB->Escape("%$value%");
					break;
					default :
						$searchargs[] = $idx.' ?LIKE? '.$this->DB->Escape("%$value%");
				}
			}
		}

		if(isset($searchargs))
			$searchargs = ' AND ('.implode(' '.$sqlskey.' ',$searchargs).')';

		$voipaccountlist =
			$this->DB->GetAll('SELECT v.id, v.login, v.passwd, v.phone, v.ownerid, '
				.$this->DB->Concat('c.lastname',"' '",'c.name').' AS owner
				FROM voipaccounts v 
				JOIN customersview c ON (v.ownerid = c.id) '
				.' WHERE 1=1 '
				.(isset($searchargs) ? $searchargs : '')
				.($sqlord != '' ? $sqlord.' '.$direction : ''));

		$voipaccountlist['total'] = sizeof($voipaccountlist);
		$voipaccountlist['order'] = $order;
		$voipaccountlist['direction'] = $direction;

		return $voipaccountlist;
	}

	function VoipAccountAdd($voipaccountdata)
	{
		if($this->DB->Execute('INSERT INTO voipaccounts (ownerid, login, passwd, phone, creatorid, creationdate)
					VALUES (?, ?, ?, ?, ?, ?NOW?)',
				array($voipaccountdata['ownerid'],
				    $voipaccountdata['login'],
				    $voipaccountdata['passwd'],
				    $voipaccountdata['phone'],
				    $this->AUTH->id
				    )))
		{
			$id = $this->DB->GetLastInsertID('voipaccounts');
			return $id;
		}
		else
			return FALSE;
	}

	function VoipAccountExists($id)
	{
		return ($this->DB->GetOne('SELECT v.id FROM voipaccounts v
				WHERE v.id = ? AND NOT EXISTS (
		            		SELECT 1 FROM customerassignments a
				        JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
					WHERE e.userid = lms_current_user() AND a.customerid = v.ownerid)',
				array($id)) ? TRUE : FALSE);
	}

	function GetVoipAccountOwner($id)
	{
		return $this->DB->GetOne('SELECT ownerid FROM voipaccounts WHERE id=?', array($id));
	}

	function GetVoipAccount($id)
	{
		if($result = $this->DB->GetRow('SELECT id, ownerid, login, passwd, phone,
					creationdate, moddate, creatorid, modid
					FROM voipaccounts WHERE id = ?', array($id)))
		{
			$result['createdby'] = $this->GetUserName($result['creatorid']);
			$result['modifiedby'] = $this->GetUserName($result['modid']);
			$result['creationdateh'] = date('Y/m/d, H:i',$result['creationdate']);
			$result['moddateh'] = date('Y/m/d, H:i',$result['moddate']);
			$result['owner'] = $this->GetCustomerName($result['ownerid']);
			return $result;
		}
		else
			return FALSE;
	}

	function GetVoipAccountIDByLogin($login)
	{
		return $this->DB->GetAll('SELECT id FROM voipaccounts WHERE login=?', array($login));
	}

	function GetVoipAccountIDByPhone($phone)
	{
		return $this->DB->GetOne('SELECT id FROM voipaccounts WHERE phone=?', array($phone));
	}

	function GetVoipAccountLogin($id)
	{
		return $this->DB->GetOne('SELECT login FROM voipaccounts WHERE id=?', array($id));
	}

	function DeleteVoipAccount($id)
	{
		$this->DB->BeginTrans();
		$this->DB->Execute('DELETE FROM voipaccounts WHERE id = ?', array($id));
		$this->DB->CommitTrans();
	}

	function VoipAccountUpdate($voipaccountdata)
	{
		$this->DB->Execute('UPDATE voipaccounts SET login=?, passwd=?, phone=?, moddate=?NOW?, 
				modid=?, ownerid=? WHERE id=?', 
				array($voipaccountdata['login'],
				    $voipaccountdata['passwd'],
				    $voipaccountdata['phone'],
				    $this->AUTH->id,
				    $voipaccountdata['ownerid'],
				    $voipaccountdata['id']
			    ));
	}

	function GetCustomerVoipAccounts($id)
	{
		if($result['accounts'] = $this->DB->GetAll('SELECT id, login, passwd, phone, ownerid
				FROM voipaccounts WHERE ownerid=? 
				ORDER BY login ASC', array($id)))
		{
			$result['total'] = sizeof($result['accounts']);
		}
		return $result;
	}
}

?>
