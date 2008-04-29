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
			case 'month':
				$last = mktime(23,59,59, date('n',$search) + 1, 0, date('Y', $search));
				$where = ' AND cdate >= '.$search.' AND cdate <= '.$last;
			break;
			case 'ten':
			        $where = ' AND ten = \''.$search.'\'';
			break;
			case 'customerid':
				$where = ' AND d.customerid = '.intval($search);
			break;
			case 'name':
				$where = ' AND UPPER(name) ?LIKE? UPPER(\'%'.$search.'%\')';
			break;
			case 'address':
				$where = ' AND UPPER(address) ?LIKE? UPPER(\'%'.$search.'%\')';
			break;
			case 'value':
				$having = ' HAVING CASE reference WHEN 0 THEN
					    SUM(a.value*a.count) 
					    ELSE
					    SUM((a.value+b.value)*(a.count+b.count)) - SUM(b.value*b.count)
					    END = '.str_replace(',','.',f_round($search)).' ';
			break;
		}
	}
        
	if($cat=='notclosed')
		$where = ' AND closed = 0';

	$result = $DB->GetAll('SELECT d.id AS id, number, cdate, type,
			d.customerid, name, address, zip, city, template, closed, 
			CASE reference WHEN 0 THEN
			    SUM(a.value*a.count) 
			ELSE
			    SUM((a.value+b.value)*(a.count+b.count)) - SUM(b.value*b.count)
			END AS value, 
			COUNT(a.docid) AS count
	    		FROM documents d
			JOIN invoicecontents a ON (a.docid = d.id)
			LEFT JOIN invoicecontents b ON (d.reference = b.docid AND a.itemid = b.itemid)
			LEFT JOIN numberplans ON (d.numberplanid = numberplans.id)
			LEFT JOIN (
				SELECT DISTINCT a.customerid FROM customerassignments a
			        JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
				WHERE e.userid = lms_current_user()
				) e ON (e.customerid = d.customerid) 
			WHERE e.customerid IS NULL AND 
				(type = '.DOC_CNOTE.(($cat != 'cnotes') ? ' OR type = '.DOC_INVOICE : '').')'
			.$where
			.(!empty($group['group']) ?
			            ' AND '.(!empty($group['exclude']) ? 'NOT' : '').' EXISTS (
			            SELECT 1 FROM customerassignments WHERE customergroupid = '.intval($group['group']).'
			            AND customerid = d.customerid)' : '')
			.' GROUP BY d.id, number, cdate, d.customerid, 
			name, address, zip, city, template, closed, type, reference '
			.(isset($having) ? $having : '')
	    		.$sqlord.' '.$direction);

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

if($c == 'cdate' && $s && ereg('^[0-9]{4}/[0-9]{2}/[0-9]{2}$', $s))
{
	list($year, $month, $day) = explode('/', $s);
	$s = mktime(0,0,0, $month, $day, $year);
}
elseif($c == 'month' && $s && ereg('^[0-9]{4}/[0-9]{2}$', $s))
{
	list($year, $month) = explode('/', $s);
        $s = mktime(0,0,0, $month, 1, $year);
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
$SMARTY->assign('newinvoice', isset($_GET['invoice']) ? $_GET['invoice'] : NULL);
$SMARTY->assign('original', isset($_GET['original']) ? TRUE : FALSE);
$SMARTY->assign('copy', isset($_GET['copy']) ? TRUE : FALSE);
$SMARTY->assign('duplicate', isset($_GET['duplicate']) ? TRUE : FALSE);
$SMARTY->display('invoicelist.html');

echo memory_get_peak_usage();
?>
