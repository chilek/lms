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

if($_GET['is_sure'])
{
	$LMS->DatabaseDelete($_GET['db']);
	header("Location: ?m=".$_SESSION['lastmodule']);
	die;
} else
{
	$layout['pagetitle'] = "Usuniêcie kopii zapasowej";
	$SMARTY->display('header.html');
	$SMARTY->display('adminheader.html');
	echo "<H1>Usuniêcie bazy danych</H1>";
	echo "<p>Czy jeste¶ pewien ¿e chcesz usun±æ kopiê bazy utworzon± ".date("Y/m/d H:i.s",$_GET['db'])."?</p>";
	echo "<a href=\"?m=dbdel&db=".$_GET['db']."&is_sure=1\">Tak, jestem pewien</A>";
	$SMARTY->display('footer.html');
}

?>