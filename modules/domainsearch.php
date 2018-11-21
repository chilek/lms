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

function GetDomainList($order='name,asc', $search, $customer='')
{
	global $DB;

	list($order,$direction) = sscanf($order, '%[^,],%s');

	($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

	switch($order)
        {
	        case 'id':
		        $sqlord = " ORDER BY d.id $direction";
		break;
		case 'description':
		        $sqlord = " ORDER BY d.description $direction";
		break;
		case 'customer':
		        $sqlord = " ORDER BY customername $direction";
		break;
		default:
			$sqlord = " ORDER BY d.name $direction";
		break;
	}
	
	if(!empty($search['domain']))
		$where[] = 'd.name ?LIKE? '.$DB->Escape('%'.$search['domain'].'%'); 
	if(!empty($search['description']))
		$where[] = 'd.description ?LIKE? '.$DB->Escape('%'.$search['description'].'%'); 
	if($customer != '')
		$where[] = 'd.ownerid = '.intval($customer);

	$where = isset($where) ? 'WHERE '.implode(' AND ', $where) : '';

        $list = $DB->GetAll('SELECT d.id AS id, d.name AS name, d.description,
	                d.ownerid, (SELECT COUNT(*) FROM passwd WHERE domainid = d.id) AS cnt, '
			.$DB->Concat('lastname', "' '",'c.name').' AS customername
			FROM domains d
			LEFT JOIN customers c ON (d.ownerid = c.id) '
			.$where
			.($sqlord != '' ? $sqlord : ''));
	
	$list['total'] = count($list);
	$list['order'] = $order;
	$list['direction'] = $direction;
	$list['customer'] = $customer;

	return $list;
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$search = array();

if(isset($_POST['search']))
	$search = $_POST['search'];

if(!isset($_GET['o']))
	$SESSION->restore('dso', $o);
else
	$o = $_GET['o'];
$SESSION->save('dso', $o);

if(isset($_GET['c']))
	$c = $_GET['c'];
elseif(count($search))
	$c = isset($search['customerid']) ? $search['customerid'] : '';
else
	$SESSION->restore('dsc', $c);
$SESSION->save('dsc', $c);

if ($SESSION->is_set('dsp') && !isset($_GET['page']) && !isset($search))
	$SESSION->restore('dsp', $_GET['page']);

if(count($search) || isset($_GET['s']))
{
	$search = count($search) ? $search : $SESSION->get('domainsearch');

	if(!$error)
	{
		$domainlist = GetDomainList($o, $search, $c);

		$listdata['total'] = $domainlist['total'];
		$listdata['order'] = $domainlist['order'];
		$listdata['direction'] = $domainlist['direction'];
		$listdata['customer'] = $domainlist['customer'];

		unset($domainlist['total']);
		unset($domainlist['order']);
		unset($domainlist['customer']);
		unset($domainlist['direction']);
    
		$page = (! isset($_GET['page']) ? 1 : $_GET['page']); 
		$pagelimit = ConfigHelper::getConfig('phpui.domainlist_pagelimit', $queuedata['total']);
		$start = ($page - 1) * $pagelimit;

		$SESSION->save('dsp', $page);
		$SESSION->save('domainsearch', $search);

		$layout['pagetitle'] = trans('Domain Search Results');

		$SMARTY->assign('listdata',$listdata);
		$SMARTY->assign('customerlist',$LMS->GetAllCustomerNames());
		$SMARTY->assign('pagelimit',$pagelimit);
		$SMARTY->assign('page',$page);
		$SMARTY->assign('start',$start);
		$SMARTY->assign('search', $search);
		$SMARTY->assign('domainlist',$domainlist);
		$SMARTY->display('domain/domainlist.html');
		$SESSION->close();
		die;
	}
}

$layout['pagetitle'] = trans('Account, Alias, Domain Search');

$SMARTY->assign('customerlist',$LMS->GetAllCustomerNames());
$SMARTY->assign('search', isset($search) ? $search : $SESSION->get('domainsearch'));
$SMARTY->display('account/accountsearch.html');

?>
