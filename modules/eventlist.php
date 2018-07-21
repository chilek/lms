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

if ($edate = $SESSION->get('edate'))
	list ($year, $month, $day) = explode('/', $SESSION->get('edate'));

if (!empty($_POST)) {
	if(isset($_POST['a']))
        $a = $_POST['a'];
	else
		$a = NULL;

    if(isset($_POST['u']))
	    $u = $_POST['u'];
    else
		$u = NULL;

    if (isset($_POST['month']))
        $month = $_POST['month'];
    else
		$month = date("m");

    if (isset($_POST['year']))
        $year = $_POST['year'];
    else
        $year = date("Y");

	if (isset($_POST['day']))
		$day = $_POST['day'];
	elseif ($edate) {
		if (empty($month))
			if($month != $_POST['month'])
				$day = 1;
		if (empty($year))
			if($year != $_POST['year'])
				$day = 1;
	} else
		$day = date('j',time());

	if (isset($_POST['type']))
	    $type = $_POST['type'];

	if (isset($_POST['privacy']))
		$privacy = intval($_POST['privacy']);

	if (isset($_POST['closed']))
		$closed = $_POST['closed'];
} else {
	if (isset($_GET['day']) && isset($_GET['month']) && isset($_GET['year'])) {
		if (isset($_GET['day']))
			$day = $_GET['day'];
		elseif ($edate) {
			if ($month != $_GET['month'] || $year != $_GET['year'])
				$day = 1;
		} else
			$day = 1;

		if (isset($_GET['month']))
			$month = $_GET['month'];

		if (isset($_GET['year']))
			$year = $_GET['year'];
	}

	$SESSION->restore('elu', $u);
	$SESSION->restore('ela', $a);
	$SESSION->restore('elt', $type);
	$SESSION->restore('elp', $privacy);
	$SESSION->restore('elc', $closed);
}

if(isset($u))
    $SESSION->save('elu', $u);
if(isset($a))
    $SESSION->save('ela', $a);
if(isset($type))
    $SESSION->save('elt', $type);
if(isset($privacy))
    $SESSION->save('elp', $privacy);
if(isset($closed))
    $SESSION->save('elc', $closed);

if(!isset($day))
    $day = date('j');

if(!isset($month))
    $month = date('n');

if(!isset($year))
    $year = date('Y');

$layout['pagetitle'] = trans('Timetable');

$eventlist = $LMS->GetEventList($year, $month, $day, ConfigHelper::getConfig('phpui.timetable_days_forward'), $u, $a, $type, $privacy, $closed);

if (ConfigHelper::checkConfig('phpui.timetable_overdue_events'))
	$overdue_events = $LMS->GetEventList($year, $month, $day, '-1', $u, $a, $type, $privacy, 0);

$SESSION->restore('elu', $listdata['customerid']);
$SESSION->restore('ela', $listdata['userid']);
$SESSION->restore('elt', $listdata['type']);
$SESSION->restore('elp', $listdata['privacy']);
$SESSION->restore('elc', $listdata['closed']);

// create calendars
for ($i = 0; $i < ConfigHelper::getConfig('phpui.timetable_days_forward'); $i++) {
	$dt = mktime(0, 0, 0, $month, $day+$i, $year);
	$daylist[$i] = $dt;
}

$date = mktime(0, 0, 0, $month, $day, $year);
$daysnum = date('t', $date);
for ($i = 1; $i < $daysnum + 1; $i++) {
	$date = mktime(0, 0, 0, $month, $i, $year);
	$days['day'][] = date('j',$date);
	$days['dow'][] = date('w',$date);
	$days['sel'][] = ($i == $day);
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);
$SESSION->save('edate', sprintf('%04d/%02d/%02d', $year, $month, $day));

$today = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
$SMARTY->assign('today', $today);

$SMARTY->assign('period', $DB->GetRow('SELECT MIN(date) AS fromdate, MAX(date) AS todate FROM events'));
$SMARTY->assign('eventlist',$eventlist);
if (ConfigHelper::checkConfig('phpui.timetable_overdue_events'))
	$SMARTY->assign('overdue_events',$overdue_events);

$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('days',$days);
$SMARTY->assign('day',$day);
$SMARTY->assign('daylist',$daylist);
$SMARTY->assign('month',$month);
$SMARTY->assign('year',$year);
$SMARTY->assign('date',$date);
$SMARTY->assign('error',$error);
$SMARTY->assign('userlist',$LMS->GetUserNames());
if (!ConfigHelper::checkConfig('phpui.big_networks'))
	$SMARTY->assign('customerlist',$LMS->GetCustomerNames());
$SMARTY->assign('getHolidays', getHolidays($year));
$SMARTY->display('event/eventlist.html');

?>
