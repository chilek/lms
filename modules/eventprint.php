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

function GetEvents($date=NULL, $adminid=0, $userid=0)
{
	global $LMS, $AUTH;

	$list = $LMS->DB->GetAll(
	        'SELECT events.id AS id, title, description, begintime, endtime, closed, note, '
		.$LMS->DB->Concat('UPPER(users.lastname)',"' '",'users.name'). ' AS username, 
		 users.address AS useraddr, users.phone1 AS userphone 
		 FROM events LEFT JOIN users ON (userid = users.id)
		 WHERE date = ? AND (private = 0 OR (private = 1 AND adminid = ?)) '
		 .($userid ? 'AND userid = '.$userid : '')
		 .' ORDER BY begintime',
		 array($date, $AUTH->id));

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

$date = $_GET['day'];

if(!$date)
{
	header('Location: ?m=eventlist');
	die;
}

$eventlist = GetEvents($date, $_GET['a'], $_GET['u']);

$layout['pagetitle'] = trans('Timetable');

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$SMARTY->assign('eventlist', $eventlist);
$SMARTY->assign('date', $date);
$SMARTY->assign('layout',$layout);
$SMARTY->display('eventprint.html');

?>
