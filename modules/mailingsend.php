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

$layout[pagetitle]="Mailing";

$mailing = $_POST[mailing];

if($mailing[group] < 0 || $mailing[group] > 3)
	$error[group] = "Wybra³e¶ b³êdn± grupê u¿ytkowników";

if($mailing[sender]=="")
	$error[sender] = "Proszê podaæ e-mail nadawcy!";

if($mailing[from]=="")
	$error[from] = "Proszê podaæ nadawcê!";

if($mailing[subject]=="")
	$error[subject] = "Proszê podaæ temat listu!";

if($error)
{
	$SMARTY->assign("layout",$layout);
	$SMARTY->assign("error",$error);
	$SMARTY->assign("mailing",$mailing);
	$SMARTY->display("header.html");
	$SMARTY->display("mailing.html");
	$SMARTY->display("footer.html");
}else{
	$layout[nomenu] = TRUE;
	$mailing[body] = textwrap($mailing[body]);
	$SMARTY->assign("mailing",$mailing);
	$SMARTY->assign("layout",$layout);
	$SMARTY->display("header.html");
	$SMARTY->display("mailingsend.html");
	$emails = $LMS->Mailing($mailing);
	$SMARTY->display("mailingsend-footer.html");
	$SMARTY->display("footer.html");
}
/*
 * $Log$
 * Revision 1.19  2003/09/05 13:11:24  lukasz
 * - nowy sposób wy¶wietlania informacji o b³êdach
 *
 * Revision 1.18  2003/08/24 13:12:54  lukasz
 * - massive attack: s/<?/<?php/g - that was causing problems on some fucked
 *   redhat's :>
 *
 * Revision 1.17  2003/08/18 16:52:19  lukasz
 * - added CVS Log tags
 *
 */
?>
