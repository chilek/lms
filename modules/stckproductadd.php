<?php
$layout['pagetitle'] = trans('Add product');
$error = NULL;

$productadd = array();
$productadd['quantitycheck'] = 1;

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if (isset($_POST['productadd'])) {
	$productadd = $_POST['productadd'];
	
	if ($productadd['name'] == '')
		$error['name'] = trans('Product must have a name!');
	
	if ($productadd['gprice'] && !preg_match('/^\d+[,.]{0,1}\d{0,2}$/i', $productadd['gprice']))
		$error['gprice'] = trans('Wrong or missing price!');
	
	if ($productadd['srp'] && !preg_match('/^\d+[,.]{0,1}\d{0,2}$/i', $productadd['srp']))
		$error['srp'] = trans('Wrong or missing price!');

	if (!ctype_digit($productadd['gtu_code']))
                unset($productadd['gtu_code']);

	if ($LMSST->ProductGetInfoByEAN($productadd['ean']))
		$error['ean'] = trans('EAN already exists!');
	
	if (!$error) {
		if ($id = $LMSST->ProductAdd($productadd)) {
			$SMARTY->assign('success', 1);
			if(!isset($productadd['reuse']) && !$layout['popup']) {
				$SESSION->redirect('?m=stckproductinfo&id='.$id);
			} 
		}
	}
}

$manufacturers =  $LMSST->ManufacturerGetList();
$groups = $LMSST->GroupGetList();
$quantities = $LMSST->QuantityGetList();
$types = $LMSST->TypeGetList();

unset($manufacturers['order']);
unset($manufacturers['direction']);
unset($manufacturers['total']);
unset($groups['order']);
unset($groups['direction']);
unset($groups['total']);
unset($quantities['order']);
unset($quantities['direction']);
unset($quantities['total']);
unset($types['order']);
unset($types['direction']);
unset($types['total']);

$SMARTY->assign('error', $error);
$SMARTY->assign('manufacturerslist', $manufacturers);
$SMARTY->assign('groupslist', $groups);
$SMARTY->assign('quantitieslist', $quantities);
$SMARTY->assign('typeslist', $types);
$SMARTY->assign('taxeslist',$LMS->GetTaxes());
$SMARTY->assign('productadd', $productadd);
$SMARTY->assign('gtucodes', $LMSST->GTUCodeList(array('active' => 1,'deleted' => 0)));
$SMARTY->display('stck/stckproductadd.html');
?>
