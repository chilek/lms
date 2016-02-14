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
	$SESSION->restore('clo', $order);
else
	$order = $_GET['o'];
$SESSION->save('clo', $order);

if(!isset($_GET['s']))
	$SESSION->restore('cls', $state);
else
	$state = $_GET['s'];
$SESSION->save('cls', $state);

if(!isset($_GET['n']))
	$SESSION->restore('cln', $network);
else
	$network = $_GET['n'];
$SESSION->save('cln', $network);

if(!isset($_GET['g']))
	$SESSION->restore('clg', $customergroup);
else
	$customergroup = $_GET['g'];
$SESSION->save('clg', $customergroup);

if(!isset($_GET['ng']))
        $SESSION->restore('clng', $nodegroup);
else
        $nodegroup = $_GET['ng'];
$SESSION->save('clng', $nodegroup);

if(!isset($_GET['d']))
        $SESSION->restore('cld', $division);
else
        $division = $_GET['d'];
$SESSION->save('cld', $division);
		
if (! isset($_GET['page']))
	$SESSION->restore('clp', $_GET['page']);

if(!isset($_GET['assigments']))
        $SESSION->restore('clas', $as);
else
        $as = $_GET['assigments'];
$SESSION->save('clas', $as);
	    
$page = !$_GET['page'] ? 1 : intval($_GET['page']);
$sqlskey = 'AND';
$offset = NULL;
$count = TRUE;
$summary = $LMS->GetCustomerList(compact("order", "state", "network", "customergroup", "search", "time", "sqlskey", "nodegroup", "division", "limit", "offset", "count", "as"));
$total = intval($summary['total']);
$limit = intval(ConfigHelper::getConfig('phpui.customerlist_pagelimit', 100));
$offset = ($page - 1) * $limit;
$count = FALSE;
$customerlist = $LMS->GetCustomerList(compact("order", "state", "network", "customergroup", "search", "time", "sqlskey", "nodegroup", "division", "limit", "offset", "count", "as"));

$pagination = LMSPaginationFactory::getPagination($page, $total, $limit, ConfigHelper::checkConfig('phpui.short_pagescroller'));

$listdata['below'] = $summary['below'];
$listdata['over'] = $summary['over'];
$listdata['total'] = $customerlist['total'];
$listdata['order'] = $customerlist['order'];
$listdata['direction'] = $customerlist['direction'];
$listdata['network'] = $network;
$listdata['nodegroup'] = $nodegroup;
$listdata['customergroup'] = $customergroup;
$listdata['division'] = $division;
$listdata['state'] = $state;
$listdata['assigments'] = $as;

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
