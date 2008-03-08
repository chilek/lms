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

function RTSearch($search, $order='createtime,desc')
{
	global $DB;

	if(!$order)
		$order = 'createtime,desc';
	
	$o = explode(',',$order);
	$order = $o[0];

	(isset($o[1]) && $o[1] == 'desc') ? $direction = 'desc' : $direction = 'asc';

	switch($order)
	{
		case 'ticketid':
			$sqlord = ' ORDER BY t.id';
		break;
		case 'subject':
			$sqlord = ' ORDER BY t.subject';
		break;
		case 'requestor':
			$sqlord = ' ORDER BY requestor';
		break;
		case 'owner':
			$sqlord = ' ORDER BY ownername';
		break;
		case 'lastmodified':
			$sqlord = ' ORDER BY lastmodified';
		break;
		default:
			$sqlord = ' ORDER BY t.createtime';
		break;
	}

	$op = !empty($search['operator']) && $search['operator'] == 'OR' ? $op = ' OR ' : $op = ' AND ';

	if(!empty($search['owner']))
		$where[] = 'owner = '.$search['owner'];
	if(!empty($search['customerid']))
		$where[] = 't.customerid = '.$search['customerid'];
	if(!empty($search['subject']))
		$where[] = 't.subject ?LIKE?\'%'.$search['subject'].'%\'';
	if(!empty($search['state']))
		$where[] = 'state = '.$search['state'];
	if(!empty($search['email']))
		$where[] = 'requestor ?LIKE? \'%'.$search['email'].'%\'';
	if(!empty($search['uptime']))
		$where[] = '(resolvetime-t.createtime > '.$search['uptime'].' OR ('.time().'-t.createtime > '.$search['uptime'].' AND resolvetime = 0) )';
	if(!empty($search['name']))
		$where[] = '(UPPER(requestor) ?LIKE? UPPER(\'%'.$search['name'].'%\') OR '.$DB->Concat('UPPER(customers.lastname)',"' '",'UPPER(customers.name)').' ?LIKE? UPPER(\'%'.$search['name'].'%\')) ';
	if(isset($search['queue']) && is_array($search['queue']))
		$where[] = 'queueid IN ('.implode(',',$search['queue']).') ';
	elseif(!empty($search['queue']))
		$where[] = 'queueid = '.$search['queue'].' ';
	
	if(isset($where))
		$where = ' WHERE '.implode($op, $where);

	if($result = $DB->GetAll('SELECT t.id, t.customerid, t.subject, t.state, t.owner AS ownerid, 
			users.name AS ownername, CASE WHEN customerid = 0 THEN t.requestor ELSE '
			.$DB->Concat('UPPER(customers.lastname)',"' '",'customers.name').'
			END AS requestor, t.requestor AS req, 
			t.createtime, (SELECT MAX(createtime) FROM rtmessages 
				WHERE t.id = ticketid) AS lastmodified 
			FROM rttickets t
			LEFT JOIN users ON (t.owner = users.id) 
			LEFT JOIN customers ON (t.customerid = customers.id)'
			.(isset($where) ? $where : '') 
			.($sqlord !='' ? $sqlord.' '.$direction:'')))
	{
		foreach($result as $idx => $ticket)
		{
			if(!$ticket['customerid'])
				list($ticket['requestor'], $ticket['requestoremail']) = sscanf($ticket['req'], "%[^<]<%[^>]");
			else
				list($ticket['requestoremail']) = sscanf($ticket['req'], "<%[^>]");
			
			$result[$idx] = $ticket;
		}
	}

	$result['total'] = sizeof($result);	
	$result['order'] = $order;
	$result['direction'] = $direction;
		
	return $result;
}

$layout['pagetitle'] = trans('Ticket Search');

if(isset($_POST['search']))
	$search = $_POST['search'];
elseif(isset($_GET['s']))
        $SESSION->restore('rtsearch', $search);

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

if(isset($search) || isset($_GET['s']))
{
	if(!isset($search['queue']) || $search['queue'] == 0)
	{
		// if user hasn't got rights for all queues...
		$queues = $DB->GetCol('SELECT queueid FROM rtrights WHERE userid=?', array($AUTH->id));
		if(sizeof($queues) != $DB->GetOne('SELECT COUNT(*) FROM rtqueues'))
			$search['queue'] = $queues;
	}
	elseif(!$LMS->GetUserRightsRT($AUTH->id, $search['queue']))
		$error['queue'] = trans('You have no privileges to review this queue!');

	if(!$error)
	{
		$queue = RTSearch($search, $o);
		
		$queuedata['total'] = $queue['total'];
		$queuedata['order'] = $queue['order'];		
		$queuedata['direction'] = $queue['direction'];		
		$queuedata['queue'] = isset($search['queue']) ? $search['queue'] : 0;
		
		unset($queue['total']);
		unset($queue['order']);		
		unset($queue['direction']);

		$page = (! isset($_GET['page']) ? 1 : $_GET['page']); 
		$pagelimit = (! $CONFIG['phpui']['ticketlist_pagelimit'] ? $queuedata['total'] : $CONFIG['phpui']['ticketlist_pagelimit']);
		$start = ($page - 1) * $pagelimit;

		$SESSION->save('rtp', $page);
		$SESSION->save('rtsearch', $search);

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
$SMARTY->assign('search', isset($search) ? $search : NULL);
$SMARTY->assign('error', $error);
$SMARTY->display('rtsearch.html');

?>
