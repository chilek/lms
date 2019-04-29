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

$LMS->InitXajax();
include(MODULES_DIR . DIRECTORY_SEPARATOR . 'eventxajax.inc.php');
$SMARTY->assign('xajax', $LMS->RunXajax());

if (isset($_GET['action'])) {
	if ($_GET['action'] == 'open') {
		$DB->Execute('UPDATE events SET closed = 0, closeduserid = NULL, closeddate = 0 WHERE id = ?',array($_GET['id']));
		$SESSION->redirect('?'.$SESSION->get('backto')
			. ($SESSION->is_set('backid') ? '#' . $SESSION->get('backid') : ''));
	} elseif ($_GET['action'] == 'close' && isset($_GET['ticketid'])) {
		$DB->Execute('UPDATE events SET closed = 1, closeduserid = ?, closeddate = ?NOW?  WHERE ticketid = ?',array(Auth::GetCurrentUser(), $_GET['ticketid']));
		$SESSION->redirect('?'.$SESSION->get('backto'));
	} elseif ($_GET['action'] == 'close') {
		$DB->Execute('UPDATE events SET closed = 1, closeduserid = ?, closeddate = ?NOW?  WHERE id = ?',array(Auth::GetCurrentUser(), $_GET['id']));
		$SESSION->redirect('?'.$SESSION->get('backto')
			. ($SESSION->is_set('backid') ? '#' . $SESSION->get('backid') : ''));
	} elseif ($_GET['action'] == 'assign') {
		$LMS->AssignUserToEvent($_GET['id'], Auth::GetCurrentUser());
		$SESSION->redirect('?' . $SESSION->get('backto')
			. ($SESSION->is_set('backid') ? '#' . $SESSION->get('backid') : ''));
	} elseif ($_GET['action'] == 'unassign') {
		$LMS->UnassignUserFromEvent($_GET['id'], Auth::GetCurrentUser());
		$SESSION->redirect('?' . $SESSION->get('backto')
			. ($SESSION->is_set('backid') ? '#' . $SESSION->get('backid') : ''));
	}
}

if (isset($_GET['id'])) {
	$event = $LMS->GetEvent($_GET['id']);
	if (!empty($event['ticketid']))
		$event['ticket'] = $LMS->GetTicketContents($event['ticketid'], true);

	if (empty($event['enddate']))
		$event['enddate'] = $event['date'];
	$event['begin'] = date('Y/m/d H:i', $event['date'] + $event['begintime']);
	$event['end'] = date('Y/m/d H:i', $event['enddate'] + ($event['endtime'] == 86400 ? 0 : $event['endtime']));
}

$userlist = $LMS->GetUserList();
unset($userlist['total']);

if(isset($_POST['event']))
{
	$event = $_POST['event'];

	if (!isset($event['usergroup']))
		$event['usergroup'] = 0;
	//$SESSION->save('eventgid', $event['usergroup']);

	if($event['title'] == '')
		$error['title'] = trans('Event title is required!');
	elseif(strlen($event['title']) > 255)
		$error['title'] = trans('Event title is too long!');

	$date = 0;
	if ($event['begin'] == '')
		$error['begin'] = trans('You have to specify event day!');
	else {
		if (isset($event['wholedays'])) {
			$date = date_to_timestamp($event['begin']);
			if (empty($date))
				$error['begin'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
			else
				$begintime = 0;
		} else {
			$date = datetime_to_timestamp($event['begin'], $midnight = true);
			if (empty($date))
				$error['begin'] = trans('Incorrect date format! Enter date in YYYY/MM/DD HH:MM format!');
			else
				$begintime = datetime_to_timestamp($event['begin']) - $date;
		}
	}

	$enddate = 0;
	if ($event['end'] != '') {
		if (isset($event['wholedays'])) {
			$enddate = date_to_timestamp($event['end']);
			if (empty($enddate))
				$error['end'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
			else
				$endtime = 86400;
		} else {
			$enddate = datetime_to_timestamp($event['end'], $midnight = true);
			if (empty($enddate))
				$error['end'] = trans('Incorrect date format! Enter date in YYYY/MM/DD HH:MM format!');
			else
				$endtime = datetime_to_timestamp($event['end']) - $enddate;
		}
	} elseif ($date) {
		$enddate = $date;
		if (isset($event['wholedays']))
			$endtime = 86400;
		else
			$endtime = $begintime;
	}

	if ($enddate && $date > $enddate)
		$error['end'] = trans('End time must not precede start time!');

	if (ConfigHelper::checkConfig('phpui.event_overlap_warning')
		&& !$error && empty($event['overlapwarned']) && ($users = $LMS->EventOverlaps(array(
			'date' => $data,
			'begintime' => $begintime,
			'enddate' => $enddate,
			'endtime' => $endtime,
			'users' => $event['userlist'],
		)))) {
		$users = array_map(function($userid) use ($userlist) {
			return $userlist[$userid]['rname'];
		}, $users);
		$error['begin'] = $error['endd'] =
			trans('Event is assigned to users which already have assigned an event in the same time: $a!',
				implode(', ', $users));
		$event['overlapwarned'] = 1;
	}

	if (!isset($event['customerid']))
		$event['customerid'] = $event['custid'];

	if (isset($event['helpdesk'])) {
		if (empty($event['ticketid']))
			$error['ticketid'] = trans('Ticket id should not be empty!');
		else {
			$event['ticket'] = $LMS->GetTicketContents($event['ticketid'], true);
			if (!empty($event['ticket']['address_id']) && $event['address_id'] > 0)
				$error['address_id'] = trans('Event location selection is not possible as it is assigned to ticket!');
		}
	}

	if (!$error) {
		$event['private'] = isset($event['private']) ? 1 : 0;

		$event['address_id'] = !isset($event['address_id']) || $event['address_id'] == -1 ? null : $event['address_id'];
		$event['nodeid'] = !isset($event['nodeid']) || empty($event['nodeid']) ? null : $event['nodeid'];

		$event['date'] = $date;
		$event['begintime'] = $begintime;
		$event['enddate'] = $enddate;
		$event['endtime'] = $endtime;
		$event['helpdesk'] = isset($event['helpdesk']) ? $event['ticketid'] : null;
		$LMS->EventUpdate($event);

		$SESSION->redirect('?m=eventlist'
			. ($SESSION->is_set('backid') ? '#' . $SESSION->get('backid') : ''));
	}
} else
	$event['overlapwarned'] = 0;

$layout['pagetitle'] = trans('Event Edit');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$usergroups = $DB->GetAll('SELECT id, name FROM usergroups');

if (isset($event['customerid']) || intval($event['customerid']))
	$SMARTY->assign('nodes', $LMS->GetNodeLocations($event['customerid'],
		isset($event['address_id']) && intval($event['address_id']) > 0 ? $event['address_id'] : null));

if (!isset($event['usergroup']))
	$event['usergroup'] = -2;
	//$SESSION->restore('eventgid', $event['usergroup']);

$SMARTY->assign('max_userlist_size', ConfigHelper::getConfig('phpui.event_max_userlist_size'));
if (!ConfigHelper::checkConfig('phpui.big_networks'))
	$SMARTY->assign('customerlist', $LMS->GetAllCustomerNames());
$SMARTY->assign('userlist', $userlist);
$SMARTY->assign('usergroups', $usergroups);
$SMARTY->assign('error', $error);
$SMARTY->assign('event', $event);

$SMARTY->display('event/eventmodify.html');

?>
