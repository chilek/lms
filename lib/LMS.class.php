<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

class LMS {

	var $DB;   // database object
	var $AUTH;   // object from Session.class.php (session management)
	var $CONFIG;   // table including lms.ini options
	var $SYSLOG;
	var $cache = array();  // internal cache
	var $hooks = array(); // registered plugin hooks
	var $xajax;  // xajax object
	var $_version = '1.11-git'; // class version
	var $_revision = '$Revision$';

	function LMS(&$DB, &$AUTH, &$CONFIG, &$SYSLOG) { // class variables setting
		$this->DB = &$DB;
		$this->AUTH = &$AUTH;
		$this->CONFIG = &$CONFIG;
		$this->SYSLOG = &$SYSLOG;

		//$this->_revision = preg_replace('/^.Revision: ([0-9.]+).*/', '\1', $this->_revision);
		$this->_revision = '';
		//$this->_version = $this->_version.' ('.$this->_revision.')';
		$this->_version = '';
	}

	function _postinit() {
		return TRUE;
	}

	function InitUI() {
		// set current user
		switch ($this->CONFIG['database']['type']) {
			case 'postgres':
				$this->DB->Execute('SELECT set_config(\'lms.current_user\', ?, false)', array($this->AUTH->id));
				break;
			case 'mysql':
			case 'mysqli':
				$this->DB->Execute('SET @lms_current_user=?', array($this->AUTH->id));
				break;
		}
	}

	function InitXajax() {
		if (!$this->xajax) {
			require(LIB_DIR . '/xajax/xajax_core/xajax.inc.php');
			$this->xajax = new xajax();
			$this->xajax->configure('errorHandler', true);
			$this->xajax->configure('javascript URI', 'img');
		}
	}

	function RunXajax() {
		$xajax_js = NULL;
		if ($this->xajax) {
			$xajax_js = $this->xajax->getJavascript();
			$this->xajax->processRequest();
		}
		return $xajax_js;
	}

	function RegisterXajaxFunction($funcname) {
		if ($this->xajax) {
			if (is_array($funcname))
				foreach ($funcname as $func)
					$this->xajax->register(XAJAX_FUNCTION, $func);
			else
				$this->xajax->register(XAJAX_FUNCTION, $funcname);
		}
	}

	/*
	 *  Logging
	 * 	0 - disabled
	 * 	1 - system log in and modules calls without access privileges
	 * 	2 - as above, addition and deletion
	 * 	3 - as above, and changes
	 * 	4 - as above, and all modules calls (paranoid)
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
	 * Plugins
	 */

	function RegisterHook($hook_name, $callback) {
		$this->hooks[] = array(
				'name' => $hook_name,
				'callback' => $callback,
		);
	}

	function ExecHook($hook_name, $vars = null) {
		foreach ($this->hooks as $hook) {
			if ($hook['name'] == $hook_name) {
				$vars = call_user_func($hook['callback'], $vars);
			}
		}

		return $vars;
	}

	/*
	 *  Database functions (backups)
	 */

	function DBDump($filename = NULL, $gzipped = FALSE, $stats = FALSE) { // dump database to file
		if (!$filename)
			return FALSE;

		if ($gzipped && extension_loaded('zlib'))
			$dumpfile = gzopen($filename, 'w');
		else
			$dumpfile = fopen($filename, 'w');

		if ($dumpfile) {
			$tables = $this->DB->ListTables();

			foreach ($tables as $tablename) {
				// skip sessions table for security
				if ($tablename == 'sessions' || ($tablename == 'stats' && $stats == FALSE))
					continue;

				fputs($dumpfile, "DELETE FROM $tablename;\n");
			}

			if ($this->CONFIG['database']['type'] == 'postgres')
				fputs($dumpfile, "SET CONSTRAINTS ALL DEFERRED;\n");

			// Since we're using foreign keys, order of tables is important
			// Note: add all referenced tables to the list
			$order = array('users', 'customers', 'customergroups', 'nodes', 'numberplans',
					'assignments', 'rtqueues', 'rttickets', 'rtmessages', 'domains',
					'cashsources', 'sourcefiles', 'ewx_channels', 'hosts');

			foreach ($tables as $idx => $table) {
				if (in_array($table, $order)) {
					unset($tables[$idx]);
				}
			}

			$tables = array_merge($order, $tables);

			foreach ($tables as $tablename) {
				// skip sessions table for security
				if ($tablename == 'sessions' || ($tablename == 'stats' && $stats == FALSE))
					continue;

				$this->DB->Execute('SELECT * FROM ' . $tablename);
				while ($row = $this->DB->_driver_fetchrow_assoc()) {
					fputs($dumpfile, "INSERT INTO $tablename (");
					foreach ($row as $field => $value) {
						$fields[] = $field;
						if (isset($value))
							$values[] = "'" . addcslashes($value, "\r\n\'\"\\") . "'";
						else
							$values[] = 'NULL';
					}
					fputs($dumpfile, implode(', ', $fields));
					fputs($dumpfile, ') VALUES (');
					fputs($dumpfile, implode(', ', $values));
					fputs($dumpfile, ");\n");
					unset($fields);
					unset($values);
				}
			}

			if ($gzipped && extension_loaded('zlib'))
				gzclose($dumpfile);
			else
				fclose($dumpfile);
		}
		else
			return FALSE;
	}

	function DatabaseCreate($gzipped = FALSE, $stats = FALSE) { // create database backup
		$basename = 'lms-' . time() . '-' . DBVERSION;
		if (($gzipped) && (extension_loaded('zlib'))) {
			$filename = $basename . '.sql.gz';
			$res = $this->DBDump($this->CONFIG['directories']['backup_dir'] . '/' . $filename, TRUE, $stats);
		} else {
			$filename = $basename . '.sql';
			$res = $this->DBDump($this->CONFIG['directories']['backup_dir'] . '/' . $filename, FALSE, $stats);
		}
		if ($this->SYSLOG)
			$this->SYSLOG->AddMessage(SYSLOG_RES_DBBACKUP, SYSLOG_OPER_ADD,
				array('filename' => $filename), null);
		return $res;
	}

	/*
	 *  Internal cache
	 */

	function GetCache($key, $idx = null, $name = null) {
		if (array_key_exists($key, $this->cache)) {
			if (!$idx)
				return $this->cache[$key];
			elseif (is_array($this->cache[$key]) && array_key_exists($idx, $this->cache[$key])) {
				if (!$name)
					return $this->cache[$key][$idx];
				elseif (is_array($this->cache[$key][$idx]) && array_key_exists($name, $this->cache[$key][$idx]))
					return $this->cache[$key][$idx][$name];
			}
		}
		return NULL;
	}

	/*
	 * Users
	 */

	function SetUserPassword($id, $passwd) {
		global $SYSLOG_RESOURCE_KEYS;
		$args = array(
			'passwd' => crypt($passwd),
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $id
		);
		$this->DB->Execute('UPDATE users SET passwd=?, passwdlastchange=?NOW? WHERE id=?', array_values($args));
		if ($this->SYSLOG) {
			unset($args['passwd']);
			$this->SYSLOG->AddMessage(SYSLOG_RES_USER, SYSLOG_OPER_USERPASSWDCHANGE, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]));
		}
	}

	function GetUserName($id = null) { // returns user name
		if ($id === null)
			$id = $this->AUTH->id;
		else if (!$id)
			return '';

		if (!($name = $this->GetCache('users', $id, 'name'))) {
			if ($this->AUTH && $this->AUTH->id == $id)
				$name = $this->AUTH->logname;
			else
				$name = $this->DB->GetOne('SELECT name FROM users WHERE id=?', array($id));
			$this->cache['users'][$id]['name'] = $name;
		}
		return $name;
	}

	function GetUserNames() { // returns short list of users
		return $this->DB->GetAll('SELECT id, name FROM users WHERE deleted=0 ORDER BY login ASC');
	}

	function GetUserList() { // returns list of users
		if ($userlist = $this->DB->GetAll('SELECT id, login, name, lastlogindate, lastloginip, 
				passwdexpiration, passwdlastchange, access, accessfrom, accessto  
				FROM users WHERE deleted=0 ORDER BY login ASC')) {
			foreach ($userlist as $idx => $row) {
				if ($row['id'] == $this->AUTH->id) {
					$row['lastlogindate'] = $this->AUTH->last;
					$userlist[$idx]['lastlogindate'] = $this->AUTH->last;
					$row['lastloginip'] = $this->AUTH->lastip;
					$userlist[$idx]['lastloginip'] = $this->AUTH->lastip;
				}

				if ($row['accessfrom'])
					$userlist[$idx]['accessfrom'] = date('Y/m/d',$row['accessfrom']);
				else
				    $userlist[$idx]['accessfrom'] = '-';
				
				if ($row['accessto'])
					$userlist[$idx]['accessto'] = date('Y/m/d',$row['accessto']);
				else
				    $userlist[$idx]['accessto'] = '-';

				if ($row['lastlogindate'])
					$userlist[$idx]['lastlogin'] = date('Y/m/d H:i', $row['lastlogindate']);
				else
					$userlist[$idx]['lastlogin'] = '-';

				if ($row['passwdlastchange'])
					$userlist[$idx]['passwdlastchange'] = date('Y/m/d H:i', $row['passwdlastchange']);
				else
					$userlist[$idx]['passwdlastchange'] = '-';

				if (check_ip($row['lastloginip']))
					$userlist[$idx]['lastloginhost'] = gethostbyaddr($row['lastloginip']);
				else {
					$userlist[$idx]['lastloginhost'] = '-';
					$userlist[$idx]['lastloginip'] = '-';
				}
			}
		}

		$userlist['total'] = sizeof($userlist);
		return $userlist;
	}

	function GetUserIDByLogin($login) {
		return $this->DB->GetOne('SELECT id FROM users WHERE login=?', array($login));
	}

	function UserAdd($user) {
		global $SYSLOG_RESOURCE_KEYS;
		$args = array(
			'login' => $user['login'],
			'name' =>  $user['name'],
			'email' => $user['email'],
			'passwd' => crypt($user['password']),
			'rights' => $user['rights'],
			'hosts' => $user['hosts'],
			'position' => $user['position'],
			'ntype' => !empty($user['ntype']) ? $user['ntype'] : null,
			'phone' => !empty($user['phone']) ? $user['phone'] : null,
			'passwdexpiration' => !empty($user['passwdexpiration']) ? $user['passwdexpiration'] : 0,
			'access' => !empty($user['access']) ? 1 : 0,
			'accessfrom' => !empty($user['accessfrom']) ? $user['accessfrom'] : 0,
			'accessto' => !empty($user['accessto']) ? $user['accessto'] : 0,
		);
		if ($this->DB->Execute('INSERT INTO users (login, name, email, passwd, rights,
				hosts, position, ntype, phone, passwdexpiration, access, accessfrom, accessto)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args))) {
			$id = $this->DB->GetOne('SELECT id FROM users WHERE login=?', array($user['login']));
			if ($this->SYSLOG) {
				unset($args['passwd']);
				$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]] = $id;
				$this->SYSLOG->AddMessage(SYSLOG_RES_USER, SYSLOG_OPER_ADD, $args,
					array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]));
			}
			return $id;
		} else
			return FALSE;
	}

	function UserDelete($id) {
		global $SYSLOG_RESOURCE_KEYS;
		if ($this->DB->Execute('UPDATE users SET deleted=1, access=0 WHERE id=?', array($id))) {
			if ($this->SYSLOG) {
				$args = array(
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $id,
					'deleted' => 1,
					'access' => 0,
				);
				$this->SYSLOG->AddMessage(SYSLOG_RES_USER, SYSLOG_OPER_UPDATE,
					$args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]));
			}
			$this->cache['users'][$id]['deleted'] = 1;
			return true;
		}
	}

	function UserExists($id) {
		switch ($this->DB->GetOne('SELECT deleted FROM users WHERE id=?', array($id))) {
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

	function UserAccess($id,$access)
	{
	    $this->DB->Execute('UPDATE users SET access = ? WHERE id = ? ;',array($access,$id));
	}

	function GetUserInfo($id) {
		if ($userinfo = $this->DB->GetRow('SELECT * FROM users WHERE id = ?', array($id))) {
			$this->cache['users'][$id] = $userinfo;

			if ($userinfo['id'] == $this->AUTH->id) {
				$userinfo['lastlogindate'] = $this->AUTH->last;
				$userinfo['lastloginip'] = $this->AUTH->lastip;
			}

			if ($userinfo['accessfrom'])
				$userinfo['accessfrom'] = date('Y/m/d', $userinfo['accessfrom']);
			else
				$userinfo['accessfrom'] = '';

			if ($userinfo['accessto'])
				$userinfo['accessto'] = date('Y/m/d', $userinfo['accessto']);
			else
				$userinfo['accessot'] = '';

			if ($userinfo['lastlogindate'])
				$userinfo['lastlogin'] = date('Y/m/d H:i', $userinfo['lastlogindate']);
			else
				$userinfo['lastlogin'] = '-';

			if ($userinfo['failedlogindate'])
				$userinfo['faillogin'] = date('Y/m/d H:i', $userinfo['failedlogindate']);
			else
				$userinfo['faillogin'] = '-';

			if ($userinfo['passwdlastchange'])
				$userinfo['passwdlastchange'] = date('Y/m/d H:i', $userinfo['passwdlastchange']);
			else
				$userinfo['passwdlastchange'] = '-';

			if (check_ip($userinfo['lastloginip']))
				$userinfo['lastloginhost'] = gethostbyaddr($userinfo['lastloginip']);
			else {
				$userinfo['lastloginhost'] = '-';
				$userinfo['lastloginip'] = '-';
			}

			if (check_ip($userinfo['failedloginip']))
				$userinfo['failedloginhost'] = gethostbyaddr($userinfo['failedloginip']);
			else {
				$userinfo['failedloginhost'] = '-';
				$userinfo['failedloginip'] = '-';
			}
		}
		return $userinfo;
	}

	function UserUpdate($user) {
		global $SYSLOG_RESOURCE_KEYS;
		$args = array(
			'login' => $user['login'],
			'name' => $user['name'],
			'email' => $user['email'],
			'rights' => $user['rights'],
			'hosts' => $user['hosts'],
			'position' => $user['position'],
			'ntype' => !empty($user['ntype']) ? $user['ntype'] : null,
			'phone' => !empty($user['phone']) ? $user['phone'] : null,
			'passwdexpiration' => !empty($user['passwdexpiration']) ? $user['passwdexpiration'] : 0,
			'access' => !empty($user['access']) ? 1 : 0,
			'accessfrom' => !empty($user['accessfrom']) ? $user['accessfrom'] : 0,
			'accessto' => !empty($user['accessto']) ? $user['accessto'] : 0,
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $user['id']
		);
		$res = $this->DB->Execute('UPDATE users SET login=?, name=?, email=?, rights=?,
				hosts=?, position=?, ntype=?, phone=?, passwdexpiration=?, access=?, accessfrom=?, accessto=? WHERE id=?',
				array_values($args));
		if ($res && $this->SYSLOG)
			$this->SYSLOG->AddMessage(SYSLOG_RES_USER, SYSLOG_OPER_UPDATE, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]));
		return $res;
	}

	function GetUserRights($id) {
		if (!($mask = $this->GetCache('users', $id, 'rights'))) {
			$mask = $this->DB->GetOne('SELECT rights FROM users WHERE id = ?', array($id));
		}

		$len = strlen($mask);
		$bin = '';
		$result = array();

		for ($cnt = $len; $cnt > 0; $cnt--)
			$bin = sprintf('%04b', hexdec($mask[$cnt - 1])) . $bin;

		$len = strlen($bin);
		for ($cnt = $len - 1; $cnt >= 0; $cnt--)
			if ($bin[$cnt] == '1')
				$result[] = $len - $cnt - 1;

		return $result;
	}

	/*
	 *  Customers functions
	 */

	function GetCustomerName($id) {
		return $this->DB->GetOne('SELECT ' . $this->DB->Concat('lastname', "' '", 'name') . ' 
			    FROM customers WHERE id=?', array($id));
	}

	function GetCustomerEmail($id) {
		return $this->DB->GetOne('SELECT email FROM customers WHERE id=?', array($id));
	}

	function CustomerExists($id) {
		switch ($this->DB->GetOne('SELECT deleted FROM customersview WHERE id=?', array($id))) {
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

	function CustomerAdd($customeradd) {
		global $SYSLOG_RESOURCE_KEYS;
		$args = array(
			'name' => lms_ucwords($customeradd['name']),
			'lastname' => $customeradd['lastname'],
			'type' => empty($customeradd['type']) ? 0 : 1,
			'address' => $customeradd['address'],
			'zip' => $customeradd['zip'],
			'city' => $customeradd['city'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY] => $customeradd['countryid'],
			'email' => $customeradd['email'],
			'ten' => $customeradd['ten'],
			'ssn' => $customeradd['ssn'],
			'status' => $customeradd['status'],
			'post_name' => $customeradd['post_name'],
			'post_address' => $customeradd['post_address'],
			'post_zip' => $customeradd['post_zip'],
			'post_city' => $customeradd['post_city'],
			'post_countryid' => $customeradd['post_countryid'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $this->AUTH->id,
			'info' => $customeradd['info'],
			'notes' => $customeradd['notes'],
			'message' => $customeradd['message'],
			'pin' => $customeradd['pin'],
			'regon' => $customeradd['regon'],
			'rbe' => $customeradd['rbe'],
			'icn' => $customeradd['icn'],
			'cutoffstop' => $customeradd['cutoffstop'],
			'consentdate' => $customeradd['consentdate'],
			'einvoice' => $customeradd['einvoice'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DIV] => $customeradd['divisionid'],
			'paytime' => $customeradd['paytime'],
			'paytype' => !empty($customeradd['paytype']) ? $customeradd['paytype'] : NULL,
			'invoicenotice' => $customeradd['invoicenotice'],
			'mailingnotice' => $customeradd['mailingnotice'],
		);
		if ($this->DB->Execute('INSERT INTO customers (name, lastname, type,
				    address, zip, city, countryid, email, ten, ssn, status, creationdate,
				    post_name, post_address, post_zip, post_city, post_countryid,
				    creatorid, info, notes, message, pin, regon, rbe,
				    icn, cutoffstop, consentdate, einvoice, divisionid, paytime, paytype,
				    invoicenotice, mailingnotice)
				    VALUES (?, UPPER(?), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?NOW?,
				    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args))
		) {
			$this->UpdateCountryState($customeradd['zip'], $customeradd['stateid']);
			if ($customeradd['post_zip'] != $customeradd['zip']) {
				$this->UpdateCountryState($customeradd['post_zip'], $customeradd['post_stateid']);
			}
			$id = $this->DB->GetLastInsertID('customers');
			if ($this->SYSLOG) {
				$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]] = $id;
				unset($args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]]);
				$this->SYSLOG->AddMessage(SYSLOG_RES_CUST, SYSLOG_OPER_ADD, $args,
					array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DIV],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY]));
			}
			return $id;
		} else
			return FALSE;
	}

	function DeleteCustomer($id) {
		global $SYSLOG_RESOURCE_KEYS;
		$this->DB->BeginTrans();

		$this->DB->Execute('UPDATE customers SET deleted=1, moddate=?NOW?, modid=?
				WHERE id=?', array($this->AUTH->id, $id));

		if ($this->SYSLOG) {
			$this->SYSLOG->AddMessage(SYSLOG_RES_CUST, SYSLOG_OPER_UPDATE, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $id, 'deleted' => 1),
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
			$assigns = $this->DB->GetAll('SELECT id, customergroupid FROM customerassignments WHERE customerid = ?', array($id));
			if (!empty($assigns))
				foreach ($assigns as $assign) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTASSIGN] => $assign['id'],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $id,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTGROUP] => $assign['customergroupid']
					);
					$this->SYSLOG->AddMessage(SYSLOG_RES_CUSTASSIGN, SYSLOG_OPER_DELETE, $args, array_keys($args));
				}
		}

		$this->DB->Execute('DELETE FROM customerassignments WHERE customerid=?', array($id));

		if ($this->SYSLOG) {
			$assigns = $this->DB->GetAll('SELECT id, tariffid, liabilityid FROM assignments WHERE customerid = ?', array($id));
			if (!empty($assigns))
				foreach ($assigns as $assign) {
					if ($assign['liabilityid']) {
						$args = array(
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB] => $assign['liabilityid'],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $id);
						$this->SYSLOG->AddMessage(SYSLOG_RES_LIAB, SYSLOG_OPER_DELETE, $args, array_keys($args));
					}
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN] => $assign['id'],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF] => $assign['tariffid'],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB] => $assign['liabilityid'],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $id
					);
					$this->SYSLOG->AddMessage(SYSLOG_RES_ASSIGN, SYSLOG_OPER_DELETE, $args, array_keys($args));
					$nodeassigns = $this->DB->GetAll('SELECT id, nodeid FROM nodeassignments WHERE assignmentid = ?', array($assign['id']));
					if (!empty($nodeassigns))
						foreach ($nodeassigns as $nodeassign) {
							$args = array(
								$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODEASSIGN] => $nodeassign['id'],
								$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $nodeassign['nodeid'],
								$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN] => $assign['id'],
								$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $id
							);
							$this->SYSLOG->AddMessage(SYSLOG_RES_NODEASSIGN, SYSLOG_OPER_DELETE, $args, array_keys($args));
						}
				}
		}
		$liabs = $this->DB->GetCol('SELECT liabilityid FROM assignments WHERE liabilityid <> 0 AND customerid = ?', array($id));
		if (!empty($liabs))
			$this->DB->Execute('DELETE FROM liabilities WHERE id IN (' . implode(',', $liabs) . ')');

		$this->DB->Execute('DELETE FROM assignments WHERE customerid=?', array($id));
		// nodes
		$nodes = $this->DB->GetCol('SELECT id FROM nodes WHERE ownerid=?', array($id));
		if ($nodes) {
			if ($this->SYSLOG) {
				$macs = $this->DB->GetAll('SELECT id, nodeid FROM macs WHERE nodeid IN (' . implode(',', $nodes) . ')');
				foreach ($macs as $mac) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_MAC] => $mac['id'],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $mac['nodeid']);
					$this->SYSLOG->AddMessage(SYSLOG_RES_MAC, SYSLOG_OPER_DELETE, $args, array_keys($args));
				}
				foreach ($nodes as $node) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $node,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $id
					);
					$this->SYSLOG->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_DELETE, $args, array_keys($args));
				}
			}

			$this->DB->Execute('DELETE FROM nodegroupassignments WHERE nodeid IN (' . join(',', $nodes) . ')');
			$plugin_data = array();
			foreach ($nodes as $node)
				$plugin_data[] = array('id' => $node, 'ownerid' => $id);
			$this->ExecHook('node_del_before', $plugin_data);
			$this->DB->Execute('DELETE FROM nodes WHERE ownerid=?', array($id));
			$this->ExecHook('node_del_after', $plugin_data);
		}
		// hosting
		$this->DB->Execute('UPDATE passwd SET ownerid=0 WHERE ownerid=?', array($id));
		$this->DB->Execute('UPDATE domains SET ownerid=0 WHERE ownerid=?', array($id));
		// Remove Userpanel rights
		if (!empty($this->CONFIG['directories']['userpanel_dir']))
			$this->DB->Execute('DELETE FROM up_rights_assignments WHERE customerid=?', array($id));

		$this->DB->CommitTrans();
	}

	function CustomerUpdate($customerdata) {
		global $SYSLOG_RESOURCE_KEYS;
		$args = array(
			'status' => $customerdata['status'],
			'type' => empty($customerdata['type']) ? 0 : 1,
			'address' => $customerdata['address'],
			'zip' => $customerdata['zip'],
			'city' => $customerdata['city'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY] => $customerdata['countryid'],
			'email' => $customerdata['email'],
			'ten' => $customerdata['ten'],
			'ssn' => $customerdata['ssn'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => isset($this->AUTH->id) ? $this->AUTH->id : 0,
			'post_name' => $customerdata['post_name'],
			'post_address' => $customerdata['post_address'],
			'post_zip' => $customerdata['post_zip'],
			'post_city' => $customerdata['post_city'],
			'post_countryid' => $customerdata['post_countryid'],
			'info' => $customerdata['info'],
			'notes' => $customerdata['notes'],
			'lastname' => $customerdata['lastname'],
			'name' => lms_ucwords($customerdata['name']),
			'message' => $customerdata['message'],
			'pin' => $customerdata['pin'],
			'regon' => $customerdata['regon'],
			'icn' => $customerdata['icn'],
			'rbe' => $customerdata['rbe'],
			'cutoffstop' => $customerdata['cutoffstop'],
			'consentdate' => $customerdata['consentdate'],
			'einvoice' => $customerdata['einvoice'],
			'invoicenotice' => $customerdata['invoicenotice'],
			'mailingnotice' => $customerdata['mailingnotice'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DIV] => $customerdata['divisionid'],
			'paytime' => $customerdata['paytime'],
			'paytype' => $customerdata['paytype'] ? $customerdata['paytype'] : null,
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerdata['id']
		);
		$res = $this->DB->Execute('UPDATE customers SET status=?, type=?, address=?,
				zip=?, city=?, countryid=?, email=?, ten=?, ssn=?, moddate=?NOW?, modid=?,
				post_name=?, post_address=?, post_zip=?, post_city=?, post_countryid=?,
				info=?, notes=?, lastname=UPPER(?), name=?,
				deleted=0, message=?, pin=?, regon=?, icn=?, rbe=?,
				cutoffstop=?, consentdate=?, einvoice=?, invoicenotice=?, mailingnotice=?,
				divisionid=?, paytime=?, paytype=?
				WHERE id=?', array_values($args));

		if ($res) {
			if ($this->SYSLOG) {
				unset($args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]]);
				$this->SYSLOG->AddMessage(SYSLOG_RES_CUST, SYSLOG_OPER_UPDATE, $args,
					array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DIV]));
			}
			$this->UpdateCountryState($customerdata['zip'], $customerdata['stateid']);
			if ($customerdata['post_zip'] != $customerdata['zip']) {
				$this->UpdateCountryState($customerdata['post_zip'], $customerdata['post_stateid']);
			}
		}

		return $res;
	}

	function GetCustomerNodesNo($id) {
		return $this->DB->GetOne('SELECT COUNT(*) FROM nodes WHERE ownerid=?', array($id));
	}

	function GetCustomerIDByIP($ipaddr) {
		return $this->DB->GetOne('SELECT ownerid FROM nodes 
			    WHERE ipaddr=inet_aton(?) OR ipaddr_pub=inet_aton(?)', array($ipaddr, $ipaddr));
	}

	function GetCashByID($id) {
		return $this->DB->GetRow('SELECT time, userid, value, taxid, customerid, comment 
			    FROM cash WHERE id=?', array($id));
	}

	function GetCustomerStatus($id) {
		return $this->DB->GetOne('SELECT status FROM customers WHERE id=?', array($id));
	}

	function GetCustomer($id, $short = false) {
		global $CONTACTTYPES;

		if ($result = $this->DB->GetRow('SELECT c.*, '
				. $this->DB->Concat('UPPER(c.lastname)', "' '", 'c.name') . ' AS customername,
			d.shortname AS division, d.account
			FROM customers' . (defined('LMS-UI') ? 'view' : '') . ' c 
			LEFT JOIN divisions d ON (d.id = c.divisionid)
			WHERE c.id = ?', array($id))) {
			if (!$short) {
				$result['createdby'] = $this->GetUserName($result['creatorid']);
				$result['modifiedby'] = $this->GetUserName($result['modid']);
				$result['creationdateh'] = date('Y/m/d, H:i', $result['creationdate']);
				$result['moddateh'] = date('Y/m/d, H:i', $result['moddate']);
				$result['consentdate'] = $result['consentdate'] ? date('Y/m/d', $result['consentdate']) : '';
				$result['up_logins'] = $this->DB->GetRow('SELECT lastlogindate, lastloginip, 
					failedlogindate, failedloginip
					FROM up_customers WHERE customerid = ?', array($result['id']));

				// Get country name
				if ($result['countryid']) {
					$result['country'] = $this->DB->GetOne('SELECT name FROM countries WHERE id = ?', array($result['countryid']));
					if ($result['countryid'] == $result['post_countryid'])
						$result['post_country'] = $result['country'];
					else if ($result['post_countryid'])
						$result['country'] = $this->DB->GetOne('SELECT name FROM countries WHERE id = ?', array($result['post_countryid']));
				}

				// Get state name
				if ($cstate = $this->DB->GetRow('SELECT s.id, s.name
					FROM states s, zipcodes
					WHERE zip = ? AND stateid = s.id', array($result['zip']))) {
					$result['stateid'] = $cstate['id'];
					$result['cstate'] = $cstate['name'];
				}
				if ($result['zip'] == $result['post_zip']) {
					$result['post_stateid'] = $result['stateid'];
					$result['post_cstate'] = $result['cstate'];
				} else if ($result['post_zip'] && ($cstate = $this->DB->GetRow('SELECT s.id, s.name
					FROM states s, zipcodes
					WHERE zip = ? AND stateid = s.id', array($result['post_zip'])))) {
					$result['post_stateid'] = $cstate['id'];
					$result['post_cstate'] = $cstate['name'];
				}
			}
			$result['balance'] = $this->GetCustomerBalance($result['id']);
			$result['bankaccount'] = bankaccount($result['id'], $result['account']);

			$result['messengers'] = $this->DB->GetAllByKey('SELECT uid, type 
					FROM imessengers WHERE customerid = ? ORDER BY type', 'type', array($result['id']));
			$result['contacts'] = $this->DB->GetAll('SELECT phone, name, type
					FROM customercontacts WHERE customerid = ? ORDER BY id', array($result['id']));

			if (is_array($result['contacts']))
				foreach ($result['contacts'] as $idx => $row) {
					$types = array();
					foreach ($CONTACTTYPES as $tidx => $tname)
						if ($row['type'] & $tidx)
							$types[] = $tname;

					if ($types)
						$result['contacts'][$idx]['typestr'] = implode('/', $types);
				}

			return $result;
		}
		else
			return FALSE;
	}

	function GetCustomerNames() {
		return $this->DB->GetAllByKey('SELECT id, ' . $this->DB->Concat('lastname', "' '", 'name') . ' AS customername 
				FROM customersview WHERE status > 1 AND deleted = 0 
				ORDER BY lastname, name', 'id');
	}

	function GetAllCustomerNames() {
		return $this->DB->GetAllByKey('SELECT id, ' . $this->DB->Concat('lastname', "' '", 'name') . ' AS customername 
				FROM customersview WHERE deleted = 0
				ORDER BY lastname, name', 'id');
	}

	function GetCustomerNodesAC($id) {
		if ($acl = $this->DB->GetALL('SELECT access FROM nodes WHERE ownerid=?', array($id))) {
			foreach ($acl as $value)
				if ($value['access'])
					$y++;
				else
					$n++;

			if ($y && !$n)
				return TRUE;
			if ($n && !$y)
				return FALSE;
		}
		if ($this->DB->GetOne('SELECT COUNT(*) FROM nodes WHERE ownerid=?', array($id)))
			return 2;
		else
			return FALSE;
	}

	function GetCustomerList($order = 'customername,asc', $state = NULL, $network = NULL, $customergroup = NULL, $search = NULL, $time = NULL, $sqlskey = 'AND', $nodegroup = NULL, $division = NULL) {
		list($order, $direction) = sscanf($order, '%[^,],%s');

		($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

		switch ($order) {
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

		switch ($state) {
			case 4:
				// When customer is deleted we have no assigned groups or nodes, see DeleteCustomer().
				// Return empty list in this case
				if (!empty($network) || !empty($customergroup) || !empty($nodegroup)) {
					$customerlist['total'] = 0;
					$customerlist['state'] = 0;
					$customerlist['order'] = $order;
					$customerlist['direction'] = $direction;
					return $customerlist;
				}
				$deleted = 1;
				break;
			case 5: $disabled = 1;
				break;
			case 6: $indebted = 1;
				break;
			case 7: $online = 1;
				break;
			case 8: $groupless = 1;
				break;
			case 9: $tariffless = 1;
				break;
			case 10: $suspended = 1;
				break;
			case 11: $indebted2 = 1;
				break;
			case 12: $indebted3 = 1;
				break;
		}

		if ($network)
			$net = $this->GetNetworkParams($network);

		$over = 0;
		$below = 0;

		if (sizeof($search))
			foreach ($search as $key => $value) {
				if ($value != '') {
					switch ($key) {
						case 'phone':
							$searchargs[] = 'EXISTS (SELECT 1 FROM customercontacts
								WHERE customerid = c.id AND phone ?LIKE? ' . $this->DB->Escape("%$value%") . ')';
							break;
						case 'zip':
						case 'city':
						case 'address':
							// UPPER here is a workaround for postgresql ILIKE bug
							$searchargs[] = "(UPPER($key) ?LIKE? UPPER(" . $this->DB->Escape("%$value%") . ")
								OR UPPER(post_$key) ?LIKE? UPPER(" . $this->DB->Escape("%$value%") . '))';
							break;
						case 'customername':
							// UPPER here is a workaround for postgresql ILIKE bug
							$searchargs[] = $this->DB->Concat('UPPER(c.lastname)', "' '", 'UPPER(c.name)') . ' ?LIKE? UPPER(' . $this->DB->Escape("%$value%") . ')';
							break;
						case 'createdfrom':
							if ($search['createdto']) {
								$searchargs['createdfrom'] = '(creationdate >= ' . intval($value)
										. ' AND creationdate <= ' . intval($search['createdto']) . ')';
								unset($search['createdto']);
							}
							else
								$searchargs[] = 'creationdate >= ' . intval($value);
							break;
						case 'createdto':
							if (!isset($searchargs['createdfrom']))
								$searchargs[] = 'creationdate <= ' . intval($value);
							break;
						case 'deletedfrom':
							if ($search['deletedto']) {
								$searchargs['deletedfrom'] = '(moddate >= ' . intval($value)
										. ' AND moddate <= ' . intval($search['deletedto']) . ')';
								unset($search['deletedto']);
							}
							else
								$searchargs[] = 'moddate >= ' . intval($value);
							$deleted = 1;
							break;
						case 'deletedto':
							if (!isset($searchargs['deletedfrom']))
								$searchargs[] = 'moddate <= ' . intval($value);
							$deleted = 1;
							break;
						case 'type':
							$searchargs[] = 'type = ' . intval($value);
							break;
						case 'linktype':
							$searchargs[] = 'EXISTS (SELECT 1 FROM nodes
								WHERE ownerid = c.id AND linktype = ' . intval($value) . ')';
							break;
						case 'linkspeed':
							$searchargs[] = 'EXISTS (SELECT 1 FROM nodes
								WHERE ownerid = c.id AND linkspeed = ' . intval($value) . ')';
							break;
						case 'doctype':
							$val = explode(':', $value); // <doctype>:<fromdate>:<todate>
							$searchargs[] = 'EXISTS (SELECT 1 FROM documents
								WHERE customerid = c.id'
									. (!empty($val[0]) ? ' AND type = ' . intval($val[0]) : '')
									. (!empty($val[1]) ? ' AND cdate >= ' . intval($val[1]) : '')
									. (!empty($val[2]) ? ' AND cdate <= ' . intval($val[2]) : '')
									. ')';
							break;
						case 'stateid':
							$searchargs[] = 'EXISTS (SELECT 1 FROM zipcodes z
								WHERE z.zip = c.zip AND z.stateid = ' . intval($value) . ')';
							break;
						case 'tariffs':
							$searchargs[] = 'EXISTS (SELECT 1 FROM assignments a 
							WHERE a.customerid = c.id
							AND (datefrom <= ?NOW? OR datefrom = 0) 
							AND (dateto >= ?NOW? OR dateto = 0)
							AND (tariffid IN (' . $value . ')))';
							break;
						default:
							$searchargs[] = "$key ?LIKE? " . $this->DB->Escape("%$value%");
					}
				}
			}

		if (isset($searchargs))
			$sqlsarg = implode(' ' . $sqlskey . ' ', $searchargs);

		$suspension_percentage = f_round($this->CONFIG['finances']['suspension_percentage']);

		if ($customerlist = $this->DB->GetAll(
				'SELECT c.id AS id, ' . $this->DB->Concat('UPPER(lastname)', "' '", 'c.name') . ' AS customername, 
				status, address, zip, city, countryid, countries.name AS country, email, ten, ssn, c.info AS info, 
				message, c.divisionid, c.paytime AS paytime, COALESCE(b.value, 0) AS balance,
				COALESCE(t.value, 0) AS tariffvalue, s.account, s.warncount, s.online,
				(CASE WHEN s.account = s.acsum THEN 1
					WHEN s.acsum > 0 THEN 2	ELSE 0 END) AS nodeac,
				(CASE WHEN s.warncount = s.warnsum THEN 1
					WHEN s.warnsum > 0 THEN 2 ELSE 0 END) AS nodewarn
				FROM customersview c
				LEFT JOIN countries ON (c.countryid = countries.id) '
				. ($customergroup ? 'LEFT JOIN customerassignments ON (c.id = customerassignments.customerid) ' : '')
				. 'LEFT JOIN (SELECT
					SUM(value) AS value, customerid
					FROM cash'
				. ($time ? ' WHERE time < ' . $time : '') . '
					GROUP BY customerid
				) b ON (b.customerid = c.id)
				LEFT JOIN (SELECT a.customerid,
					SUM((CASE a.suspended
						WHEN 0 THEN (((100 - a.pdiscount) * (CASE WHEN t.value IS NULL THEN l.value ELSE t.value END) / 100) - a.vdiscount)
						ELSE ((((100 - a.pdiscount) * (CASE WHEN t.value IS NULL THEN l.value ELSE t.value END) / 100) - a.vdiscount) * ' . $suspension_percentage . ' / 100) END)
					* (CASE t.period
						WHEN ' . MONTHLY . ' THEN 1
						WHEN ' . YEARLY . ' THEN 1/12.0
						WHEN ' . HALFYEARLY . ' THEN 1/6.0
						WHEN ' . QUARTERLY . ' THEN 1/3.0
						ELSE (CASE a.period
						    WHEN ' . MONTHLY . ' THEN 1
						    WHEN ' . YEARLY . ' THEN 1/12.0
						    WHEN ' . HALFYEARLY . ' THEN 1/6.0
						    WHEN ' . QUARTERLY . ' THEN 1/3.0
						    ELSE 0 END)
						END)
					) AS value 
					FROM assignments a
					LEFT JOIN tariffs t ON (t.id = a.tariffid)
					LEFT JOIN liabilities l ON (l.id = a.liabilityid AND a.period != ' . DISPOSABLE . ')
					WHERE (a.datefrom <= ?NOW? OR a.datefrom = 0) AND (a.dateto > ?NOW? OR a.dateto = 0) 
					GROUP BY a.customerid
				) t ON (t.customerid = c.id)
				LEFT JOIN (SELECT ownerid,
					SUM(access) AS acsum, COUNT(access) AS account,
					SUM(warning) AS warnsum, COUNT(warning) AS warncount, 
					(CASE WHEN MAX(lastonline) > ?NOW? - ' . intval($this->CONFIG['phpui']['lastonline_limit']) . '
						THEN 1 ELSE 0 END) AS online
					FROM nodes
					WHERE ownerid > 0
					GROUP BY ownerid
				) s ON (s.ownerid = c.id)
				WHERE c.deleted = ' . intval($deleted)
				. ($state <= 3 && $state > 0 ? ' AND c.status = ' . intval($state) : '')
				. ($division ? ' AND c.divisionid = ' . intval($division) : '')
				. ($online ? ' AND s.online = 1' : '')
				. ($indebted ? ' AND b.value < 0' : '')
				. ($indebted2 ? ' AND b.value < -t.value' : '')
				. ($indebted3 ? ' AND b.value < -t.value * 2' : '')
				. ($disabled ? ' AND s.ownerid IS NOT NULL AND s.account > s.acsum' : '')
				. ($network ? ' AND EXISTS (SELECT 1 FROM nodes WHERE ownerid = c.id 
					AND (netid = ' . $network . '
					OR (ipaddr_pub > ' . $net['address'] . ' AND ipaddr_pub < ' . $net['broadcast'] . ')))' : '')
				. ($customergroup ? ' AND customergroupid=' . intval($customergroup) : '')
				. ($nodegroup ? ' AND EXISTS (SELECT 1 FROM nodegroupassignments na
							JOIN nodes n ON (n.id = na.nodeid) 
							WHERE n.ownerid = c.id AND na.nodegroupid = ' . intval($nodegroup) . ')' : '')
				. ($groupless ? ' AND NOT EXISTS (SELECT 1 FROM customerassignments a 
							WHERE c.id = a.customerid)' : '')
				. ($tariffless ? ' AND NOT EXISTS (SELECT 1 FROM assignments a 
							WHERE a.customerid = c.id
								AND (datefrom <= ?NOW? OR datefrom = 0) 
								AND (dateto >= ?NOW? OR dateto = 0)
								AND (tariffid != 0 OR liabilityid != 0))' : '')
				. ($suspended ? ' AND EXISTS (SELECT 1 FROM assignments a
							WHERE a.customerid = c.id AND (
								(tariffid = 0 AND liabilityid = 0
								    AND (datefrom <= ?NOW? OR datefrom = 0)
								    AND (dateto >= ?NOW? OR dateto = 0)) 
								OR ((datefrom <= ?NOW? OR datefrom = 0)
								    AND (dateto >= ?NOW? OR dateto = 0)
								    AND suspended = 1)
								))' : '')
				. (isset($sqlsarg) ? ' AND (' . $sqlsarg . ')' : '')
				. ($sqlord != '' ? $sqlord . ' ' . $direction : '')
		)) {
			foreach ($customerlist as $idx => $row) {
				// summary
				if ($row['balance'] > 0)
					$over += $row['balance'];
				elseif ($row['balance'] < 0)
					$below += $row['balance'];
			}
		}

		$customerlist['total'] = sizeof($customerlist);
		$customerlist['state'] = $state;
		$customerlist['order'] = $order;
		$customerlist['direction'] = $direction;
		$customerlist['below'] = $below;
		$customerlist['over'] = $over;

		return $customerlist;
	}

	function GetCustomerNodes($id, $count = NULL) {
		if ($result = $this->DB->GetAll('SELECT id, name, mac, ipaddr,
				inet_ntoa(ipaddr) AS ip, ipaddr_pub,
				inet_ntoa(ipaddr_pub) AS ip_pub, passwd, access,
				warning, info, ownerid, lastonline, location,
				(SELECT COUNT(*) FROM nodegroupassignments
					WHERE nodeid = vnodes.id) AS gcount
				FROM vnodes
				WHERE ownerid = ?
				ORDER BY name ASC ' . ($count ? 'LIMIT ' . $count : ''), array($id))) {
			// assign network(s) to node record
			$networks = (array) $this->GetNetworks();

			foreach ($result as $idx => $node) {
				$ids[$node['id']] = $idx;
				$result[$idx]['lastonlinedate'] = lastonline_date($node['lastonline']);

				foreach ($networks as $net)
					if (isipin($node['ip'], $net['address'], $net['mask'])) {
						$result[$idx]['network'] = $net;
						break;
					}

				if ($node['ipaddr_pub'])
					foreach ($networks as $net)
						if (isipin($node['ip_pub'], $net['address'], $net['mask'])) {
							$result[$idx]['network_pub'] = $net;
							break;
						}
			}

			// get EtherWerX channels
			if (chkconfig($this->CONFIG['phpui']['ewx_support'])) {
				$channels = $this->DB->GetAllByKey('SELECT nodeid, channelid, c.name, c.id, cid,
				        nc.upceil, nc.downceil
					FROM ewx_stm_nodes
					JOIN ewx_stm_channels nc ON (channelid = nc.id)
					LEFT JOIN ewx_channels c ON (c.id = nc.cid)
					WHERE nodeid IN (' . implode(',', array_keys($ids)) . ')', 'nodeid');

				if ($channels)
					foreach ($channels as $channel) {
						$idx = $ids[$channel['nodeid']];
						$result[$idx]['channelid'] = $channel['id'] ? $channel['id'] : $channel['channelid'];
						$result[$idx]['channelname'] = $channel['name'];
						$result[$idx]['cid'] = $channel['cid'];
						$result[$idx]['downceil'] = $channel['downceil'];
						$result[$idx]['upceil'] = $channel['upceil'];
					}
			}
		}
		return $result;
	}

	/* added balance totime - tcpdf invoice */

	function GetCustomerBalance($id, $totime = NULL) {
		return $this->DB->GetOne('SELECT SUM(value) FROM cash WHERE customerid = ?' . ($totime ? ' AND time < ' . intval($totime) : ''), array($id));
	}

	function GetCustomerBalanceList($id, $totime = NULL, $direction = 'ASC') {
		($direction == 'ASC' || $direction == 'asc') ? $direction == 'ASC' : $direction == 'DESC';

		$saldolist = array();

		if ($tslist = $this->DB->GetAll('SELECT cash.id AS id, time, cash.type AS type, 
					cash.value AS value, taxes.label AS tax, cash.customerid AS customerid, 
					comment, docid, users.name AS username,
					documents.type AS doctype, documents.closed AS closed
					FROM cash
					LEFT JOIN users ON users.id = cash.userid
					LEFT JOIN documents ON documents.id = docid
					LEFT JOIN taxes ON cash.taxid = taxes.id
					WHERE cash.customerid = ?'
				. ($totime ? ' AND time <= ' . intval($totime) : '')
				. ' ORDER BY time ' . $direction, array($id))) {
			$saldolist['balance'] = 0;
			$saldolist['total'] = 0;
			$i = 0;

			foreach ($tslist as $row) {
				// old format wrapper
				foreach ($row as $column => $value)
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

	function CustomerStats() {
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

	function CustomergroupWithCustomerGet($id) {
		return $this->DB->GetOne('SELECT COUNT(*) FROM customerassignments
				WHERE customergroupid = ?', array($id));
	}

	function CustomergroupAdd($customergroupdata) {
		global $SYSLOG_RESOURCE_KEYS;
		if ($this->DB->Execute('INSERT INTO customergroups (name, description) VALUES (?, ?)', array($customergroupdata['name'], $customergroupdata['description']))) {
			$id = $this->DB->GetLastInsertID('customergroups');
			if ($this->SYSLOG) {
				$args = array(
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTGROUP] => $id,
					'name' => $customergroupdata['name'],
					'description' => $customergroupdata['description']
				);
				$this->SYSLOG->AddMessage(SYSLOG_RES_CUSTGROUP, SYSLOG_OPER_ADD, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTGROUP]));
			}
			return $id;
		}
		else
			return FALSE;
	}

	function CustomergroupUpdate($customergroupdata) {
		global $SYSLOG_RESOURCE_KEYS;
		$args = array(
			'name' => $customergroupdata['name'],
			'description' => $customergroupdata['description'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTGROUP] => $customergroupdata['id']
		);
		if ($this->SYSLOG)
			$this->SYSLOG->AddMessage(SYSLOG_RES_CUSTGROUP, SYSLOG_OPER_UPDATE,
				$args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTGROUP]));
		return $this->DB->Execute('UPDATE customergroups SET name=?, description=? 
				WHERE id=?', array_values($args));
	}

	function CustomergroupDelete($id) {
		global $SYSLOG_RESOURCE_KEYS;
		if (!$this->CustomergroupWithCustomerGet($id)) {
			if ($this->SYSLOG) {
				$custassigns = $this->DB->Execute('SELECT id, customerid, customergroupid FROM customerassignments
					WHERE customergroupid = ?', array($id));
				if (!empty($custassigns))
					foreach ($custassigns as $custassign) {
						$args = array(
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTASSIGN] => $custassign['id'],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $custassign['customerid'],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTGROUP] => $custassign['customergroupid']
						);
						$this->SYSLOG->AddMessage(SYSLOG_RES_CUSTASSIGN, SYSLOG_OPER_DELETE, $args, array_keys($args));
					}
				$this->SYSLOG->AddMessage(SYSLOG_RES_CUSTGROUP, SYSLOG_OPER_DELETE,
					array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTGROUP] => $id), array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTGROUP]));
			}
			$this->DB->Execute('DELETE FROM customergroups WHERE id=?', array($id));
			return TRUE;
		}
		else
			return FALSE;
	}

	function CustomergroupExists($id) {
		return ($this->DB->GetOne('SELECT id FROM customergroups WHERE id=?', array($id)) ? TRUE : FALSE);
	}

	function CustomergroupGetId($name) {
		return $this->DB->GetOne('SELECT id FROM customergroups WHERE name=?', array($name));
	}

	function CustomergroupGetName($id) {
		return $this->DB->GetOne('SELECT name FROM customergroups WHERE id=?', array($id));
	}

	function CustomergroupGetAll() {
		return $this->DB->GetAll('SELECT g.id, g.name, g.description 
				FROM customergroups g
				WHERE NOT EXISTS (
					SELECT 1 FROM excludedgroups 
					WHERE userid = lms_current_user() AND customergroupid = g.id) 
				ORDER BY g.name ASC');
	}

	function CustomergroupGet($id, $network = NULL) {
		if ($network)
			$net = $this->GetNetworkParams($network);

		$result = $this->DB->GetRow('SELECT id, name, description 
			FROM customergroups WHERE id=?', array($id));

		$result['customers'] = $this->DB->GetAll('SELECT c.id AS id,'
				. $this->DB->Concat('c.lastname', "' '", 'c.name') . ' AS customername 
			FROM customerassignments, customers c '
				. ($network ? 'LEFT JOIN nodes ON c.id = nodes.ownerid ' : '')
				. 'WHERE c.id = customerid AND customergroupid = ? '
				. ($network ? 'AND ((ipaddr > ' . $net['address'] . ' AND ipaddr < ' . $net['broadcast'] . ') OR
			(ipaddr_pub > ' . $net['address'] . ' AND ipaddr_pub < ' . $net['broadcast'] . ')) ' : '')
				. ' GROUP BY c.id, c.lastname, c.name ORDER BY c.lastname, c.name', array($id));

		$result['customerscount'] = sizeof($result['customers']);
		$result['count'] = $network ? $this->CustomergroupWithCustomerGet($id) : $result['customerscount'];

		return $result;
	}

	function CustomergroupGetList() {
		if ($customergrouplist = $this->DB->GetAll('SELECT id, name, description,
				(SELECT COUNT(*)
					FROM customerassignments 
					WHERE customergroupid = customergroups.id
				) AS customerscount
				FROM customergroups ORDER BY name ASC')) {
			$totalcount = 0;

			foreach ($customergrouplist as $idx => $row) {
				$totalcount += $row['customerscount'];
			}

			$customergrouplist['total'] = sizeof($customergrouplist);
			$customergrouplist['totalcount'] = $totalcount;
		}

		return $customergrouplist;
	}

	function CustomergroupGetForCustomer($id) {
		return $this->DB->GetAll('SELECT customergroups.id AS id, name, description 
			    FROM customergroups, customerassignments 
			    WHERE customergroups.id=customergroupid AND customerid=? 
			    ORDER BY name ASC', array($id));
	}

	function GetGroupNamesWithoutCustomer($customerid) {
		return $this->DB->GetAll('SELECT customergroups.id AS id, name, customerid
			FROM customergroups 
			LEFT JOIN customerassignments ON (customergroups.id=customergroupid AND customerid = ?)
			GROUP BY customergroups.id, name, customerid 
			HAVING customerid IS NULL ORDER BY name', array($customerid));
	}

	function CustomerassignmentGetForCustomer($id) {
		return $this->DB->GetAll('SELECT customerassignments.id AS id, customergroupid, customerid 
			FROM customerassignments, customergroups 
			WHERE customerid=? AND customergroups.id = customergroupid 
			ORDER BY customergroupid ASC', array($id));
	}

	function CustomerassignmentDelete($customerassignmentdata) {
		global $SYSLOG_RESOURCE_KEYS;
		if ($this->SYSLOG) {
			$assign = $this->DB->GetRow('SELECT id, customerid FROM customerassignments
				WHERE customergroupid = ? AND customerid = ?', array($customerassignmentdata['customergroupid'],
							$customerassignmentdata['customerid']));
			if ($assign) {
				$args = array(
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTASSIGN] => $assign['id'],
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $assign['customerid'],
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTGROUP] => $customerassignmentdata['customergroupid']
				);
				$this->SYSLOG->AddMessage(SYSLOG_RES_CUSTASSIGN, SYSLOG_OPER_DELETE, $args, array_keys($args));
			}
		}
		return $this->DB->Execute('DELETE FROM customerassignments 
			WHERE customergroupid=? AND customerid=?', array($customerassignmentdata['customergroupid'],
						$customerassignmentdata['customerid']));
	}

	function CustomerassignmentAdd($customerassignmentdata) {
		global $SYSLOG_RESOURCE_KEYS;
		$res = $this->DB->Execute('INSERT INTO customerassignments (customergroupid, customerid) VALUES (?, ?)', array($customerassignmentdata['customergroupid'],
						$customerassignmentdata['customerid']));
		if ($this->SYSLOG && $res) {
			$id = $this->DB->GetLastInsertID('customerassignments');
			$args = array(
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTASSIGN] => $id,
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerassignmentdata['customerid'],
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTGROUP] => $customerassignmentdata['customergroupid']
			);
			$this->SYSLOG->AddMessage(SYSLOG_RES_CUSTASSIGN, SYSLOG_OPER_ADD, $args, array_keys($args));
		}
		return $res;
	}

	function CustomerassignmentExist($groupid, $customerid) {
		return $this->DB->GetOne('SELECT 1 FROM customerassignments WHERE customergroupid=? AND customerid=?', array($groupid, $customerid));
	}

	function GetCustomerWithoutGroupNames($groupid, $network = NULL) {
		if ($network)
			$net = $this->GetNetworkParams($network);

		return $this->DB->GetAll('SELECT c.id AS id, ' . $this->DB->Concat('c.lastname', "' '", 'c.name') . ' AS customername
			FROM customersview c '
						. ($network ? 'LEFT JOIN nodes ON (c.id = nodes.ownerid) ' : '')
						. 'WHERE c.deleted = 0 AND c.id NOT IN (
				SELECT customerid FROM customerassignments WHERE customergroupid = ?) '
						. ($network ? 'AND ((ipaddr > ' . $net['address'] . ' AND ipaddr < ' . $net['broadcast'] . ') OR (ipaddr_pub > '
								. $net['address'] . ' AND ipaddr_pub < ' . $net['broadcast'] . ')) ' : '')
						. 'GROUP BY c.id, c.lastname, c.name
			ORDER BY c.lastname, c.name', array($groupid));
	}

	/*
	 *  Nodes functions
	 */

	function GetNodeOwner($id) {
		return $this->DB->GetOne('SELECT ownerid FROM nodes WHERE id=?', array($id));
	}

	function NodeUpdate($nodedata, $deleteassignments = FALSE) {
		global $SYSLOG_RESOURCE_KEYS;
		$args = array(
			'name' => $nodedata['name'],
			'ipaddr_pub' => $nodedata['ipaddr_pub'],
			'ipaddr' => $nodedata['ipaddr'],
			'passwd' => $nodedata['passwd'],
			'netdev' => $nodedata['netdev'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $this->AUTH->id,
			'access' => $nodedata['access'],
			'warning' => $nodedata['warning'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $nodedata['ownerid'],
			'info' => $nodedata['info'],
			'location' => $nodedata['location'],
			'location_city' => $nodedata['location_city'] ? $nodedata['location_city'] : null,
			'location_street' => $nodedata['location_street'] ? $nodedata['location_street'] : null,
			'location_house' => $nodedata['location_house'] ? $nodedata['location_house'] : null,
			'location_flat' => $nodedata['location_flat'] ? $nodedata['location_flat'] : null,
			'chkmac' => $nodedata['chkmac'],
			'halfduplex' => $nodedata['halfduplex'],
			'linktype' => isset($nodedata['linktype']) ? intval($nodedata['linktype']) : 0,
			'linkspeed' => isset($nodedata['linkspeed']) ? intval($nodedata['linkspeed']) : 100000,
			'port' => isset($nodedata['port']) && $nodedata['netdev'] ? intval($nodedata['port']) : 0,
			'nas' => isset($nodedata['nas']) ? $nodedata['nas'] : 0,
			'longitude' => !empty($nodedata['longitude']) ? str_replace(',', '.', $nodedata['longitude']) : null,
			'latitude' => !empty($nodedata['latitude']) ? str_replace(',', '.', $nodedata['latitude']) : null,
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $nodedata['id']
		);
		$this->DB->Execute('UPDATE nodes SET name=UPPER(?), ipaddr_pub=inet_aton(?),
				ipaddr=inet_aton(?), passwd=?, netdev=?, moddate=?NOW?,
				modid=?, access=?, warning=?, ownerid=?, info=?, location=?,
				location_city=?, location_street=?, location_house=?, location_flat=?,
				chkmac=?, halfduplex=?, linktype=?, linkspeed=?, port=?, nas=?,
				longitude=?, latitude=? 
				WHERE id=?', array_values($args));

		if ($this->SYSLOG) {
			unset($args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]]);
			$this->SYSLOG->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));

			$macs = $this->DB->GetAll('SELECT id, nodeid FROM macs WHERE nodeid = ?', array($nodedata['id']));
			if (!empty($macs))
				foreach ($macs as $mac) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_MAC] => $mac['id'],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $mac['nodeid'],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $nodedata['ownerid']
					);
					$this->SYSLOG->AddMessage(SYSLOG_RES_MAC, SYSLOG_OPER_DELETE, $args, array_keys($args));
				}
		}
		$this->DB->Execute('DELETE FROM macs WHERE nodeid=?', array($nodedata['id']));
		foreach ($nodedata['macs'] as $mac) {
			$this->DB->Execute('INSERT INTO macs (mac, nodeid) VALUES(?, ?)', array(strtoupper($mac), $nodedata['id']));
			if ($this->SYSLOG) {
				$macid = $this->DB->GetLastInsertID('macs');
				$args = array(
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_MAC] => $macid,
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $nodedata['id'],
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $nodedata['ownerid'],
					'mac' => strtoupper($mac)
				);
				$this->SYSLOG->AddMessage(SYSLOG_RES_MAC, SYSLOG_OPER_ADD, $args,
					array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_MAC],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
			}
		}

		if ($deleteassignments) {
			if ($this->SYSLOG) {
				$nodeassigns = $this->DB->GetAll('SELECT id, nodeid, assignmentid FROM nodeassignments
					WHERE nodeid = ?', array($nodedata['id']));
				if (!empty($nodeassigns))
					foreach ($nodeassigns as $nodeassign) {
						$args = array(
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODEASSIGN] => $nodeassign['id'],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $nodedata['id'],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN] => $nodedata['assignmentid'],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $nodedata['ownerid']
						);
						$this->SYSLOG->AddMessage(SYSLOG_RES_NODEASSIGN, SYSLOG_OPER_DELETE, $args, array_keys($args));
					}
			}
			$this->DB->Execute('DELETE FROM nodeassignments WHERE nodeid = ?', array($nodedata['id']));
		}
	}

	function DeleteNode($id) {
		global $SYSLOG_RESOURCE_KEYS;
		$this->DB->BeginTrans();

		if ($this->SYSLOG) {
			$customerid = $this->DB->GetOne('SELECT ownerid FROM nodes WHERE id = ?', array($id));
			$macs = $this->DB->GetCol('SELECT macid FROM vmacs WHERE id = ?', array($id));
			if (!empty($macs))
				foreach ($macs as $mac) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_MAC] => $mac,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $id,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid
					);
					$this->SYSLOG->AddMessage(SYSLOG_RES_MAC, SYSLOG_OPER_DELETE, $args, array_keys($args));
				}
			$args = array(
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $id,
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid
			);
			$this->SYSLOG->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_DELETE, $args, array_keys($args));
		}

		$this->DB->Execute('DELETE FROM nodes WHERE id = ?', array($id));
		$this->DB->Execute('DELETE FROM nodegroupassignments WHERE nodeid = ?', array($id));
		$this->DB->CommitTrans();
	}

	function GetNodeNameByMAC($mac) {
		return $this->DB->GetOne('SELECT name FROM vnodes WHERE mac=UPPER(?)', array($mac));
	}

	function GetNodeIDByIP($ipaddr) {
		return $this->DB->GetOne('SELECT id FROM nodes WHERE ipaddr=inet_aton(?) OR ipaddr_pub=inet_aton(?)', array($ipaddr, $ipaddr));
	}

	function GetNodeIDByMAC($mac) {
		return $this->DB->GetOne('SELECT nodeid FROM macs WHERE mac=UPPER(?)', array($mac));
	}

	function GetNodeIDByName($name) {
		return $this->DB->GetOne('SELECT id FROM nodes WHERE name=UPPER(?)', array($name));
	}

	function GetNodeIPByID($id) {
		return $this->DB->GetOne('SELECT inet_ntoa(ipaddr) FROM nodes WHERE id=?', array($id));
	}

	function GetNodePubIPByID($id) {
		return $this->DB->GetOne('SELECT inet_ntoa(ipaddr_pub) FROM nodes WHERE id=?', array($id));
	}

	function GetNodeMACByID($id) {
		return $this->DB->GetOne('SELECT mac FROM vnodes WHERE id=?', array($id));
	}

	function GetNodeName($id) {
		return $this->DB->GetOne('SELECT name FROM nodes WHERE id=?', array($id));
	}

	function GetNodeNameByIP($ipaddr) {
		return $this->DB->GetOne('SELECT name FROM nodes WHERE ipaddr=inet_aton(?) OR ipaddr_pub=inet_aton(?)', array($ipaddr, $ipaddr));
	}

	function GetNode($id) {
		if ($result = $this->DB->GetRow('SELECT n.*,
		    inet_ntoa(n.ipaddr) AS ip, inet_ntoa(n.ipaddr_pub) AS ip_pub,
		    lc.name AS city_name,
				(CASE WHEN ls.name2 IS NOT NULL THEN ' . $this->DB->Concat('ls.name2', "' '", 'ls.name') . ' ELSE ls.name END) AS street_name, lt.name AS street_type
			FROM vnodes n
			LEFT JOIN location_cities lc ON (lc.id = n.location_city)
			LEFT JOIN location_streets ls ON (ls.id = n.location_street)
			LEFT JOIN location_street_types lt ON (lt.id = ls.typeid)
			WHERE n.id = ?', array($id))
		) {
			$result['owner'] = $this->GetCustomerName($result['ownerid']);
			$result['createdby'] = $this->GetUserName($result['creatorid']);
			$result['modifiedby'] = $this->GetUserName($result['modid']);
			$result['creationdateh'] = date('Y/m/d, H:i', $result['creationdate']);
			$result['moddateh'] = date('Y/m/d, H:i', $result['moddate']);
			$result['lastonlinedate'] = lastonline_date($result['lastonline']);

			$result['mac'] = preg_split('/,/', $result['mac']);
			foreach ($result['mac'] as $mac)
				$result['macs'][] = array('mac' => $mac, 'producer' => get_producer($mac));
			unset($result['mac']);

			if ($net = $this->DB->GetRow('SELECT id, name FROM networks
				WHERE address = (inet_aton(?) & inet_aton(mask))', array($result['ip']))) {
				$result['netid'] = $net['id'];
				$result['netname'] = $net['name'];
			}

			return $result;
		} else
			return FALSE;
	}

	function GetNodeList($order = 'name,asc', $search = NULL, $sqlskey = 'AND', $network = NULL, $status = NULL, $customergroup = NULL, $nodegroup = NULL) {
		if ($order == '')
			$order = 'name,asc';

		list($order, $direction) = sscanf($order, '%[^,],%s');

		($direction == 'desc') ? $direction = 'desc' : $direction = 'asc';

		switch ($order) {
			case 'name':
				$sqlord = ' ORDER BY n.name';
				break;
			case 'id':
				$sqlord = ' ORDER BY n.id';
				break;
			case 'mac':
				$sqlord = ' ORDER BY n.mac';
				break;
			case 'ip':
				$sqlord = ' ORDER BY n.ipaddr';
				break;
			case 'ip_pub':
				$sqlord = ' ORDER BY n.ipaddr_pub';
				break;
			case 'ownerid':
				$sqlord = ' ORDER BY n.ownerid';
				break;
			case 'owner':
				$sqlord = ' ORDER BY owner';
				break;
		}

		if (sizeof($search))
			foreach ($search as $idx => $value) {
				if ($value != '') {
					switch ($idx) {
						case 'ipaddr':
							$searchargs[] = '(inet_ntoa(n.ipaddr) ?LIKE? ' . $this->DB->Escape('%' . trim($value) . '%')
									. ' OR inet_ntoa(n.ipaddr_pub) ?LIKE? ' . $this->DB->Escape('%' . trim($value) . '%') . ')';
							break;
						case 'state':
							if ($value != '0')
								$searchargs[] = 'n.location_city IN (SELECT lc.id FROM location_cities lc 
								JOIN location_boroughs lb ON lb.id = lc.boroughid 
								JOIN location_districts ld ON ld.id = lb.districtid 
								JOIN location_states ls ON ls.id = ld.stateid WHERE ls.id = ' . $this->DB->Escape($value) . ')';
							break;
						case 'district':
							if ($value != '0')
								$searchargs[] = 'n.location_city IN (SELECT lc.id FROM location_cities lc 
								JOIN location_boroughs lb ON lb.id = lc.boroughid 
								JOIN location_districts ld ON ld.id = lb.districtid WHERE ld.id = ' . $this->DB->Escape($value) . ')';
							break;
						case 'borough':
							if ($value != '0')
								$searchargs[] = 'n.location_city IN (SELECT lc.id FROM location_cities lc WHERE lc.boroughid = '
										. $this->DB->Escape($value) . ')';
							break;
						default:
							$searchargs[] = 'n.' . $idx . ' ?LIKE? ' . $this->DB->Escape("%$value%");
					}
				}
			}

		if (isset($searchargs))
			$searchargs = ' AND (' . implode(' ' . $sqlskey . ' ', $searchargs) . ')';

		$totalon = 0;
		$totaloff = 0;

		if ($network)
			$net = $this->GetNetworkParams($network);

		if ($nodelist = $this->DB->GetAll('SELECT n.id AS id, n.ipaddr, inet_ntoa(n.ipaddr) AS ip, ipaddr_pub,
				inet_ntoa(n.ipaddr_pub) AS ip_pub, n.mac, n.name, n.ownerid, n.access, n.warning,
				n.netdev, n.lastonline, n.info, '
				. $this->DB->Concat('c.lastname', "' '", 'c.name') . ' AS owner
				FROM vnodes n
				JOIN customersview c ON (n.ownerid = c.id) '
				. ($customergroup ? 'JOIN customerassignments ON (customerid = c.id) ' : '')
				. ($nodegroup ? 'JOIN nodegroupassignments ON (nodeid = n.id) ' : '')
				. ' WHERE 1=1 '
				. ($network ? ' AND (n.netid = ' . $network . '
					OR (n.ipaddr_pub > ' . $net['address'] . ' AND n.ipaddr_pub < ' . $net['broadcast'] . '))' : '')
				. ($status == 1 ? ' AND n.access = 1' : '') //connected
				. ($status == 2 ? ' AND n.access = 0' : '') //disconnected
				. ($status == 3 ? ' AND n.lastonline > ?NOW? - ' . intval($this->CONFIG['phpui']['lastonline_limit']) : '') //online
				. ($customergroup ? ' AND customergroupid = ' . intval($customergroup) : '')
				. ($nodegroup ? ' AND nodegroupid = ' . intval($nodegroup) : '')
				. (isset($searchargs) ? $searchargs : '')
				. ($sqlord != '' ? $sqlord . ' ' . $direction : ''))) {
			foreach ($nodelist as $idx => $row) {
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

	function NodeSet($id, $access = -1) {
		global $SYSLOG_RESOURCE_KEYS;
		$keys = array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]);
		$customerid = $this->DB->GetOne('SELECT ownerid FROM nodes WHERE id = ?', array($id));
		$args = array(
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $id,
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid
		);

		if ($access != -1) {
			$args['access'] = $access;
			if ($access) {
				if ($this->DB->GetOne('SELECT 1 FROM nodes WHERE id = ? AND EXISTS
					(SELECT 1 FROM customers WHERE id = ownerid AND status = 3)', array($id))) {
					if ($this->SYSLOG)
						$this->SYSLOG->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE,
							$args, $keys);
					return $this->DB->Execute('UPDATE nodes SET access = 1 WHERE id = ?
						AND EXISTS (SELECT 1 FROM customers WHERE id = ownerid 
							AND status = 3)', array($id));
				}
				return 0;
			} else {
				if ($this->SYSLOG)
					$this->SYSLOG->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args, $keys);
				return $this->DB->Execute('UPDATE nodes SET access = 0 WHERE id = ?', array($id));
			}
		}
		elseif ($this->DB->GetOne('SELECT access FROM nodes WHERE id = ?', array($id)) == 1) {
			if ($this->SYSLOG) {
				$args['access'] = 0;
				$this->SYSLOG->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args, $keys);
			}
			return $this->DB->Execute('UPDATE nodes SET access=0 WHERE id = ?', array($id));
		} else {
			if ($this->DB->GetOne('SELECT 1 FROM nodes WHERE id = ? AND EXISTS
				(SELECT 1 FROM customers WHERE id = ownerid AND status = 3)', array($id))) {
				if ($this->SYSLOG) {
					$args['access'] = 1;
					$this->SYSLOG->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args, $keys);
				}
				return $this->DB->Execute('UPDATE nodes SET access = 1 WHERE id = ?
						AND EXISTS (SELECT 1 FROM customers WHERE id = ownerid 
							AND status = 3)', array($id));
			}
			return 0;
		}
	}

	function NodeSetU($id, $access = FALSE) {
		global $SYSLOG_RESOURCE_KEYS;
		$keys = array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]);

		if ($access) {
			if ($this->DB->GetOne('SELECT status FROM customers WHERE id = ?', array($id)) == 3) {
				if ($this->SYSLOG) {
					$nodes = $this->DB->GetCol('SELECT id FROM nodes WHERE ownerid = ?', array($id));
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $id,
						'access' => $access
					);
					if (!empty($nodes))
						foreach ($nodes as $nodeid) {
							$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE]] = $nodeid;
							$this->SYSLOG->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args, $keys);
						}
				}
				return $this->DB->Execute('UPDATE nodes SET access=1 WHERE ownerid=?', array($id));
			}
		}
		else {
			if ($this->SYSLOG) {
				$nodes = $this->DB->GetCol('SELECT id FROM nodes WHERE ownerid = ?', array($id));
				$args = array(
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $id,
					'access' => $access
				);
				if (!empty($nodes))
					foreach ($nodes as $nodeid) {
						$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE]] = $nodeid;
						$this->SYSLOG->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args, $keys);
					}
			}
			return $this->DB->Execute('UPDATE nodes SET access=0 WHERE ownerid=?', array($id));
		}
	}

	function NodeSetWarn($id, $warning = FALSE) {
		global $SYSLOG_RESOURCE_KEYS;
		if ($this->SYSLOG) {
			$cids = $this->DB->GetAll('SELECT id, ownerid FROM nodes WHERE id IN ('
				. (is_array($id) ? implode(',', $id) : $id) . ')');
			if (!empty($cids))
				foreach ($cids as $cid) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $cid['id'],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $cid['ownerid'],
						'warning' => $warning
					);
					$this->SYSLOG->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args,
						array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
				}
		}
		return $this->DB->Execute('UPDATE nodes SET warning = ? WHERE id IN ('
			. (is_array($id) ? implode(',', $id) : $id) . ')', array($warning ? 1 : 0));
	}

	function NodeSwitchWarn($id) {
		global $SYSLOG_RESOURCE_KEYS;
		if ($this->SYSLOG) {
			$node = $this->DB->GetRow('SELECT ownerid, warning FROM nodes WHERE id = ?', array($id));
			$args = array(
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $id,
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $node['ownerid'],
				'warning' => ($node['warning'] ? 0 : 1)
			);
			$this->SYSLOG->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
		}
		return $this->DB->Execute('UPDATE nodes 
			SET warning = (CASE warning WHEN 0 THEN 1 ELSE 0 END)
			WHERE id = ?', array($id));
	}

	function NodeSetWarnU($id, $warning = FALSE) {
		global $SYSLOG_RESOURCE_KEYS;
		if ($this->SYSLOG) {
			$nodes = $this->DB->GetAll('SELECT id, ownerid FROM nodes WHERE ownerid IN ('
				. (is_array($id) ? implode(',', $id) : $id) . ')');
			if (!empty($nodes))
				foreach ($nodes as $node) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $node['id'],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $node['ownerid'],
						'warning' => $warning
					);
					$this->SYSLOG->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args,
						array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
				}
		}
		return $this->DB->Execute('UPDATE nodes SET warning = ? WHERE ownerid IN ('
			. (is_array($id) ? implode(',', $id) : $id) . ')', array($warning ? 1 : 0));
	}

	function IPSetU($netdev, $access = FALSE) {
		if ($access)
			return $this->DB->Execute('UPDATE nodes SET access=1 WHERE netdev=? AND ownerid=0', array($netdev));
		else
			return $this->DB->Execute('UPDATE nodes SET access=0 WHERE netdev=? AND ownerid=0', array($netdev));
	}

	function NodeAdd($nodedata) {
		global $SYSLOG_RESOURCE_KEYS;
		$args = array(
			'name' => strtoupper($nodedata['name']),
			'ipaddr' => $nodedata['ipaddr'],
			'ipaddr_pub' => $nodedata['ipaddr_pub'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $nodedata['ownerid'],
			'passwd' => $nodedata['passwd'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $this->AUTH->id,
			'access' => $nodedata['access'],
			'warning' => $nodedata['warning'],
			'info' => $nodedata['info'],
			'netdev' => $nodedata['netdev'],
			'location' => $nodedata['location'],
			'location_city' => $nodedata['location_city'] ? $nodedata['location_city'] : null,
			'location_street' => $nodedata['location_street'] ? $nodedata['location_street'] : null,
			'location_house' => $nodedata['location_house'] ? $nodedata['location_house'] : null,
			'location_flat' => $nodedata['location_flat'] ? $nodedata['location_flat'] : null,
			'linktype' => isset($nodedata['linktype']) ? intval($nodedata['linktype']) : 0,
			'linkspeed' => isset($nodedata['linkspeed']) ? intval($nodedata['linkspeed']) : 100000,
			'port' => isset($nodedata['port']) && $nodedata['netdev'] ? intval($nodedata['port']) : 0,
			'chkmac' => $nodedata['chkmac'],
			'halfduplex' => $nodedata['halfduplex'],
			'nas' => isset($nodedata['nas']) ? $nodedata['nas'] : 0,
			'longitude' => !empty($nodedata['longitude']) ? str_replace(',', '.', $nodedata['longitude']) : null,
			'latitude' => !empty($nodedata['latitude']) ? str_replace(',', '.', $nodedata['latitude']) : null
		);

		if ($this->DB->Execute('INSERT INTO nodes (name, ipaddr, ipaddr_pub, ownerid,
			passwd, creatorid, creationdate, access, warning, info, netdev,
			location, location_city, location_street, location_house, location_flat,
			linktype, linkspeed, port, chkmac, halfduplex, nas, longitude, latitude)
			VALUES (?, inet_aton(?), inet_aton(?), ?, ?, ?,
			?NOW?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args))) {
			$id = $this->DB->GetLastInsertID('nodes');

			// EtherWerX support (devices have some limits)
			// We must to replace big ID with smaller (first free)
			if ($id > 99999 && chkconfig($this->CONFIG['phpui']['ewx_support'])) {
				$this->DB->BeginTrans();
				$this->DB->LockTables('nodes');

				if ($newid = $this->DB->GetOne('SELECT n.id + 1 FROM nodes n 
						LEFT OUTER JOIN nodes n2 ON n.id + 1 = n2.id
						WHERE n2.id IS NULL AND n.id <= 99999
						ORDER BY n.id ASC LIMIT 1')) {
					$this->DB->Execute('UPDATE nodes SET id = ? WHERE id = ?', array($newid, $id));
					$id = $newid;
				}

				$this->DB->UnLockTables();
				$this->DB->CommitTrans();
			}

			if ($this->SYSLOG) {
				unset($args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]]);
				$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE]] = $id;
				$this->SYSLOG->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_ADD, $args,
					array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
			}

			foreach ($nodedata['macs'] as $mac)
				$this->DB->Execute('INSERT INTO macs (mac, nodeid) VALUES(?, ?)', array(strtoupper($mac), $id));
			if ($this->SYSLOG) {
				$macs = $this->DB->GetAll('SELECT id, mac FROM macs WHERE nodeid = ?', array($id));
				foreach ($macs as $mac) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_MAC] => $mac['id'],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $id,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $nodedata['ownerid'],
						'mac' => $mac['mac']
					);
					$this->SYSLOG->AddMessage(SYSLOG_RES_MAC, SYSLOG_OPER_ADD, $args,
						array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_MAC],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
				}
			}

			return $id;
		}

		return FALSE;
	}

	function NodeExists($id) {
		return ($this->DB->GetOne('SELECT n.id FROM nodes n
			WHERE n.id = ? AND n.ownerid > 0 AND NOT EXISTS (
		        	SELECT 1 FROM customerassignments a
			        JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
				WHERE e.userid = lms_current_user() AND a.customerid = n.ownerid)'
						, array($id)) ? TRUE : FALSE);
	}

	function NodeStats() {
		$result = $this->DB->GetRow('SELECT COUNT(CASE WHEN access=1 THEN 1 END) AS connected, 
				COUNT(CASE WHEN access=0 THEN 1 END) AS disconnected,
				COUNT(CASE WHEN ?NOW?-lastonline < ? THEN 1 END) AS online
				FROM nodes WHERE ownerid > 0', array($this->CONFIG['phpui']['lastonline_limit']));

		$result['total'] = $result['connected'] + $result['disconnected'];
		return $result;
	}

	function GetNodeGroupNames() {
		return $this->DB->GetAllByKey('SELECT id, name, description FROM nodegroups
				ORDER BY name ASC', 'id');
	}

	function GetNodeGroupNamesByNode($nodeid) {
		return $this->DB->GetAllByKey('SELECT id, name, description FROM nodegroups
				WHERE id IN (SELECT nodegroupid FROM nodegroupassignments
					WHERE nodeid = ?)
				ORDER BY name', 'id', array($nodeid));
	}

	function GetNodeGroupNamesWithoutNode($nodeid) {
		return $this->DB->GetAllByKey('SELECT id, name FROM nodegroups
				WHERE id NOT IN (SELECT nodegroupid FROM nodegroupassignments
					WHERE nodeid = ?)
				ORDER BY name', 'id', array($nodeid));
	}

	function GetNodesWithoutGroup($groupid, $network = NULL) {
		if ($network)
			$net = $this->GetNetworkParams($network);

		return $this->DB->GetAll('SELECT n.id AS id, n.name AS nodename, a.nodeid
			FROM nodes n
			JOIN customersview c ON (n.ownerid = c.id)
			LEFT JOIN nodegroupassignments a ON (n.id = a.nodeid AND a.nodegroupid = ?) 
			WHERE a.nodeid IS NULL '
						. ($network ?
								' AND ((ipaddr > ' . $net['address'] . ' AND ipaddr < ' . $net['broadcast'] . ') 
					OR (ipaddr_pub > ' . $net['address'] . ' AND ipaddr_pub < ' . $net['broadcast'] . ')) ' : '')
						. ' ORDER BY nodename', array($groupid));
	}

	function GetNodesWithGroup($groupid, $network = NULL) {
		if ($network)
			$net = $this->GetNetworkParams($network);

		return $this->DB->GetAll('SELECT n.id AS id, n.name AS nodename, a.nodeid
			FROM nodes n
			JOIN customersview c ON (n.ownerid = c.id)
			JOIN nodegroupassignments a ON (n.id = a.nodeid) 
			WHERE a.nodegroupid = ?'
						. ($network ?
								' AND ((ipaddr > ' . $net['address'] . ' AND ipaddr < ' . $net['broadcast'] . ') 
					OR (ipaddr_pub > ' . $net['address'] . ' AND ipaddr_pub < ' . $net['broadcast'] . ')) ' : '')
						. ' ORDER BY nodename', array($groupid));
	}

	function GetNodeGroup($id, $network = NULL) {
		$result = $this->DB->GetRow('SELECT id, name, description, prio,
				(SELECT COUNT(*) FROM nodegroupassignments 
					WHERE nodegroupid = nodegroups.id) AS count
				FROM nodegroups WHERE id = ?', array($id));

		$result['nodes'] = $this->GetNodesWithGroup($id, $network);
		$result['nodescount'] = sizeof($result['nodes']);

		return $result;
	}

	function CompactNodeGroups() {
		global $SYSLOG_RESOURCE_KEYS;
		$this->DB->BeginTrans();
		$this->DB->LockTables('nodegroups');
		if ($nodegroups = $this->DB->GetAll('SELECT id, prio FROM nodegroups ORDER BY prio ASC')) {
			$prio = 1;
			foreach ($nodegroups as $idx => $row) {
				$this->DB->Execute('UPDATE nodegroups SET prio=? WHERE id=?', array($prio, $row['id']));
				if ($this->SYSLOG) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODEGROUP] => $row['id'],
						'prio' => $prio
					);
					$this->SYSLOG->AddMessage(SYSLOG_RES_NODEGROUP, SYSLOG_OPER_UPDATE, $args,
						array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODEGROUP]));
				}
				$prio++;
			}
		}
		$this->DB->UnLockTables();
		$this->DB->CommitTrans();
	}

	function GetNetDevLinkedNodes($id) {
		return $this->DB->GetAll('SELECT nodes.id AS id, nodes.name AS name, linktype, linkspeed, ipaddr, 
			inet_ntoa(ipaddr) AS ip, ipaddr_pub, inet_ntoa(ipaddr_pub) AS ip_pub, 
			netdev, port, ownerid,
			' . $this->DB->Concat('c.lastname', "' '", 'c.name') . ' AS owner 
			FROM nodes, customersview c 
			WHERE ownerid = c.id AND netdev = ? AND ownerid > 0 
			ORDER BY nodes.name ASC', array($id));
	}

	function NetDevLinkNode($id, $devid, $type = 0, $speed = 100000, $port = 0) {
		return $this->DB->Execute('UPDATE nodes SET netdev=?, linktype=?, linkspeed=?, port=?
			 WHERE id=?', array($devid,
						intval($type),
						intval($speed),
						intval($port),
						$id
				));
	}

	function SetNetDevLinkType($dev1, $dev2, $type = 0, $speed = 100000) {
		return $this->DB->Execute('UPDATE netlinks SET type=?, speed=? WHERE (src=? AND dst=?) OR (dst=? AND src=?)', array($type, $speed, $dev1, $dev2, $dev1, $dev2));
	}

	function SetNodeLinkType($node, $type = 0, $speed = 100000) {
		return $this->DB->Execute('UPDATE nodes SET linktype=?, linkspeed=? WHERE id=?', array($type, $speed, $node));
	}

	/*
	 *  Tarrifs and finances
	 */

	function GetCustomerTariffsValue($id) {
		return $this->DB->GetOne('SELECT SUM(tariffs.value)
		    FROM assignments, tariffs
			WHERE tariffid = tariffs.id AND customerid = ? AND suspended = 0
			    AND (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0)', array($id));
	}

	function GetCustomerAssignments($id, $show_expired = false) {
		$now = mktime(0, 0, 0, date('n'), date('d'), date('Y'));

		if ($assignments = $this->DB->GetAll('SELECT a.id AS id, a.tariffid,
			a.customerid, a.period, a.at, a.suspended, a.invoice, a.settlement,
			a.datefrom, a.dateto, a.pdiscount, a.vdiscount, a.liabilityid,
			t.uprate, t.upceil, t.downceil, t.downrate,
			(CASE WHEN t.value IS NULL THEN l.value ELSE t.value END) AS value,
			(CASE WHEN t.name IS NULL THEN l.name ELSE t.name END) AS name
			FROM assignments a
			LEFT JOIN tariffs t ON (a.tariffid = t.id)
			LEFT JOIN liabilities l ON (a.liabilityid = l.id)
			WHERE a.customerid=? '
				. (!$show_expired ? 'AND (a.dateto > ' . $now . ' OR a.dateto = 0)
			    AND (a.liabilityid = 0 OR (a.liabilityid != 0 AND (a.at >= ' . $now . ' OR a.at < 531)))' : '')
				. ' ORDER BY a.datefrom, value', array($id))) {
			foreach ($assignments as $idx => $row) {
				switch ($row['period']) {
					case DISPOSABLE:
						$row['payday'] = date('Y/m/d', $row['at']);
						$row['period'] = trans('disposable');
						break;
					case DAILY:
						$row['period'] = trans('daily');
						$row['payday'] = trans('daily');
						break;
					case WEEKLY:
						$row['at'] = strftime("%a", mktime(0, 0, 0, 0, $row['at'] + 5, 0));
						$row['payday'] = trans('weekly ($a)', $row['at']);
						$row['period'] = trans('weekly');
						break;
					case MONTHLY:
						$row['payday'] = trans('monthly ($a)', $row['at']);
						$row['period'] = trans('monthly');
						break;
					case QUARTERLY:
						$row['at'] = sprintf('%02d/%02d', $row['at'] % 100, $row['at'] / 100 + 1);
						$row['payday'] = trans('quarterly ($a)', $row['at']);
						$row['period'] = trans('quarterly');
						break;
					case HALFYEARLY:
						$row['at'] = sprintf('%02d/%02d', $row['at'] % 100, $row['at'] / 100 + 1);
						$row['payday'] = trans('half-yearly ($a)', $row['at']);
						$row['period'] = trans('half-yearly');
						break;
					case YEARLY:
						$row['at'] = date('d/m', ($row['at'] - 1) * 86400);
						$row['payday'] = trans('yearly ($a)', $row['at']);
						$row['period'] = trans('yearly');
						break;
				}

				$assignments[$idx] = $row;

				// assigned nodes
				$assignments[$idx]['nodes'] = $this->DB->GetAll('SELECT nodes.name, nodes.id FROM nodeassignments, nodes
						    WHERE nodeid = nodes.id AND assignmentid = ?', array($row['id']));

				$assignments[$idx]['discounted_value'] = (((100 - $row['pdiscount']) * $row['value']) / 100) - $row['vdiscount'];

				if ($row['suspended'] == 1)
					$assignments[$idx]['discounted_value'] = $assignments[$idx]['discounted_value'] * $this->CONFIG['finances']['suspension_percentage'] / 100;

				$assignments[$idx]['discounted_value'] = round($assignments[$idx]['discounted_value'], 2);

				$now = time();

				if ($row['suspended'] == 0 &&
						(($row['datefrom'] == 0 || $row['datefrom'] < $now) &&
						($row['dateto'] == 0 || $row['dateto'] > $now))) {
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

	function DeleteAssignment($id) {
		global $SYSLOG_RESOURCE_KEYS;
		$this->DB->BeginTrans();

		if ($this->SYSLOG) {
			$custid = $this->DB->GetOne('SELECT customerid FROM assignments WHERE id=?', array($id));

			$nodeassigns = $this->DB->GetAll('SELECT id, nodeid FROM nodeassignments WHERE assignmentid = ?', array($id));
			if (!empty($nodeassigns))
				foreach ($nodeassigns as $nodeassign) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODEASSIGN] => $nodeassign['id'],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $custid,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $nodeassign['nodeid'],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN] => $id
					);
					$this->SYSLOG->AddMessage(SYSLOG_RES_NODEASSIGN, SYSLOG_OPER_DELETE,
						$args, array_keys($args));
				}

			$assign = $this->DB->GetRow('SELECT tariffid, liabilityid FROM assignments WHERE id=?', array($id));
			$lid = $assign['liabilityid'];
			$tid = $assign['tariffid'];
			if ($lid) {
				$args = array(
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB] => $lid,
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $custid
				);
				$this->SYSLOG->AddMessage(SYSLOG_RES_LIAB, SYSLOG_OPER_DELETE, $args, array_keys($args));
			}
		}
		$this->DB->Execute('DELETE FROM liabilities WHERE id=(SELECT liabilityid FROM assignments WHERE id=?)', array($id));
		$this->DB->Execute('DELETE FROM assignments WHERE id=?', array($id));
		if ($this->SYSLOG) {
			$args = array(
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF] => $tid,
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB] => $lid,
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN] => $id,
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $custid
			);
			$this->SYSLOG->AddMessage(SYSLOG_RES_ASSIGN, SYSLOG_OPER_DELETE, $args, array_keys($args));
		}

		$this->DB->CommitTrans();
	}

	function AddAssignment($data) {
		global $SYSLOG_RESOURCE_KEYS;
		$result = array();

		// Create assignments according to promotion schema
		if (!empty($data['promotiontariffid']) && !empty($data['schemaid'])) {
			$data['tariffid'] = $data['promotiontariffid'];
			$tariff = $this->DB->GetRow('SELECT a.data, s.data AS sdata,
                    t.name, t.value, t.period, t.id, t.prodid, t.taxid,
                    s.continuation, s.ctariffid
                    FROM promotionassignments a
                    JOIN promotionschemas s ON (s.id = a.promotionschemaid)
                    JOIN tariffs t ON (t.id = a.tariffid)
                    WHERE a.promotionschemaid = ? AND a.tariffid = ?', array($data['schemaid'], $data['promotiontariffid']));
			$data_schema = explode(';', $tariff['sdata']);
			$data_tariff = explode(';', $tariff['data']);
			$datefrom = $data['datefrom'];
			$cday = date('d', $datefrom);

			foreach ($data_tariff as $idx => $dt) {
				list($value, $period) = explode(':', $dt);
				// Activation
				if (!$idx) {
					// if activation value specified, create disposable liability
					if (f_round($value)) {
						$start_day = date('d', $data['datefrom']);
						$start_month = date('n', $data['datefrom']);
						$start_year = date('Y', $data['datefrom']);
						// payday is before the start of the period
						// set activation payday to next month's payday
						if ($start_day > $data['at']) {
							$_datefrom = $data['datefrom'];
							$datefrom = mktime(0, 0, 0, $start_month + 1, $data['at'], $start_year);
						}

						$args = array(
							'name' => trans('Activation payment'),
							'value' => str_replace(',', '.', $value),
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX] => intval($tariff['taxid']),
							'prodid' => $tariff['prodid']
						);
						$this->DB->Execute('INSERT INTO liabilities (name, value, taxid, prodid)
							VALUES (?, ?, ?, ?)', array_values($args));

						if ($this->SYSLOG) {
							$lid = $this->DB->GetLastInsertID('liabilities');
							$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB]] = $lid;
							$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]] = $data['customerid'];
							$this->SYSLOG->AddMessage(SYSLOG_RES_LIAB, SYSLOG_OPER_ADD, $args,
								array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB],
									$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
									$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX]));
						}

						$tariffid = 0;
						$period = DISPOSABLE;
						$at = $datefrom;
					} else {
						continue;
					}
				}
				// promotion period
				else {
					$lid = 0;
					if (!$period)
						$period = $data['period'];
					$datefrom = $_datefrom ? $_datefrom : $datefrom;
					$_datefrom = 0;
					$at = $this->CalcAt($period, $datefrom);
					$length = $data_schema[$idx - 1];
					$month = date('n', $datefrom);
					$year = date('Y', $datefrom);
					// assume $data['at'] == 1, set last day of the specified month
					$dateto = mktime(23, 59, 59, $month + $length + ($cday && $cday != 1 ? 1 : 0), 0, $year);
					$cday = 0;

					// Find tariff with specified name+value+period...
					$tariffid = $this->DB->GetOne('SELECT id FROM tariffs
						WHERE name = ? AND value = ? AND period = ?
						LIMIT 1', array(
							$tariff['name'],
							str_replace(',', '.', $value),
							$tariff['period'],
							));

					// ... if not found clone tariff
					if (!$tariffid) {
						$args = $this->DB->GetRow('SELECT name, value, period,
							taxid AS ' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX] . ', type, upceil, downceil, uprate, downrate,
							prodid, plimit, climit, dlimit, upceil_n, downceil_n, uprate_n, downrate_n,
							domain_limit, alias_limit, sh_limit, www_limit, ftp_limit, mail_limit, sql_limit,
							quota_sh_limit, quota_www_limit, quota_ftp_limit, quota_mail_limit, quota_sql_limit
							FROM tariffs WHERE id = ?', array($tariff['id']));
						$args = array_merge($args, array(
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX] => $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX]],
							'name' => $tariff['name'],
							'value' => str_replace(',', '.', $value),
							'period' => $tariff['period']
						));
						unset($args['taxid']);
						$this->DB->Execute('INSERT INTO tariffs (name, value, period,
							taxid, type, upceil, downceil, uprate, downrate,
							prodid, plimit, climit, dlimit, upceil_n, downceil_n, uprate_n, downrate_n,
							domain_limit, alias_limit, sh_limit, www_limit, ftp_limit, mail_limit, sql_limit,
							quota_sh_limit, quota_www_limit, quota_ftp_limit, quota_mail_limit, quota_sql_limit)
							VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
								array_values($args));
						$tariffid = $this->DB->GetLastInsertId('tariffs');
						if ($this->SYSLOG) {
							$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF]] = $tariffid;
							$this->SYSLOG->AddMessage(SYSLOG_RES_TARIFF, SYSLOG_OPER_ADD, $args,
								array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF],
									$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX]));
						}
					}
				}

				// Create assignment
				$args = array(
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF] => $tariffid,
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $data['customerid'],
					'period' => $period,
					'at' => $at,
					'invoice' => !empty($data['invoice']) ? 1 : 0,
					'settlement' => !empty($data['settlement']) ? 1 : 0,
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN] => !empty($data['numberplanid']) ? $data['numberplanid'] : NULL,
					'paytype' => !empty($data['paytype']) ? $data['paytype'] : NULL,
					'datefrom' => $idx ? $datefrom : 0,
					'dateto' => $idx ? $dateto : 0,
					'pdiscount' => 0,
					'vdiscount' => 0,
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB] => $lid,
				);

				$this->DB->Execute('INSERT INTO assignments (tariffid, customerid, period, at, invoice,
					    settlement, numberplanid, paytype, datefrom, dateto, pdiscount, vdiscount, liabilityid)
					    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));

				$id = $this->DB->GetLastInsertID('assignments');

				if ($this->SYSLOG) {
					$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN]] = $id;
					$this->SYSLOG->AddMessage(SYSLOG_RES_ASSIGN, SYSLOG_OPER_ADD, $args,
						array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN]));
				}

				$result[] = $id;
				if ($idx) {
					$datefrom = $dateto + 1;
				}
			}

			// add "after promotion" tariff(s)
			if ($tariff['continuation'] || !$data_schema[0]) {
				$tariffs[] = $tariff['id'];
				if ($tariff['ctariffid'] && $data_schema[0] != 0) {
					$tariffs[] = $tariff['ctariffid'];
				}

				// Create assignments
				foreach ($tariffs as $t) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF] => $t,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $data['customerid'],
						'period' => $data['period'],
						'at' => $this->CalcAt($data['period'], $datefrom),
						'invoice' => !empty($data['invoice']) ? 1 : 0,
						'settlement' => !empty($data['settlement']) ? 1 : 0,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN] => !empty($data['numberplanid']) ? $data['numberplanid'] : NULL,
						'paytype' => !empty($data['paytype']) ? $data['paytype'] : NULL,
						'datefrom' => $datefrom,
						'dateto' => 0,
						'pdiscount' => 0,
						'vdiscount' => 0,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB] => 0,
					);

					$this->DB->Execute('INSERT INTO assignments (tariffid, customerid, period, at, invoice,
					    settlement, numberplanid, paytype, datefrom, dateto, pdiscount, vdiscount, liabilityid)
					    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));

					$id = $this->DB->GetLastInsertID('assignments');

					if ($this->SYSLOG) {
						$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN]] = $id;
						$this->SYSLOG->AddMessage(SYSLOG_RES_ASSIGN, SYSLOG_OPER_ADD, $args,
							array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN],
								$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
								$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF],
								$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB],
								$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN]));
					}

					$result[] = $id;
				}
			}
		}
		// Create one assignment record
		else {
			if (!empty($data['value'])) {
				$args = array(
					'name' => $data['name'],
					'value' => str_replace(',', '.', $data['value']),
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX] => intval($data['taxid']),
					'prodid' => $data['prodid']
				);
				$this->DB->Execute('INSERT INTO liabilities (name, value, taxid, prodid)
					    VALUES (?, ?, ?, ?)', array_values($args));
				$lid = $this->DB->GetLastInsertID('liabilities');
				if ($this->SYSLOG) {
					$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB]] = $lid;
					$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]] = $data['customerid'];
					$this->SYSLOG->AddMessage(SYSLOG_RES_LIAB, SYSLOG_OPER_ADD, $args,
						array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX]));
				}
			}

			$args = array(
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF] => intval($data['tariffid']),
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $data['customerid'],
				'period' => $data['period'],
				'at' => $data['at'],
				'invoice' => !empty($data['invoice']) ? 1 : 0,
				'settlement' => !empty($data['settlement']) ? 1 : 0,
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN] => !empty($data['numberplanid']) ? $data['numberplanid'] : NULL,
				'paytype' => !empty($data['paytype']) ? $data['paytype'] : NULL,
				'datefrom' => $data['datefrom'],
				'dateto' => $data['dateto'],
				'pdiscount' => str_replace(',', '.', $data['pdiscount']),
				'vdiscount' => str_replace(',', '.', $data['vdiscount']),
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB] => isset($lid) ? $lid : 0,
			);
			$this->DB->Execute('INSERT INTO assignments (tariffid, customerid, period, at, invoice,
					    settlement, numberplanid, paytype, datefrom, dateto, pdiscount, vdiscount, liabilityid)
					    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));

			$id = $this->DB->GetLastInsertID('assignments');

			if ($this->SYSLOG) {
				$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN]] = $id;
				$this->SYSLOG->AddMessage(SYSLOG_RES_ASSIGN, SYSLOG_OPER_ADD, $args,
					array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN]));
			}

			$result[] = $id;
		}

		if (!empty($result) && count($result = array_filter($result))) {
			if (!empty($data['nodes'])) {
				// Use multi-value INSERT query
				$values = array();
				foreach ((array) $data['nodes'] as $nodeid)
					foreach ($result as $aid)
						$values[] = sprintf('(%d, %d)', $nodeid, $aid);

				$this->DB->Execute('INSERT INTO nodeassignments (nodeid, assignmentid)
					VALUES ' . implode(', ', $values));
				if ($this->SYSLOG) {
					$nodeassigns = $this->DB->GetAll('SELECT id, nodeid FROM nodeassignments WHERE assignmentid = ?', array($aid));
					foreach ($nodeassigns as $nodeassign) {
						$args = array(
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODEASSIGN] => $nodeassign['id'],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $data['customerid'],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $nodeassign['nodeid'],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN] => $aid
						);
						$this->SYSLOG->AddMessage(SYSLOG_RES_NODEASSIGN, SYSLOG_OPER_ADD, $args, array_keys($args));
					}
				}
			}
		}

		return $result;
	}

	function SuspendAssignment($id, $suspend = TRUE) {
		global $SYSLOG_RESOURCE_KEYS;
		if ($this->SYSLOG) {
			$assign = $this->DB->GetRow('SELECT id, tariffid, liabilityid, customerid FROM assignments WHERE id = ?', array($id));
			$args = array(
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN] => $assign['id'],
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF] => $assign['tariffid'],
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB] => $assign['liabilityid'],
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $assign['customerid'],
				'suspend' => ($suspend ? 1 : 0)
			);
			$this->SYSLOG->AddMessage(SYSLOG_RES_ASSIGN, SYSLOG_OPER_UPDATE, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN],
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF],
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB],
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
		}
		return $this->DB->Execute('UPDATE assignments SET suspended=? WHERE id=?', array($suspend ? 1 : 0, $id));
	}

	function AddInvoice($invoice) {
		global $SYSLOG_RESOURCE_KEYS;

		$currtime = time();
		$cdate = $invoice['invoice']['cdate'] ? $invoice['invoice']['cdate'] : $currtime;
		$sdate = $invoice['invoice']['sdate'] ? $invoice['invoice']['sdate'] : $currtime;
		$number = $invoice['invoice']['number'];
		$type = $invoice['invoice']['type'];

		$args = array(
			'number' => $number,
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN] => $invoice['invoice']['numberplanid'] ? $invoice['invoice']['numberplanid'] : 0,
			'type' => $type,
			'cdate' => $cdate,
			'sdate' => $sdate,
			'paytime' => $invoice['invoice']['paytime'],
			'paytype' => $invoice['invoice']['paytype'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $this->AUTH->id,
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $invoice['customer']['id'],
			'customername' => $invoice['customer']['customername'],
			'address' => $invoice['customer']['address'],
			'ten' => $invoice['customer']['ten'],
			'ssn' => $invoice['customer']['ssn'],
			'zip' => $invoice['customer']['zip'],
			'city' => $invoice['customer']['city'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY] => $invoice['customer']['countryid'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DIV] => $invoice['customer']['divisionid'],
		);
		$this->DB->Execute('INSERT INTO documents (number, numberplanid, type,
			cdate, sdate, paytime, paytype, userid, customerid, name, address, 
			ten, ssn, zip, city, countryid, divisionid)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
		$iid = $this->DB->GetLastInsertID('documents');
		if ($this->SYSLOG) {
			unset($args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]]);
			$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC]] = $iid;
			$this->SYSLOG->AddMessage(SYSLOG_RES_DOC, SYSLOG_OPER_ADD, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN],
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY],
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DIV]));
		}

		$itemid = 0;
		foreach ($invoice['contents'] as $idx => $item) {
			$itemid++;
			$item['valuebrutto'] = str_replace(',', '.', $item['valuebrutto']);
			$item['count'] = str_replace(',', '.', $item['count']);
			$item['discount'] = str_replace(',', '.', $item['discount']);
			$item['pdiscount'] = str_replace(',', '.', $item['pdiscount']);
			$item['vdiscount'] = str_replace(',', '.', $item['vdiscount']);
			$item['taxid'] = isset($item['taxid']) ? $item['taxid'] : 0;

			$args =  array(
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $iid,
				'itemid' => $itemid,
				'value' => $item['valuebrutto'],
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX] => $item['taxid'],
				'prodid' => $item['prodid'],
				'content' => $item['jm'],
				'count' => $item['count'],
				'pdiscount' => $item['pdiscount'],
				'vdiscount' => $item['vdiscount'],
				'description' => $item['name'],
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF] => $item['tariffid'],
			);
			$this->DB->Execute('INSERT INTO invoicecontents (docid, itemid,
				value, taxid, prodid, content, count, pdiscount, vdiscount, description, tariffid) 
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
			if ($this->SYSLOG) {
				$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]] = $invoice['customer']['id'];
				$this->SYSLOG->AddMessage(SYSLOG_RES_INVOICECONT, SYSLOG_OPER_ADD, $args,
					array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF]));
			}

			$this->AddBalance(array(
					'time' => $cdate,
					'value' => $item['valuebrutto'] * $item['count'] * -1,
					'taxid' => $item['taxid'],
					'customerid' => $invoice['customer']['id'],
					'comment' => $item['name'],
					'docid' => $iid,
					'itemid' => $itemid
			));
		}

		return $iid;
	}

	function InvoiceDelete($invoiceid) {
		global $SYSLOG_RESOURCE_KEYS;
		$this->DB->BeginTrans();
		if ($this->SYSLOG) {
			$customerid = $this->DB->GetOne('SELECT customerid FROM documents WHERE id = ?', array($invoiceid));
			$args = array(
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $invoiceid,
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
			);
			$this->SYSLOG->AddMessage(SYSLOG_RES_DOC, SYSLOG_OPER_DELETE, $args, array_keys($args));
			$cashids = $this->DB->GetCol('SELECT id FROM cash WHERE docid = ?', array($invoiceid));
			foreach ($cashids as $cashid) {
				$args = array(
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASH] => $cashid,
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $invoiceid,
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
				);
				$this->SYSLOG->AddMessage(SYSLOG_RES_CASH, SYSLOG_OPER_DELETE, $args, array_keys($args));
			}
			$itemids = $this->DB->GetCol('SELECT itemid FROM invoicecontents WHERE docid = ?', array($invoiceid));
			foreach ($itemids as $itemid) {
				$args = array(
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $invoiceid,
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
					'itemid' => $itemid,
				);
				$this->SYSLOG->AddMessage(SYSLOG_RES_INVOICECONT, SYSLOG_OPER_DELETE, $args,
					array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
			}
		}
		$this->DB->Execute('DELETE FROM documents WHERE id = ?', array($invoiceid));
		$this->DB->Execute('DELETE FROM invoicecontents WHERE docid = ?', array($invoiceid));
		$this->DB->Execute('DELETE FROM cash WHERE docid = ?', array($invoiceid));
		$this->DB->CommitTrans();
	}

	function InvoiceContentDelete($invoiceid, $itemid = 0) {
		global $SYSLOG_RESOURCE_KEYS;
		if ($itemid) {
			$this->DB->BeginTrans();
			if ($this->SYSLOG) {
				$customerid = $this->DB->GetOne('SELECT customerid FROM documents
					JOIN invoicecontents ON docid = id WHERE id = ?', array($invoiceid));
				$args = array(
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $invoiceid,
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
					'itemid' => $itemid,
				);
				$this->SYSLOG->AddMessage(SYSLOG_RES_INVOICECONT, SYSLOG_OPER_DELETE, $args,
					array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
			}
			$this->DB->Execute('DELETE FROM invoicecontents WHERE docid=? AND itemid=?', array($invoiceid, $itemid));

			if (!$this->DB->GetOne('SELECT COUNT(*) FROM invoicecontents WHERE docid=?', array($invoiceid))) {
				// if that was the last item of invoice contents
				$this->DB->Execute('DELETE FROM documents WHERE id = ?', array($invoiceid));
				if ($this->SYSLOG) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $invoiceid,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
					);
					$this->SYSLOG->AddMessage(SYSLOG_RES_DOC, SYSLOG_OPER_DELETE, $args,
						array_keys($args));
				}
			}

			if ($this->SYSLOG) {
				$cashid = $this->DB->GetOne('SELECT id FROM cash WHERE docid = ? AND itemid = ?',
					array($invoiceid, $itemid));
				$args = array(
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASH] => $cashid,
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $invoiceid,
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
				);
				$this->SYSLOG->AddMessage(SYSLOG_RES_CASH, SYSLOG_OPER_DELETE, $args,
					array_keys($args));
			}
			$this->DB->Execute('DELETE FROM cash WHERE docid = ? AND itemid = ?', array($invoiceid, $itemid));
			$this->DB->CommitTrans();
		}
		else
			$this->InvoiceDelete($invoiceid);
	}

	function GetInvoiceContent($invoiceid) {
		global $PAYTYPES;

		if ($result = $this->DB->GetRow('SELECT d.id, d.number, d.name, d.customerid,
				d.userid, d.address, d.zip, d.city, d.countryid, cn.name AS country,
				d.ten, d.ssn, d.cdate, d.sdate, d.paytime, d.paytype, d.numberplanid,
				d.closed, d.reference, d.reason, d.divisionid,
				(SELECT name FROM users WHERE id = d.userid) AS user, n.template,
				ds.name AS division_name, ds.shortname AS division_shortname,
				ds.address AS division_address, ds.zip AS division_zip,
				ds.city AS division_city, ds.countryid AS division_countryid, 
				ds.ten AS division_ten, ds.regon AS division_regon, ds.account AS account,
				ds.inv_header AS division_header, ds.inv_footer AS division_footer,
				ds.inv_author AS division_author, ds.inv_cplace AS division_cplace,
				c.pin AS customerpin, c.divisionid AS current_divisionid,
				c.post_name, c.post_address, c.post_zip, c.post_city, c.post_countryid
				FROM documents d
				JOIN customers c ON (c.id = d.customerid)
				LEFT JOIN countries cn ON (cn.id = d.countryid)
				LEFT JOIN divisions ds ON (ds.id = d.divisionid)
				LEFT JOIN numberplans n ON (d.numberplanid = n.id)
				WHERE d.id = ? AND (d.type = ? OR d.type = ?)', array($invoiceid, DOC_INVOICE, DOC_CNOTE))) {
			$result['pdiscount'] = 0;
			$result['vdiscount'] = 0;
			$result['totalbase'] = 0;
			$result['totaltax'] = 0;
			$result['total'] = 0;

			if ($result['reference'])
				$result['invoice'] = $this->GetInvoiceContent($result['reference']);

			if (!$result['division_header'])
				$result['division_header'] = $result['division_name'] . "\n"
						. $result['division_address'] . "\n" . $result['division_zip'] . ' ' . $result['division_city']
						. ($result['division_countryid'] && $result['countryid']
						&& $result['division_countryid'] != $result['countryid'] ? "\n" . trans($this->GetCountryName($result['division_countryid'])) : '')
						. ($result['division_ten'] != '' ? "\n" . trans('TEN') . ' ' . $result['division_ten'] : '');

			if ($result['content'] = $this->DB->GetAll('SELECT invoicecontents.value AS value, 
						itemid, taxid, taxes.value AS taxvalue, taxes.label AS taxlabel, 
						prodid, content, count, invoicecontents.description AS description, 
						tariffid, itemid, pdiscount, vdiscount 
						FROM invoicecontents 
						LEFT JOIN taxes ON taxid = taxes.id 
						WHERE docid=? 
						ORDER BY itemid', array($invoiceid))
			)
				foreach ($result['content'] as $idx => $row) {
					if (isset($result['invoice'])) {
						$row['value'] += $result['invoice']['content'][$idx]['value'];
						$row['count'] += $result['invoice']['content'][$idx]['count'];
					}

					$result['content'][$idx]['basevalue'] = round(($row['value'] / (100 + $row['taxvalue']) * 100), 2);
					$result['content'][$idx]['total'] = round($row['value'] * $row['count'], 2);
					$result['content'][$idx]['totalbase'] = round($result['content'][$idx]['total'] / (100 + $row['taxvalue']) * 100, 2);
					$result['content'][$idx]['totaltax'] = round($result['content'][$idx]['total'] - $result['content'][$idx]['totalbase'], 2);
					$result['content'][$idx]['value'] = $row['value'];
					$result['content'][$idx]['count'] = $row['count'];

					if (isset($result['taxest'][$row['taxvalue']])) {
						$result['taxest'][$row['taxvalue']]['base'] += $result['content'][$idx]['totalbase'];
						$result['taxest'][$row['taxvalue']]['total'] += $result['content'][$idx]['total'];
						$result['taxest'][$row['taxvalue']]['tax'] += $result['content'][$idx]['totaltax'];
					} else {
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

					$result['pdiscount'] += $row['pdiscount'];
					$result['vdiscount'] += $row['vdiscount'];
				}

			$result['pdate'] = $result['cdate'] + ($result['paytime'] * 86400);
			$result['value'] = $result['total'] - (isset($result['invoice']) ? $result['invoice']['value'] : 0);

			if ($result['value'] < 0) {
				$result['value'] = abs($result['value']);
				$result['rebate'] = true;
			}
			$result['valuep'] = round(($result['value'] - floor($result['value'])) * 100);

			// NOTE: don't waste CPU/mem when printing history is not set:
			if (chkconfig($this->CONFIG['invoices']['print_balance_history'])) {
				if (isset($this->CONFIG['invoices']['print_balance_history_save']) && chkconfig($this->CONFIG['invoices']['print_balance_history_save']))
					$result['customerbalancelist'] = $this->GetCustomerBalanceList($result['customerid'], $result['cdate']);
				else
					$result['customerbalancelist'] = $this->GetCustomerBalanceList($result['customerid']);
				$result['customerbalancelistlimit'] = $this->CONFIG['invoices']['print_balance_history_limit'];
			}

			$result['paytypename'] = $PAYTYPES[$result['paytype']];

			// for backward compat.
			$result['totalg'] = round(($result['value'] - floor($result['value'])) * 100);
			$result['year'] = date('Y', $result['cdate']);
			$result['month'] = date('m', $result['cdate']);
			$result['pesel'] = $result['ssn'];
			$result['nip'] = $result['ten'];
			if ($result['post_name'] || $result['post_address']) {
				$result['serviceaddr'] = $result['post_name'];
				if ($result['post_address'])
					$result['serviceaddr'] .= "\n" . $result['post_address'];
				if ($result['post_zip'] && $result['post_city'])
					$result['serviceaddr'] .= "\n" . $result['post_zip'] . ' ' . $result['post_city'];
			}

			return $result;
		}
		else
			return FALSE;
	}

	function GetNoteContent($id) {
		if ($result = $this->DB->GetRow('SELECT d.id, d.number, d.name, d.customerid,
				d.userid, d.address, d.zip, d.city, d.countryid, cn.name AS country,
				d.ten, d.ssn, d.cdate, d.numberplanid, d.closed, d.divisionid, d.paytime, 
				(SELECT name FROM users WHERE id = d.userid) AS user, n.template,
				ds.name AS division_name, ds.shortname AS division_shortname,
				ds.address AS division_address, ds.zip AS division_zip,
				ds.city AS division_city, ds.countryid AS division_countryid, 
				ds.ten AS division_ten, ds.regon AS division_regon, ds.account AS account,
				ds.inv_header AS division_header, ds.inv_footer AS division_footer,
				ds.inv_author AS division_author, ds.inv_cplace AS division_cplace,
				c.pin AS customerpin, c.divisionid AS current_divisionid,
				c.post_name, c.post_address, c.post_zip, c.post_city, c.post_countryid
				FROM documents d
				JOIN customers c ON (c.id = d.customerid)
				LEFT JOIN countries cn ON (cn.id = d.countryid)
				LEFT JOIN divisions ds ON (ds.id = d.divisionid)
				LEFT JOIN numberplans n ON (d.numberplanid = n.id)
				WHERE d.id = ? AND d.type = ?', array($id, DOC_DNOTE))) {
			$result['value'] = 0;

			if (!$result['division_header'])
				$result['division_header'] = $result['division_name'] . "\n"
						. $result['division_address'] . "\n" . $result['division_zip'] . ' ' . $result['division_city']
						. ($result['division_countryid'] && $result['countryid']
						&& $result['division_countryid'] != $result['countryid'] ? "\n" . trans($this->GetCountryName($result['division_countryid'])) : '')
						. ($result['division_ten'] != '' ? "\n" . trans('TEN') . ' ' . $result['division_ten'] : '');

			if ($result['content'] = $this->DB->GetAll('SELECT
				value, itemid, description 
				FROM debitnotecontents 
				WHERE docid=? 
				ORDER BY itemid', array($id))
			)
				foreach ($result['content'] as $idx => $row) {
					$result['content'][$idx]['value'] = $row['value'];
					$result['value'] += $row['value'];
				}

			$result['valuep'] = round(($result['value'] - floor($result['value'])) * 100);
			$result['pdate'] = $result['cdate'] + ($result['paytime'] * 86400);

			// NOTE: don't waste CPU/mem when printing history is not set:
			if (!empty($this->CONFIG['notes']['print_balance_history']) && chkconfig($this->CONFIG['notes']['print_balance_history'])) {
				if (isset($this->CONFIG['notes']['print_balance_history_save']) && chkconfig($this->CONFIG['notes']['print_balance_history_save']))
					$result['customerbalancelist'] = $this->GetCustomerBalanceList($result['customerid'], $result['cdate']);
				else
					$result['customerbalancelist'] = $this->GetCustomerBalanceList($result['customerid']);
				$result['customerbalancelistlimit'] = $this->CONFIG['notes']['print_balance_history_limit'];
			}

			// for backward compatibility
			if ($result['post_name'] || $result['post_address']) {
				$result['serviceaddr'] = $result['post_name'];
				if ($result['post_address'])
					$result['serviceaddr'] .= "\n" . $result['post_address'];
				if ($result['post_zip'] && $result['post_city'])
					$result['serviceaddr'] .= "\n" . $result['post_zip'] . ' ' . $result['post_city'];
			}

			return $result;
		}
		else
			return FALSE;
	}

	function TariffAdd($tariff) {
		global $SYSLOG_RESOURCE_KEYS;
		$args = array(
			'name' => $tariff['name'],
			'description' => $tariff['description'],
			'value' => $tariff['value'],
			'period' => $tariff['period'] ? $tariff['period'] : null,
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX] => $tariff['taxid'],
			'prodid' => $tariff['prodid'],
			'uprate' => $tariff['uprate'],
			'downrate' => $tariff['downrate'],
			'upceil' => $tariff['upceil'],
			'downceil' => $tariff['downceil'],
			'climit' => $tariff['climit'],
			'plimit' => $tariff['plimit'],
			'uprate_n' => $tariff['uprate_n'],
			'downrate_n' => $tariff['downrate_n'],
			'upceil_n' => $tariff['upceil_n'],
			'downceil_n' => $tariff['downceil_n'],
			'climit_n' => $tariff['climit_n'],
			'plimit_n' => $tariff['plimit_n'],
			'dlimit' => $tariff['dlimit'],
			'type' => $tariff['type'],
			'sh_limit' => $tariff['sh_limit'],
			'www_limit' => $tariff['www_limit'],
			'mail_limit' => $tariff['mail_limit'],
			'sql_limit' => $tariff['sql_limit'],
			'ftp_limit' => $tariff['ftp_limit'],
			'quota_sh_limit' => $tariff['quota_sh_limit'],
			'quota_www_limit' => $tariff['quota_www_limit'],
			'quota_mail_limit' => $tariff['quota_mail_limit'],
			'quota_sql_limit' => $tariff['quota_sql_limit'],
			'quota_ftp_limit' => $tariff['quota_ftp_limit'],
			'domain_limit' => $tariff['domain_limit'],
			'alias_limit' => $tariff['alias_limit'],
		);
		$result = $this->DB->Execute('INSERT INTO tariffs (name, description, value,
				period, taxid, prodid, uprate, downrate, upceil, downceil, climit,
				plimit, uprate_n, downrate_n, upceil_n, downceil_n, climit_n,
				plimit_n, dlimit, type, sh_limit, www_limit, mail_limit, sql_limit,
				ftp_limit, quota_sh_limit, quota_www_limit, quota_mail_limit,
				quota_sql_limit, quota_ftp_limit, domain_limit, alias_limit)
				VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', array_values($args));
		if ($result) {
			$id = $this->DB->GetLastInsertID('tariffs');
			if ($this->SYSLOG) {
				$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF]] = $id;
				$this->SYSLOG->AddMessage(SYSLOG_RES_TARIFF, SYSLOG_OPER_ADD, $args,
					array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX]));
			}
			return $id;
		}
		else
			return FALSE;
	}

	function TariffUpdate($tariff) {
		global $SYSLOG_RESOURCE_KEYS;
		$args = array(
			'name' => $tariff['name'],
			'description' => $tariff['description'],
			'value' => $tariff['value'],
			'period' => $tariff['period'] ? $tariff['period'] : null,
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX] => $tariff['taxid'],
			'prodid' => $tariff['prodid'],
			'uprate' => $tariff['uprate'],
			'downrate' => $tariff['downrate'],
			'upceil' => $tariff['upceil'],
			'downceil' => $tariff['downceil'],
			'climit' => $tariff['climit'],
			'plimit' => $tariff['plimit'],
			'uprate_n' => $tariff['uprate_n'],
			'downrate_n' => $tariff['downrate_n'],
			'upceil_n' => $tariff['upceil_n'],
			'downceil_n' => $tariff['downceil_n'],
			'climit_n' => $tariff['climit_n'],
			'plimit_n' => $tariff['plimit_n'],
			'dlimit' => $tariff['dlimit'],
			'sh_limit' => $tariff['sh_limit'],
			'www_limit' => $tariff['www_limit'],
			'mail_limit' => $tariff['mail_limit'],
			'sql_limit' => $tariff['sql_limit'],
			'ftp_limit' => $tariff['ftp_limit'],
			'quota_sh_limit' => $tariff['quota_sh_limit'],
			'quota_www_limit' => $tariff['quota_www_limit'],
			'quota_mail_limit' => $tariff['quota_mail_limit'],
			'quota_sql_limit' => $tariff['quota_sql_limit'],
			'quota_ftp_limit' => $tariff['quota_ftp_limit'],
			'domain_limit' => $tariff['domain_limit'],
			'alias_limit' => $tariff['alias_limit'],
			'type' => $tariff['type'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF] => $tariff['id']
		);
		$res = $this->DB->Execute('UPDATE tariffs SET name=?, description=?, value=?,
				period=?, taxid=?, prodid=?, uprate=?, downrate=?, upceil=?, downceil=?,
				climit=?, plimit=?, uprate_n=?, downrate_n=?, upceil_n=?, downceil_n=?,
				climit_n=?, plimit_n=?, dlimit=?, sh_limit=?, www_limit=?, mail_limit=?,
				sql_limit=?, ftp_limit=?, quota_sh_limit=?, quota_www_limit=?,
				quota_mail_limit=?, quota_sql_limit=?, quota_ftp_limit=?,
				domain_limit=?, alias_limit=?, type=? WHERE id=?', array_values($args));
		if ($res && $this->SYSLOG)
			$this->SYSLOG->AddMessage(SYSLOG_RES_TARIFF, SYSLOG_OPER_UPDATE, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX]));
		return $res;
	}

	function TariffDelete($id) {
		global $SYSLOG_RESOURCE_KEYS;
		if ($this->SYSLOG)
			$assigns = $this->DB->GetAll('SELECT promotionid, a.id, promotionschemaid FROM promotionassignments a
				JOIN promotionschemas s ON s.id = a.promotionschemaid
				WHERE a.tariffid = ?', array($id));
		$res = $this->DB->Execute('DELETE FROM tariffs WHERE id=?', array($id));
		if ($res && $this->SYSLOG) {
			$this->SYSLOG->AddMessage(SYSLOG_RES_TARIFF, SYSLOG_OPER_DELETE, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF] => $id),
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF]));
			if (!empty($assigns))
				foreach ($assigns as $assign) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_PROMOASSIGN] => $assign['id'],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_PROMOSCHEMA] => $assign['promotionschemaid'],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_PROMO] => $assign['promotionid'],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF] => $id
					);
					$this->SYSLOG->AddMessage(SYSLOG_RES_PROMOASSIGN, SYSLOG_OPER_DELETE,
						$args, array_keys($args));
				}
		}
		return $res;
	}

	function GetTariff($id, $network = NULL) {
		if ($network)
			$net = $this->GetNetworkParams($network);

		$result = $this->DB->GetRow('SELECT t.*, taxes.label AS tax, taxes.value AS taxvalue
			FROM tariffs t
			LEFT JOIN taxes ON (t.taxid = taxes.id)
			WHERE t.id=?', array($id));

		$result['customers'] = $this->DB->GetAll('SELECT c.id AS id, COUNT(c.id) AS cnt, '
				. $this->DB->Concat('c.lastname', "' '", 'c.name') . ' AS customername '
				. ($network ? ', COUNT(nodes.id) AS nodescount ' : '')
				. 'FROM assignments, customersview c '
				. ($network ? 'LEFT JOIN nodes ON (c.id = nodes.ownerid) ' : '')
				. 'WHERE c.id = customerid AND deleted = 0 AND tariffid = ? '
				. ($network ? 'AND ((ipaddr > ' . $net['address'] . ' AND ipaddr < ' . $net['broadcast'] . ') OR (ipaddr_pub > '
						. $net['address'] . ' AND ipaddr_pub < ' . $net['broadcast'] . ')) ' : '')
				. 'GROUP BY c.id, c.lastname, c.name ORDER BY c.lastname, c.name', array($id));

		$unactive = $this->DB->GetRow('SELECT COUNT(*) AS count,
            SUM(CASE t.period
				WHEN ' . MONTHLY . ' THEN t.value
				WHEN ' . QUARTERLY . ' THEN t.value/3
				WHEN ' . HALFYEARLY . ' THEN t.value/6
				WHEN ' . YEARLY . ' THEN t.value/12
				ELSE (CASE a.period
				    WHEN ' . MONTHLY . ' THEN t.value
				    WHEN ' . QUARTERLY . ' THEN t.value/3
				    WHEN ' . HALFYEARLY . ' THEN t.value/6
				    WHEN ' . YEARLY . ' THEN t.value/12
				    ELSE 0
				    END)
				END) AS value
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
			SUM(CASE t.period
				WHEN ' . MONTHLY . ' THEN t.value
				WHEN ' . QUARTERLY . ' THEN t.value/3
				WHEN ' . HALFYEARLY . ' THEN t.value/6
				WHEN ' . YEARLY . ' THEN t.value/12
				ELSE (CASE a.period
				    WHEN ' . MONTHLY . ' THEN t.value
				    WHEN ' . QUARTERLY . ' THEN t.value/3
				    WHEN ' . HALFYEARLY . ' THEN t.value/6
				    WHEN ' . YEARLY . ' THEN t.value/12
				    ELSE 0
				    END)
				 END) AS value
			FROM assignments a
			JOIN tariffs t ON (t.id = a.tariffid)
			WHERE tariffid = ?', array($id));

		// count of all customers with that tariff
		$result['customerscount'] = sizeof($result['customers']);
		// count of all assignments
		$result['count'] = $all['count'];
		// count of 'active' assignments
		$result['activecount'] = $all['count'] - $unactive['count'];
		// avg monthly income (without unactive assignments)
		$result['totalval'] = $all['value'] - $unactive['value'];

		$result['rows'] = ceil($result['customerscount'] / 2);
		return $result;
	}

	function GetTariffs() {
		return $this->DB->GetAll('SELECT t.id, t.name, t.value, uprate, taxid, prodid,
				downrate, upceil, downceil, climit, plimit, taxes.value AS taxvalue,
				taxes.label AS tax, t.period
				FROM tariffs t
				LEFT JOIN taxes ON t.taxid = taxes.id
				WHERE t.disabled = 0
				ORDER BY t.name, t.value DESC');
	}

	function TariffSet($id) {
		if($this->DB->GetOne('SELECT disabled FROM tariffs WHERE id = ?', array($id)) == 1 )
			return $this->DB->Execute('UPDATE tariffs SET disabled = 0 WHERE id = ?', array($id));
		else
			return $this->DB->Execute('UPDATE tariffs SET disabled = 1 WHERE id = ?', array($id));
	}

	function TariffExists($id) {
		return ($this->DB->GetOne('SELECT id FROM tariffs WHERE id=?', array($id)) ? TRUE : FALSE);
	}

	function ReceiptContentDelete($docid, $itemid = 0) {
		global $SYSLOG_RESOURCE_KEYS;
		if ($itemid) {
			if ($this->SYSLOG) {
				$customerid = $this->DB->GetOne('SELECT customerid FROM documents WHERE id=?',
					array($docid));
				$args = array(
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $docid,
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
					'itemid' => $itemid,
				);
				$this->SYSLOG->AddMessage(SYSLOG_RES_RECEIPTCONT, SYSLOG_OPER_DELETE, $args,
					array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
			}
			$this->DB->Execute('DELETE FROM receiptcontents WHERE docid=? AND itemid=?', array($docid, $itemid));

			if (!$this->DB->GetOne('SELECT COUNT(*) FROM receiptcontents WHERE docid=?', array($docid))) {
				// if that was the last item of invoice contents
				if ($this->SYSLOG) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $docid,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
					);
					$this->SYSLOG->AddMessage(SYSLOG_RES_DOC, SYSLOG_OPER_DELETE, $args,
						array_keys($args));
				}
				$this->DB->Execute('DELETE FROM documents WHERE id = ?', array($docid));
			}
			if ($this->SYSLOG) {
				$cashid = $this->DB->GetOne('SELECT id FROM cash WHERE docid = ? AND itemid = ?',
					array($docid, $itemid));
				$args = array(
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASH] => $cashid,
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $docid,
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
				);
				$this->SYSLOG->AddMessage(SYSLOG_RES_CASH, SYSLOG_OPER_DELETE, $args,
					array_keys($args));
			}
			$this->DB->Execute('DELETE FROM cash WHERE docid = ? AND itemid = ?', array($docid, $itemid));
		} else {
			if ($this->SYSLOG) {
				$customerid = $this->DB->GetOne('SELECT customerid FROM documents WHERE id=?', array($docid));
				$itemids = $this->DB->GetCol('SELECT itemid FROM receiptcontents WHERE docid=?', array($docid));
				foreach ($itemids as $itemid) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $docid,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
						'itemid' => $itemid,
					);
					$this->SYSLOG->AddMessage(SYSLOG_RES_RECEIPTCONT, SYSLOG_OPER_DELETE, $args, array_keys($args));
				}
				$args = array(
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $docid,
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
				);
				$this->SYSLOG->AddMessage(SYSLOG_RES_DOC, SYSLOG_OPER_DELETE, $args, array_keys($args));
				$cashids = $this->GetCol('SELECT id FROM cash WHERE docid=?', array($docid));
				foreach ($cashids as $itemid) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASH] => $itemid,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $docid,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
					);
					$this->SYSLOG->AddMessage(SYSLOG_RES_CASH, SYSLOG_OPER_DELETE, $args, array_keys($args));
				}
			}
			$this->DB->Execute('DELETE FROM receiptcontents WHERE docid=?', array($docid));
			$this->DB->Execute('DELETE FROM documents WHERE id = ?', array($docid));
			$this->DB->Execute('DELETE FROM cash WHERE docid = ?', array($docid));
		}
	}

	function DebitNoteContentDelete($docid, $itemid = 0) {
		global $SYSLOG_RESOURCE_KEYS;
		if ($itemid) {
			if ($this->SYSLOG) {
				list ($dnotecontid, $customerid) = array_values($this->DB->GetRow('SELECT dn.id, customerid FROM debitnotecontents dn
					JOIN documents d ON d.id = dn.docid WHERE docid=? AND itemid=?',
					array($docid, $itemid)));
				$args = array(
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DNOTECONT] => $dnotecontid,
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $docid,
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
				);
				$this->SYSLOG->AddMessage(SYSLOG_RES_DNOTECONT, SYSLOG_OPER_DELETE, $args,
					array_keys($args));
			}
			$this->DB->Execute('DELETE FROM debitnotecontents WHERE docid=? AND itemid=?', array($docid, $itemid));

			if (!$this->DB->GetOne('SELECT COUNT(*) FROM debitnotecontents WHERE docid=?', array($docid))) {
				// if that was the last item of debit note contents
				if ($this->SYSLOG) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $docid,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
					);
					$this->SYSLOG->AddMessage(SYSLOG_RES_DOC, SYSLOG_OPER_DELETE, $args,
						array_keys($args));
				}
				$this->DB->Execute('DELETE FROM documents WHERE id = ?', array($docid));
			}
			if ($this->SYSLOG) {
				$cashid = $this->DB->GetOne('SELECT id FROM cash WHERE docid = ? AND itemid = ?',
					array($docid, $itemid));
				$args = array(
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASH] => $cashid,
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $docid,
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
				);
				$this->SYSLOG->AddMessage(SYSLOG_RES_CASH, SYSLOG_OPER_DELETE, $args,
					array_keys($args));
			}
			$this->DB->Execute('DELETE FROM cash WHERE docid = ? AND itemid = ?', array($docid, $itemid));
		} else {
			if ($this->SYSLOG) {
				$customerid = $this->DB->GetOne('SELECT customerid FROM documents WHERE id=?', array($docid));
				$dnotecontids = $this->DB->GetCol('SELECT id FROM debitnotecontents WHERE docid=?', array($docid));
				foreach ($dnotecontids as $itemid) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DNOTECONT] => $itemid,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $docid,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
					);
					$this->SYSLOG->AddMessage(SYSLOG_RES_DNOTECONT, SYSLOG_OPER_DELETE, $args, array_keys($args));
				}
				$args = array(
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $docid,
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
				);
				$this->SYSLOG->AddMessage(SYSLOG_RES_DOC, SYSLOG_OPER_DELETE, $args, array_keys($args));
				$cashids = $this->GetCol('SELECT id FROM cash WHERE docid=?', array($docid));
				foreach ($cashids as $itemid) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASH] => $itemid,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $docid,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
					);
					$this->SYSLOG->AddMessage(SYSLOG_RES_CASH, SYSLOG_OPER_DELETE, $args, array_keys($args));
				}
			}
			$this->DB->Execute('DELETE FROM debitnotecontents WHERE docid=?', array($docid));
			$this->DB->Execute('DELETE FROM documents WHERE id = ?', array($docid));
			$this->DB->Execute('DELETE FROM cash WHERE docid = ?', array($docid));
		}
	}

	function AddBalance($addbalance) {
		global $SYSLOG_RESOURCE_KEYS;
		$args = array(
			'time' => isset($addbalance['time']) ? $addbalance['time'] : time(),
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => isset($addbalance['userid']) ? $addbalance['userid'] : $this->AUTH->id,
			'value' => str_replace(',', '.', round($addbalance['value'], 2)),
			'type' =>  isset($addbalance['type']) ? $addbalance['type'] : 0,
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX] => isset($addbalance['taxid']) ? $addbalance['taxid'] : 0,
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $addbalance['customerid'],
			'comment' => $addbalance['comment'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => isset($addbalance['docid']) ? $addbalance['docid'] : 0,
			'itemid' => isset($addbalance['itemid']) ? $addbalance['itemid'] : 0,
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASHIMPORT] => !empty($addbalance['importid']) ? $addbalance['importid'] : NULL,
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASHSOURCE] => !empty($addbalance['sourceid']) ? $addbalance['sourceid'] : NULL,
		);
		$res = $this->DB->Execute('INSERT INTO cash (time, userid, value, type, taxid,
			customerid, comment, docid, itemid, importid, sourceid)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
		if ($res && $this->SYSLOG) {
			unset($args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]]);
			$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASH]] = $this->DB->GetLastInsertID('cash');
			$this->SYSLOG->AddMessage(SYSLOG_RES_CASH, SYSLOG_OPER_ADD, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASH], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER],
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASHIMPORT],
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASHSOURCE]));
		}
		return $res;
	}

	function DelBalance($id) {
		global $SYSLOG_RESOURCE_KEYS;

		$row = $this->DB->GetRow('SELECT cash.customerid, docid, itemid, documents.type AS doctype, importid
					FROM cash
					LEFT JOIN documents ON (docid = documents.id)
					WHERE cash.id = ?', array($id));

		if ($row['doctype'] == DOC_INVOICE || $row['doctype'] == DOC_CNOTE)
			$this->InvoiceContentDelete($row['docid'], $row['itemid']);
		elseif ($row['doctype'] == DOC_RECEIPT)
			$this->ReceiptContentDelete($row['docid'], $row['itemid']);
		elseif ($row['doctype'] == DOC_DNOTE)
			$this->DebitNoteContentDelete($row['docid'], $row['itemid']);
		else {
			$this->DB->Execute('DELETE FROM cash WHERE id = ?', array($id));
			if ($this->SYSLOG) {
				$args = array(
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASH] => $id,
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $row['customerid'],
				);
				$this->SYSLOG->AddMessage(SYSLOG_RES_CASH, SYSLOG_OPER_DELETE, $args, array_keys($args));
			}
			if ($row['importid']) {
				if ($this->SYSLOG) {
					$cashimport = $this->GetRow('SELECT customerid, sourceid, sourcefileid FROM cashimport WHERE id = ?',
						array($row['importid']));
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASHIMPORT] => $row['importid'],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $cashimport['customerid'],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASHSOURCE] => $cashimport['sourceid'],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_SOURCEFILE] => $cashimport['sourcefileid'],
						'closed' => 0,
					);
					$this->SYSLOG->AddMessage(SYSLOG_RES_CASHIMPORT, SYSLOG_OPER_UPDATE, $args,
						array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASHIMPORT],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASHSOURCE],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_SOURCEFILE]));
				}
				$this->DB->Execute('UPDATE cashimport SET closed = 0 WHERE id = ?', array($row['importid']));
			}
		}
	}

	/*
	 *   Payments
	 */

	function GetPaymentList() {
		if ($paymentlist = $this->DB->GetAll('SELECT id, name, creditor, value, period, at, description FROM payments ORDER BY name ASC'))
			foreach ($paymentlist as $idx => $row) {
				switch ($row['period']) {
					case DAILY:
						$row['payday'] = trans('daily');
						break;
					case WEEKLY:
						$row['payday'] = trans('weekly ($a)', strftime("%a", mktime(0, 0, 0, 0, $row['at'] + 5, 0)));
						break;
					case MONTHLY:
						$row['payday'] = trans('monthly ($a)', $row['at']);
						break;
					case QUARTERLY:
						$row['payday'] = trans('quarterly ($a)', sprintf('%02d/%02d', $row['at'] % 100, $row['at'] / 100 + 1));
						break;
					case HALFYEARLY:
						$row['payday'] = trans('half-yearly ($a)', sprintf('%02d/%02d', $row['at'] % 100, $row['at'] / 100 + 1));
						break;
					case YEARLY:
						$row['payday'] = trans('yearly ($a)', date('d/m', ($row['at'] - 1) * 86400));
						break;
				}

				$paymentlist[$idx] = $row;
			}

		$paymentlist['total'] = sizeof($paymentlist);

		return $paymentlist;
	}

	function GetPayment($id) {
		$payment = $this->DB->GetRow('SELECT id, name, creditor, value, period, at, description FROM payments WHERE id=?', array($id));

		switch ($payment['period']) {
			case DAILY:
				$payment['payday'] = trans('daily');
				break;
			case WEEKLY:
				$payment['payday'] = trans('weekly ($a)', strftime("%a", mktime(0, 0, 0, 0, $payment['at'] + 5, 0)));
				break;
			case MONTHLY:
				$payment['payday'] = trans('monthly ($a)', $payment['at']);
				break;
			case QUARTERLY:
				$payment['payday'] = trans('quarterly ($a)', sprintf('%02d/%02d', $payment['at'] % 100, $payment['at'] / 100 + 1));
				break;
			case HALFYEARLY:
				$payment['payday'] = trans('half-yearly ($a)', sprintf('%02d/%02d', $payment['at'] % 100, $payment['at'] / 100 + 1));
				break;
			case YEARLY:
				$payment['payday'] = trans('yearly ($a)', date('d/m', ($payment['at'] - 1) * 86400));
				break;
		}
		return $payment;
	}

	function GetPaymentName($id) {
		return $this->DB->GetOne('SELECT name FROM payments WHERE id=?', array($id));
	}

	function GetPaymentIDByName($name) {
		return $this->DB->GetOne('SELECT id FROM payments WHERE name=?', array($name));
	}

	function PaymentExists($id) {
		return ($this->DB->GetOne('SELECT id FROM payments WHERE id=?', array($id)) ? TRUE : FALSE);
	}

	function PaymentAdd($paymentdata) {
		global $SYSLOG_RESOURCE_KEYS;
		$args = array(
			'name' => $paymentdata['name'],
			'creditor' => $paymentdata['creditor'],
			'description' => $paymentdata['description'],
			'value' => $paymentdata['value'],
			'period' => $paymentdata['period'],
			'at' => $paymentdata['at'],
		);
		if ($this->DB->Execute('INSERT INTO payments (name, creditor, description, value, period, at)
			VALUES (?, ?, ?, ?, ?, ?)', array_values($args))) {
			$id = $this->DB->GetLastInsertID('payments');
			if ($this->SYSLOG) {
				$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_PAYMENT]] = $id;
				$this->SYSLOG->AddMessage(SYSLOG_RES_PAYMENT, SYSLOG_OPER_ADD, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_PAYMENT]));
			}
			return $id;
		} else
			return FALSE;
	}

	function PaymentDelete($id) {
		global $SYSLOG_RESOURCE_KEYS;
		if ($this->SYSLOG) {
			$args = array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_PAYMENT] => $id);
			$this->SYSLOG->AddMessage(SYSLOG_RES_PAYMENT, SYSLOG_OPER_DELETE, $args, array_keys($args));
		}
		return $this->DB->Execute('DELETE FROM payments WHERE id=?', array($id));
	}

	function PaymentUpdate($paymentdata) {
		global $SYSLOG_RESOURCE_KEYS;
		$args = array(
			'name' => $paymentdata['name'],
			'creditor' => $paymentdata['creditor'],
			'description' => $paymentdata['description'],
			'value' => $paymentdata['value'],
			'period' => $paymentdata['period'],
			'at' => $paymentdata['at'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_PAYMENT] => $paymentdata['id'],
		);
		$res = $this->DB->Execute('UPDATE payments SET name=?, creditor=?, description=?, value=?, period=?, at=? WHERE id=?',
			array_values($args));
		if ($res && $this->SYSLOG)
			$this->SYSLOG->AddMessage(SYSLOG_RES_PAYMENT, SYSLOG_OPER_UPDATE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_PAYMENT]));
		return $res;
	}

	function ScanNodes() {
		$result = array();
		$networks = $this->GetNetworks();
		if ($networks)
			foreach ($networks as $idx => $network) {
				if ($res = execute_program('nbtscan', '-q -s: ' . $network['address'] . '/' . $network['prefix'])) {
					$out = explode("\n", $res);
					foreach ($out as $line) {
						list($ipaddr, $name, $null, $login, $mac) = explode(':', $line, 5);
						$row['ipaddr'] = trim($ipaddr);
						if ($row['ipaddr']) {
							$row['name'] = trim($name);
							$row['mac'] = strtoupper(str_replace('-', ':', trim($mac)));
							if ($row['mac'] != "00:00:00:00:00:00" && !$this->GetNodeIDByIP($row['ipaddr']))
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

	function NetworkExists($id) {
		return ($this->DB->GetOne('SELECT * FROM networks WHERE id=?', array($id)) ? TRUE : FALSE);
	}

	function NetworkSet($id, $disabled = -1) {
		if ($disabled != -1) {
			if ($disabled == 1)
				return $this->DB->Execute('UPDATE networks SET disabled = 1 WHERE id = ?', array($id));
			else
				return $this->DB->Execute('UPDATE networks SET disabled = 0 WHERE id = ?', array($id));
		}
		elseif ($this->DB->GetOne('SELECT disabled FROM networks WHERE id = ?', array($id)) == 1)
			return $this->DB->Execute('UPDATE networks SET disabled = 0 WHERE id = ?', array($id));
		else
			return $this->DB->Execute('UPDATE networks SET disabled = 1 WHERE id = ?', array($id));
	}

	function IsIPFree($ip) {
		return !($this->DB->GetOne('SELECT id FROM nodes WHERE ipaddr=inet_aton(?) OR ipaddr_pub=inet_aton(?)', array($ip, $ip)) ? TRUE : FALSE);
	}

	function IsIPGateway($ip) {
		return ($this->DB->GetOne('SELECT gateway FROM networks WHERE gateway = ?', array($ip)) ? TRUE : FALSE);
	}

	function GetPrefixList() {
		for ($i = 30; $i > 15; $i--) {
			$prefixlist['id'][] = $i;
			$prefixlist['value'][] = trans('$a ($b addresses)', $i, pow(2, 32 - $i));
		}

		return $prefixlist;
	}

	function NetworkAdd($netadd) {
		if ($netadd['prefix'] != '')
			$netadd['mask'] = prefix2mask($netadd['prefix']);

		if ($this->DB->Execute('INSERT INTO networks (name, address, mask, interface, gateway, 
				dns, dns2, domain, wins, dhcpstart, dhcpend, notes) 
				VALUES (?, inet_aton(?), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array(strtoupper($netadd['name']),
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

	function NetworkDelete($id) {
		return $this->DB->Execute('DELETE FROM networks WHERE id=?', array($id));
	}

	function GetNetworkName($id) {
		return $this->DB->GetOne('SELECT name FROM networks WHERE id=?', array($id));
	}

	function GetNetIDByIP($ipaddr) {
		return $this->DB->GetOne('SELECT id FROM networks 
				WHERE address = (inet_aton(?) & inet_aton(mask))', array($ipaddr));
	}

	function GetNetworks($with_disabled = true) {
		if ($with_disabled == false)
			return $this->DB->GetAll('SELECT id, name, inet_ntoa(address) AS address, 
				address AS addresslong, mask, mask2prefix(inet_aton(mask)) AS prefix, disabled 
				FROM networks WHERE disabled=0 ORDER BY name');
		else
			return $this->DB->GetAll('SELECT id, name, inet_ntoa(address) AS address, 
				address AS addresslong, mask, mask2prefix(inet_aton(mask)) AS prefix, disabled 
				FROM networks ORDER BY name');
	}

	function GetNetworkParams($id) {
		return $this->DB->GetRow('SELECT *, inet_ntoa(address) AS netip, 
			broadcast(address, inet_aton(mask)) AS broadcast
			FROM networks WHERE id = ?', array($id));
	}

	function GetNetworkList() {
		if ($networks = $this->DB->GetAll('SELECT n.id, h.name AS hostname, n.name, inet_ntoa(address) AS address, 
				address AS addresslong, mask, interface, gateway, dns, dns2, 
				domain, wins, dhcpstart, dhcpend,
				mask2prefix(inet_aton(mask)) AS prefix,
				broadcast(address, inet_aton(mask)) AS broadcastlong,
				inet_ntoa(broadcast(address, inet_aton(mask))) AS broadcast,
				pow(2,(32 - mask2prefix(inet_aton(mask)))) AS size, disabled,
				(SELECT COUNT(*) 
					FROM nodes 
					WHERE netid = n.id AND (ipaddr >= address AND ipaddr <= broadcast(address, inet_aton(mask))) 
						OR (ipaddr_pub >= address AND ipaddr_pub <= broadcast(address, inet_aton(mask)))
				) AS assigned,
				(SELECT COUNT(*) 
					FROM nodes 
					WHERE netid = n.id AND ((ipaddr >= address AND ipaddr <= broadcast(address, inet_aton(mask))) 
						OR (ipaddr_pub >= address AND ipaddr_pub <= broadcast(address, inet_aton(mask))))
						AND (?NOW? - lastonline < ?)
				) AS online
				FROM networks n
				JOIN hosts h ON h.id = n.hostid
				ORDER BY n.name', array(intval($this->CONFIG['phpui']['lastonline_limit'])))) {
			$size = 0;
			$assigned = 0;
			$online = 0;

			foreach ($networks as $idx => $row) {
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

	function IsIPValid($ip, $checkbroadcast = FALSE, $ignoreid = 0) {
		$ip = ip_long($ip);
		return $this->DB->GetOne('SELECT 1 FROM networks
			WHERE id != ? AND address < ?
			AND broadcast(address, inet_aton(mask)) >' . ($checkbroadcast ? '=' : '') . ' ?', array(intval($ignoreid), $ip, $ip));
	}

	function NetworkOverlaps($network, $mask, $hostid, $ignorenet = 0) {
		$cnetaddr = ip_long($network);
		$cbroadcast = ip_long(getbraddr($network, $mask));

		return $this->DB->GetOne('SELECT 1 FROM networks
			WHERE id != ? AND hostid = ? AND (
				address = ? OR broadcast(address, inet_aton(mask)) = ?
				OR (address > ? AND broadcast(address, inet_aton(mask)) < ?) 
				OR (address < ? AND broadcast(address, inet_aton(mask)) > ?) 
			)', array(
				intval($ignorenet),
				intval($hostid),
				$cnetaddr, $cbroadcast,
				$cnetaddr, $cbroadcast,
				$cnetaddr, $cbroadcast
			));
	}

	function NetworkShift($network = '0.0.0.0', $mask = '0.0.0.0', $shift = 0) {
		return ($this->DB->Execute('UPDATE nodes SET ipaddr = ipaddr + ? 
				WHERE ipaddr >= inet_aton(?) AND ipaddr <= inet_aton(?)', array($shift, $network, getbraddr($network, $mask)))
				+ $this->DB->Execute('UPDATE nodes SET ipaddr_pub = ipaddr_pub + ? 
				WHERE ipaddr_pub >= inet_aton(?) AND ipaddr_pub <= inet_aton(?)', array($shift, $network, getbraddr($network, $mask))));
	}

	function NetworkUpdate($networkdata) {
		return $this->DB->Execute('UPDATE networks SET name=?, address=inet_aton(?), 
			mask=?, interface=?, gateway=?, dns=?, dns2=?, domain=?, wins=?, 
			dhcpstart=?, dhcpend=?, notes=?, hostid=? WHERE id=?', array(strtoupper($networkdata['name']),
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
						$networkdata['hostid'],
						$networkdata['id']
				));
	}

	function NetworkCompress($id, $shift = 0) {
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

		foreach ($network['nodes']['id'] as $idx => $value)
			if ($value)
				$nodes[] = $network['nodes']['addresslong'][$idx];

		for ($i = $address + 1; $i < $broadcast; $i++) {
			if (!sizeof($nodes))
				break;

			// skip special and dhcp range addresses
			if (in_array($i, $specials) || ($i >= $dhcpstart && $i <= $dhcpend))
				continue;

			$ip = array_shift($nodes);

			if ($i == $ip)
				continue;

			// don't change assigned special addresses
			if (in_array($ip, $specials)) {
				array_unshift($nodes, $ip);
				$size = sizeof($nodes);

				foreach ($nodes as $idx => $ip)
					if (!in_array($ip, $specials)) {
						unset($nodes[$idx]);
						break;
					}

				if ($size == sizeof($nodes))
					break;
			}

			if (!$this->DB->Execute('UPDATE nodes SET ipaddr=? WHERE netid=? AND ipaddr=?', array($i, $id, $ip)))
				$this->DB->Execute('UPDATE nodes SET ipaddr_pub=? WHERE netid=? AND ipaddr_pub=?', array($i, $id, $ip));
		}
	}

	function NetworkRemap($src, $dst) {
		$network['source'] = $this->GetNetworkRecord($src);
		$network['dest'] = $this->GetNetworkRecord($dst);
		$address = $network['dest']['addresslong'] + 1;
		$broadcast = $network['dest']['addresslong'] + $network['dest']['size'];
		foreach ($network['source']['nodes']['id'] as $idx => $value)
			if ($value)
				$nodes[] = $network['source']['nodes']['addresslong'][$idx];
		foreach ($network['dest']['nodes']['id'] as $idx => $value)
			if ($value)
				$destnodes[] = $network['dest']['nodes']['addresslong'][$idx];

		for ($i = $address; $i < $broadcast; $i++) {
			if (!sizeof($nodes))
				break;
			$ip = array_pop($nodes);

			while (in_array($i, (array) $destnodes))
				$i++;

			if (!$this->DB->Execute('UPDATE nodes SET ipaddr=? WHERE netid=? AND ipaddr=?', array($i, $dst, $ip)))
				$this->DB->Execute('UPDATE nodes SET ipaddr_pub=? WHERE netid=? AND ipaddr_pub=?', array($i, $dst, $ip));

			$counter++;
		}

		return $counter;
	}

	function GetNetworkRecord($id, $page = 0, $plimit = 4294967296, $firstfree = false) {
		$network = $this->DB->GetRow('SELECT id, name, inet_ntoa(address) AS address, 
				address AS addresslong, mask, interface, gateway, dns, dns2, 
				domain, wins, dhcpstart, dhcpend, hostid,
				mask2prefix(inet_aton(mask)) AS prefix,
				inet_ntoa(broadcast(address, inet_aton(mask))) AS broadcast, 
				notes 
				FROM networks WHERE id = ?', array($id));

		$nodes = $this->DB->GetAllByKey('
				SELECT id, name, ipaddr, ownerid, netdev 
				FROM nodes WHERE netid = ? AND ipaddr > ? AND ipaddr < ?
				UNION ALL
				SELECT id, name, ipaddr_pub AS ipaddr, ownerid, netdev 
				FROM nodes WHERE netid = ? AND ipaddr_pub > ? AND ipaddr_pub < ?', 'ipaddr',
				array($id, $network['addresslong'], ip_long($network['broadcast']),
					$id, $network['addresslong'], ip_long($network['broadcast'])));

		if($network['hostid'])
			$network['hostname'] = $this->DB->GetOne('SELECT name FROM hosts WHERE id=?', array($network['hostid']));
		$network['size'] = pow(2, 32 - $network['prefix']);
		$network['assigned'] = sizeof($nodes);
		$network['free'] = $network['size'] - $network['assigned'] - 2;
		if ($network['dhcpstart'])
			$network['free'] = $network['free'] - (ip_long($network['dhcpend']) - ip_long($network['dhcpstart']) + 1);

		if (!$plimit)
			$plimit = 256;
		$network['pages'] = ceil($network['size'] / $plimit);

		if ($page > $network['pages'])
			$page = $network['pages'];
		if ($page < 1)
			$page = 1;
		$page--;

		while (1) {
			$start = $page * $plimit;
			$end = ($network['size'] > $plimit ? $start + $plimit : $network['size']);
			$network['pageassigned'] = 0;
			unset($network['nodes']);

			for ($i = 0; $i < ($end - $start); $i++) {
				$longip = (string) ($network['addresslong'] + $i + $start);

				$network['nodes']['addresslong'][$i] = $longip;
				$network['nodes']['address'][$i] = long2ip($longip);

				if (isset($nodes[$longip])) {
					$network['nodes']['id'][$i] = $nodes[$longip]['id'];
					$network['nodes']['netdev'][$i] = $nodes[$longip]['netdev'];
					$network['nodes']['ownerid'][$i] = $nodes[$longip]['ownerid'];
					$network['nodes']['name'][$i] = $nodes[$longip]['name'];
					$network['pageassigned']++;
				} else {
					$network['nodes']['id'][$i] = 0;

					if ($longip == $network['addresslong'])
						$network['nodes']['name'][$i] = '<b>NETWORK</b>';
					elseif ($network['nodes']['address'][$i] == $network['broadcast'])
						$network['nodes']['name'][$i] = '<b>BROADCAST</b>';
					elseif ($network['nodes']['address'][$i] == $network['gateway'])
						$network['nodes']['name'][$i] = '<b>GATEWAY</b>';
					elseif ($longip >= ip_long($network['dhcpstart']) && $longip <= ip_long($network['dhcpend']))
						$network['nodes']['name'][$i] = '<b>DHCP</b>';
					else
						$freenode = true;
				}
			}

			if ($firstfree && !isset($freenode)) {
				if ($page + 1 >= $network['pages'])
					break;
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

	function NetDevExists($id) {
		return ($this->DB->GetOne('SELECT * FROM netdevices WHERE id=?', array($id)) ? TRUE : FALSE);
	}

	function GetNetDevIDByNode($id) {
		return $this->DB->GetOne('SELECT netdev FROM nodes WHERE id=?', array($id));
	}

	function CountNetDevLinks($id) {
		return $this->DB->GetOne('SELECT COUNT(*) FROM netlinks WHERE src = ? OR dst = ?', array($id, $id))
				+ $this->DB->GetOne('SELECT COUNT(*) FROM nodes WHERE netdev = ? AND ownerid > 0', array($id));
	}

	function GetNetDevLinkType($dev1, $dev2) {
		return $this->DB->GetRow('SELECT type, speed FROM netlinks 
			WHERE (src=? AND dst=?) OR (dst=? AND src=?)', array($dev1, $dev2, $dev1, $dev2));
	}

	function GetNetDevConnectedNames($id) {
		return $this->DB->GetAll('SELECT d.id, d.name, d.description,
			d.location, d.producer, d.ports, l.type AS linktype,
			l.speed AS linkspeed, l.srcport, l.dstport,
			(SELECT COUNT(*) FROM netlinks WHERE src = d.id OR dst = d.id) 
			+ (SELECT COUNT(*) FROM nodes WHERE netdev = d.id AND ownerid > 0)
			AS takenports 
			FROM netdevices d
			JOIN (SELECT DISTINCT type, speed, 
				(CASE src WHEN ? THEN dst ELSE src END) AS dev, 
				(CASE src WHEN ? THEN dstport ELSE srcport END) AS srcport, 
				(CASE src WHEN ? THEN srcport ELSE dstport END) AS dstport 
				FROM netlinks WHERE src = ? OR dst = ?
			) l ON (d.id = l.dev)
			ORDER BY name', array($id, $id, $id, $id, $id));
	}

	function GetNetDevList($order = 'name,asc') {
		list($order, $direction) = sscanf($order, '%[^,],%s');

		($direction == 'desc') ? $direction = 'desc' : $direction = 'asc';

		switch ($order) {
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
				. ($sqlord != '' ? $sqlord . ' ' . $direction : ''));

		$netdevlist['total'] = sizeof($netdevlist);
		$netdevlist['order'] = $order;
		$netdevlist['direction'] = $direction;

		return $netdevlist;
	}

	function GetNetDevNames() {
		return $this->DB->GetAll('SELECT id, name, location, producer 
			FROM netdevices ORDER BY name');
	}

	function GetNotConnectedDevices($id) {
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

	function GetNetDev($id) {
		$result = $this->DB->GetRow('SELECT d.*, t.name AS nastypename, c.name AS channel,
		        lc.name AS city_name,
				(CASE WHEN ls.name2 IS NOT NULL THEN ' . $this->DB->Concat('ls.name2', "' '", 'ls.name') . ' ELSE ls.name END) AS street_name, lt.name AS street_type
			FROM netdevices d
			LEFT JOIN nastypes t ON (t.id = d.nastype)
			LEFT JOIN ewx_channels c ON (d.channelid = c.id)
			LEFT JOIN location_cities lc ON (lc.id = d.location_city)
			LEFT JOIN location_streets ls ON (ls.id = d.location_street)
			LEFT JOIN location_street_types lt ON (lt.id = ls.typeid)
			WHERE d.id = ?', array($id));

		$result['takenports'] = $this->CountNetDevLinks($id);

		if ($result['guaranteeperiod'] != NULL && $result['guaranteeperiod'] != 0)
			$result['guaranteetime'] = strtotime('+' . $result['guaranteeperiod'] . ' month', $result['purchasetime']); // transform to UNIX timestamp
		elseif ($result['guaranteeperiod'] == NULL)
			$result['guaranteeperiod'] = -1;

		return $result;
	}

	function NetDevDelLinks($id) {
		$this->DB->Execute('DELETE FROM netlinks WHERE src=? OR dst=?', array($id, $id));
		$this->DB->Execute('UPDATE nodes SET netdev=0, port=0 
				WHERE netdev=? AND ownerid>0', array($id));
	}

	function DeleteNetDev($id) {
		$this->DB->BeginTrans();
		$this->DB->Execute('DELETE FROM netlinks WHERE src=? OR dst=?', array($id));
		$this->DB->Execute('DELETE FROM nodes WHERE ownerid=0 AND netdev=?', array($id));
		$this->DB->Execute('UPDATE nodes SET netdev=0 WHERE netdev=?', array($id));
		$this->DB->Execute('DELETE FROM netdevices WHERE id=?', array($id));
		$this->DB->CommitTrans();
	}

	function NetDevAdd($data) {
		if ($this->DB->Execute('INSERT INTO netdevices (name, location,
				location_city, location_street, location_house, location_flat,
				description, producer, model, serialnumber,
				ports, purchasetime, guaranteeperiod, shortname,
				nastype, clients, secret, community, channelid,
				longitude, latitude)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($data['name'],
						$data['location'],
						$data['location_city'] ? $data['location_city'] : null,
						$data['location_street'] ? $data['location_street'] : null,
						$data['location_house'] ? $data['location_house'] : null,
						$data['location_flat'] ? $data['location_flat'] : null,
						$data['description'],
						$data['producer'],
						$data['model'],
						$data['serialnumber'],
						$data['ports'],
						$data['purchasetime'],
						$data['guaranteeperiod'],
						$data['shortname'],
						$data['nastype'],
						$data['clients'],
						$data['secret'],
						$data['community'],
						!empty($data['channelid']) ? $data['channelid'] : NULL,
						!empty($data['longitude']) ? str_replace(',', '.', $data['longitude']) : NULL,
						!empty($data['latitude']) ? str_replace(',', '.', $data['latitude']) : NULL
				))) {
			$id = $this->DB->GetLastInsertID('netdevices');

			// EtherWerX support (devices have some limits)
			// We must to replace big ID with smaller (first free)
			if ($id > 99999 && chkconfig($this->CONFIG['phpui']['ewx_support'])) {
				$this->DB->BeginTrans();
				$this->DB->LockTables('ewx_channels');

				if ($newid = $this->DB->GetOne('SELECT n.id + 1 FROM ewx_channels n 
						LEFT OUTER JOIN ewx_channels n2 ON n.id + 1 = n2.id
						WHERE n2.id IS NULL AND n.id <= 99999
						ORDER BY n.id ASC LIMIT 1')) {
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

	function NetDevUpdate($data) {
		$this->DB->Execute('UPDATE netdevices SET name=?, description=?, producer=?, location=?,
				location_city=?, location_street=?, location_house=?, location_flat=?,
				model=?, serialnumber=?, ports=?, purchasetime=?, guaranteeperiod=?, shortname=?,
				nastype=?, clients=?, secret=?, community=?, channelid=?, longitude=?, latitude=? 
				WHERE id=?', array($data['name'],
				$data['description'],
				$data['producer'],
				$data['location'],
				$data['location_city'] ? $data['location_city'] : null,
				$data['location_street'] ? $data['location_street'] : null,
				$data['location_house'] ? $data['location_house'] : null,
				$data['location_flat'] ? $data['location_flat'] : null,
				$data['model'],
				$data['serialnumber'],
				$data['ports'],
				$data['purchasetime'],
				$data['guaranteeperiod'],
				$data['shortname'],
				$data['nastype'],
				$data['clients'],
				$data['secret'],
				$data['community'],
				!empty($data['channelid']) ? $data['channelid'] : NULL,
				!empty($data['longitude']) ? str_replace(',', '.', $data['longitude']) : null,
				!empty($data['latitude']) ? str_replace(',', '.', $data['latitude']) : null,
				$data['id']
		));
	}

	function IsNetDevLink($dev1, $dev2) {
		return $this->DB->GetOne('SELECT COUNT(id) FROM netlinks 
			WHERE (src=? AND dst=?) OR (dst=? AND src=?)', array($dev1, $dev2, $dev1, $dev2));
	}

	function NetDevLink($dev1, $dev2, $type = 0, $speed = 100000, $sport = 0, $dport = 0) {
		if ($dev1 != $dev2)
			if (!$this->IsNetDevLink($dev1, $dev2))
				return $this->DB->Execute('INSERT INTO netlinks 
					(src, dst, type, speed, srcport, dstport) 
					VALUES (?, ?, ?, ?, ?, ?)', array($dev1, $dev2, $type, $speed,
								intval($sport), intval($dport)));

		return FALSE;
	}

	function NetDevUnLink($dev1, $dev2) {
		$this->DB->Execute('DELETE FROM netlinks WHERE (src=? AND dst=?) OR (dst=? AND src=?)', array($dev1, $dev2, $dev1, $dev2));
	}

	function GetUnlinkedNodes() {
		return $this->DB->GetAll('SELECT *, inet_ntoa(ipaddr) AS ip 
			FROM nodes WHERE netdev=0 ORDER BY name ASC');
	}

	function GetNetDevIPs($id) {
		return $this->DB->GetAll('SELECT id, name, mac, ipaddr, inet_ntoa(ipaddr) AS ip, 
			ipaddr_pub, inet_ntoa(ipaddr_pub) AS ip_pub, access, info, port 
			FROM vnodes 
			WHERE ownerid = 0 AND netdev = ?', array($id));
	}

	/*
	 *   Request Tracker (Helpdesk)
	 */

	function GetQueue($id) {
		if ($queue = $this->DB->GetRow('SELECT * FROM rtqueues WHERE id=?', array($id))) {
			$users = $this->DB->GetAll('SELECT id, name FROM users WHERE deleted=0');
			foreach ($users as $user) {
				$user['rights'] = $this->GetUserRightsRT($user['id'], $id);
				$queue['rights'][] = $user;
			}
			return $queue;
		}
		else
			return NULL;
	}

	function GetQueueContents($ids, $order = 'createtime,desc', $state = NULL, $owner = 0, $catids = NULL) {
		if (!$order)
			$order = 'createtime,desc';

		list($order, $direction) = sscanf($order, '%[^,],%s');

		($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

		switch ($order) {
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

		switch ($state) {
			case '0':
			case '1':
			case '2':
			case '3':
				$statefilter = ' AND state = ' . $state;
				break;
			case '-1':
				$statefilter = ' AND state != ' . RT_RESOLVED;
				break;
			default:
				$statefilter = '';
				break;
		}

		if ($result = $this->DB->GetAll(
				'SELECT DISTINCT t.id, t.customerid, c.address, users.name AS ownername,
			    t.subject, state, owner AS ownerid, t.requestor AS req,
			    CASE WHEN customerid = 0 THEN t.requestor ELSE '
				. $this->DB->Concat('c.lastname', "' '", 'c.name') . ' END AS requestor, 
			    t.createtime AS createtime, u.name AS creatorname,
			    (SELECT MAX(createtime) FROM rtmessages WHERE ticketid = t.id) AS lastmodified
		    FROM rttickets t 
		    LEFT JOIN rtticketcategories tc ON (t.id = tc.ticketid)
		    LEFT JOIN users ON (owner = users.id)
		    LEFT JOIN customers c ON (t.customerid = c.id)
		    LEFT JOIN users u ON (t.creatorid = u.id)
		    WHERE 1=1 '
				. (is_array($ids) ? ' AND t.queueid IN (' . implode(',', $ids) . ')' : ($ids != 0 ? ' AND t.queueid = ' . $ids : ''))
				. (is_array($catids) ? ' AND tc.categoryid IN (' . implode(',', $catids) . ')' : ($catids != 0 ? ' AND tc.categoryid = ' . $catids : ''))
				. $statefilter
				. ($owner ? ' AND t.owner = ' . intval($owner) : '')
				. ($sqlord != '' ? $sqlord . ' ' . $direction : ''))) {
			foreach ($result as $idx => $ticket) {
				//$ticket['requestoremail'] = preg_replace('/^.*<(.*@.*)>$/', '\1',$ticket['requestor']);
				//$ticket['requestor'] = str_replace(' <'.$ticket['requestoremail'].'>','',$ticket['requestor']);
				if (!$ticket['customerid'])
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

	function GetUserRightsRT($user, $queue, $ticket = NULL) {
		if (!$queue && $ticket) {
			if (!($queue = $this->GetCache('rttickets', $ticket, 'queueid')))
				$queue = $this->DB->GetOne('SELECT queueid FROM rttickets WHERE id=?', array($ticket));
		}

		if (!$queue)
			return 0;

		$rights = $this->DB->GetOne('SELECT rights FROM rtrights WHERE userid=? AND queueid=?', array($user, $queue));

		return ($rights ? $rights : 0);
	}

	function GetQueueList($stats = true) {
		if ($result = $this->DB->GetAll('SELECT q.id, name, email, description 
				FROM rtqueues q'
				. (!check_conf('privileges.superuser') ? ' JOIN rtrights r ON r.queueid = q.id
					WHERE r.rights <> 0 AND r.userid = ?' : '') . ' ORDER BY name', array($this->AUTH->id))) {
			if ($stats)
				foreach ($result as $idx => $row)
					foreach ($this->GetQueueStats($row['id']) as $sidx => $row)
						$result[$idx][$sidx] = $row;
		}
		return $result;
	}

	function GetQueueNames() {
		return $this->DB->GetAll('SELECT q.id, name FROM rtqueues q'
			. (!check_conf('privileges.superuser') ? ' JOIN rtrights r ON r.queueid = q.id 
				WHERE r.rights <> 0 AND r.userid = ?' : '') . ' ORDER BY name', array($this->AUTH->id));
	}

	function QueueExists($id) {
		return ($this->DB->GetOne('SELECT * FROM rtqueues WHERE id=?', array($id)) ? TRUE : FALSE);
	}

	function GetQueueIdByName($queue) {
		return $this->DB->GetOne('SELECT id FROM rtqueues WHERE name=?', array($queue));
	}

	function GetQueueName($id) {
		return $this->DB->GetOne('SELECT name FROM rtqueues WHERE id=?', array($id));
	}

	function GetQueueEmail($id) {
		return $this->DB->GetOne('SELECT email FROM rtqueues WHERE id=?', array($id));
	}

	function GetQueueStats($id) {
		if ($result = $this->DB->GetAll('SELECT state, COUNT(state) AS scount 
			FROM rttickets WHERE queueid = ? GROUP BY state ORDER BY state ASC', array($id))) {
			foreach ($result as $row)
				$stats[$row['state']] = $row['scount'];
			foreach (array('new', 'open', 'resolved', 'dead') as $idx => $value)
				$stats[$value] = isset($stats[$idx]) ? $stats[$idx] : 0;
		}
		$stats['lastticket'] = $this->DB->GetOne('SELECT createtime FROM rttickets 
			WHERE queueid = ? ORDER BY createtime DESC', array($id));

		return $stats;
	}

	function GetCategory($id) {
		if ($category = $this->DB->GetRow('SELECT * FROM rtcategories WHERE id=?', array($id))) {
			$users = $this->DB->GetAll('SELECT id, name FROM users WHERE deleted=0 ORDER BY login asc');
			foreach ($users as $user) {
				$user['owner'] = $this->DB->GetOne('SELECT 1 FROM rtcategoryusers WHERE userid = ? AND categoryid = ?', array($user['id'], $id));
				$category['owners'][] = $user;
			}
			return $category;
		}
		else
			return NULL;
	}

	function GetUserRightsToCategory($user, $category, $ticket = NULL) {
		if (!$category && $ticket) {
			if (!($category = $this->GetCache('rttickets', $ticket, 'categoryid')))
				$category = $this->DB->GetCol('SELECT categoryid FROM rtticketcategories WHERE ticketid=?', array($ticket));
		}

		// grant access to ticket when no categories assigned to this ticket
		if (!$category)
			return 1;

		$owner = $this->DB->GetOne('SELECT 1 FROM rtcategoryusers WHERE userid=? AND categoryid ' .
				(is_array($category) ? 'IN (' . implode(',', $category) . ')' : '= ' . $category), array($user));

		return ($owner === '1');
	}

	function GetCategoryList($stats = true) {
		if ($result = $this->DB->GetAll('SELECT id, name, description 
				FROM rtcategories ORDER BY name')) {
			if ($stats)
				foreach ($result as $idx => $row)
					foreach ($this->GetCategoryStats($row['id']) as $sidx => $row)
						$result[$idx][$sidx] = $row;
			foreach ($result as $idx => $category)
				$result[$idx]['owners'] = $this->DB->GetAll('SELECT u.id, name FROM rtcategoryusers cu 
				LEFT JOIN users u ON cu.userid = u.id 
				WHERE categoryid = ?', array($category['id']));
		}
		return $result;
	}

	function GetCategoryStats($id) {
		if ($result = $this->DB->GetAll('SELECT state, COUNT(state) AS scount 
			FROM rttickets LEFT JOIN rtticketcategories ON rttickets.id = rtticketcategories.ticketid 
			WHERE rtticketcategories.categoryid = ? GROUP BY state ORDER BY state ASC', array($id))) {
			foreach ($result as $row)
				$stats[$row['state']] = $row['scount'];
			foreach (array('new', 'open', 'resolved', 'dead') as $idx => $value)
				$stats[$value] = isset($stats[$idx]) ? $stats[$idx] : 0;
		}
		$stats['lastticket'] = $this->DB->GetOne('SELECT createtime FROM rttickets 
			LEFT JOIN rtticketcategories ON rttickets.id = rtticketcategories.ticketid 
			WHERE rtticketcategories.categoryid = ? ORDER BY createtime DESC', array($id));

		return $stats;
	}

	function CategoryExists($id) {
		return ($this->DB->GetOne('SELECT * FROM rtcategories WHERE id=?', array($id)) ? TRUE : FALSE);
	}

	function GetCategoryIdByName($category) {
		return $this->DB->GetOne('SELECT id FROM rtcategories WHERE name=?', array($category));
	}

	function GetCategoryListByUser($userid = NULL) {
		return $this->DB->GetAll('SELECT c.id, name
		    FROM rtcategories c
		    LEFT JOIN rtcategoryusers cu 
			ON c.id = cu.categoryid '
						. ($userid ? 'WHERE userid = ' . intval($userid) : '' )
						. ' ORDER BY name');
	}

	function RTStats() {
		$categories = $this->GetCategoryListByUser($this->AUTH->id);
		if (empty($categories))
			return NULL;
		foreach ($categories as $category)
			$catids[] = $category['id'];
		return $this->DB->GetAll('SELECT tc.categoryid AS id, c.name,
				    COUNT(CASE state WHEN ' . RT_NEW . ' THEN 1 END) AS new,
				    COUNT(CASE state WHEN ' . RT_OPEN . ' THEN 1 END) AS opened,
				    COUNT(CASE state WHEN ' . RT_RESOLVED . ' THEN 1 END) AS resolved,
				    COUNT(CASE state WHEN ' . RT_DEAD . ' THEN 1 END) AS dead,
				    COUNT(CASE WHEN state != ' . RT_RESOLVED . ' THEN 1 END) AS unresolved
				    FROM rttickets t
				    LEFT JOIN rtticketcategories tc ON t.id = tc.ticketid
				    LEFT JOIN rtcategories c ON c.id = tc.categoryid
				    WHERE tc.categoryid IN (' . implode(',', $catids) . ')
				    GROUP BY tc.categoryid, c.name
				    ORDER BY c.name');
	}

	function GetQueueByTicketId($id) {
		if ($queueid = $this->DB->GetOne('SELECT queueid FROM rttickets WHERE id=?', array($id)))
			return $this->DB->GetRow('SELECT * FROM rtqueues WHERE id=?', array($queueid));
		else
			return NULL;
	}

	function TicketExists($id) {
		$ticket = $this->DB->GetOne('SELECT * FROM rttickets WHERE id = ?', array($id));
		$this->cache['rttickets'][$id] = $ticket;
		return $ticket;
	}

	function TicketAdd($ticket) {
		$ts = time();
		$this->DB->Execute('INSERT INTO rttickets (queueid, customerid, requestor, subject, 
				state, owner, createtime, cause, creatorid)
				VALUES (?, ?, ?, ?, 0, 0, ?, ?, ?)', array($ticket['queue'],
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
				VALUES (?, ?, ?, ?, ?, ?)', array($id,
				$ticket['customerid'],
				$ts,
				$ticket['subject'],
				preg_replace("/\r/", "", $ticket['body']),
				$ticket['mailfrom']));

		foreach (array_keys($ticket['categories']) as $catid)
			$this->DB->Execute('INSERT INTO rtticketcategories (ticketid, categoryid) 
				VALUES (?, ?)', array($id, $catid));

		return $id;
	}

	function GetTicketContents($id) {
		global $RT_STATES;

		$ticket = $this->DB->GetRow('SELECT t.id AS ticketid, t.queueid, rtqueues.name AS queuename, 
				    t.requestor, t.state, t.owner, t.customerid, t.cause, t.creatorid, c.name AS creator, '
				. $this->DB->Concat('customers.lastname', "' '", 'customers.name') . ' AS customername, 
				    o.name AS ownername, t.createtime, t.resolvetime, t.subject
				FROM rttickets t
				LEFT JOIN rtqueues ON (t.queueid = rtqueues.id)
				LEFT JOIN users o ON (t.owner = o.id)
				LEFT JOIN users c ON (t.creatorid = c.id)
				LEFT JOIN customers ON (customers.id = t.customerid)
				WHERE t.id = ?', array($id));

		$ticket['categories'] = $this->DB->GetAllByKey('SELECT categoryid AS id FROM rtticketcategories WHERE ticketid = ?', 'id', array($id));

		$ticket['messages'] = $this->DB->GetAll(
				'(SELECT rtmessages.id AS id, mailfrom, subject, body, createtime, '
				. $this->DB->Concat('customers.lastname', "' '", 'customers.name') . ' AS customername, 
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

		if (!$ticket['customerid'])
			list($ticket['requestor'], $ticket['requestoremail']) = sscanf($ticket['requestor'], "%[^<]<%[^>]");
		else
			list($ticket['requestoremail']) = sscanf($ticket['requestor'], "<%[^>]");
//		$ticket['requestoremail'] = preg_replace('/^.* <(.+@.+)>$/', '\1',$ticket['requestor']);
//		$ticket['requestor'] = str_replace(' <'.$ticket['requestoremail'].'>','',$ticket['requestor']);
		$ticket['status'] = $RT_STATES[$ticket['state']];
		$ticket['uptime'] = uptimef($ticket['resolvetime'] ? $ticket['resolvetime'] - $ticket['createtime'] : time() - $ticket['createtime']);

		return $ticket;
	}

	function SetTicketState($ticket, $state) {
		($state == 2 ? $resolvetime = time() : $resolvetime = 0);

		if ($this->DB->GetOne('SELECT owner FROM rttickets WHERE id=?', array($ticket)))
			$this->DB->Execute('UPDATE rttickets SET state=?, resolvetime=? WHERE id=?', array($state, $resolvetime, $ticket));
		else
			$this->DB->Execute('UPDATE rttickets SET state=?, owner=?, resolvetime=? WHERE id=?', array($state, $this->AUTH->id, $resolvetime, $ticket));
	}

	function GetMessage($id) {
		if ($message = $this->DB->GetRow('SELECT * FROM rtmessages WHERE id=?', array($id)))
			$message['attachments'] = $this->DB->GetAll('SELECT * FROM rtattachments WHERE messageid = ?', array($id));
		return $message;
	}

	/*
	 * Konfiguracja LMS-UI
	 */

	function GetConfigOptionId($var, $section) {
		return $this->DB->GetOne('SELECT id FROM uiconfig WHERE section = ? AND var = ?', array($section, $var));
	}

	function CheckOption($var, $value) {
		switch ($var) {
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
				if ($value <= 0)
					return trans('Value of option "$a" must be a number grater than zero!', $var);
				break;
			case 'reload_type':
				if ($value != 'sql' && $value != 'exec')
					return trans('Incorrect reload type. Valid types are: sql, exec!');
				break;
			case 'force_ssl':
			case 'allow_mac_sharing':
			case 'smarty_debug':
			case 'use_current_payday':
			case 'helpdesk_backend_mode':
			case 'helpdesk_reply_body':
			case 'to_words_short_version':
			case 'newticket_notify':
			case 'print_balance_list':
			case 'short_pagescroller':
			case 'big_networks':
			case 'ewx_support':
			case 'helpdesk_stats':
			case 'helpdesk_customerinfo':
				if (!isboolean($value))
					return trans('Incorrect value! Valid values are: 1|t|true|y|yes|on and 0|n|no|off|false');
				break;
			case 'debug_email':
				if (!check_email($value))
					return trans('Incorrect email address!');
				break;
		}
		return NULL;
	}

	/*
	 *  Miscalenous
	 */

	function GetHostingLimits($customerid) {
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

		if ($limits = $this->DB->GetAll('SELECT alias_limit, domain_limit, sh_limit,
			www_limit, mail_limit, sql_limit, ftp_limit, quota_sh_limit,
			quota_www_limit, quota_mail_limit, quota_sql_limit, quota_ftp_limit
	                FROM tariffs WHERE id IN (SELECT tariffid FROM assignments
				WHERE customerid = ? AND tariffid != 0
				AND (dateto > ?NOW? OR dateto = 0)
				AND (datefrom < ?NOW? OR datefrom = 0))', array($customerid))) {
			foreach ($limits as $row)
				foreach ($row as $idx => $val)
					if ($val === NULL || $result[$idx] === NULL) {
						$result[$idx] = NULL; // no limit
					} else {
						$result[$idx] += $val;
					}
		}

		return $result;
	}

	function GetRemoteMACs($host = '127.0.0.1', $port = 1029) {
		$inputbuf = '';
		$result = array();

		if ($socket = socket_create(AF_INET, SOCK_STREAM, 0))
			if (@socket_connect($socket, $host, $port)) {
				while ($input = socket_read($socket, 2048))
					$inputbuf .= $input;
				socket_close($socket);
			}
		if ($inputbuf) {
			foreach (explode("\n", $inputbuf) as $line) {
				list($ip, $hwaddr) = explode(' ', $line);
				if (check_mac($hwaddr)) {
					$result['mac'][] = $hwaddr;
					$result['ip'][] = $ip;
					$result['longip'][] = ip_long($ip);
					$result['nodename'][] = $this->GetNodeNameByMAC($hwaddr);
				}
			}
		}

		return $result;
	}

	function GetMACs() {
		$result = array();
		if ($this->CONFIG['phpui']['arp_table_backend'] != '') {
			exec($this->CONFIG['phpui']['arp_table_backend'], $result);
			foreach ($result as $arpline) {
				list($ip, $mac) = explode(' ', $arpline);
				$result['mac'][] = $mac;
				$result['ip'][] = $ip;
				$result['longip'][] = ip_long($ip);
				$result['nodename'][] = $this->GetNodeNameByMAC($mac);
			}
		}
		else
			switch (PHP_OS) {
				case 'Linux':
					if (@is_readable('/proc/net/arp'))
						$file = fopen('/proc/net/arp', 'r');
					else
						break;
					while (!feof($file)) {
						$line = fgets($file, 4096);
						$line = preg_replace('/[\t ]+/', ' ', $line);
						if (preg_match('/[0-9]/', $line)) { // skip header line
							list($ip, $hwtype, $flags, $hwaddr, $mask, $device) = explode(' ', $line);
							if ($flags != '0x6' && $hwaddr != '00:00:00:00:00:00' && check_mac($hwaddr)) {
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
					exec('arp -an|grep -v incompl', $result);
					foreach ($result as $arpline) {
						list($fqdn, $ip, $at, $mac, $hwtype, $perm) = explode(' ', $arpline);
						$ip = str_replace('(', '', str_replace(')', '', $ip));
						if ($perm != "PERM") {
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

	function GetUniqueInstallationID() {
		if (!($uiid = $this->DB->GetOne('SELECT keyvalue FROM dbinfo WHERE keytype=?', array('unique_installation_id')))) {
			list($usec, $sec) = explode(' ', microtime());
			$uiid = md5(uniqid(rand(), true)) . sprintf('%09x', $sec) . sprintf('%07x', ($usec * 10000000));
			$this->DB->Execute('INSERT INTO dbinfo (keytype, keyvalue) VALUES (?, ?)', array('unique_installation_id', $uiid));
		}
		return $uiid;
	}

	function CheckUpdates($force = FALSE) {
		$uiid = $this->GetUniqueInstallationID();
		$time = $this->DB->GetOne('SELECT ?NOW?');
		$content = FALSE;
		if ($force == TRUE)
			$lastcheck = 0;
		elseif (!($lastcheck = $this->DB->GetOne('SELECT keyvalue FROM dbinfo WHERE keytype=?', array('last_check_for_updates_timestamp'))))
			$lastcheck = 0;
		if ($lastcheck + $this->CONFIG['phpui']['check_for_updates_period'] < $time) {
			list($v, ) = explode(' ', $this->_version);

			if ($content = fetch_url('http://register.lms.org.pl/update.php?uiid=' . $uiid . '&v=' . $v)) {
				if ($lastcheck == 0)
					$this->DB->Execute('INSERT INTO dbinfo (keyvalue, keytype) VALUES (?NOW?, ?)', array('last_check_for_updates_timestamp'));
				else
					$this->DB->Execute('UPDATE dbinfo SET keyvalue=?NOW? WHERE keytype=?', array('last_check_for_updates_timestamp'));

				$content = unserialize((string) $content);
				$content['regdata'] = unserialize((string) $content['regdata']);

				if (is_array($content['regdata'])) {
					$this->DB->Execute('DELETE FROM dbinfo WHERE keytype LIKE ?', array('regdata_%'));

					foreach (array('id', 'name', 'url', 'hidden') as $key)
						$this->DB->Execute('INSERT INTO dbinfo (keytype, keyvalue) VALUES (?, ?)', array('regdata_' . $key, $content['regdata'][$key]));
				}
			}
		}

		return $content;
	}

	function GetRegisterData() {
		if ($regdata = $this->DB->GetAll('SELECT * FROM dbinfo WHERE keytype LIKE ?', array('regdata_%'))) {
			foreach ($regdata as $regline)
				$registerdata[str_replace('regdata_', '', $regline['keytype'])] = $regline['keyvalue'];
			return $registerdata;
		}
		return NULL;
	}

	function UpdateRegisterData($name, $url, $hidden) {
		$name = rawurlencode($name);
		$url = rawurlencode($url);
		$uiid = $this->GetUniqueInstallationID();
		$url = 'http://register.lms.org.pl/register.php?uiid=' . $uiid . '&name=' . $name . '&url=' . $url . ($hidden == TRUE ? '&hidden=1' : '');

		if (fetch_url($url) !== FALSE) {
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

	function SendMail($recipients, $headers, $body, $files = NULL) {
		@include_once('Mail.php');
		if (!class_exists('Mail'))
			return trans('Can\'t send message. PEAR::Mail not found!');

		$params['host'] = $this->CONFIG['mail']['smtp_host'];
		$params['port'] = $this->CONFIG['mail']['smtp_port'];

		if (!empty($this->CONFIG['mail']['smtp_username'])) {
			$params['auth'] = !empty($this->CONFIG['mail']['smtp_auth_type']) ? $this->CONFIG['mail']['smtp_auth_type'] : true;
			$params['username'] = $this->CONFIG['mail']['smtp_username'];
			$params['password'] = $this->CONFIG['mail']['smtp_password'];
		}
		else
			$params['auth'] = false;

		$headers['X-Mailer'] = 'LMS-' . $this->_version;
		$headers['X-Remote-IP'] = $_SERVER['REMOTE_ADDR'];
		$headers['X-HTTP-User-Agent'] = $_SERVER['HTTP_USER_AGENT'];
		$headers['Mime-Version'] = '1.0';
		$headers['Subject'] = qp_encode($headers['Subject']);

		if (!empty($this->CONFIG['mail']['debug_email'])) {
			$recipients = $this->CONFIG['mail']['debug_email'];
			$headers['To'] = '<' . $recipients . '>';
		}

		if (empty($headers['Date']))
			$headers['Date'] = date('r');

		if ($files) {
			$boundary = '-LMS-' . str_replace(' ', '.', microtime());
			$headers['Content-Type'] = "multipart/mixed;\n  boundary=\"" . $boundary . '"';
			$buf = "\nThis is a multi-part message in MIME format.\n\n";
			$buf .= '--' . $boundary . "\n";
			$buf .= "Content-Type: text/plain; charset=UTF-8\n\n";
			$buf .= $body . "\n";
			while (list(, $chunk) = each($files)) {
				$buf .= '--' . $boundary . "\n";
				$buf .= "Content-Transfer-Encoding: base64\n";
				$buf .= "Content-Type: " . $chunk['content_type'] . "; name=\"" . $chunk['filename'] . "\"\n";
				$buf .= "Content-Description:\n";
				$buf .= "Content-Disposition: attachment; filename=\"" . $chunk['filename'] . "\"\n\n";
				$buf .= chunk_split(base64_encode($chunk['data']), 60, "\n");
			}
			$buf .= '--' . $boundary . '--';
		} else {
			$headers['Content-Type'] = 'text/plain; charset=UTF-8';
			$buf = $body;
		}

		$error = $mail_object = & Mail::factory('smtp', $params);
		//if (PEAR::isError($error))
		if (is_a($error, 'PEAR_Error'))
			return $error->getMessage();

		$error = $mail_object->send($recipients, $headers, $buf);
		//if (PEAR::isError($error))
		if (is_a($error, 'PEAR_Error'))
			return $error->getMessage();
		else
			return MSG_SENT;
	}

	function SendSMS($number, $message, $messageid = 0) {
		$msg_len = mb_strlen($message);

		if (!$msg_len) {
			return trans('SMS message is empty!');
		}

		if (!empty($this->CONFIG['sms']['debug_phone'])) {
			$number = $this->CONFIG['sms']['debug_phone'];
		}

		$prefix = !empty($this->CONFIG['sms']['prefix']) ? $this->CONFIG['sms']['prefix'] : '';
		$number = preg_replace('/[^0-9]/', '', $number);
		$number = preg_replace('/^0+/', '', $number);

		// add prefix to the number if needed
		if ($prefix && substr($number, 0, strlen($prefix)) != $prefix)
			$number = $prefix . $number;

		// message ID must be unique
		if (!$messageid) {
			$messageid = '0.' . time();
		}

		$message = preg_replace("/\r/", "", $message);

		if (!empty($this->CONFIG['sms']['max_length']) && intval($this->CONFIG['sms']['max_length']) > 6
			&& $msg_len > intval($this->CONFIG['sms']['max_length']))
			$message = mb_substr($message, 0, $this->CONFIG['sms']['max_length'] - 6) . ' [...]';

		$data = array(
				'number' => $number,
				'message' => $message,
				'messageid' => $messageid
		);

		// call external SMS handler(s)
		$data = $this->ExecHook('send_sms_before', $data);

		if ($data['abort']) {
			return $data['result'];
		}

		$number = $data['number'];
		$message = $data['message'];
		$messageid = $data['messageid'];

		if (empty($this->CONFIG['sms']['service']))
			return trans('SMS "service" not set!');
		else
			$service = $this->CONFIG['sms']['service'];

		switch ($service) {
			case 'smscenter':
				if (!function_exists('curl_init'))
					return trans('Curl extension not loaded!');
				if (empty($this->CONFIG['sms']['username']))
					return trans('SMSCenter username not set!');
				if (empty($this->CONFIG['sms']['password']))
					return trans('SMSCenter username not set!');
				if (empty($this->CONFIG['sms']['from']))
					return trans('SMS "from" not set!');
				else
					$from = $this->CONFIG['sms']['from'];

				if ($msg_len < 160)
					$type_sms = 'sms';
				else if ($msg_len <= 459)
					$type_sms = 'concat';
				else
					return trans('SMS Message too long!');

				if (strlen($number) > 16 || strlen($number) < 4)
					return trans('Wrong phone number format!');

				$type = !empty($this->CONFIG['sms']['smscenter_type']) ? $this->CONFIG['sms']['smscenter_type'] : 'dynamic';
				$message .= ($type == 'static') ? "\n\n" . $from : '';

				$args = array(
						'user' => $this->CONFIG['sms']['username'],
						'pass' => $this->CONFIG['sms']['password'],
						'type' => $type_sms,
						'number' => $number,
						'text' => $message,
						'from' => $from
				);

				$encodedargs = array();
				foreach (array_keys($args) as $thiskey)
					array_push($encodedargs, urlencode($thiskey) . "=" . urlencode($args[$thiskey]));
				$encodedargs = implode('&', $encodedargs);

				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, 'http://api.statsms.net/send.php');
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $encodedargs);
				curl_setopt($curl, CURLOPT_TIMEOUT, 10);

				$page = curl_exec($curl);
				if (curl_error($curl))
					return 'SMS communication error. ' . curl_error($curl);

				$info = curl_getinfo($curl);
				if ($info['http_code'] != '200')
					return 'SMS communication error. Http code: ' . $info['http_code'];

				curl_close($curl);
				$smsc = explode(', ', $page);
				$smsc_result = array();

				foreach ($smsc as $element) {
					$tmp = explode(': ', $element);
					array_push($smsc_result, $tmp[1]);
				}

				switch ($smsc_result[0]) {
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
						return 'Smscenter error ' . $smsc_result[0] . '. Please contact smscenter administrator';
				}
				break;
			case 'smstools':
				$dir = !empty($this->CONFIG['sms']['smstools_outdir']) ? $this->CONFIG['sms']['smstools_outdir'] : '/var/spool/sms/outgoing';

				if (!file_exists($dir))
					return trans('SMSTools outgoing directory not exists ($a)!', $dir);
				if (!is_writable($dir))
					return trans('Unable to write to SMSTools outgoing directory ($a)!', $dir);

				$filename = $dir . '/lms-' . $messageid . '-' . $number;
				$latin1 = iconv('UTF-8', 'ISO-8859-15', $message);
				$alphabet = '';
				if (strlen($latin1) != mb_strlen($message, 'UTF-8')) {
					$alphabet = "Alphabet: UCS2\n";
					$message = iconv('UTF-8', 'UNICODEBIG', $message);
				}
				//$message = clear_utf($message);
				$file = sprintf("To: %s\n%s\n%s", $number, $alphabet, $message);

				if ($fp = fopen($filename, 'w')) {
					fwrite($fp, $file);
					fclose($fp);
				}
				else
					return trans('Unable to create file $a!', $filename);

				return MSG_NEW;
				break;
			default:
				return trans('Unknown SMS service!');
		}
	}

	function GetMessages($customerid, $limit = NULL) {
		return $this->DB->GetAll('SELECT i.messageid AS id, i.status, i.error,
		        i.destination, m.subject, m.type, m.cdate
			FROM messageitems i
			JOIN messages m ON (m.id = i.messageid)
			WHERE i.customerid = ?
			ORDER BY m.cdate DESC'
						. ($limit ? ' LIMIT ' . $limit : ''), array($customerid));
	}

	function GetDocuments($customerid = NULL, $limit = NULL) {
		if (!$customerid)
			return NULL;

		if ($list = $this->DB->GetAll('SELECT c.docid, d.number, d.type, c.title, c.fromdate, c.todate, 
			c.description, c.filename, c.md5sum, c.contenttype, n.template, d.closed, d.cdate
			FROM documentcontents c
			JOIN documents d ON (c.docid = d.id)
			JOIN docrights r ON (d.type = r.doctype AND r.userid = ? AND (r.rights & 1) = 1)
			LEFT JOIN numberplans n ON (d.numberplanid = n.id)
			WHERE d.customerid = ?
			ORDER BY cdate', array($this->AUTH->id, $customerid))) {
			if ($limit) {
				$index = (sizeof($list) - $limit) > 0 ? sizeof($list) - $limit : 0;
				for ($i = $index; $i < sizeof($list); $i++)
					$result[] = $list[$i];

				return $result;
			}
			else
				return $list;
		}
	}

	function GetTaxes($from = NULL, $to = NULL) {
		$from = $from ? $from : mktime(0, 0, 0);
		$to = $to ? $to : mktime(23, 59, 59);

		return $this->DB->GetAllByKey('SELECT id, value, label, taxed FROM taxes
			WHERE (validfrom = 0 OR validfrom <= ?)
			    AND (validto = 0 OR validto >= ?)
			ORDER BY value', 'id', array($from, $to));
	}

	function EventSearch($search, $order = 'date,asc', $simple = false) {
		list($order, $direction) = sscanf($order, '%[^,],%s');

		(strtolower($direction) != 'desc') ? $direction = 'ASC' : $direction = 'DESC';

		switch ($order) {
			default:
				$sqlord = ' ORDER BY date ' . $direction . ', begintime ' . $direction;
				break;
		}

		$list = $this->DB->GetAll(
				'SELECT events.id AS id, title, description, date, begintime, endtime, customerid, closed, '
				. $this->DB->Concat('customers.lastname', "' '", 'customers.name') . ' AS customername
			FROM events
			LEFT JOIN customers ON (customerid = customers.id)
			WHERE (private = 0 OR (private = 1 AND userid = ?)) '
				. (!empty($search['datefrom']) ? ' AND date >= ' . intval($search['datefrom']) : '')
				. (!empty($search['dateto']) ? ' AND date <= ' . intval($search['dateto']) : '')
				. (!empty($search['customerid']) ? ' AND customerid = ' . intval($search['customerid']) : '')
				. (!empty($search['title']) ? ' AND title ?LIKE? ' . $this->DB->Escape('%' . $search['title'] . '%') : '')
				. (!empty($search['description']) ? ' AND description ?LIKE? ' . $this->DB->Escape('%' . $search['description'] . '%') : '')
				. (!empty($search['note']) ? ' AND note ?LIKE? ' . $this->DB->Escape('%' . $search['note'] . '%') : '')
				. $sqlord, array($this->AUTH->id));

		if ($list) {
			foreach ($list as $idx => $row) {
				if (!$simple)
					$list[$idx]['userlist'] = $this->DB->GetAll('SELECT userid AS id, users.name
						FROM eventassignments, users
						WHERE userid = users.id AND eventid = ? ', array($row['id']));

				if ($search['userid'] && !empty($list[$idx]['userlist']))
					foreach ($list[$idx]['userlist'] as $user)
						if ($user['id'] == $search['userid']) {
							$list2[] = $list[$idx];
							break;
						}
			}

			if ($search['userid'])
				return $list2;
			else
				return $list;
		}
	}

	function GetNumberPlans($doctype = NULL, $cdate = NULL, $division = NULL, $next = true) {
		if (is_array($doctype))
			$where[] = 'doctype IN (' . implode(',', $doctype) . ')';
		else if ($doctype)
			$where[] = 'doctype = ' . intval($doctype);

		if ($division)
			$where[] = 'id IN (SELECT planid FROM numberplanassignments
                WHERE divisionid = ' . intval($division) . ')';

		if (!empty($where))
			$where = ' WHERE ' . implode(' AND ', $where);

		$list = $this->DB->GetAllByKey('
				SELECT id, template, isdefault, period, doctype
				FROM numberplans' . $where . '
				ORDER BY id', 'id');

		if ($list && $next) {
			if ($cdate)
				list($curryear, $currmonth) = explode('/', $cdate);
			else {
				$curryear = date('Y');
				$currmonth = date('n');
			}
			switch ($currmonth) {
				case 1: case 2: case 3: $startq = 1;
					$starthy = 1;
					break;
				case 4: case 5: case 6: $startq = 4;
					$starthy = 1;
					break;
				case 7: case 8: case 9: $startq = 7;
					$starthy = 7;
					break;
				case 10: case 11: case 12: $startq = 10;
					$starthy = 7;
					break;
			}

			$yearstart = mktime(0, 0, 0, 1, 1, $curryear);
			$yearend = mktime(0, 0, 0, 1, 1, $curryear + 1);
			$halfyearstart = mktime(0, 0, 0, $starthy, 1);
			$halfyearend = mktime(0, 0, 0, $starthy + 3, 1);
			$quarterstart = mktime(0, 0, 0, $startq, 1);
			$quarterend = mktime(0, 0, 0, $startq + 3, 1);
			$monthstart = mktime(0, 0, 0, $currmonth, 1, $curryear);
			$monthend = mktime(0, 0, 0, $currmonth + 1, 1, $curryear);
			$weekstart = mktime(0, 0, 0, $currmonth, date('j') - strftime('%u') + 1);
			$weekend = mktime(0, 0, 0, $currmonth, date('j') - strftime('%u') + 1 + 7);
			$daystart = mktime(0, 0, 0);
			$dayend = mktime(0, 0, 0, date('n'), date('j') + 1);

			$max = $this->DB->GetAllByKey('SELECT numberplanid AS id, MAX(number) AS max 
					    FROM documents LEFT JOIN numberplans ON (numberplanid = numberplans.id)
					    WHERE '
					. ($doctype ? 'numberplanid IN (' . implode(',', array_keys($list)) . ') AND ' : '')
					. ' cdate >= (CASE period
						WHEN ' . YEARLY . ' THEN ' . $yearstart . '
						WHEN ' . HALFYEARLY . ' THEN ' . $halfyearstart . '
						WHEN ' . QUARTERLY . ' THEN ' . $quarterstart . '
						WHEN ' . MONTHLY . ' THEN ' . $monthstart . '
						WHEN ' . WEEKLY . ' THEN ' . $weekstart . '
						WHEN ' . DAILY . ' THEN ' . $daystart . ' ELSE 0 END)
					    AND cdate < (CASE period
						WHEN ' . YEARLY . ' THEN ' . $yearend . '
						WHEN ' . HALFYEARLY . ' THEN ' . $halfyearend . '
						WHEN ' . QUARTERLY . ' THEN ' . $quarterend . '
						WHEN ' . MONTHLY . ' THEN ' . $monthend . '
						WHEN ' . WEEKLY . ' THEN ' . $weekend . '
						WHEN ' . DAILY . ' THEN ' . $dayend . ' ELSE 4294967296 END)
					    GROUP BY numberplanid', 'id');

			foreach ($list as $idx => $item)
				if (isset($max[$item['id']]['max']))
					$list[$idx]['next'] = $max[$item['id']]['max'] + 1;
				else
					$list[$idx]['next'] = 1;
		}

		return $list;
	}

	function GetNewDocumentNumber($doctype = NULL, $planid = NULL, $cdate = NULL) {
		if ($planid)
			$period = $this->DB->GetOne('SELECT period FROM numberplans WHERE id=?', array($planid));
		else
			$planid = 0;

		$period = isset($period) ? $period : YEARLY;
		$cdate = $cdate ? $cdate : time();

		switch ($period) {
			case DAILY:
				$start = mktime(0, 0, 0, date('n', $cdate), date('j', $cdate), date('Y', $cdate));
				$end = mktime(0, 0, 0, date('n', $cdate), date('j', $cdate) + 1, date('Y', $cdate));
				break;
			case WEEKLY:
				$weekstart = date('j', $cdate) - strftime('%u', $cdate) + 1;
				$start = mktime(0, 0, 0, date('n', $cdate), $weekstart, date('Y', $cdate));
				$end = mktime(0, 0, 0, date('n', $cdate), $weekstart + 7, date('Y', $cdate));
				break;
			case MONTHLY:
				$start = mktime(0, 0, 0, date('n', $cdate), 1, date('Y', $cdate));
				$end = mktime(0, 0, 0, date('n', $cdate) + 1, 1, date('Y', $cdate));
				break;
			case QUARTERLY:
				$currmonth = date('n');
				switch (date('n')) {
					case 1: case 2: case 3: $startq = 1;
						break;
					case 4: case 5: case 6: $startq = 4;
						break;
					case 7: case 8: case 9: $startq = 7;
						break;
					case 10: case 11: case 12: $startq = 10;
						break;
				}
				$start = mktime(0, 0, 0, $startq, 1, date('Y', $cdate));
				$end = mktime(0, 0, 0, $startq + 3, 1, date('Y', $cdate));
				break;
			case HALFYEARLY:
				$currmonth = date('n');
				switch (date('n')) {
					case 1: case 2: case 3: case 4: case 5: case 6: $startq = 1;
						break;
					case 7: case 8: case 9: case 10: case 11: case 12: $startq = 7;
						break;
				}
				$start = mktime(0, 0, 0, $starthy, 1, date('Y', $cdate));
				$end = mktime(0, 0, 0, $starthy + 6, 1, date('Y', $cdate));
				break;
			case YEARLY:
				$start = mktime(0, 0, 0, 1, 1, date('Y', $cdate));
				$end = mktime(0, 0, 0, 1, 1, date('Y', $cdate) + 1);
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
				WHERE cdate >= ? AND cdate < ? AND type = ? AND numberplanid = ?', array($start, $end, $doctype, $planid));

		return $number ? ++$number : 1;
	}

	function DocumentExists($number, $doctype = NULL, $planid = 0, $cdate = NULL) {
		if ($planid)
			$period = $this->DB->GetOne('SELECT period FROM numberplans WHERE id=?', array($planid));

		$period = isset($period) ? $period : YEARLY;
		$cdate = $cdate ? $cdate : time();

		switch ($period) {
			case DAILY:
				$start = mktime(0, 0, 0, date('n', $cdate), date('j', $cdate), date('Y', $cdate));
				$end = mktime(0, 0, 0, date('n', $cdate), date('j', $cdate) + 1, date('Y', $cdate));
				break;
			case WEEKLY:
				$weekstart = date('j', $cdate) - strftime('%u', $cdate) + 1;
				$start = mktime(0, 0, 0, date('n', $cdate), $weekstart, date('Y', $cdate));
				$end = mktime(0, 0, 0, date('n', $cdate), $weekstart + 7, date('Y', $cdate));
				break;
			case MONTHLY:
				$start = mktime(0, 0, 0, date('n', $cdate), 1, date('Y', $cdate));
				$end = mktime(0, 0, 0, date('n', $cdate) + 1, 1, date('Y', $cdate));
				break;
			case QUARTERLY:
				$currmonth = date('n');
				switch (date('n')) {
					case 1: case 2: case 3: $startq = 1;
						break;
					case 4: case 5: case 6: $startq = 4;
						break;
					case 7: case 8: case 9: $startq = 7;
						break;
					case 10: case 11: case 12: $startq = 10;
						break;
				}
				$start = mktime(0, 0, 0, $startq, 1, date('Y', $cdate));
				$end = mktime(0, 0, 0, $startq + 3, 1, date('Y', $cdate));
				break;
			case HALFYEARLY:
				$currmonth = date('n');
				switch (date('n')) {
					case 1: case 2: case 3: case 4: case 5: case 6: $startq = 1;
						break;
					case 7: case 8: case 9: case 10: case 11: case 12: $startq = 7;
						break;
				}
				$start = mktime(0, 0, 0, $starthy, 1, date('Y', $cdate));
				$end = mktime(0, 0, 0, $starthy + 6, 1, date('Y', $cdate));
				break;
			case YEARLY:
				$start = mktime(0, 0, 0, 1, 1, date('Y', $cdate));
				$end = mktime(0, 0, 0, 1, 1, date('Y', $cdate) + 1);
				break;
			case CONTINUOUS:
				return $this->DB->GetOne('SELECT number FROM documents 
						WHERE type = ? AND number = ? AND numberplanid = ?', array($doctype, $number, $planid)) ? TRUE : FALSE;
				break;
		}

		return $this->DB->GetOne('SELECT number FROM documents 
				WHERE cdate >= ? AND cdate < ? AND type = ? AND number = ? AND numberplanid = ?', array($start, $end, $doctype, $number, $planid)) ? TRUE : FALSE;
	}

	function GetCountryStates() {
		return $this->DB->GetAllByKey('SELECT id, name FROM states ORDER BY name', 'id');
	}

	function GetCountries() {
		return $this->DB->GetAllByKey('SELECT id, name FROM countries ORDER BY name', 'id');
	}

	function GetCountryName($id) {
		return $this->DB->GetOne('SELECT name FROM countries WHERE id = ?', array($id));
	}

	function UpdateCountryState($zip, $stateid) {
		if (empty($zip) || empty($stateid)) {
			return;
		}

		$cstate = $this->DB->GetOne('SELECT stateid FROM zipcodes WHERE zip = ?', array($zip));

		$args = array(
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_STATE] => $stateid,
			'zip' => $zip
		);
		if ($cstate === NULL) {
			$this->DB->Execute('INSERT INTO zipcodes (stateid, zip) VALUES (?, ?)', array_values($args));
			if ($SYSLOG) {
				$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ZIP]] = $this->DB->GetLastInsertID('zipcodes');
				$this->SYSLOG->AddMessage(SYSLOG_RES_ZIP, SYSLOG_OPER_ADD, $args,
					array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_STATE],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ZIP]));
			}
		} else if ($cstate != $stateid) {
			$this->DB->Execute('UPDATE zipcodes SET stateid = ? WHERE zip = ?', array_values($args));
			if ($SYSLOG) {
				$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ZIP]] =
					$this->DB->GetOne('SELECT id FROM zipcodes WHERE zip = ?', array($zip));
				$this->SYSLOG->AddMessage(SYSLOG_RES_ZIP, SYSLOG_OPER_UPDATE, $args,
					array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_STATE],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ZIP]));
			}
		}
	}

	function GetNAStypes() {
		return $this->DB->GetAllByKey('SELECT id, name FROM nastypes ORDER BY name', 'id');
	}

	function CalcAt($period, $date) {
		$m = date('n', $date);

		if ($period == YEARLY) {
			if ($m) {
				$ttime = mktime(12, 0, 0, $m, 1, 1990);
				return date('z', $ttime) + 1;
			} else {
				return 1;
			}
		} else if ($period == HALFYEARLY) {
			if ($m > 6)
				$m -= 6;
			return ($m - 1) * 100 + 1;
		} else if ($period == QUARTERLY) {
			if ($m > 9)
				$m -= 9;
			else if ($m > 6)
				$m -= 6;
			else if ($m > 3)
				$m -= 3;
			return ($m - 1) * 100 + 1;
		} else {
			return 1;
		}
	}

	/**
	 * VoIP functions
	 */
	function GetVoipAccountList($order = 'login,asc', $search = NULL, $sqlskey = 'AND') {
		if ($order == '')
			$order = 'login,asc';

		list($order, $direction) = sscanf($order, '%[^,],%s');

		($direction == 'desc') ? $direction = 'desc' : $direction = 'asc';

		switch ($order) {
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

		if (sizeof($search))
			foreach ($search as $idx => $value) {
				if ($value != '') {
					switch ($idx) {
						case 'login' :
							$searchargs[] = 'v.login ?LIKE? ' . $this->DB->Escape("%$value%");
							break;
						case 'phone' :
							$searchargs[] = 'v.phone ?LIKE? ' . $this->DB->Escape("%$value%");
							break;
						case 'password' :
							$searchargs[] = 'v.passwd ?LIKE? ' . $this->DB->Escape("%$value%");
							break;
						default :
							$searchargs[] = $idx . ' ?LIKE? ' . $this->DB->Escape("%$value%");
					}
				}
			}

		if (isset($searchargs))
			$searchargs = ' WHERE ' . implode(' ' . $sqlskey . ' ', $searchargs);

		$voipaccountlist =
				$this->DB->GetAll('SELECT v.id, v.login, v.passwd, v.phone, v.ownerid, '
				. $this->DB->Concat('c.lastname', "' '", 'c.name') . ' AS owner, v.access
				FROM voipaccounts v 
				JOIN customersview c ON (v.ownerid = c.id) '
				. (isset($searchargs) ? $searchargs : '')
				. ($sqlord != '' ? $sqlord . ' ' . $direction : ''));

		$voipaccountlist['total'] = sizeof($voipaccountlist);
		$voipaccountlist['order'] = $order;
		$voipaccountlist['direction'] = $direction;

		return $voipaccountlist;
	}

	function VoipAccountSet($id, $access = -1) {
		if ($access != -1) {
			if ($access)
				return $this->DB->Execute('UPDATE voipaccounts SET access = 1 WHERE id = ?
					AND EXISTS (SELECT 1 FROM customers WHERE id = ownerid 
						AND status = 3)', array($id));
			else
				return $this->DB->Execute('UPDATE voipaccounts SET access = 0 WHERE id = ?', array($id));
		}
		elseif ($this->DB->GetOne('SELECT access FROM voipaccounts WHERE id = ?', array($id)) == 1)
			return $this->DB->Execute('UPDATE voipaccounts SET access=0 WHERE id = ?', array($id));
		else
			return $this->DB->Execute('UPDATE voipaccounts SET access = 1 WHERE id = ?
					AND EXISTS (SELECT 1 FROM customers WHERE id = ownerid 
						AND status = 3)', array($id));
	}

	function VoipAccountSetU($id, $access = FALSE) {
		if ($access) {
			if ($this->DB->GetOne('SELECT status FROM customers WHERE id = ?', array($id)) == 3) {
				return $this->DB->Execute('UPDATE voipaccounts SET access=1 WHERE ownerid=?', array($id));
			}
		}
		else
			return $this->DB->Execute('UPDATE voipaccounts SET access=0 WHERE ownerid=?', array($id));
	}

	function VoipAccountAdd($voipaccountdata) {
		if ($this->DB->Execute('INSERT INTO voipaccounts (ownerid, login, passwd, phone, creatorid, creationdate, access)
					VALUES (?, ?, ?, ?, ?, ?NOW?, ?)', array($voipaccountdata['ownerid'],
						$voipaccountdata['login'],
						$voipaccountdata['passwd'],
						$voipaccountdata['phone'],
						$this->AUTH->id,
						$voipaccountdata['access']
				))) {
			$id = $this->DB->GetLastInsertID('voipaccounts');
			return $id;
		}
		else
			return FALSE;
	}

	function VoipAccountExists($id) {
		return ($this->DB->GetOne('SELECT v.id FROM voipaccounts v
				WHERE v.id = ? AND NOT EXISTS (
		            		SELECT 1 FROM customerassignments a
				        JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
					WHERE e.userid = lms_current_user() AND a.customerid = v.ownerid)', array($id)) ? TRUE : FALSE);
	}

	function GetVoipAccountOwner($id) {
		return $this->DB->GetOne('SELECT ownerid FROM voipaccounts WHERE id=?', array($id));
	}

	function GetVoipAccount($id) {
		if ($result = $this->DB->GetRow('SELECT id, ownerid, login, passwd, phone,
					creationdate, moddate, creatorid, modid, access
					FROM voipaccounts WHERE id = ?', array($id))) {
			$result['createdby'] = $this->GetUserName($result['creatorid']);
			$result['modifiedby'] = $this->GetUserName($result['modid']);
			$result['creationdateh'] = date('Y/m/d, H:i', $result['creationdate']);
			$result['moddateh'] = date('Y/m/d, H:i', $result['moddate']);
			$result['owner'] = $this->GetCustomerName($result['ownerid']);
			return $result;
		}
		else
			return FALSE;
	}

	function GetVoipAccountIDByLogin($login) {
		return $this->DB->GetAll('SELECT id FROM voipaccounts WHERE login=?', array($login));
	}

	function GetVoipAccountIDByPhone($phone) {
		return $this->DB->GetOne('SELECT id FROM voipaccounts WHERE phone=?', array($phone));
	}

	function GetVoipAccountLogin($id) {
		return $this->DB->GetOne('SELECT login FROM voipaccounts WHERE id=?', array($id));
	}

	function DeleteVoipAccount($id) {
		$this->DB->BeginTrans();
		$this->DB->Execute('DELETE FROM voipaccounts WHERE id = ?', array($id));
		$this->DB->CommitTrans();
	}

	function VoipAccountUpdate($voipaccountdata) {
		$this->DB->Execute('UPDATE voipaccounts SET login=?, passwd=?, phone=?, moddate=?NOW?, access=?, 
				modid=?, ownerid=? WHERE id=?', array($voipaccountdata['login'],
				$voipaccountdata['passwd'],
				$voipaccountdata['phone'],
				$voipaccountdata['access'],
				$this->AUTH->id,
				$voipaccountdata['ownerid'],
				$voipaccountdata['id']
		));
	}

	function GetCustomerVoipAccounts($id) {
		if ($result['accounts'] = $this->DB->GetAll('SELECT id, login, passwd, phone, ownerid, access
				FROM voipaccounts WHERE ownerid=? 
				ORDER BY login ASC', array($id))) {
			$result['total'] = sizeof($result['accounts']);
		}
		return $result;
	}

	function GetConfigSections() {
		$sections = $this->DB->GetCol('SELECT DISTINCT section FROM uiconfig ORDER BY section');
		$sections = array_unique(array_merge($sections,
			array('phpui', 'finances', 'invoices', 'receipts', 'mail', 'sms', 'zones', 'tarifftypes')));
		sort($sections);
		return $sections;
	}

	function GetNodeSessions($nodeid) {
		$nodesessions = $this->DB->GetAll('SELECT INET_NTOA(ipaddr) AS ipaddr, mac, start, stop, download, upload
			FROM nodesessions WHERE nodeid = ? ORDER BY stop DESC LIMIT 10', array($nodeid));
		if (!empty($nodesessions))
			foreach ($nodesessions as $idx => $session) {
				list ($number, $unit) = setunits($session['download']);
				$nodesessions[$idx]['download'] = round($number, 2) . ' ' . $unit;
				list ($number, $unit) = setunits($session['upload']);
				$nodesessions[$idx]['upload'] = round($number, 2) . ' ' . $unit;
				$nodesessions[$idx]['duration'] = uptimef($session['stop'] - $session['start']);
			}
		return $nodesessions;
	}
}

?>
