<?php

if (!isset($_GET['id']) || !ctype_digit($_GET['id']))
	$SESSION->redirect('?m=stckproductlist');
elseif (! $LMSST->ReceiveNoteExists($_GET['id']))
	$SESSION->redirect('?m=stckproductlist');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Edit receive note: $a', $_GET['id']);

$taxeslist = $LMS->GetTaxes();
$quantities = $LMSST->QuantityGetList();
$warehouses = $LMSST->WarehouseGetList('name');
unset($warehouses['total']);
unset($warehouses['order']);
unset($warehouses['direction']);
unset($quantities['order']);
unset($quantities['direction']);
unset($quantities['total']);

$receivenoteedit = $LMSST->ReceiveNoteGetInfoById($_GET['id']);
$osid = $receivenoteedit['supplierid'];

$productlist = $LMSST->StockProductList(NULL, NULL, 1, $receivenoteedit['id']);
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

$rnepl = array();

$SESSION->restore('rnepl', $rnepl);

if (!isset($rnepl['docid'])) {
	if (ctype_digit($_GET['id']))
		$rnepl['rnid'] = $_GET['id'];
	else
		die;

	$rnepl['doc']['net'] = $receivenoteedit['netvalue'];
	$rnepl['doc']['gross'] = $receivenoteedit['grossvalue'];
} elseif ($rnepl['rnid'] != $_GET['id']) {
	$SESSION->remove($rnepl);
	$rnepl = array();
}

if (isset($_GET['sid']) && ctype_digit($_GET['sid'])) {
        $receivenoteedit['supplierid'] = $_GET['sid'];
	$receivenoteedit['sname'] = $LMS->GetCustomerName($receivenoteedit['supplierid']);
}

if (isset($_POST['receivenoteedit'])) {
	$receivenoteedit = $_POST['receivenoteedit'];
	$receivenoteedit['osid'] = $osid;
	$receivenoteedit['id'] = $_GET['id'];
	
	if ($receivenoteedit['supplierid'] == '' || !ctype_digit($receivenoteedit['supplierid']))
		$error['supplier'] = trans('Incorrect supplier!');
	
	if (!$LMS->CustomerExists($receivenoteedit['supplierid']))
		$error['supplier'] = trans('Incorrect supplier!');
	else
		$receivenoteedit['sname'] = $LMS->GetCustomerName($receivenoteedit['supplierid']);

	if ($receivenoteedit['datesettlement'] == '' || !isset($receivenoteedit['datesettlement']))
		$error['datesettlement'] = trans('Settlement date can`t be empty!');

	if(preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $receivenoteedit['datesettlement'])) {
		list($y, $m, $d) = explode('/', $receivenoteedit['datesettlement']);
		if(checkdate($m, $d, $y)) {
			$id = mktime(0, 0, 0, $m, $d, $y);
			if($id > time())
				$error['datesettlement'] = trans('Incorrect future date!');
			else
				$receivenoteedit['datesettlement'] = DateChange($receivenoteedit['datesettlement']);
		} else
			$error['datesettlement'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
	} else
		$error['datesettlement'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');

	if ($receivenoteedit['datesale'] == '' || !isset($receivenoteedit['datesale']))
		$error['sale'] = trans('Sale date can`t be empty!');

	if(preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $receivenoteedit['datesale'])) {
		list($y, $m, $d) = explode('/', $receivenoteedit['datesale']);
		if(checkdate($m, $d, $y)) {
			$id = mktime(0, 0, 0, $m, $d, $y);
			if($id > time())
				$error['datesale'] = trans('Incorrect future date!');
			else
				$receivenoteedit['datesale'] = DateChange($receivenoteedit['datesale']);
		} else
			$error['datesale'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
	} else
		$error['datesale'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');

	if ($receivenoteedit['deadline'] == '' || !isset($receivenoteedit['deadline']))
		$error['deadline'] = trans('Deadline date can`t be empty!');

	if(preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $receivenoteedit['deadline'])) {
		list($y, $m, $d) = explode('/', $receivenoteedit['deadline']);
		if(checkdate($m, $d, $y)) {
			$id = mktime(0, 0, 0, $m, $d, $y);
			$receivenoteedit['deadline'] = DateChange($receivenoteedit['deadline']);
		} else
			$error['deadline'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
	} else
		$error['deadline'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');

	if ($receivenoteedit['number'] == '')
		$error['number'] = trans('Document number can`t be empty!');
	else
		$receivenoteedit['number'] = strtoupper($receivenoteedit['number']);

	if (!$error) {
		if ($id = $LMSST->ReceiveNoteEdit($receivenoteedit, $productlist, $rnepl)) {
			$SESSION->remove('rnepl');
			$SESSION->redirect('?m=stckreceivenoteinfo&id='.$id);
		}
		else
			$error['supplier'] = trans('Unknown error!');
	}
}

if (isset($_POST['rnepl']['product']) && !isset($_GET['action'])) {
	$itemdata = $_POST['rnepl']['product'];
	$itemdata['serial'] = $_POST['receivenote']['product']['serial'];
	
	if (!ctype_digit($itemdata['warehouse']))
		$error['warehouse'] = 'Incorrect warehouse!';

	$itemdata['warehousename'] = $LMSST->WarehouseGetNameById($itemdata['warehouse']);

	if (!ctype_digit($itemdata['pid']))
		$error['product'] = trans('Product not in databse');

	if (!ctype_digit($itemdata['count']) || $itemdata['count'] < 1)
		$error['count'] = trans('Incorrect ammount!');

	if (ctype_digit($itemdata['unit']))
		$itemdata['unitname'] = $LMSST->QuantityGetNameById($itemdata['unit']);
	else
		$error['unit'] = trans('Incorrect unit!');

	if (!preg_match('/^\d+[,.]{0,1}\d{0,2}$/i', $itemdata['price']['net']) && !preg_match('/^\d+[,.]{0,1}\d{0,2}$/i', $itemdata['price']['gross']))
		$error['price'] = trans('Wrong or missing price!');

        if (!ctype_digit($itemdata['gtu_code']))
		unset($itemdata['gtu_code']);
	else
		$itemdata['gtu_data'] = $LMSST->GTUCodeGetById($itemdata['gtu_code']);

	if (!ctype_digit($itemdata['warranty']))
                unset($itemdata['warranty']);

	$itemdata['price']['tax'] = isset($itemdata['price']['taxid']) ? $taxeslist[$itemdata['price']['taxid']]['label'] : '';

	if (!$error) {
		$taxvalue = isset($itemdata['price']['taxid']) ? $taxeslist[$itemdata['price']['taxid']]['value'] : 0;
		if ($itemdata['price']['net'] != 0) {
			$itemdata['price']['net'] = f_round($itemdata['price']['net']);
			$itemdata['price']['gross'] = f_round($itemdata['price']['net'] * ($taxvalue / 100 + 1),2);
			$itemdata['price']['net'] = f_round($itemdata['price']['gross'] / ($taxvalue / 100 + 1),2);
		} elseif ($itemdata['price']['gross'] != 0) {
			$itemdata['price']['gross'] = f_round($itemdata['price']['gross'], 2);
			$itemdata['price']['net'] = f_round($itemdata['price']['gross'] / ($taxvalue / 100 + 1),2);
		}

		if ($itemdata['count'] > 1) {
			$serials = array();
			$rnepl['doc']['net'] += $itemdata['count']*$itemdata['price']['net'];
			$rnepl['doc']['gross'] += $itemdata['count']*$itemdata['price']['gross'];
			for ($i = 0; $i < $itemdata['count']; ++ $i) {
				$serials[] = strtoupper($itemdata['serial'][$i]);
				unset($itemdata['serial'][$i]);
			}
			$itemdata['count'] = 1;
			$itemdata['serial'] = strtoupper(array_shift($serials));
			$rnepl['product'][] = $itemdata;
			foreach($serials as $serial) {
				$itemdata['serial'] = $serial;
				$rnepl['product'][] = $itemdata;
			}
		} else {
			$itemdata['serial'] = strtoupper($itemdata['serial'][0]);
			$rnepl['product'][] = $itemdata;
			$rnepl['doc']['net'] += $itemdata['price']['net'];
			$rnepl['doc']['gross'] += $itemdata['price']['gross'];
		}
		
		unset($itemdata);

		$SESSION->remove('rnepl');
		$SESSION->save('rnepl', $rnepl);
	} else {
		$SMARTY->assign('item', $itemdata);
	}
} elseif (isset($_GET['action']) && ctype_digit($_GET['posid'])) {
	switch($_GET['action']) {
		case 'del':
			$rnepl['doc']['net'] -= $rnepl['product'][$_GET['posid']]['price']['net'];
			$rnepl['doc']['gross'] -= $rnepl['product'][$_GET['posid']]['price']['gross'];
			unset($rnepl['product'][$_GET['posid']]);
			$SESSION->remove('rnepl');
			$SESSION->save('rnepl', $rnepl);
			break;
		case 'edit':
			$itemdata = $rnepl['product'][$_GET['posid']];
			$rnepl['doc']['net'] -= $rnepl['product'][$_GET['posid']]['price']['net'];
			$rnepl['doc']['gross'] -= $rnepl['product'][$_GET['posid']]['price']['gross'];
			unset($rnepl['product'][$_GET['posid']]);
			$SESSION->remove('rnepl');
			$SESSION->save('rnepl', $rnepl);
			$SMARTY->assign('itemdata', $itemdata);
			break;
		default:
			break;
	}
} elseif (isset($_GET['action'])) {
	switch($_GET['action']) {
	case 'cancel':
		$SESSION->remove('rnepl');
		$SESSION->redirect('?m=stckreceivenoteedit&id='.$receivenoteedit['id']);
	break;
	case 'save':
		$rnepl['doc']['supplierid'] = $receivenoteedit['supplierid'];
		$rnepl['doc']['number'] = $receivenoteedit['id'];
		$LMSST->ReceiveNotePositionAdd($rnepl);
		$SESSION->remove('rnepl');
		$SESSION->redirect('?m=stckreceivenoteinfo&id='.$receivenoteedit['id']);
		break;
	}
}

/*$productlist = $LMSST->StockProductList($o, NULL, 1, $receivenoteedit['id']);
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
unset($productlist['totalvn']);*/

if(!isset($_GET['page']))
	$SESSION->restore('srnepl', $_GET['page']);

$page = (! $_GET['page'] ? 1 : $_GET['page']);
$pagelimit = (! $CONFIG['phpui']['productlist_pagelimit'] ? $listdata['total'] : $CONFIG['phpui']['productlist_pagelimit']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('srnepl', $page);

//print_r($receivenoteedit);

$SMARTY->assign('ssp', 1);
$SMARTY->assign('page',$page);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('start',$start);
$SMARTY->assign('rnepl', $rnepl);
$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('productlist', $productlist);
$SMARTY->assign('warehouses', $warehouses);
$SMARTY->assign('taxeslist', $taxeslist);
$SMARTY->assign('quantities', $quantities);
$SMARTY->assign('error', $error);
$SMARTY->assign('gtucodes', $LMSST->GTUCodeList(array('active' => 1,'deleted' => 0)));
$SMARTY->assign('receivenoteedit', $receivenoteedit);
$SMARTY->display('stck/stckreceivenoteedit.html');

?>
