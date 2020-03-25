<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

if (! $LMS->TicketExists($_GET['id'])) {
    $SESSION->redirect('?m=rtqueuelist');
} else {
    $id = $_GET['id'];
}

if (!$LMS->CheckTicketAccess($id)) {
    access_denied();
}

$ticket = $LMS->GetTicketContents($id);

$ticket['childtickets'] = $LMS->GetChildTickets($id);

if (!empty($ticket['relatedtickets'])) {
    foreach ($ticket['relatedtickets'] as $rticket) {
        if ($LMS->CheckTicketAccess($rticket['id'])) {
            $relatedticketscontent[] = $LMS->GetTicketContents($rticket['id']);
        }
    }
}


if (!empty($ticket['parentid'])) {
    $parentticket = true;
    if ($LMS->CheckTicketAccess($ticket['parentid'])) {
        $parentticketcontent[] = $LMS->GetTicketContents($ticket['parentid']);
    }
}

$categories = $LMS->GetUserCategories(Auth::GetCurrentUser());
if (empty($categories)) {
    $categories = array();
}

if ($ticket['deluserid']) {
    $ticket['delusername'] = $LMS->GetUserName($ticket['deluserid']);
}

if ($ticket['customerid'] && ConfigHelper::checkConfig('phpui.helpdesk_stats')) {
    $yearago = mktime(0, 0, 0, date('n'), date('j'), date('Y')-1);
    //$del = 0;
    $stats = $DB->GetAllByKey('SELECT COUNT(*) AS num, cause FROM rttickets
				WHERE 1=1'
                . (!ConfigHelper::checkPrivilege('helpdesk_advanced_operations') ? ' AND rttickets.deleted = 0' : '')
                . ' AND customerid = ? AND createtime >= ?'
                . ' GROUP BY cause', 'cause', array($ticket['customerid'], $yearago));

    $SMARTY->assign('stats', $stats);
}

if ($ticket['customerid'] && ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')) {
    $customer = $LMS->GetCustomer($ticket['customerid'], true);
    $customer['groups'] = $LMS->CustomergroupGetForCustomer($ticket['customerid']);

    if (!empty($customer['contacts'])) {
        $customer['phone'] = $customer['contacts'][0]['phone'];
    }

    $customernodes = $LMS->GetCustomerNodes($ticket['customerid']);
    $allnodegroups = $LMS->GetNodeGroupNames();

    $SMARTY->assign('customerinfo', $customer);
    $SMARTY->assign('customernodes', $customernodes);
    $SMARTY->assign('allnodegroups', $allnodegroups);
}

$iteration = $LMS->GetQueueContents(array('ids' => $ticket['queueid'], 'order' => 'createtime,desc',
    'state' => -1, 'priority' => null, 'owner' => -1, 'catids' => null));

if (!empty($iteration['total'])) {
    foreach ($iteration as $idx => $element) {
        if (isset($element['id']) && intval($element['id']) == intval($_GET['id'])) {
            $next_ticketid = isset($iteration[$idx + 1]) ? $iteration[$idx + 1]['id'] : 0;
            $prev_ticketid = isset($iteration[$idx - 1]) ? $iteration[$idx - 1]['id'] : 0;
            break;
        }
    }
    $ticket['next_ticketid'] = $next_ticketid;
    $ticket['prev_ticketid'] = $prev_ticketid;
}

foreach ($categories as $category) {
    $category['checked'] = isset($ticket['categories'][$category['id']]);
    $ncategories[] = $category;
}
$categories = $ncategories;
$assignedevents = $LMS->GetEventsByTicketId($id);

$LMS->MarkTicketAsRead($id);

$layout['pagetitle'] = trans('Ticket Review: $a', sprintf("%06d", $ticket['ticketid']));

$SESSION->save('backto', $_SERVER['QUERY_STRING']);


if (isset($_GET['highlight'])) {
    $highlight = $_GET['highlight'];
    if (isset($highlight['regexp'])) {
        foreach ($ticket['messages'] as &$message) {
            $message['body'] = preg_replace(
                '/(' . $highlight['pattern'] . ')/i',
                '[matched-text]$1[/matched-text]',
                $message['body']
            );
        }
    } else {
        foreach ($ticket['messages'] as &$message) {
            $offset = 0;
            while (($pos = mb_stripos($message['body'], $highlight['pattern'], $offset)) !== false) {
                $message['body'] = mb_substr($message['body'], 0, $pos)
                . '[matched-text]' . mb_substr($message['body'], $pos, mb_strlen($highlight['pattern']))
                . '[/matched-text]' . mb_substr($message['body'], $pos + mb_strlen($highlight['pattern']));
                $offset = $pos + strlen('[matched-text][/matched-text]') + mb_strlen($highlight['pattern']) + 1;
            }
        }
    }
        unset($message);
}

$SMARTY->assign('ticket', $ticket);
$SMARTY->assign('relatedticketscontent', $relatedticketscontent);
$SMARTY->assign('parentticketcontent', $parentticketcontent);

$SMARTY->assign('categories', $categories);
$SMARTY->assign('assignedevents', $assignedevents);
$SMARTY->display('rt/rtticketview.html');
