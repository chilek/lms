<?php

$layout['pagetitle'] = trans('Tags list');

$tarifftaglist = $LMS->TarifftagGetList();

$listdata['total'] = $tarifftaglist['total'];
$listdata['totalcount'] = $tarifftaglist['totalcount'];

unset($tarifftaglist['total']);
unset($tarifftaglist['totalcount']);

$SMARTY->assign('tarifftaglist', $tarifftaglist);
$SMARTY->assign('listdata', $listdata);
$SMARTY->display('tariff/tarifftaglist.html');

?>
