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
	
	if(!eregi("^[a-z0-9.-_]+$", $account['login']))
    	    $error['login'] = 'Login zawiera niepoprawne znaki!';
	    
	if(GetAccountIdByLogin($account['login']))
	    $error['login'] = 'Konto o podanej nazwie ju¿ istnieje!'; 
	    
	if($account['passwd1'] != $account['passwd2'])
	    $error['passwd'] = 'Has³a nie mog± siê ró¿niæ!';
	    
	if($account['passwd1'] == '')
	    $error['passwd'] = 'Has³a nie mog± byæ puste';

	if(!$error)
	{
		$LMS->DB->Execute('INSERT INTO passwd (ownerid, login, password, home) VALUES (?, ?, ?, ?)', 
				array(	$account['ownerid'], 
					$account['login'], 
					crypt($account['passwd1']), 
					'/home/'.$account['login']
					));
		$LMS->DB->Execute('UPDATE passwd SET uid = id+2000 WHERE login = ?',array($account['login']));
		header('Location: ?m=accountlist');
	}
}

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$SMARTY->assign('error', $error);
$SMARTY->assign('users', $LMS->GetUserNames());
$SMARTY->assign('account', $account);
$SMARTY->assign('layout', $layout);
$SMARTY->display('accountadd.html');

?>
