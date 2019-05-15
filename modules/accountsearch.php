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

function GetAccountList($order = 'login,asc', $search, $customer = null, $type = null, $kind = null, $domain = '')
{
    global $DB, $ACCOUNTTYPES;

    list($order,$direction) = sscanf($order, '%[^,],%s');

    ($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

    switch ($order) {
        case 'id':
            $sqlord = " ORDER BY p.id $direction";
            break;
        case 'customername':
            $sqlord = " ORDER BY customername $direction, login";
            break;
        case 'lastlogin':
            $sqlord = " ORDER BY lastlogin $direction, customername, login";
            break;
        case 'domain':
            $sqlord = " ORDER BY domain $direction, login";
            break;
        case 'expdate':
            $sqlord = " ORDER BY expdate $direction, login";
            break;
        default:
            $sqlord = " ORDER BY login $direction, customername";
            break;
    }

    if (!empty($search['login'])) {
        $where[] = 'p.login ?LIKE? '.$DB->Escape('%'.$search['login'].'%');
    }
    if (!empty($search['domain'])) {
        $where[] = 'd.name ?LIKE? '.$DB->Escape('%'.$search['domain'].'%');
    }
    if (!empty($search['realname'])) {
        $where[] = 'p.realname ?LIKE? '.$DB->Escape('%'.$search['realname'].'%');
    }
    if (!empty($search['description'])) {
        $where[] = 'p.description ?LIKE? '.$DB->Escape('%'.$search['description'].'%');
    }
    if ($customer != '') {
        $where[] = 'p.ownerid = '.intval($customer);
    }
    if ($type) {
        $where[] = 'p.type & '.$type.' = '.intval($type);
    }
    if ($kind == 1) {
        $where[] = 'p.expdate != 0 AND p.expdate < ?NOW?';
    } elseif ($kind == 2) {
        $where[] = '(p.expdate = 0 OR p.expdate > ?NOW?)';
    }
    if ($domain) {
        $where[] = 'p.domainid = '.intval($domain);
    }

    $where = isset($where) ? 'WHERE '.implode(' AND ', $where) : '';

    $quota_fields = array();
    foreach ($ACCOUNTTYPES as $typeidx => $atype) {
        $quota_fields[] = 'p.quota_' . $atype['alias'];
    }
    $list = $DB->GetAll('SELECT p.id, p.ownerid, p.login, p.lastlogin,
			p.expdate, d.name AS domain, p.type, ' . implode(', ', $quota_fields) . ', '
            .$DB->Concat('c.lastname', "' '", 'c.name').' AS customername 
		FROM passwd p
		LEFT JOIN customers c ON (c.id = p.ownerid)
		LEFT JOIN domains d ON (d.id = p.domainid) '
        .$where
        .($sqlord != '' ? $sqlord : ''));

    $list['total'] = count($list);
    $list['order'] = $order;
    $list['type'] = $type;
    $list['kind'] = $kind;
    $list['customer'] = $customer;
    $list['domain'] = $domain;
    $list['direction'] = $direction;

    return $list;
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$search = array();

if (isset($_POST['search'])) {
    $search = $_POST['search'];
}

if (!isset($_GET['o'])) {
    $SESSION->restore('aso', $o);
} else {
    $o = $_GET['o'];
}
$SESSION->save('aso', $o);

if (isset($_GET['u'])) {
    $u = $_GET['u'];
} elseif (count($search)) {
    $u = isset($search['ownerid']) ? $search['ownerid'] : '';
} else {
    $SESSION->restore('asu', $u);
}
$SESSION->save('asu', $u);

if (isset($_GET['t'])) {
    $t = $_GET['t'];
} elseif (count($search)) {
    $t = isset($search['type']) ? $search['type'] : 0;
} else {
    $SESSION->restore('ast', $t);
}
$SESSION->save('ast', $t);

if (isset($_GET['k'])) {
    $k = $_GET['k'];
} elseif (count($search)) {
    $k = isset($search['kind']) ? $search['kind'] : 0;
} else {
    $SESSION->restore('ask', $k);
}
$SESSION->save('ask', $k);

if (isset($_GET['d'])) {
    $d = $_GET['d'];
} elseif (count($search)) {
    $d = 0;
} else {
    $SESSION->restore('asd', $d);
}
$SESSION->save('asd', $d);

if ($SESSION->is_set('asp') && !isset($_GET['page']) && !isset($search)) {
    $SESSION->restore('asp', $_GET['page']);
}

if (count($search) || isset($_GET['s'])) {
    $search = count($search) ? $search : $SESSION->get('accountsearch');

    if (!$error) {
        $accountlist = GetAccountList($o, $search, $u, $t, $k, $d);

        $listdata['total'] = $accountlist['total'];
        $listdata['order'] = $accountlist['order'];
        $listdata['direction'] = $accountlist['direction'];
        $listdata['type'] = $accountlist['type'];
        $listdata['kind'] = $accountlist['kind'];
        $listdata['customer'] = $accountlist['customer'];
        $listdata['domain'] = $accountlist['domain'];

        unset($accountlist['total']);
        unset($accountlist['order']);
        unset($accountlist['type']);
        unset($accountlist['kind']);
        unset($accountlist['customer']);
        unset($accountlist['domain']);
        unset($accountlist['direction']);
    
        $page = (! isset($_GET['page']) ? 1 : $_GET['page']);
        $pagelimit = ConfigHelper::getConfig('phpui.accountlist_pagelimit', $queuedata['total']);
        $start = ($page - 1) * $pagelimit;

        $SESSION->save('asp', $page);
        $SESSION->save('accountsearch', $search);

        $layout['pagetitle'] = trans('Account Search Results');

        $SMARTY->assign('listdata', $listdata);
        $SMARTY->assign('customerlist', $LMS->GetAllCustomerNames());
        $SMARTY->assign('domainlist', $DB->GetAll('SELECT id, name FROM domains ORDER BY name'));
        $SMARTY->assign('pagelimit', $pagelimit);
        $SMARTY->assign('page', $page);
        $SMARTY->assign('start', $start);
        $SMARTY->assign('search', $search);
        $SMARTY->assign('accountlist', $accountlist);
        $SMARTY->display('account/accountlist.html');
        $SESSION->close();
        die;
    }
}

$layout['pagetitle'] = trans('Account, Alias, Domain Search');

$SMARTY->assign('customerlist', $LMS->GetAllCustomerNames());
$SMARTY->assign('search', isset($search) ? $search : $SESSION->get('accountsearch'));
$SMARTY->display('account/accountsearch.html');
