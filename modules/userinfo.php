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

if($LMS->UserExists($_GET[id]) == 0)
{
	header("Location: ?m=userlist");
	die;
}

$userinfo = $LMS->GetUser($_GET[id]);
$assigments = $LMS->GetUserAssignments($_GET[id]);
$balancelist = $LMS->GetUserBalanceList($_GET[id]);
$usernodes = $LMS->GetUserNodes($_GET[id]);
$tariffs = $LMS->GetTariffs();

$_SESSION[backto] = $_SERVER[QUERY_STRING];

$layout[pagetitle]="Informacje o u¿ytkowniku ".$userinfo[username];

$usernodes[ownerid] = $_GET[id];
$SMARTY->assign("usernodes",$usernodes);
$SMARTY->assign("balancelist",$balancelist);
$SMARTY->assign("assignments",$assigments);
$SMARTY->assign("error",$error);
$SMARTY->assign("userinfo",$userinfo);
$SMARTY->assign("layout",$layout);
$SMARTY->assign("tariffs",$tariffs);
$SMARTY->display("userinfo.html");

/*
 * $Log$
 * Revision 1.26  2003/09/09 01:22:28  lukasz
 * - nowe finanse
 * - kosmetyka
 * - bugfixy
 * - i inne rzeczy o których aktualnie nie pamiêtam
 *
 * Revision 1.25  2003/08/27 20:32:54  lukasz
 * - changed another ENUM (users.deleted) to BOOL
 *
 * Revision 1.24  2003/08/27 19:26:22  lukasz
 * - changed format of ipaddr storage in database
 *
 * Revision 1.23  2003/08/25 02:12:37  lukasz
 * - zmieniona obs³uga usuwania userów
 *
 * Revision 1.22  2003/08/24 13:12:54  lukasz
 * - massive attack: s/<?/<?php/g - that was causing problems on some fucked
 *   redhat's :>
 *
 * Revision 1.21  2003/08/18 16:52:19  lukasz
 * - added CVS Log tags
 *
 */
?>
