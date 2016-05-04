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

function GetMessagesList($order='cdate,desc', $search=NULL, $cat=NULL, $type='', $status=NULL)
{
	global $DB;
	
	if($order=='')
		$order='cdate,desc';
	
	list($order,$direction) = sscanf($order, '%[^,],%s');
	($direction=='desc') ? $direction = 'desc' : $direction = 'asc';
	
	switch($order)
	{
		case 'subject':
			$sqlord = ' ORDER BY m.subject';
		break;
		case 'type':
			$sqlord = ' ORDER BY m.type';
		break;
		case 'cnt':
			$sqlord = ' ORDER BY cnt';
		break;
		default:
			$sqlord = ' ORDER BY m.cdate';
		break;
	}
	
	if($search!='' && $cat)
        {
	        switch($cat)
		{
			case 'userid':
				$where[] = 'm.userid = '.intval($search);
			break;
			case 'username':
				$where[] = 'UPPER(u.name) ?LIKE? UPPER('.$DB->Escape('%'.$search.'%').')';
				$userjoin = true;
			break;
			case 'subject':
				$where[] = 'UPPER(m.subject) ?LIKE? UPPER('.$DB->Escape('%'.$search.'%').')';
			break;
			case 'destination':
				$where[] = 'EXISTS (SELECT 1 FROM messageitems i
					WHERE i.messageid = m.id AND UPPER(i.destination) ?LIKE? UPPER('.$DB->Escape('%'.$search.'%').'))';
			break;
			case 'customerid':
				$where[] = 'EXISTS (SELECT 1 FROM messageitems i
					WHERE i.customerid = '.intval($search).' AND i.messageid = m.id)';
			break;
			case 'name':
				$where[] = 'EXISTS (SELECT 1 FROM messageitems i
					JOIN customers c ON (c.id = i.customerid)
					WHERE i.messageid = m.id AND UPPER(c.lastname) ?LIKE? UPPER('.$DB->Escape('%'.$search.'%').'))';
			break;
		}
	}
	
	if($type)
	{
		$type = intval($type);
		$where[] = 'm.type = '.$type;
        }
	
	if($status)
	{
		switch($status)
		{
			case MSG_NEW: $where[] = 'x.sent + x.delivered + x.error = 0'; break;
			case MSG_ERROR: $where[] = 'x.error > 0'; break;
			case MSG_SENT: $where[] = 'x.sent = x.cnt'; break;
			case MSG_DELIVERED: $where[] = 'x.delivered = x.cnt'; break;
		}
        }
	
	if(!empty($where))
		$where = 'WHERE '.implode(' AND ', $where);
	
	$result = $DB->GetAll('SELECT m.id, m.cdate, m.type, m.subject,
			x.cnt, x.sent, x.error, x.delivered
	    	FROM messages m
		JOIN (
			SELECT i.messageid, 
				COUNT(*) AS cnt,
				COUNT(CASE WHEN i.status = '.MSG_SENT.' THEN 1 ELSE NULL END) AS sent,
				COUNT(CASE WHEN i.status = '.MSG_DELIVERED.' THEN 1 ELSE NULL END) AS delivered,
				COUNT(CASE WHEN i.status = '.MSG_ERROR.' THEN 1 ELSE NULL END) AS error
			FROM messageitems i
			LEFT JOIN (
				SELECT DISTINCT a.customerid FROM customerassignments a
			        JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
				WHERE e.userid = lms_current_user()
			) e ON (e.customerid = i.customerid) 
			WHERE e.customerid IS NULL 
			GROUP BY i.messageid
		) x ON (x.messageid = m.id) '
		.(!empty($userjoin) ? 'JOIN users u ON (u.id = m.userid) ' : '')
		.(!empty($where) ? $where : '')
    		.$sqlord.' '.$direction);

	$result['type'] = $type;
	$result['status'] = $status;
	$result['order'] = $order;
	$result['direction'] = $direction;

	return $result;
}

$layout['pagetitle'] = trans('Messages List');

if(isset($_POST['search']))
	$s = $_POST['search'];
else
	$SESSION->restore('mls', $s);
$SESSION->save('mls', $s);

if(isset($_POST['cat']))
	$c = $_POST['cat'];
else
	$SESSION->restore('mlc', $c);
$SESSION->save('mlc', $c);

if(isset($_GET['o']))
	$o = $_GET['o'];
else
	$SESSION->restore('mlo', $o);
$SESSION->save('mlo', $o);

if(isset($_POST['type']))
	$t = $_POST['type'];
else
	$SESSION->restore('mlt', $t);
$SESSION->save('mlt', $t);

if(isset($_POST['status']))
	$status = $_POST['status'];
else
	$SESSION->restore('mlst', $status);
$SESSION->save('mlst', $status);

if(!empty($_GET['cid']))
{
	$s = $_GET['cid'];
	$c = 'customerid';
	$o = $t = $status = NULL;
}

$messagelist = GetMessagesList($o, $s, $c, $t, $status);

$listdata['type'] = $messagelist['type'];
$listdata['status'] = $messagelist['status'];
$listdata['order'] = $messagelist['order'];
$listdata['direction'] = $messagelist['direction'];
$listdata['cat'] = $c;
$listdata['search'] = $s;

unset($messagelist['type']);
unset($messagelist['status']);
unset($messagelist['order']);
unset($messagelist['direction']);

$listdata['total'] = sizeof($messagelist);

if ($SESSION->is_set('mlp') && !isset($_GET['page']))
        $SESSION->restore('mlp', $_GET['page']);
	
$page = (empty($_GET['page']) ? 1 : $_GET['page']);
$pagelimit = ConfigHelper::getConfig('phpui.messagelist_pagelimit', $listdata['total']);
$SESSION->save('mlp', $page);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('start',($page - 1) * $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('messagelist',$messagelist);
$SMARTY->display('message/messagelist.html');

?>
