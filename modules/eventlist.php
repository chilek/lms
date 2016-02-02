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

function GetEventList($year=NULL, $month=NULL, $day=NULL, $forward=0, $customerid=0, $userid=0)
{
	global $DB, $AUTH;

	if(!$year) $year = date('Y',time());
	if(!$month) $month = date('n',time());
	if(!$day) $day = date('j',time());

	$startdate = mktime(0,0,0, $month, $day, $year);
	$enddate = mktime(0,0,0, $month, $day+$forward, $year);

	$list = $DB->GetAll(
		'SELECT events.id AS id, title, description, date, begintime, enddate, endtime, customerid, closed, events.type, '
		.$DB->Concat('UPPER(customers.lastname)',"' '",'customers.name').' AS customername,
		userid, users.name AS username, '.$DB->Concat('customers.city',"', '",'customers.address').' AS customerlocation, nodeid, nodes.location AS location 
		FROM events 
		LEFT JOIN nodes ON (nodeid = nodes.id)
		LEFT JOIN customers ON (customerid = customers.id)
		LEFT JOIN users ON (userid = users.id)
		WHERE ((date >= ? AND date < ?) OR (enddate <> 0 AND date < ? AND enddate >= ?))
			AND (private = 0 OR (private = 1 AND userid = ?)) '
		.($customerid ? ' AND customerid = '.intval($customerid) : '')
		.($userid ? ' AND EXISTS (
			SELECT 1 FROM eventassignments 
			WHERE eventid = events.id AND userid = '.intval($userid).'
			)' : '')
		.' ORDER BY date, begintime',
		 array($startdate, $enddate, $enddate, $startdate, $AUTH->id));

	$list2 = array();
	if ($list)
		foreach ($list as $idx => $row) {
			$row['userlist'] = $DB->GetAll('SELECT userid AS id, users.name
					FROM eventassignments, users
					WHERE userid = users.id AND eventid = ? ',
					array($row['id']));
			$endtime = $row['endtime'];
			if ($row['enddate'] && $row['enddate'] - $row['date']) {
				$days = round(($row['enddate'] - $row['date']) / 86400);
				$row['endtime'] = 0;
				$list2[] = $row;
				while ($days) {
					if ($days == 1)
						$row['endtime'] = $endtime;
					$row['date'] += 86400;
					if ($days > 1 || $endtime)
						$list2[] = $row;
					$days--;
				}
			} else
				$list2[] = $row;
		}

	return $list2;
}

if(!isset($_GET['a']))
	$SESSION->restore('ela', $a);
else
	$a = $_GET['a'];
$SESSION->save('ela', $a);

if(!isset($_GET['u']))
	$SESSION->restore('elu', $u);
else 
	$u = $_GET['u'];
$SESSION->save('elu', $u);

if($edate = $SESSION->get('edate'))
	list($year, $month, $day) = explode('/', $SESSION->get('edate'));

if(isset($_GET['month']) && isset($_GET['year']))
{
	if(isset($_GET['day']))
		$day = $_GET['day'];
	elseif($edate)
	{
		if($month != $_GET['month'] || $year != $_GET['year'])
			$day = 1;
	}
	else
		$day = 1;
		
	$month = $_GET['month'];
	$year = $_GET['year'];
}

$day = (isset($day) ? $day : date('j',time()));
$month = (isset($month) ? sprintf('%d',$month) : date('n',time()));
$year = (isset($year) ? $year : date('Y',time()));

$layout['pagetitle'] = trans('Timetable');

$eventlist = GetEventList($year, $month, $day, ConfigHelper::getConfig('phpui.timetable_days_forward'), $u, $a);
$SESSION->restore('elu', $listdata['customerid']);
$SESSION->restore('ela', $listdata['userid']);

// create calendars
for($i=0; $i<ConfigHelper::getConfig('phpui.timetable_days_forward'); $i++)
{
	$dt = mktime(0, 0, 0, $month, $day+$i, $year);
	$daylist[$i] = $dt;
}

$date = mktime(0, 0, 0, $month, $day, $year);
$daysnum = date('t', $date);
for($i=1; $i<$daysnum+1; $i++)
{
	$date = mktime(0, 0, 0, $month, $i, $year);
	$days['day'][] = date('j',$date);
	$days['dow'][] = date('w',$date);
	$days['sel'][] = ($i == $day);
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);
$SESSION->save('edate', sprintf('%04d/%02d/%02d', $year, $month, $day));

$SMARTY->assign('period', $DB->GetRow('SELECT MIN(date) AS fromdate, MAX(date) AS todate FROM events'));
$SMARTY->assign('eventlist',$eventlist);
$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('days',$days);
$SMARTY->assign('day',$day);
$SMARTY->assign('daylist',$daylist);
$SMARTY->assign('month',$month);
$SMARTY->assign('year',$year);
$SMARTY->assign('date',$date);
$SMARTY->assign('userlist',$LMS->GetUserNames());
$SMARTY->assign('customerlist',$LMS->GetCustomerNames());
$SMARTY->assign('getHolidays', getHolidays($year));
$SMARTY->display('event/eventlist.html');

?>
