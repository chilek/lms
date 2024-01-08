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
$currentuser = Auth::GetCurrentUser();
$helpdesk_adm = ConfigHelper::CheckPrivilege('helpdesk_administration');
$helpdesk_oper = ConfigHelper::CheckPrivilege('helpdesk_operation');
$timetable_mgnt = ConfigHelper::CheckPrivilege('timetable_management');

switch ($action) {
    case 'critical':
        if ($helpdesk_adm || $helpdesk_oper) {
            $count = $LMS->GetQueueContents(array('count' => true, 'priority' => RT_PRIORITY_CRITICAL,
                'state' => -1, 'rights' => RT_RIGHT_INDICATOR));
            if ($count == 1) {
                $tickets = $LMS->GetQueueContents(array('count' => false, 'priority' => RT_PRIORITY_CRITICAL,
                    'state' => -1, 'rights' => RT_RIGHT_INDICATOR));
                $ticket = reset($tickets);
                $redirect = '?m=rtticketview&id=' . $ticket['id'] . (empty($ticket['firstunread']) ? '' : '#rtmessage-' . $ticket['firstunread']);
            } else {
                $redirect = '?m=rtqueueview&persistent-filter=-1&priority=' . RT_PRIORITY_CRITICAL .'&rights=' . RT_RIGHT_INDICATOR;
            }
        }
        break;
    case 'urgent':
        if ($helpdesk_adm || $helpdesk_oper) {
            $count = $LMS->GetQueueContents(array('count' => true, 'priority' => RT_PRIORITY_URGENT,
                'state' => -3, 'rights' => RT_RIGHT_INDICATOR));
            if ($count == 1) {
                $tickets = $LMS->GetQueueContents(array('count' => false, 'priority' => RT_PRIORITY_URGENT,
                    'state' => -3, 'rights' => RT_RIGHT_INDICATOR));
                $ticket = reset($tickets);
                $redirect = '?m=rtticketview&id=' . $ticket['id'] . (empty($ticket['firstunread']) ? '' : '#rtmessage-' . $ticket['firstunread']);
            } else {
                $redirect = '?m=rtqueueview&persistent-filter=-1&priority=' . RT_PRIORITY_URGENT . '&rights=' . RT_RIGHT_INDICATOR;
            }
        }
        break;
    case 'unread':
        if ($helpdesk_adm || $helpdesk_oper) {
            $count = $LMS->GetQueueContents(array('count' => true, 'state' => -1, 'unread' => 1,
                'rights' => RT_RIGHT_INDICATOR));
            if ($count == 1) {
                $tickets = $LMS->GetQueueContents(array('count' => false, 'state' => -1, 'unread' => 1,
                    'rights' => RT_RIGHT_INDICATOR));
                $ticket = reset($tickets);
                $redirect = '?m=rtticketview&id=' . $ticket['id'] . (empty($ticket['firstunread']) ? '' : '#rtmessage-' . $ticket['firstunread']);
            } else {
                $redirect = '?m=rtqueueview&persistent-filter=-1&s=-1&unread=1&rights=' . RT_RIGHT_INDICATOR;
            }
        }
        break;
    case 'expired':
        if ($helpdesk_adm || $helpdesk_oper) {
                $count = $LMS->GetQueueContents(array('count' => true, 'state' => -1, 'deadline' => -2, 'owner' => $currentuser, 'rights' => RT_RIGHT_INDICATOR));
            if ($count == 1) {
                $tickets = $LMS->GetQueueContents(array('count' => false, 'state' => -1, 'deadline' => -2, 'owner' => $currentuser, 'rights' => RT_RIGHT_INDICATOR));
                $ticket = reset($tickets);
                $redirect = '?m=rtticketview&id=' . $ticket['id'];
            } else {
                $redirect = '?m=rtqueueview&persistent-filter=-1&d=-2&owner=' . $currentuser . '&rights=' . RT_RIGHT_INDICATOR;
            }
        }
        break;
    case 'outdated':
        if ($helpdesk_adm || $helpdesk_oper) {
            $count = $LMS->GetQueueContents(array('count' => true, 'state' => RT_EXPIRED, 'owner' => $currentuser, 'rights' => RT_RIGHT_INDICATOR));
            if ($count == 1) {
                $tickets = $LMS->GetQueueContents(array('count' => false, 'state' => RT_EXPIRED, 'owner' => $currentuser, 'rights' => RT_RIGHT_INDICATOR));
                $ticket = reset($tickets);
                $redirect = '?m=rtticketview&id=' . $ticket['id'];
            } else {
                $redirect = '?m=rtqueueview&persistent-filter=-1&s[]=' . RT_EXPIRED . '&owner=' . $currentuser . '&rights=' . RT_RIGHT_INDICATOR;
            }
        }
        break;
    case 'verify':
        if ($helpdesk_adm || $helpdesk_oper) {
            $count = $LMS->GetQueueContents(array('count' => true, 'state' => RT_VERIFIED, 'verifierids' => $currentuser, 'rights' => RT_RIGHT_INDICATOR));
            if ($count == 1) {
                $tickets = $LMS->GetQueueContents(array('count' => false, 'state' => RT_VERIFIED, 'verifierids' => $currentuser, 'rights' => RT_RIGHT_INDICATOR));
                $ticket = reset($tickets);
                $redirect = '?m=rtticketview&id=' . $ticket['id'];
            } else {
                $redirect = '?m=rtqueueview&persistent-filter=-1&s=' . RT_VERIFIED . '&vids=' . $currentuser . '&rights=' . RT_RIGHT_INDICATOR;
            }
        }
        break;
    case 'left':
        if ($helpdesk_adm || $helpdesk_oper) {
            $count = $LMS->GetQueueContents(array('count' => true, 'state' => -1, 'owner' => -3, 'rights' => RT_RIGHT_INDICATOR));
            if ($count == 1) {
                $tickets = $LMS->GetQueueContents(array('count' => false, 'state' => -1, 'owner' => $currentuser, 'rights' => RT_RIGHT_INDICATOR));
                $ticket = reset($tickets);
                $redirect = '?m=rtticketview&id=' . $ticket['id'];
            } else {
                $redirect = '?m=rtqueueview&persistent-filter=-1&owner=' . $currentuser . '&rights=' . RT_RIGHT_INDICATOR;
            }
        }
        break;
    case 'events':
        if ($timetable_mgnt) {
            $count = $LMS->GetEventList(array('userid' => $currentuser,
                'forward' => 1, 'closed' => 0, 'count' => true));
            if ($count == 1) {
                $events = $LMS->GetEventList(array('userid' => $currentuser,
                    'forward' => 1, 'closed' => 0, 'count' => false));
                $event = reset($events);
                $redirect = '?m=eventinfo&id=' . $event['id'];
            } else {
                $redirect = '?m=eventlist&persistent-filter=-1&a[]=' . $currentuser;
            }
        }
        break;
    case 'overdue':
        if ($timetable_mgnt) {
            $count = $LMS->GetEventList(array('userid' => $currentuser,
                'forward' => -1, 'closed' => 0, 'count' => true));
            if ($count == 1) {
                $events = $LMS->GetEventList(array('userid' => $currentuser,
                    'forward' => -1, 'closed' => 0, 'count' => false));
                $event = reset($events);
                $redirect = '?m=eventinfo&id=' . $event['id'];
            } else {
                $redirect = '?m=eventlist&persistent-filter=-1&force_overdue_events=1&a[]=' . $currentuser;
            }
        }
        break;
    case 'watching':
        if ($helpdesk_adm || $helpdesk_oper) {
            $count = $LMS->GetQueueContents(array('watching' => 1, 'count' => true, 'state' => -2));
            if ($count == 1) {
                $ticket = $LMS->GetQueueContents(array('watching' => 1, 'count' => false, 'state' => -2));
                $ticket = reset($ticket);
                $redirect = '?m=rtticketview&id=' . $ticket['id'];
            } else {
                $redirect = '?m=rtqueueview&persistent-filter=-1&watching=1';
            }
        }
        break;
}

if (!empty($redirect)) {
    $SESSION->redirect($redirect);
}
