<?php

/*
 * LMS version 1.2-cvs
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

$layout['pagetitle'] = "Wydruki";
$SMARTY->assign('layout',$layout);

$su = $DB->GetOne("SELECT min(time) FROM cash");
$eu = $DB->GetOne("SELECT max(time) FROM cash");
$sm = date("m",$su);
$sy = date("Y",$su);
$em = date("m",$eu);
$ey = date("Y",$eu);

for($d=$sy;$d<$ey+1;$d++)
{
//	echo "<B>$d</B><BR>";
	if($d==$sy)
		$smm = $sm;
	else
		$smm = 1;
	if($d==$ey)
		$emm = $em;
	else
		$emm = 12;
//	echo "<B>$smm-$emm</B><BR>";
	for($m=$smm;$m<$emm+1;$m++)
		$monthlist[] = "$d/$m";
}

$SMARTY->assign('monthlist',$monthlist);

switch($_GET['type'])
{
	case "userlist":
		$SMARTY->assign('userlist',$LMS->GetUserList($_SESSION['ulo'],$_SESSION['uls']));
		$SMARTY->display('printuserlist.html');
	break;

	case "nodelist":
		$SMARTY->assign('nodelist',$LMS->GetNodeList($_SESSION['nlo']));
		$SMARTY->display('printnodelist.html');
	break;
	
	case "userlistminus":
		$SMARTY->assign('userlist',$LMS->GetUserList($_SESSION['ulo'],$_SESSION['uls']));
		$SMARTY->display('printuserlistminus.html');
	break;

	default:
		$SMARTY->display('printindex.html');
	break;
}

?>