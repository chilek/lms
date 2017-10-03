<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

if (isset($_GET['id'])) {
	if (is_array($_GET['id']))
		$queuedata['id'] = array_filter($_GET['id'], 'intval');
	elseif (intval($_GET['id']))
		$queuedata['id'] = array(intval($_GET['id']));
	if (!isset($queuedata['id']) || empty($queuedata['id']))
		$SESSION->redirect('?m=rtqueuelist');
	if (isset($queuedata['id']))
		$queuedata['id'] = array_filter($queuedata['id'], array($LMS, 'QueueExists'));
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

if(!isset($_GET['r']))
	$SESSION->restore('rtr', $r);
else
	$r = $_GET['r'];
	$SESSION->save('rtr', $r);

if (isset($_GET['s'])) {
	if (is_array($_GET['s']))
		$s = $_GET['s'];
	elseif ($_GET['s'] == -1)
		$s = null;
	else
		$s = array(intval($_GET['s']));
} elseif ($SESSION->is_set('rts'))
	$SESSION->restore('rts', $s);
else
	$s = ConfigHelper::getConfig('phpui.ticketlist_status');
$SESSION->save('rts', $s);

$layout['pagetitle'] = trans('Tickets List');
$queue = $LMS->GetQueueContents($queuedata['id'], $o, $s, $owner, $queuedata['catid'], $r);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if ($SESSION->is_set('rtp') && !isset($_GET['page']))
	$SESSION->restore('rtp', $_GET['page']);

$queuedata['total'] = $queue['total'];
$queuedata['state'] = $queue['state'];
$queuedata['order'] = $queue['order'];
$queuedata['direction'] = $queue['direction'];
$queuedata['owner'] = $queue['owner'];
$queuedata['removed'] = $queue['removed'];

unset($queue['total']);
unset($queue['state']);
unset($queue['order']);
unset($queue['direction']);
unset($queue['owner']);
unset($queue['removed']);

$page = (!isset($_GET['page']) ? 1 : $_GET['page']);
$pagelimit = ConfigHelper::getConfig('phpui.ticketlist_pagelimit', $queuedata['total']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('rtp', $page);

$queues = $LMS->GetQueueList(false);
$categories = $LMS->GetCategoryListByUser(Auth::GetCurrentUser());

$SMARTY->assign('queues', $queues);
$SMARTY->assign('categories', $categories);
$SMARTY->assign('queue', $queue);
$SMARTY->assign('queuedata', $queuedata);
$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->assign('users', $LMS->GetUserNames());
$SMARTY->display('rt/rtqueueview.html');

?>
