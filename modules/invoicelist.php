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

$proforma = isset($_GET['proforma']) ? 1 : 0;

$layout['pagetitle'] = $proforma ? trans('Pro Forma Invoice List') : trans('Invoices List');
$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SESSION->restore('ilm', $marks);
if (isset($_POST['marks'])) {
    foreach ($_POST['marks'] as $id => $mark) {
        $marks[$id] = $mark;
    }
}
$SESSION->save('ilm', $marks);

if (isset($_POST['search'])) {
    $s = $_POST['search'];
} else {
    $SESSION->restore('ils', $s);
}
if (!isset($s)) {
     $year=date("Y", time());
     $month=date("m", time());
     $s = $year.'/'.$month;
}
$SESSION->save('ils', $s);

if (isset($_GET['o'])) {
    $o = $_GET['o'];
} else {
    $SESSION->restore('ilo', $o);
}
$SESSION->save('ilo', $o);

if (isset($_POST['cat'])) {
    $c = $_POST['cat'];
} else {
    $SESSION->restore('ilc', $c);
}
if (!isset($c)) {
    $c="month";
}
$SESSION->save('ilc', $c);

if (isset($_POST['numberplanid'])) {
    if ($_POST['numberplanid'] == 'all') {
        $np = array();
    } else {
        $np = $_POST['numberplanid'];
    }
} else {
    $SESSION->restore('ilnp', $np);
}
$SESSION->save('ilnp', $np);

if (isset($_POST['divisionid'])) {
    if (empty($_POST['divisionid'])) {
        $div = 0;
    } else {
        $div = $_POST['divisionid'];
    }
} else {
    $SESSION->restore('ildiv', $div);
}
$SESSION->save('ildiv', $div);

if (isset($_POST['search'])) {
    $h = isset($_POST['hideclosed']);
} elseif (($h = $SESSION->get('ilh')) === null) {
    $h = ConfigHelper::checkConfig('invoices.hide_closed');
}
$SESSION->save('ilh', $h);

if (isset($_POST['group'])) {
    if ($_POST['group'] == 'all') {
        $g = array();
    } else {
        $g = $_POST['group'];
    }
    $ge = isset($_POST['groupexclude']) ? $_POST['groupexclude'] : null;
} else {
    $SESSION->restore('ilg', $g);
    $SESSION->restore('ilge', $ge);
}
$SESSION->save('ilg', $g);
$SESSION->save('ilge', $ge);

if ($c == 'cdate' && $s && preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $s)) {
    list($year, $month, $day) = explode('/', $s);
    $s = mktime(0, 0, 0, $month, $day, $year);
} elseif ($c == 'month' && $s && preg_match('/^[0-9]{4}\/[0-9]{2}$/', $s)) {
    list($year, $month) = explode('/', $s);
        $s = mktime(0, 0, 0, $month, 1, $year);
}

$total = intval($LMS->GetInvoiceList(array('search' => $s, 'cat' => $c, 'group' => $g, 'exclude'=> $ge,
    'numberplan' => $np, 'division' => $div, 'hideclosed' => $h, 'order' => $o, 'proforma' => $proforma, 'count' => true)));

$limit = intval(ConfigHelper::getConfig('phpui.invoicelist_pagelimit', 100));
$page = !isset($_GET['page']) ? ceil($total / $limit) : $_GET['page'];
if (empty($page)) {
    $page = 1;
}
$page = intval($page);
$offset = ($page - 1) * $limit;

$invoicelist = $LMS->GetInvoiceList(array('search' => $s, 'cat' => $c, 'group' => $g, 'exclude'=> $ge,
    'numberplan' => $np, 'division' => $div, 'hideclosed' => $h, 'order' => $o, 'limit' => $limit, 'offset' => $offset, 'proforma' => $proforma,
    'count' => false));

$pagination = LMSPaginationFactory::getPagination($page, $total, $limit, ConfigHelper::checkConfig('phpui.short_pagescroller'));

$SESSION->restore('ilc', $listdata['cat']);
$SESSION->restore('ils', $listdata['search']);
$SESSION->restore('ilg', $listdata['group']);
$SESSION->restore('ilge', $listdata['groupexclude']);
$SESSION->restore('ilnp', $listdata['numberplanid']);
$SESSION->restore('ildiv', $listdata['divisionid']);
$SESSION->restore('ilh', $listdata['hideclosed']);

$listdata['total'] = $total;
$listdata['order'] = $invoicelist['order'];
$listdata['direction'] = $invoicelist['direction'];

unset($invoicelist['order']);
unset($invoicelist['direction']);

if ($invoice = $SESSION->get('invoiceprint')) {
        $SMARTY->assign('invoice', $invoice);
        $SESSION->remove('invoiceprint');
}

$hook_data = $LMS->ExecuteHook(
    'invoicelist_before_display',
    array(
        'invoicelist' => $invoicelist,
    )
);
$invoicelist = $hook_data['invoicelist'];

$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('pagination', $pagination);
$SMARTY->assign('marks', $marks);
$SMARTY->assign('grouplist', $LMS->CustomergroupGetAll());

if ($proforma) {
    $doctypes = array(DOC_INVOICE_PRO);
} else {
    $doctypes = array(DOC_INVOICE, DOC_CNOTE);
}
$SMARTY->assign('numberplans', $LMS->GetNumberPlans(array(
    'doctype' => $doctypes,
)));

$SMARTY->assign('proforma', $proforma);
$SMARTY->assign('divisions', $LMS->GetDivisions());
$SMARTY->assign('invoicelist', $invoicelist);
$SMARTY->display('invoice/invoicelist.html');
