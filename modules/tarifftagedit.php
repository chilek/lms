<?php

if (!$LMS->TarifftagExists($_GET['id'])) {
    $SESSION->redirect('?m=tarifftaglist');
}
if (isset($_POST['userassignments'])) {
    $oper = $_POST['oper'];
    $userassignments = $_POST['userassignments'];
}
$tarifftag = $LMS->TarifftagGet($_GET['id']);
$tariffs = $LMS->GetTariffWithoutTagNames($_GET['id']);
$tariffscount = sizeof($tariffs);

$layout['pagetitle'] = trans('Tag Edit: $a', $tarifftag['name']);

if (isset($_POST['tarifftag'])) {
    $tarifftagedit = $_POST['tarifftag'];
    foreach ($tarifftagedit as $key => $value)
        $tarifftagedit[$key] = trim($value);

    $tarifftagedit['id'] = $_GET['id'];

    if ($tarifftagedit['name'] == '') {
        $error['name'] = trans('Tag name required!');
    } elseif (strlen($tarifftagedit['name']) > 255) {
        $error['name'] = trans('Tag name is too long!');
    } elseif (!preg_match('/^[._a-z0-9-]+$/i', $tarifftagedit['name'])) {
        $error['name'] = trans('Invalid chars in tag name!');
    } elseif (($id = $LMS->TarifftagGetId($tarifftagedit['name'])) && $id != $tarifftagedit['id']) {
        $error['name'] = trans('Tag with name $a already exists!', $tarifftagedit['name']);
    }

    if (!$error) {
        $LMS->TarifftagUpdate($tarifftagedit);
        $SESSION->redirect('?m=tarifftaginfo&id=' . $tarifftag['id']);
    }

    $tarifftag['description'] = $tarifftagedit['description'];
    $tarifftag['name'] = $tarifftagedit['name'];
}


$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('tarifftag', $tarifftag);
$SMARTY->assign('error', $error);
$SMARTY->assign('tariffs', $tariffs);
$SMARTY->assign('tariffscount', $tariffscount);
$SMARTY->assign('tarifftags', $LMS->TarifftagGetAll());
$SMARTY->display('tariff/tarifftagedit.html');
