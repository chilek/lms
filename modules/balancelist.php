<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2008 LMS Developers
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

function GetBalanceList($search=NULL, $cat=NULL, $group=NULL, $pagelimit=100, $page=NULL)
{
	global $DB;

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
				$where = ' AND cash.time >= '.$search.' AND cash.time < '.($search+86400);
			break;
			case 'ten':
				$where = ' AND c.ten = \''.$search.'\'';
			break;
			case 'customerid':
				$where = ' AND cash.customerid = '.intval($search);
			break;
			case 'name':
				$where = ' AND '.$DB->Concat('UPPER(c.lastname)',"' '",'c.name').' ?LIKE? \'%'.$search.'%\'';
			break;
			case 'address':
				$where = ' AND c.address ?LIKE? \'%'.$search.'%\'';
			break;
		}
	}

	if($res = $DB->Exec('SELECT cash.id AS id, time, cash.userid AS userid, cash.value AS value, 
				cash.customerid AS customerid, comment, docid, cash.type AS type,
				documents.type AS doctype, documents.closed AS closed, '
				.$DB->Concat('UPPER(c.lastname)',"' '",'c.name').' AS customername
				FROM cash
				LEFT JOIN customers c ON (cash.customerid = c.id)
				LEFT JOIN documents ON (documents.id = docid) '
				.' WHERE NOT EXISTS (
				        SELECT 1 FROM customerassignments a
					JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
					WHERE e.userid = lms_current_user() AND a.customerid = cash.customerid)'
				.(isset($where) ? $where : '')
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

		while($row = $DB->FetchRow($res))
		{
			$balancelist[$id]['user'] = isset($userlist[$row['userid']]['name']) ? $userlist[$row['userid']]['name'] : '';
			$balancelist[$id]['before'] = isset($balancelist[$id-1]['after']) ? $balancelist[$id-1]['after'] : 0;

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
$SESSION->save('bls', $s);

if(isset($_POST['cat']))
        $c = $_POST['cat'];
else
	$SESSION->restore('blc', $c);
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

$pagelimit = $CONFIG['phpui']['balancelist_pagelimit'];
$page = (empty($_GET['page']) ? 0 : intval($_GET['page']));

$balancelist = GetBalanceList($s, $c, array('group' => $g, 'exclude'=> $ge), $pagelimit, $page);

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

$layout['pagetitle'] = trans('Balance Sheet');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('balancelist',$balancelist);
$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('start', ($page - 1) * $pagelimit);
$SMARTY->assign('page',$page);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('grouplist',$LMS->CustomergroupGetAll());
$SMARTY->display('balancelist.html');

?>
