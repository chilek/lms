<?php

/*
 * LMS version 1.11-cvs
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

function GetInvoicesList($search=NULL, $cat=NULL, $group=NULL, $order)
{
	global $DB;
	
	if($order=='')
		$order='id,asc';
	
	list($order,$direction) = sscanf($order, '%[^,],%s');
	($direction=='desc') ? $direction = 'desc' : $direction = 'asc';
	
	switch($order)
	{
		case 'id':
			$sqlord = ' ORDER BY d.id';
		break;
		case 'cdate':
			$sqlord = ' ORDER BY d.cdate';
		break;
		case 'number':
			$sqlord = ' ORDER BY number';
		break;
		case 'value':
			$sqlord = ' ORDER BY value';
		break;
		case 'count':
			$sqlord = ' ORDER BY count';
		break;
		case 'name':
			$sqlord = ' ORDER BY name';
		break;
	}
	
	$where = '';
	
	if($search!='' && $cat)
        {
	        switch($cat)
		{
			case 'number':
				$where = ' AND number = '.intval($search);
			break;
			case 'cdate':
				$where = ' AND cdate >= '.$search.' AND cdate < '.($search+86400);
			break;
			case 'ten':
			        $where = ' AND ten = \''.$search.'\'';
			break;
			case 'customerid':
				$where = ' AND customerid = '.intval($search);
			break;
			case 'name':
				$where = ' AND UPPER(name) ?LIKE? UPPER(\'%'.$search.'%\')';
			break;
			case 'address':
				$where = ' AND UPPER(address) ?LIKE? UPPER(\'%'.$search.'%\')';
			break;
		}
	}
        
	if($cat=='notclosed')
		$where = ' AND closed = 0';

	if($result = $DB->GetAll('SELECT d.id AS id, number, cdate, type,
			customerid, name, address, zip, city, template, closed, 
			CASE reference WHEN 0 THEN
			    SUM(a.value*a.count) 
			ELSE
			    SUM((a.value+b.value)*(a.count+b.count)) - SUM(b.value*b.count)
			END AS value, 
			COUNT(a.docid) AS count
	    		FROM documents d
			LEFT JOIN invoicecontents a ON (a.docid = d.id)
			LEFT JOIN invoicecontents b ON (d.reference = b.docid AND a.itemid = b.itemid)
			LEFT JOIN numberplans ON (d.numberplanid = numberplans.id)
			WHERE (type = '.DOC_CNOTE.(($cat != 'cnotes') ? ' OR type = '.DOC_INVOICE : '').')'
			.$where
			.' AND NOT EXISTS (
			        SELECT 1 FROM customerassignments a
				JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
				WHERE e.userid = lms_current_user() AND a.customerid = d.customerid)'
			.' GROUP BY d.id, number, cdate, customerid, 
			name, address, zip, city, template, closed, type, reference '
	    		.$sqlord.' '.$direction))
	{
		$result1 = array();

		if($search!='' && $cat=='value')
		{
			$val = f_round($search);

			foreach($result as $idx => $row)
			{
				if($row['value']==$val)
					$result1[] = $result[$idx];
			}

			$result = $result1;
			unset($result1);
		}

		if($group['group'])
		{
			$customers = $DB->GetAllByKey('SELECT customerid AS id
		        	FROM customerassignments WHERE customergroupid=?', 
				'id', array($group['group']));
		
			foreach($result as $idx => $row)
			{
				if(!$group['exclude'] && $customers[$result[$idx]['customerid']])
					$result1[] = $result[$idx];
				elseif($group['exclude'] && !$customers[$result[$idx]['customerid']])
					$result1[] = $result[$idx];
			}

			$result = $result1;
			unset($result1);
		}
	}

	$result['order'] = $order;
	$result['direction'] = $direction;
	return $result;
}																																																																																																					       

$layout['pagetitle'] = trans('Invoices List');
$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SESSION->restore('ilm', $marks);
if(isset($_POST['marks']))
	foreach($_POST['marks'] as $id => $mark)
		$marks[$id] = $mark;
$SESSION->save('ilm', $marks);

if(isset($_POST['search']))
	$s = $_POST['search'];
else
	$SESSION->restore('ils', $s);
$SESSION->save('ils', $s);

if(isset($_GET['o']))
	$o = $_GET['o'];
else
	$SESSION->restore('ilo', $o);
$SESSION->save('ilo', $o);

if(isset($_POST['cat']))
	$c = $_POST['cat'];
else
	$SESSION->restore('ilc', $c);
$SESSION->save('ilc', $c);

if(isset($_POST['group'])) {
	$g = $_POST['group'];
	$ge = isset($_POST['groupexclude']) ? $_POST['groupexclude'] : NULL;
} else {
	$SESSION->restore('ilg', $g);
	$SESSION->restore('ilge', $ge);
}
$SESSION->save('ilg', $g);
$SESSION->save('ilge', $ge);

if($c == 'cdate' && $s)
{
	list($year, $month, $day) = explode('/', $s);
	$s = mktime(0,0,0, $month, $day, $year);
}

$invoicelist = GetInvoicesList($s, $c, array('group' => $g, 'exclude'=> $ge), $o);

$SESSION->restore('ilc', $listdata['cat']);
$SESSION->restore('ils', $listdata['search']);
$SESSION->restore('ilg', $listdata['group']);
$SESSION->restore('ilge', $listdata['groupexclude']);
$listdata['order'] = $invoicelist['order'];
$listdata['direction'] = $invoicelist['direction'];
unset($invoicelist['order']);
unset($invoicelist['direction']);

$listdata['total'] = sizeof($invoicelist);

$pagelimit = $CONFIG['phpui']['invoicelist_pagelimit'];
$page = !isset($_GET['page']) ? ceil($listdata['total']/$pagelimit) : intval($_GET['page']);
$start = ($page - 1) * $pagelimit;

$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('start',$start);
$SMARTY->assign('page',$page);
$SMARTY->assign('marks',$marks);
$SMARTY->assign('grouplist',$LMS->CustomergroupGetAll());
$SMARTY->assign('invoicelist',$invoicelist);
$SMARTY->display('invoicelist.html');

?>
