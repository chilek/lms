<?php

if (!isset($_GET['id']) || !ctype_digit($_GET['id']))
	$SESSION->redirect('?m=stckmanufacturerlist');
elseif (! $LMSST->ManufacturerExists($_GET['id']))
	$SESSION->redirect('?m=stckmanufacturerlist');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Edit manufacturer');
$error = NULL;

if (isset($_POST['manufactureredit'])) {
	$manufactureredit = $_POST['manufactureredit'];
	$manufactureredit['id'] = $_GET['id'];

	if ($manufactureredit['name'] == '')
		$error['name'] = trans('Manufacturer must have a name!');
	
	if (!$error) {
		$id = $LMSST->ManufacturerEdit($manufactureredit);

		$SESSION->redirect('?m=stckmanufacturerinfo&id='.$id);
	}
} else {
	$manufactureredit = $LMSST->ManufacturerGetInfoById($_GET['id']);
}

$SMARTY->assign('error', $error);
$SMARTY->assign('manufactureredit', $manufactureredit);
$SMARTY->display('stck/stckmanufactureredit.html');

?>
