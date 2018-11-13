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

function GetAccountList($order='login,asc', $customer=NULL, $type=NULL, $kind=NULL, $domain='')
{
	global $DB, $ACCOUNTTYPES;

	list($order,$direction) = sscanf($order, '%[^,],%s');

	($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

	switch($order)
	{
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

	$quota_fields = array();
	foreach ($ACCOUNTTYPES as $typeidx => $atype)
		$quota_fields[] = 'p.quota_' . $atype['alias'];
	$list = $DB->GetAll('SELECT p.id, p.ownerid, p.login, p.lastlogin, 
			p.expdate, d.name AS domain, p.type, '
			. implode(', ', $quota_fields) . ', '
			.$DB->Concat('c.lastname', "' '",'c.name').' AS customername 
		FROM passwd p
		LEFT JOIN customers c ON c.id = p.ownerid 
		LEFT JOIN domains d ON d.id = p.domainid WHERE 1=1'
		.($customer != '' ? ' AND p.ownerid = '.intval($customer) : '')
		.($type ? ' AND p.type & '.$type.' = '.intval($type) : '')
		.($kind == 1 ? ' AND p.expdate!= 0 AND p.expdate < ?NOW?' : '')
		.($kind == 2 ? ' AND (p.expdate=0 OR p.expdate > ?NOW?)' : '')
		.($domain != '' ? ' AND p.domainid = '.intval($domain) : '')
		.($sqlord != '' ? $sqlord : '')
		);
	
	$list['total'] = empty($list) ? 0 : count($list);
	$list['order'] = $order;
	$list['type'] = $type;
	$list['kind'] = $kind;
	$list['customer'] = $customer;
	$list['domain'] = $domain;
	$list['direction'] = $direction;

	return $list;
}

if(!isset($_GET['o']))
	$SESSION->restore('alo', $o);
else
	$o = $_GET['o'];
$SESSION->save('alo', $o);

if(!isset($_GET['u']))
	$SESSION->restore('alu', $u);
else
	$u = $_GET['u'];
$SESSION->save('alu', $u);

if(!isset($_GET['t']))
	$SESSION->restore('alt', $t);
else
	$t = $_GET['t'];
$SESSION->save('alt', $t);

if(!isset($_GET['k']))
	$SESSION->restore('alk', $k);
else
	$k = $_GET['k'];
$SESSION->save('alk', $k);

if(!isset($_GET['d']))
	$SESSION->restore('ald', $d);
else
	$d = $_GET['d'];
$SESSION->save('ald', $d);

if ($SESSION->is_set('alp') && !isset($_GET['page']))
	$SESSION->restore('alp', $_GET['page']);
	    
$layout['pagetitle'] = trans('Accounts List');

$accountlist = GetAccountList($o, $u, $t, $k, $d);

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

$page = (empty($_GET['page']) ? 1 : $_GET['page']);
$pagelimit = ConfigHelper::getConfig('phpui.accountlist_pagelimit', $listdata['total']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('alp', $page);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->assign('accountlist',$accountlist);
$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('customerlist',$LMS->GetAllCustomerNames());
$SMARTY->assign('domainlist',$DB->GetAll('SELECT id, name FROM domains ORDER BY name'));
$SMARTY->display('account/accountlist.html');

?>
