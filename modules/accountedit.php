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

function AccountExists($login) 
{
	global $LMS;
	return $LMS->DB->GetOne('SELECT id FROM passwd WHERE login = ?', array($login));
}

$option = $_GET['op'];

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];
$layout['pagetitle'] = 'Zarz±dzanie kontami';

switch ($option) 
{
    case 'chpasswddlg':
	
	$id = $_GET['id'];
	$account = $LMS->DB->GetRow("SELECT passwd.id, passwd.ownerid, passwd.login, passwd.lastlogin, users.name, users.lastname FROM passwd,users WHERE users.id=passwd.ownerid AND passwd.id=$id");
	$SMARTY->assign('account',$account);
	$SMARTY->assign('layout',$layout);
	$SMARTY->display('accountpasswd.html');
	die(0);
	
    case 'chpasswd':
	
	$id = $_GET['id'];
	$passwd1 = $_POST['passwd']['passwd'];
	$passwd2 = $_POST['passwd']['confirm'];
	if ($passwd1 != $passwd2) 
	{
	    $layout['error']="Has³a nie mog± siê ró¿niæ"; 
	    $account=$LMS->DB->GetRow("SELECT passwd.id, passwd.ownerid, passwd.login, passwd.lastlogin, users.name, users.lastname FROM passwd,users WHERE users.id=passwd.ownerid AND passwd.id=$id");
	    $SMARTY->assign('account',$account);
	    $SMARTY->assign('layout',$layout);
	    $SMARTY->display('accountpasswd.html');
	    die(0); 
	}
	if ($passwd1 == '') 
	{
	    $layout['error'] = 'Has³a nie mog± byæ puste';
	    $account=$LMS->DB->GetRow('SELECT passwd.id, passwd.ownerid, passwd.user, passwd.lastlogin, users.login, users.lastname FROM passwd, users WHERE users.id = passwd.ownerid AND passwd.id = '.$id);
	    $SMARTY->assign('account',$account);
	    $SMARTY->assign('layout',$layout);
	    $SMARTY->display('accountpasswd.html');
	    die(0); 
	}
	$LMS->DB->Execute('UPDATE passwd SET password=? WHERE id = ?', array(crypt($passwd1),$id));
	header('Location: ?m=accountlist');
	die(0);
}

header('Location: ?m=accountlist');

?>
