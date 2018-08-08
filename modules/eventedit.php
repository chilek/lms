<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

include(MODULES_DIR . DIRECTORY_SEPARATOR . 'eventxajax.inc.php');

if(isset($_GET['action']) && $_GET['action'] == 'open')
{
	$DB->Execute('UPDATE events SET closed = 0, closeduserid = NULL, closeddate = 0 WHERE id = ?',array($_GET['id']));
	$SESSION->redirect('?'.$SESSION->get('backto'));
}
elseif(isset($_GET['action']) && $_GET['action'] == 'close' && isset($_GET['ticketid']) )
{
	$DB->Execute('UPDATE events SET closed = 1, closeduserid = ?, closeddate = ?NOW?  WHERE ticketid = ?',array(Auth::GetCurrentUser(), $_GET['ticketid']));
	$SESSION->redirect('?'.$SESSION->get('backto'));
}
elseif(isset($_GET['action']) && $_GET['action'] == 'close')
{
	$DB->Execute('UPDATE events SET closed = 1, closeduserid = ?, closeddate = ?NOW?  WHERE id = ?',array(Auth::GetCurrentUser(), $_GET['id']));
	$SESSION->redirect('?'.$SESSION->get('backto'));
}
elseif(isset($_GET['action']) && $_GET['action'] == 'assign') {
    $LMS->AssignCurrentUserToEvent($_GET['id'], Auth::GetCurrentUser());
    $SESSION->redirect('?' . $SESSION->get('backto'));
}
elseif(isset($_GET['action']) && $_GET['action'] == 'deassign') {
    $LMS->DeassignCurrentUserFromEvent($_GET['id'], Auth::GetCurrentUser());
    $SESSION->redirect('?' . $SESSION->get('backto'));
}

$event = $LMS->GetEvent($_GET['id']);

$event['date'] = sprintf('%04d/%02d/%02d', date('Y',$event['date']),date('n',$event['date']),date('j',$event['date']));
if (empty($event['enddate']))
	$event['enddate'] = '';
else
	$event['enddate'] = sprintf('%04d/%02d/%02d', date('Y',$event['enddate']),date('n',$event['enddate']),date('j',$event['enddate']));

$userlist = $DB->GetAllByKey('SELECT id, rname FROM vusers
	WHERE deleted = 0 AND vusers.access = 1 ORDER BY lastname ASC', 'id');

if(isset($_POST['event']))
{
	$event = $_POST['event'];

	if (!isset($event['usergroup']))
		$event['usergroup'] = 0;
	$SESSION->save('eventgid', $event['usergroup']);

	$event['id'] = $_GET['id'];

	if($event['title'] == '')
		$error['title'] = trans('Event title is required!');
	elseif(strlen($event['title']) > 255)
		$error['title'] = trans('Event title is too long!');

	if ($event['date'] == '')
		$error['date'] = trans('You have to specify event day!');
	else {
		list ($year,$month, $day) = explode('/',$event['date']);
		if (checkdate($month, $day, $year))
			$date = mktime(0, 0, 0, $month, $day, $year);
		else
			$error['date'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
	}

	$enddate = 0;
	if ($event['enddate'] != '') {
		list ($year,$month, $day) = explode('/', $event['enddate']);
		if (checkdate($month, $day, $year))
			$enddate = mktime(0, 0, 0, $month, $day, $year);
		else
			$error['enddate'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
	}

	if ($enddate && $date > $enddate)
		$error['enddate'] = trans('End time must not precede start time!');

	if (ConfigHelper::checkConfig('phpui.event_overlap_warning')
		&& !$error && empty($event['overlapwarned']) && ($users = $LMS->EventOverlaps(array(
			'begindate' => $date,
			'begintime' => $event['begintime'],
			'enddate' => $enddate,
			'endtime' => $event['endtime'],
			'users' => $event['userlist'],
		)))) {
		$users = array_map(function($userid) use ($userlist) {
			return $userlist[$userid]['rname'];
		}, $users);
		$error['date'] = $error['enddate'] = $error['begintime'] = $error['endtime'] =
			trans('Event is assigned to users which already have assigned an event in the same time: $a!',
				implode(', ', $users));
		$event['overlapwarned'] = 1;
	}

	if (!isset($event['customerid']))
		$event['customerid'] = $event['custid'];

	if (!$error) {
		$event['private'] = isset($event['private']) ? 1 : 0;

		$event['address_id'] = !isset($event['address_id']) || $event['address_id'] == -1 ? null : $event['address_id'];
		$event['nodeid'] = !isset($event['nodeid']) || empty($event['nodeid']) ? null : $event['nodeid'];

		$event['date'] = $date;
		$event['enddate'] = $enddate;
		$event['helpdesk'] = isset($event['helpdesk']) && !empty($event['ticketid']) ? $event['ticketid'] : null;
		$LMS->EventUpdate($event);

		$SESSION->redirect('?m=eventlist');
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
	$SESSION->restore('eventgid', $event['usergroup']);

$SMARTY->assign('max_userlist_size', ConfigHelper::getConfig('phpui.event_max_userlist_size'));
if (!ConfigHelper::checkConfig('phpui.big_networks'))
	$SMARTY->assign('customerlist', $LMS->GetAllCustomerNames());
$SMARTY->assign('userlist', $userlist);
$SMARTY->assign('usergroups', $usergroups);
$SMARTY->assign('error', $error);
$SMARTY->assign('event', $event);
$SMARTY->assign('hours',
		array(0,30,100,130,200,230,300,330,400,430,500,530,
		600,630,700,730,800,830,900,930,1000,1030,1100,1130,
		1200,1230,1300,1330,1400,1430,1500,1530,1600,1630,1700,1730,
		1800,1830,1900,1930,2000,2030,2100,2130,2200,2230,2300,2330));
$SMARTY->display('event/eventedit.html');

?>
