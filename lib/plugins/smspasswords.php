<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

class lms_smspasswords_plugin
{
	private $lms;
	private $authenticated = false;

	function __construct($LMS) {
		$this->lms = $LMS;
	}

	function access_table_init($vars) {
		$vars['accesstable'][250] = array('name' => trans('one-time passwords'), 'privilege' => 'onetime_passwords');
		ksort($vars['accesstable']);

		return $vars;
	}

	function send_new_password($phone) {
		global $SMARTY;

		$LMS = $this->lms;
		$SESSION = $LMS->AUTH->SESSION;

		$retries = 3;
		$smspassword = strval(rand(10000000, 99999999));
		$LMS->SendSMS($phone, trans('Your one-time password is $a', $smspassword));
		$SESSION->save('session_smspassword', $smspassword);
		$SESSION->save('session_retries', $retries);
		$SMARTY->assign('target', '?' . $_SERVER['QUERY_STRING']);
		$SMARTY->assign('retries', $retries);
		$SMARTY->display('smspassword.html');
	}

	function module_load_before($vars) {
		global $SMARTY;

		$LMS = $this->lms;
		$SESSION = $LMS->AUTH->SESSION;
		$SESSION->restore('session_smsauthenticated', $this->authenticated);

		//$this->authenticated = true;
		if (!$this->authenticated) {
			$module = $vars['module'];
			$SESSION->restore('session_retries', $retries);
			if (isset($_POST['smspasswordform']['passwd'])) {
				$SESSION->restore('session_smspassword', $smspassword);
				$passwd = $_POST['smspasswordform']['passwd'];
				if ($passwd == $smspassword) {
					$SESSION->save('session_smsauthenticated', true);
					$SESSION->remove('session_smspassword');
					$SESSION->remove('session_retries');
					$vars['abort'] = false;
				} else {
					$retries--;
					if (empty($retries)) {
						$phone = $LMS->DB->GetOne('SELECT phone FROM users WHERE id = ?',
							array(Auth::GetCurrentUser()));
						$this->send_new_password($phone);
						$vars['abort'] = true;
						return $vars;
					}
					$SESSION->save('session_retries', $retries);
					$SMARTY->assign('retries', $retries);
					$SMARTY->assign('target', $_POST['smspasswordform']['target']);
					$SMARTY->display('smspassword.html');
					$vars['abort'] = true;
				}
				return $vars;
			}
			$phone = $LMS->DB->GetOne('SELECT phone FROM users WHERE id = ?',
				array(Auth::GetCurrentUser()));
			$rights = $LMS->GetUserRights(Auth::GetCurrentUser());
			if (empty($phone) || !array_search(250, $rights)) {
				$SESSION->save('session_smsauthenticated', true);
				$vars['abort'] = false;
				return $vars;
			}
			$this->send_new_password($phone);
			$vars['abort'] = true;
		} else
			$vars['abort'] = false;

		return $vars;
	}
}

// Initialize plugin
$lms_smspasswords_plugin = new lms_smspasswords_plugin($LMS);

// Register plugin actions:
$LMS->RegisterHook('module_load_before', array($lms_smspasswords_plugin, 'module_load_before'));
$LMS->RegisterHook('access_table_init', array($lms_smspasswords_plugin, 'access_table_init'));
