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

$LMS->CleanupTicketLastView();

// queue id's
if (isset($_GET['id']) && $_GET['id'] != 'all') {
	if (is_array($_GET['id']))
		$filter['ids'] = array_filter($_GET['id'], 'intval');
	elseif (intval($_GET['id']))
		$filter['ids'] = array(intval($_GET['id']));
	if (!isset($filter['ids']) || empty($filter['ids']))
		$SESSION->redirect('?m=rtqueuelist');
	if (isset($filter['ids']))
		$filter['ids'] = array_filter($filter['ids'], array($LMS, 'QueueExists'));
}

if (!empty($filter['ids'])) {
	foreach ($filter['ids'] as $queueidx => $queueid)
		if (!$LMS->GetUserRightsRT(Auth::GetCurrentUser(), $queueid))
			unset($filter['ids'][$queueidx]);
	if (empty($filter['ids']))
		access_denied();
} else {
	$queues = $DB->GetCol('SELECT queueid FROM rtrights WHERE userid=?', array(Auth::GetCurrentUser()));

	if (!$queues)
		access_denied();

	if (count($queues) != $DB->GetOne('SELECT COUNT(*) FROM rtqueues'))
		$filter['ids'] = $queues;
}

// category id's
if (isset($_GET['catid'])) {
	if (is_array($_GET['catid']))
		$filter['catids'] = array_filter($_GET['catid'], 'intval');
	elseif (intval($_GET['catid']))
		$filter['catids'] = array(intval($_GET['catid']));
}

if (!empty($filter['catids'])) {
	foreach ($filter['catids'] as $catidx => $catid)
		if (!$LMS->GetUserRightsToCategory(Auth::GetCurrentUser(), $catid))
			unset($filter['catids'][$catidx]);
	if (empty($filter['catids']))
		access_denied();
} else {
	$categories = $DB->GetCol('SELECT categoryid FROM rtcategoryusers WHERE userid=?', array(Auth::GetCurrentUser()));
	$all_cat = $DB->GetOne('SELECT COUNT(*) FROM rtcategories');

	if (!$categories && $all_cat)
		access_denied();

	if (count($categories) != $all_cat)
		$filter['catids'] = $categories;
}

// sort order
if (isset($_GET['o']))
	$filter['order'] = $_GET['o'];

// service id's
if (isset($_GET['ts'])) {
	if (is_array($_GET['ts']))
		$filter['serviceids'] = array_filter($_GET['ts'], 'intval');
	elseif (intval($_GET['ts']))
		$filter['serviceids'] = array(intval($_GET['ts']));
	elseif ($_GET['ts'] == 'all')
		$filter['serviceids'] = null;
}

// verifier id's
if (isset($_GET['vids'])) {
	if (is_array($_GET['vids']))
		$filter['verifierids'] = array_filter($_GET['vids'], 'intval');
	elseif (intval($_GET['vids']))
		$filter['verifierids'] = array(intval($_GET['vids']));
	elseif ($_GET['vids'] == 'all')
		$filter['verifierids'] = null;
}

// types
if (isset($_GET['tt'])) {
	if (is_array($_GET['tt']))
		$filter['typeids'] = array_filter($_GET['tt'], 'intval');
	elseif (intval($_GET['tt']))
		$filter['typeids'] = array(intval($_GET['tt']));
	elseif ($_GET['tt'] == 'all')
		$filter['typeids'] = null;
}

// owner
if (isset($_GET['owner'])) {
	if (is_array($_GET['owner'])) {
		if (count($_GET['owner']) == 1 && reset($_GET['owner']) <= 0)
			$filter['owner'] = intval(reset($_GET['owner']));
		else
			$filter['owner'] = array_filter($_GET['owner'], 'intval');
	} elseif (intval($_GET['owner']) > 0)
		$filter['owner'] = array(intval($_GET['owner']));
	else
		$filter['owner'] = intval($_GET['owner']);
} elseif (!isset($filter['owner']))
	$filter['owner'] = -1;

// removed or not?
if (isset($_GET['r']))
	$filter['removed'] = $_GET['r'];

// deadline
if (isset($_GET['d']))
    $filter['deadline'] = $_GET['d'];

// status/state
if (isset($_GET['s'])) {
	if (is_array($_GET['s']))
		$filter['state'] = $_GET['s'];
	elseif ($_GET['s'] < 0)
		$filter['state'] = intval($_GET['s']);
	else
		$filter['state'] = array(intval($_GET['s']));
} elseif (!isset($filter['state'])) {
	$filter['state'] = ConfigHelper::getConfig('phpui.ticketlist_status');
	if (strlen($filter['state'])) {
		$filter['state'] = explode(',', $filter['state']);
		if (count($filter['state']) == 1)
			$filter['state'] = array_shift($filter['state']);
	}
}

// priority
if (isset($_GET['priority'])) {
	if (is_array($_GET['priority']))
		$filter['priority'] = $_GET['priority'];
	elseif ($_GET['priority'] == 'all')
		$filter['priority'] = null;
	else
		$filter['priority'] = array(intval($_GET['priority']));
} elseif (!isset($filter['priority'])) {
	$filter['priority'] = ConfigHelper::getConfig('phpui.ticketlist_priority');
	if (strlen($filter['priority']))
		$filter['priority'] = explode(',', $filter['priority']);
}

// netnodeid's
if (isset($_GET['nnids'])) {
    if (is_array($_GET['nnids']))
        $filter['netnodeids'] = array_filter($_GET['nnids'], 'intval');
	elseif (intval($_GET['nnids']))
        $filter['netnodeids'] = array(intval($_GET['nnids']));
	elseif ($_GET['nnids'] == 'all')
        $filter['netnodeids'] = null;
}

if (isset($_GET['unread']))
	$filter['unread'] = $_GET['unread'];
elseif (!isset($filter['unread']))
	$filter['unread'] = -1;

if (isset($_GET['rights']))
	$filter['rights'] = $_GET['rights'];

if (isset($_GET['page']))
	$filter['page'] = intval($_GET['page']);
elseif (!isset($filter['page']) || empty($filter['page']))
	$filter['page'] = 1;

$SESSION->saveFilter($filter);

$layout['pagetitle'] = trans('Tickets List');

$filter['netdevids'] = null;
$filter['count'] = true;

$filter['total'] = intval($LMS->GetQueueContents($filter));

$filter['limit'] = intval(ConfigHelper::getConfig('phpui.ticketlist_pagelimit', $filter['total']));
$filter['offset'] = ($filter['page'] - 1) * $filter['limit'];
$filter['count'] = false;

$queue = $LMS->GetQueueContents($filter);

$pagination = LMSPaginationFactory::getPagination($filter['page'], $filter['total'], $filter['limit'],
	ConfigHelper::checkConfig('phpui.short_pagescroller'));

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$filter['direction'] = $queue['direction'];
$filter['order'] = $queue['order'];

unset($queue['total']);
unset($queue['state']);
unset($queue['priority']);
unset($queue['order']);
unset($queue['direction']);
unset($queue['owner']);
unset($queue['removed']);
unset($queue['deadline']);
unset($queue['service']);
unset($queue['type']);
unset($queue['unread']);
unset($queue['rights']);
unset($queue['verifier']);
unset($queue['netnode']);

$queues = $LMS->GetQueueList(array('stats' => false));
$categories = $LMS->GetCategoryListByUser(Auth::GetCurrentUser());
$netnodelist = $LMS->GetNetNodeList(array(), 'name');
unset($netnodelist['total']);
unset($netnodelist['order']);
unset($netnodelist['direction']);

if (isset($_GET['assign']) && !empty($_GET['ticketid'])) {
	$LMS->TicketChange($_GET['ticketid'], array('owner' => Auth::GetCurrentUser()));
	$SESSION->redirect(str_replace('&assign','',"$_SERVER[REQUEST_URI]"));
}

$SMARTY->assign('pagination', $pagination);
$SMARTY->assign('queues', $queues);
$SMARTY->assign('categories', $categories);
$SMARTY->assign('queue', $queue);
$SMARTY->assign('netnodelist', $netnodelist);
$SMARTY->assign('users', $LMS->GetUserNames());

$SMARTY->display('rt/rtqueueview.html');

?>
