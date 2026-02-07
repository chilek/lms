<?php
$action = isset($_GET['action']) ? $_GET['action'] : '';
$exists = $LMSST->WarehouseExists($_GET['id']);

if (!isset($_GET['id']) || !ctype_digit($_GET['id']))
	$SESSION->redirect('?m=stckwarehouselist');
elseif ($exists < 0 && $action != 'recover')
	$SESSION->redirect('?m=stckwarehouselist');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Edit warehouse');
$error = NULL;

if (isset($_POST['warehouseedit'])) {
	$warehouseedit = $_POST['warehouseedit'];
	$warehouseedit['id'] = $_GET['id'];
	
	if ($warehouseedit['name'] == '')
		$error['name'] = trans('Warehouse must have a name!');
	
	if (!$error) {
		$id = $LMSST->WarehouseEdit($warehouseedit);
		$SESSION->redirect('?m=stckwarehouseinfo&id='.$id);
	}
} else {
	$warehouseedit = $LMSST->WarehouseGetInfoById($_GET['id']);
}

$SMARTY->assign('error', $error);
$SMARTY->assign('warehouseedit', $warehouseedit);
$SMARTY->assign('recover',($action == 'recover' ? 1 : 0));
$SMARTY->display('stck/stckwarehouseedit.html');

?>
