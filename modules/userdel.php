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

$layout[pagetitle]="Usuni�cie u�ytkownika ID: ".sprintf("%04d",$_GET[id]);
$SMARTY->assign("layout",$layout);
$SMARTY->assign("userid",$_GET[id]);

if (!$LMS->UserExists($_GET[id]))
{
	$body = "<H1>".$layout[pagetitle]."</H1><P>Podany przez Ciebie ID jest b��dny b�d� nie istnieje w bazie danych.</P>";
}else{

	if($_GET[is_sure]!=1)
	{
		$body = "<H1>".$layout[pagetitle]."</H1>";
		$body .= "<P>Czy jeste� pewien �e chcesz usun�� u�ytkownika ".$LMS->GetUserName($_GET[id])."?</P>"; 
		$body .= "<P>Wszystkie dane u�ytkownika zostan� utracone, a tak�e wszystkie przypisane temu u�ytkownikowi komputery zostan� usuni�te.</P>";
		$body .= "<P><A HREF=\"?m=userdel&id=".$_GET[id]."&is_sure=1\">Tak, jestem pewien.</A></P>";
	}else{
		header("Location: ?".$_SESSION[backto]);
		$body = "<H1>".$layout[pagetitle]."</H1>";
		$body .= "<P>U�ytkownik ".$LMS->GetUserName($_GET[id])." zosta� usuni�ty.</P>";
		$LMS->DeleteUser($_GET[id]);
	}
		
}

$SMARTY->display("header.html");
$SMARTY->assign("body",$body);
$SMARTY->display("dialog.html");
$SMARTY->display("footer.html");

?>
