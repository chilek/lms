<?php

/*
 * LMS version 1.5-cvs
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

$layout['pagetitle'] = 'Wyszukiwanie zg³oszeñ';

$search = $_POST['search'];

if(isset($_GET['state']))
{
	$search = array(
		'state' => $_GET['state'],
		'subject' => '',
		'userid' => '0',
		'name' => '',
		'email' => '',
		'owner' => '0',
		'queue' => '0',
		'uptime' => ''
		);
}

if(!isset($_GET['o']))
	$o = $_SESSION['rto'];
else
	$o = $_GET['o'];
$_SESSION['rto'] = $o;

if (isset($_SESSION['rtp']) && !isset($_GET['page']) && !isset($search))
	$_GET['page'] = $_SESSION['rtp'];

$page = (! $_GET['page'] ? 1 : $_GET['page']); 
$pagelimit = (! $LMS->CONFIG['phpui']['ticketlist_pagelimit'] ? $queuedata['total'] : $LMS->CONFIG['phpui']['ticketlist_pagelimit']);
$start = ($page - 1) * $pagelimit;

$_SESSION['rtp'] = $page;

if(isset($search) || $_GET['search'])
{
	if($search['queue'] && !$LMS->GetAdminRightsRT($SESSION->id, $search['queue']))
		$error['queue'] = 'Nie posiadasz uprawnieñ do przegl±dania tej kolejki!';
	
	$search = $search ? $search : $_SESSION['rtsearch'];
	
	if(!$error)
	{
		$queue = $LMS->RTSearch($search, $o);
		
		$_SESSION['rtsearch'] = $search;
		
		$queuedata['total'] = $queue['total'];
		$queuedata['order'] = $queue['order'];		
		$queuedata['direction'] = $queue['direction'];		
		$queuedata['queue'] = $search['queue'];
		unset($queue['total']);
		unset($queue['order']);		
		unset($queue['direction']);
		
		$SMARTY->assign('queue', $queue);
		$SMARTY->assign('queuedata', $queuedata);
		$SMARTY->assign('pagelimit',$pagelimit);
		$SMARTY->assign('page',$page);
		$SMARTY->assign('start',$start);
		$SMARTY->display('rtsearchresults.html');
		die;
	}
}

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$SMARTY->assign('queuelist', $LMS->GetQueueNames());
$SMARTY->assign('adminlist', $LMS->GetAdminNames());
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->assign('search', $_SESSION['rtsearch']);
$SMARTY->assign('error', $error);
$SMARTY->display('rtsearch.html');

?>
