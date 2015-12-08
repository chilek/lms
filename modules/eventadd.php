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

function select_customer($id)
{
    $JSResponse = new xajaxResponse();
    $nodes_location = LMSDB::getInstance()->GetAll('SELECT n.id, n.name, location FROM vnodes n WHERE ownerid = ? ORDER BY n.name ASC', array($id));
    $JSResponse->call('update_nodes_location', (array)$nodes_location);
    return $JSResponse;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction('select_customer');
$SMARTY->assign('xajax', $LMS->RunXajax());

if(isset($_POST['event']))
{
	$event = $_POST['event'];
	
	if(!($event['title'] || $event['description'] || $event['date']))
	{
		$SESSION->redirect('?m=eventlist');
	}

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

	if (!$error) {
		$event['status'] = isset($event['status']) ? 1 : 0;
		if (isset($event['customerid']))
			$event['custid'] = $event['customerid'];
		if ($event['custid'] == '')
			$event['custid'] = 0;
		$event['nodeid'] = (isset($event['customer_location'])||is_null($event['nodeid'])) ? NULL : $event['nodeid'];

		$DB->BeginTrans();

		$DB->Execute('INSERT INTO events (title, description, date, begintime, enddate, endtime, userid, private, customerid, type, nodeid)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
				array($event['title'], 
					$event['description'], 
					$date, 
					$event['begintime'], 
					$enddate,
					$event['endtime'], 
					$AUTH->id, 
					$event['status'], 
					intval($event['custid']),
					$event['type'],
					$event['nodeid']
					));

		if (!empty($event['userlist'])) {
			$id = $DB->GetLastInsertID('events');
			foreach($event['userlist'] as $userid)
				$DB->Execute('INSERT INTO eventassignments (eventid, userid) 
					VALUES (?, ?)', array($id, $userid));
		}

		$DB->CommitTrans();

		if(!isset($event['reuse']))
		{
			$SESSION->redirect('?m=eventlist');
		}
		
		unset($event['title']);
		unset($event['description']);
	}
}

$event['date'] = isset($event['date']) ? $event['date'] : $SESSION->get('edate');
if(empty($event['customerid']) && !empty($_GET['customerid']))
	$event['customerid'] = intval($_GET['customerid']);

if(isset($_GET['day']) && isset($_GET['month']) && isset($_GET['year']))
{
	$event['date'] = sprintf('%04d/%02d/%02d', $_GET['year'], $_GET['month'], $_GET['day']);
}

$layout['pagetitle'] = trans('New Event');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$userlist = $LMS->GetUserNames();

if (!ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.big_networks', false)))
{
	$SMARTY->assign('customerlist', $LMS->GetCustomerNames());
}

$SMARTY->assign('max_userlist_size', ConfigHelper::getConfig('phpui.event_max_userlist_size'));
$SMARTY->assign('userlist', $userlist);
$SMARTY->assign('userlistsize', sizeof($userlist));
$SMARTY->assign('error', $error);
$SMARTY->assign('event', $event);
$SMARTY->assign('hours', 
		array(0,30,100,130,200,230,300,330,400,430,500,530,
		600,630,700,730,800,830,900,930,1000,1030,1100,1130,
		1200,1230,1300,1330,1400,1430,1500,1530,1600,1630,1700,1730,
		1800,1830,1900,1930,2000,2030,2100,2130,2200,2230,2300,2330
		));
$SMARTY->display('event/eventadd.html');

?>
