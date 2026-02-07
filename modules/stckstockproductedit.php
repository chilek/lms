<?php

$taxeslist = $LMS->GetTaxes();

if (!isset($_GET['id']) || !ctype_digit($_GET['id']))
	$SESSION->redirect('?m=stckproductlist');
elseif (! $LMSST->StockExists($_GET['id']))
	$SESSION->redirect('?m=stckproductlist');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Edit product on stock');
$error = NULL;

if (isset($_POST['productedit'])) {
	$productedit = $_POST['productedit'];
	$productedit['id'] = $_GET['id'];
	
        if (!preg_match('/^\d+[,.]{0,1}\d{0,2}$/i', $productedit['pricebuynet']))
		$error['pricenet'] = 'Wrong or missing price!';

	if (!preg_match('/^\d+[,.]{0,1}\d{0,2}$/i', $productedit['pricebuygross']))
		$error['pricegross'] = 'Wrong or missing price!';

	if ($productedit['sold'])
		$productedit['sold'] = 1;
	else
		$productedit['sold'] = '0';

	if (isset($productedit['pricesell']) && !preg_match('/^\d+[,.]{0,1}\d{0,2}$/i', $productedit['pricebuygross']))
		$error['pricesell'] = 'Wrong or missing price!';
	
	if (isset($productedit['leavedate']) && !$productedit['leavedate'] == '0') {
		if(preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $productedit['leavedate'])) {
			list($y, $m, $d) = explode('/', $productedit['leavedate']);
			if(checkdate($m, $d, $y)) {
				$id = mktime(0, 0, 0, $m, $d, $y);
				if($id > time())
					$error['leavedate'] = trans('Incorrect future date!');
			} else
				$error['leavedate'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
		} else
			$error['leavedate'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
	} else {
		unset($productedit['leavedate']);
		unset($productedit['pricesell']);
	}

	if (!$error) {
		if ($productedit['leavedate'])
			$productedit['leavedate'] = DateChange($productedit['leavedate']);
		$taxvalue = isset($productedit['taxid']) ? $taxeslist[$productedit['taxid']]['value'] : 0;
		if ($productedit['pricebuynet'] != 0) {
			$productedit['pricebuynet'] = f_round($productedit['pricebuynet']);
			$productedit['pricebuygross'] = f_round($productedit['pricebuynet'] * ($taxvalue / 100 + 1),2);
			$productedit['pricebuynet'] = f_round($productedit['pricebuygross'] / ($taxvalue / 100 + 1),2);
		} elseif ($productedit['pricebuygross'] != 0) {
			$productedit['pricebuygross'] = f_round($productedit['pricebuygross'], 2);
			$productedit['pricebuynet'] = f_round($productedit['pricebuygross'] / ($taxvalue / 100 + 1),2);
		}

		if ($LMSST->StockPositionEdit($productedit)) {
			$SMARTY->assign('success', 1);
			$SMARTY->assign('reload', 1);
		} else {
			$error['general'] = trans('Unknown error!');
		}
	}
} else {
	if (!$productedit = $LMSST->StockPositionGetById($_GET['id']))
		$SESSION->redirect('?m=stckproductlist');
}

$wlist = $LMSST->WarehouseGetList();
unset($wlist['total']);
unset($wlist['order']);
unset($wlist['direction']);

$SMARTY->assign('error', $error);
$SMARTY->assign('wlist', $wlist);
$SMARTY->assign('productedit', $productedit);
$SMARTY->assign('txlist', $taxeslist);
$SMARTY->display('stck/stckstockproductedit.html');

?>
