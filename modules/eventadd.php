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

if (!empty($_GET['ticketid'])) {
	$eventticketid = intval($_GET['ticketid']);
	$tqname = $LMS->GetQueueNameByTicketId($eventticketid);
}

if(isset($_POST['event']))
{
	$event = $_POST['event'];

	if (!empty($event['helpdesk']) && !count($event['categories']))
		$error['categories'] = trans('You have to select category!');

	if (!isset($event['usergroup']))
		$event['usergroup'] = 0;
	$SESSION->save('eventgid', $event['usergroup']);

	if (!($event['title'] || $event['description'] || $event['date']))
		$SESSION->redirect('?m=eventlist');

	if ($event['title'] == '')
		$error['title'] = trans('Event title is required!');
	elseif(strlen($event['title']) > 255)
		$error['title'] = trans('Event title is too long!');

	if ($event['date'] == '')
		$error['date'] = trans('You have to specify event day!');
	else {
		list ($year, $month, $day) = explode('/',$event['date']);
		if (checkdate($month, $day, $year))
			$date = mktime(0, 0, 0, $month, $day, $year);
		else
			$error['date'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
	}

	$enddate = 0;
	if ($event['enddate'] != '') {
		list ($year, $month, $day) = explode('/', $event['enddate']);
		if (checkdate($month, $day, $year))
			$enddate = mktime(0, 0, 0, $month, $day, $year);
		else
			$error['enddate'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
	}

	if ($enddate && $date > $enddate)
		$error['enddate'] = trans('End time must not precede start time!');

	if (!isset($event['customerid']))
		$event['customerid'] = $event['custid'];

	$event['status'] = isset($event['status']) ? 1 : 0;

	if (!$error) {
		$event['address_id'] = !isset($event['address_id']) || $event['address_id'] == -1 ? null : $event['address_id'];
		$event['nodeid'] = !isset($event['nodeid']) || empty($event['nodeid']) ? null : $event['nodeid'];

		if (isset($event['helpdesk'])) {
			$ticket['queue'] = $event['rtqueue'];
			$ticket['customerid'] = $event['customerid'];
			$ticket['body'] = $event['description'];
			$ticket['requestor'] = $event['name']." ".$event['surname'];
			$ticket['subject'] = $event['title'];
			$ticket['mailfrom'] = $event['email'];
			$ticket['categories'] = $event['categories'];
			$ticket['owner'] = '0';
			$ticket['address_id'] = $event['address_id'];
			$ticket['nodeid'] = $event['nodeid'];
			$event['ticketid'] = $LMS->TicketAdd($ticket);
		}

		$event['date'] = $date;
		$event['enddate'] = $enddate;

		$LMS->EventAdd($event);

		if (!isset($event['reuse'])) {
			$SESSION->redirect('?m=eventlist');
		}

		unset($event['title']);
		unset($event['description']);
		unset($event['categories']);
	}
} else {
	$event['helpdesk'] = ConfigHelper::checkConfig('phpui.default_event_ticket_assignment');
}

$event['date'] = isset($event['date']) ? $event['date'] : $SESSION->get('edate');

if (isset($_GET['customerid']))
	$event['customerid'] = intval($_GET['customerid']);
if (isset($event['customerid'])) {
	$event['customername'] = $LMS->GetCustomerName($event['customerid']);
	$SMARTY->assign('nodes', $LMS->GetNodeLocations($event['customerid'],
		isset($event['address_id']) && intval($event['address_id']) > 0 ? $event['address_id'] : null));
}

if(isset($_GET['day']) && isset($_GET['month']) && isset($_GET['year']))
{
	$event['date'] = sprintf('%04d/%02d/%02d', $_GET['year'], $_GET['month'], $_GET['day']);
}


$layout['pagetitle'] = trans('New Event');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$usergroups = $DB->GetAll('SELECT id, name FROM usergroups');
$userlist = $DB->GetAll('SELECT id, rname FROM vusers
	WHERE deleted = 0 AND access = 1 ORDER BY lastname ASC');

if (!isset($event['usergroup']))
	$SESSION->restore('eventgid', $event['usergroup']);

if (!ConfigHelper::checkConfig('phpui.big_networks'))
	$SMARTY->assign('customerlist', $LMS->GetCustomerNames());

if (isset($eventticketid))
	$event['ticketid'] = $eventticketid;

$categories = $LMS->GetCategoryListByUser($AUTH->id);
$SMARTY->assign('max_userlist_size', ConfigHelper::getConfig('phpui.event_max_userlist_size'));
$SMARTY->assign('userlist', $userlist);
$SMARTY->assign('tqname',$tqname);
$SMARTY->assign('usergroups', $usergroups);
$SMARTY->assign('error', $error);
$SMARTY->assign('event', $event);
$SMARTY->assign('hours',
		array(0,30,100,130,200,230,300,330,400,430,500,530,
		600,630,700,730,800,830,900,930,1000,1030,1100,1130,
		1200,1230,1300,1330,1400,1430,1500,1530,1600,1630,1700,1730,
		1800,1830,1900,1930,2000,2030,2100,2130,2200,2230,2300,2330
		));
$SMARTY->assign('queuelist', $LMS->GetQueueNames());
$SMARTY->assign('categories', $categories);
$SMARTY->display('event/eventadd.html');

?>
