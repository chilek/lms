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

function AccountExists($id) 
{
	global $LMS;
	return ($LMS->DB->GetOne('SELECT id FROM passwd WHERE id = ?', array($id)) ? TRUE : FALSE);
}

$id = $_GET['id'];
$option = $_GET['op'];

if($id && !AccountExists($id))
{
	header('Location: ?'.$_SESSION['backto']);
	die;
}

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$account = $LMS->DB->GetRow('SELECT passwd.id AS id, ownerid, login, lastlogin, domainid, expdate, type, '.$LMS->DB->Concat('users.lastname', "' '", 'users.name').' AS username FROM passwd LEFT JOIN users ON users.id = ownerid WHERE passwd.id = ?', array($id));

switch ($option) 
{
    case 'chpasswddlg':
	
	$layout['pagetitle'] = trans('Password Change for Account: $0',$account['login']);
	$template = 'accountpasswd.html';
	break;	

    case 'chpasswd':
	
	$account['passwd1'] = $_POST['passwd']['passwd'];
	$account['passwd2'] = $_POST['passwd']['confirm'];
	
	if($account['passwd1'] != $account['passwd2'])
	    $error['passwd'] = trans('Passwords do not match!'); 
	
	if($account['passwd1'] == '') 
	    $error['passwd'] = trans('Empty passwords are not allowed!');
	
	if(!$error)
	{
		$LMS->DB->Execute('UPDATE passwd SET password = ? WHERE id = ?', array(crypt($account['passwd1']), $id));
		$LMS->SetTS('passwd');
		header('Location: ?m=accountlist');
		die;
	}
	
	$layout['pagetitle'] = trans('Password Change for Account: $0',$account['login']);
	$template = 'accountpasswd.html';
        break;
    
    default:
    
	$layout['pagetitle'] = trans('Account Edit: $0', $account['login']);
    
	if($_POST['account'])
	{
		$oldlogin = $account['login'];
		$account = $_POST['account'];
		$account['id'] = $id;
		
		if(!eregi("^[a-z0-9._-]+$", $account['login']))
    			$error['login'] = trans('Login contains forbidden characters!');
	    
		if($account['login'] != $oldlogin)
			if($LMS->GetAccountIdByLogin($account['login']))
				$error['login'] = trans('Account with specified login exists!'); 
	
		if($account['expdate'] == '')
			$account['expdate'] = 0;
		else
		{
			$date = explode('/',$account['expdate']);
			if(!checkdate($date[1],$date[2],$date[0]))
				$error['expdate'] = trans('Incorrect date format! Enter date in format YYYY/MM/DD!');
			elseif(!$error)
				$account['expdate'] = mktime(0,0,0,$date[1],$date[2],$date[0]);
		}

		$account['type'] = array_sum($account['type']);

		if(!$account['domainid'] && (($account['type'] & 2) == 2))
			$error['domainid'] = trans('E-mail account must have domain!');
			
		if(!$error)
		{
			$LMS->DB->Execute('UPDATE passwd SET ownerid = ?, login = ?, home = ?, expdate = ?, domainid = ?, type = ? WHERE id = ?', 
				array(	$account['ownerid'], 
					$account['login'], 
					'/home/'.$account['login'],
					$account['expdate'],
					$account['domainid'],
					$account['type'],
					$account['id']
					));
			$LMS->SetTS('passwd');
			header('Location: ?m=accountlist');
			die;
		}
	}
	
	$template = 'accountedit.html';
}

$SMARTY->assign('error', $error);
$SMARTY->assign('account', $account);
$SMARTY->assign('layout', $layout);
$SMARTY->assign('users', $LMS->GetUserNames());
$SMARTY->assign('domainlist', $LMS->DB->GetAll('SELECT id, name FROM domains ORDER BY name'));
$SMARTY->display($template);

?>
