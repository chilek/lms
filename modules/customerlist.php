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

if ($api) {
    $count = false;
    $customerlist = $LMS->GetCustomerList(compact("count"));
    if (empty($customerlist)) {
        $customerlist = array();
    } else {
        unset($customerlist['total']);
        unset($customerlist['state']);
        unset($customerlist['order']);
        unset($customerlist['below']);
        unset($customerlist['over']);
        unset($customerlist['direction']);
    }
    header('Content-Type: application/json');
    echo json_encode(array_values($customerlist));
    die;
} else {
    $SESSION->save('backto', $_SERVER['QUERY_STRING']);

    $layout['pagetitle'] = trans('Customers List');

    if (isset($_GET['o'])) {
        $filter['order'] = $_GET['o'];
    }

    if (isset($_GET['s'])) {
        $filter['state'] = $_GET['s'];
    }

    if (isset($_GET['n'])) {
        $filter['network'] = $_GET['n'];
    }

    if (isset($_GET['g'])) {
        $filter['customergroup'] = Utils::filterIntegers($_GET['g']);
    }

    if (isset($_GET['ng'])) {
        $filter['nodegroup'] = $_GET['ng'];
    }

    if (isset($_GET['d'])) {
        $filter['division'] = $_GET['d'];
    }

    if (isset($_GET['assignments'])) {
        $filter['assignments'] = $_GET['assignments'];
    }

    if (isset($_GET['page'])) {
        $filter['page'] = intval($_GET['page']);
    } elseif (!isset($filter['page']) || empty($filter['page'])) {
        $filter['page'] = 1;
    }

    $SESSION->saveFilter($filter);

    $filter['search'] = array();
    $filter['sqlskey'] = 'AND';
    $filter['count'] = true;
    $summary = $LMS->GetCustomerList($filter);

    $filter['total'] = intval($summary['total']);
    $filter['limit'] = intval(ConfigHelper::getConfig('phpui.customerlist_pagelimit', 100));
    $filter['offset'] = ($filter['page'] - 1) * $filter['limit'];
    if ($filter['total'] && $filter['total'] < $filter['offset']) {
        $filter['page'] = 1;
        $filter['offset'] = 0;
    }
    $filter['count'] = false;
    $customerlist = $LMS->GetCustomerList($filter);
}
$pagination = LMSPaginationFactory::getPagination($filter['page'], $filter['total'], $filter['limit'], ConfigHelper::checkConfig('phpui.short_pagescroller'));

$filter['below'] = $summary['below'];
$filter['over'] = $summary['over'];
$filter['order'] = $customerlist['order'];
$filter['direction'] = $customerlist['direction'];

unset($customerlist['total']);
unset($customerlist['state']);
unset($customerlist['order']);
unset($customerlist['below']);
unset($customerlist['over']);
unset($customerlist['direction']);

$SMARTY->assign('customerlist', $customerlist);
$SMARTY->assign('networks', $LMS->GetNetworks());
$SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
$SMARTY->assign('nodegroups', $LMS->GetNodeGroupNames());
$SMARTY->assign('divisions', $LMS->GetDivisions());
$SMARTY->assign('pagination', $pagination);

$SMARTY->display('customer/customerlist.html');
