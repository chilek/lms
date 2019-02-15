<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

function RTSearch($search, $order='createtime,desc') {
	$DB = LMSDB::getInstance();

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
		case 'priority':
			$sqlord = ' ORDER BY priority';
			break;
                case 'service':
                        $sqlord = ' ORDER BY t.service';
                        break;
                case 'type':
                        $sqlord = ' ORDER BY t.type';
                        break;
		default:
			$sqlord = ' ORDER BY t.createtime';
		break;
	}

	$join = array();

	$op = !empty($search['operator']) && $search['operator'] == 'OR' ? $op = ' OR ' : $op = ' AND ';

	if(!empty($search['owner']))
		$where[] = 'owner = '.intval($search['owner']);
	if(!empty($search['custid']))
		$where[] = 't.customerid = '.intval($search['custid']);
	if(!empty($search['subject']))
		$where[] = 't.subject ?LIKE? '.$DB->Escape('%'.$search['subject'].'%');
	if (isset($search['body']) && !empty($search['body']['pattern'])) {
		$join[] = ($op == ' OR ' ? 'LEFT ' : '') . 'JOIN (SELECT ticketid,
			MIN(id) AS messageid FROM rtmessages WHERE type <= ' . RTMESSAGE_NOTE
			. ' AND ' . (isset($search['body']['regexp']) ? $DB->RegExp('body', $search['body']['pattern'])
				: 'body ?LIKE? ' . $DB->Escape('%' . $search['body']['pattern'] . '%')) . '
			GROUP BY ticketid) m3 ON m3.ticketid = t.id';
	} else {
		$join[] = 'JOIN (SELECT DISTINCT ticketid, 0 AS messageid FROM rtmessages) m3 ON m3.ticketid = t.id';
	}
	if (isset($search['state']) && strlen($search['state']))
	{
		if($search['state'] == '-1')
			$where[] = 't.state != '.RT_RESOLVED;
		else
			$where[] = 't.state = '.intval($search['state']);
	}
	if(!empty($search['priority']))
		$where[] = 'priority = '.intval($search['priority']);
	if(!empty($search['email']))
		$where[] = 'requestor ?LIKE? '.$DB->Escape('%'.$search['email'].'%');
	if(!empty($search['uptime']))
		$where[] = '(resolvetime-t.createtime > '.intval($search['uptime'])
			.' OR ('.time().'-t.createtime > '.intval($search['uptime']).' AND resolvetime = 0))';
	if(!empty($search['name']))
		$where[] = '(UPPER(requestor) ?LIKE? UPPER('.$DB->Escape('%'.$search['name'].'%').') OR '
			.$DB->Concat('UPPER(customers.lastname)',"' '",'UPPER(customers.name)').' ?LIKE? UPPER('.$DB->Escape('%'.$search['name'].'%').'))';
	if (isset($search['queue'])) {
		if (is_array($search['queue']))
			$where_queue = '(queueid IN (' . implode(',', $search['queue']) . ')';
		elseif (empty($search['queue']))
			return null;
		else
			$where_queue = '(queueid = '.intval($search['queue']);
		$user_permission_checks = ConfigHelper::checkConfig('phpui.helpdesk_additional_user_permission_checks');
		$userid = Auth::GetCurrentUser();
		$where[] = $where_queue . ($user_permission_checks ? ' OR t.owner = ' . $userid . ' OR t.verifierid = ' . $userid : '') . ')';
	}
	if(isset($search['catids']))
		$where[] = 'tc.categoryid IN ('.implode(',', $search['catids']).')';

		if(!ConfigHelper::checkPrivilege('helpdesk_advanced_operations'))
		$where[] = 't.deleted = 0';
	else
	{
		if(!empty($search['removed']))
		{
			if($search['removed'] == '-1')
				$where[] = 't.deleted = 0';
				else
					$where[] = 't.deleted = 1';
		}
	}

	if(!empty($search['netnodeid']))
		$where[] = 't.netnodeid = '.intval($search['netnodeid']);

	if(!empty($search['netdevid']))
		$where[] = 't.netdevid = '.intval($search['netdevid']);

	if(!empty($search['verifierid']))
		$where[] = 't.verifierid = '.intval($search['verifierid']);

	if(!empty($search['expired']))
		$where[] = 't.deadline < ?NOW?';

        if(!empty($search['service']))
                $where[] = 't.service = '.intval($search['service']);

        if(!empty($search['type']))
                $where[] = 't.type = '.intval($search['type']);

	if (!empty($search['address']) || !empty($search['zip']) || !empty($search['city'])) {
		$join[] = 'JOIN vaddresses va ON va.id = t.address_id';
		$where[] = '('
			. (empty($search['address']) ? '1=1' : 'UPPER(va.address) ?LIKE? UPPER(' . $DB->Escape('%' . $search['address'] . '%') . ')')
			. ' AND '
			. (empty($search['zip']) ? '1=1' : 'UPPER(va.zip) ?LIKE? UPPER(' . $DB->Escape('%' . $search['zip'] . '%') . ')')
			. ' AND '
			. (empty($search['city']) ? '1=1' : 'UPPER(va.city) ?LIKE? UPPER(' . $DB->Escape('%' . $search['city'] . '%') . ')')
			. ')';
	}

	if(isset($where))
		$where = ' WHERE '.implode($op, $where);

	if ($search['count'])
		return $DB->GetOne('SELECT COUNT(DISTINCT t.id)
			FROM rttickets t
			' . implode(' ', $join) . '
			LEFT JOIN (SELECT MAX(createtime) AS lastmodified, ticketid FROM rtmessages GROUP BY ticketid) m ON m.ticketid = t.id
			LEFT JOIN rtticketcategories tc ON t.id = tc.ticketid
			LEFT JOIN rtqueues ON (rtqueues.id = t.queueid)
			LEFT JOIN vusers ON (t.owner = vusers.id)
			LEFT JOIN vusers AS e ON (t.verifierid = vusers.id)
			LEFT JOIN customers ON (t.customerid = customers.id)'
			.(isset($where) ? $where : ''));

	$result = $DB->GetAll('SELECT DISTINCT t.id, t.customerid, t.subject, t.state, t.owner AS ownerid, t.service, t.type,
		vusers.name AS ownername, rtqueues.name as name, CASE WHEN t.customerid IS NULL THEN t.requestor ELSE '
		.$DB->Concat('UPPER(customers.lastname)',"' '",'customers.name').'
		END AS requestor, t.requestor AS req, t.createtime,
		(CASE WHEN m.lastmodified IS NULL THEN 0 ELSE m.lastmodified END) AS lastmodified, t.deleted, t.deltime,
		t.priority, t.verifierid, t.deadline, m3.messageid, COUNT(m2.id) AS delcount
		FROM rttickets t
		LEFT JOIN rtmessages m2 ON m2.ticketid = t.id AND m2.deleted = 1
		' . implode(' ', $join) . '
		LEFT JOIN (SELECT MAX(createtime) AS lastmodified, ticketid FROM rtmessages GROUP BY ticketid) m ON m.ticketid = t.id
		LEFT JOIN rtticketcategories tc ON t.id = tc.ticketid
		LEFT JOIN rtqueues ON (rtqueues.id = t.queueid)
		LEFT JOIN vusers ON (t.owner = vusers.id)
		LEFT JOIN vusers AS e ON (t.verifierid = vusers.id)
		LEFT JOIN customers ON (t.customerid = customers.id)'
		.(isset($where) ? $where : '')
		. ' GROUP BY t.id, t.customerid, t.subject, t.state, t.owner, t.service, t.type, vusers.name, rtqueues.name,
			t.requestor, customers.lastname, customers.name, t.createtime, m.lastmodified, t.deleted, t.deltime, t.priority,
			t.verifierid, t.deadline, m3.messageid '
		. ($sqlord !='' ? $sqlord . ' ' . $direction : '')
		. (isset($search['limit']) ? ' LIMIT ' . $search['limit'] : '')
		. (isset($search['offset']) ? ' OFFSET ' . $search['offset'] : ''));

	if ($result) {
		foreach ($result as &$ticket) {
			if (!$ticket['custid'])
				list ($ticket['requestor'], $ticket['requestoremail']) = sscanf($ticket['req'], "%[^<]<%[^>]");
			else
				list ($ticket['requestoremail']) = sscanf($ticket['req'], "<%[^>]");
		}
		unset($ticket);
	}

	$result['order'] = $order;
	$result['direction'] = $direction;

	return $result;
}

$categories = $LMS->GetUserCategories(Auth::GetCurrentUser());

$layout['pagetitle'] = trans('Ticket Search');

if (isset($_POST['search']))
	$search = $_POST['search'];
elseif (isset($_GET['page']))
	$SESSION->restore('rtsearch', $search);

if (isset($_GET['id']))
	$search['custid'] = $_GET['id'];

if (isset($_GET['state']))
	$search = array(
		'state' => $_GET['state'],
		'subject' => '',
		'body' => array(
			'message' => '',
			'regexp' => false,
		),
		'custid' => '0',
		'address' => '',
		'zip' => '',
		'city' => '',
		'name' => '',
		'email' => '',
		'owner' => '0',
		'queue' => '0',
		'uptime' => '',
		'catids' => NULL
	);

if (!isset($_GET['o']))
	$SESSION->restore('rto', $o);
else
	$o = $_GET['o'];

$SESSION->save('rto', $o);

if ($SESSION->is_set('rtp') && !isset($_GET['page']) && !isset($search))
	$SESSION->restore('rtp', $_GET['page']);

if(isset($search) || isset($_GET['page']))
{
	if(!isset($search['queue']) || $search['queue'] == 0)
	{
		// if user hasn't got rights for all queues...
		$queues = $DB->GetCol('SELECT queueid FROM rtrights WHERE userid=?', array(Auth::GetCurrentUser()));
		if (!count($queues))
			$search['queue'] = 0;
		elseif (count($queues) != $DB->GetOne('SELECT COUNT(*) FROM rtqueues'))
			$search['queue'] = $queues;
	}
	else
		if (is_array($search['queue']))
			foreach($search['queue'] as $queue)
			{
				if(!$LMS->GetUserRightsRT(Auth::GetCurrentUser(), $queue))
					$error['queue'] = trans('You have no privileges to review this queue!');
			}
		else
			if(!$LMS->GetUserRightsRT(Auth::GetCurrentUser(), $search['queue']))
				$error['queue'] = trans('You have no privileges to review this queue!');

	if(!isset($search['categories']))
		$search['catids'] = NULL;
	else
		foreach($search['categories'] as $catid => $val)
			$search['catids'][] = $catid;

	if (!$error) {
		$search['count'] = true;
		$search['total'] = intval(RTSearch($search, $o));

		$search['page'] = intval((! isset($_GET['page']) ? 1 : $_GET['page']));
		$search['limit'] = intval(ConfigHelper::getConfig('phpui.ticketlist_pagelimit', $search['total']));
		$search['offset'] = ($search['page'] - 1) * $search['limit'];

		$search['count'] = false;
		$queue = RTSearch($search, $o);

		$pagination = LMSPaginationFactory::getPagination($search['page'], $search['total'], $search['limit'],
			ConfigHelper::checkConfig('phpui.short_pagescroller'));

		$search['order'] = $queue['order'];
		$search['direction'] = $queue['direction'];
		$search['queue'] = isset($search['queue']) ? $search['queue'] : 0;

		unset($queue['order']);
		unset($queue['direction']);

		$SESSION->save('rtp', $page);
		$SESSION->save('rtsearch', $search);

		$SMARTY->assign('pagination', $pagination);
		$SMARTY->assign('queue', $queue);
		$SMARTY->assign('filter', $search);
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

function netnode_changed($netnodeid, $netdevid) {
	global $LMS, $SMARTY;

	$JSResponse = new xajaxResponse();

	$search = array();
	if (!empty($netnodeid))
		$search['netnode'] = $netnodeid;
	$netdevlist = $LMS->GetNetDevList('name', $search);
	unset($netdevlist['total']);
	unset($netdevlist['order']);
	unset($netdevlist['direction']);

	$SMARTY->assign('netdevlist', $netdevlist);
	$SMARTY->assign('ticket', array('netdevid' => $netdevid));
	$SMARTY->assign('form', 'search');
	$content = $SMARTY->fetch('rt' . DIRECTORY_SEPARATOR . 'rtnetdevs.html');
	$JSResponse->assign('rtnetdevs', 'innerHTML', $content);

	return $JSResponse;

}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction('netnode_changed');
$SMARTY->assign('xajax', $LMS->RunXajax());

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$netnodelist = $LMS->GetNetNodeList(array(),'name');
unset($netnodelist['total']);
unset($netnodelist['order']);
unset($netnodelist['direction']);

$netdevlist = $LMS->GetNetDevList('name', array());
unset($netdevlist['total']);
unset($netdevlist['order']);
unset($netdevlist['direction']);

$SMARTY->assign('queuelist', $LMS->GetQueueList(array('stats' => false)));
$SMARTY->assign('categories', $categories);
$SMARTY->assign('netnodelist', $netnodelist);
$SMARTY->assign('netdevlist', $netdevlist);
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->assign('customerlist', $LMS->GetAllCustomerNames());
$SMARTY->assign('search', isset($search) ? $search : NULL);
$SMARTY->assign('error', $error);
$SMARTY->display('rt/rtsearch.html');

?>
