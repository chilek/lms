<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

if (!$LMS->TarifftagExists($_GET['id'])) {
    $SESSION->redirect('?m=tarifftaglist');
}
if (isset($_POST['userassignments'])) {
    $oper = $_POST['oper'];
    $userassignments = $_POST['userassignments'];
}
$tarifftag = $LMS->TarifftagGet($_GET['id']);
$tariffs = $LMS->GetTariffWithoutTagNames($_GET['id']);
$tariffscount = count($tariffs);

$layout['pagetitle'] = trans('Edit tag').': '.$tarifftag['name'];

if (isset($_POST['tarifftag'])) {
    $tarifftagedit = $_POST['tarifftag'];
    foreach ($tarifftagedit as $key => $value) {
        $tarifftagedit[$key] = trim($value);
    }

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
