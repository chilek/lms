<?php

/*
 * LMS version 1.9-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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
	global $DB;

	list($order,$direction) = sscanf($order, '%[^,],%s');

	($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

	switch($order)
	{
		case 'id':
			$sqlord = " ORDER BY passwd.id $direction";
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

	$list = $DB->GetAll(
	        'SELECT passwd.id AS id, ownerid, login, lastlogin, expdate, domains.name AS domain, type, quota_www, quota_sh, quota_mail, quota_ftp, '
		.$DB->Concat('customers.lastname', "' '",'customers.name').
		' AS customername FROM passwd LEFT JOIN customers ON customers.id = ownerid 
		LEFT JOIN domains ON domains.id = domainid WHERE 1=1'
		.($customer != '' ? ' AND ownerid = '.$customer : '')
		.($type ? ' AND type & '.$type.' = '.$type : '')
		.($kind == 1 ? ' AND expdate!= 0 AND expdate < ?NOW?' : '')
		.($kind == 2 ? ' AND (expdate=0 OR expdate > ?NOW?)' : '')
		.($domain != '' ? ' AND domainid = '.$domain : '')
		.($sqlord != '' ? $sqlord : '')
		);
	
	$list['total'] = sizeof($list);
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
	    
$page = (!isset($_GET['page']) ? 1 : $_GET['page']);
$pagelimit = (!isset($LMS->CONFIG['phpui']['accountlist_pagelimit']) ? $listdata['total'] : $LMS->CONFIG['phpui']['accountlist_pagelimit']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('alp', $page);

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

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->assign('accountlist',$accountlist);
$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('customerlist',$LMS->GetCustomerNames());
$SMARTY->assign('domainlist',$DB->GetAll('SELECT id, name FROM domains ORDER BY name'));
$SMARTY->display('accountlist.html');

?>
