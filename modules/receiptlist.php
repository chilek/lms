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

function GetReceiptList($registry, $order='', $search=NULL, $cat=NULL, $from=0, $to=0, $advances=0)
{
	global $DB;

	list($order,$direction) = sscanf($order, '%[^,],%s');

	($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

	switch($order)
	{
		case 'number':
			$sqlord = " ORDER BY documents.number $direction";
		break;
		case 'name':
			$sqlord = " ORDER BY documents.name $direction, documents.cdate";
		break;
		case 'user':
			$sqlord = " ORDER BY vusers.lastname $direction, documents.cdate";
		break;
		case 'cdate':
		default:
			$sqlord = " ORDER BY documents.cdate $direction, number";
		break;
	}

	$where = ''; $having = '';

	if($search && $cat)
	{
		switch($cat)
		{
			case 'value':
				$having = ' HAVING SUM(value) = '.$DB->Escape(str_replace(',','.',$search));
				break;
			case 'number':
				$where = ' AND number = '.intval($search);
				break;
			case 'ten':
				$where = ' AND ten = '.$DB->Escape($search);
				break;
			case 'customerid':
				$where = ' AND customerid = '.intval($search);
				break;
			case 'name':
				$where = ' AND documents.name ?LIKE? '.$DB->Escape('%'.$search.'%');
				break;
			case 'address':
				$where = ' AND address ?LIKE? '.$DB->Escape('%'.$search.'%');
				break;
		}
	}

	if($from)
		$where .= ' AND cdate >= '.intval($from);
	if($to)
		$where .= ' AND cdate <= '.intval($to);

	if($advances)
		$where = ' AND closed = 0';

	if($list = $DB->GetAll(
	        'SELECT documents.id AS id, SUM(value) AS value, number, cdate, customerid,
		documents.name AS customer, address, zip, city, template, extnumber, closed,
		MIN(description) AS title, COUNT(*) AS posnumber, vusers.name AS user
		FROM documents
		LEFT JOIN numberplans ON (numberplanid = numberplans.id)
		LEFT JOIN vusers ON (userid = vusers.id)
		LEFT JOIN receiptcontents ON (documents.id = docid AND type = ?)
		WHERE regid = ?'
		.$where
		.' GROUP BY documents.id, number, cdate, customerid, documents.name, address, zip, city, template, vusers.name, extnumber, closed '
		.$having
		.($sqlord != '' ? $sqlord : ''),
		array(DOC_RECEIPT, $registry)
		))
	{
		$totalincome = 0;
		$totalexpense = 0;

		foreach($list as $idx => $row)
		{
			$list[$idx]['number'] = docnumber(array(
				'number' => $row['number'],
				'template' => $row['template'],
				'cdate' => $row['cdate'],
				'ext_num' => $row['extnumber'],
				'customerid' => $row['customerid'],
			));
			$list[$idx]['customer'] = $row['customer'].' '.$row['address'].' '.$row['zip'].' '.$row['city'];

			// don't retrive descriptions of all items to not decrease speed
			// but we want to know that there is something hidden ;)
			if($row['posnumber'] > 1) $list[$idx]['title'] .= ' ...';

			// summary
			if($row['value'] > 0)
				$totalincome += $row['value'];
			else
				$totalexpense += -$row['value'];
		}

		$list['totalincome'] = $totalincome;
		$list['totalexpense'] = $totalexpense;
		$list['order'] = $order;
		$list['direction'] = $direction;

		return $list;
	}
}

$SESSION->restore('rlm', $marks);
$marked = isset($_POST['marks']) ? $_POST['marks'] : array();
if(sizeof($marked))
        foreach($marked as $id => $mark)
	        $marks[$id] = $mark;
$SESSION->save('rlm', $marks);

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

if(isset($_GET['regid']))
	$regid = $_GET['regid'];
else
	$SESSION->restore('rlreg', $regid);
$SESSION->save('rlreg', $regid);

if(isset($_POST['from']))
{
	if($_POST['from'] != '')
	{
		list($year, $month, $day) = explode('/', $_POST['from']);
		$from = mktime(0,0,0, $month, $day, $year);
	}
}
elseif($SESSION->is_set('rlf'))
	$SESSION->restore('rlf', $from);
else
	$from = mktime(0,0,0);
$SESSION->save('rlf', $from);

if(isset($_POST['to']))
{
	if($_POST['to'] != '')
	{
		list($year, $month, $day) = explode('/', $_POST['to']);
		$to = mktime(23,59,59, $month, $day, $year);
	}
}
elseif($SESSION->is_set('rlt'))
	$SESSION->restore('rlt', $to);
else
	$to = mktime(23,59,59);
$SESSION->save('rlt', $to);

if(isset($_POST['advances']))
	$a = 1;
else
	$a = 0;
$SESSION->save('rla', $a);

if(!$regid)
{
	$SESSION->redirect('?m=cashreglist');
}

if (!$DB->GetOne('SELECT rights FROM cashrights WHERE userid = ? AND regid = ? AND (rights & 1) > 0',
	array($AUTH->id, $regid))) {
	$SMARTY->display('noaccess.html');
	$SESSION->close();
	die;
}

$receiptlist = GetReceiptList($regid, $o, $s, $c, $from, $to, $a);

$SESSION->restore('rlc', $listdata['cat']);
$SESSION->restore('rls', $listdata['search']);
$SESSION->restore('rlf', $listdata['from']);
$SESSION->restore('rlt', $listdata['to']);
$SESSION->restore('rla', $listdata['advances']);

$listdata['order'] = $receiptlist['order'];
$listdata['direction'] = $receiptlist['direction'];
$listdata['totalincome'] = $receiptlist['totalincome'];
$listdata['totalexpense'] = $receiptlist['totalexpense'];
$listdata['regid'] = $regid;

unset($receiptlist['order']);
unset($receiptlist['direction']);
unset($receiptlist['totalincome']);
unset($receiptlist['totalexpense']);

$listdata['total'] = sizeof($receiptlist);
$listdata['cashstate'] = $DB->GetOne('SELECT SUM(value) FROM receiptcontents WHERE regid=?', array($regid));
if($from > 0)
	$listdata['startbalance'] = $DB->GetOne(
		'SELECT SUM(value) FROM receiptcontents
		LEFT JOIN documents ON (docid = documents.id AND type = ?)
		WHERE cdate < ? AND regid = ?',
		array(DOC_RECEIPT, $from, $regid));

$listdata['endbalance'] = $listdata['startbalance'] + $listdata['totalincome'] - $listdata['totalexpense'];

$pagelimit = ConfigHelper::getConfig('phpui.receiptlist_pagelimit');
$page = (!isset($_GET['page']) ? ceil($listdata['total']/$pagelimit) : $_GET['page']);
$start = ($page - 1) * $pagelimit;

$logentry = $DB->GetRow('SELECT * FROM cashreglog WHERE regid = ?
			ORDER BY time DESC LIMIT 1', array($regid, $regid));

$layout['pagetitle'] = trans('Cash Registry: $a', $DB->GetOne('SELECT name FROM cashregs WHERE id=?', array($regid)));

$SESSION->save('backto', 'm=receiptlist&regid='.$regid);

if($receipt = $SESSION->get('receiptprint'))
{
	$SMARTY->assign('receipt', $receipt);
	$SESSION->remove('receiptprint');
}
$SMARTY->assign('logentry', $logentry);
$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('start',$start);
$SMARTY->assign('page',$page);
$SMARTY->assign('marks',$marks);
$SMARTY->assign('receiptlist',$receiptlist);
$SMARTY->display('receipt/receiptlist.html');

?>
