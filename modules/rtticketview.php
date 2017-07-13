<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

if(! $LMS->TicketExists($_GET['id']))
{
	$SESSION->redirect('?m=rtqueuelist');
}
else
{
	$id = $_GET['id'];
}

$rights = $LMS->GetUserRightsRT($AUTH->id, 0, $id);
$catrights = $LMS->GetUserRightsToCategory($AUTH->id, 0, $id);

if(!$rights || !$catrights)
{
	$SMARTY->display('noaccess.html');
	$SESSION->close();
	die;
}

$ticket = $LMS->GetTicketContents($id);
$categories = $LMS->GetCategoryListByUser($AUTH->id);
if (empty($categories))
	$categories = array();

if($ticket['deluserid'])
	$ticket['delusername'] = $LMS->GetUserName($ticket['deluserid']);

if ($ticket['customerid'] && ConfigHelper::checkConfig('phpui.helpdesk_stats')) {
	$yearago = mktime(0, 0, 0, date('n'), date('j'), date('Y')-1);
	//$del = 0;
	$stats = $DB->GetAllByKey('SELECT COUNT(*) AS num, cause FROM rttickets 
			    WHERE 1=1 '
				. (!ConfigHelper::checkPrivilege('helpdesk_advanced_operations') ? 'AND rttickets.deleted = 0' : '')
				. ('AND customerid = ? AND createtime >= ?')
			    . ('GROUP BY cause'), 'cause', array($ticket['customerid'], $yearago));

	$SMARTY->assign('stats', $stats);
}

if ($ticket['customerid'] && ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')) {
	$customer = $LMS->GetCustomer($ticket['customerid'], true);
	$customer['groups'] = $LMS->CustomergroupGetForCustomer($ticket['customerid']);

	if(!empty($customer['contacts'])) $customer['phone'] = $customer['contacts'][0]['phone'];

	$customernodes = $LMS->GetCustomerNodes($ticket['customerid']);
	$allnodegroups = $LMS->GetNodeGroupNames();

	$SMARTY->assign('customerinfo', $customer);
	$SMARTY->assign('customernodes', $customernodes);
	$SMARTY->assign('allnodegroups', $allnodegroups);
}

foreach($categories as $category)
	$catids[] = $category['id'];
$iteration = $LMS->GetQueueContents($ticket['queueid'], $order='createtime,desc', $state=-1, 0, $catids);
if (!empty($iteration['total'])) {
	foreach ($iteration as $idx => $element)
		if (isset($element['id']) && intval($element['id']) == intval($_GET['id'])) {
			$next_ticketid = isset($iteration[$idx + 1]) ? $iteration[$idx + 1]['id'] : 0;
			$prev_ticketid = isset($iteration[$idx - 1]) ? $iteration[$idx - 1]['id'] : 0;
			break;
		}
	$ticket['next_ticketid'] = $next_ticketid;
	$ticket['prev_ticketid'] = $prev_ticketid;
}

foreach ($categories as $category)
{
	$category['checked'] = isset($ticket['categories'][$category['id']]);
	$ncategories[] = $category;
}
$categories = $ncategories;
$assignedevents = $LMS->GetEventsByTicketId($id);

$layout['pagetitle'] = trans('Ticket Review: $a',sprintf("%06d", $ticket['ticketid']));

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('ticket', $ticket);
$SMARTY->assign('categories', $categories);
$SMARTY->assign('assignedevents', $assignedevents);
$SMARTY->display('rt/rtticketview.html');

?>
