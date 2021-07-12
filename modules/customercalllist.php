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

$cid = 0;
$uid = 0;
$assigned = '';
if (!isset($_GET['reset'])) {
    if (isset($_POST['customerid'])) {
        $cid = intval($_POST['customerid']);
    } elseif (isset($_GET['c'])) {
        $cid = intval($_GET['c']);
    } elseif ($SESSION->is_set('customer_call_list_customerid')) {
        $SESSION->restore('customer_call_list_customerid', $cid);
        $cid = intval($cid);
    }

    if (isset($_POST['userid'])) {
        $uid = intval($_POST['userid']);
    } elseif (isset($_GET['u'])) {
        $uid = intval($_GET['u']);
    } elseif ($SESSION->is_set('customer_call_list_userid')) {
        $SESSION->restore('customer_call_list_userid', $uid);
        $uid = intval($uid);
    }

    if (isset($_POST['assigned'])) {
        switch ($_POST['assigned']) {
            case '1':
                $assigned = 1;
                break;
            case '0':
                $assigned = 0;
                break;
            case '':
            default:
                $assigned = '';
                break;
        }
    } elseif ($SESSION->is_set('customer_call_list_assigned')) {
        $SESSION->get('customer_call_list_assigned,', $assigned);
    }
}

if ($cid && !$LMS->CustomerExists($cid)) {
    $SESSION->redirect('?m=customerlist');
}
if ($uid && !$LMS->userExists($uid)) {
    $SESSION->redirect('?m=userlist');
}

$params = array(
    'assigned' => $assigned,
    'count' => true
);

if ($cid) {
    $params['customerid'] = $cid;
    $SMARTY->assign('customername', $LMS->GetCustomerName($cid));
}
if ($uid) {
    $params['userid'] = $uid;
    $SMARTY->assign('username', $LMS->getUserName($uid));
}
$total= intval($LMS->getCustomerCalls($params));

$params['count'] = false;
$params['total'] = $total;
$params['limit'] = $limit;
$params['offset'] = $offset;
if ($total && $total < $params['offset']) {
    $page = 1;
    $params['offset'] = 0;
}
$customercalls = $LMS->getCustomerCalls($params);

$SMARTY->assign('users', $LMS->getUserNames(array()));

$pagination = LMSPaginationFactory::getPagination($page, $total, $limit, ConfigHelper::checkConfig('phpui.short_pagescroller'));

$SMARTY->assign('uid', $uid);
$SMARTY->assign('cid', $cid);

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

$SMARTY->assign('assigned', $assigned);

$SMARTY->assign('customercalls', $customercalls);
if (!ConfigHelper::checkConfig('phpui.big_networks')) {
    $SMARTY->assign('customers', $LMS->GetCustomerNames());
}
$SMARTY->assign('pagination', $pagination);

$SMARTY->display('customer/customercalllist.html');
