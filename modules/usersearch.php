<?php

/*
 * LMS version 1.5-cvs
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
	$SESSION->restore('usersearch', $search);
else
	$SESSION->save('usersearch', $search);

if(!isset($_GET['o']))
	$SESSION->restore('uslo', $o);
else
	$o = $_GET['o'];

$SESSION->save('uslo', $o);

if(!isset($_GET['s']))
	$SESSION->restore('usls', $s);	
else
	$s = $_GET['s'];
	
$SESSION->save('usls', $s);
				
$layout['pagetitle'] = trans('Customer Search');

if($_GET['search']==1 || isset($_GET['search']))
{
	$userlist = $LMS->SearchUserList($o,$s,$search);
	if ($userlist['total'] == 1)
	{
		$SESSION->redirect('?m=userinfo&id='.$userlist[0]['id']);
	}
	else
	{
		$SMARTY->assign('userlist',$userlist);
		$SMARTY->display('usersearchresults.html');
	}
}
else
	$SMARTY->display('usersearch.html');

?>
