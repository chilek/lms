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

$account = $LMS->DB->GetRow('SELECT passwd.id AS id, ownerid, login, lastlogin, '.$LMS->DB->Concat('users.lastname', "' '", 'users.name').' AS username FROM passwd, users WHERE users.id = ownerid AND passwd.id = ?', array($id));

switch ($option) 
{
    case 'chpasswddlg':
	
	$layout['pagetitle'] = 'Zmiana has³a dla konta: '.$account['login'];
	$template = 'accountpasswd.html';
	break;	

    case 'chpasswd':
	
	$account['passwd1'] = $_POST['passwd']['passwd'];
	$account['passwd2'] = $_POST['passwd']['confirm'];
	
	if($account['passwd1'] != $account['passwd2'])
	    $error['passwd'] = 'Has³a nie mog± siê ró¿niæ!'; 
	
	if($account['passwd1'] == '') 
	    $error['passwd'] = 'Has³a nie mog± byæ puste!';
	
	if(!$error)
	{
		$LMS->DB->Execute('UPDATE passwd SET password = ? WHERE id = ?', array(crypt($account['passwd1']), $id));
		header('Location: ?m=accountlist');
		die;
	}
	
	$layout['pagetitle'] = 'Zmiana has³a dla konta: '.$account['login'];
	$template = 'accountpasswd.html';
        break;
    
    default:
    
	header('Location: ?m=accountlist');
	die;
}


$SMARTY->assign('error', $error);
$SMARTY->assign('account', $account);
$SMARTY->assign('layout', $layout);
$SMARTY->display($template);

?>
