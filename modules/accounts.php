<?php

/*
 * LMS version 1.3-cvs
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

function AccountExists($login) {
/*
    $service_port = 25;
    $address = gethostbyname('poczta.polarnet.gliwice.pl');

    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket < 0) {
       echo "socket_create() failed: reason: " . socket_strerror($socket) . "\n";
    }
    $result = socket_connect($socket, $address, $service_port);
    if ($result < 0) {
       echo "socket_connect() failed.\nReason: ($result) " . socket_strerror($result) . "\n";
    }

    $out = socket_read($socket, 2048);
    $code = explode(" ", $out);
    $code = $code[0];
    if ($code != 220) { echo "Error:".$out; }
    
    $in = "mail from: user_exists@lms.rulez.pl\r\n";
    socket_write($socket, $in, strlen($in));
    
    $out = socket_read($socket, 2048);
    $code = explode(" ", $out);
    $code = $code[0];
    if ($code != 250) { echo "Error:".$out; }
    
    $in = "rcpt to: ".$login."@polarnet.gliwice.pl\r\n";
    socket_write($socket, $in, strlen($in));

    $out = explode(" ", socket_read($socket, 2048));
    $out = $out[0];
    socket_close($socket);

    if ($out != 250) { 
        return false; 
    } else {
        return true; 
    }
*/
return true;
}

$option = $_GET['op'];

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];
$layout['pagetitle'] = "Zarz±dzanie kontami";

switch ($option) {
    case 'newdlg':
	$users=$LMS->GetUserList();	
	unset ($users["total"]);
	unset ($users["state"]);
	unset ($users["order"]);
	unset ($users["below"]);
	unset ($users["over"]);
	unset ($users["network"]);
	unset ($users["usergroup"]);
	unset ($users["direction"]);
	$SMARTY->assign('users',$users);
	$SMARTY->assign('layout',$layout);
	$SMARTY->display('accountadd.html');
	die(0);
    case 'new':
	$login = $_POST['login'];
	$passwd1 = $_POST['passwd']['passwd'];
	$passwd2 = $_POST['passwd']['confirm'];
	$ownerid = $_POST['ownerid'];
	if(!eregi("^[a-z0-9.-_]+$",$login)) {
    	    $layout['error'] = "Login zawiera niepoprawne znaki!";
	}
	if (AccountExists($login)) {
	    $layout['error']="U¿ytkownik istnieje"; 
	}
	if ($passwd1 != $passwd2) {
	    $layout['error']="Has³a nie mog± siê ró¿niæ"; 
	}
	if ($passwd1 == '') {
	    $layout['error']="Has³a nie mog± byæ puste"; 
	}
	if ($layout['error']) {
	    $users=$LMS->GetUserList();	
	    unset ($users["total"]);
	    unset ($users["state"]);
	    unset ($users["order"]);
	    unset ($users["below"]);
	    unset ($users["over"]);
	    unset ($users["direction"]);
	    unset ($users["usergroup"]);
	    unset ($users["direction"]);
	    $SMARTY->assign('users',$users);
	    $SMARTY->assign('layout',$layout);
	    $SMARTY->display('accountadd.html');
	    die(0);
	}
	$LMS->DB->Execute("INSERT INTO `passwd` ( `OwnerId` , `user` , `password`, `home` ) VALUES ( '".$ownerid."', '".$login."', '".crypt($passwd1)."', '/home/".$login."' )");
	$LMS->DB->Execute("UPDATE `passwd` SET `uid` = id+2000 WHERE `user` = '".$login."'");
	header("Location: ?m=accounts");	
	die(0);	
    case 'delete':
	$id = $_GET['id'];
	$LMS->DB->Execute("DELETE FROM `passwd` WHERE `id` = '".$id."'");
	header("Location: ?m=accounts");	
	die(0);
    case 'chpasswddlg':
	$id = $_GET['id'];
	$account=$LMS->DB->GetRow("SELECT passwd.id, passwd.ownerid, passwd.user, DATE_FORMAT(passwd.lastlogin, '%Y-%m-%d, %H:%i') as lastlogin, users.name, users.lastname FROM passwd,users WHERE users.id=passwd.ownerid AND passwd.id=".$id);
	$SMARTY->assign('account',$account);
	$SMARTY->assign('layout',$layout);
	$SMARTY->display('accountpasswd.html');
	die(0);
    case 'chpasswd':
	$id = $_GET['id'];
	$passwd1 = $_POST['passwd']['passwd'];
	$passwd2 = $_POST['passwd']['confirm'];
	if ($passwd1 != $passwd2) {
	    $layout['error']="Has³a nie mog± siê ró¿niæ"; 
	    $account=$LMS->DB->GetRow("SELECT passwd.id, passwd.ownerid, passwd.user, DATE_FORMAT(passwd.lastlogin, '%Y-%m-%d, %H:%i') as lastlogin, users.name, users.lastname FROM passwd,users WHERE users.id=passwd.ownerid AND passwd.id=".$id);
	    $SMARTY->assign('account',$account);
	    $SMARTY->assign('layout',$layout);
	    $SMARTY->display('accountpasswd.html');
	    die(0); 
	}
	if ($passwd1 == '') {
	    $layout['error']="Has³a nie mog± byæ puste"; 
	    $account=$LMS->DB->GetRow("SELECT passwd.id, passwd.ownerid, passwd.user, DATE_FORMAT(passwd.lastlogin, '%Y-%m-%d, %H:%i') as lastlogin, users.name, users.lastname FROM passwd,users WHERE users.id=passwd.ownerid AND passwd.id=".$id);
	    $SMARTY->assign('account',$account);
	    $SMARTY->assign('layout',$layout);
	    $SMARTY->display('accountpasswd.html');
	    die(0); 
	}
	$LMS->DB->Execute("UPDATE `passwd` SET `password`='".crypt($passwd1)."' WHERE `id` = '".$id."'");
	header("Location: ?m=accounts");	
	die(0);
}


$accountlist=$LMS->DB->GetAll("SELECT passwd.id, passwd.ownerid, passwd.user, DATE_FORMAT(passwd.lastlogin, '%Y-%m-%d, %H:%i') as lastlogin, users.name, users.lastname FROM passwd,users WHERE users.id=passwd.ownerid");
$listdata['total']=sizeof($accountlist);

$SMARTY->assign('accountlist',$accountlist);
$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('layout',$layout);
$SMARTY->display('accountlist.html');

?>
