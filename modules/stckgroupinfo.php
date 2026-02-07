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

if(! $LMSST->GroupExists($_GET['id']) || !ctype_digit($_GET['id'])) {
	$SESSION->redirect('?m=stckgrouplist');
}

$groupinfo = $LMSST->GroupGetInfoById($_GET['id']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Group Info: $a', $groupinfo['name']);

if(!isset($_GET['o']))
	$SESSION->restore('sgiplo', $o);
else
	$o = $_GET['o'];

$SESSION->save('sgiplo', $o);

if(!isset($_GET['ssp']))
	$SESSION->restore('sgiplssp', $ssp);
else
	$ssp = $_GET['ssp'];

$SESSION->save('sgiplssp', $ssp);

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

$productlist = $LMSST->StockProductList($o, NULL, $ssp, NULL, NULL, NULL, $groupinfo['id'], $filter);
//$productlist = $LMSST->StockList($o, NULL, $groupinfo['id']);
$listdata['total'] = $productlist['total'];
$listdata['totalvn'] = $productlist['totalvn'];
$listdata['totalvg'] = $productlist['totalvg'];
$listdata['totalpcs'] = $productlist['totalpcs'];
$listdata['direction'] = $productlist['direction'];
$listdata['order'] = $productlist['order'];
$listdata['id'] = $groupinfo['id']; 
unset($productlist['total']);
unset($productlist['direction']);
unset($productlist['order']);
unset($productlist['totalpcs']);
unset($productlist['totalvg']);
unset($productlist['totalvn']);

if(!isset($_GET['page']))
	$SESSION->restore('sgipl', $_GET['page']);

$page = (! $_GET['page'] ? 1 : $_GET['page']);
$pagelimit = (!ConfigHelper::getConfig('phpui.productlist_pagelimit') ? 100 : ConfigHelper::getConfig('phpui.productlist_pagelimit'));
$start = ($page - 1) * $pagelimit;

$SESSION->save('sgipl', $page);

$SMARTY->assign('filter', $filter);
$SMARTY->assign('ssp', $ssp);
$SMARTY->assign('page',$page);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('start',$start);
$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('productlist', $productlist);
$SMARTY->assign('groupinfo',$groupinfo);
$SMARTY->display('stck/stckgroupinfo.html');
?>
