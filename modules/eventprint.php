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

function GetEvents($date=NULL, $userid=0, $customerid=0)
{
	global $DB, $AUTH;

	$list = $DB->GetAll(
	        'SELECT events.id AS id, title, description, begintime, endtime, closed, note, events.type,'
		.$DB->Concat('UPPER(c.lastname)',"' '",'c.name'). ' AS customername, '
	        .$DB->Concat('c.city',"', '",'c.address').' AS customerlocation,
		 nodes.location AS nodelocation,
		 (SELECT contact FROM customercontacts WHERE customerid = c.id
			AND (customercontacts.type & ?) > 0 AND (customercontacts.type & ?) <> ?  ORDER BY id LIMIT 1) AS customerphone
		 FROM events LEFT JOIN customerview c ON (customerid = c.id) LEFT JOIN nodes ON (nodeid = nodes.id)
		 WHERE (date = ? OR (enddate <> 0 AND date <= ? AND enddate > ?)) AND (private = 0 OR (private = 1 AND userid = ?)) '
		 .($customerid ? 'AND customerid = '.intval($customerid) : '')
		 .' ORDER BY begintime',
		 array((CONTACT_MOBILE|CONTACT_FAX|CONTACT_LANDLINE), CONTACT_DISABLED, CONTACT_DISABLED, $date, $date, $date, $AUTH->id));

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
$SMARTY->display('event/eventprint.html');

?>
