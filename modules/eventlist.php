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

function GetEventList($year=NULL, $month=NULL, $day=NULL, $forward=0, $userid=0, $adminid=0)
{
	global $LMS, $AUTH;

	if(!$year) $year = date('Y',time());
	if(!$month) $month = date('n',time());
	if(!$day) $day = date('j',time());
	
	$startdate = mktime(0,0,0, $month, $day, $year);
	$enddate = mktime(0,0,0, $month, $day+$forward, $year);

	$list = $LMS->DB->GetAll(
	        'SELECT events.id AS id, title, description, date, begintime, endtime, userid, closed, '
		.$LMS->DB->Concat('UPPER(users.lastname)',"' '",'users.name').' AS username 
		 FROM events LEFT JOIN users ON (userid = users.id)
		 WHERE date >= ? AND date < ? AND (private = 0 OR (private = 1 AND adminid = ?)) '
		.($userid ? 'AND userid = '.$userid : '')
		.' ORDER BY date, begintime',
		 array($startdate, $enddate, $AUTH->id));
	
	if($list)
		foreach($list as $idx => $row)
		{
			$list[$idx]['adminlist'] = $LMS->DB->GetAll('SELECT adminid AS id, admins.name
								    FROM eventassignments, admins
								    WHERE adminid = admins.id AND eventid = ? ',
								    array($row['id']));

			if($adminid && sizeof($list[$idx]['adminlist']))
				foreach($list[$idx]['adminlist'] as $admin)
					if($admin['id'] == $adminid)
					{
						$list2[] = $list[$idx];
						break;
					}
		}
	
	if($adminid)
		return $list2;	
	else	
		return $list;
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

if($_GET['day'] && $_GET['month'] && $_GET['year'])
{
	$day = $_GET['day'];
	$month = $_GET['month'];
	$year = $_GET['year'];
}
else
	list($year, $month, $day) = explode('/', $SESSION->get('edate'));

$day = ($day ? $day : date('j',time()));
$month = ($month ? $month : date('n',time()));
$year = ($year ? $year : date('Y',time()));

$layout['pagetitle'] = trans('Timetable');

$eventlist = GetEventList($year, $month, $day, $LMS->CONFIG['phpui']['timetable_days_forward'], $u, $a);
$SESSION->restore('elu', $listdata['userid']);
$SESSION->restore('ela', $listdata['adminid']);

// create calendars
for($i=0; $i<$LMS->CONFIG['phpui']['timetable_days_forward']; $i++)
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

$SMARTY->assign('eventlist',$eventlist);
$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('days',$days);
$SMARTY->assign('day',$day);
$SMARTY->assign('daylist',$daylist);
$SMARTY->assign('month',$month);
$SMARTY->assign('year',$year);
$SMARTY->assign('date',$date);
$SMARTY->assign('adminlist',$LMS->GetAdminNames());
$SMARTY->assign('userlist',$LMS->GetUserNames());
$SMARTY->assign('layout',$layout);
$SMARTY->display('eventlist.html');

?>
