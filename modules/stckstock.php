<?php
$layout['pagetitle'] = trans('Stock');

if(!isset($_GET['o']))
	$SESSION->restore('splo', $o);
else
	$o = $_GET['o'];

$SESSION->save('splo', $o);

if (isset($_POST['stock'])) {
	$stock = $_POST['stock'];
	foreach($stock as $k => $v) {
		if (!ctype_digit($v) && $v != "") {
			unset($stock);
			break;
		}
		if ($v == "") {
			$stock[$k] = NULL;
		}
	}
	$SESSION->save('splfl', $stock);
} else {
	$SESSION->restore('splfl', $stock);
}

if (!$stock['warehouse'])
	$stock['warehouse'] = $LMSST->WarehouseGetDefaultId();

$productlist = $LMSST->StockList($o, $stock['manufacturer'], $stock['group'], $stock['warehouse']);
$listdata['total'] = $productlist['total'];
$listdata['totalvn'] = $productlist['totalvn'];
$listdata['totalvg'] = $productlist['totalvg'];
$listdata['totalpcs'] = $productlist['totalpcs'];
$listdata['direction'] = $productlist['direction'];
$listdata['order'] = $productlist['order'];
unset($productlist['total']);
unset($productlist['direction']);
unset($productlist['order']);
unset($productlist['totalpcs']);
unset($productlist['totalvg']);
unset($productlist['totalvn']);

$warehouselist = $LMSST->WarehouseGetList($o);
unset($warehouselist['total']);
unset($warehouselist['direction']);
unset($warehouselist['order']);

$manufacturerlist = $LMSST->ManufacturerGetList($o);
unset($manufacturerlist['total']);
unset($manufacturerlist['direction']);
unset($manufacturerlist['order']);

$grouplist = $LMSST->GroupGetList($o);
$params['sql4'] = sprintf('%.4f', microtime(true) - START_TIME);
unset($grouplist['total']);
unset($grouplist['direction']);
unset($grouplist['order']);


if(!isset($_GET['page']))
        $SESSION->restore('sslp', $_GET['page']);

$page = (! $_GET['page'] ? 1 : $_GET['page']);
$pagelimit = (!ConfigHelper::getConfig('phpui.productlist_pagelimit') ? 100 : ConfigHelper::getConfig('phpui.productlist_pagelimit'));
$start = ($page - 1) * $pagelimit;

$SESSION->save('sslp', $page);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);
$SMARTY->assign('error', $error);
$SMARTY->assign('page',$page);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('start',$start);
$SMARTY->assign('productlist', $productlist);
$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('warehouses', $warehouselist);
$SMARTY->assign('manufacturers', $manufacturerlist);
$SMARTY->assign('groups', $grouplist);
$SMARTY->assign('stockfl', $stock);
$SMARTY->display('stck/stckstock.html');
?>
