<?php

$taxeslist = $LMS->GetTaxes();

if (!isset($_GET['id']) || !ctype_digit($_GET['id']))
	$SESSION->redirect('?m=stckproductlist');
elseif (! $LMSST->StockExists($_GET['id']))
	$SESSION->redirect('?m=stckproductlist');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$product =  $LMSST->StockPositionGetById($_GET['id']);
$product['warehouse'] = $LMSST->WarehouseGetNameById($product['warehouseid']);

$layout['pagetitle'] = trans('Stock position: $a', '('.$product['id'].') '.$product['pname']);

$SMARTY->assign('product', $product);

//$SMARTY->assign('wlist', $wlist);
if ($_GET['short'] == '1')
	$SMARTY->display('stck/stckstockproductinfoshort.html');
else
	$SMARTY->display('stck/stckstockproductinfo.html');

?>
