<?

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

if(!$LMS->NodeExists($_GET[id]))
	if(isset($_GET[ownerid]))
		header("Location: ?m=userinfo&id=".$_GET[ownerid]);
	else
		header("Location: ?m=nodelist");

$nodeid = $_GET[id];
$ownerid = $LMS->GetNodeOwner($nodeid);
$_SESSION[backto] = $_SERVER[QUERY_STRING];
	
if(!isset($_GET[ownerid]))
	$_SESSION[backto] .= "&ownerid=".$ownerid;
							
$owner = $ownerid;
$userinfo=$LMS->GetUser($owner);
$layout[pagetitle]="Informacje o u¿ytkowniku ".$userinfo[username]." - edycja komputera ".$LMS->GetNodeName($_GET[id]);

$nodeedit = $_POST[nodeedit];
$usernodes = $LMS->GetUserNodes($owner);
$nodeinfo = $LMS->GetNode($_GET[id]);
if(isset($nodeedit))
{
	$nodeedit[ipaddr] = $_POST[nodeeditipaddr];
	$nodeedit[mac] = $_POST[nodeeditmac];

	foreach($nodeedit as $key => $value)
		$nodeedit[$key] = trim($value);
	
	if($nodeedit[ipaddr]==""&&$nodeedit[mac]==""&&$nodeedit[name]=="")
	{
		header("Location: ?m=nodeinfo&id=".$nodeedit[id]);
		exit(0);
	}

	if(check_ip($nodeedit[ipaddr]))
	{
		if($LMS->IsIPValid($nodeedit[ipaddr]))
		{
			if(!$LMS->IsIPFree($nodeedit[ipaddr])&&$LMS->GetNodeIPByID($nodeedit[id])!=$nodeedit[ipaddr])
			{
				$error[ipaddr] = "Podany adres IP jest zajêty!";
			}
		}
		else
		{
			$error[ipaddr] = "Podany adres IP nie nale¿y do ¿adnej sieci!";
		}
	}
	else
	{
		$error[ipaddr] = "Podany adres IP jest niepoprawny!";
	}

	if(check_mac($nodeedit[mac]))
	{
		if(
				$LMS->GetNodeIDByMAC($nodeedit[mac]) &&
				$LMS->GetNodeMACByID($nodeedit[id])!=$nodeedit[mac] &&
				$_CONFIG[phpui][allow_mac_sharing] == FALSE
		)
		{
			$error[mac] = "Podany adres MAC jest ju¿ przypisany do innego komputera!";
		}
	}
	else
	{
		$error[mac] = "Podany adres MAC jest b³êdny!";
	}

	if($nodeedit[name]=="")
		$error[name] = "Podaj nazwê!";
	elseif($LMS->GetNodeIDByName($nodeedit[name]) && $LMS->GetNodeIDByNAME($nodeedit[name]) != $nodeedit[id])
		$error[name] = "Ta nazwa jest zajêta!";
	elseif(!eregi("^[_a-z0-9-]+$",$nodeedit[name]))
		$error[name] = "Podana nazwa zawiera niepoprawne znaki!";

	if($nodeedit[access]!="Y")
		$nodeedit[access] = "N";
	
	$nodeinfo[name] = $nodeedit[name];
	$nodeinfo[mac] = $nodeedit[mac];
	$nodeinfo[ipaddr] = $nodeedit[ipaddr];
	$nodeinfo[access] = $nodeedit[access];
	$nodeinfo[ownerid] = $nodeedit[ownerid];

	if(!$error)
	{
		$LMS->NodeUpdate($nodeedit);
		header("Location: ?m=nodeinfo&id=".$nodeedit[id]);
	}
}

if($userinfo[status]==3) $userinfo[shownodes] = TRUE;
$users = $LMS->GetUserNames();


$SMARTY->assign("balancelist",$LMS->GetUserBalanceList($owner));
$SMARTY->assign("error",$error);
$SMARTY->assign("userinfo",$userinfo);
$SMARTY->assign("layout",$layout);
$SMARTY->assign("nodeinfo",$nodeinfo);
$SMARTY->assign("users",$users);
$SMARTY->display("nodeedit.html");
?>
