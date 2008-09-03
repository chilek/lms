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

function GetQueueContents($ids, $order='createtime,desc', $state=NULL, $owner=0)
{
	global $DB;
	
	if(!$order)
		$order = 'createtime,desc';

	list($order,$direction) = sscanf($order, '%[^,],%s');

	($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

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
		case 'creator':
			$sqlord = ' ORDER BY creatorname';
		break;
		default:
			$sqlord = ' ORDER BY t.createtime';
		break;
	}

	switch($state)
	{
		case '0':
		case '1':
		case '2':
		case '3':
			$statefilter = 'AND state = '.$state;
		break;
		case '-1':
			$statefilter = 'AND state != 2';
		break;
		default:
			$statefilter = '';
	}

	if($result = $DB->GetAll(
		    'SELECT t.id, t.customerid, c.address, users.name AS ownername,
			    t.subject, state, owner AS ownerid, t.requestor AS req,
			    CASE WHEN customerid = 0 THEN t.requestor ELSE '
			    .$DB->Concat('UPPER(c.lastname)',"' '",'c.name').' END AS requestor, 
			    t.createtime AS createtime, u.name AS creatorname,
			    (SELECT MAX(createtime) FROM rtmessages WHERE ticketid = t.id) AS lastmodified
		    FROM rttickets t 
		    LEFT JOIN users ON (owner = users.id)
		    LEFT JOIN customers c ON (t.customerid = c.id)
		    LEFT JOIN users u ON (t.creatorid = u.id)
		    WHERE '
		    .(is_array($ids) ? 'queueid IN ('.implode(',', $ids).') ' : ($ids != 0 ? 'queueid = '.$ids.' ' : ''))
		    .$statefilter
		    .($owner ? ' AND t.owner = '.intval($owner) : '')
		    .($sqlord !='' ? $sqlord.' '.$direction:''), array($id)))
	{
		foreach($result as $idx => $ticket)
		{
			//$ticket['requestoremail'] = ereg_replace('^.*<(.*@.*)>$','\1',$ticket['requestor']);
			//$ticket['requestor'] = str_replace(' <'.$ticket['requestoremail'].'>','',$ticket['requestor']);
			if(!$ticket['customerid'])
				list($ticket['requestor'], $ticket['requestoremail']) = sscanf($ticket['req'], "%[^<]<%[^>]");
			else
				list($ticket['requestoremail']) = sscanf($ticket['req'], "<%[^>]");
			$result[$idx] = $ticket;
		}
	}

	$result['total'] = sizeof($result);
	$result['state'] = $state;
	$result['order'] = $order;
	$result['direction'] = $direction;
	$result['owner'] = $owner;

	return $result;
}

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
$queue = GetQueueContents($queuedata['id'], $o, $s, $owner);

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
