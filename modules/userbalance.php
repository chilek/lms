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

if(!$LMS->UserExists($_GET[id]))
	header("Location: ?m=userlist");

$username=$LMS->GetUserName($_GET[id]);
$id = $_GET[id];

$layout[pagetitle]='Rachunek u¿ytkownika '.$username;

$SMARTY->assign("balancelist",$LMS->GetUserBalanceList($_GET[id]));
$SMARTY->assign("layout",$layout);
$SMARTY->assign("username",$username);
$SMARTY->assign("id",$id);
$SMARTY->display("userbalance.html");

/*
 * $Log$
 * Revision 1.18  2003/10/06 18:53:22  lexx
 * - odsy³acz psu³ tytu³ strony
 *
 * Revision 1.17  2003/09/09 01:22:28  lukasz
 * - nowe finanse
 * - kosmetyka
 * - bugfixy
 * - i inne rzeczy o których aktualnie nie pamiêtam
 *
 * Revision 1.16  2003/08/24 13:12:54  lukasz
 * - massive attack: s/<?/<?php/g - that was causing problems on some fucked
 *   redhat's :>
 *
 * Revision 1.15  2003/08/18 16:52:19  lukasz
 * - added CVS Log tags
 *
 */
?>
