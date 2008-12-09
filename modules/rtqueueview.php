<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2008 LMS Developers
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

if(isset($_GET['id']))
	$queuedata['id'] = ($_GET['id'] == '' ? 0 : $_GET['id']);

if(! $LMS->QueueExists($queuedata['id']) && $queuedata['id'] != 0)
{
	$SESSION->redirect('?m=rtqueuelist');
}

if($queuedata['id'] != 0)
{
	$right = $LMS->GetUserRightsRT($AUTH->id, $queuedata['id']);

	if(!$right)
	{
		$SMARTY->display('noaccess.html');
		$SESSION->close();
		die;
	}
}
else
{
	$queues = $DB->GetCol('SELECT queueid FROM rtrights WHERE userid=?', array($AUTH->id));
	if(sizeof($queues) != $DB->GetOne('SELECT COUNT(*) FROM rtqueues'))
		$queuedata['id'] = $queues;
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

if(isset($_GET['s']))
	$s = $_GET['s'];
elseif($SESSION->is_set('rts'))
	$SESSION->restore('rts', $s);
elseif(isset($CONFIG['phpui']['ticketlist_status']))
	$s = $CONFIG['phpui']['ticketlist_status'];
else
	$s = NULL;
$SESSION->save('rts', $s);

$layout['pagetitle'] = trans('Tickets List');
$queue = $LMS->GetQueueContents($queuedata['id'], $o, $s, $owner);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if ($SESSION->is_set('rtp') && !isset($_GET['page']))
	$SESSION->restore('rtp', $_GET['page']);

$queuedata['total'] = $queue['total'];
$queuedata['state'] = $queue['state'];
$queuedata['order'] = $queue['order'];
$queuedata['direction'] = $queue['direction'];
$queuedata['owner'] = $queue['owner'];

unset($queue['total']);
unset($queue['state']);
unset($queue['order']);
unset($queue['direction']);
unset($queue['owner']);

$page = (!isset($_GET['page']) ? 1 : $_GET['page']); 
$pagelimit = (!isset($CONFIG['phpui']['ticketlist_pagelimit']) ? $queuedata['total'] : $CONFIG['phpui']['ticketlist_pagelimit']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('rtp', $page);

$queues = $LMS->GetQueueList();

$SMARTY->assign('queues', $queues);
$SMARTY->assign('queue', $queue);
$SMARTY->assign('queuedata', $queuedata);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('page',$page);
$SMARTY->assign('start',$start);
$SMARTY->assign('users', $LMS->GetUserNames());
$SMARTY->display('rtqueueview.html');

?>
