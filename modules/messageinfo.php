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

function GetItemList($id, $order='id,desc', $search=NULL, $cat=NULL, $status=NULL)
{
	global $DB;

	if($order=='')
		$order='id,desc';

	list($order,$direction) = sscanf($order, '%[^,],%s');
	($direction=='desc') ? $direction = 'desc' : $direction = 'asc';

	switch($order)
	{
		case 'customer':
			$sqlord = ' ORDER BY customer';
		break;
		case 'status':
			$sqlord = ' ORDER BY i.status';
		break;
		default:
			$sqlord = ' ORDER BY i.id';
		break;
	}

	if($search!='' && $cat)
	{
		switch($cat)
		{
			case 'customerid':
				$where[] = ' i.customerid = '.intval($search);
			break;
			case 'destination':
				$where[] = ' UPPER(i.destination) ?LIKE? UPPER('.$DB->Escape('%'.$search.'%').')';
			break;
			case 'name':
				$where[] = ' UPPER(c.lastname) ?LIKE? UPPER('.$DB->Escape('%'.$search.'%').')';
			break;
		}
	}

	if($status)
	{
		switch($status)
		{
			case MSG_NEW:
			case MSG_ERROR:
			case MSG_SENT:
			case MSG_DELIVERED:
				$where[] = 'i.status = '.$status;
				break;
		}
	}

	if(!empty($where))
		$where = ' AND '.implode(' AND ', $where);

	$result = $DB->GetAll('SELECT i.id, i.customerid, i.status, i.error,
			i.destination, i.lastdate, i.lastreaddate,'
			.$DB->Concat('UPPER(c.lastname)',"' '",'c.name').' AS customer
		FROM messageitems i
		LEFT JOIN customers c ON (c.id = i.customerid)
		LEFT JOIN (
			SELECT DISTINCT a.customerid FROM customerassignments a
				JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
			WHERE e.userid = lms_current_user()
		) e ON (e.customerid = c.id) 
		WHERE e.customerid IS NULL AND i.messageid = '.intval($id)
		.(!empty($where) ? $where : '')
		.$sqlord.' '.$direction);

	$result['status'] = $status;
	$result['order'] = $order;
	$result['direction'] = $direction;

	return $result;
}

$message = $DB->GetRow('SELECT m.*, u.name
		FROM messages m
		LEFT JOIN vusers u ON (u.id = m.userid) 
		WHERE m.id = ?', array(intval($_GET['id'])));

if(!$message)
{
	$SESSION->redirect('?m=messagelist');
}

if(mb_strlen($message['subject']) > 25)
	$subject = mb_substr($message['subject'], 0, 25).'...';
else
	$subject = $message['subject'];

$SESSION->restore('milm', $marks);
if(isset($_POST['marks']))
	foreach($_POST['marks'] as $id => $mark)
		$marks[$id] = $mark;
$SESSION->save('milm', $marks);

if(isset($_POST['search']))
	$s = $_POST['search'];
else
	$SESSION->restore('mils', $s);
$SESSION->save('mils', $s);

if(isset($_POST['cat']))
	$c = $_POST['cat'];
else
	$SESSION->restore('milc', $c);
$SESSION->save('milc', $c);

if(isset($_GET['o']))
	$o = $_GET['o'];
else
	$SESSION->restore('milo', $o);
$SESSION->save('milo', $o);

if(isset($_POST['status']))
	$status = $_POST['status'];
else
	$SESSION->restore('milst', $status);
$SESSION->save('milst', $status);

$itemlist = GetItemList($message['id'], $o, $s, $c, $status);

$listdata['status'] = $itemlist['status'];
$listdata['order'] = $itemlist['order'];
$listdata['direction'] = $itemlist['direction'];
$listdata['cat'] = $c;
$listdata['search'] = $s;
$listdata['id'] = intval($_GET['id']);

unset($itemlist['status']);
unset($itemlist['order']);
unset($itemlist['direction']);

$listdata['total'] = count($itemlist);

if ($SESSION->is_set('milp') && !isset($_GET['page']))
	$SESSION->restore('milp', $_GET['page']);

$page = (empty($_GET['page']) ? 1 : $_GET['page']);
$pagelimit = ConfigHelper::getConfig('phpui.messagelist_pagelimit', $listdata['total']);
$SESSION->save('milp', $page);

$layout['pagetitle'] = trans('Message Info: $a', $subject);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('message', $message);
$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('start', ($page - 1) * $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('marks', $marks);
$SMARTY->assign('itemlist', $itemlist);

$SMARTY->display('message/messageinfo.html');

?>
