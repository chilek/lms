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

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$layout['pagetitle'] = trans('Customer List');

if(!isset($_GET['o']))
	$o = $_SESSION['ulo'];
else
	$o = $_GET['o'];
$_SESSION['ulo'] = $o;

if(!isset($_GET['s']))
	$s = $_SESSION['uls'];
else
	$s = $_GET['s'];
$_SESSION['uls'] = $s;

if(!isset($_GET['n']))
	$n = $_SESSION['uln'];
else
	$n = $_GET['n'];
$_SESSION['uln'] = $n;

if(!isset($_GET['g']))
	$g = $_SESSION['ulg'];
else
	$g = $_GET['g'];
$_SESSION['ulg'] = $g;

if (isset($_SESSION['ulp']) && !isset($_GET['page']))
	$_GET['page'] = $_SESSION['ulp'];
	    
$userlist=$LMS->GetUserList($o, $s, $n, $g);
$listdata['total'] = $userlist['total'];
$listdata['state'] = $_SESSION['uls'];;
$listdata['network'] = $userlist['network'];
$listdata['usergroup'] = $userlist['usergroup'];
$listdata['order'] = $userlist['order'];
$listdata['below'] = $userlist['below'];
$listdata['over'] = $userlist['over'];
$listdata['direction'] = $userlist['direction'];

$page = (! $_GET['page'] ? 1 : $_GET['page']); 
$pagelimit = (! $LMS->CONFIG['phpui']['userlist_pagelimit'] ? $listdata['total'] : $LMS->CONFIG['phpui']['userlist_pagelimit']);
$start = ($page - 1) * $pagelimit;

$_SESSION['ulp'] = $page;

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