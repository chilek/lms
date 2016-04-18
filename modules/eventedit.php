<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

function select_customer($id)
{
    $JSResponse = new xajaxResponse();
    $nodes_location = LMSDB::getInstance()->GetAll('SELECT n.id, n.name, location FROM vnodes n WHERE ownerid = ? ORDER BY n.name ASC', array($id));
    $JSResponse->call('update_nodes_location', (array)$nodes_location);
    return $JSResponse;
}

function getUsersForGroup($groupid) {
	$JSResponse = new xajaxResponse();

	if (empty($groupid))
		$users = null;
	else
		$users = LMSDB::getInstance()->GetCol('SELECT u.id FROM users u
			JOIN userassignments ua ON ua.userid = u.id
			WHERE u.deleted = 0 AND u.access = 1 AND ua.usergroupid = ?',
			array($groupid));

	$JSResponse->call('update_user_selection', $users);

	return $JSResponse;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array('select_customer', 'getUsersForGroup'));
$SMARTY->assign('xajax', $LMS->RunXajax());

if(isset($_GET['action']) && $_GET['action'] == 'open')
{
	$DB->Execute('UPDATE events SET closed = 0 WHERE id = ?',array($_GET['id']));
	$SESSION->redirect('?'.$SESSION->get('backto'));
}
elseif(isset($_GET['action']) && $_GET['action'] == 'close')
{
	$DB->Execute('UPDATE events SET closed = 1 WHERE id = ?',array($_GET['id']));
	$SESSION->redirect('?'.$SESSION->get('backto'));
}

$event = $DB->GetRow('SELECT events.id AS id, title, description, note, events.type,
			date, begintime, enddate, endtime, customerid, private, closed, ' 
			.$DB->Concat('UPPER(customers.lastname)',"' '",'customers.name').' AS customername, nodeid
			FROM events LEFT JOIN customers ON (customers.id = customerid)
			WHERE events.id = ?', array($_GET['id']));

$event['date'] = sprintf('%04d/%02d/%02d', date('Y',$event['date']),date('n',$event['date']),date('j',$event['date']));
if (empty($event['enddate']))
	$event['enddate'] = '';
else
	$event['enddate'] = sprintf('%04d/%02d/%02d', date('Y',$event['enddate']),date('n',$event['enddate']),date('j',$event['enddate']));

$eventuserlist = $DB->GetCol('SELECT userid AS id
				FROM users, eventassignments
				WHERE users.id = userid
				AND eventid = ?', array($event['id']));

if ($eventuserlist === null) {
    $eventuserlist = array();
}

if(isset($_POST['event']))
{
	$event = $_POST['event'];

	if (!isset($event['usergroup']))
		$event['usergroup'] = 0;
	$SESSION->save('eventgid', $event['usergroup']);

	$event['id'] = $_GET['id'];

	if($event['title'] == '')
		$error['title'] = trans('Event title is required!');
	elseif(strlen($event['title']) > 255)
		$error['title'] = trans('Event title is too long!');

	if ($event['date'] == '')
		$error['date'] = trans('You have to specify event day!');
	else {
		list ($year,$month, $day) = explode('/',$event['date']);
		if (checkdate($month, $day, $year))
			$date = mktime(0, 0, 0, $month, $day, $year);
		else
			$error['date'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
	}

	$enddate = 0;
	if ($event['enddate'] != '') {
		list ($year,$month, $day) = explode('/', $event['enddate']);
		if (checkdate($month, $day, $year))
			$enddate = mktime(0, 0, 0, $month, $day, $year);
		else
			$error['enddate'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
	}

	if ($enddate && $date > $enddate)
		$error['enddate'] = trans('End time must not precede start time!');

	if (!$error) {
		$event['private'] = isset($event['private']) ? 1 : 0;
		if (isset($event['customerid']))
			$event['custid'] = $event['customerid'];
		if ($event['custid'] == '')
			$event['custid'] = 0;
		$event['nodeid'] = isset($event['customer_location']) ? NULL : $event['nodeid'];

		$DB->BeginTrans();

		$DB->Execute('UPDATE events SET title=?, description=?, date=?, begintime=?, enddate=?, endtime=?, private=?, note=?, customerid=?, type=?, nodeid=? WHERE id=?',
				array($event['title'], $event['description'], $date, $event['begintime'], $enddate, $event['endtime'], $event['private'], $event['note'], $event['custid'], $event['type'], $event['nodeid'], $event['id']));

		if (!empty($event['userlist']) && is_array($event['userlist'])) {
			$DB->Execute('DELETE FROM eventassignments WHERE eventid = ?', array($event['id']));
			foreach ($event['userlist'] as $userid)
				$DB->Execute('INSERT INTO eventassignments (eventid, userid) VALUES (?, ?)',
					array($event['id'], $userid));
		}

		$DB->Execute('UPDATE events SET moddate=?, moduserid=? WHERE id=?',
			array(time(), $AUTH->id, $event['id']));

		$DB->CommitTrans();

		$SESSION->redirect('?m=eventlist');
	}
} else
	$event['userlist'] = $eventuserlist;

$layout['pagetitle'] = trans('Event Edit');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$usergroups = $DB->GetAll('SELECT id, name FROM usergroups');
$userlist = $DB->GetAll('SELECT id, name FROM users
	WHERE deleted = 0 AND users.access = 1 ORDER BY login ASC');

if (empty($nodes_location))
	$nodes_location = $DB->GetAll('SELECT n.id, n.name, location FROM vnodes n WHERE ownerid = ? ORDER BY name ASC', array($event['customerid']));

if (!isset($event['usergroup']))
	$SESSION->restore('eventgid', $event['usergroup']);

$SMARTY->assign('max_userlist_size', ConfigHelper::getConfig('phpui.event_max_userlist_size'));
$SMARTY->assign('nodes_location', $nodes_location);
if (!ConfigHelper::checkConfig('phpui.big_networks'))
	$SMARTY->assign('customerlist', $LMS->GetCustomerNames());
$SMARTY->assign('userlist', $userlist);
$SMARTY->assign('usergroups', $usergroups);
$SMARTY->assign('error', $error);
$SMARTY->assign('event', $event);
$SMARTY->assign('hours', 
		array(0,30,100,130,200,230,300,330,400,430,500,530,
		600,630,700,730,800,830,900,930,1000,1030,1100,1130,
		1200,1230,1300,1330,1400,1430,1500,1530,1600,1630,1700,1730,
		1800,1830,1900,1930,2000,2030,2100,2130,2200,2230,2300,2330));
$SMARTY->display('event/eventedit.html');

?>
