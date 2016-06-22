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

$id = (isset($_GET['fvoipaccid']) && $_GET['fvoipaccid'] != 'all') ? (int) $_GET['fvoipaccid'] : NULL;

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

// CUSTOMER ID
if ($id !== NULL)
	$where[] = "(cdr.callervoipaccountid = $id OR cdr.calleevoipaccountid = $id)";

// CALL BILLING RANGE
if (isset($_GET['frangefrom']) && $_GET['frangefrom'] != '') {
	list($year, $month, $day) = explode('/', $_GET['frangefrom']);
	$from = mktime(0,0,0, $month, $day, $year);

	$where[] = 'call_start_time >= ' . $from;
	$listdata['frangefrom'] = $from;
	unset($from);
}

if (isset($_GET['frangeto']) && $_GET['frangeto'] != '') {
	list($year, $month, $day) = explode('/', $_GET['frangeto']);
	$to = mktime(23,59,59, $month, $day, $year);

	$where[] = 'call_start_time <= ' . $to;
	$listdata['frangeto'] = $to;
	unset($to);
}

// CALL STATUS
if (!empty($_GET['fstatus'])) {
	switch ($_GET['fstatus']) {
		case CALL_ANSWERED:
		case CALL_NO_ANSWER:
		case CALL_BUSY:
		case CALL_SERVER_FAILED:
			$where[] = "cdr.status = " . $_GET['fstatus'];
			$listdata['fstatus'] = $_GET['fstatus'];
		break;
	}
}

// CALL TYPE
if (!empty($_GET['ftype'])) {
	switch ($_GET['ftype']) {
		case CALL_OUTGOING:
		case CALL_INCOMING:
			$where[] = "cdr.type = " . $_GET['ftype'];
			$listdata['ftype'] = $_GET['ftype'];
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
								   cdr.id, caller, callee, call_start_time as begintime, time_start_to_end as callbegintime, time_answer_to_end as callanswertime,
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

$voipaccountlist = $LMS->GetVoipAccountList($o, NULL, NULL);
unset($voipaccountlist['total']);
unset($voipaccountlist['order']);
unset($voipaccountlist['direction']);

$page = !$_GET['page'] ? 1 : intval($_GET['page']);
$total = intval(count($bill_list));
$limit = intval(ConfigHelper::getConfig('phpui.billinglist_pagelimit', 100));
$pagination = LMSPaginationFactory::getPagination($page, $total, $limit, ConfigHelper::checkConfig('phpui.short_pagescroller'));

$listdata['order'] = $order[0];
$listdata['direction'] = $order[1];

if (!empty($_GET['page']))
	$listdata['page'] = (int) $_GET['page'];

if ($id != NULL)
	$listdata['fvoipaccid'] = $id;

if ($SESSION->is_set('valp') && !isset($_GET['page']))
	$SESSION->restore('valp', $_GET['page']);

$SESSION->save('valp', $page);

$SMARTY->assign('voipaccounts', $voipaccountlist);
$SMARTY->assign('pagination', $pagination);
$SMARTY->assign('billings', $bill_list);
$SMARTY->assign('total', $total);
$SMARTY->assign('page', $page);
$SMARTY->assign('pagelimit', $limit);
$SMARTY->assign('listdata', $listdata);
$SMARTY->display('voipaccount/voipaccountbillinglist.html');

?>
