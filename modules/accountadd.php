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

/*
 * types of account:
 *    shell = 1 (0000000000000001)
 *    mail = 2, (0000000000000010)
 *    www = 4,  (0000000000000100)
 *    ftp = 8	(0000000000001000)
 *    sql = 16	(0000000000010000)
 */

$types = array(1 => 'sh', 2 => 'mail', 4 => 'www', 8 => 'ftp', 16 => 'sql');

if(isset($_POST['account']))
{
	$account = $_POST['account'];
	$quota = $_POST['quota'];
	
	foreach($account as $key=>$value)
		if(!is_array($value))
            		$account[$key] = trim($value);

	if(!($account['login'] || $account['domainid'] || $account['passwd1'] || $account['passwd2']))
	{
		$SESSION->redirect('?m=accountlist');
	}
	
	if(isset($account['type']))
		$account['type'] = array_sum($account['type']);
	else
		$error['type'] = true;
	
	if($account['login'] == '')
                $error['login'] = trans('You have to specify login!');
	elseif(!preg_match('/^[a-z0-9._-]+$/', $account['login']))
    		$error['login'] = trans('Login contains forbidden characters!');
	elseif(!$account['domainid'])
                $error['domainid'] = trans('You have to select domain for account!');
	elseif($DB->GetOne('SELECT id FROM passwd WHERE login = ? AND domainid = ?', 
		array($account['login'], $account['domainid'])))
	{
		$error['login'] = trans('Account with that login name exists!');
	}
	// if account is of type mail, check if we've got an alias with the same login@domain
	elseif ($account['domainid'] && ($account['type'] & 2))
		if ($DB->GetOne('SELECT 1 FROM aliases WHERE login=? AND domainid=?', array($account['login'], $account['domainid'])))
			$error['login'] = trans('Alias with that login name already exists in that domain!');

	if($account['mail_forward'] != '' && !check_email($account['mail_forward']))
	        $error['mail_forward'] = trans('Incorrect email!');

	if($account['mail_bcc'] != '' && !check_email($account['mail_bcc']))
	        $error['mail_bcc'] = trans('Incorrect email!');
			
	if($account['passwd1'] != $account['passwd2'])
		$error['passwd'] = trans('Passwords does not match!');
	    
	if($account['passwd1'] == '')
		$error['passwd'] = trans('Empty passwords are not allowed!');
	
	if($account['expdate'] == '')
		$account['expdate'] = 0;
	else
	{
		$date = explode('/',$account['expdate']);
		if(!checkdate($date[1],$date[2],$date[0]))
			$error['expdate'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
		elseif(!$error)
			$account['expdate'] = mktime(0,0,0,$date[1],$date[2],$date[0]);
	}

	if($account['domainid'] && $account['ownerid'])
		if(!$DB->GetOne('SELECT 1 FROM domains WHERE id=? AND (ownerid=0 OR ownerid=?)', array($account['domainid'], $account['ownerid'])))
			$error['domainid'] = trans('Selected domain has other owner!');

	foreach($types as $idx => $name)
		if(!preg_match('/^[0-9]+$/', $quota[$name]))
	                $error['quota_'.$name] = trans('Integer value expected!');


	// finally lets check limits
	if($account['ownerid'])
        {
                $limits = $LMS->GetHostingLimits($account['ownerid']);
		
		foreach($types as $idx => $name)
		{
			// quota limit
			$limitidx = 'quota_'.$name.'_limit';
			if(!isset($error['quota_'.$name]) && $limits[$limitidx] !== NULL && ($account['type'] & $idx) == $idx)
			{
				if($quota[$name] > $limits[$limitidx])
				{
					$error['quota_'.$name] = trans('Exceeded \'$a\' account quota limit of selected customer ($b)!',
						$name, $limits[$limitidx]);
				}
			}
			
			// count limit
			$limitidx = $name.'_limit';
			if($limits[$limitidx] !== NULL && ($account['type'] & $idx) == $idx)
			{
	    			if($limits[$limitidx] > 0)
		            		$cnt = $DB->GetOne('SELECT COUNT(*) FROM passwd WHERE ownerid = ?
						AND (type & ?) = ?', array($account['ownerid'], $idx, $idx));

			        if(!$error && ($limits[$limitidx] == 0 || $limits[$limitidx] <= $cnt))
				{
    		                	$error['ownerid'] = trans('Exceeded \'$a\' accounts limit of selected customer ($b)!', 
							$name, $limits[$limitidx]);
				}
			}
		}
	}

	if(!$error)
	{
		$DB->BeginTrans();
		
		$DB->Execute('INSERT INTO passwd (ownerid, login, password, home, expdate, domainid, 
				type, realname, quota_sh, quota_mail, quota_www, quota_ftp, quota_sql,
				mail_forward, mail_bcc, description) 
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
				array(	$account['ownerid'],
					$account['login'],
					crypt($account['passwd1']),
					$account['home'],
					$account['expdate'],
					$account['domainid'],
					$account['type'],
					$account['realname'],
					$quota['sh'],
					$quota['mail'],
					$quota['www'],
					$quota['ftp'],
					$quota['sql'],
					$account['mail_forward'],
					$account['mail_bcc'],
					$account['description'],
					));

		$id = $DB->GetLastInsertId('passwd');

		$DB->Execute('UPDATE passwd SET uid = id + 2000 WHERE id = ?', array($id));
		
		$DB->CommitTrans();
		
		if(!isset($account['reuse']))
		{
			$SESSION->redirect('?m=accountinfo&id='.$id);
		}
		
		unset($account['login']);
		unset($account['home']);
		unset($account['realname']);
		unset($account['passwd1']);
		unset($account['passwd2']);
		unset($account['mail_forward']);
		unset($account['description']);
	}
	
	$SMARTY->assign('error', $error);
}
else
{
	$quota = array();

	if(!empty($_GET['did']))
	{
		$account['domainid'] = intval($_GET['did']);
	}
	
	if(!empty($_GET['cid']))
	{
		$account['ownerid'] = intval($_GET['cid']);
		$limits = $LMS->GetHostingLimits($account['ownerid']);

		foreach($types as $idx => $name)
			$quota[$name] = intval($limits['quota_'.$name.'_limit']);
	}
	else
	{
		foreach($types as $idx => $name)
			if(isset($CONFIG['phpui']['quota_'.$name]))
				$quota[$name] = intval($CONFIG['phpui']['quota_'.$name]);
			else
				$quota[$name] = 0;
	}

	if(!empty($CONFIG['phpui']['account_type']))
		$account['type'] = intval($CONFIG['phpui']['account_type']);
}

$layout['pagetitle'] = trans('New Account');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if(!isset($account['type'])) $account['type'] = 32767;

$SMARTY->assign('quota', $quota);
$SMARTY->assign('account', $account);
$SMARTY->assign('customers', $LMS->GetCustomerNames());
$SMARTY->assign('domainlist', $DB->GetAll('SELECT id, name FROM domains ORDER BY name'));

$SMARTY->display('accountadd.html');

?>
