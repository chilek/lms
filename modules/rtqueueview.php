<?php

/*
 * LMS version 1.4-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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
	header('Location: ?m=rtqueuelist');
	die;
}

$queuedata['id'] = $_GET['id'];

if(! $LMS->GetAdminRightsRT($SESSION->id, $queuedata['id']))
{
	$SMARTY->display('noaccess.html');
	die;
}

if(!isset($_GET['o']))
	$o = $_SESSION['rto'];
else
	$o = $_GET['o'];
$_SESSION['rto'] = $o;

if(!isset($_GET['s']))
	$s = $_SESSION['rts'];
else
	$s = $_GET['s'];
$_SESSION['rts'] = $s;

$queuedata['name'] = $LMS->GetQueueName($queuedata['id']);

$layout['pagetitle'] = 'Podgl±d kolejki: '.$queuedata['name'];
$queue = $LMS->GetQueueContents($_GET['id'], $o, $s);

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

if (isset($_SESSION['rtp']) && !isset($_GET['page']))
	$_GET['page'] = $_SESSION['rtp'];

$queuedata['total'] = $queue['total'];
$queuedata['state'] = $_SESSION['rts'];;
$queuedata['order'] = $queue['order'];
$queuedata['direction'] = $queue['direction'];
unset($queue['total']);
unset($queue['state']);
unset($queue['order']);
unset($queue['direction']);

$page = (! $_GET['page'] ? 1 : $_GET['page']); 
$pagelimit = (! $LMS->CONFIG['phpui']['ticketlist_pagelimit'] ? $queuedata['total'] : $LMS->CONFIG['phpui']['ticketlist_pagelimit']);
$start = ($page - 1) * $pagelimit;

$_SESSION['rtp'] = $page;

$SMARTY->assign('queue', $queue);
$SMARTY->assign('queuedata', $queuedata);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('page',$page);
$SMARTY->assign('start',$start);
$SMARTY->display('rtqueueview.html');

?>
