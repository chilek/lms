<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2008 LMS Developers
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

$id = intval($_GET['id']);
$option = isset($_GET['op']) ? $_GET['op'] : '';

$account = $DB->GetRow('SELECT p.id, p.ownerid, p.login, p.realname, 
		p.lastlogin, p.domainid, p.expdate, p.type, p.home, 
		p.quota_sh, p.quota_mail, p.quota_www, p.quota_ftp, p.quota_sql, '
		.$DB->Concat('c.lastname', "' '", 'c.name').' 
		AS customername, d.name AS domain 
		FROM passwd p
		JOIN domains d ON (p.domainid = d.id)
		LEFT JOIN customers c ON (c.id = p.ownerid)
		WHERE p.id = ?', array($id));

if(!$account)
{
	$SESSION->redirect('?'.$SESSION->get('backto'));
}

foreach(array('sh', 'mail', 'www', 'ftp', 'sql') as $type)
	$quota[$type] = $account['quota_'.$type];

switch ($option) 
{
    case 'chpasswd':
	
	$layout['pagetitle'] = trans('Password Change for Account: $0',$account['login'].'@'.$account['domain']);
	
	if(isset($_POST['passwd']))
	{
		$account['passwd1'] = $_POST['passwd']['passwd'];
		$account['passwd2'] = $_POST['passwd']['confirm'];
	
		if($account['passwd1'] != $account['passwd2'])
			$error['passwd'] = trans('Passwords does not match!'); 
		elseif($account['passwd1'] == '') 
			$error['passwd'] = trans('Empty passwords are not allowed!');
	
		if(!$error)
		{
			$DB->Execute('UPDATE passwd SET password = ? WHERE id = ?', 
				array(crypt($account['passwd1']), $id));
		
			$SESSION->redirect('?m=accountlist');
		}
	}
		
	$template = 'accountpasswd.html';
        break;
    
    default:
    
	$layout['pagetitle'] = trans('Account Edit: $0', $account['login'].'@'.$account['domain']);
    
	if(isset($_POST['account']))
	{
		$oldlogin = $account['login'];
		$account = $_POST['account'];
		$quota = $_POST['quota'];
		$account['id'] = $id;
		
		foreach($quota as $type => $value)
			$quota[$type] = sprintf('%d', $value);			
		
		if(!eregi("^[a-z0-9._-]+$", $account['login']))
    			$error['login'] = trans('Login contains forbidden characters!');
		elseif(!$account['domainid'])
            		$error['domainid'] = trans('You have to select domain for account!');
		elseif($account['login'] != $oldlogin)
			if($DB->GetOne('SELECT id FROM passwd WHERE login = ? AND domainid = ?',
				array($account['login'], $account['domainid'])))
			{
				$error['login'] = trans('Account with that login name exists!'); 
			}
		
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

		$account['type'] = array_sum($account['type']);

		if($account['domainid'] && $account['ownerid'])
    			if(!$DB->GetOne('SELECT 1 FROM domains WHERE id=? AND (ownerid=0 OR ownerid=?)', array($account['domainid'], $account['ownerid'])))
	            		$error['domainid'] = trans('Selected domain has other owner!');
						
		if(!$error)
		{
			$DB->Execute('UPDATE passwd SET ownerid = ?, login = ?, realname=?, 
				home = ?, expdate = ?, domainid = ?, type = ?, 
				quota_sh = ?, quota_mail = ?, quota_www = ?, quota_ftp = ?, 
				quota_sql = ? WHERE id = ?', 
				array(	$account['ownerid'], 
					$account['login'],
					$account['realname'],
					$account['home'],
					$account['expdate'],
					$account['domainid'],
					$account['type'],
					$quota['sh'],
					$quota['mail'],
					$quota['www'],
					$quota['ftp'],
					$quota['sql'],
					$account['id']
					));

			$SESSION->redirect('?m=accountlist');
		}
	}
	
	$template = 'accountedit.html';
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('error', $error);
$SMARTY->assign('quota', $quota);
$SMARTY->assign('account', $account);
$SMARTY->assign('customers', $LMS->GetCustomerNames());
$SMARTY->assign('domainlist', $DB->GetAll('SELECT id, name FROM domains ORDER BY name'));
$SMARTY->display($template);

?>
