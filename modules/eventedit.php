<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

$LMS->InitXajax();
include(MODULES_DIR . DIRECTORY_SEPARATOR . 'eventxajax.inc.php');
include(MODULES_DIR . DIRECTORY_SEPARATOR . 'rtticketxajax.inc.php');

$aee = ConfigHelper::getConfig(
    'timetable.allow_modify_closed_events_newer_than',
    ConfigHelper::getConfig('phpui.allow_modify_closed_events_newer_than', 604800)
);
$superuser = ConfigHelper::checkPrivilege('superuser');
$event_overlap_warning = ConfigHelper::checkConfig('timetable.event_overlap_warning', ConfigHelper::checkConfig('phpui.event_overlap_warning'));
$max_userlist_size = ConfigHelper::getConfig('timetable.event_max_userlist_size', ConfigHelper::getConfig('phpui.event_max_userlist_size'));
$big_networks = ConfigHelper::checkConfig('phpui.big_networks');
$now = time();

if (isset($_GET['id'])) {
    $event = $LMS->GetEvent($_GET['id']);
    if (empty($event)) {
        $SESSION->redirect('?m=eventlist');
    }

    if (!empty($event['userlist'])) {
        $event['userlist'] = array_keys($event['userlist']);
    }
    if (!empty($event['ticketid'])) {
        $event['ticket'] = $LMS->getTickets($event['ticketid']);
    }

    if (empty($event['enddate'])) {
        $event['enddate'] = $event['date'];
    }
    $event['begin'] = date('Y/m/d H:i', $event['date'] + $event['begintime']);
    $event['end'] = date('Y/m/d H:i', $event['enddate'] + ($event['endtime'] == 86400 ? 0 : $event['endtime']));
}

$backto = $SESSION->get_history_entry('m=eventlist');
$backid = $SESSION->get('backid');
$backurl = '?' . $backto . (empty($backid) ? '' : '#' . $backid);

$action = $_GET['action'] ?? null;
switch ($action) {
    case 'open':
        if (empty($event['closeddate']) || ($event['closed'] == 1 && $aee && ($now - $event['closeddate'] < $aee)) || $superuser) {
            $DB->Execute('UPDATE events SET closed = 0, closeduserid = NULL, closeddate = 0 WHERE id = ?', array($_GET['id']));
            $SESSION->remove_history_entry();
            $SESSION->redirect($backurl);
        } else {
            die(trans('Cannot open event - event closed too long ago.'));
        }
        break;
    case 'close':
        $SESSION->remove_history_entry();
        if (isset($_GET['ticketid'])) {
            $DB->Execute('UPDATE events SET closed = 1, closeduserid = ?, closeddate = ?NOW? WHERE closed = 0 AND ticketid = ?', array(Auth::GetCurrentUser(), $_GET['ticketid']));
        } else {
            $DB->Execute('UPDATE events SET closed = 1, closeduserid = ?, closeddate = ?NOW? WHERE id = ?', array(Auth::GetCurrentUser(), $_GET['id']));
        }
        $SESSION->redirect($backurl);
        break;
    case 'assign':
        if ($event['closed'] != 1 || ($event['closed'] == 1 && $aee && (($now - $event['closeddate']) < $aee)) || $superuser) {
            $LMS->AssignUserToEvent($_GET['id'], Auth::GetCurrentUser());
            $SESSION->remove_history_entry();
            $SESSION->redirect($backurl);
        } else {
            die("Cannot assign to event - event closed too long ago.");
        }
        break;
    case 'unassign':
        if ($event['closed'] != 1 || ($event['closed'] == 1 && $aee && (($now - $event['closeddate']) < $aee)) || $superuser) {
            $LMS->UnassignUserFromEvent($_GET['id'], Auth::GetCurrentUser());
            $SESSION->remove_history_entry();
            $SESSION->redirect($backurl);
        } else {
            die("Cannot unassign from event - event closed too long ago.");
        }
        break;
}

$params['withDeleted'] = 1;
$userlist = $LMS->GetUserNames($params);

$netdevices = $LMS->GetNetDevList();
unset($netdevices['total'], $netdevices['order'], $netdevices['direction']);

if (isset($_POST['event'])) {
    $event = $_POST['event'];

    if (!isset($event['usergroup'])) {
        $event['usergroup'] = -2;
    }
    //$SESSION->save('eventgid', $event['usergroup']);

    if ($event['title'] == '') {
        $error['title'] = trans('Event title is required!');
    } elseif (strlen($event['title']) > 255) {
        $error['title'] = trans('Event title is too long!');
    }

    $date = 0;
    if ($event['begin'] == '') {
        $error['begin'] = trans('You have to specify event day!');
    } else {
        if (isset($event['wholedays'])) {
            $date = date_to_timestamp($event['begin']);
            if (empty($date)) {
                $error['begin'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
            } else {
                $begintime = 0;
            }
        } else {
            $date = datetime_to_timestamp($event['begin'], $midnight = true);
            if (empty($date)) {
                $error['begin'] = trans('Incorrect date format! Enter date in YYYY/MM/DD HH:MM format!');
            } else {
                $begintime = datetime_to_timestamp($event['begin']) - $date;
            }
        }
    }

    $enddate = 0;
    if ($event['end'] != '') {
        if (isset($event['wholedays'])) {
            $enddate = date_to_timestamp($event['end']);
            if (empty($enddate)) {
                $error['end'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
            } else {
                $endtime = 86400;
            }
        } else {
            $enddate = datetime_to_timestamp($event['end'], $midnight = true);
            if (empty($enddate)) {
                $error['end'] = trans('Incorrect date format! Enter date in YYYY/MM/DD HH:MM format!');
            } else {
                $endtime = datetime_to_timestamp($event['end']) - $enddate;
            }
        }
    } elseif ($date) {
        $enddate = $date;
        if (isset($event['wholedays'])) {
            $endtime = 86400;
        } else {
            $endtime = $begintime;
        }
    }

    if ($enddate && $date > $enddate) {
        $error['end'] = trans('End time must not precede start time!');
    }

    if ($event_overlap_warning
        && !$error && empty($event['overlapwarned']) && ($users = $LMS->EventOverlaps(array(
            'date' => $date,
            'begintime' => $begintime,
            'enddate' => $enddate,
            'endtime' => $endtime,
            'users' => $event['userlist'] ?? array(),
            'ignoredevent' => $event['id'],
        )))) {
        $users_by_id = Utils::array_column($userlist, 'rname', 'id');
        $users = array_map(function ($userid) use ($users_by_id) {
            return $users_by_id[$userid];
        }, $users);
        $error['begin'] = $error['end'] =
            trans(
                'Event is assigned to users which already have assigned an event in the same time: $a!',
                implode(', ', $users)
            );
        $event['overlapwarned'] = 1;
    }

    if (!isset($event['customerid'])) {
        $event['customerid'] = $event['custid'];
    }

    if (isset($event['helpdesk'])) {
        if (empty($event['ticketid'])) {
            $error['ticketid'] = trans('Ticket id should not be empty!');
        } else {
            $event['ticket'] = $LMS->GetTicketContents($event['ticketid'], true);
            if (!empty($event['ticket']['address_id']) && $event['address_id'] > 0) {
                $error['address_id'] = trans('Event location selection is not possible as it is assigned to ticket!');
            }
        }
    }

    $hook_data = $LMS->executeHook(
        'eventedit_validation_before_submit',
        array(
            'event' => $event,
            'error'   => $error,
        )
    );
    $event = $hook_data['event'];
    $error = $hook_data['error'];

    if (!$error) {
        $event['private'] = isset($event['private']) ? 1 : 0;

        $event['address_id'] = !isset($event['address_id']) || $event['address_id'] == -1 ? null : $event['address_id'];
        $event['nodeid'] = empty($event['nodeid']) ? null : $event['nodeid'];

        $event['date'] = $date;
        $event['begintime'] = $begintime;
        $event['enddate'] = $enddate;
        $event['endtime'] = $endtime;
        $event['helpdesk'] = $event['ticketid'] ?: null;
        $event['netnodeid'] = empty($event['netnodeid']) ? null : $event['netnodeid'];
        $event['netdevid'] = empty($event['netdevid']) ? null : $event['netdevid'];

        $LMS->EventUpdate($event);

        $hook_data = $LMS->executeHook(
            'eventedit_after_submit',
            array(
                'event' => $event
            )
        );
        $event = $hook_data['event'];

        $SESSION->redirect($backurl);
    } else {
        if (!empty($event['ticketid'])) {
            $event['ticket'] = $LMS->getTickets($event['ticketid']);
        }
    }
} else {
    $SMARTY->assign('backurl', $backurl);

    $event['overlapwarned'] = 0;
}

$layout['pagetitle'] = trans('Event Edit');

$usergroups = $LMS->UsergroupGetList();
unset($usergroups['total'], $usergroups['totalcount']);

if (isset($event['customerid']) && intval($event['customerid'])) {
    $addresses = $LMS->getCustomerAddresses($event['customerid']);
    $address_id = $LMS->determineDefaultCustomerAddress($addresses);
    if (isset($event['address_id']) && intval($event['address_id']) > 0) {
        $nodes = $LMS->GetNodeLocations($event['customerid'], $event['address_id']);
    } else {
        $nodes = $LMS->GetNodeLocations($event['customerid'], $address_id);
    }
    $SMARTY->assign('addresses', $addresses);
    $SMARTY->assign('nodes', $nodes);
}

if (!isset($event['usergroup'])) {
    $event['usergroup'] = -2;
}
    //$SESSION->restore('eventgid', $event['usergroup']);

$SMARTY->assign(array(
    'xajax' => $LMS->RunXajax(),
    'netdevices' => $netdevices,
    'netnodes' => $LMS->GetNetNodes(),
    'max_userlist_size' => $max_userlist_size,
    'customerlist' => $big_networks ? null : $LMS->GetAllCustomerNames(),
    'userlist' => $userlist,
    'usergroups' => $usergroups,
    'error' => $error,
    'event' => $event));

$SMARTY->display('event/eventmodify.html');
