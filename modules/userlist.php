<?php

/*
 * LMS version 1.6-cvs
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

$layout['pagetitle'] = trans('Customers List');

if(!isset($_GET['o']))
	$SESSION->restore('ulo', $o);
else
	$o = $_GET['o'];
$SESSION->save('ulo', $o);

if(!isset($_GET['s']))
	$SESSION->restore('uls', $s);
else
	$s = $_GET['s'];
$SESSION->save('uls', $s);

if(!isset($_GET['n']))
	$SESSION->restore('uln', $n);
else
	$n = $_GET['n'];
$SESSION->save('uln', $n);

if(!isset($_GET['g']))
	$SESSION->restore('ulg', $g);
else
	$g = $_GET['g'];
$SESSION->save('ulg', $g);

if (! isset($_GET['page']))
	$SESSION->restore('ulp', $_GET['page']);
	    
$userlist = $LMS->GetUserList($o, $s, $n, $g);
$listdata['total'] = $userlist['total'];
$SESSION->restore('uls', $listdata['state']);
$listdata['network'] = $userlist['network'];
$listdata['usergroup'] = $userlist['usergroup'];
$listdata['order'] = $userlist['order'];
$listdata['below'] = $userlist['below'];
$listdata['over'] = $userlist['over'];
$listdata['direction'] = $userlist['direction'];

$page = (! $_GET['page'] ? 1 : $_GET['page']); 
$pagelimit = (!isset($LMS->CONFIG['phpui']['customerlist_pagelimit']) ? $listdata['total'] : $LMS->CONFIG['phpui']['customerlist_pagelimit']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('ulp', $page);

unset($userlist['total']);
unset($userlist['state']);
unset($userlist['network']);
unset($userlist['usergroup']);
unset($userlist['order']);
unset($userlist['below']);
unset($userlist['over']);
unset($userlist['direction']);
$SMARTY->assign('userlist',$userlist);
$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('networks', $LMS->GetNetworks());
$SMARTY->assign('usergroups', $LMS->UsergroupGetAll());
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('page',$page);
$SMARTY->assign('start',$start);

$SMARTY->display('userlist.html');

?>
