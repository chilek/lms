<?php

/*
 * LMS version 1.4-cvs
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

$layout['pagetitle'] = "Mailing";

$mailing = $_POST['mailing'];

if($mailing['group'] < 0 || $mailing['group'] > 3)
	$error['group'] = "Wybra³e¶ b³êdn± grupê u¿ytkowników";

if($mailing['sender']==""){
	$error['sender'] = "Proszê podaæ e-mail nadawcy!";
}elseif(!check_email($mailing['sender']))
	$error['sender'] = "Podany e-mail nie wydaje siê poprawny!";

if($mailing['from']=="")
	$error['from'] = "Proszê podaæ nadawcê!";

if($mailing['subject']=="")
	$error['subject'] = "Proszê podaæ temat listu!";

if($error)
{
	$SMARTY->assign('error',$error);
	$SMARTY->assign('mailing',$mailing);
	$SMARTY->display('mailing.html');
}
else
{
	$layout['nomenu'] = TRUE;
	$mailing['body'] = textwrap($mailing['body']);
	$mailing['body'] = str_replace("\r", "", $mailing['body']);
	$SMARTY->assign('mailing',$mailing);
	$SMARTY->display('header.html');
	$SMARTY->display('mailingsend.html');
	$emails = $LMS->Mailing($mailing);
	$SMARTY->display("mailingsend-footer.html");
	$SMARTY->display('footer.html');
}

?>
