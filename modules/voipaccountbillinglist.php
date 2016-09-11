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

function sessionHandler($item, $name) {
	global $SESSION;

	if(!isset($_GET[$item]))
		$SESSION->restore($name, $o);
	else
		$o = $_GET[$item];

	$SESSION->save($name, $o);
	return $o;
}

$layout['pagetitle'] = trans('Billing list');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$params = array();
$params['o']          = sessionHandler('o', 'vblo');
$params['id']         = sessionHandler('fvoipaccid', 'vblfvoipaccid');
$params['frangefrom'] = sessionHandler('frangefrom', 'vblfrangefrom');
$params['frangeto']   = sessionHandler('frangeto', 'vblfrangeto');
$params['ftype']      = sessionHandler('ftype', 'vblftype');
$params['fstatus']    = sessionHandler('fstatus', 'vblfstatus');

$bill_list = $LMS->getVoipBillings($params);

// CALL BILLING RANGE
if (!empty($params['frangefrom'])) {
	list($year, $month, $day) = explode('/', $params['frangefrom']);
	$listdata['frangefrom'] = mktime(0,0,0, $month, $day, $year);
}

if (!empty($params['frangeto'])) {
	list($year, $month, $day) = explode('/', $params['frangeto']);
	$listdata['frangeto'] = mktime(23,59,59, $month, $day, $year);
}

// CALL STATUS
if (!empty($params['fstatus']))
	switch ($params['fstatus']) {
		case CALL_ANSWERED:
		case CALL_NO_ANSWER:
		case CALL_BUSY:
		case CALL_SERVER_FAILED:
			$listdata['fstatus'] = $params['fstatus'];
		break;
	}

// CALL TYPE
if (!empty($params['ftype']))
	switch ($params['ftype']) {
		case CALL_OUTGOING:
		case CALL_INCOMING:
			$listdata['ftype'] = $params['ftype'];
		break;
	}

$voipaccountlist = $LMS->GetVoipAccountList($o, NULL, NULL);
unset($voipaccountlist['total']);
unset($voipaccountlist['order']);
unset($voipaccountlist['direction']);

$page = !$_GET['page'] ? 1 : intval($_GET['page']);
$total = intval(count($bill_list));
$limit = intval(ConfigHelper::getConfig('phpui.billinglist_pagelimit', 100));
$pagination = LMSPaginationFactory::getPagination($page, $total, $limit, ConfigHelper::checkConfig('phpui.short_pagescroller'));

$order = explode(',', $params['o']);
if (empty($order[1]) || $order[1] != 'desc')
	$order[1] = 'asc';

$listdata['order'] = $order[0];
$listdata['direction'] = $order[1];

if (!empty($_GET['page']))
	$listdata['page'] = (int) $_GET['page'];

if ($params['id'] != NULL)
	$listdata['fvoipaccid'] = $params['id'];

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
