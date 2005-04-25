<?php

/*
 * LMS version 1.5-cvs
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
	global $LMS;

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
	$where .= ($search['userid']    ? 'AND rttickets.userid='.$search['userid'].' '   : '');
	$where .= ($search['subject']   ? 'AND rttickets.subject ?LIKE?\'%'.$search['subject'].'%\' '       : '');
	$where .= ($search['state']!='' ? 'AND state='.$search['state'].' '            : '');
	$where .= ($search['name']!=''  ? 'AND requestor ?LIKE? \'%'.$search['name'].'%\' '  : '');
	$where .= ($search['email']!='' ? 'AND requestor ?LIKE? \'%'.$search['email'].'%\' ' : '');
	$where .= ($search['uptime']!='' ? 'AND (resolvetime-rttickets.createtime > '.$search['uptime'].' OR ('.time().'-rttickets.createtime > '.$search['uptime'].' AND resolvetime = 0) ) ' : '');
	
	if($search['name'] && !$search['userid'])
		$where = 'OR '.$LMS->DB->Concat('users.lastname',"' '",'users.name').' ?LIKE? \'%'.$search['name'].'%\'';

	if($result = $LMS->DB->GetAll('SELECT rttickets.id AS id, rttickets.userid AS userid, requestor, rttickets.subject AS subject, state, owner AS ownerid, admins.name AS ownername, '.$LMS->DB->Concat('UPPER(users.lastname)',"' '",'users.name').' AS username, rttickets.createtime AS createtime, MAX(rtmessages.createtime) AS lastmodified 
			FROM rttickets 
			LEFT JOIN rtmessages ON (rttickets.id = rtmessages.ticketid)
			LEFT JOIN admins ON (owner = admins.id) 
			LEFT JOIN users ON (rttickets.userid = users.id)
			WHERE 1=1 '
			.$where 
			.'GROUP BY rttickets.id, requestor, rttickets.createtime, rttickets.subject, state, owner, admins.name, rttickets.userid, users.lastname, users.name '
			.($sqlord !='' ? $sqlord.' '.$direction:'')))
	{
		foreach($result as $idx => $ticket)
		{
			if(!$ticket['userid'])
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

$search = $_POST['search'];
if(isset($_POST['rtsearch']))
	$search = $_POST['rtsearch'];

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
	if($search['queue'] && !$LMS->GetAdminRightsRT($AUTH->id, $search['queue']))
		$error['queue'] = trans('You have no privilleges to review this queue!');

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
		$SMARTY->display('rtsearchresults.html');
		$SESSION->close();
		die;
	}
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('queuelist', $LMS->GetQueueNames());
$SMARTY->assign('adminlist', $LMS->GetAdminNames());
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->assign('search', $SESSION->get('rtsearch'));
$SMARTY->assign('error', $error);
$SMARTY->display('rtsearch.html');

?>
