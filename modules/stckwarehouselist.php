<?php
$layout['pagetitle'] = trans('Warehouses');

if(!isset($_GET['o']))
	$SESSION->restore('swlo', $o);
else
	$o = $_GET['o'];

$SESSION->save('swlo', $o);

$warehouselist = $LMSST->WarehouseGetList($o);

$listdata['total'] = $warehouselist['total'];
$listdata['direction'] = $warehouselist['direction'];
$listdata['order'] = $warehouselist['order'];
unset($warehouselist['total']);
unset($warehouselist['direction']);
unset($warehouselist['order']);

if(!isset($_GET['page']))
        $SESSION->restore('swlp', $_GET['page']);

$page = (!$_GET['page'] ? 1 : $_GET['page']);

$pagelimit = (!ConfigHelper::getConfig('phpui.warehouselist_pagelimit') ? 100 : ConfigHelper::getConfig('phpui.warehouselist_pagelimit'));
$start = ($page - 1) * $pagelimit;

$SESSION->save('swlp', $page);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('page',$page);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('start',$start);
$SMARTY->assign('warehouselist', $warehouselist);
$SMARTY->assign('listdata', $listdata);
$SMARTY->display('stck/stckwarehouselist.html');
?>
