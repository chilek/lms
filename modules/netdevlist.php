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

$layout['pagetitle'] = trans('Network Devices');

if(!isset($_GET['o']))
	$o = $_SESSION['ndlo'];
else
	$o = $_GET['o'];
$_SESSION['ndlo'] = $o;

$netdevlist = $LMS->GetNetDevList($o);
$listdata['total'] = $netdevlist['total'];
$listdata['order'] = $netdevlist['order'];
$listdata['direction'] = $netdevlist['direction'];
unset($netdevlist['total']);
unset($netdevlist['order']);
unset($netdevlist['direction']);

if (isset($_SESSION['nlp']) && !isset($_GET['page']))
        $_GET['page'] = $_SESSION['nlp'];
	
$page = (! $_GET['page'] ? 1 : $_GET['page']);
$pagelimit = (! $LMS->CONFIG['phpui']['nodelist_pagelimit'] ? $listdata['total'] : $LMS->CONFIG['phpui']['nodelist_pagelimit']);
$start = ($page - 1) * $pagelimit;

$_SESSION['nlp'] = $page;

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$SMARTY->assign('page',$page);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('start',$start);
$SMARTY->assign('netdevlist',$netdevlist);
$SMARTY->assign('listdata',$listdata);
$SMARTY->display('netdevlist.html');

?>
