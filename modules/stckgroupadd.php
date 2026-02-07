<?php
$layout['pagetitle'] = trans('Add product group');
$error = NULL;

$groupadd = array();

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if (isset($_POST['groupadd'])) {
	$groupadd = $_POST['groupadd'];
	
	if ($groupadd['name'] == '')
		$error['name'] = trans('Product group must have a name!');
	
	if(!isset($groupadd['taxid']))
		$groupadd['taxid'] = 0;
	
	if (!$error) {
		if ($id = $LMSST->GroupAdd($groupadd)) {
			if(!isset($groupadd['reuse']) && !$layout['popup']) {
				$SESSION->redirect('?m=stckgroupinfo&id='.$id);
			} else {
				$SMARTY->assign('success', 1);
				$SMARTY->assign('reload', 1);

			}
		} else {
			$error['name'] = trans('Group with this name already exists!');
		}

	}
}
$groupadd['quantitycheck'] = 1;
$quantities = $LMSST->QuantityGetList();

unset($quantities['order']);
unset($quantities['direction']);
unset($quantities['total']);

$SMARTY->assign('error', $error);
$SMARTY->assign('quantitieslist', $quantities);
$SMARTY->assign('taxeslist',$LMS->GetTaxes());
$SMARTY->assign('groupadd', $groupadd);
$SMARTY->display('stck/stckgroupadd.html');
?>
