<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

function GetAliasList($order = 'login,asc', $search, $customer = '', $domain = '')
{
    global $DB;

    list($order,$direction) = sscanf($order, '%[^,],%s');

    ($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

    switch ($order) {
        case 'id':
            $sqlord = " ORDER BY a.id $direction";
            break;
        case 'domain':
                $sqlord = " ORDER BY domain $direction, a.login";
            break;
        default:
            $sqlord = " ORDER BY a.login $direction, domain";
            break;
    }

    if (!empty($search['login'])) {
        $where[] = 'a.login ?LIKE? '.$DB->Escape('%'.$search['login'].'%');
    }
    if (!empty($search['domain'])) {
        $where[] = 'd.name ?LIKE? '.$DB->Escape('%'.$search['domain'].'%');
    }
    if ($customer != '') {
        $where[] = 'd.ownerid = '.intval($customer);
    }
    if ($domain) {
        $where[] = 'a.domainid = '.intval($domain);
    }

    $where = isset($where) ? 'WHERE '.implode(' AND ', $where) : '';

    $list = $DB->GetAll('SELECT a.id, a.login, d.name AS domain, domainid,
            		(SELECT '.$DB->Concat('p.login', "'@'", 'pd.name').'
		    		FROM passwd p
				JOIN domains pd ON (p.domainid = pd.id)
				WHERE p.id = s.accountid) AS dest,
			s.cnt
			FROM aliases a
			JOIN domains d ON (d.id = a.domainid)
			JOIN (SELECT COUNT(*) AS cnt, MIN(accountid) AS accountid, aliasid
				FROM aliasassignments GROUP BY aliasid) s ON (a.id = s.aliasid) '
            .$where
                    .($sqlord != '' ? $sqlord : ''));
    
    $list['total'] = count($list);
    $list['order'] = $order;
    $list['direction'] = $direction;
    $list['customer'] = $customer;
    $list['domain'] = $domain;
    
    return $list;
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$search = array();

if (isset($_POST['search'])) {
    $search = $_POST['search'];
}

if (!isset($_GET['o'])) {
    $SESSION->restore('also', $o);
} else {
    $o = $_GET['o'];
}
$SESSION->save('also', $o);

if (isset($_GET['u'])) {
    $u = $_GET['u'];
} elseif (count($search)) {
    $u = isset($search['ownerid']) ? $search['ownerid'] : '';
} else {
    $SESSION->restore('alsu', $u);
}
$SESSION->save('alsu', $u);

if (isset($_GET['d'])) {
    $d = $_GET['d'];
} elseif (count($search)) {
    $d = 0;
} else {
    $SESSION->restore('alsd', $d);
}
$SESSION->save('alsd', $d);

if ($SESSION->is_set('alsp') && !isset($_GET['page']) && !isset($search)) {
    $SESSION->restore('alsp', $_GET['page']);
}

if (count($search) || isset($_GET['s'])) {
    $search = count($search) ? $search : $SESSION->get('aliassearch');

    if (!$error) {
        $aliaslist = GetAliasList($o, $search, $u, $d);

        $listdata['total'] = $aliaslist['total'];
        $listdata['order'] = $aliaslist['order'];
        $listdata['direction'] = $aliaslist['direction'];
        $listdata['customer'] = $aliaslist['customer'];
        $listdata['domain'] = $aliaslist['domain'];

        unset($aliaslist['total']);
        unset($aliaslist['order']);
        unset($aliaslist['customer']);
        unset($aliaslist['domain']);
        unset($aliaslist['direction']);
    
        $page = (! isset($_GET['page']) ? 1 : $_GET['page']);
        $pagelimit = ConfigHelper::getConfig('phpui.aliaslist_pagelimit', $queuedata['total']);
        $start = ($page - 1) * $pagelimit;

        $SESSION->save('alsp', $page);
        $SESSION->save('aliassearch', $search);

        $layout['pagetitle'] = trans('Alias Search Results');

        $SMARTY->assign('listdata', $listdata);
        $SMARTY->assign('customerlist', $LMS->GetAllCustomerNames());
        $SMARTY->assign('domainlist', $DB->GetAll('SELECT id, name FROM domains ORDER BY name'));
        $SMARTY->assign('pagelimit', $pagelimit);
        $SMARTY->assign('page', $page);
        $SMARTY->assign('start', $start);
        $SMARTY->assign('search', $search);
        $SMARTY->assign('aliaslist', $aliaslist);
        $SMARTY->display('alias/aliaslist.html');
        $SESSION->close();
        die;
    }
}

$layout['pagetitle'] = trans('Account, Alias, Domain Search');

$SMARTY->assign('customerlist', $LMS->GetAllCustomerNames());
$SMARTY->assign('search', isset($search) ? $search : $SESSION->get('aliassearch'));
$SMARTY->display('account/accountsearch.html');
