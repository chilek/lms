<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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
		$where[] = 'owner = '.intval($search['owner']);
	if(!empty($search['customerid']))
		$where[] = 't.customerid = '.intval($search['customerid']);
	if(!empty($search['subject']))
		$where[] = 't.subject ?LIKE? '.$DB->Escape('%'.$search['subject'].'%');
	if(!empty($search['state']))
	{
		if($search['state'] == '-1')
			$where[] = 'state != '.RT_RESOLVED;
		else
			$where[] = 'state = '.intval($search['state']);
	}
	if(!empty($search['email']))
		$where[] = 'requestor ?LIKE? '.$DB->Escape('%'.$search['email'].'%');
	if(!empty($search['uptime']))
		$where[] = '(resolvetime-t.createtime > '.intval($search['uptime'])
			.' OR ('.time().'-t.createtime > '.intval($search['uptime']).' AND resolvetime = 0))';
	if(!empty($search['name']))
		$where[] = '(UPPER(requestor) ?LIKE? UPPER('.$DB->Escape('%'.$search['name'].'%').') OR '
			.$DB->Concat('UPPER(customers.lastname)',"' '",'UPPER(customers.name)').' ?LIKE? UPPER('.$DB->Escape('%'.$search['name'].'%').'))';
	if(isset($search['queue']) && is_array($search['queue']))
		$where[] = 'queueid IN ('.implode(',', $search['queue']).')';
	elseif(!empty($search['queue']))
		$where[] = 'queueid = '.intval($search['queue']);
	if(isset($search['catids']))
		$where[] = 'tc.categoryid IN ('.implode(',', $search['catids']).')';

	if(isset($where))
		$where = ' WHERE '.implode($op, $where);

	if($result = $DB->GetAll('SELECT DISTINCT t.id, t.customerid, t.subject, t.state, t.owner AS ownerid, 
			users.name AS ownername, CASE WHEN customerid = 0 THEN t.requestor ELSE '
			.$DB->Concat('UPPER(customers.lastname)',"' '",'customers.name').'
			END AS requestor, t.requestor AS req, t.createtime,
			(CASE WHEN m.lastmodified IS NULL THEN (CASE WHEN n.lastmodified IS NULL THEN 0 ELSE n.lastmodified END) ELSE
				(CASE WHEN n.lastmodified IS NULL THEN m.lastmodified ELSE 
					(CASE WHEN m.lastmodified > n.lastmodified THEN m.lastmodified ELSE n.lastmodified END)
				END)
			END) AS lastmodified
			FROM rttickets t
			LEFT JOIN (SELECT MAX(createtime) AS lastmodified, ticketid FROM rtmessages GROUP BY ticketid) m ON m.ticketid = t.id
			LEFT JOIN (SELECT MAX(createtime) AS lastmodified, ticketid FROM rtnotes GROUP BY ticketid) n ON n.ticketid = t.id
			LEFT JOIN rtticketcategories tc ON t.id = tc.ticketid 
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

$categories = $LMS->GetCategoryListByUser($AUTH->id);

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
		'uptime' => '',
		'catids' => NULL
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
	else
		if (is_array($search['queue']))
			foreach($search['queue'] as $queue)
			{
				if(!$LMS->GetUserRightsRT($AUTH->id, $queue))
					$error['queue'] = trans('You have no privileges to review this queue!');
			}
		else
			if(!$LMS->GetUserRightsRT($AUTH->id, $search['queue']))
				$error['queue'] = trans('You have no privileges to review this queue!');

	if(!isset($search['categories']))
		$search['catids'] = NULL;
	else
		foreach($search['categories'] as $catid => $val)
			$search['catids'][] = $catid;

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
		$pagelimit = ConfigHelper::getConfig('phpui.ticketlist_pagelimit', $queuedata['total']);
		$start = ($page - 1) * $pagelimit;

		$SESSION->save('rtp', $page);
		$SESSION->save('rtsearch', $search);

		$SMARTY->assign('queue', $queue);
		$SMARTY->assign('queuedata', $queuedata);
		$SMARTY->assign('pagelimit',$pagelimit);
		$SMARTY->assign('page',$page);
		$SMARTY->assign('start',$start);
		$SMARTY->assign('search', $search);
		$SMARTY->display('rt/rtsearchresults.html');
		$SESSION->close();
		die;
	}
}
else
{
	if (!empty($categories)) foreach($categories as $category)
	{
		$category['checked'] = true;
		$ncategories[] = $category;
	}
	$categories = $ncategories;
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('queuelist', $LMS->GetQueueNames());
$SMARTY->assign('categories', $categories);
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->assign('customerlist', $LMS->GetAllCustomerNames());
$SMARTY->assign('search', isset($search) ? $search : NULL);
$SMARTY->assign('error', $error);
$SMARTY->display('rt/rtsearch.html');

?>
