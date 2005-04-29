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

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if(isset($_POST['search']))
	$search = $_POST['search'];

if(!isset($search))
	$SESSION->restore('usersearch', $search);
else
	$SESSION->save('usersearch', $search);

if(!isset($_POST['n']))
	$SESSION->restore('usln', $n);
else
	$n = $_POST['n'];

$s = $_POST['s'];

if(!isset($_POST['s']) && !$s)
	$SESSION->restore('usls', $s);	
else if(isset($_GET['s']))
	$s = $_GET['s'];
$SESSION->save('usls', $s);

if(!isset($_GET['o']))
	$SESSION->restore('uslo', $o);
else
	$o = $_GET['o'];
$SESSION->save('uslo', $o);

if(!isset($_POST['n']))
	$SESSION->restore('usln', $n);
else
	$n = $_POST['n'];
$SESSION->save('usln', $n);

if(!isset($_POST['g']))
	$SESSION->restore('uslg', $g);
else
	$g = $_POST['g'];
$SESSION->save('uslg', $g);

if(isset($_GET['search']))
{
	$layout['pagetitle'] = trans('Customer Search Results');
	$userlist = $LMS->GetUserList($o, $s, $n, $g, $search);
	
	$listdata['total'] = $userlist['total'];
	$listdata['direction'] = $userlist['direction'];
	$listdata['order'] = $userlist['order'];
	$listdata['state'] = $userlist['state'];
	
	unset($userlist['total']);
	unset($userlist['state']);
	unset($userlist['network']);
	unset($userlist['usergroup']);
	unset($userlist['direction']);
	unset($userlist['order']);
	unset($userlist['below']);
	unset($userlist['over']);
		
	$SMARTY->assign('userlist',$userlist);
	$SMARTY->assign('listdata',$listdata);
	
	if(isset($_GET['print']))
	{
		$SMARTY->display('printuserlist.html');
	}
	elseif($userlist['total'] == 1)
	{
		$SESSION->redirect('?m=userinfo&id='.$userlist[0]['id']);
	}
	else
		$SMARTY->display('usersearchresults.html');
}
else
{
	$layout['pagetitle'] = trans('Customer Search');
	$SMARTY->assign('networks', $LMS->GetNetworks());
	$SMARTY->assign('usergroups', $LMS->UsergroupGetAll());
	$SMARTY->display('usersearch.html');
}

?>
