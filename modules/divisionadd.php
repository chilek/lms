<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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
        if (!is_array($value)) {
            $division[$key] = trim($value);
        }
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

    if (!empty($division['naturalperson'])) {
        if (empty($division['firstname'])) {
            $error['firstname'] = trans('First name cannot be empty for natural person!');
        }
        if (empty($division['lastname'])) {
            $error['lastname'] = trans('Last name cannot be empty for natural person!');
        }
        if (empty($division['birthdate'])) {
            $error['birthdate'] = trans('Birth date cannot be empty for natural person!');
        }
    } else {
        $division['firstname'] = $division['lastname'] = $division['birthdate'] = null;
    }

    if ($division['location_city_name'] == '') {
        $error['division[location_city_name]'] = trans('City is required!');
    }

    if (!empty($division['location_country_id'])) {
        Localisation::setSystemLanguage($LMS->getCountryCodeById($division['location_country_id']));
    }
    if ($division['location_zip'] == '') {
        $error['division[location_zip]'] = trans('Zip code is required!');
    } else if (!check_zip($division['location_zip'])) {
        $error['division[location_zip]'] = trans('Incorrect ZIP code!');
    }
    if (!empty($division['location_country_id'])) {
        Localisation::resetSystemLanguage();
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

    if ($division['phone'] != '' && !preg_match('/^\+?[0-9\s\-]+$/', $division['phone'])) {
        $error['phone'] = trans('Incorrect phone number!');
    }

    if ($division['servicephone'] != '' && !preg_match('/^\+?[0-9\s\-]+$/', $division['servicephone'])) {
        $error['servicephone'] = trans('Incorrect phone number!');
    }

    if (strlen($division['url']) && !filter_var($division['url'], FILTER_VALIDATE_URL)) {
        $error['url'] = trans('Invalid URL address format!');
    }

    if (strlen($division['userpanel_url']) && !filter_var($division['userpanel_url'], FILTER_VALIDATE_URL)) {
        $error['userpanel_url'] = trans('Invalid URL address format!');
    }

    if ($division['inv_paytime'] == '') {
        $division['inv_paytime'] = null;
    }

    if (!preg_match('/^[0-9]*$/', $division['tax_office_code'])) {
        $error['tax_office_code'] = trans('Invalid format of Tax Office Code!');
    }

    if (!preg_match('/^([0-9a-fA-F]{64})?$/', $division['kseftoken'])) {
        $error['kseftoken'] = trans('Invalid format of KSeF token!');
    }

    if (!ConfigHelper::checkPrivilege('full_access') && ConfigHelper::checkConfig('phpui.teryt_required')
        && !empty($division['location_city_name']) && ($division['location_country_id'] == 2 || empty($division['location_country_id']))
        && (!isset($division['teryt']) || empty($division['location_city'])) && $LMS->isTerritState($division['location_state_name'])) {
        $error['division[teryt]'] = trans('TERYT address is required!');
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

$division['office_address']['prefix'] = 'division[office_address]';

$layout['pagetitle'] = trans('New Division');

if (Localisation::getCurrentSystemLanguage() == 'pl_PL') {
    require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'tax_office_codes.php');
}

$usersList = $LMS->GetUserList(array('superuser' => 1));
unset($usersList['total']);

$SESSION->add_history_entry();

$SMARTY->assign('division', $division);
$SMARTY->assign('userslist', $usersList);
$SMARTY->assign('error', $error);
$SMARTY->display('division/divisionadd.html');
