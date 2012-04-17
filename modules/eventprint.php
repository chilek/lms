<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

function GetEvents($date=NULL, $userid=0, $customerid=0)
{
	global $DB, $AUTH;

	$list = $DB->GetAll(
	        'SELECT events.id AS id, title, description, begintime, endtime, closed, note, '
		.$DB->Concat('UPPER(customers.lastname)',"' '",'customers.name'). ' AS customername, 
		 customers.address AS customeraddr, customers.city AS customercity,
		 (SELECT phone FROM customercontacts WHERE customerid = customers.id ORDER BY id LIMIT 1) AS customerphone 
		 FROM events LEFT JOIN customers ON (customerid = customers.id)
		 WHERE date = ? AND (private = 0 OR (private = 1 AND userid = ?)) '
		 .($customerid ? 'AND customerid = '.intval($customerid) : '')
		 .' ORDER BY begintime',
		 array($date, $AUTH->id));

	if($list)
		foreach($list as $idx => $row)
		{
			$list[$idx]['userlist'] = $DB->GetAll('SELECT userid AS id, users.name
								    FROM eventassignments, users
								    WHERE userid = users.id AND eventid = ? ',
								    array($row['id']));

			if($userid && sizeof($list[$idx]['userlist']))
				foreach($list[$idx]['userlist'] as $user)
					if($user['id'] == $userid)
					{
						$list2[] = $list[$idx];
						break;
					}
		}

	if($userid)
		return $list2;
	else
		return $list;
}

$date = $_GET['day'];

if(!$date)
{
	$SESSION->redirect('?m=eventlist');
}

$eventlist = GetEvents($date, $_GET['a'], $_GET['u']);

$layout['pagetitle'] = trans('Timetable');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('eventlist', $eventlist);
$SMARTY->assign('date', $date);
$SMARTY->display('eventprint.html');

?>
