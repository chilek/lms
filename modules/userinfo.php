<?

/*
 * LMS version 1.0.0
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

if(!$LMS->UserExists($_GET[id]))
	header("Location: ?m=userlist");

$userinfo=$LMS->GetUser($_GET[id]);

$_SESSION[backto] = $_SERVER[QUERY_STRING];

$layout[pagetitle]="Informacje o u¿ytkowniku ".$userinfo[username];

$usernodes = $LMS->GetUserNodes($_GET[id]);
$usernodes[ownerid] = $_GET[id];
$SMARTY->assign("usernodes",$usernodes);
$SMARTY->assign("balancelist",$LMS->GetUserBalanceList($_GET[id]));
$SMARTY->assign("error",$error);
$SMARTY->assign("userinfo",$userinfo);
$SMARTY->assign("layout",$layout);
$SMARTY->display("userinfo.html");

?>
