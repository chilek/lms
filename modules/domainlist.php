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
 *  $Id: domainlist.php,v 1.34 2012/01/02 11:01:34 alec Exp $
 */

function GetDomainList($order = 'name,asc', $customer = '', $filtr = '') {
	global $DB;

	list($order, $direction) = sscanf($order, '%[^,],%s');

	($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

	switch ($order) {
		case 'id':
			$sqlord = " ORDER BY d.id $direction";
			break;
		case 'description':
			$sqlord = " ORDER BY d.description $direction";
			break;
		case 'customer':
			$sqlord = " ORDER BY customername $direction";
			break;
		case 'type':
			$sqlord = " ORDER BY type $direction";
			break;
		default:
			$sqlord = " ORDER BY d.name $direction";
			break;
	}

	if ($filtr == '0-9') {
		if (ConfigHelper::getConfig('database.type') == 'postgres')
			$where[] = "d.name ~ '^[0-9]'";
		else
			$where[] = "d.name REGEXP '^[0-9]'";
	} else if ($filtr) {
		$filtr = substr($filtr, 0, 1);
		$where[] = 'd.name ?LIKE? ' . $DB->Escape("$filtr%");
	}
	if ($customer != '')
		$where[] = 'd.ownerid = ' . intval($customer);

	$where = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';

	$list = $DB->GetAll('SELECT d.id AS id, d.name AS name, d.description, 
		d.ownerid, d.type, (SELECT COUNT(*) FROM passwd WHERE domainid = d.id) AS cnt, '
			. $DB->Concat('lastname', "' '", 'c.name') . ' AS customername 
		FROM domains d
		LEFT JOIN customers c ON (d.ownerid = c.id)'
			. $where
			. ($sqlord != '' ? $sqlord : ''));

	$list['total'] = empty($list) ? 0 : count($list);
	$list['order'] = $order;
	$list['direction'] = $direction;
	$list['customer'] = $customer;

	return $list;
}

function GetDomainFirstLetters($customer = '') {
	global $DB;

	if ($list = $DB->GetAllByKey('SELECT DISTINCT UPPER(SUBSTR(name, 1, 1)) AS idx
		FROM domains'
			. ($customer != '' ? ' WHERE ownerid = ' . intval($customer) : '')
			. ' ORDER BY 1', 'idx')) {
		foreach ($list as $idx => $row)
			if (preg_match('/[0-9]/', $row['idx'])) {
				$list['0-9'] = 1;
				unset($list[$idx]);
			}
	}

	return $list;
}

if (!isset($_GET['o']))
	$SESSION->restore('dlo', $o);
else
	$o = $_GET['o'];
$SESSION->save('dlo', $o);

if (!isset($_GET['c']))
	$SESSION->restore('dlc', $c);
else
	$c = $_GET['c'];
$SESSION->save('dlc', $c);

if (!isset($_GET['f']))
	$SESSION->restore('dfi', $f);
else
	$f = $_GET['f'];
$SESSION->save('dfi', $f);

if ($SESSION->is_set('dlp') && !isset($_GET['page']))
	$SESSION->restore('dlp', $_GET['page']);

$layout['pagetitle'] = trans('Domains List');

$domainlist = GetDomainList($o, $c, $f);
$domaincount = GetDomainFirstLetters($c);

$listdata['total'] = $domainlist['total'];
$listdata['order'] = $domainlist['order'];
$listdata['direction'] = $domainlist['direction'];
$listdata['customer'] = $domainlist['customer'];
$listdata['name'] = $f;

unset($domainlist['total']);
unset($domainlist['order']);
unset($domainlist['direction']);
unset($domainlist['customer']);

$page = (empty($_GET['page']) ? 1 : $_GET['page']);
$pagelimit = ConfigHelper::getConfig('phpui.domainlist_pagelimit', $listdata['total']);

if ($page > ceil($listdata['total'] / $pagelimit))
	$page = 1;

$start = ($page - 1) * $pagelimit;

$SESSION->save('dlp', $page);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->assign('domainlist', $domainlist);
$SMARTY->assign('domaincount', $domaincount);
$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('customerlist', $LMS->GetCustomerNames());
$SMARTY->display('domain/domainlist.html');
?>
