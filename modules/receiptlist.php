<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

$SESSION->restore('rlm', $marks);
$marked = isset($_POST['marks']) ? $_POST['marks'] : array();
if (count($marked)) {
    foreach ($marked as $id => $mark) {
        $marks[$id] = $mark;
    }
}
$SESSION->save('rlm', $marks);

if (isset($_POST['search'])) {
    $s = $_POST['search'];
} else {
    $SESSION->restore('rls', $s);
}
$SESSION->save('rls', $s);

if (isset($_GET['o'])) {
    $o = $_GET['o'];
} else {
    $SESSION->restore('rlo', $o);
}
$SESSION->save('rlo', $o);

if (isset($_POST['cat'])) {
    $c = $_POST['cat'];
} else {
    $SESSION->restore('rlc', $c);
}
$SESSION->save('rlc', $c);

if (isset($_GET['regid'])) {
    $regid = $_GET['regid'];
} else {
    $SESSION->restore('rlreg', $regid);
}
$SESSION->save('rlreg', $regid);

if (isset($_POST['from'])) {
    if (!empty($_POST['from'])) {
        $from = date_to_timestamp($_POST['from']);
        if (empty($from)) {
            $error['datefrom'] = trans('Invalid date format!');
        }
    }
} elseif ($SESSION->is_set('rlf')) {
    $SESSION->restore('rlf', $from);
} else {
    $from = mktime(0, 0, 0);
}

if (isset($_POST['to'])) {
    if (!empty($_POST['to'])) {
        $to = date_to_timestamp($_POST['to']);
        if (empty($to)) {
            $error['dateto'] = trans('Invalid date format!');
        } else {
            $to += 86399;
        }
    }
} elseif ($SESSION->is_set('rlt')) {
    $SESSION->restore('rlt', $to);
} else {
    $to = mktime(23, 59, 59);
}

if ($from && $to && $from > $to) {
    $error['datefrom'] = trans('Incorrect date range!');
    $error['dateto'] = trans('Incorrect date range!');
} else {
    $SESSION->save('rlf', $from);
    $SESSION->save('rlt', $to);
}

if (isset($_POST['advances'])) {
    $a = 1;
} else {
    $a = 0;
}
$SESSION->save('rla', $a);

if (!$regid) {
    $SESSION->redirect('?m=cashreglist');
}

if (!$DB->GetOne(
    'SELECT rights FROM cashrights WHERE userid = ? AND regid = ? AND (rights & 1) > 0',
    array(Auth::GetCurrentUser(), $regid)
)) {
    $SMARTY->display('noaccess.html');
    $SESSION->close();
    die;
}

$summary = $LMS->GetReceiptList(array('registry' => $regid, 'order' => $o, 'search' => $s,
    'cat' => $c, 'from' => $from, 'to' => $to, 'advances' => $a, 'count' => true));
$total = intval($summary['total']);

$limit = intval(ConfigHelper::getConfig('phpui.receiptlist_pagelimit', 100));
$page = !isset($_GET['page']) ? ceil($total / $limit) : $_GET['page'];
if (empty($page)) {
    $page = 1;
}
$page = intval($page);
$offset = ($page - 1) * $limit;

$receiptlist = $LMS->GetReceiptList(array('registry' => $regid, 'order' => $o, 'search' => $s,
    'cat' => $c, 'from' => $from, 'to' => $to, 'advances' => $a,
    'offset' => $offset, 'limit' => $limit, 'count' => false));

$pagination = LMSPaginationFactory::getPagination($page, $total, $limit, ConfigHelper::checkConfig('phpui.short_pagescroller'));

$SESSION->restore('rlc', $listdata['cat']);
$SESSION->restore('rls', $listdata['search']);
$SESSION->restore('rlf', $listdata['from']);
$SESSION->restore('rlt', $listdata['to']);
$SESSION->restore('rla', $listdata['advances']);

$listdata['order'] = $receiptlist['order'];
$listdata['direction'] = $receiptlist['direction'];
$listdata['totalincome'] = $summary['totalincome'];
$listdata['totalexpense'] = $summary['totalexpense'];
$listdata['regid'] = $regid;

unset($receiptlist['order']);
unset($receiptlist['direction']);

$listdata['total'] = $total;
$listdata['cashstate'] = $DB->GetOne(
    'SELECT SUM(value * d.currencyvalue) FROM receiptcontents c
    JOIN documents d ON d.id = c.docid
    WHERE regid = ?',
    array($regid)
);
if ($from > 0) {
    $listdata['startbalance'] = $DB->GetOne(
        'SELECT SUM(value) FROM receiptcontents
		LEFT JOIN documents ON (docid = documents.id AND type = ?)
		WHERE cdate < ? AND regid = ?',
        array(DOC_RECEIPT, $from, $regid)
    );
}

$listdata['endbalance'] = $listdata['startbalance'] + $listdata['totalincome'] - $listdata['totalexpense'];

$pagelimit = ConfigHelper::getConfig('phpui.receiptlist_pagelimit');
$page = (!isset($_GET['page']) ? ceil($listdata['total']/$pagelimit) : $_GET['page']);
if (empty($page)) {
    $page = 1;
}
$start = ($page - 1) * $pagelimit;

$logentry = $DB->GetRow('SELECT * FROM cashreglog WHERE regid = ?
			ORDER BY time DESC LIMIT 1', array($regid));

$layout['pagetitle'] = trans('Cash Registry: $a', $DB->GetOne('SELECT name FROM cashregs WHERE id=?', array($regid)));

$SESSION->save('backto', 'm=receiptlist&regid='.$regid);

if ($receipt = $SESSION->get('receiptprint')) {
    $SMARTY->assign('receipt', $receipt);
    $SESSION->remove('receiptprint');
}

$SMARTY->assign('error', $error);
$SMARTY->assign('logentry', $logentry);
$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('pagination', $pagination);
$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('start', $start);
$SMARTY->assign('page', $page);
$SMARTY->assign('marks', $marks);
$SMARTY->assign('receiptlist', $receiptlist);
$SMARTY->display('receipt/receiptlist.html');
