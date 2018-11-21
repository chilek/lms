<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

class Auth {

	private $id = null;
	public $login;
	public $logname;
	public $passwd;
	public $islogged = FALSE;
	public $nousers = FALSE;
	public $passverified = FALSE;
	public $hostverified = FALSE;
	public $access = FALSE;
	public $accessfrom = FALSE;
	public $accessto = FALSE;
	public $last;
	public $ip;
	public $lastip;
	public $passwdrequiredchange = FALSE;
	public $error;
	public $_version = '1.11-git';
	public $_revision = '$Revision$';
	public $DB = NULL;
	public $SESSION = NULL;
	public $SYSLOG = NULL;

	private static $auth = null;

	public static function GetCurrentUser() {
		if (self::$auth)
			return self::$auth->id;
		return null;
	}

	public function __construct(&$DB, &$SESSION) {
		self::$auth = $this;
		$this->DB = &$DB;
		$this->SESSION = &$SESSION;
		$this->SYSLOG = SYSLOG::getInstance();
		//$this->_revision = preg_replace('/^.Revision: ([0-9.]+).*/', '\1', $this->_revision);
		$this->_revision = '';

		if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		elseif (isset($_SERVER['HTTP_CLIENT_IP']))
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		else
			$ip = $_SERVER['REMOTE_ADDR'];

		$this->ip = str_replace('::ffff:', '', $ip);

		if (isset($_GET['override']))
			$loginform = $_GET['loginform'];
		elseif (isset($_POST['loginform']))
			$loginform = $_POST['loginform'];

		$this->SESSION->restore('session_login', $this->login);

		if ($this->login)
			$this->islogged = TRUE;
		elseif (isset($loginform))
		{
			$this->login = $loginform['login'];
			$this->passwd = $loginform['pwd'];
			writesyslog('Login attempt by ' . $this->login, LOG_INFO);
		}
		elseif ($this->DB->GetOne('SELECT COUNT(id) FROM users') == 0)
		{
			$this->islogged = TRUE;
			$this->nousers = TRUE;
			$_GET['m'] = 'useradd';
			return TRUE;
		}

		if ($this->islogged || ($this->login && $this->VerifyUser()))
		{
			$this->SESSION->restore('session_passwdrequiredchange', $this->passwdrequiredchange);
			if (empty($this->last))
			{
				$this->SESSION->restore('session_last', $this->last);
				$this->SESSION->restore('session_lastip', $this->lastip);
			}

			$this->logname = $this->logname ? $this->logname : $this->SESSION->get('session_logname');
			$this->id = $this->id ? $this->id : $this->SESSION->get('session_id');

			if (isset($loginform))
			{
				$this->DB->Execute('UPDATE users SET lastlogindate=?, lastloginip=? WHERE id=?', array(time(), $this->ip ,$this->id));
				writesyslog('User '.$this->login.' logged in.', LOG_INFO);
				if ($this->SYSLOG) {
					$this->SYSLOG->NewTransaction('auth', $this->id);
					$this->SYSLOG->AddMessage(SYSLOG::RES_USER, SYSLOG::OPER_USERLOGIN,
						array(SYSLOG::RES_USER => $this->id, 'ip' => $this->ip, 'useragent' => $_SERVER['HTTP_USER_AGENT']));
				}
			}

			$this->SESSION->save('session_id', $this->id);
			$this->SESSION->save('session_login', $this->login);
			$this->SESSION->restore_user_settings();
			$this->SESSION->save('session_logname', $this->logname);
			$this->SESSION->save('session_last', $this->last);
			$this->SESSION->save('session_lastip', $this->lastip);
		}
		else
		{
			if (isset($loginform))
			{
				if ($this->id)
				{
					if (!$this->hostverified)
						writesyslog('Bad host (' . $this->ip . ') for ' . $this->login, LOG_WARNING);
					if (!$this->passverified)
						writesyslog('Bad password for ' . $this->login, LOG_WARNING);

					$this->DB->Execute('UPDATE users SET failedlogindate=?, failedloginip=? WHERE id = ?',
						array(time(), $this->ip, $this->id));
					if ($this->SYSLOG) {
						$this->SYSLOG->NewTransaction('auth', $this->id);
						$this->SYSLOG->AddMessage(SYSLOG::RES_USER, SYSLOG::OPER_USERLOGFAIL,
							array(SYSLOG::RES_USER => $this->id, 'ip' => $this->ip, 'useragent' => $_SERVER['HTTP_USER_AGENT']));
					}
				} else {
					writesyslog('Unknown login ' . $this->login . ' from ' . $this->ip, LOG_WARNING);
				}
			}

			if (!$this->error)
				$this->error = trans('Please login.');

			$this->LogOut();
		}
	}

	public function _postinit() {
		return TRUE;
	}

	public function LogOut() {
		if ($this->islogged) {
			writesyslog('User ' . $this->login . ' logged out.', LOG_INFO);
			if ($this->SYSLOG) {
				$this->SYSLOG->NewTransaction('auth', $this->id);
				$this->SYSLOG->AddMessage(SYSLOG::RES_USER, SYSLOG::OPER_USERLOGOUT,
					array(SYSLOG::RES_USER => $this->id, 'ip' => $this->ip, 'useragent' => $_SERVER['HTTP_USER_AGENT']));
			}
		}
		$this->SESSION->finish();
	}

	public function VerifyPassword($dbpasswd = '') {
		if (crypt($this->passwd, $dbpasswd) == $dbpasswd)
			return TRUE;

		$this->error = trans('Wrong password or login.');
		return FALSE;
	}

	public function VerifyAccess($access) {
	    $access = intval($access);
	    if (empty($access)) {
		$this->error = trans('Account is disabled');
		return FALSE;
	    }
	    else return TRUE;
	}
	
	public function VerifyAccessFrom($access) {
	    $access = intval($access);
	    if (empty($access)) return TRUE;
	    if ($access < time()) return TRUE;
	    if ($access > time()) {
		$this->error = trans('Account is not active');
		return FALSE;
	    }
	}
	
	public function VerifyAccessTo($access) {
	    $access = intval($access);
	    if (empty($access)) return TRUE;
	    if ($access > time()) return TRUE;
	    if ($access < time()) {
		$this->error = trans('Account is not active');
		return FALSE;
	    }
	}

	public function VerifyHost($hosts = '') {
		if (!$hosts)
			return TRUE;

		$allowedlist = explode(',', $hosts);
		$isin = FALSE;

		foreach ($allowedlist as $value)
		{
			$net = '';
			$mask = '';

			if (strpos($value, '/') === FALSE)
				$net = $value;
			else
				list($net, $mask) = explode('/', $value);

			$net = trim($net);
			$mask = trim($mask);

			if ($mask == '')
				$mask = '255.255.255.255';
			elseif (is_numeric($mask))
				$mask = prefix2mask($mask);

			if (isipinstrict($this->ip, $net, $mask))
				return TRUE;
		}

		$this->error = trans('Access denied!');
		return FALSE;
	}

	public function VerifyUser() {
		$this->islogged = false;

		if ($user = $this->DB->GetRow('SELECT id, name, passwd, hosts, lastlogindate, lastloginip, 
			passwdexpiration, passwdlastchange, access, accessfrom, accessto
			FROM vusers WHERE login=? AND deleted=0', array($this->login)))
		{
			$this->logname = $user['name'];
			$this->id = $user['id'];
			$this->last = $user['lastlogindate'];
			$this->lastip = $user['lastloginip'];
			$this->passwdexpiration = $user['passwdexpiration'];
			$this->passwdlastchange = $user['passwdlastchange'];

			$this->passverified = $this->VerifyPassword($user['passwd']);
			$this->hostverified = $this->VerifyHost($user['hosts']);
			$this->access = $this->VerifyAccess($user['access']);
			$this->accessfrom = $this->VerifyAccessFrom($user['accessfrom']);
			$this->accessto = $this->VerifyAccessTo($user['accessto']);
			$this->islogged = ($this->passverified && $this->hostverified && $this->access && $this->accessfrom && $this->accessto);

			if ($this->islogged && $this->passwdexpiration
				&& (time() - $this->passwdlastchange) / 86400 >= $user['passwdexpiration'])
				$this->SESSION->save('session_passwdrequiredchange', TRUE);
		} else {
			$this->error = trans('Wrong password or login.');
		}

		return $this->islogged;
	}
}

?>
