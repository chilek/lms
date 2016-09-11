<?php

/*
 *  LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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

class Session {
	public $id;
	private $login;
	private $passwd;
	private $ip;
	private $db;
	public $islogged = false;
	public $error;

	public $_content = array();     // session content array

	public function __construct(&$DB, $timeout = 600) {
		global $LMS;

		session_start();
		$this->db = &$DB;
		$this->ip = str_replace('::ffff:', '', $_SERVER['REMOTE_ADDR']);

		if (isset($_GET['override']))
			$loginform = $_GET['loginform'];
		elseif (isset($_POST['loginform']))
			$loginform = $_POST['loginform'];
		elseif (isset($_POST['remindform']))
			$remindform = $_POST['remindform'];

		if (isset($remindform)) {
			$ten = preg_replace('/-/', '', $remindform['ten']);
			$params = array($ten, $ten);
			switch ($remindform['type']) {
				case 1:
					if (!check_email($remindform['email']))
						return;
					$join = 'JOIN customercontacts cc ON cc.customerid = c.id';
					$where = ' AND contact = ? AND cc.type & ? > 0';
					$params = array_merge($params, array($remindform['email'],(CONTACT_EMAIL|CONTACT_INVOICES|CONTACT_NOTIFICATIONS)));
					break;
				case 2:
					if (!preg_match('/^[0-9]+$/', $remindform['phone']))
						return;
					$join = 'JOIN customercontacts cc ON cc.customerid = c.id';
					$where = ' AND contact = ? AND cc.type & ? = ?';
					$params = array_merge($params,
						array(preg_replace('/ -/', '', $remindform['phone']),
							CONTACT_MOBILE, CONTACT_MOBILE));
					break;
				default:
					return;
			}
			$customer = $this->db->GetRow("SELECT c.id, pin FROM customers c $join WHERE (REPLACE(ten, '-', '') = ? OR ssn = ?)"
				. $where, $params);
			if (!$customer) {
				$this->error = trans('Credential reminder couldn\'t be sent!');
				return;
			}
			if ($remindform['type'] == 1) {
				$subject = ConfigHelper::getConfig('userpanel.reminder_mail_subject');
				$body = ConfigHelper::getConfig('userpanel.reminder_mail_body');
			} else
				$body = ConfigHelper::getConfig('userpanel.reminder_sms_body');
			$body = str_replace('%id', $customer['id'], $body);
			$body = str_replace('%pin', $customer['pin'], $body);
			if ($remindform['type'] == 1)
				$LMS->SendMail($remindform['email'],
					array('From' => '<' . ConfigHelper::getConfig('userpanel.reminder_mail_sender') . '>',
						'To' => '<' . $remindform['email'] . '>',
						'Subject' => $subject), $body);
			else
				$LMS->SendSMS($remindform['phone'], $body);
			$this->error = trans('Credential reminder has been sent!');
			return;
		}

		if (isset($loginform)) {
			$this->login = trim($loginform['login']);
			$this->passwd = trim($loginform['pwd']);
			$_SESSION['session_timestamp'] = time();
		} else {
			$this->login = isset($_SESSION['session_login']) ? $_SESSION['session_login'] : NULL;
			$this->passwd = isset($_SESSION['session_passwd']) ? $_SESSION['session_passwd'] : NULL;
			$this->id = isset($_SESSION['session_id']) ? $_SESSION['session_id'] : 0;
		}

		$authdata = $this->VerifyPassword();

		if ($authdata != NULL) {
			$authinfo = $this->GetCustomerAuthInfo($authdata['id']);
			if ($authinfo != NULL && isset($authinfo['enabled'])
				&& $authinfo['enabled'] == 0
				&& time() - $authinfo['failedlogindate'] < 600)
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
				$authinfo = $this->GetCustomerAuthInfo($this->id);
				if ($authinfo == NULL || $authinfo['failedlogindate'] == NULL)
				{
					$authinfo['failedlogindate'] = 0;
					$authinfo['failedloginip'] = '';
				}
				$authinfo['id'] = $this->id;
				$authinfo['lastlogindate'] = time();
				$authinfo['lastloginip'] = $this->ip;
				$authinfo['enabled'] = 3;
				$this->SetCustomerAuthInfo($authinfo);
			}
		} else {
			$this->islogged = FALSE;
			if (isset($loginform))
			{
				writesyslog("Bad password for customer ID:".$this->login,LOG_WARNING);

				if ($authdata != NULL && $authdata['passwd'] == NULL)
				{
					$authinfo = $this->GetCustomerAuthInfo($authdata['id']);
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

					$authinfo['id'] = $authdata['id'];
					$authinfo['failedlogindate'] = time();
					$authinfo['failedloginip'] = $this->ip;
					$this->SetCustomerAuthInfo($authinfo);
				}
				
				$this->error = trans('Access denied!');
			}
			
			$this->LogOut();
		}
	}

	public function _postinit() {
		return TRUE;
	}

	public function LogOut() {
		if ($this->islogged)
			session_destroy();
		unset($this->login);
		unset($this->password);
		unset($this->id);
		unset($_SESSION);
	}

	public function TimeOut($timeout = 600) {
		if ((time()-$_SESSION['session_timestamp']) > $timeout) {
			$this->error = trans('Idle time limit exceeded ($a sec.)', $timeout);
			return FALSE;
		} else {
			$_SESSION['session_timestamp'] = time();
			return TRUE;
		}
	}

	private function GetCustomerIDByPhoneAndPIN()
	{
		if(!preg_match('/^[0-9]+$/', $this->passwd))
			return null;

		$authinfo['id'] = $this->db->GetOne('SELECT c.id FROM customers c, customercontacts cc
			WHERE customerid = c.id AND contact = ? AND cc.type < ? AND deleted = 0 LIMIT 1', 
			array($this->login, CONTACT_EMAIL));

		if (empty($authinfo['id']))
			return null;

		$authinfo['passwd'] = $this->db->GetOne('SELECT pin FROM customers
			WHERE pin = ? AND id = ?', array($this->passwd, $authinfo['id']));

		return $authinfo;
	}

	private function GetCustomerIDByIDAndPIN()
	{
		if(!preg_match('/^[0-9]+$/', $this->passwd) || !preg_match('/^[0-9]+$/', $this->login))
			return null;

		$authinfo['id'] = $this->db->GetOne('SELECT id FROM customers
			WHERE id = ? AND deleted = 0', array($this->login));

		if (empty($authinfo['id']))
			return null;

		$authinfo['passwd'] = $this->db->GetOne('SELECT pin FROM customers
			WHERE pin = ? AND id = ?', array($this->passwd, $this->login));

		return $authinfo;
	}

	private function GetCustomerIDByDocumentAndPIN()
	{
		if(!preg_match('/^[0-9]+$/', $this->passwd))
			return null;

		$authinfo['id'] = $this->db->GetOne('SELECT c.id FROM customers c
			JOIN documents d ON d.customerid = c.id
			WHERE fullnumber = ? AND deleted = 0',
			array($this->login));

		if (empty($authinfo['id']))
			return null;

		$authinfo['passwd'] = $this->db->GetOne('SELECT pin FROM customers
			WHERE pin = ? AND id = ?', array($this->passwd, $authinfo['id']));

		return $authinfo;
	}

	private function GetCustomerIDByEmailAndPIN()
	{
		if (!preg_match('/^[0-9]+$/', $this->passwd))
			return null;

		$authinfo['id'] = $this->db->GetOne('SELECT c.id FROM customers c, customercontacts cc
			WHERE cc.customerid = c.id AND contact = ? AND cc.type & ? > 0 AND deleted = 0 LIMIT 1',
			array($this->login, (CONTACT_EMAIL|CONTACT_INVOICES|CONTACT_NOTIFICATIONS)));

		if (empty($authinfo['id']))
			return null;

		$authinfo['passwd'] = $this->db->GetOne('SELECT pin FROM customers
			WHERE pin = ? AND id = ?', array($this->passwd, $authinfo['id']));

		return $authinfo;
	}

	private function GetCustomerAuthInfo($customerid)
	{
		return $this->db->GetRow('SELECT customerid AS id, lastlogindate, lastloginip, failedlogindate, failedloginip, enabled FROM up_customers WHERE customerid=?',
			array($customerid));
	}

	private function SetCustomerAuthInfo($authinfo)
	{
		$actauthinfo = $this->GetCustomerAuthInfo($authinfo['id']);
		if ($actauthinfo != null) {
			$this->db->Execute('UPDATE up_customers SET lastlogindate=?, lastloginip=?, failedlogindate=?, failedloginip=?, enabled=? WHERE customerid=?',
				array($authinfo['lastlogindate'], $authinfo['lastloginip'], $authinfo['failedlogindate'], $authinfo['failedloginip'],
				$authinfo['enabled'], $authinfo['id']));
		} else {
			$this->db->Execute('INSERT INTO up_customers(customerid, lastlogindate, lastloginip, failedlogindate, failedloginip, enabled) VALUES (?, ?, ?, ?, ?, ?)',
				array($authinfo['id'], $authinfo['lastlogindate'], $authinfo['lastloginip'],
				$authinfo['failedlogindate'], $authinfo['failedloginip'], $authinfo['enabled']));
		}
	}

	public function VerifyPassword() {
		if (empty($this->login)) {
			$this->error = trans('Please login.');
			return NULL;
		}

		switch (ConfigHelper::getConfig('userpanel.auth_type', 1)) {
			case 1:
				$authinfo = $this->GetCustomerIDByIDAndPIN();
				break;
			case 2:
				$authinfo = $this->GetCustomerIDByPhoneAndPIN();
				break;
			case 3:
				$authinfo = $this->GetCustomerIDByDocumentAndPIN();
				break;
			case 4:
				$authinfo = $this->GetCustomerIDByEmailAndPIN();
				break;
		}

		if (!empty($authinfo) && isset($authinfo['id']))
			return $authinfo;
		else
			return NULL;
	}
}

?>
