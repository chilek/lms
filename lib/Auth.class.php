<?php

/*
 * LMS version 1.6-cvs
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

class Auth {

	var $id;
	var $login;
	var $logname;
	var $passwd;
	var $islogged = FALSE;
	var $passverified = FALSE;
	var $hostverified = FALSE;
	var $last;
	var $ip;
	var $lastip;
	var $error;
	var $_version = '1.6-cvs';
	var $_revision = '$Revision$';
	var $DB = NULL;
	var $SESSION = NULL;

	function Auth(&$DB, &$SESSION)
	{
		$this->DB = &$DB;
		$this->SESSION = &$SESSION;
		$this->ip = str_replace('::ffff:', '', $_SERVER['REMOTE_ADDR']);
		
		if(isset($_GET['override']))
			$loginform = $_GET['loginform'];
		elseif(isset($_POST['loginform']))
			$loginform = $_POST['loginform'];
		
		if(isset($loginform))
		{
			$this->login = $loginform['login'];
			$this->passwd = $loginform['pwd'];
			$this->SESSION->save('session_timestamp', time());
			writesyslog('Login attempt by '.$this->login, LOG_INFO);
		}
		elseif($this->DB->GetOne('SELECT COUNT(id) FROM admins') == 0)
		{
			$this->islogged = TRUE;
			$this->passwd = 'EMPTY';
			$this->logname = '';
			$_GET['m'] = 'adminadd';
			return TRUE;
		}
		else
		{
			$this->SESSION->restore('session_login', $this->login);
			$this->SESSION->restore('session_passwd', $this->passwd);
		}
		
		if($this->VerifyAdmin())
		{
			$this->SESSION->restore('session_last', $this->last);
			$this->SESSION->restore('session_lastip', $this->lastip);
			if(isset($loginform))
			{
				$admindata = $this->DB->GetRow('SELECT lastlogindate, lastloginip FROM admins WHERE id=?',array($this->id));
				$this->last = $admindata['lastlogindate'];
				$this->lastip = $admindata['lastloginip'];
				
				$this->DB->Execute('UPDATE admins SET lastlogindate=?, lastloginip=? WHERE id=?', array(time(), $this->ip ,$this->id));
				writesyslog('User '.$this->login.' logged in.', LOG_INFO);
			}
			$this->SESSION->save('session_login', $this->login);
			$this->SESSION->save('session_passwd', $this->passwd);
			$this->SESSION->save('session_last', $this->last);
			$this->SESSION->save('session_lastip', $this->lastip);
		}
		else
		{
			$this->islogged = FALSE;
			if(isset($loginform))
			{
				if(!$this->hostverified)
					writesyslog('Bad host ('.$this->ip.') for '.$this->login, LOG_WARNING);
				if(!$this->passverified)
					writesyslog('Bad password for '.$this->login, LOG_WARNING);
				
				$this->DB->Execute('UPDATE admins SET failedlogindate=?, failedloginip=? WHERE login=?',array(time(),$_SERVER['REMOTE_ADDR'],$this->login));
			}
			$this->LogOut();
		}
	}

	function _postinit()
	{
		return TRUE;
	}

	function LogOut()
	{
		if ($this->islogged)
			writesyslog('User '.$this->login.' logged out.',LOG_INFO);
		unset($this->login);
		unset($this->password);
		$this->SESSION->finish();
	}		
	
	function VerifyPassword($dbpasswd = '')
	{
		if (crypt($this->passwd,$dbpasswd)==$dbpasswd)
			return TRUE;
		else 
		{
			if(isset($this->login))
				$this->error = trans('Wrong password or login.');
			else
				$this->error = trans('Login yourself, please.');
			return FALSE;
		}
	}

	function VerifyHost($hosts = '')
	{
		if(!$hosts)
			return TRUE;
		
		$allowedlist = explode(',', $hosts);
		$isin = FALSE;
		
		foreach($allowedlist as $value)
		{
			list($net,$mask) = sscanf($value, '%[0-9.]/%[0-9]');
			$net = trim($net);
			$mask = trim($mask);
			if($mask == '')
				$mask = '32';
			if($mask >= 0 || $mask <= 32)
				$mask = prefix2mask($mask);
			if(isipinstrict($this->ip, $net, $mask))
			{
				$isin = TRUE;
				break;
			}
		}

		if($isin)
			return TRUE;
		else 
		{
			$this->error = trans('Access denied!');
			return FALSE;
		}
	}
	
	function VerifyAdmin()
	{
		$admin = $this->DB->GetRow('SELECT id, name, passwd, hosts FROM admins WHERE login=? AND deleted=0', array($this->login));
		
		$this->passverified = $this->VerifyPassword($admin['passwd']);
		$this->hostverified = $this->VerifyHost($admin['hosts']);
		$this->logname = $admin['name'];
		$this->id = $admin['id'];
		$this->islogged = ($this->passverified && $this->hostverified);
		
		return $this->islogged;
	}
}

?>
