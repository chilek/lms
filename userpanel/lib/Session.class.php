<?php

/*
 *  LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

require_once('authentication.inc'); 

class Session {

	var $id;
	var $login;
	var $passwd;
	var $ip;
	var $islogged = FALSE;
	var $error;
	var $_version = '1.11-cvs';
	var $_revision = '$Revision$';

	function Session(&$DB,$timeout = 600)
	{
		session_start();
		$this->DB = &$DB;
		$this->_revision = preg_replace('/^.Revision: ([0-9.]+).*/i', '\1', $this->_revision);
		$this->ip = str_replace('::ffff:', '', $_SERVER['REMOTE_ADDR']);

		if(isset($_GET['override']))
			$loginform = $_GET['loginform'];
		elseif(isset($_POST['loginform']))
			$loginform = $_POST['loginform'];
		
		if(isset($loginform))
		{
			$this->login = trim($loginform['login']);
			$this->passwd = trim($loginform['pwd']);
			$_SESSION['session_timestamp'] = time();
		}
		else
		{
			$this->login = isset($_SESSION['session_login']) ? $_SESSION['session_login'] : NULL;
			$this->passwd = isset($_SESSION['session_passwd']) ? $_SESSION['session_passwd'] : NULL;
			$this->id = isset($_SESSION['session_id']) ? $_SESSION['session_id'] : 0;
		}

		$authdata = $this->VerifyPassword();
		
		if($authdata != NULL)
		{
			$authinfo = GetCustomerAuthInfo($authdata['id']);
			if ($authinfo != NULL && isset($authinfo['enabled']) 
				&& $authinfo['enabled'] == 0
				&& time() - $authinfo['failedlogindate'] < 600
			)
				$authdata['passwd'] = NULL;
		}
		
		if($authdata != NULL && $authdata['passwd'] != NULL && $this->TimeOut($timeout))
		{
			$this->islogged = TRUE;
			$this->id = $authdata['id'];
			$_SESSION['session_login'] = $this->login;
			$_SESSION['session_passwd'] = $this->passwd;
			$_SESSION['session_id'] = $this->id;

			if ($this->id)
			{
				$authinfo = GetCustomerAuthInfo($this->id);
				if ($authinfo == NULL || $authinfo['failedlogindate'] == NULL)
				{
					$authinfo['failedlogindate'] = 0;
					$authinfo['failedloginip'] = '';
				}
				$authinfo['id'] = $this->id;
				$authinfo['lastlogindate'] = time();
				$authinfo['lastloginip'] = $this->ip;
				$authinfo['enabled'] = 3;
				SetCustomerAuthInfo($authinfo);
			}
		}else{
			$this->islogged = FALSE;
			if (isset($loginform))
			{
				writesyslog("Bad password for customer ID:".$this->login,LOG_WARNING);

				if ($authdata != NULL && $authdata['passwd'] == NULL)
				{
					$authinfo = GetCustomerAuthInfo($authdata['id']);
					if ($authinfo == NULL)
					{
						$authinfo['lastlogindate'] = 0;
						$authinfo['lastloginip'] = '';
						$authinfo['failedlogindate'] = 0;
					}
					
					if (time() - $authinfo['failedlogindate'] < 600)
					{
						if (isset($authinfo['enabled']) && $authinfo['enabled'] > 0)
							$authinfo['enabled'] -= 1;
					}
					else
						$authinfo['enabled'] = 2;
					
					$authinfo['failedlogindate'] = time();
					$authinfo['failedloginip'] = $this->ip;
					SetCustomerAuthInfo($authinfo);
				}
				
				$this->error = trans('Access denied!');
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
			session_destroy();
		unset($this->login);
		unset($this->password);
		unset($this->id);
		unset($_SESSION);
	}		
	
	function TimeOut($timeout = 600)
	{
		if( (time()-$_SESSION['session_timestamp']) > $timeout )
		{
			$this->error = trans('Idle time limit exceeded ($a sec.)', $timeout);
			return FALSE;
		}
		else
		{
			$_SESSION['session_timestamp'] = time();
			return TRUE;
		}
	}
	
	function VerifyPassword()
	{
		if(empty($this->login))
		{
			$this->error = trans('Please login.');
			return NULL;
		}
		
		// customer authorization ways
		// $authinfo = GetCustomerIDByPhoneAndPIN($this->login, $this->passwd);
		// $authinfo = GetCustomerIDByContractAndPIN($this->login, $this->passwd);

		$authinfo = GetCustomerIDByIDAndPIN($this->login, $this->passwd);
		
		if($authinfo != NULL && $authinfo['id'] != NULL)
			return $authinfo;
		else 
		{
			return NULL;
		}
	}
}

?>
