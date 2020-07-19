<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

if (!empty($_POST['division'])) {
    $division = $_POST['division'];

    foreach ($division as $key => $value) {
        $division[$key] = trim($value);
    }

    if ($division['name']=='' && $division['description']=='' && $division['shortname']=='') {
        $SESSION->redirect('?m=divisionlist');
    }

    if ($division['name'] == '') {
        $error['name'] = trans('Division long name is required!');
    }

    if ($division['shortname'] == '') {
        $error['shortname'] = trans('Division short name is required!');
    } elseif ($DB->GetOne('SELECT 1 FROM divisions WHERE shortname = ?', array($division['shortname']))) {
        $error['shortname'] = trans('Division with specified name already exists!');
    }

    if ($division['location_city_name'] == '') {
        $error['division[location_city_name]'] = trans('City is required!');
    }

    if ($division['location_zip'] == '') {
        $error['division[location_zip]'] = trans('Zip code is required!');
    } else if (!Localisation::checkZip($division['location_zip'], $division['location_country_id'])) {
        $error['division[location_zip]'] = trans('Incorrect ZIP code!');
    }

    if ($division['ten'] != '' && !check_ten($division['ten']) && !isset($division['tenwarning'])) {
        $error['ten'] = trans('Incorrect Tax Exempt Number! If you are sure you want to accept it, then click "Submit" again.');
        $division['tenwarning'] = 1;
    }

    if ($division['regon'] != '' && !check_regon($division['regon'])) {
        $error['regon'] = trans('Incorrect Business Registration Number!');
    }

    if ($division['account'] != '' && (strlen($division['account'])>48 || !preg_match('/^([A-Z][A-Z])?[0-9]+$/', $division['account']))) {
        $error['account'] = trans('Wrong account number!');
    }

    if ($division['email'] != '' && !check_email($division['email'])) {
        $error['email'] = trans('E-mail isn\'t correct!');
    }

    if ($division['inv_paytime'] == '') {
        $division['inv_paytime'] = null;
    }

    if (!preg_match('/^[0-9]*$/', $division['tax_office_code'])) {
        $error['tax_office_code'] = trans('Invalid format of Tax Office Code!');
    }

    if (!ConfigHelper::checkPrivilege('full_access') && ConfigHelper::checkConfig('phpui.teryt_required')
        && !empty($division['location_city_name']) && ($division['location_country_id'] == 2 || empty($division['location_country_id']))
        && (!isset($division['teryt']) || empty($division['location_city']))) {
        $error['division[teryt]'] = trans('TERRIT address is required!');
    }

    if (!$error) {
        $LMS->AddDivision($division);

        if (!isset($division['reuse'])) {
            $SESSION->redirect('?m=divisionlist');
        }
    }
}

$default_zip     = ConfigHelper::getConfig('phpui.default_zip');
$default_city    = ConfigHelper::getConfig('phpui.default_city');
$default_address = ConfigHelper::getConfig('phpui.default_address');

if (!isset($division['location_zip']) && $default_zip) {
    $division['location_zip'] = $default_zip;
}

if (!isset($division['location_city']) && $default_city) {
    $division['location_city'] = $default_city;
}

$layout['pagetitle'] = trans('New Division');

if (Localisation::getCurrentSystemLanguage() == 'pl_PL') {
    require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'tax_office_codes.php');
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('division', $division);
$SMARTY->assign('countries', $LMS->GetCountries());
$SMARTY->assign('error', $error);
$SMARTY->display('division/divisionadd.html');
