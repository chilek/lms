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

function GetAliasList($order = 'login,asc', $customer = null, $domain = '')
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

    $list = $DB->GetAll('SELECT a.id, a.login, d.name AS domain, domainid, s.accounts, s.forwards, s.cnt 
		FROM aliases a
		JOIN domains d ON (d.id = a.domainid)
		JOIN (SELECT COUNT(*) AS cnt, '.$DB->GroupConcat('(SELECT '.$DB->Concat('p.login', "'@'", 'pd.name').' 
			FROM passwd p 
			JOIN domains pd ON (p.domainid = pd.id) 
			WHERE p.id = aliasassignments.accountid)').' AS accounts, '
            .$DB->GroupConcat('CASE WHEN mail_forward <> \'\' THEN mail_forward ELSE NULL END').' AS forwards, 
			aliasid 
			FROM aliasassignments GROUP BY aliasid) s ON (a.id = s.aliasid)
		WHERE 1=1'
        .($customer != '' ? ' AND d.ownerid = '.intval($customer) : '')
        .($domain != '' ? ' AND a.domainid = '.intval($domain) : '')
        .($sqlord != '' ? $sqlord : ''));
    
    $list['total'] = empty($list) ? 0 : count($list);
    $list['order'] = $order;
    $list['customer'] = $customer;
    $list['domain'] = $domain;
    $list['direction'] = $direction;

    return $list;
}

if (!isset($_GET['o'])) {
    $SESSION->restore('alo', $o);
} else {
    $o = $_GET['o'];
}
$SESSION->save('alo', $o);

if (!isset($_GET['u'])) {
    $SESSION->restore('alu', $u);
} else {
    $u = $_GET['u'];
}
$SESSION->save('alu', $u);

if (!isset($_GET['d'])) {
    $SESSION->restore('ald', $d);
} else {
    $d = $_GET['d'];
}
$SESSION->save('ald', $d);

if ($SESSION->is_set('allp') && !isset($_GET['page'])) {
    $SESSION->restore('allp', $_GET['page']);
}

$layout['pagetitle'] = trans('Aliases List');

$aliaslist = GetAliasList($o, $u, $d);

$listdata['total'] = $aliaslist['total'];
$listdata['order'] = $aliaslist['order'];
$listdata['direction'] = $aliaslist['direction'];
$listdata['customer'] = $aliaslist['customer'];
$listdata['domain'] = $aliaslist['domain'];

unset($aliaslist['total']);
unset($aliaslist['order']);
unset($aliaslist['kind']);
unset($aliaslist['customer']);
unset($aliaslist['domain']);
unset($aliaslist['direction']);
        
$page = (empty($_GET['page']) ? 1 : $_GET['page']);
$pagelimit = ConfigHelper::getConfig('phpui.aliaslist_pagelimit', $listdata['total']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('allp', $page);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->assign('aliaslist', $aliaslist);
$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('customerlist', $LMS->GetCustomerNames());
$SMARTY->assign('domainlist', $DB->GetAll('SELECT id, name FROM domains ORDER BY name'));
$SMARTY->display('alias/aliaslist.html');
