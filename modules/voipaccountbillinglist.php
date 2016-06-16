<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

$layout['pagetitle'] = trans('Billing list');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if(!isset($_GET['o']))
	$SESSION->restore('nlo', $o);
else
	$o = $_GET['o'];

$SESSION->save('nlo', $o);

$id = (isset($_GET['id'])) ? (int) $_GET['id'] : NULL;

// ORDER 
$order = explode(',', $o);
if (empty($order[1]) || $order[1] != 'desc')
	 $order[1] = 'asc';

 switch ($order[0]) {
	case 'caller_name':
	case 'callee_name':
	case 'caller':
	case 'callee':
	case 'begintime':
	case 'callbegintime':
	case 'callanswertime':
	case 'status':
	case 'type':
		$order_string = ' ORDER BY ' . $order[0] . ' ' . $order[1];
	break;

	default:
		$order_string = '';
}

// FILTERS
$where = array();

if ($id !== NULL)
	$where[] = "(cdr.callervoipaccountid = $id OR cdr.calleevoipaccountid = $id)";

// CALL START RANGE
if (!empty($_GET['frange'])) {
	switch ($_GET['frange']) {
		case 'today':
			$where[] = 'call_start_time > ' . mktime(0, 0, 0, date("n"), date("j"), date("Y"));
			$listdata['frange'] = 'today';
		break;
		
		case 'yesterday':
			$where[] = 'call_start_time > ' . (mktime(0, 0, 0, date("n"), date("j"), date("Y")) - 86400) . ' AND call_start_time < ' . mktime(0, 0, 0, date("n"), date("j"), date("Y"));
			$listdata['frange'] = 'yesterday';
		break;
		
		case 'currentmonth':
			$where[] = 'call_start_time > ' . mktime(0, 0, 0, date("n"), 1, date("Y"));
			$listdata['frange'] = 'currentmonth';
		break;
		
		case 'lastmonth':
			$where[] = 'call_start_time > ' . mktime(0, 0, 0, date('n', strtotime('first day of last month')), 1, date("Y")) . ' AND call_start_time < ' . mktime(0, 0, 0, date("n"), 1, date("Y"));
			$listdata['frange'] = 'lastmonth';
		break;
		
		case 'currentyear':
			$where[] = 'call_start_time > ' . mktime(0, 0, 0, 1, 1, date("Y"));
			$listdata['frange'] = 'currentyear';
		break;
		
		case 'lastyear':
			$where[] = 'call_start_time > ' . mktime(0, 0, 0, 1, 1, date('Y', strtotime('last year'))) . ' AND call_start_time < ' . mktime(0, 0, 0, 1, 1, date("Y"));
			$listdata['frange'] = 'lastyear';
		break;
	}
}

// CALL STATUS
if (!empty($_GET['fstatus'])) {
	switch ($_GET['fstatus']) {
		case 'answered':
			$where[] = "cdr.status ?LIKE? 'answered'";
			$listdata['fstatus'] = 'answered';
		break;
		
		case 'noanswer':
			$where[] = "cdr.status ?LIKE? 'no answer'";
			$listdata['fstatus'] = 'noanswer';
		break;
		
		case 'busy':
			$where[] = "cdr.status ?LIKE? 'busy'";
			$listdata['fstatus'] = 'busy';
		break;
	}
}

// CALL TYPE
if (!empty($_GET['ftype'])) {
	switch ($_GET['ftype']) {
		case CALL_OUTGOING:
			$where[] = "cdr.type = " . CALL_OUTGOING;
			$listdata['ftype'] = CALL_OUTGOING;
		break;
		
		case CALL_INCOMING:
			$where[] = "cdr.type = '" . CALL_INCOMING;
			$listdata['ftype'] = CALL_INCOMING;
		break;
	}
}

if ($where) {
	$where_string = ' WHERE ';
	foreach ($where as $single_condition)
		$where_string .= $single_condition . ' AND ';
	$where_string = rtrim($where_string, ' AND ');
}
else
	$where_string = '';

$bill_list = $DB->GetAll('SELECT
								   caller, callee, call_start_time as begintime, time_start_to_end as callbegintime, time_answer_to_end as callanswertime, 
								   cdr.type as type, callervoipaccountid, calleevoipaccountid, cdr.status as status, vacc.ownerid as callerownerid, vacc2.ownerid as calleeownerid,
								   c1.name as caller_name, c1.lastname as caller_lastname, c1.city as caller_city, c1.street as caller_street, c1.building as caller_building,
								   c2.name as callee_name, c2.lastname as callee_lastname, c2.city as callee_city, c2.street as callee_street, c2.building as callee_building
								FROM
								   voip_cdr cdr 
								   left join voipaccounts vacc on cdr.callervoipaccountid = vacc.id
								   left join voipaccounts vacc2 on cdr.calleevoipaccountid = vacc2.id
								   left join customers c1 on c1.id = vacc.ownerid
								   left join customers c2 on c2.id = vacc2.ownerid' .
								$where_string . $order_string);

$page = !$_GET['page'] ? 1 : intval($_GET['page']);
$total = intval(count($bill_list));
$limit = intval(ConfigHelper::getConfig('phpui.customerlist_pagelimit', 100));
$pagination = LMSPaginationFactory::getPagination($page, $total, $limit, ConfigHelper::checkConfig('phpui.short_pagescroller'));

$listdata['order'] = $order[0];
$listdata['direction'] = $order[1];

if (!empty($_GET['page']))
	$listdata['page'] = (int) $_GET['page'];

if ($id != NULL)
	$listdata['id'] = $id;

if ($SESSION->is_set('valp') && !isset($_GET['page']))
	$SESSION->restore('valp', $_GET['page']);

$SESSION->save('valp', $page);

$SMARTY->assign('pagination', $pagination);
$SMARTY->assign('billings', $bill_list);
$SMARTY->assign('total', $total);
$SMARTY->assign('page', $page);
$SMARTY->assign('pagelimit', $limit);
$SMARTY->assign('listdata', $listdata);
$SMARTY->display('voipaccount/voipaccountbillinglist.html');

?>
