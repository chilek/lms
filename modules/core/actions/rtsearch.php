<?php

/*
 * LMS version 1.9-cvs
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

function RTSearch($search, $order='createtime,desc')
{
	global $DB;

	if(!$order)
		$order = 'createtime,desc';
	
	list($order,$direction) = explode(',',$order);

	($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

	switch($order)
	{
		case 'ticketid':
			$sqlord = 'ORDER BY rttickets.id';
		break;
		case 'subject':
			$sqlord = 'ORDER BY rttickets.subject';
		break;
		case 'requestor':
			$sqlord = 'ORDER BY requestor';
		break;
		case 'owner':
			$sqlord = 'ORDER BY ownername';
		break;
		case 'lastmodified':
			$sqlord = 'ORDER BY lastmodified';
		break;
		default:
			$sqlord = 'ORDER BY rttickets.createtime';
		break;
	}

	$where  = ($search['queue']     ? 'AND queueid='.$search['queue'].' '          : '');
	$where .= ($search['owner']     ? 'AND owner='.$search['owner'].' '            : '');
	$where .= ($search['customerid']    ? 'AND rttickets.customerid='.$search['customerid'].' '   : '');
	$where .= ($search['subject']   ? 'AND rttickets.subject ?LIKE?\'%'.$search['subject'].'%\' '       : '');
	$where .= ($search['state']!='' ? 'AND state='.$search['state'].' '            : '');
	$where .= ($search['email']!='' ? 'AND requestor ?LIKE? \'%'.$search['email'].'%\' ' : '');
	$where .= ($search['uptime']!='' ? 'AND (resolvetime-rttickets.createtime > '.$search['uptime'].' OR ('.time().'-rttickets.createtime > '.$search['uptime'].' AND resolvetime = 0) ) ' : '');
	
	if($search['name'])
		$where .= 'AND (UPPER(requestor) ?LIKE? UPPER(\'%'.$search['name'].'%\') OR '.$DB->Concat('UPPER(customers.lastname)',"' '",'UPPER(customers.name)').' ?LIKE? UPPER(\'%'.$search['name'].'%\')) ';

	if($result = $DB->GetAll('SELECT rttickets.id AS id, rttickets.customerid AS customerid, requestor, rttickets.subject AS subject, state, owner AS ownerid, users.name AS ownername, '.$DB->Concat('UPPER(customers.lastname)',"' '",'customers.name').' AS customername, rttickets.createtime AS createtime, MAX(rtmessages.createtime) AS lastmodified 
			FROM rttickets 
			LEFT JOIN rtmessages ON (rttickets.id = rtmessages.ticketid)
			LEFT JOIN users ON (owner = users.id) 
			LEFT JOIN customers ON (rttickets.customerid = customers.id)
			WHERE 1=1 '
			.$where 
			.'GROUP BY rttickets.id, requestor, rttickets.createtime, rttickets.subject, state, owner, users.name, rttickets.customerid, customers.lastname, customers.name '
			.($sqlord !='' ? $sqlord.' '.$direction:'')))
	{
		foreach($result as $idx => $ticket)
		{
			if(!$ticket['customerid'])
				list($ticket['requestor'], $ticket['requestoremail']) = sscanf($ticket['requestor'], "%[^<]<%[^>]");
			else
				list($ticket['requestoremail']) = sscanf($ticket['requestor'], "<%[^>]");
			$result[$idx] = $ticket;
			$result['total']++;
		}
	}
		
	$result['order'] = $order;
	$result['direction'] = $direction;
		
	return $result;
}

$layout['pagetitle'] = trans('Ticket Search');

if(isset($_POST['search']))
	$search = $_POST['search'];

if(isset($_GET['id']))
	$search['customerid'] = $_GET['id'];

if(isset($_GET['state']))
{
	$search = array(
		'state' => $_GET['state'],
		'subject' => '',
		'customerid' => '0',
		'name' => '',
		'email' => '',
		'owner' => '0',
		'queue' => '0',
		'uptime' => ''
		);
}

if(!isset($_GET['o']))
	$SESSION->restore('rto', $o);
else
	$o = $_GET['o'];

$SESSION->save('rto', $o);

if ($SESSION->is_set('rtp') && !isset($_GET['page']) && !isset($search))
	$SESSION->restore('rtp', $_GET['page']);

$page = (! $_GET['page'] ? 1 : $_GET['page']); 
$pagelimit = (! $LMS->CONFIG['phpui']['ticketlist_pagelimit'] ? $queuedata['total'] : $LMS->CONFIG['phpui']['ticketlist_pagelimit']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('rtp', $page);

if(isset($search) || $_GET['search'])
{
	if($search['queue'] && !$LMS->GetUserRightsRT($AUTH->id, $search['queue']))
		$error['queue'] = trans('You have no privileges to review this queue!');

	$search = $search ? $search : $SESSION->get('rtsearch');
	
	if(!$error)
	{
		$queue = RTSearch($search, $o);
		
		$SESSION->save('rtsearch', $search);
		
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
		$SMARTY->assign('search', $search);
		$SMARTY->display('rtsearchresults.html');
		$SESSION->close();
		die;
	}
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('queuelist', $LMS->GetQueueNames());
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->assign('customerlist', $LMS->GetAllCustomerNames());
$SMARTY->assign('search', $SESSION->get('rtsearch'));
$SMARTY->assign('error', $error);
$SMARTY->display('rtsearch.html');

?>
