<?php
$action = isset($_GET['action']) ? $_GET['action'] : '';
$exists = $LMSST->GroupExists($_GET['id']);

if (!isset($_GET['id']) || !ctype_digit($_GET['id']))
	$SESSION->redirect('?m=stckgrouplist');
elseif ($exists < 0 && $action != 'recover')
	$SESSION->redirect('?m=stckgrouplist');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Edit product group');
$error = NULL;

if (isset($_POST['groupedit'])) {
	$groupedit = $_POST['groupedit'];
	$groupedit['id'] = $_GET['id'];

	if ($groupedit['name'] == '')
		$error['name'] = trans('Product grup must have a name!');
	
	if(!isset($groupadd['taxid']))
	                $groupadd['taxid'] = 0;

	if (!$error) {
		$id = $LMSST->GroupEdit($groupedit);

		$SESSION->redirect('?m=stckgroupinfo&id='.$id);
	}
} else {
	$groupedit = $LMSST->GroupGetInfoById($_GET['id']);
}

$quantities = $LMSST->QuantityGetList();

unset($quantities['order']);
unset($quantities['direction']);
unset($quantities['total']);

$SMARTY->assign('error', $error);
$SMARTY->assign('quantitieslist', $quantities);
$SMARTY->assign('groupedit', $groupedit);
$SMARTY->assign('recover',($action == 'recover' ? 1 : 0));
$SMARTY->display('stck/stckgroupedit.html');

?>
