<?php

/*
 * LMS version 1.7-cvs
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

function GetReceiptList($order='cdate,asc', $search=NULL, $cat=NULL)
{
	global $DB, $LMS;

	list($order,$direction) = sscanf($order, '%[^,],%s');

	($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

	switch($order)
	{
		case 'id':
			$sqlord = " ORDER BY documents.id $direction";
		break;
		case 'customername':
			$sqlord = " ORDER BY customername $direction, documents.cdate";
		break;
		case 'value':
			$sqlord = " ORDER BY value $direction, documents.name, documents.cdate";
		break;
		default:
			$sqlord = " ORDER BY documents.cdate $direction";
		break;
	}

	if($search && $cat)
	{
		switch($cat)
		{
			case 'value':
				$where = ' SUM(value) = '.intval($search);
				break;
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
				$where = ' AND name ?LIKE? \'%'.$search.'%\'';
				break;
			case 'address':
				$where = ' AND address ?LIKE? \'%'.$search.'%\'';
				break;
		}
	}

	$ntempl = $LMS->CONFIG['receipts']['number_template'];

	if($list = $DB->GetAll(
	        'SELECT documents.id AS id, SUM(value) AS value, number, cdate, customerid, documents.name AS customername, address, zip, city 
		FROM documents LEFT JOIN receiptcontents ON (documents.id = docid AND type = 2) 
		WHERE 1=1 '
		.$where
		.' GROUP BY documents.id, number, cdate, customerid, name, address, zip, city '
		.($sqlord != '' ? $sqlord : '')
		))
	{
		foreach($list as $idx => $row)
		{
			$ntempl = str_replace('%N',$row['number'],$ntempl);
			$ntempl = str_replace('%M',date('m',$row['cdate']),$ntempl);
			$ntempl = str_replace('%Y',date('Y',$row['cdate']),$ntempl);
			$list[$idx]['number'] = $ntempl;
		}
		
		$list['order'] = $order;
		$list['direction'] = $direction;

		return $list;
	}
}

$layout['pagetitle'] = trans('Cash Receipts List');
$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$marks = $_POST['marks'];
unset($marked);
if( sizeof($marks) )
	foreach($marks as $marksid => $mark)
		$marked[] = $mark;

if(isset($_POST['search']))
	$s = $_POST['search'];
else
	$SESSION->restore('rls', $s);
$SESSION->save('rls', $s);

if(isset($_GET['o']))
	$o = $_GET['o'];
else
	$SESSION->restore('rlo', $o);
$SESSION->save('rlo', $o);

if(isset($_POST['cat']))
	$c = $_POST['cat'];
else
	$SESSION->restore('rlc', $c);
$SESSION->save('rlc', $c);

if($c == 'cdate' && $s)
{
	list($year, $month, $day) = explode('/', $s);
	$s = mktime(0,0,0, $month, $day, $year);
}

$receiptlist = GetReceiptList($o, $s, $c);

$SESSION->restore('rlc', $listdata['cat']);
$SESSION->restore('rls', $listdata['search']);
$listdata['order'] = $receiptlist['order'];
$listdata['direction'] = $receiptlist['direction'];
unset($receiptlist['order']);
unset($receiptlist['direction']);

$listdata['totalpos'] = sizeof($receiptlist);

$pagelimit = $LMS->CONFIG['phpui']['receiptlist_pagelimit'];
$page = (! $_GET['page'] ? ceil($listdata['totalpos']/$pagelimit) : $_GET['page']);
$start = ($page - 1) * $pagelimit;

$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('start',$start);
$SMARTY->assign('page',$page);
$SMARTY->assign('marks',$marks);
$SMARTY->assign('marked',$marked);
$SMARTY->assign('receiptlist',$receiptlist);
$SMARTY->display('receiptlist.html');

?>
