<?php

/*
 * LMS version 1.7-cvs
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
	var $_version = '1.7-cvs';
	var $_revision = '$Revision$';
	var $DB = NULL;
	var $SESSION = NULL;

	function Auth(&$DB, &$SESSION)
	{
		$this->DB = &$DB;
		$this->SESSION = &$SESSION;
                $this->_revision = eregi_replace('^.Revision: ([0-9.]+).*','\1', $this->_revision);
	       		
		if($_SERVER['HTTP_X_FORWARDED_FOR'])
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		elseif($_SERVER['HTTP_CLIENT_IP'])
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		else
			$ip = $_SERVER['REMOTE_ADDR'];
		  
		$this->ip = str_replace('::ffff:', '', $ip);
		
		if(isset($_GET['override']))
			$loginform = $_GET['loginform'];
		elseif(isset($_POST['loginform']))
			$loginform = $_POST['loginform'];
		
		$this->SESSION->restore('session_login', $this->login);
		
		if($this->login)
		{
			$this->islogged = TRUE; 
		}		
		elseif(isset($loginform))
		{
			$this->login = $loginform['login'];
			$this->passwd = $loginform['pwd'];
			$this->SESSION->save('session_timestamp', time());
			writesyslog('Login attempt by '.$this->login, LOG_INFO);
		}
		elseif($this->DB->GetOne('SELECT COUNT(id) FROM users') == 0)
		{
			$this->islogged = TRUE;
			$this->passwd = 'EMPTY';
			$this->logname = '';
			$_GET['m'] = 'useradd';
			return TRUE;
		}

		if($this->islogged || $this->VerifyUser())
		{
			$this->SESSION->restore('session_last', $this->last);
			$this->SESSION->restore('session_lastip', $this->lastip);
			$this->logname = $this->logname ? $this->logname : $this->SESSION->get('session_logname');
			$this->id = $this->id ? $this->id : $this->SESSION->get('session_id');

			if(isset($loginform))
			{
				$userdata = $this->DB->GetRow('SELECT lastlogindate, lastloginip FROM users WHERE id=?',array($this->id));
				$this->last = $userdata['lastlogindate'];
				$this->lastip = $userdata['lastloginip'];
				
				$this->DB->Execute('UPDATE users SET lastlogindate=?, lastloginip=? WHERE id=?', array(time(), $this->ip ,$this->id));
				writesyslog('User '.$this->login.' logged in.', LOG_INFO);
			}
			
			$this->SESSION->save('session_id', $this->id);
			$this->SESSION->save('session_login', $this->login);
			$this->SESSION->save('session_logname', $this->logname);
			$this->SESSION->save('session_last', $this->last);
			$this->SESSION->save('session_lastip', $this->lastip);
		}
		else
		{
			if(isset($loginform))
			{
				if(!$this->hostverified)
					writesyslog('Bad host ('.$this->ip.') for '.$this->login, LOG_WARNING);
				if(!$this->passverified)
					writesyslog('Bad password for '.$this->login, LOG_WARNING);
				
				$this->DB->Execute('UPDATE users SET failedlogindate=?, failedloginip=? WHERE login=?',array(time(), $this->ip, $this->login));
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
		$this->SESSION->finish();
	}		
	
	function VerifyPassword($dbpasswd = '')
	{
		if(crypt($this->passwd,$dbpasswd)==$dbpasswd)
			return TRUE;
		else 
		{
			if(isset($this->login))
				$this->error = trans('Wrong password or login.');
			else
				$this->error = trans('Please login.');
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
	
	function VerifyUser()
	{
		$user = $this->DB->GetRow('SELECT id, name, passwd, hosts FROM users WHERE login=? AND deleted=0', array($this->login));

		$this->logname = $user['name'];
		$this->id = $user['id'];
	
		$this->passverified = $this->VerifyPassword($user['passwd']);
		$this->hostverified = $this->VerifyHost($user['hosts']);
		$this->islogged = ($this->passverified && $this->hostverified);
		
		return $this->islogged;
	}
}

?>
