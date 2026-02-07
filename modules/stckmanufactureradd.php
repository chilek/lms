<?php
$layout['pagetitle'] = trans('Add manufacturer');
$error = NULL;

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$manufactureradd = array();

if (isset($_POST['manufactureradd'])) {
	$manufactureradd = $_POST['manufactureradd'];

	if ($manufactureradd['name'] == '')
		$error['name'] = trans('Manufacturer must have a name!');
	
	if (!$error) {
		if ($id = $LMSST->ManufacturerAdd($manufactureradd)) {
			if(!isset($manufactureradd['reuse']) && !$layout['popup']) {
				$SESSION->redirect('?m=stckmanufacturerinfo&id='.$id);
			} elseif ($layout['popup']) {
				$SMARTY->assign('success', 1);
				$SMARTY->assign('reload', 1);
			}
		} else {
			$error['name'] = trans('Manufacturer already exists in database!');
		}

	}
}

$SMARTY->assign('error', $error);
$SMARTY->assign('manufactureradd', $manufactureradd);
$SMARTY->display('stck/stckmanufactureradd.html');
?>
