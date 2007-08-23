<?php

/*
 * LMS version 1.10-cvs
 *
 *  (C) Copyright 2001-2007 LMS Developers
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

function GetBalanceList($search=NULL, $cat=NULL, $group=NULL)
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
				.' ORDER BY time, cash.id'))
	{
		$userlist = $DB->GetAllByKey('SELECT id, name FROM users','id');

    		if($group['group'])
		        $customers = $DB->GetAllByKey('SELECT customerid AS id
					    FROM customerassignments WHERE customergroupid=?',
					    'id', array($group['group']));

		$balancelist['liability'] = 0;
		$balancelist['expense'] = 0;
		$balancelist['income'] = 0;

		$id = 0;
		while($row = $DB->FetchRow($res))
		{
			if($group['group'])
			{
				if(!$group['exclude'] && !isset($customers[$row['customerid']]))
					continue;
				elseif($group['exclude'] && isset($customers[$row['customerid']]))
					continue;
			}

			$balancelist[$id] = $row;
			$balancelist[$id]['user'] = isset($userlist[$row['userid']]['name']) ? $userlist[$row['userid']]['name'] : '';
			$balancelist[$id]['before'] = isset($balancelist[$id-1]['after']) ? $balancelist[$id-1]['after'] : 0;
			$balancelist[$id]['value'] = $row['value'];

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
			$id++;
		}
	
		$balancelist['totalval'] = $balancelist['income'] - $balancelist['expense'];
	
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

$balancelist = GetBalanceList($s, $c, array('group' => $g, 'exclude'=> $ge));

$listdata['liability'] = $balancelist['liability'];
$listdata['income'] = $balancelist['income'];
$listdata['expense'] = $balancelist['expense'];
$listdata['totalval'] = $balancelist['totalval'];
unset($balancelist['liability']);
unset($balancelist['income']);
unset($balancelist['expense']);
unset($balancelist['totalval']);

$listdata['total'] = sizeof($balancelist);

$SESSION->restore('blc', $listdata['cat']);
$SESSION->restore('bls', $listdata['search']);
$SESSION->restore('blg', $listdata['group']);
$SESSION->restore('blge', $listdata['groupexclude']);

$pagelimit = $CONFIG['phpui']['balancelist_pagelimit'];
$page = (! isset($_GET['page']) ? ceil($listdata['total']/$pagelimit) : intval($_GET['page'])); 
$start = ($page - 1) * $pagelimit;

$layout['pagetitle'] = trans('Balance Sheet');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('balancelist',$balancelist);
$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('start',$start);
$SMARTY->assign('page',$page);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('grouplist',$LMS->CustomergroupGetAll());
$SMARTY->display('balancelist.html');

?>
