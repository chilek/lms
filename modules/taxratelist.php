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

function GetTaxRateList($order='name,asc')
{
	global $DB;

	list($order,$direction) = sscanf($order, '%[^,],%s');

	($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

	switch($order)
	{
		case 'id':
			$sqlord = " ORDER BY id $direction";
		break;
		case 'label':
			$sqlord = " ORDER BY label $direction";
		break;
		default:
			$sqlord = " ORDER BY value $direction";
		break;
	}

	$list = $DB->GetAll('SELECT * FROM taxes'.($sqlord != '' ? $sqlord : ''));
	
	$list['total'] = empty($list) ? 0 : count($list);
	$list['order'] = $order;
	$list['direction'] = $direction;

	return $list;
}

if(!isset($_GET['o']))
	$SESSION->restore('trlo', $o);
else
	$o = $_GET['o'];
$SESSION->save('trlo', $o);

if ($SESSION->is_set('trlp') && !isset($_GET['page']))
	$SESSION->restore('trlp', $_GET['page']);

$page = (!isset($_GET['page']) ? 1 : $_GET['page']); 
$pagelimit = ConfigHelper::getConfig('phpui.taxratelist_pagelimit', $listdata['total']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('trlp', $page);

$layout['pagetitle'] = trans('Tax Rates List');

$taxratelist = GetTaxRateList($o);
$listdata['total'] = $taxratelist['total'];
$listdata['order'] = $taxratelist['order'];
$listdata['direction'] = $taxratelist['direction'];
unset($taxratelist['total']);
unset($taxratelist['order']);
unset($taxratelist['direction']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->assign('taxratelist', $taxratelist);
$SMARTY->assign('listdata', $listdata);
$SMARTY->display('taxrate/taxratelist.html');

?>
