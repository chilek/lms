<?php
$layout['pagetitle'] = trans('Add stock from receive note');
$error = NULL;
$taxeslist = $LMS->GetTaxes();
$quantities = $LMSST->QuantityGetList();
unset($quantities['order']);
unset($quantities['direction']);
unset($quantities['total']);

$receivenote = array();

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SESSION->restore('receivenote', $receivenote);

if (!$receivenote)
	$SESSION->redirect('?m=stckreceiveadd');

if (isset($_POST['receivenote']['product']) && !isset($_GET['action'])) {

	$itemdata = $_POST['receivenote']['product'];
	
	if (!ctype_digit($itemdata['gtu_code']))
		unset($itemdata['gtu_code']);

	if (!ctype_digit($itemdata['warranty']))
		unset($itemdata['warranty']);
	
	if (!ctype_digit($itemdata['warehouse']))
		$error['warehouse'] = trans('Incorrect warehouse!');

	$itemdata['warehousename'] = $LMSST->WarehouseGetNameById($itemdata['warehouse']);

	if (!ctype_digit($itemdata['pid']))
		$error['product'] = trans('Product not in database');

	if (!ctype_digit($itemdata['count']) || $itemdata['count'] < 1)
		$error['count'] = trans('Incorrect ammount!');

	if (!ctype_digit($itemdata['unit']))
		$error['unit'] = trans('Unknown unit');

	$itemdata['unitname'] = $LMSST->QuantityGetNameById($itemdata['unit']);

	if (!$itemdata['unitname'])
		$error['unit'] = trans('Unit not in databse');

	$itemdata['quantity'] = $LMSST->QuantityGetByProductId($itemdata['pid']);

	//if (isset($itemdata['gtu_code']) && !ctype_digit($itemdata['gtu_code']))
	//	$error['gtu_code'] = trans('Unknown GTU code');

	if (!preg_match('/^\d+[,.]{0,1}\d{0,2}$/i', $itemdata['price']['net']) && !preg_match('/^\d+[,.]{0,1}\d{0,2}$/i', $itemdata['price']['gross']))
		$error['price'] = trans('Wrong or missing price!');
	else {
		$itemdata['price']['net'] = f_round($itemdata['price']['net']);
		$itemdata['price']['gross'] = f_round($itemdata['price']['gross']);
	}
	
	$itemdata['price']['tax'] = isset($itemdata['price']['taxid']) ? $taxeslist[$itemdata['price']['taxid']]['label'] : '';

	if (!$error) {
		$taxvalue = isset($itemdata['price']['taxid']) ? $taxeslist[$itemdata['price']['taxid']]['value'] : 0;
		if ((float)$itemdata['price']['net'] > 0) {
			//$itemdata['price']['net'] = f_round($itemdata['price']['net']);
			$itemdata['price']['gross'] = f_round($itemdata['price']['net'] * ($taxvalue / 100 + 1),2);
			$itemdata['price']['net'] = f_round($itemdata['price']['gross'] / ($taxvalue / 100 + 1),2);
		} elseif ((float)$itemdata['price']['gross'] > 0) {
			//$itemdata['price']['gross'] = f_round($itemdata['price']['gross'], 2);
			$itemdata['price']['net'] = f_round($itemdata['price']['gross'] / ($taxvalue / 100 + 1),2);
		}
		 
		 if ($itemdata['count'] > 1) {
		 	$serials = array();
			$receivenote['doc']['net'] += $itemdata['count']*$itemdata['price']['net'];
			$receivenote['doc']['gross'] +=  $itemdata['count']*$itemdata['price']['gross'];
			for ($i = 1; $i < $itemdata['count']; ++ $i) {
				$serials[] = strtoupper($itemdata['serial'][$i]);
				unset($itemdata['serial'][$i]);
			}
			$itemdata['count'] = 1;
			$itemdata['serial'] = strtoupper($itemdata['serial'][0]);
			$receivenote['product'][] = $itemdata;
			foreach($serials as $serial) {
				$itemdata['serial'] = $serial;
				$receivenote['product'][] = $itemdata;
			}
		} elseif ($itemdata['count'] = 1) {
			$itemdata['serial'] = strtoupper($itemdata['serial'][0]);
			$receivenote['product'][] = $itemdata;
			$receivenote['doc']['net'] += $itemdata['price']['net'];
			$receivenote['doc']['gross'] += $itemdata['price']['gross'];
		} else {
			$receivenote['product'][] = array();
			$receivenote['doc']['net'] = $receivenote['doc']['gross'] = 0;
		}

		unset($itemdata);
		
		$SESSION->remove('receivenote');
		$SESSION->save('receivenote', $receivenote);
		//print_r($receivenote['product']);
	} else {
		$SMARTY->assign('item', $itemdata);
	}
} elseif (isset($_FILES['receivenote'])) {
	if (!$files =& $_FILES['receivenote'])
		$error['file'] = trans('Unknown error');

	$product_tmp = $_POST['receivenote']['product_tmp'];

	if (!ctype_digit($product_tmp['warehouse']))
		$error['warehouse_file'] = 'Incorrect warehouse!';

	if (!$error)
		$whname = $LMSST->WarehouseGetNameById($product_tmp['warehouse']);

	if (($file = fopen($files['tmp_name']['file'], 'r')) !== FALSE) {
		$receivenote['product_tmp'] = NULL;
		$reveivenote['doc_tmp'] = NULL;

		$product_tmp['tax'] = isset($product_tmp['taxid']) ? $taxeslist[$product_tmp['taxid']]['label'] : '';
		$taxvalue = isset($product_tmp['taxid']) ? $taxeslist[$product_tmp['taxid']]['value'] : 0;

		while(($product = fgetcsv($file, 2048, ',', ';')) !== FALSE) {
			$receivenote['product_tmp'][] = $product;
		}

		array_shift($receivenote['product_tmp']);
		$keys = array_shift($receivenote['product_tmp']);

		foreach($receivenote['product_tmp'] as $k => $v) {
			foreach ($v as $k2 => $v2) {
				$receivenote['product_tmp'][$k][$keys[$k2]] = $v2;
				unset($receivenote['product_tmp'][$k][$k2]);
			}
			$receivenote['product_tmp'][$k]['serial'] = strtoupper($receivenote['product_tmp'][$k]['serial']);
			$receivenote['product_tmp'][$k]['warehouse'] = $product_tmp['warehouse'];
			$receivenote['product_tmp'][$k]['warehousename'] = $whname;
			$receivenote['product_tmp'][$k]['model'] = trim($receivenote['product_tmp'][$k]['model']);
                        $receivenote['product_tmp'][$k]['config'] = trim($receivenote['product_tmp'][$k]['config']);
                        $receivenote['product_tmp'][$k]['manufacturer'] = trim($receivenote['product_tmp'][$k]['manufacturer']);
                        $receivenote['product_tmp'][$k]['group'] = trim($receivenote['product_tmp'][$k]['group']);
			$receivenote['product_tmp'][$k]['name'] = $receivenote['product_tmp'][$k]['model'].' '.$receivenote['product_tmp'][$k]['config'];
			$receivenote['product_tmp'][$k]['product'] = $receivenote['product_tmp'][$k]['manufacturer'].' '.$receivenote['product_tmp'][$k]['name'];
			$price = $receivenote['product_tmp'][$k]['price'];
			unset($receivenote['product_tmp'][$k]['price']);
			$receivenote['product_tmp'][$k]['price']['net'] = f_round($price);
			$receivenote['product_tmp'][$k]['price']['gross'] = f_round($receivenote['product_tmp'][$k]['price']['net'] * ($taxvalue / 100 + 1),2);
			$receivenote['product_tmp'][$k]['price']['net'] = f_round($receivenote['product_tmp'][$k]['price']['gross'] / ($taxvalue / 100 + 1),2);
			$receivenote['product_tmp'][$k]['price']['tax'] = $product_tmp['tax'];
			$receivenote['product_tmp'][$k]['price']['taxid'] = $product_tmp['taxid'];
			$receivenote['product_tmp'][$k]['taxid'] = $product_tmp['taxid'];
			$receivenote['product_tmp'][$k]['typeid'] = '1';
			$receivenote['product_tmp'][$k]['quantityid'] = '1';
			$receivenote['product_tmp'][$k]['quantitycheck'] = '1';
			$receivenote['product_tmp'][$k]['quantity'] = '1';
			$receivenote['product_tmp'][$k]['count'] = '1';
			$receivenote['product_tmp'][$k]['unit'] = '1';
			$receivenote['product_tmp'][$k]['unitname'] = 'szt.';

			$receivenote['doc_tmp']['net'] = $receivenote['doc_tmp']['net'] + $receivenote['product_tmp'][$k]['price']['net'];
			$receivenote['doc_tmp']['gross'] = $receivenote['doc_tmp']['gross'] + $receivenote['product_tmp'][$k]['price']['gross'];
			
			if ($q = $LMSST->ManufacturerGetIdByName($receivenote['product_tmp'][$k]['manufacturer'])) {
				$receivenote['product_tmp'][$k]['manufacturerid'] = $q['id'];
			} elseif ($product_tmp['create_m']) {
				if(!$receivenote['product_tmp'][$k]['manufacturerid'] = $LMSST->ManufacturerAdd(array('name' => $receivenote['product_tmp'][$k]['manufacturer'], 'comment' => trans('Added autmatically during CSV import')))) {
					$error['file'] = trans('Unable to add manufacturer: $a', $receivenote['product_tmp'][$k]['manufacturer']);
					$receivenote['product_tmp'] = NULL;
					break;
				}
			} else {
				$error['file'] = trans('Unknown manufacturer: $a', $receivenote['product_tmp'][$k]['manufacturer']);
				$receivenote['product_tmp'] = NULL;
				break;
			}

			if (!$q = $LMSST->GroupGetIdByName($receivenote['product_tmp'][$k]['group'])) {
				$error['file'] = trans('Unknown group: $a', $receivenote['product_tmp'][$k]['group']);
				$receivenote['product_tmp'] = NULL;
				break;
			} else {
				$receivenote['product_tmp'][$k]['groupid'] = $q['id'];
			}

			if ($q = $LMSST->ProductGetIdByName($receivenote['product_tmp'][$k]['name'], $receivenote['product_tmp'][$k]['manufacturerid'])) {
				$receivenote['product_tmp'][$k]['pid'] = $q['id'];
				$receivenote['product_tmp'][$k]['newp'] = false;
			} elseif ($product_tmp['create_p']) {
				if (!$receivenote['product_tmp'][$k]['pid'] = $LMSST->ProductAdd($receivenote['product_tmp'][$k])) {
					$error['file'] = trans('Unable to add product: $a', $receivenote['product_tmp'][$k]['name']);
					$receivenote['product_tmp'] = NULL;
					break;
				} else {
					$receivenote['product_tmp'][$k]['newp'] = true;
				}
			} else {
				$error['file'] = trans('Unknown product: $a', $receivenote['product_tmp'][$k]['name']);
				$receivenote['product_tmp'] = NULL;
				break;
			}
		}
	
//		print_r($receivenote['product_tmp']);
		$SESSION->remove('receivenote');
		$SESSION->save('receivenote', $receivenote);
	}
} elseif (isset($_GET['actiontmp']) && $_GET['actiontmp'] == 'save') {
	foreach($receivenote['product_tmp'] as $k => $v) {
		unset(
			$receivenote['product_tmp'][$k]['group'],
			$receivenote['product_tmp'][$k]['manufacturer'],
			$receivenote['product_tmp'][$k]['model'],
			$receivenote['product_tmp'][$k]['config'],
			$receivenote['product_tmp'][$k]['name'],
			$receivenote['product_tmp'][$k]['taxid'],
			$receivenote['product_tmp'][$k]['typeid'],
			$receivenote['product_tmp'][$k]['quantityid'],
			$receivenote['product_tmp'][$k]['quantitycheck'],
			$receivenote['product_tmp'][$k]['quantity'],
			$receivenote['product_tmp'][$k]['manufacturerid'],
			$receivenote['product_tmp'][$k]['groupid']
		);
		$receivenote['product'][] = $receivenote['product_tmp'][$k];
	}
	if ($receivenote['doc']['net'])
		$receivenote['doc']['net'] = $receivenote['doc']['net'] + $receivenote['doc_tmp']['net'];
	else
		$receivenote['doc']['net'] = $receivenote['doc_tmp']['net'];

	if ($receivenote['doc']['gross'])
		$receivenote['doc']['gross'] = $receivenote['doc']['gross'] + $receivenote['doc_tmp']['gross'];
	else
		$receivenote['doc']['gross'] = $receivenote['doc_tmp']['gross'];

	unset($receivenote['product_tmp'], $receivenote['doc_tmp']);
	$SESSION->remove('receivenote');
	$SESSION->save('receivenote', $receivenote);

} elseif (isset($_GET['actiontmp']) && $_GET['actiontmp'] == 'cancel') {
	unset($receivenote['product_tmp'], $receivenote['doc_tmp']);
	$SESSION->remove('receivenote');
	$SESSION->save('receivenote', $receivenote);
} elseif (isset($_GET['action']) && isset($_GET['id']) && ctype_digit($_GET['id'])) {
	switch($_GET['action']) {
		case 'del':
			$receivenote['doc']['net'] -= $receivenote['product'][$_GET['id']]['price']['net'];
			$receivenote['doc']['gross'] -= $receivenote['product'][$_GET['id']]['price']['gross'];
			unset($receivenote['product'][$_GET['id']]);
			$SESSION->remove('receivenote');
			$SESSION->save('receivenote', $receivenote);
			break;
		case 'edit':
			$itemdata = $receivenote['product'][$_GET['id']];
			$receivenote['doc']['net'] -= $receivenote['product'][$_GET['id']]['price']['net'];
			$receivenote['doc']['gross'] -= $receivenote['product'][$_GET['id']]['price']['gross'];
			unset($receivenote['product'][$_GET['id']]);
			$SESSION->remove('receivenote');
			$SESSION->save('receivenote', $receivenote);
			break;
		default:
			break;
	}
} elseif (isset($_GET['action'])) {
	switch($_GET['action']) {
		case 'cancel':
			$SESSION->remove('receivenote');
			$SESSION->redirect('?m=stckstock');
			break;
		case 'save':
			//print_r($receivenote);
			if ($LMSST->ReceiveNoteExistsByInfo($receivenote['doc'], array('number', 'supplierid')))
                		$error['comment'] = trans('Document already exists!');
			if (!$error) {
				$LMSST->ReceiveNoteAdd($receivenote);
				$SESSION->remove('receivenote');
				$SESSION->redirect('?m=stckstock');
			}
			break;
	}
}
$warehouses = $LMSST->WarehouseGetList('name');
unset($warehouses['total']);
unset($warehouses['order']);
unset($warehouses['direction']);

$SMARTY->assign('error', $error);
$SMARTY->assign('receivenote', $receivenote);
$SMARTY->assign('warehouses', $warehouses);
$SMARTY->assign('taxeslist', $taxeslist);
$SMARTY->assign('quantities', $quantities);
if (isset($itemdata) && is_array($itemdata))
	$SMARTY->assign('itemdata', $itemdata);
$SMARTY->assign('gtucodes', $LMSST->GTUCodeList(array('active' => 1,'deleted' => 0)));
$SMARTY->display('stck/stckreceiveproductlist.html');
?>
