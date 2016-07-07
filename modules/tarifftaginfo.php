<?php

$id = !empty($_GET['id']) ? $_GET['id'] : NULL;

if (!$id || !$LMS->TarifftagExists($id)) {
    $SESSION->redirect('?m=tarifftaglist');
}

$tarifftag = $LMS->TarifftagGet($id);

$tariffs = $LMS->GetTariffWithoutTagNames($id);

$tariffscount = sizeof($tariffs);

$layout['pagetitle'] = trans('Tag Info: $a', $tarifftag['name']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('tarifftag', $tarifftag);
$SMARTY->assign('tariffs', $tariffs);
$SMARTY->assign('tariffscount', $tariffscount);
$SMARTY->display('tariff/tarifftaginfo.html');
