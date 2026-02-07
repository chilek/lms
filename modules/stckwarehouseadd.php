<?php
$layout['pagetitle'] = trans('Add warehouse');
$error = NULL;

$warehouseadd = array();

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if (isset($_POST['warehouseadd'])) {
	$warehouseadd = $_POST['warehouseadd'];

	if ($warehouseadd['name'] == '')
		$error['name'] = trans('Warehouse must have a name!');
	
	if (!$error) {
		if ($id = $LMSST->WarehouseAdd($warehouseadd)) {
			if(!isset($warehouseadd['reuse'])) {
				$SESSION->redirect('?m=stckwarehouseinfo&id='.$id);
			}
		} else {
			$error['name'] = trans('Warehouse with this name already exists!');
		}
	}
}

$SMARTY->assign('error', $error);
$SMARTY->assign('warehouseadd', $warehouseadd);
$SMARTY->display('stck/stckwarehouseadd.html');

?>
