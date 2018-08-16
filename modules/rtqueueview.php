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

$queuedata = array(
	'id' => null,
	'service' => null,
	'type' => null,
);

if (isset($_GET['id']) && $_GET['id'] != 'all') {
	if (is_array($_GET['id']))
		$queuedata['id'] = array_filter($_GET['id'], 'intval');
	elseif (intval($_GET['id']))
		$queuedata['id'] = array(intval($_GET['id']));
	if (!isset($queuedata['id']) || empty($queuedata['id']))
		$SESSION->redirect('?m=rtqueuelist');
	if (isset($queuedata['id']))
		$queuedata['id'] = array_filter($queuedata['id'], array($LMS, 'QueueExists'));
}

if (isset($_GET['ts'])) {
    if (is_array($_GET['ts']))
        $queuedata['service'] = array_filter($_GET['ts'], 'intval');
	elseif (intval($_GET['ts']))
        $queuedata['service'] = array(intval($_GET['ts']));
    if (!isset($queuedata['service']) || empty($queuedata['service']))
        $SESSION->redirect('?m=rtqueuelist');
}

if (isset($_GET['tt'])) {
    if (is_array($_GET['tt']))
        $queuedata['type'] = array_filter($_GET['tt'], 'intval');
	elseif (intval($_GET['tt']))
        $queuedata['type'] = array(intval($_GET['tt']));
    if (!isset($queuedata['type']) || empty($queuedata['type']))
        $SESSION->redirect('?m=rtqueuelist');
}

if (isset($_GET['catid'])) {
	if (is_array($_GET['catid']))
		$queuedata['catid'] = array_filter($_GET['catid'], 'intval');
	elseif (intval($_GET['catid']))
		$queuedata['catid'] = array(intval($_GET['catid']));
}

if (!empty($queuedata['id'])) {
	foreach ($queuedata['id'] as $queueidx => $queueid)
		if (!$LMS->GetUserRightsRT(Auth::GetCurrentUser(), $queueid))
			unset($queuedata['id'][$queueidx]);
	if (empty($queuedata['id'])) {
		$SMARTY->display('noaccess.html');
		$SESSION->close();
		die;
	}
} else {
	$queues = $DB->GetCol('SELECT queueid FROM rtrights WHERE userid=?', array(Auth::GetCurrentUser()));

	if (!$queues) {
		$SMARTY->display('noaccess.html');
		$SESSION->close();
		die;
	}

	if (count($queues) != $DB->GetOne('SELECT COUNT(*) FROM rtqueues'))
		$queuedata['id'] = $queues;
}

if (!empty($queuedata['catid'])) {
	foreach ($queuedata['catid'] as $catidx => $catid)
		if (!$LMS->GetUserRightsToCategory(Auth::GetCurrentUser(), $catid))
			unset($queuedata['catid'][$catidx]);
	if (empty($queuedata['catid'])) {
		$SMARTY->display('noaccess.html');
		$SESSION->close();
		die;
	}
} else {
	$categories = $DB->GetCol('SELECT categoryid FROM rtcategoryusers WHERE userid=?', array(Auth::GetCurrentUser()));
	$all_cat = $DB->GetOne('SELECT COUNT(*) FROM rtcategories');

	if (!$categories && $all_cat) {
		$SMARTY->display('noaccess.html');
		$SESSION->close();
		die;
	}

	if (count($categories) != $all_cat)
		$queuedata['catid'] = $categories;
}

if (!isset($_GET['id']))
	$SESSION->restore('rtqueue', $queuedata['id']);
$SESSION->save('rtqueue', $queuedata['id']);

if(!isset($_GET['o']))
	$SESSION->restore('rto', $o);
else
	$o = $_GET['o'];
$SESSION->save('rto', $o);

if(!isset($_GET['owner']))
	$SESSION->restore('rtowner', $owner);
else
	$owner = $_GET['owner'];
$SESSION->save('rtowner', $owner);
if (is_null($owner))
	$owner = -1;

if (!isset($_GET['catid']))
	$SESSION->restore('rtc', $queuedata['catid']);
$SESSION->save('rtc', $queuedata['catid']);

if(!isset($_GET['r']))
	$SESSION->restore('rtr', $r);
else
	$r = $_GET['r'];
$SESSION->save('rtr', $r);

if(!isset($_GET['d']))
    $SESSION->restore('rtd', $deadline);
else
    $deadline = $_GET['d'];
$SESSION->save('rtd', $deadline);

if (isset($_GET['s'])) {
	if (is_array($_GET['s']))
		$s = $_GET['s'];
	elseif ($_GET['s'] < 0)
		$s = intval($_GET['s']);
	else
		$s = array(intval($_GET['s']));
} elseif ($SESSION->is_set('rts'))
	$SESSION->restore('rts', $s);
else {
	$s = ConfigHelper::getConfig('phpui.ticketlist_status');
	if (strlen($s)) {
		$s = explode(',', $s);
		if (count($s) == 1)
			$s = $s[0];
	}
}
$SESSION->save('rts', $s);

if (isset($_GET['priority'])) {
	if (is_array($_GET['priority']))
		$priority = $_GET['priority'];
	elseif ($_GET['priority'] == 'all')
		$priority = null;
	else
		$priority = array(intval($_GET['priority']));
} elseif ($SESSION->is_set('rtprio'))
	$SESSION->restore('rtprio', $priority);
else {
	$priority = ConfigHelper::getConfig('phpui.ticketlist_priority');
	if (strlen($priority))
		$priority = explode(',', $priority);
}
$SESSION->save('rtprio', $priority);

if(!isset($_GET['ts']))
    $SESSION->restore('ts', $ts);
else
    $ts = $_GET['ts'];
$SESSION->save('rtts', $ts);

if(!isset($_GET['tt']))
    $SESSION->restore('tt', $tt);
else
    $tt = $_GET['tt'];
$SESSION->save('tt', $tt);

if (isset($_GET['unread']))
	$unread = $_GET['unread'];
else
	$SESSION->restore('rtunread', $unread);
$SESSION->save('rtunread', $unread);

if (isset($_GET['rights']))
	$rights = $_GET['rights'];
else
	$SESSION->restore('rtrights', $rights);
$SESSION->save('rtrights', $rights);

$layout['pagetitle'] = trans('Tickets List');

$total = intval($LMS->GetQueueContents(array('ids' => $queuedata['id'], 'order' => $o, 'state' => $s, 'priority' => $priority,
	'owner' => $owner, 'catids' => $queuedata['catid'], 'removed' => $r, 'netdevids' => null, 'netnodeids' => null,
	'deadline' => $deadline, 'serviceids' => $queuedata['service'], 'typeids' => $queuedata['type'], 'unread' => $unread,
	'rights' => $rights, 'count' => true)));

$limit = intval(ConfigHelper::getConfig('phpui.ticketlist_pagelimit', $total));
$page = !isset($_GET['page']) ? 1 : intval($_GET['page']);
$offset = ($page - 1) * $limit;

$queue = $LMS->GetQueueContents(array('ids' => $queuedata['id'], 'order' => $o, 'state' => $s, 'priority' => $priority,
	'owner' => $owner, 'catids' => $queuedata['catid'], 'removed' => $r, 'netdevids' => null, 'netnodeids' => null,
	'deadline' => $deadline, 'serviceids' => $queuedata['service'], 'typeids' => $queuedata['type'], 'unread' => $unread,
	'rights' => $rights, 'count' => false, 'offset' => $offset, 'limit' => $limit));

$pagination = LMSPaginationFactory::getPagination($page, $total, $limit, ConfigHelper::checkConfig('phpui.short_pagescroller'));

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if ($SESSION->is_set('rtp') && !isset($_GET['page']))
	$SESSION->restore('rtp', $_GET['page']);

$queuedata['total'] = $total;
$queuedata['state'] = $queue['state'];
$queuedata['priority'] = $queue['priority'];
$queuedata['order'] = $queue['order'];
$queuedata['direction'] = $queue['direction'];
$queuedata['owner'] = $queue['owner'];
$queuedata['removed'] = $queue['removed'];
$queuedata['deadline'] = $queue['deadline'];
$queuedata['service'] = $queue['service'];
$queuedata['type'] = $queue['type'];
$queuedata['unread'] = $queue['unread'];
$queuedata['rights'] = $queue['rights'];

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

$SESSION->save('rtp', $page);

$queues = $LMS->GetQueueListByUser(Auth::GetCurrentUser(), false);
$categories = $LMS->GetCategoryListByUser(Auth::GetCurrentUser());

if (isset($_GET['assign']) && !empty($_GET['ticketid'])) {
    $LMS->TicketChange($_GET['ticketid'], array('owner' => Auth::GetCurrentUser()));
    $SESSION->redirect(str_replace('&assign','',"$_SERVER[REQUEST_URI]"));
}

$SMARTY->assign('pagination', $pagination);
$SMARTY->assign('queues', $queues);
$SMARTY->assign('categories', $categories);
$SMARTY->assign('queue', $queue);
$SMARTY->assign('queuedata', $queuedata);
$SMARTY->assign('users', $LMS->GetUserNames());

$SMARTY->display('rt/rtqueueview.html');

?>
