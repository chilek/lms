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

function GetEvents($date=NULL, $userid=0, $type = 0, $customerid=0, $privacy = 0, $closed = '')
{
	global $AUTH;

	$DB = LMSDB::getInstance();

	switch ($privacy) {
		case 0:
			$privacy_condition = '(private = 0 OR (private = 1 AND userid = ' . intval(Auth::GetCurrentUser()) . '))';
			break;
		case 1:
			$privacy_condition = 'private = 0';
			break;
		case 2:
			$privacy_condition = 'private = 1 AND userid = ' . intval(Auth::GetCurrentUser());
			break;
	}

	$enddate = $date + 86400;
	$list = $DB->GetAll(
	        'SELECT events.id AS id, title, note, description, date, begintime, enddate, endtime, closed, events.type, c.id AS customerid,'
		.$DB->Concat('UPPER(c.lastname)',"' '",'c.name'). ' AS customername, '
	        .$DB->Concat('c.city',"', '",'c.address').' AS customerlocation,
		events.address_id, va.location, events.nodeid, nodes.location AS nodelocation, cc.customerphone, rt.netnodeid AS netnodeid, rt.netdevid AS netdevid,
		ticketid
		 FROM events
		 LEFT JOIN vaddresses va ON va.id = events.address_id
		 LEFT JOIN customerview c ON (customerid = c.id)
		 LEFT JOIN vnodes nodes ON (events.nodeid = nodes.id)
		 LEFT JOIN rttickets as rt ON (rt.id = events.ticketid)
		LEFT JOIN (
			SELECT ' . $DB->GroupConcat('contact', ', ') . ' AS customerphone, customerid
			FROM customercontacts
			WHERE type & ? > 0 AND type & ? = 0
			GROUP BY customerid
		) cc ON cc.customerid = c.id
		 WHERE ((date >= ? AND date < ?) OR (enddate <> 0 AND date < ? AND enddate >= ?)) AND ' . $privacy_condition
		 .($customerid ? 'AND customerid = '.intval($customerid) : '')
		.(!empty($userid) ? ' AND EXISTS (
			SELECT 1 FROM eventassignments
			WHERE eventid = events.id AND userid ' . (is_array($userid) ? 'IN (' . implode(',', array_filter($userid, 'intval')) . ')' : '=' . intval($userid)) . '
			)' : '')
		. (!empty($type) ? ' AND events.type ' . (is_array($type) ? 'IN (' . implode(',', array_filter($type, 'intval')) . ')' : '=' . intval($type)) : '')
		 . ($closed != '' ? ' AND closed = ' . intval($closed) : '')
		 .' ORDER BY date, begintime',
		array(CONTACT_MOBILE | CONTACT_FAX | CONTACT_LANDLINE, CONTACT_DISABLED,
			$date, $enddate, $enddate, $date));

	$list2 = array();
	if ($list)
		foreach ($list as $idx => $row) {
			$row['userlist'] = $DB->GetAll('SELECT userid AS id, vusers.name
				FROM eventassignments, vusers
				WHERE userid = vusers.id AND eventid = ?', array($row['id']));
            if(!empty($row['netnodeid'])) {
            $row['netnode_name'] = $DB->GetOne('SELECT name FROM netnodes WHERE id = ?', array($row['netnodeid']));
            $row['netnode_location'] = $DB->GetOne('SELECT address FROM vaddresses WHERE id = (SELECT address_id FROM netnodes WHERE id = ?)', array($row['netnodeid']));
			}

			$endtime = $row['endtime'];
			if ($row['enddate'] && $row['enddate'] - $row['date']) {
				$days = round(($row['enddate'] - $row['date']) / 86400);
				$row['enddate'] = $row['date'] + 86400;
				$row['endtime'] = 0;
				$list2[] = $row;
			} else
				$list2[] = $row;
		}

	return $list2;
}

$date = $_GET['day'];

if(!$date)
{
	$SESSION->redirect('?m=eventlist');
}

$eventlist = GetEvents($date, $_GET['a'], $_GET['t'], $_GET['u'], intval($_GET['privacy']), $_GET['closed']);

$layout['pagetitle'] = trans('Timetable');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('eventlist', $eventlist);
$SMARTY->assign('date', $date);
$SMARTY->display('event/eventprint.html');

?>
