<?php

/*
 * LMS version 1.7-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

if(! $LMS->QueueExists($_GET['id']))
{
	$SESSION->redirect('?m=rtqueuelist');
}

$queuedata['id'] = $_GET['id'];

if(! $LMS->GetAdminRightsRT($AUTH->id, $queuedata['id']))
{
	$SMARTY->display('noaccess.html');
	$SESSION->close();
	die;
}

if(isset($_GET['delticketid']))
{
	$LMS->TicketDelete($_GET['delticketid']);
}

if(!isset($_GET['o']))
	$SESSION->restore('rto', $o);
else
	$o = $_GET['o'];
$SESSION->save('rto', $o);

if(!isset($_GET['s']))
	$SESSION->restore('rts', $s);
else
	$s = $_GET['s'];
$SESSION->save('rts', $s);

$queuedata['name'] = $LMS->GetQueueName($queuedata['id']);

$layout['pagetitle'] = trans('Queue Review: $0',$queuedata['name']);
$queue = $LMS->GetQueueContents($_GET['id'], $o, $s);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if ($SESSION->is_set('rtp') && !isset($_GET['page']))
	$SESSION->restore('rtp', $_GET['page']);

$queuedata['total'] = $queue['total'];
$queuedata['state'] = $queue['state'];
$queuedata['order'] = $queue['order'];
$queuedata['direction'] = $queue['direction'];
unset($queue['total']);
unset($queue['state']);
unset($queue['order']);
unset($queue['direction']);

$page = (!isset($_GET['page']) ? 1 : $_GET['page']); 
$pagelimit = (!isset($LMS->CONFIG['phpui']['ticketlist_pagelimit']) ? $queuedata['total'] : $LMS->CONFIG['phpui']['ticketlist_pagelimit']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('rtp', $page);

$SMARTY->assign('queue', $queue);
$SMARTY->assign('queuedata', $queuedata);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('page',$page);
$SMARTY->assign('start',$start);
$SMARTY->display('rtqueueview.html');

?>
