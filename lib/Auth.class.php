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

class Auth {

	var $id;
	var $login;
	var $logname;
	var $passwd;
	var $islogged = FALSE;
	var $last;
	var $lastip;
	var $error;
	var $_version = '1.5-cvs';
	var $_revision = '$Revision$';
	var $DB = NULL;
	var $SESSION = NULL;

	function Auth(&$DB, &$SESSION)
	{
		session_start();		// we will remove this when all references
						// to $_SESSION variable will be replaced
		
		$this->DB = &$DB;
		$this->SESSION = &$SESSION;
		
		if($_GET['override'])
			$loginform = $_GET['loginform'];
		else
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
			$this->logname = trans('not logged in');
			$_GET['m'] = 'adminadd';
			return TRUE;
		}
		else
		{
			$this->SESSION->restore('session_login', $this->login);
			$this->SESSION->restore('session_passwd', $this->passwd);
		}
		
		if($this->VerifyPassword())
		{
			$this->islogged = TRUE;
			$admindata = $this->DB->GetRow('SELECT id, name FROM admins WHERE login=?',array($this->login));
			$this->id = $admindata['id'];
			$this->logname = $admindata['name'];
			$this->SESSION->restore('session_last', $this->last);
			$this->SESSION->restore('session_lastip', $this->lastip);
			if(isset($loginform))
			{
				$admindata = $this->DB->GetRow('SELECT lastlogindate, lastloginip FROM admins WHERE id=?',array($this->id));
				$this->last = $admindata['lastlogindate'];
				$this->lastip = $admindata['lastloginip'];
				
				$this->DB->Execute('UPDATE admins SET lastlogindate=?, lastloginip=? WHERE id=?', array(time(),$_SERVER['REMOTE_ADDR'],$this->id));
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
		session_destroy();
		unset($this->login);
		unset($this->password);
		$this->SESSION->finish();
	}		
	
	function VerifyPassword()
	{
		$dbpasswd = $this->DB->GetOne('SELECT passwd FROM admins WHERE login=? AND deleted=0',array($this->login));
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
}

?>
