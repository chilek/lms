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

$layout['pagetitle'] = "Podgl±d bazy danych";

$database = $LMS->DatabaseFetchContent($_GET['db']);

if($_GET['rawmode']=="true")
{
	$database['rawmode'] = TRUE;
	if($_GET['save']=="true")
	{
		header("Content-Type: application/octetstream");
		header("Content-Disposition: attachment; filename=lms-backup-".date("Ymd-His",$_GET['db']).".sql");
		header("Pragma: public");
	}
	else
		header("Content-Type: text/plain; charset=ISO-8859-2");
}

$SMARTY->assign('layout',$layout);
$SMARTY->assign('database',$database);
if(!$database['rawmode'])
	$SMARTY->display('header.html');
$SMARTY->display('dbview.html');
if(!$database['rawmode'])
	$SMARTY->display('footer.html');
?>