<?php

/*
 * LMS version 1.5-cvs
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
		header('Location: ?m=eventlist');
		die;
	}
	
	if($event['title'] == '')
    		$error['title'] = trans('Event title is required!');
	
	if($event['date'] == '')
		$error['date'] = trans('You must specify event day!');
	else
	{
		list($year,$month, $day) = explode('/',$event['date']);
		if(!checkdate($month,$day,$year))
			$error['date'] = trans('Incorrect date format! Enter date in format YYYY/MM/DD!');
	}

	if(!$error)
	{
		$date = mktime(0, 0, 0, $month, $day, $year);
		$event['status'] = $event['status'] ? 1 : 0;

		$LMS->DB->Execute('INSERT INTO events (title, description, date, begintime, endtime, adminid, private, userid) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
				array($event['title'], $event['description'], $date, $event['begintime'], $event['endtime'], $SESSION->id, $event['status'], $event['userid']));
		
		$LMS->SetTS('events');
		
		if($event['adminlist'])
		{
			$id = $LMS->DB->GetOne('SELECT id FROM events WHERE title=? AND date=? AND begintime=? AND endtime=? AND adminid=?',
				array($event['title'], $date, $event['begintime'], $event['endtime'], $SESSION->id));

			foreach($event['adminlist'] as $adminid)
				$LMS->DB->Execute('INSERT INTO eventassignments (eventid, adminid) 
					VALUES (?, ?)', array($id, $adminid));

			$LMS->SetTS('eventassignments');
		}
		
		if(!$event['reuse'])
		{
			header('Location: ?m=eventlist');
			die;
		}
		
		unset($event['title']);
		unset($event['description']);
	}
}

$event['date'] = $event['date'] ? $event['date'] : $_SESSION['edate'];

if($_GET['day'] && $_GET['month'] && $_GET['year'])
{
	$event['date'] = sprintf('%04d/%02d/%02d', $_GET['year'], $_GET['month'], $_GET['day']);
}

$layout['pagetitle'] = trans('New Event');

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$adminlist = $LMS->GetAdminNames();

$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->assign('adminlist', $adminlist);
$SMARTY->assign('adminlistsize', sizeof($adminlist));
$SMARTY->assign('error', $error);
$SMARTY->assign('event', $event);
$SMARTY->assign('layout', $layout);
$SMARTY->assign('hours', 
		array(0,30,100,130,200,230,300,330,400,430,500,530,
		600,630,700,730,800,830,900,930,1000,1030,1100,1130,
		1200,1230,1300,1330,1400,1430,1500,1530,1600,1630,1700,1730,
		1800,1830,1900,1930,2000,2030,2100,2130,2200,2230,2300,2330
		));
$SMARTY->display('eventadd.html');

?>
