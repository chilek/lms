<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2011 LMS Developers
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
 *  $Id: netdevinfo.php,v 1.34 2011/01/18 08:12:23 alec Exp $
 */

if(! $LMSST->ProductExists($_GET['id']) || !ctype_digit($_GET['id'])) {
	$SESSION->redirect('?m=stckproductlist');
}

$productinfo = $LMSST->ProductGetInfoById($_GET['id']);
if (!$productinfo['id']) {
	echo "Unknown error! Unable to get product";print_r($productinfo);exit;
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Product Info: $a', $productinfo['mname']." ".$productinfo['name']);

if(!isset($_GET['o']))
	$SESSION->restore('spiplo', $o);
else
	$o = $_GET['o'];

$SESSION->save('spiplo', $o);

if(!isset($_GET['ssp']))
	$SESSION->restore('spiplssp', $ssp);
else
	$ssp = $_GET['ssp'];

$SESSION->save('spiplssp', $ssp);

if (isset($_POST['filter'])) {
	if ($_POST['filter']['sn'])
		$filter['sn'] = $_POST['filter']['sn'];
	else
		$filter['sn'] = NULL;
	
	if ($_POST['filter']['name'])
		$filter['name'] = $_POST['filter']['name'];
	else
		$filter['name'] = NULL;
}

$productlist = $LMSST->StockProductList($o, $productinfo['id'], $ssp, NULL, NULL, NULL, NULL, $filter);
$listdata['total'] = $productlist['total'];
$listdata['totalvn'] = $productlist['totalvn'];
$listdata['totalvg'] = $productlist['totalvg'];
$listdata['totalpcs'] = $productlist['totalpcs'];
$listdata['direction'] = $productlist['direction'];
$listdata['order'] = $productlist['order'];
$listdata['id'] = $productinfo['id'];
unset($productlist['total']);
unset($productlist['direction']);
unset($productlist['order']);
unset($productlist['totalpcs']);
unset($productlist['totalvg']);
unset($productlist['totalvn']);

if(!isset($_GET['page']))
	$SESSION->restore('spipl', $_GET['page']);

$page = (! $_GET['page'] ? 1 : $_GET['page']);
$pagelimit = (!isset($CONFIG['phpui']['productlist_pagelimit']) ? $listdata['total'] : $CONFIG['phpui']['productlist_pagelimit']);
$start = ($page - 1) * $pagelimit;

if ($start > $listdata['total']) {
        $page = 1;
        $start = 0;
}

$SESSION->save('smipl', $page);

$SMARTY->assign('filter', $filter);
$SMARTY->assign('page',$page);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('start',$start);
$SMARTY->assign('ssp', $ssp);
$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('productlist', $productlist);
$SMARTY->assign('productinfo',$productinfo);
$SMARTY->display('stck/stckproductinfo.html');
?>
