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

if (isset($_GET['ajax'])) {
    $stats = $LMS->GetIndicatorStats();
    header('Content-Type: application/json');
    die(json_encode($stats));
}

$action = $_GET['action'];
$redirect = '';

switch ($action) {
    case 'events':
        if (ConfigHelper::CheckPrivilege('timetable_management')) {
            $count = $LMS->GetEventList(array('userid' => Auth::GetCurrentUser(),
                'forward' => 1, 'closed' => 0, 'count' => true));
            if ($count == 1) {
                $events = $LMS->GetEventList(array('userid' => Auth::GetCurrentUser(),
                    'forward' => 1, 'closed' => 0, 'count' => false));
                $event = reset($events);
                $redirect = '?m=eventinfo&id=' . $event['id'];
            } else {
                $redirect = '?m=eventlist&u=all&a[]=' . Auth::GetCurrentUser() . '&type=all&closed=all&privacy=all';
            }
        }
        break;
    case 'critical':
        if (ConfigHelper::CheckPrivilege('helpdesk_administration') || ConfigHelper::CheckPrivilege('helpdesk_operation')) {
            $count = $LMS->GetQueueContents(array('count' => true, 'priority' => RT_PRIORITY_CRITICAL,
                'state' => -1, 'rights' => RT_RIGHT_INDICATOR));
            if ($count == 1) {
                $tickets = $LMS->GetQueueContents(array('count' => false, 'priority' => RT_PRIORITY_CRITICAL,
                    'state' => -1, 'rights' => RT_RIGHT_INDICATOR));
                $ticket = reset($tickets);
                $redirect = '?m=rtticketview&id=' . $ticket['id'] . (empty($ticket['firstunread']) ? '' : '#rtmessage-' . $ticket['firstunread']);
            } else {
                $redirect = '?m=rtqueueview&id=all&catid=all&priority=' . RT_PRIORITY_CRITICAL . '&ownerid=-1&s=-1&unread=-1'
                    . '&rights=' . RT_RIGHT_INDICATOR . '&o=lastmodified';
            }
        }
        break;
    case 'urgent':
        if (ConfigHelper::CheckPrivilege('helpdesk_administration') || ConfigHelper::CheckPrivilege('helpdesk_operation')) {
            $count = $LMS->GetQueueContents(array('count' => true, 'priority' => RT_PRIORITY_URGENT,
                'state' => -1, 'rights' => RT_RIGHT_INDICATOR));
            if ($count == 1) {
                $tickets = $LMS->GetQueueContents(array('count' => false, 'priority' => RT_PRIORITY_URGENT,
                    'state' => -1, 'rights' => RT_RIGHT_INDICATOR));
                $ticket = reset($tickets);
                $redirect = '?m=rtticketview&id=' . $ticket['id'] . (empty($ticket['firstunread']) ? '' : '#rtmessage-' . $ticket['firstunread']);
            } else {
                $redirect = '?m=rtqueueview&id=all&catid=all&priority=' . RT_PRIORITY_URGENT . '&ownerid=-1&s=-1&unread=-1'
                    . '&rights=' . RT_RIGHT_INDICATOR . '&o=lastmodified';
            }
        }
        break;
    case 'unread':
        if (ConfigHelper::CheckPrivilege('helpdesk_administration') || ConfigHelper::CheckPrivilege('helpdesk_operation')) {
            $count = $LMS->GetQueueContents(array('count' => true, 'state' => -1, 'unread' => 1,
                'rights' => RT_RIGHT_INDICATOR));
            if ($count == 1) {
                $tickets = $LMS->GetQueueContents(array('count' => false, 'state' => -1, 'unread' => 1,
                    'rights' => RT_RIGHT_INDICATOR));
                $ticket = reset($tickets);
                $redirect = '?m=rtticketview&id=' . $ticket['id'] . (empty($ticket['firstunread']) ? '' : '#rtmessage-' . $ticket['firstunread']);
            } else {
                $redirect = '?m=rtqueueview&id=all&catid=all&priority=all&ownerid=-1&s=-1&unread=1'
                    . '&rights=' . RT_RIGHT_INDICATOR . '&o=lastmodified';
            }
        }
        break;
    case 'expired':
        if (ConfigHelper::CheckPrivilege('helpdesk_administration') || ConfigHelper::CheckPrivilege('helpdesk_operation')) {
                $count = $LMS->GetQueueContents(array('count' => true, 'state' => -1, 'deadline' => -2, 'owner' => Auth::GetCurrentUser(), 'rights' => RT_RIGHT_INDICATOR));
            if ($count == 1) {
                $tickets = $LMS->GetQueueContents(array('count' => false, 'state' => -1, 'deadline' => -2, 'owner' => Auth::GetCurrentUser(), 'rights' => RT_RIGHT_INDICATOR));
                $ticket = reset($tickets);
                $redirect = '?m=rtticketview&id=' . $ticket['id'];
            } else {
                $redirect = '?m=rtqueueview&id=all&catid=all&priority=all&deadline=-2&owner=' . Auth::GetCurrentUser() . '&rights=' . RT_RIGHT_INDICATOR;
            }
        }
        break;
    case 'expired2':
        if (ConfigHelper::CheckPrivilege('helpdesk_administration') || ConfigHelper::CheckPrivilege('helpdesk_operation')) {
            $count = $LMS->GetQueueContents(array('count' => true, 'state' => -1, 'deadline' => -2, 'verifier' => Auth::GetCurrentUser(), 'rights' => RT_RIGHT_INDICATOR));
            if ($count == 1) {
                $tickets = $LMS->GetQueueContents(array('count' => false, 'state' => -1, 'deadline' => -2, 'verifier' => Auth::GetCurrentUser(), 'rights' => RT_RIGHT_INDICATOR));
                $ticket = reset($tickets);
                $redirect = '?m=rtticketview&id=' . $ticket['id'];
            } else {
                $redirect = '?m=rtqueueview&id=all&catid=all&priority=all&deadline=-2&owner=' . Auth::GetCurrentUser() . '&rights=' . RT_RIGHT_INDICATOR;
            }
        }
        break;
}

if (!empty($redirect)) {
    $SESSION->redirect($redirect);
}
