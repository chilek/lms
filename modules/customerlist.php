<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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
	$SESSION->restore('clo', $o);
else
	$o = $_GET['o'];
$SESSION->save('clo', $o);

if(!isset($_GET['s']))
	$SESSION->restore('cls', $s);
else
	$s = $_GET['s'];
$SESSION->save('cls', $s);

if(!isset($_GET['n']))
	$SESSION->restore('cln', $n);
else
	$n = $_GET['n'];
$SESSION->save('cln', $n);

if(!isset($_GET['g']))
	$SESSION->restore('clg', $g);
else
	$g = $_GET['g'];
$SESSION->save('clg', $g);

if(!isset($_GET['ng']))
        $SESSION->restore('clng', $ng);
else
        $ng = $_GET['ng'];
$SESSION->save('clng', $ng);

if(!isset($_GET['d']))
        $SESSION->restore('cld', $d);
else
        $d = $_GET['d'];
$SESSION->save('cld', $d);
		
if (! isset($_GET['page']))
	$SESSION->restore('clp', $_GET['page']);
	    
$page = !$_GET['page'] ? 1 : intval($_GET['page']);
$per_page = intval(ConfigHelper::getConfig('phpui.customerlist_pagelimit', 100));
$offset = ($page - 1) * $per_page;
$total = intval($LMS->GetCustomerList($o, $s, $n, $g, NULL, NULL, 'AND', $ng, $d, null, null, true));

$customerlist = $LMS->GetCustomerList($o, $s, $n, $g, NULL, NULL, 'AND', $ng, $d, $per_page, $offset);

$pagination = LMSPaginationFactory::getPagination($page, $total, $per_page, ConfigHelper::checkConfig('phpui.short_pagescroller'));

$listdata['total'] = $customerlist['total'];
$listdata['order'] = $customerlist['order'];
$listdata['below'] = $customerlist['below'];
$listdata['over'] = $customerlist['over'];
$listdata['direction'] = $customerlist['direction'];
$listdata['network'] = $n;
$listdata['nodegroup'] = $ng;
$listdata['customergroup'] = $g;
$listdata['division'] = $d;
$listdata['state'] = $s;

$SESSION->save('clp', $page);

unset($customerlist['total']);
unset($customerlist['state']);
unset($customerlist['order']);
unset($customerlist['below']);
unset($customerlist['over']);
unset($customerlist['direction']);

$SMARTY->assign('customerlist',$customerlist);
$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('networks', $LMS->GetNetworks());
$SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
$SMARTY->assign('nodegroups', $LMS->GetNodeGroupNames());
$SMARTY->assign('divisions', $DB->GetAll('SELECT id, shortname FROM divisions ORDER BY shortname'));
$SMARTY->assign('pagination', $pagination);

$SMARTY->display('customer/customerlist.html');
