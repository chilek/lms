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

function getVoipAccountList($fownerid = null)
{
    $lms = LMS::getInstance();
    $voipaccountlist = $lms->GetVoipAccountList('owner', empty($fownerid) ? null : array('ownerid' => $fownerid), null);
    unset($voipaccountlist['total']);
    unset($voipaccountlist['order']);
    unset($voipaccountlist['direction']);

    $hook_data =  $lms->executeHook(
        'voipbillinglist_accountlist_init',
        array(
            'voipaccountlist' => $voipaccountlist,
        )
    );

    return $hook_data['voipaccountlist'];
}

if (isset($_POST['str'])) {
    $str = !empty($_POST['str']) ? $_POST['str'] : null;
    $voipaccounts = getVoipAccountList($str);

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
if (isset($_GET['init'])) {
    $params['fvownerid'] = 0;
    $params['fvoipaccid'] = 0;
} else {
    $params['fvownerid'] = sessionHandler('fvownerid', 'vblfownerid');
    $params['fvoipaccid'] = sessionHandler('fvoipaccid', 'vblfvoipaccid');
}
if (empty($params['fvoipaccid']) && !isset($_GET['fvoipaccid'])) {
    $params['id'] = null;
} else {
    $params['id'] = $params['fvoipaccid'];
    if (!empty($params['id'])) {
        $params['fvownerid'] = $LMS->getVoipAccountOwner($params['id']);
        $SESSION->save('vblfownerid', $params['fvownerid']);
    } else {
        $params['fvownerid'] = sessionHandler('fvownerid', 'vblfownerid');
    }
}
$params['frangeto']   = sessionHandler('frangeto', 'vblfrangeto');
$params['fdirection']      = sessionHandler('fdirection', 'vblfdirection');
$params['ftype']      = sessionHandler('ftype', 'vblftype');
$params['fstatus']    = sessionHandler('fstatus', 'vblfstatus');

$LMS->executeHook('voip_billing_preparation', array(
    'customerid' => $params['fvownerid'],
    'voipaccountid' => $params['id'],
    'number' => null,
    'datefrom' => $params['frangefrom'],
    'dateto' => $params['frangeto'],
    'direction' => $params['fdirection'],
    'type' => $params['ftype'],
    'status' => $params['fstatus'],
));

$hook_data = $plugin_manager->executeHook(
    'voipbillinglist_init',
    array(
        'params' => $params,
    )
);
$params = $hook_data['params'];

$params['count'] = true;
$total = intval($LMS->getVoipBillings($params));

$page  = !isset($_GET['page']) ? (!isset($_POST['page']) ? ($SESSION->is_set('vablp') ? $SESSION->get('vablp') : 1) : intval($_POST['page'])) : intval($_GET['page']);
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

// billing record statuses
if (!empty($params['fstatus'])) {
    switch ($params['fstatus']) {
        case BILLING_RECORD_STATUS_ANSWERED:
        case BILLING_RECORD_STATUS_NO_ANSWER:
        case BILLING_RECORD_STATUS_BUSY:
        case BILLING_RECORD_STATUS_SERVER_FAILED:
        case BILLING_RECORD_STATUS_UNKNOWN:
            $listdata['fstatus'] = $params['fstatus'];
            break;
    }
}

// billing record directions
if (!empty($params['fdirection'])) {
    switch ($params['fdirection']) {
        case BILLING_RECORD_DIRECTION_OUTGOING:
        case BILLING_RECORD_DIRECTION_INCOMING:
            $listdata['fdirection'] = $params['fdirection'];
            break;
    }
}

// billing record types
if (isset($params['ftype'])) {
    $listdata['ftype'] = is_numeric($params['ftype']) ? $params['ftype'] : null;
}

$fvownerid = !empty($params['fvownerid']) ? $params['fvownerid'] : null;
$voipaccountlist = getVoipAccountList($fvownerid);
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

$SESSION->save('vablp', $page);

$params['stats'] = true;
$billing_stats = $LMS->getVoipBillings($params);
$params['stats'] = false;

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
