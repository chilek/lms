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

if(! $LMS->NetDevExists($_GET['id']))
{
	header('Location: ?m=netdevlist');
	die;
}		

$edit = data;

switch($_GET['action'])
{
case "replace":
	$dev1 = $LMS->GetNetDev($_GET['id']);
	$dev2 = $LMS->GetNetDev($_GET['netdev']);
	if ($dev1['ports']<$dev2['takenports']) {
	    $error['replace'] = "Brak wystarczaj�cej liczby port�w w urz�dzeniu �r�d�owym";
	    $edit = FALSE;
	} elseif ($dev2['ports']<$dev1['takenports']) {
	    $error['replace'] = "Brak wystarczaj�cej liczby port�w w urz�dzeniu docelowym";
	    $edit = FALSE;
	} else {
	    $LMS->NetDevReplace($_GET['id'],$_GET['netdev']);
	    header("Location: ?m=netdevinfo&id=".$_GET['id']);
	    die;
	}
	break;
	
case "disconnect":
	$LMS->NetDevUnLink($_GET['id'],$_GET['devid']);
	header("Location: ?m=netdevinfo&id=".$_GET['id']);
	die;

case "disconnectnode":
	$LMS->NetDevLinkNode($_GET['nodeid'],0);
	header("Location: ?m=netdevinfo&id=".$_GET['id']);
	die;

case "connect":
	if(! $LMS->NetDevLink($_GET['netdev'], $_GET['id']) )
	{
		$edit = FALSE;
		$error['link'] = "Brak wolnych port�w w urz�dzeniu";
	} else
		header("Location: ?m=netdevinfo&id=".$_GET['id']);
	break;
    
case "connectnode":
	if(! $LMS->NetDevLinkNode($_GET['nodeid'], $_GET['id']) )
	{
		$error['linknode'] = "Brak wolnych port�w w urz�dzeniu";
		$edit = FALSE;
	} else
		header("Location: ?m=netdevinfo&id=".$_GET['id']);
	break;

case "addip":
	$edit = 'addip';
	break;

case "editip":
	$nodeipdata=$LMS->GetNode($_GET['ip']);
	$nodeipdata['ipaddr'] = $nodeipdata['ip'];
	$SMARTY->assign('nodeipdata',$nodeipdata);
	$edit = 'ip';
	break;

case "formaddip":
	$nodeipdata = $_POST['ipadd'];
	$nodeipdata['ownerid']=0;
	
	$nodeipdata['mac'] = str_replace("-",":",$nodeipdata['mac']);
	foreach($nodeipdata as $key => $value)
		$nodeipdata[$key] = trim($value);
	
	if($nodeipdata['ipaddr']=="" && $nodeipdata['mac']=="" && $nodeipdata['name']=="")
	{
		header("Location: ?m=netdevedit&action=addip&id=".$_GET['id']);
		die;
        }
	
	if($nodeipdata['name']=="")
		$error['ipname'] = "Prosz� poda� nazw� dla adresu!";
	elseif(strlen($nodeipdata['name']) > 16)
		$error['ipname'] = "Podana nazwa jest za d�uga!";
	elseif($LMS->GetNodeIDByName($nodeipdata['name']))
		$error['ipname'] = "Podana nazwa jest u�ywana!";
	elseif(!eregi("^[_a-z0-9-]+$",$nodeipdata['name']))
		$error['ipname'] = "Podana nazwa zawiera niepoprawne znaki!";		

	if(!$nodeipdata['ipaddr'])
		$error['ipaddr'] = "Prosz� podac adres IP!";
	elseif(!check_ip($nodeipdata['ipaddr']))
		$error['ipaddr'] = "Podany adres IP jest niepoprawny!";
	elseif(!$LMS->IsIPValid($nodeipdata['ipaddr']))
		$error['ipaddr'] = "Podany adres IP nie nale�y do �adnej sieci!";
	elseif(!$LMS->IsIPFree($nodeipdata['ipaddr']))
		$error['ipaddr'] = "Podany adres IP jest zaj�ty!";

	if(!$nodeipdata['mac'])
		$error['mac'] = "Prosz� podac adres MAC!";
	elseif($LMS->GetNodeIDByMAC($nodeipdata['mac']) && $LMS->CONFIG['phpui']['allow_mac_sharing'] == FALSE)
		$error['mac'] = "Podany MAC jest ju� w bazie!";
	elseif(!check_mac($nodeipdata['mac']))
		$error['mac'] = "Podany adres MAC jest nieprawid�owy!";

	if(!$error)
	{
		$LMS->NetDevLinkNode($LMS->NodeAdd($nodeipdata),$_GET['id']);
		header("Location: ?m=netdevinfo&id=".$_GET['id']);
		die;
	}
	$SMARTY->assign('nodeipdata',$nodeipdata); 
	$edit='addip';
	break;
		
case "formeditip":
	$nodeipdata = $_POST['ipadd'];
	$nodeipdata['ownerid']=0;
	$nodeipdata['netdev']=$_GET['id'];

	$nodeipdata['mac'] = str_replace("-",":",$nodeipdata['mac']);
	foreach($nodeipdata as $key => $value)
		$nodeipdata[$key] = trim($value);
	
	if($nodeipdata['ipaddr']=="" && $nodeipdata['mac']=="" && $nodeipdata['name']=="")
	{
		header("Location: ?m=netdevedit&action=editip&id=".$_GET['id']."&ip=".$_GET['ip']);
		die;
        }
	
	if($nodeipdata['name']=="")
		$error['ipname'] = "Prosz� poda� nazw� dla adresu!";
	elseif(strlen($nodeipdata['name']) > 16)
		$error['ipname'] = "Podana nazwa jest za d�uga!";
	elseif(
		$LMS->GetNodeIDByName($nodeipdata['name']) &&
		$LMS->GetNodeName($_GET['ip'])!=$nodeipdata['name']
		)
		$error['ipname'] = "Podana nazwa jest u�ywana!";
	elseif(!eregi("^[_a-z0-9-]+$",$nodeipdata['name']))
		$error['ipname'] = "Podana nazwa zawiera niepoprawne znaki!";		

	if(!$nodeipdata['ipaddr'])
		$error['ipaddr'] = "Prosz� podac adres IP!";
	elseif(!check_ip($nodeipdata['ipaddr']))
		$error['ipaddr'] = "Podany adres IP jest niepoprawny!";
	elseif(!$LMS->IsIPValid($nodeipdata['ipaddr']))
		$error['ipaddr'] = "Podany adres IP nie nale�y do �adnej sieci!";
	elseif(
		!$LMS->IsIPFree($nodeipdata['ipaddr']) &&
		$LMS->GetNodeIPByID($_GET['ip'])!=$nodeipdata['ipaddr']
		)
		$error['ipaddr'] = "Podany adres IP jest zaj�ty!";
	
	if(!$nodeipdata['mac'])
		$error['mac'] = "Prosz� podac adres MAC!";
	elseif(
		$LMS->GetNodeIDByMAC($nodeipdata['mac']) && 
		$LMS->GetNodeMACByID($_GET['ip'])!=$nodeipdata['mac'] &&
		$LMS->CONFIG['phpui']['allow_mac_sharing'] == FALSE
		)
		$error['mac'] = "Podany MAC jest ju� w bazie!";
	elseif(!check_mac($nodeipdata['mac']))
		$error['mac'] = "Podany adres MAC jest nieprawid�owy!";

	if(!$error)
	{
		$LMS->NodeUpdate($nodeipdata);	
		header("Location: ?m=netdevinfo&id=".$_GET['id']);
		die;
	}
	$SMARTY->assign('nodeipdata',$nodeipdata); 
	$edit='ip';
	break;
}

$netdevdata = $_POST['netdev'];
if(isset($netdevdata))
{
	$netdevdata['id'] = $_GET['id'];

	if($netdevdata['name'] == "")
		$error['name'] = "Pole nazwa nie mo�e by� puste!";

	if($netdevdata['ports'] < $LMS->CountNetDevLinks($_GET['id']))
		$error['ports'] = "Liczba pod��czonych urz�dze� przekracza liczb� port�w!";
	
	if(!$error)
	{
		$LMS->NetDevUpdate($netdevdata);
		header("Location: ?m=netdevinfo&id=".$_GET['id']);
		die;
	}

}
else
	$netdevdata = $LMS->GetNetDev($_GET['id']);

$netdevdata['id'] = $_GET['id'];

$netdevconnected = $LMS->GetNetDevConnectedNames($_GET['id']);
$netcomplist = $LMS->GetNetdevLinkedNodes($_GET['id']);
$netdevlist = $LMS->GetNotConnectedDevices($_GET['id']);

unset($netdevlist['total']);
unset($netdevlist['order']);
unset($netdevlist['direction']);

$nodelist = $LMS->GetUnlinkedNodes();
unset($nodelist['totaloff']);
unset($nodelist['totalon']);
unset($nodelist['total']);
unset($nodelist['order']);
unset($nodelist['direction']);

$replacelist = $LMS->GetNetDevList();
$replacelisttotal = $replacelist['total'];
unset($replacelist['order']);
unset($replacelist['total']);
unset($replacelist['direction']);

$netdevips = $LMS->GetNetDevIPs($_GET['id']);

$layout['pagetitle'] = "Edycja urz�dzenia: ".$netdevdata['name']." ".$netdevdata['producer'];

$SMARTY->assign('error',$error);
$SMARTY->assign('netdevinfo',$netdevdata);
$SMARTY->assign('netdevlist',$netdevconnected);
$SMARTY->assign('netcomplist',$netcomplist);
$SMARTY->assign('nodelist',$nodelist);
$SMARTY->assign('netdevips',$netdevips);
$SMARTY->assign('restnetdevlist',$netdevlist);
$SMARTY->assign('replacelist',$replacelist);
$SMARTY->assign('replacelisttotal',$replacelisttotal);

switch($edit)
{
    case 'data':
	$SMARTY->display('netdevedit.html');
    break;
    case 'ip':
	$SMARTY->display('netdevipedit.html');
    break;
    case 'addip':
	$SMARTY->display('netdevipadd.html');
    break;
    default:
	$SMARTY->display('netdevinfo.html');
    break;
}
?>

