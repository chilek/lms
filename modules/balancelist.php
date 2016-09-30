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

function GetBalanceList($search=NULL, $cat=NULL, $group=NULL, $pagelimit=100, $page=NULL, $from, $to) {
	global $DB;

	$where = '';

	if($search && $cat)
        {
		switch($cat)
		{
			case 'value':
				$val = intval($search) > 0 ? intval($search) : intval($search)*-1;
				$where = ' AND ABS(cash.value) = '.$val;
				break;
			case 'number':
				$where = ' AND documents.number = '.intval($search);
				break;
			case 'cdate':
				$where = ' AND cash.time >= '.intval($search).' AND cash.time < '.(intval($search)+86400);
				break;
			case 'ten':
				$where = ' AND c.ten = '.$DB->Escape($search);
				break;
			case 'customerid':
				$where = ' AND cash.customerid = '.intval($search);
				break;
			case 'name':
				$where = ' AND '.$DB->Concat('UPPER(c.lastname)',"' '",'c.name').' ?LIKE? '.$DB->Escape("%$search%");
				break;
			case 'address':
				$where = ' AND c.address ?LIKE? '.$DB->Escape("%$search%");
				break;
			case 'comment':
				$where = ' AND cash.comment ?LIKE? '.$DB->Escape("%$search%");
				break;
			case 'cashimport':
				$where = ' AND cash.importid IN (SELECT i.id FROM cashimport i JOIN sourcefiles f ON f.id = i.sourcefileid WHERE f.name = ' . $DB->Escape("$search") . ')';
				break;
		}
	}
	elseif($cat)
	{
		switch($cat)
		{
			case 'documented': $where = ' AND cash.docid > 0'; break;
			case 'notdocumented': $where = ' AND cash.docid = 0'; break;
		}
	}

	if($from)
        	$where .= ' AND cash.time >= '.intval($from);
	if($to)
		$where .= ' AND cash.time <= '.intval($to);

	if($res = $DB->Exec('SELECT cash.id AS id, time, cash.userid AS userid, cash.value AS value, 
				cash.customerid AS customerid, comment, docid, cash.type AS type,
				documents.type AS doctype, documents.closed AS closed,
				documents.published, '
				.$DB->Concat('UPPER(c.lastname)',"' '",'c.name').' AS customername
				FROM cash
				LEFT JOIN customers c ON (cash.customerid = c.id)
				LEFT JOIN documents ON (documents.id = docid)
				LEFT JOIN (
				        SELECT DISTINCT a.customerid
					FROM customerassignments a
					JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
					WHERE e.userid = lms_current_user()
				) e ON (e.customerid = cash.customerid)
				WHERE e.customerid IS NULL'
				.$where
				.(!empty($group['group']) ? 
					' AND '.(!empty($group['exclude']) ? 'NOT' : '').' EXISTS (
					SELECT 1 FROM customerassignments WHERE customergroupid = '.intval($group['group']).'
					AND customerid = cash.customerid)' : '')
				.' ORDER BY time, cash.id'))
	{
		$userlist = $DB->GetAllByKey('SELECT id, name FROM users','id');

		$balancelist['liability'] = 0;
		$balancelist['expense'] = 0;
		$balancelist['income'] = 0;
		if ($page > 0) {
		    $start =  ($page - 1) * $pagelimit;
		    $stop = $start + $pagelimit;
		}
		$id = 0;
		$after = 0;

		while($row = $DB->FetchRow($res))
		{
			$balancelist[$id]['user'] = isset($userlist[$row['userid']]['name']) ? $userlist[$row['userid']]['name'] : '';
			$balancelist[$id]['before'] = $after;

			if($row['customerid'] && $row['type'] == 0)
			{
				// customer covenant
				$balancelist[$id]['after'] = $balancelist[$id]['before'];
				$balancelist[$id]['covenant'] = true;
				$balancelist['liability'] -= $row['value'];
			}
			else
			{
				$balancelist[$id]['after'] = $balancelist[$id]['before'] + $row['value'];
				if($row['value'] > 0)
					//income
					$balancelist['income'] += $row['value'];
				else
					//expense
					$balancelist['expense'] += -$row['value'];
			}

			$balancelist[$id] = array_merge($balancelist[$id], $row);
			$after = $balancelist[$id]['after'];

			// free memory for rows which will not be displayed
			if($page > 0)
			{
				if(($id < $start - 1 || $id > $stop) && isset($balancelist[$id]))
					$balancelist[$id] = NULL;
			}
			elseif(isset($balancelist[$id-$pagelimit]))
				$balancelist[$id-$pagelimit] = NULL;
			
			$id++;
		}
	
		$balancelist['totalval'] = $balancelist['income'] - $balancelist['expense'];
		$balancelist['page'] = $page > 0 ? $page : ceil($id / $pagelimit);
		$balancelist['total'] = $id;

		return $balancelist;
	}
}

if(isset($_POST['search']))
        $s = $_POST['search'];
else
	$SESSION->restore('bls', $s);
if(!isset($s))
     {
     $year=date("Y", time());
     $month=date("m", time());
     $day=date("d", time());
     $s = $year.'/'.$month.'/'.$day;
     }
$SESSION->save('bls', $s);

if(isset($_POST['cat']))
        $c = $_POST['cat'];
else
	$SESSION->restore('blc', $c);
if (!isset($c))
{
$c="cdate";
}
$SESSION->save('blc', $c);

if(isset($_POST['group']))
{
        $g = $_POST['group'];
	$ge = isset($_POST['groupexclude']) ? 1 : 0;
} else {
        $SESSION->restore('blg', $g);
        $SESSION->restore('blge', $ge);
}
$SESSION->save('blg', $g);
$SESSION->save('blge', $ge);
				
if($c == 'cdate' && $s)
{
        list($year, $month, $day) = explode('/', $s);
	$s = mktime(0,0,0, (int)$month, (int)$day, (int)$year);
}

if(!empty($_POST['from']))
{
	list($year, $month, $day) = explode('/', $_POST['from']);
	$from = mktime(0,0,0, $month, $day, $year);
}
elseif($SESSION->is_set('blf'))
	$SESSION->restore('blf', $from);
else
	$from = '';
$SESSION->save('blf', $from);

if(!empty($_POST['to']))
{
	list($year, $month, $day) = explode('/', $_POST['to']);
	$to = mktime(23,59,59, $month, $day, $year);
}
elseif($SESSION->is_set('blt'))
	$SESSION->restore('blt', $to);
else
	$to = '';
$SESSION->save('blt', $to);

$pagelimit = ConfigHelper::getConfig('phpui.balancelist_pagelimit');
$page = (empty($_GET['page']) ? 0 : intval($_GET['page']));

if (isset($_GET['sourcefileid'])) {
	$s = $DB->GetOne('SELECT name FROM sourcefiles WHERE id = ?', array($_GET['sourcefileid']));
	$c = 'cashimport';
	$SESSION->save('bls', $s);
	$SESSION->save('blc', $c);
}

$balancelist = GetBalanceList($s, $c, array('group' => $g, 'exclude'=> $ge), $pagelimit, $page, $from, $to);

$listdata['liability'] = $balancelist['liability'];
$listdata['income'] = $balancelist['income'];
$listdata['expense'] = $balancelist['expense'];
$listdata['totalval'] = $balancelist['totalval'];
$listdata['total'] = $balancelist['total'];
$page = $balancelist['page'];

unset($balancelist['liability']);
unset($balancelist['income']);
unset($balancelist['expense']);
unset($balancelist['totalval']);
unset($balancelist['total']);
unset($balancelist['page']);

$SESSION->restore('blc', $listdata['cat']);
$SESSION->restore('bls', $listdata['search']);
$SESSION->restore('blg', $listdata['group']);
$SESSION->restore('blge', $listdata['groupexclude']);
$SESSION->restore('blf', $listdata['from']);
$SESSION->restore('blt', $listdata['to']);

$layout['pagetitle'] = trans('Balance Sheet');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('balancelist',$balancelist);
$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('start', ($page - 1) * $pagelimit);
$SMARTY->assign('page',$page);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('grouplist',$LMS->CustomergroupGetAll());
$SMARTY->display('balance/balancelist.html');

?>
