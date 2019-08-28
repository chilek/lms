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

function GetEvents($date = null, $userid = 0, $type = 0, $customerid = 0, $privacy = 0, $closed = '')
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
        .$DB->Concat('UPPER(c.lastname)', "' '", 'c.name'). ' AS customername, '
        .$DB->Concat('c.city', "', '", 'c.address').' AS customerlocation, 
		events.address_id, va.location, events.nodeid, nodes.location AS nodelocation, cc.customerphone, nn.id AS netnode_id,
		nn.name AS netnode_name, vd.address AS netnode_location, ticketid
		 FROM events
		 LEFT JOIN vaddresses va ON va.id = events.address_id
		 LEFT JOIN customerview c ON (customerid = c.id)
		 LEFT JOIN vnodes nodes ON (events.nodeid = nodes.id)
		 LEFT JOIN rttickets as rtt ON (rtt.id = events.ticketid)
		 LEFT JOIN netnodes as nn ON (nn.id = rtt.netnodeid)
		 LEFT JOIN vaddresses as vd ON (vd.id = nn.address_id)
		 LEFT JOIN (
			SELECT ' . $DB->GroupConcat('contact', ', ') . ' AS customerphone, customerid
			FROM customercontacts
			WHERE type & ? > 0 AND type & ? = 0
			GROUP BY customerid
		) cc ON cc.customerid = c.id
		 WHERE ((date >= ? AND date < ?) OR (enddate <> 0 AND date < ? AND enddate >= ?)) AND ' . $privacy_condition
         .($customerid ? ' AND events.customerid = '.intval($customerid) : '')
        .(!empty($userid) ? ' AND EXISTS (
			SELECT 1 FROM eventassignments
			WHERE eventid = events.id AND userid ' . (is_array($userid) ? 'IN (' . implode(',', Utils::filterIntegers($userid)) . ')' : '=' . intval($userid)) . '
			)' : '')
        . (!empty($type) ? ' AND events.type ' . (is_array($type) ? 'IN (' . implode(',', Utils::filterIntegers($type)) . ')' : '=' . intval($type)) : '')
         . ($closed != '' ? ' AND closed = ' . intval($closed) : '')
         .' ORDER BY date, begintime',
        array(CONTACT_MOBILE | CONTACT_FAX | CONTACT_LANDLINE, CONTACT_DISABLED,
            $date,
        $enddate,
        $enddate,
        $date)
    );

    $list2 = array();
    if ($list) {
        foreach ($list as $idx => $row) {
            $row['userlist'] = $DB->GetAll(
                'SELECT userid AS id, vusers.name
				FROM eventassignments, vusers
				WHERE userid = vusers.id AND eventid = ? ',
                array($row['id'])
            );

            $endtime = $row['endtime'];

            $row['wholeday'] = $endtime == 86400;
            $row['multiday'] = false;

            if ($row['enddate'] && $row['enddate'] - $row['date']) {
                $days = round(($row['enddate'] - $row['date']) / 86400);
                $row['multiday'] = $days > 0;
                $row['enddate'] = $row['date'] + 86400;
                //$row['endtime'] = 0;
                $list2[] = $row;
            } else {
                $list2[] = $row;
            }
        }
    }

    return $list2;
}

$date = $_GET['day'];

if (empty($date)) {
    $date=date_to_timestamp(time());
}

$eventlist = GetEvents($date, $_GET['a'], $_GET['t'], $_GET['u'], intval($_GET['privacy']), $_GET['closed']);

$layout['pagetitle'] = trans('Timetable');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('eventlist', $eventlist);
$SMARTY->assign('date', $date);
$SMARTY->display('event/eventprint.html');
