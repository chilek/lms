<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

function sessionHandler($item, $name)
{
    global $SESSION;

    if (isset($_GET[$item])) {
        $o = $_GET[$item];
    } elseif (isset($_POST[$item])) {
        $o = $_POST[$item];
    } else {
        $SESSION->restore($name, $o);
    }

    $SESSION->save($name, $o);
    return $o;
}

if (!empty($_POST['str'])) {
    $voipaccounts = $LMS->GetCustomerVoipAccounts($_POST['str']);
    $SMARTY->assign('voipaccounts', $voipaccounts);
    $content = $SMARTY->fetch('voipaccount/voipaccounts.html');
    echo json_encode($content);
    die();
}

$layout['pagetitle'] = trans('Billing list');

$SESSION->add_history_entry();

$params = array();
$params['o']          = sessionHandler('o', 'vblo');
if (!isset($params['o'])) {
    $params['o'] = 'login,asc';
}
$params['frangefrom'] = sessionHandler('frangefrom', 'vblfrangefrom');
if (empty($params['frangefrom'])) {
    $params['frangefrom'] = date('Y/m/01');
}
$params['id'] = $params['fvoipaccid'] = sessionHandler('fvoipaccid', 'vblfvoipaccid');
if (!empty($params['id'])) {
    $params['fvownerid'] = $LMS->getVoipAccountOwner($params['id']);
} else {
    $params['fvownerid'] = sessionHandler('fvownerid', 'vblfownerid');
}
$params['frangeto']   = sessionHandler('frangeto', 'vblfrangeto');
$params['ftype']      = sessionHandler('ftype', 'vblftype');
$params['fstatus']    = sessionHandler('fstatus', 'vblfstatus');

$LMS->executeHook('voip_billing_preparation', array(
    'customerid' => $params['fvownerid'],
    'voipaccountid' => $params['id'],
    'number' => null,
    'datefrom' => $params['frangefrom'],
    'dateto' => $params['frangeto'],
    'type' => $params['ftype'],
    'status' => $params['fstatus'],
));

$params['count'] = true;
$total = intval($LMS->getVoipBillings($params));

$page  = !isset($_GET['page']) ? (!isset($_POST['page']) ? ($SESSION->is_set('valp') ? $SESSION->get('valp') : 1) : intval($_POST['page'])) : intval($_GET['page']);
$limit = intval(ConfigHelper::getConfig('phpui.billinglist_pagelimit', 100));
$offset = ($page - 1) * $limit;

$params['count'] = false;
$params['offset'] = $offset;
$params['limit'] = $limit;
$bill_list = $LMS->getVoipBillings($params);

$pagination = LMSPaginationFactory::getPagination($page, $total, $limit, ConfigHelper::checkConfig('phpui.short_pagescroller'));

// CALL BILLING RANGE
if (!empty($params['frangefrom'])) {
    $listdata['frangefrom'] = date_to_timestamp($params['frangefrom']);
}

if (!empty($params['frangeto'])) {
    $listdata['frangeto'] = date_to_timestamp($params['frangeto']);
}

// CALL STATUS
if (!empty($params['fstatus'])) {
    switch ($params['fstatus']) {
        case CALL_ANSWERED:
        case CALL_NO_ANSWER:
        case CALL_BUSY:
        case CALL_SERVER_FAILED:
            $listdata['fstatus'] = $params['fstatus'];
            break;
    }
}

// CALL TYPE
if (!empty($params['ftype'])) {
    switch ($params['ftype']) {
        case CALL_OUTGOING:
        case CALL_INCOMING:
            $listdata['ftype'] = $params['ftype'];
            break;
    }
}

$voipaccountlist = $LMS->GetVoipAccountList('owner', empty($params['fvownerid']) ? null : array('ownerid' => $params['fvownerid']), null);
unset($voipaccountlist['total']);
unset($voipaccountlist['order']);
unset($voipaccountlist['direction']);

$voipownerlist = Utils::array_column($voipaccountlist, "owner", "ownerid");

$order = explode(',', $params['o']);
if (empty($order[1]) || $order[1] != 'desc') {
    $order[1] = 'asc';
}

$listdata['order'] = $order[0];
$listdata['direction'] = $order[1];

if (!empty($page)) {
    $listdata['page'] = $page;
}

if (!empty($params['fvownerid'])) {
    $listdata['fvownerid'] = $params['fvownerid'];
}

if (!empty($params['fvoipaccid'])) {
    $listdata['fvoipaccid'] = $params['fvoipaccid'];
}

$SESSION->save('valp', $page);

$billing_stats = $DB->GetRow('SELECT
                                 SUM(price) AS price,
                                 SUM(totaltime) AS totaltime,
                                 SUM(billedtime) AS billedtime,
                                 COUNT(*) AS cnt
                              FROM
                                 voip_cdr');

$SMARTY->assign('voipaccounts', $voipaccountlist);
$SMARTY->assign('voipownerlist', $voipownerlist);
$SMARTY->assign('pagination', $pagination);
$SMARTY->assign('billings', $bill_list);
$SMARTY->assign('total', $total);
$SMARTY->assign('page', $page);
$SMARTY->assign('pagelimit', $limit);
$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('stats', $billing_stats);
$SMARTY->display('voipaccount/voipaccountbillinglist.html');
