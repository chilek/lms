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

function EventSearch($search)
{
	global $LMS, $AUTH;

	$list = $LMS->DB->GetAll(
	        'SELECT events.id AS id, title, description, date, begintime, endtime, customerid, closed, '
		.$LMS->DB->Concat('UPPER(customers.lastname)',"' '",'customers.name').' AS customername 
		 FROM events LEFT JOIN customers ON (customerid = customers.id)
		 WHERE (private = 0 OR (private = 1 AND adminid = ?)) '
		.($search['datefrom'] ? ' AND date >= '.$search['datefrom'] : '')
		.($search['dateto'] ? ' AND date <= '.$search['dateto'] : '')
		.($search['customerid'] ? ' AND customerid = '.$search['customerid'] : '')
		.($search['title'] ? ' AND title ?LIKE? \'%'.$search['title'].'%\'' : '')
		.($search['description'] ? ' AND description ?LIKE? \'%'.$search['description'].'%\'' : '')
		.($search['note'] ? ' AND note ?LIKE? \'%'.$search['note'].'%\'' : '')
		.' ORDER BY date, begintime', array($AUTH->id));
	
	if($list)
		foreach($list as $idx => $row)
		{
			$list[$idx]['adminlist'] = $LMS->DB->GetAll('SELECT adminid AS id, admins.name
								    FROM eventassignments, admins
								    WHERE adminid = admins.id AND eventid = ? ',
								    array($row['id']));

			if($search['adminid'] && sizeof($list[$idx]['adminlist']))
				foreach($list[$idx]['adminlist'] as $admin)
					if($admin['id'] == $search['adminid'])
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

$layout['pagetitle'] = trans('Event Search');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if($event = $_POST['event'])
{
	if($event['datefrom'])
	{
		list($year, $month, $day) = explode('/', $event['datefrom']);
		$event['datefrom'] = mktime(0,0,0, $month, $day, $year);
	}

	if($event['dateto'])
	{
		list($year, $month, $day) = explode('/', $event['dateto']);
		$event['dateto'] = mktime(0,0,0, $month, $day, $year);
	}
	
	$eventlist = EventSearch($event);

	if(sizeof($eventlist))
		foreach($eventlist as $event)
			if(!in_array($event['date'], (array) $daylist))
				$daylist[] = $event['date'];
		
	$SMARTY->assign('eventlist', $eventlist);
	$SMARTY->assign('daylist', $daylist);
	$SMARTY->display('eventsearchresults.html');
	$SESSION->close();
	die;
}

$SMARTY->assign('adminlist',$LMS->GetAdminNames());
$SMARTY->assign('customerlist',$LMS->GetCustomerNames());
$SMARTY->assign('layout',$layout);
$SMARTY->display('eventsearch.html');

?>
