<?php

/*
 * LMS version 1.5-cvs
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

$layout['pagetitle'] = "Usuniêcie komputera ".$LMS->GetNodeName($_GET['id']);
$SMARTY->assign('nodeid',$_GET['id']);

if (!$LMS->NodeExists($_GET['id']))
{
	$body = "<H1>".$layout['pagetitle']."</H1><P>Podany przez Ciebie ID jest b³êdny b±d¼ nie istnieje w bazie danych.</P>";
}else{

	if($_GET['is_sure']!=1)
	{
		$body = "<H1>".$layout['pagetitle']."</H1>";
		$body .= "<P>Czy jeste¶ pewien ¿e chcesz usun±æ komputer ".$LMS->GetNodeName($_GET['id'])."?</P>"; 
		$body .= "<P><A HREF=\"?m=nodedel&id=".$_GET['id']."&is_sure=1\">Tak, jestem pewien.</A></P>";
		$body .= "<P><A HREF=\"?".$_SESSION['backto']."\">Nie, rozmy¶li³em siê.</A></P>";
	}else{
		$owner = $LMS->GetNodeOwner($_GET['id']);
		$LMS->DeleteNode($_GET['id']);
		if(isset($_SESSION['backto']))
			header("Location: ?".$_SESSION['backto']);
		else
			header("Location: ?m=userinfo&id=".$owner);
		$body = "<H1>".$layout['pagetitle']."</H1>";
		$body .= "<P>Komputer ".$LMS->GetNodeName($_GET['id'])." zosta³ usuniêty.</P>";
	}
		
}
$SMARTY->display('header.html');
$SMARTY->assign('body',$body);
$SMARTY->display('dialog.html');
$SMARTY->display('footer.html');

?>