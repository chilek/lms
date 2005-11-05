<?php

/*
 * LMS version 1.7-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

if($event = $_POST['event'])
{
	if(!($event['title'] || $event['description'] || $event['date']))
	{
		$SESSION->redirect('?m=eventlist');
	}
	
	if($event['title'] == '')
    		$error['title'] = trans('Event title is required!');
	
	if($event['date'] == '')
		$error['date'] = trans('You have to specify event day!');
	else
	{
		list($year,$month, $day) = explode('/',$event['date']);
		if(!checkdate($month,$day,$year))
			$error['date'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
	}

	if(!$error)
	{
		$date = mktime(0, 0, 0, $month, $day, $year);
		$event['status'] = $event['status'] ? 1 : 0;

		$DB->Execute('INSERT INTO events (title, description, date, begintime, endtime, userid, private, customerid) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
				array($event['title'], $event['description'], $date, $event['begintime'], $event['endtime'], $AUTH->id, $event['status'], $event['customerid']));
		
		$LMS->SetTS('events');
		
		if($event['userlist'])
		{
			$id = $DB->GetOne('SELECT id FROM events WHERE title=? AND date=? AND begintime=? AND endtime=? AND userid=?',
				array($event['title'], $date, $event['begintime'], $event['endtime'], $AUTH->id));

			foreach($event['userlist'] as $userid)
				$DB->Execute('INSERT INTO eventassignments (eventid, userid) 
					VALUES (?, ?)', array($id, $userid));

			$LMS->SetTS('eventassignments');
		}
		
		if(!$event['reuse'])
		{
			$SESSION->redirect('?m=eventlist');
		}
		
		unset($event['title']);
		unset($event['description']);
	}
}

$event['date'] = $event['date'] ? $event['date'] : $SESSION->get('edate');

if($_GET['day'] && $_GET['month'] && $_GET['year'])
{
	$event['date'] = sprintf('%04d/%02d/%02d', $_GET['year'], $_GET['month'], $_GET['day']);
}

$layout['pagetitle'] = trans('New Event');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$userlist = $LMS->GetUserNames();

$SMARTY->assign('customerlist', $LMS->GetCustomerNames());
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
$SMARTY->display('eventadd.html');

?>
