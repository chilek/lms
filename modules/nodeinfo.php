<?

/*
 * LMS version 1.0-cvs
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

$layout[pagetitle]="Informacje o komputerze";
$nodeid = $_GET[id];
$ownerid = $LMS->GetNodeOwner($nodeid);

$_SESSION[backto] = $_SERVER[QUERY_STRING];

if(!isset($_GET[ownerid]))
	$_SESSION[backto] .= "&ownerid=".$ownerid;

$SMARTY->assign("balancelist",$LMS->GetUserBalanceList($ownerid));
$SMARTY->assign("userinfo",$LMS->GetUser($ownerid));
$SMARTY->assign("nodeinfo",$LMS->GetNode($nodeid));
$SMARTY->assign("layout",$layout);
$SMARTY->display("nodeinfo.html");

?>
