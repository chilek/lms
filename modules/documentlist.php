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

function GetDocumentList($order='cdate,asc', $search) {
	$type = array_key_exists('type', $search) ? $search['type'] : NULL;
	$customer = array_key_exists('customer', $search) ? $search['customer'] : NULL;
	$numberplan = array_key_exists('numberplan', $search) ? $search['numberplan'] : NULL;
	$from = array_key_exists('from', $search) ? $search['from'] : 0;
	$to = array_key_exists('to', $search) ? $search['to'] : 0;
	$status = array_key_exists('status', $search) ? $search['status'] : -1;

	global $DB, $AUTH;

	if($order=='')
		$order='cdate,asc';

	list($order,$direction) = sscanf($order, '%[^,],%s');
	($direction=='desc') ? $direction = 'desc' : $direction = 'asc';

	switch($order)
	{
		case 'type':
			$sqlord = ' ORDER BY d.type '.$direction.', d.name';
		break;
		case 'title':
			$sqlord = ' ORDER BY title '.$direction.', d.name';
		break;
		case 'customer':
			$sqlord = ' ORDER BY d.name '.$direction.', title';
		break;
		default:
			$sqlord = ' ORDER BY d.cdate '.$direction.', d.name';
		break;
	}

	$list = $DB->GetAll('SELECT docid, d.number, d.type, title, d.cdate, fromdate, todate, description, 
				filename, md5sum, contenttype, template, d.closed, d.name, d.customerid
                	FROM documentcontents
			JOIN documents d ON (d.id = documentcontents.docid)
			JOIN docrights r ON (d.type = r.doctype AND r.userid = ? AND (r.rights & 1) = 1)
		        LEFT JOIN numberplans ON (d.numberplanid = numberplans.id)
			LEFT JOIN (
			        SELECT DISTINCT a.customerid FROM customerassignments a
				JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
				WHERE e.userid = lms_current_user()
			) e ON (e.customerid = d.customerid)
			WHERE e.customerid IS NULL '
			.($customer ? 'AND d.customerid = '.intval($customer) : '')
			.($type ? ' AND d.type = '.intval($type) : '')
			. ($numberplan ? ' AND d.numberplanid = ' . intval($numberplan) : '')
			.($from ? ' AND d.cdate >= '.intval($from) : '')
			.($to ? ' AND d.cdate <= '.intval($to) : '')
			.($status == -1 ? '' : ' AND d.closed = ' . intval($status))
			.$sqlord, array($AUTH->id));

	$list['total'] = sizeof($list);
	$list['direction'] = $direction;
	$list['order'] = $order;

	return $list;
}

if (empty($_GET['init']))
{
    if(!isset($_GET['o']))
	    $SESSION->restore('doclo', $o);
    else
	    $o = $_GET['o'];
    $SESSION->save('doclo', $o);

    if(!isset($_GET['t']))
	    $SESSION->restore('doclt', $t);
    else
	    $t = $_GET['t'];
    $SESSION->save('doclt', $t);

    if(!isset($_GET['c']))
	    $SESSION->restore('doclc', $c);
    else
	    $c = $_GET['c'];
    $SESSION->save('doclc', $c);

	if(!isset($_GET['p']))
		$SESSION->restore('doclp', $p);
	else
		$p = $_GET['p'];
	$SESSION->save('doclp', $p);

    if(isset($_GET['from']))
    {
        if($_GET['from'] != '')
        {
            list($year, $month, $day) = explode('/', $_GET['from']);
            $from = mktime(0,0,0, $month, $day, $year);
        }
        else
		    $from = 0;
    }
    elseif($SESSION->is_set('doclfrom'))
	    $SESSION->restore('doclfrom', $from);
    else
        $from = 0;
    $SESSION->save('doclfrom', $from);

    if(isset($_GET['to']))
    {
        if($_GET['to'] != '')
        {
            list($year, $month, $day) = explode('/', $_GET['to']);
            $to = mktime(23,59,59, $month, $day, $year);
        }
        else
		    $to = 0;
    }
    elseif($SESSION->is_set('doclto'))
	    $SESSION->restore('doclto', $to);
    else
        $to = 0;
    $SESSION->save('doclto', $to);

	if(!isset($_GET['s']))
		$SESSION->restore('docls', $s);
	else
		$s = $_GET['s'];
	$SESSION->save('docls', $s);
}

$documentlist = GetDocumentList($o, array(
	'type' => $t,
	'customer' => $c,
	'numberplan' => $p,
	'from' => $from,
	'to' => $to,
	'status' => $s,
));

$listdata['total'] = $documentlist['total'];
$listdata['order'] = $documentlist['order'];
$listdata['direction'] = $documentlist['direction'];
$listdata['type'] = $t;
$listdata['customer'] = $c;
$listdata['numberplan'] = $p;
$listdata['from'] = $from;
$listdata['to'] = $to;
$listdata['status'] = $s;

unset($documentlist['total']);
unset($documentlist['order']);
unset($documentlist['direction']);

$pagelimit = ConfigHelper::getConfig('phpui.documentlist_pagelimit');
$page = !isset($_GET['page']) ? ceil($listdata['total']/$pagelimit) : intval($_GET['page']);
$start = ($page - 1) * $pagelimit;

$layout['pagetitle'] = trans('Documents List');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if($docid = $SESSION->get('documentprint'))
{
	$SMARTY->assign('docid', $docid);
	$SESSION->remove('documentprint');
}

if($listdata['total'])
{
	$SMARTY->assign('docrights', $DB->GetAllByKey('SELECT doctype, rights
			FROM docrights WHERE userid = ? AND rights > 1', 'doctype', array($AUTH->id)));
}

if (!ConfigHelper::checkConfig('phpui.big_networks'))
	$SMARTY->assign('customers', $LMS->GetCustomerNames());

$SMARTY->assign('numberplans', $LMS->GetNumberPlans(array(DOC_CONTRACT, DOC_ANNEX, DOC_PROTOCOL, DOC_ORDER, DOC_SHEET, -6, -7, -8, -9, -99, DOC_OTHER)));
$SMARTY->assign('documentlist', $documentlist);
$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->assign('listdata', $listdata);
$SMARTY->display('document/documentlist.html');

?>
