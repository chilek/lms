<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2021 LMS Developers
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

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if (isset($_GET['page'])) {
    $page = intval($_GET['page']);
} else {
    if ($SESSION->is_set('customer_call_list_page')) {
        $SESSION->restore('customer_call_list_page', $page);
        $page = intval($page);
        if (empty($page)) {
            $page = 1;
        }
    } else {
        $page = 1;
    }
}

$limit = intval(ConfigHelper::getConfig('phpui.customer_call_list_pagelimit', 20));
$offset = ($page - 1) * $limit;

if (isset($_GET['c'])) {
    $cid = intval($_GET['c']);
    $uid = 0;
} elseif (isset($_GET['u'])) {
    $uid = intval($_GET['u']);
    $cid = 0;
}

if (!isset($uid) && !isset($_GET['c']) && $SESSION->is_set('customer_call_list_customerid')) {
    $SESSION->restore('customer_call_list_customerid', $cid);
    $cid = intval($cid);
}

if (!isset($cid) && !isset($_GET['u']) && $SESSION->is_set('customer_call_list_userid')) {
    $SESSION->restore('customer_call_list_userid', $uid);
    $uid = intval($uid);
}

if ($cid) {
    if (!$LMS->CustomerExists($cid)) {
        $SESSION->redirect('?m=customerlist');
    }

    $customername = $LMS->GetCustomerName($cid);
    $layout['pagetitle'] = trans('Customer Call List: $a', '<a href="?m=customerinfo&id=' . $cid . '">' . $customername . '</a>');

    $params = array(
        'customerid' => $cid,
        'count' => true
    );
    $total = intval($LMS->getCustomerCalls($params));

    $params = array(
        'customerid' => $cid,
        'count' => false,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
    );
    if ($total && $total < $params['offset']) {
        $page = 1;
        $params['offset'] = 0;
    }
    $customercalls = $LMS->getCustomerCalls($params);

    $SMARTY->assign('cid', $cid);
} elseif ($uid) {
    if (!$LMS->userExists($uid)) {
        $SESSION->redirect('?m=userlist');
    }

    $username = $LMS->getUserName($uid);
    $layout['pagetitle'] = trans('User Call List: $a', '<a href="?m=userinfo&id=' . $uid . '">' . $username . '</a>');

    $params = array(
        'userid' => $uid,
        'count' => true
    );
    $total = intval($LMS->getCustomerCalls($params));

    $params = array(
        'userid' => $uid,
        'count' => false,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
    );
    if ($total && $total < $params['offset']) {
        $page = 1;
        $params['offset'] = 0;
    }
    $customercalls = $LMS->getCustomerCalls($params);

    $SMARTY->assign('uid', $uid);
} else {
    $layout['pagetitle'] = trans('Call List');

    $params = array(
        'count' => true
    );
    $total = intval($LMS->getCustomerCalls($params));

    $params = array(
        'count' => false,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
    );
    if ($total && $total < $params['offset']) {
        $page = 1;
        $params['offset'] = 0;
    }
    $customercalls = $LMS->getCustomerCalls($params);
}

$pagination = LMSPaginationFactory::getPagination($page, $total, $limit, ConfigHelper::checkConfig('phpui.short_pagescroller'));

if ($cid) {
    $SESSION->save('customer_call_list_customerid', $cid);
} else {
    $SESSION->remove('customer_call_list_customerid');
}
if ($uid) {
    $SESSION->save('customer_call_list_userid', $uid);
} else {
    $SESSION->remove('customer_call_list_userid');
}
$SESSION->save('customer_call_list_page', $page);

$SMARTY->assign('customercalls', $customercalls);
$SMARTY->assign('customername', $customername);
if (!ConfigHelper::checkConfig('phpui.big_networks')) {
    $SMARTY->assign('customers', $LMS->GetCustomerNames());
}
$SMARTY->assign('pagination', $pagination);

$SMARTY->display('customer/customercalllist.html');
