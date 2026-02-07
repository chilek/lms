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

if(!ctype_digit($_GET['id']) || !$LMSST->ReceiveNoteExists($_GET['id'])) {
	$SESSION->redirect('?m=stckreceivenotelist');
}

switch ($_GET['action']) {
	case 'srna':
		$LMSST->ReceiveNoteAccount($_GET['id']);
		$SESSION->redirect('?m=stckreceivenoteinfo&id='.$_GET['id']);
		break;
	default:
		break;
}

$receivenoteinfo = $LMSST->ReceiveNoteGetInfoById($_GET['id']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Receive Note Info: $a', $receivenoteinfo['number'].' - '.$receivenoteinfo['sname']);

if(!isset($_GET['o']))
	$SESSION->restore('srniplo', $o);
else
	$o = $_GET['o'];

$SESSION->save('srniplo', $o);

if(!isset($_GET['ssp']))
	$SESSION->restore('srniplssp', $ssp);
else
	$ssp = $_GET['ssp'];

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

$SESSION->save('srniplssp', $ssp);

$productlist = $LMSST->StockProductList($o, NULL, $ssp, $receivenoteinfo['id'], NULL, NULL, NULL, $filter);
$listdata['total'] = $productlist['total'];
$listdata['totalvn'] = $productlist['totalvn'];
$listdata['totalvg'] = $productlist['totalvg'];
$listdata['totalpcs'] = $productlist['totalpcs'];
$listdata['direction'] = $productlist['direction'];
$listdata['order'] = $productlist['order'];
$listdata['id'] = $receivenoteinfo['id'];
unset($productlist['total']);
unset($productlist['direction']);
unset($productlist['order']);
unset($productlist['totalpcs']);
unset($productlist['totalvg']);
unset($productlist['totalvn']);

if(!isset($_GET['page']))
	$SESSION->restore('smirnpl', $_GET['page']);

$page = (! $_GET['page'] ? 1 : $_GET['page']);

$SESSION->save('smirnpl', $page);

$pagelimit = (! $CONFIG['phpui']['productlist_pagelimit'] ? $listdata['total'] : $CONFIG['phpui']['productlist_pagelimit']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('smipl', $page);
$SMARTY->assign('paytypes', Localisation::arraySort(Utils::array_column($GLOBALS['PAYTYPES'], 'label')));
$SMARTY->assign('ssp', $ssp);
$SMARTY->assign('page', $page);
$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('start', $start);
$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('productlist', $productlist);
$SMARTY->assign('receivenoteinfo', $receivenoteinfo);
$SMARTY->display('stck/stckreceivenoteinfo.html');
?>
