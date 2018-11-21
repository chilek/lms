<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

if (!isset($_GET['init'])) {
	if (isset($_GET['o']))
		$filter['order'] = $_GET['o'];

	if (isset($_GET['t']))
		if (is_array($_GET['t'])) {
			$filter['type'] = Utils::filterIntegers($_GET['t']);
			if (count($filter['type']) == 1) {
				$first = reset($filter['type']);
				if ($first == 0)
					$filter['type'] = 0;
			}
		} else
			$filter['type'] = intval($_GET['t']);

	if (isset($_GET['service']))
		if (is_array($_GET['service'])) {
			$filter['service'] = Utils::filterIntegers($_GET['service']);
			if (count($filter['service']) == 1) {
				$first = reset($filter['service']);
				if ($first == 0)
					$filter['service'] = 0;
			}
		} else
			$filter['service'] = intval($_GET['service']);

	if (isset($_GET['c']))
		$filter['customer'] = $_GET['c'];

	if (isset($_GET['p']))
		$filter['numberplan'] = $_GET['p'];

	if (isset($_GET['usertype']))
		$filter['usertype'] = $_GET['usertype'];
	if (!isset($filter['usertype']) || empty($filter['usertype']))
		$filter['usertype'] = 'creator';

	if (isset($_GET['u']))
		if (is_array($_GET['u'])) {
			$filter['userid'] = Utils::filterIntegers($_GET['u']);
			if (count($filter['userid']) == 1) {
				$first = reset($filter['userid']);
				if ($first == 0)
					$filter['userid'] = 0;
			}
		} else
			$filter['userid'] = intval($_GET['u']);

	if (isset($_GET['periodtype']))
		$filter['periodtype'] = $_GET['periodtype'];
	if (!isset($filter['periodtype']) || empty($filter['periodtype']))
		$filter['periodtype'] = 'creationdate';

	if (isset($_GET['from'])) {
		if($_GET['from'] != '') {
			list ($year, $month, $day) = explode('/', $_GET['from']);
			$filter['from'] = mktime(0,0,0, $month, $day, $year);
		} else
			$filter['from'] = 0;
	} elseif (!isset($filter['from']))
		$filter['from'] = 0;

	if (isset($_GET['to'])) {
		if ($_GET['to'] != '') {
			list ($year, $month, $day) = explode('/', $_GET['to']);
			$filter['to'] = mktime(23,59,59, $month, $day, $year);
		} else
			$filter['to'] = 0;
	} elseif (!isset($filter['to']))
		$filter['to'] = 0;

	if (isset($_GET['s']))
		$filter['status'] = $_GET['s'];
	elseif (!isset($filter['status']))
		$filter['status'] = -1;
} else {
	$filter = array(
		'status' => -1,
	);
	$SMARTY->clearAssign('persistent_filter');
	$SESSION->saveFilter($filter);
}

$filter['count'] = true;
$filter['total'] = intval($LMS->GetDocumentList($filter));

$filter['limit'] = intval(ConfigHelper::getConfig('phpui.documentlist_pagelimit', 100));
$filter['page'] = intval(isset($_GET['page']) ? $_GET['page'] : ceil($filter['total'] / $filter['limit']));
if (empty($filter['page']))
	$filter['page'] = 1;
$filter['offset'] = ($filter['page'] - 1) * $filter['limit'];

$SESSION->saveFilter($filter);

$filter['count'] = false;
$documentlist = $LMS->GetDocumentList($filter);

$pagination = LMSPaginationFactory::getPagination($filter['page'], $filter['total'], $filter['limit'],
	ConfigHelper::checkConfig('phpui.short_pagescroller'));

$filter['order'] = $documentlist['order'];
$filter['direction'] = $documentlist['direction'];

unset($documentlist['total']);
unset($documentlist['order']);
unset($documentlist['direction']);

$layout['pagetitle'] = trans('Documents List');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if ($docid = $SESSION->get('documentprint')) {
	$SMARTY->assign('docid', $docid);
	$SESSION->remove('documentprint');
}

if ($filter['total']) {
	$SMARTY->assign('docrights', $DB->GetAllByKey('SELECT doctype, rights
			FROM docrights WHERE userid = ? AND rights > 1', 'doctype', array(Auth::GetCurrentUser())));
}

if (!ConfigHelper::checkConfig('phpui.big_networks'))
	$SMARTY->assign('customers', $LMS->GetCustomerNames());

$SMARTY->assign('users', $LMS->GetUserNames());
$SMARTY->assign('numberplans', $LMS->GetNumberPlans(array(
	'doctype' => array(DOC_CONTRACT, DOC_ANNEX, DOC_PROTOCOL, DOC_ORDER, DOC_SHEET, -6, -7, -8, -9, -99, DOC_PRICELIST, DOC_PROMOTION, DOC_WARRANTY, DOC_REGULATIONS, DOC_OTHER),
)));
$SMARTY->assign('documentlist', $documentlist);
$SMARTY->assign('pagination', $pagination);
$SMARTY->assign('filter', $filter);
$SMARTY->display('document/documentlist.html');

?>
