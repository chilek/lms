<?php

/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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
 */

function GetAccountIdByLogin($login) 
{
	global $LMS;
	return $LMS->DB->GetOne('SELECT id FROM passwd WHERE login = ?', array($login));
}

$layout['pagetitle'] = 'Utworzenie nowego konta';

if($account = $_POST['account'])
{
	if(!($account['login'] || $account['passwd1'] || $account['passwd2']))
	{
		header('Location: ?m=accountlist');
		die;
	}
	
	if(!eregi("^[a-z0-9._-]+$", $account['login']))
    	    $error['login'] = 'Login zawiera niepoprawne znaki!';
	    
	if(GetAccountIdByLogin($account['login']))
	    $error['login'] = 'Konto o podanej nazwie ju¿ istnieje!'; 
	
	if($account['passwd1'] != $account['passwd2'])
	    $error['passwd'] = 'Has³a nie mog± siê ró¿niæ!';
	    
	if($account['passwd1'] == '')
	    $error['passwd'] = 'Has³a nie mog± byæ puste';
	
	if($account['expdate'] == '')
		$account['expdate'] = 0;
	else
	{
	    $date = explode('/',$account['expdate']);
	    if(!checkdate($date[1],$date[2],$date[0]))
		$error['expdate'] = 'Zastosuj prawid³owy format daty - RRRR/MM/DD!';
	    elseif(!$error)
		$account['expdate'] = mktime(0,0,0,$date[1],$date[2],$date[0]);
	}

	$account['type'] = array_sum($account['type']);	
	
	if(!$error)
	{
		$LMS->DB->Execute('INSERT INTO passwd (ownerid, login, password, home, expdate, domainid, type) VALUES (?, ?, ?, ?, ?, ?, ?)', 
				array(	$account['ownerid'], 
					$account['login'], 
					crypt($account['passwd1']), 
					'/home/'.$account['login'],
					$account['expdate'],
					$account['domainid'],
					$account['type']
					));
		$LMS->DB->Execute('UPDATE passwd SET uid = id+2000 WHERE login = ?',array($account['login']));
		$LMS->SetTS('passwd');
		if(!$account['reuse'])
		{
			header('Location: ?m=accountlist');
			die;
		}
		
		unset($account['login']);
		unset($account['passwd1']);
		unset($account['passwd2']);
	}
}

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

if(!$account['type']) $account['type'] = 32767;

$SMARTY->assign('error', $error);
$SMARTY->assign('users', $LMS->GetUserNames());
$SMARTY->assign('domainlist', $LMS->DB->GetAll('SELECT id, name FROM domains ORDER BY name'));
$SMARTY->assign('account', $account);
$SMARTY->assign('layout', $layout);
$SMARTY->display('accountadd.html');

?>
