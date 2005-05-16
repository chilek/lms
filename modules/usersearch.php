<?php

/*
 * LMS version 1.7-cvs
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

if(!isset($_GET['o']))
	$SESSION->restore('uslo', $o);
else
	$o = $_GET['o'];
$SESSION->save('uslo', $o);

if(!isset($_POST['s']))
	$SESSION->restore('usls', $s);
else
	$s = $_POST['s'];
$SESSION->save('usls', $s);

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

if(!isset($_POST['k']))
	$SESSION->restore('uslk', $k);
else
	$k = $_POST['k'];
$SESSION->save('uslk', $k);

if(isset($_GET['search']))
{
	$layout['pagetitle'] = trans('Customer Search Results');
	$userlist = $LMS->GetUserList($o, $s, $n, $g, $search, NULL, $k);
	
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

	if (! isset($_GET['page']))
		$SESSION->restore('uslp', $_GET['page']);

	$page = (! $_GET['page'] ? 1 : $_GET['page']); 
	$pagelimit = (!isset($LMS->CONFIG['phpui']['customerlist_pagelimit']) ? $listdata['total'] : $LMS->CONFIG['phpui']['customerlist_pagelimit']);
	$start = ($page - 1) * $pagelimit;

	$SESSION->save('uslp', $page);
		
	$SMARTY->assign('userlist',$userlist);
	$SMARTY->assign('listdata',$listdata);
	$SMARTY->assign('pagelimit',$pagelimit);
	$SMARTY->assign('page',$page);
	$SMARTY->assign('start',$start);
	
	if(isset($_GET['print']))
	{
		$SMARTY->display('printuserlist.html');
	}
	elseif($listdata['total'] == 1)
	{
		$SESSION->redirect('?m=userinfo&id='.$userlist[0]['id']);
	}
	else
		$SMARTY->display('usersearchresults.html');
}
else
{
	$layout['pagetitle'] = trans('Customer Search');
	
	$SESSION->remove('uslp');
	
	$SMARTY->assign('networks', $LMS->GetNetworks());
	$SMARTY->assign('usergroups', $LMS->UsergroupGetAll());
	$SMARTY->assign('k', $k);
	$SMARTY->display('usersearch.html');
}

?>


