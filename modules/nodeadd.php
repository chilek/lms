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


$nodedata = $_POST['nodedata'];

if(isset($nodedata))
{
	$nodedata['ipaddr'] = $_POST['nodedataipaddr'];
	$nodedata['mac'] = $_POST['nodedatamac'];
	$nodedata['mac'] = str_replace("-",":",$nodedata['mac']);

	foreach($nodedata as $key => $value)
		$nodedata[$key] = trim($value);

	if($nodedata['ipaddr']=="" && $nodedata['mac']=="" && $nodedata['name']=="")
		if($_GET['ownerid'])
		{
			header("Location: ?m=userinfo&id=".$_GET['ownerid']);
			die;
		}else{
			header("Location: ?m=nodelist");
			die;
		}
	
	if($nodedata['name']=="")
		$error['name'] = "Prosz� poda� nazw� komputera!";
	elseif(strlen($nodedata['name']) > 16)
		$error['name'] = "Podana nazwa jest za d�uga!";
	elseif($LMS->GetNodeIDByName($nodedata['name']))
		$error['name'] = "Podana nazwa jest u�ywana!";
	elseif(!eregi("^[_a-z0-9-]+$",$nodedata['name']))
		$error['name'] = "Podana nazwa zawiera niepoprawne znaki!";		

	if(!$nodedata['ipaddr'])
		$error['ipaddr'] = "Prosz� podac adres IP!";
	elseif(!check_ip($nodedata['ipaddr']))
		$error['ipaddr'] = "Podany adres IP jest niepoprawny!";
	elseif(!$LMS->IsIPValid($nodedata['ipaddr']))
		$error['ipaddr'] = "Podany adres IP nie nale�y do �adnej sieci!";
	elseif(!$LMS->IsIPFree($nodedata['ipaddr']))
		$error['ipaddr'] = "Podany adres IP jest zaj�ty!";

	if(!$nodedata['mac'])
		$error['mac'] = "Prosz� podac adres MAC!";
	elseif($LMS->GetNodeIDByMAC($nodedata['mac']) && $LMS->CONFIG['phpui']['allow_mac_sharing'] == FALSE)
		$error['mac'] = "Podany MAC jest ju� w bazie!";
	elseif(!check_mac($nodedata['mac']))
		$error['mac'] = "Podany adres MAC jest nieprawid�owy!";

	if(! $LMS->UserExists($nodedata['ownerid']))
		$error['user'] = "Prosz� wybra� u�ytkownika!";

	if($LMS->GetUserStatus($nodedata['ownerid']) != 3 || $LMS->UserExists($nodedata['ownerid']) != TRUE)
		$error['user'] = "Wybrany u�ytkownik $nodedata['ownerid'] jest b��dny!";

	if(!$error)
	{
		$nodeid=$LMS->NodeAdd($nodedata);
		if($nodedata['reuse']=='')
		{
			header("Location: ?m=nodeinfo&id=".$nodeid);
			die;
		}
		unset($nodedata);
		$nodedata['reuse'] = '1';
	}

}

if($LMS->UserExists($_GET['ownerid']) < 0)
{
	header('Location: ?m=userinfo&id='.$_GET['ownerid']);
	die;
}

$nodedata['access'] = 1;

if($_GET['ownerid'] && $LMS->UserExists($_GET['ownerid']) > 0)
{
	$nodedata['ownerid'] = $_GET['ownerid'];
	$userinfo = $LMS->GetUser($_GET['ownerid']);
}

if(isset($_GET['preip'])&&$nodedata['ipaddr']=="")
	$nodedata['ipaddr'] = $_GET['preip'];

if(isset($_GET['premac'])&&$nodedata['mac']=="")
	$nodedata['mac'] = $_GET['premac'];

if(isset($_GET['prename'])&&$nodedata['name']=="")
	$nodedata['name'] = $_GET['prename'];
		

$layout['pagetitle'] = "Nowy komputer";

$tariffs = $LMS->GetTariffs();
$balancelist = $LMS->GetUserBalanceList($nodedata['ownerid']);
$assignments = $LMS->GetUserAssignments($nodedata['ownerid']);
$users = $LMS->GetUserNames();

$SMARTY->assign('balancelist',$balancelist);
$SMARTY->assign('assignments',$assignments);
$SMARTY->assign('tariffs',$tariffs);
$SMARTY->assign('users',$users);
$SMARTY->assign('error',$error);
$SMARTY->assign('userinfo',$userinfo);
$SMARTY->assign('nodedata',$nodedata);
$SMARTY->assign('layout',$layout);

$SMARTY->display('nodeadd.html');

?>

