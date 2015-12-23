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

if(!$_GET['id'])
{
	$SESSION->redirect('?m=eventlist');
}

$event = $DB->GetRow('SELECT events.id AS id, title, description, note, userid, customerid, date, begintime, enddate, endtime, private, closed, events.type, '
			    .$DB->Concat('UPPER(c.lastname)',"' '",'c.name').' AS customername,
			    users.name AS username, events.moddate, events.moduserid, nodes.location AS location, '
			    .$DB->Concat('c.city',"', '",'c.address').' AS customerlocation,
			    (SELECT name FROM users WHERE id=events.moduserid) AS modusername 
			    FROM events 
			    LEFT JOIN nodes ON (nodeid = nodes.id)
			    LEFT JOIN customerview c ON (c.id = customerid)
			    LEFT JOIN users ON (users.id = userid)
			    WHERE events.id = ?', array($_GET['id']));

$event['userlist'] = $DB->GetAll('SELECT userid AS id, users.name
					FROM users, eventassignments
					WHERE users.id = userid
					AND eventid = ?', array($event['id']));

$layout['pagetitle'] = trans('Event Info');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('event', $event);
$SMARTY->display('event/eventinfo.html');

?>
