<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

	if (!isset($_GET['usertype']))
		$SESSION->restore('doclut', $usertype);
	else
		$usertype = $_GET['usertype'];
	if (empty($usertype))
		$usertype = 'creator';
	$SESSION->save('doclut', $usertype);

	if (!isset($_GET['u']))
		$SESSION->restore('doclu', $u);
	else
		$u = $_GET['u'];
	$SESSION->save('doclu', $u);

	if (!isset($_GET['periodtype']))
		$SESSION->restore('doclpt', $periodtype);
	else
		$periodtype = $_GET['periodtype'];
	if (empty($periodtype))
		$periodtype = 'creationdate';
	$SESSION->save('doclpt', $periodtype);

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

$total = intval($LMS->GetDocumentList(array(
	'order' => $o,
	'type' => $t,
	'customer' => $c,
	'numberplan' => $p,
	'usertype' => $usertype,
	'userid' => $u,
	'periodtype' => $periodtype,
	'from' => $from,
	'to' => $to,
	'status' => $s,
	'count' => true,
)));

$limit = intval(ConfigHelper::getConfig('phpui.documentlist_pagelimit', 100));
$page = intval(!isset($_GET['page']) ? ceil($total / $limit) : $_GET['page']);
$offset = ($page - 1) * $limit;

$documentlist = $LMS->GetDocumentList(array(
	'order' => $o,
	'type' => $t,
	'customer' => $c,
	'numberplan' => $p,
	'usertype' => $usertype,
	'userid' => $u,
	'periodtype' => $periodtype,
	'from' => $from,
	'to' => $to,
	'status' => $s,
	'count' => false,
	'offset' => $offset,
	'limit' => $limit,
));

$pagination = LMSPaginationFactory::getPagination($page, $total, $limit, ConfigHelper::checkConfig('phpui.short_pagescroller'));

$listdata['total'] = $total;
$listdata['order'] = $documentlist['order'];
$listdata['direction'] = $documentlist['direction'];
$listdata['type'] = $t;
$listdata['customer'] = $c;
$listdata['numberplan'] = $p;
$listdata['usertype'] = $usertype;
$listdata['userid'] = $u;
$listdata['periodtype'] = $periodtype;
$listdata['from'] = $from;
$listdata['to'] = $to;
$listdata['status'] = $s;

unset($documentlist['total']);
unset($documentlist['order']);
unset($documentlist['direction']);

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
			FROM docrights WHERE userid = ? AND rights > 1', 'doctype', array(Auth::GetCurrentUser())));
}

if (!ConfigHelper::checkConfig('phpui.big_networks'))
	$SMARTY->assign('customers', $LMS->GetCustomerNames());

$SMARTY->assign('users', $LMS->GetUserNames());
$SMARTY->assign('numberplans', $LMS->GetNumberPlans(array(
	'doctype' => array(DOC_CONTRACT, DOC_ANNEX, DOC_PROTOCOL, DOC_ORDER, DOC_SHEET, -6, -7, -8, -9, -99, DOC_PRICELIST, DOC_PROMOTION, DOC_WARRANTY, DOC_REGULATIONS, DOC_OTHER),
)));
$SMARTY->assign('documentlist', $documentlist);
$SMARTY->assign('pagination', $pagination);
$SMARTY->assign('listdata', $listdata);
$SMARTY->display('document/documentlist.html');

?>
