<?php

/*
 * LMS version 1.9-cvs
 *
 *  (C) Copyright 2001-2006 LMS Developers
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
	global $DB, $LMS;

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
				$where = ' AND customers.ten = \''.$search.'\'';
			break;
			case 'customerid':
				$where = ' AND cash.customerid = '.intval($search);
			break;
			case 'name':
				$where = ' AND '.$DB->Concat('UPPER(customers.lastname)',"' '",'customers.name').' ?LIKE? \'%'.$search.'%\'';
			break;
			case 'address':
				$where = ' AND customers.address ?LIKE? \'%'.$search.'%\'';
			break;
		}
	}
	
	if($balancelist = $DB->GetAll('SELECT cash.id AS id, time, cash.userid AS userid, cash.value AS value, 
				cash.customerid AS customerid, comment, docid, taxid, cash.type AS type,
				documents.type AS doctype, documents.closed AS closed, '
				.$DB->Concat('UPPER(customers.lastname)',"' '",'customers.name').' AS customername
				FROM cash
				LEFT JOIN customers ON (cash.customerid = customers.id)
				LEFT JOIN documents ON (documents.id = docid)
				LEFT JOIN taxes ON (taxid = taxes.id) '
				.($where ? 'WHERE 1=1 '.$where : '')
				.' ORDER BY time, cash.id'))
	{
		$taxeslist = $LMS->GetTaxes();
		$userlist = $DB->GetAllByKey('SELECT id, name FROM users','id');

    		if($group['group'])
	        {
		        $customers = $DB->GetAllByKey('SELECT customerid AS id
					FROM customerassignments WHERE customergroupid=?',
					'id', array($group['group']));

/*			foreach($result as $idx => $row)
			{
			if(!$group['exclude'] && $customers[$result[$idx]['customerid']])
				$result1[] = $result[$idx];
			elseif($group['exclude'] && !$customers[$result[$idx]['customerid']])
				$result1[] = $result[$idx];
			}
			$result = $result1;
*/		}
			
		foreach($balancelist as $idx => $row)
		{
			$balancelist[$idx]['user'] = $userlist[$row['userid']]['name'];
			$balancelist[$idx]['tax'] = $taxeslist[$row['taxid']]['label'];
			$balancelist[$idx]['before'] = $balancelist[$idx-1]['after'];
			$balancelist[$idx]['value'] = $row['value'];

			if($row['customerid'] && $row['type'] == 0)
			{
				// customer covenant
				$balancelist[$idx]['after'] = $balancelist[$idx]['before'];
				$balancelist[$idx]['covenant'] = true;
				$balancelist['liability'] -= $row['value'];
			}
			else
			{
				$balancelist[$idx]['after'] = $balancelist[$idx]['before'] + $row['value'];
				if($row['value'] > 0)
					//income
					$balancelist['income'] += $row['value'];
				else
					//expense
					$balancelist['expense'] += -$row['value'];
			}
		}
		$balancelist['total'] = $balancelist['income'] - $balancelist['expense'];
	}
	
	return $balancelist;
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
	$ge = $_POST['groupexclude'];
} else {
        $SESSION->restore('blg', $g);
        $SESSION->restore('blge', $ge);
}
$SESSION->save('blg', $g);
$SESSION->save('blge', $ge);
				
if($c == 'cdate' && $s)
{
        list($year, $month, $day) = explode('/', $s);
	$s = mktime(0,0,0, $month, $day, $year);
}

$balancelist = GetBalanceList($s, $c, array('group' => $g, 'exclude'=> $ge));

$listdata['liability'] = $balancelist['liability'];
$listdata['income'] = $balancelist['income'];
$listdata['expense'] = $balancelist['expense'];
$listdata['total'] = $balancelist['total'];
unset($balancelist['liability']);
unset($balancelist['income']);
unset($balancelist['expense']);
unset($balancelist['total']);

$listdata['totalpos'] = sizeof($balancelist);

$SESSION->restore('blc', $listdata['cat']);
$SESSION->restore('bls', $listdata['search']);
$SESSION->restore('blg', $listdata['group']);
$SESSION->restore('blge', $listdata['groupexclude']);

$pagelimit = $LMS->CONFIG['phpui']['balancelist_pagelimit'];
$page = (! $_GET['page'] ? ceil($listdata['totalpos']/$pagelimit) : $_GET['page']); 
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
