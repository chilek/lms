<?php

/*
 * LMS version 1.1-cvs
 *
 *  (C) Copyright 2001-2003 LMS Developers
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

if(! $LMS->NetDevExists($_GET[id]))
{
	header('Location: ?m=netdevlist');
	die;
}		

$edit = data;

if($_GET[action]=="replace")
{
	$dev1 = $LMS->GetNetDev($_GET[id]);
	$dev2 = $LMS->GetNetDev($_GET[netdev]);
	if ($dev1[ports]<$dev2[takenports]) {
	    $error[replace] = "Brak wystarczaj±cej liczby portów w urz±dzeniu ¼ród³owym";
	    $edit = FALSE;
	} elseif ($dev2[ports]<$dev1[takenports]) {
	    $error[replace] = "Brak wystarczaj±cej liczby portów w urz±dzeniu docelowym";
	    $edit = FALSE;
	} else {
	    $LMS->NetDevReplace($_GET[id],$_GET[netdev]);
	    header("Location: ?m=netdevinfo&id=".$_GET[id]);
	    die;
	}
}

if($_GET[action]=="disconnect")
{
	$LMS->NetDevUnLink($_GET[id],$_GET[devid]);
	header("Location: ?m=netdevinfo&id=".$_GET[id]);
	die;
}

if($_GET[action]=="disconnectnode")
{
	$LMS->NetDevLinkNode($_GET[nodeid],0);
	header("Location: ?m=netdevinfo&id=".$_GET[id]);
	die;
}

if($_GET[action]=="connect")
{
	if(! $LMS->NetDevLink($_GET[netdev], $_GET[id]) )
	{
		$edit = FALSE;
		$error[link] = "Brak wolnych portów w urz±dzeniu";
	}
	else
		header("Location: ?m=netdevinfo&id=".$_GET[id]);
}
    
if($_GET[action]=="connectnode") 
{
	if(! $LMS->NetDevLinkNode($_GET[nodeid], $_GET[id]) )
	{
		$error[linknode] = "Brak wolnych portów w urz±dzeniu";
		$edit = FALSE;
	}
	else
		header("Location: ?m=netdevinfo&id=".$_GET[id]);
}

// NetDevIp - Narazie tylko podstawa, pu¼niej sprawdzanie b³êdów i reszta

if($_GET[action]=="addip")
{
	$edit = 'addip';
}

if($_GET[action]=="editip")
{
	$nodeipdata=$LMS->GetNode($_GET[netdev]);
	$SMARTY->assign("nodeipdata",$nodeipdata);
	$edit = 'ip';
}

if($_GET[action]=="formaddip")
{
	$netdevipdata = $_POST['ipadd'];
	$netdevipdata['ownerid']=0;
	$LMS->NetDevLinkNode($LMS->NodeAdd($netdevipdata),$_GET[id]);
	header("Location: ?m=netdevinfo&id=".$_GET[id]);
	die;
}

if($_GET[action]=="formeditip")
{
	$netdevipdata = $_POST['ipadd'];
	$netdevipdata['ownerid']=0;
	$netdevipdata['netdev']=$_GET[id];
	$LMS->NodeUpdate($netdevipdata);
	header("Location: ?m=netdevinfo&id=".$_GET[id]);
	die;
}

$netdevdata = $_POST[netdev];
if(isset($netdevdata))
{
	$netdevdata[id] = $_GET[id];

	if($netdevdata[name] == "")
		$error[name] = "Pole nazwa nie mo¿e byæ puste!";

	if($netdevdata[ports] < $LMS->CountNetDevLinks($_GET[id]))
		$error[ports] = "Liczba pod³±czonych urz±dzeñ przekracza liczbê portów!";
	
	if(!$error)
	{
		$LMS->NetDevUpdate($netdevdata);
		header("Location: ?m=netdevinfo&id=".$_GET[id]);
		die;
	}

}
else
	$netdevdata = $LMS->GetNetDev($_GET[id]);

$netdevdata[id] = $_GET[id];

$netdevconnected = $LMS->GetNetDevConnectedNames($_GET[id]);
$netcomplist = $LMS->GetNetdevLinkedNodes($_GET[id]);
$netdevlist = $LMS->GetNotConnectedDevices($_GET[id]);

unset($netdevlist[total]);
unset($netdevlist[order]);
unset($netdevlist[direction]);

$nodelist = $LMS->GetUnlinkedNodes();

unset($nodelist[totaloff]);
unset($nodelist[totalon]);
unset($nodelist[total]);
unset($nodelist[order]);
unset($nodelist[direction]);

$replacelist = $LMS->GetNetDevList();

unset($replacelist[order]);
unset($replacelist[total]);
unset($replacelist[direction]);

$layout[pagetitle]="Edycja urz±dzenia: ".$netdevdata[name]." ".$netdevdata[producer];

$SMARTY->assign("layout",$layout);
$SMARTY->assign("error",$error);
$SMARTY->assign("netdevinfo",$netdevdata);
$SMARTY->assign("netdevlist",$netdevconnected);
$SMARTY->assign("netcomplist",$netcomplist);
$SMARTY->assign("nodelist",$nodelist);
$SMARTY->assign("restnetdevlist",$netdevlist);
$SMARTY->assign("replacelist",$replacelist);

if($edit == 'data')
	$SMARTY->display('netdevedit.html');
else if($edit == 'ip')
	$SMARTY->display('netdeveditip.html');
else if($edit == 'addip')
	$SMARTY->display('netdevaddip.html');
else
	$SMARTY->display('netdevinfo.html');

?>
