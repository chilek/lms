<?php

/*
 * LMS version 1.4-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

$search = $_POST['search'];

if(isset($search['s']))
	$_GET['s'] = $search['s'];

if(!isset($search))
	$search = $_SESSION['usersearch'];
else
	$_SESSION['usersearch'] = $search;

if(!isset($_GET['o']))
	$o = $_SESSION['uslo'];
else
	$o = $_GET['o'];

$_SESSION['uslo'] = $o;

if(!isset($_GET['s']))
	$s = $_SESSION['usls'];	
else
	$s = $_GET['s'];
	
$_SESSION['usls'] = $s;
				
$layout['pagetitle'] = 'Wyszukiwanie u¿ytkowników';

if($_GET['search']==1 || isset($_GET['search']))
{
	$SMARTY->assign('userlist',$LMS->SearchUserList($o,$s,$search));
	$SMARTY->display('usersearchresults.html');
}
else
{
	$SMARTY->display('usersearch.html');
}

?>
