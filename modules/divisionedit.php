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

$id = intval($_GET['id']);

if (!empty($_GET['changestatus'])) {
    if ($SYSLOG) {
        $div = $DB->GetRow('SELECT countryid, status FROM vdivisions WHERE id = ?', array($id));
        $args = array(
            SYSLOG::RES_DIV => $id,
            SYSLOG::RES_COUNTRY => $div['countryid'],
            'status' => intval($div['status']) ? 0 : 1
        );
        $SYSLOG->AddMessage(SYSLOG::RES_DIV, SYSLOG::OPER_UPDATE, $args);
    }
    $DB->Execute('UPDATE divisions SET status = (CASE WHEN status > 0 THEN 0 ELSE 1 END)
		WHERE id = ?', array($id));
    $SESSION->redirect('?m=divisionlist');
}

$olddiv = $DB->GetRow(
    'SELECT d.*,
        addr.name AS location_name,
        addr.city AS location_city_name,
        addr.street AS location_street_name,
        addr.city_id AS location_city,
        addr.street_id AS location_street,
        addr.house AS location_house,
        addr.flat AS location_flat,
        addr.zip AS location_zip,
        addr.state AS location_state_name,
        addr.state_id AS location_state,
        addr.country_id AS location_country_id,
        addr.postoffice AS location_postoffice,
        o_addr.location AS location_office,
        o_addr.name AS location_office_name,
        o_addr.city AS location_office_city_name,
        o_addr.street AS location_office_street_name,
        o_addr.city_id AS location_office_city,
        o_addr.street_id AS location_office_street,
        o_addr.house AS location_office_house,
        o_addr.flat AS location_office_flat,
        o_addr.zip AS location_office_zip,
        o_addr.state AS location_office_state_name,
        o_addr.state_id AS location_office_state,
        o_addr.country_id AS location_office_country_id,
        o_addr.postoffice AS location_office_postoffice,
        ' . $DB->Concat('simc.woj', 'simc.pow', 'simc.gmi', 'simc.rodz_gmi') . ' AS office_terc,
        simc.sym AS office_simc,
        ulic.sym_ul AS office_ulic
    FROM vdivisions d
    LEFT JOIN addresses addr           ON addr.id = d.address_id
    LEFT JOIN location_cities lc       ON lc.id = addr.city_id
    LEFT JOIN location_streets ls      ON ls.id = addr.street_id
    LEFT JOIN location_street_types lt ON lt.id = ls.typeid
    LEFT JOIN vaddresses o_addr        ON o_addr.id = d.office_address_id
    LEFT JOIN location_cities o_lc     ON o_lc.id = o_addr.city_id
    LEFT JOIN location_streets o_ls    ON o_ls.id = o_addr.street_id
    LEFT JOIN location_street_types o_lt ON o_lt.id = o_ls.typeid
    LEFT JOIN teryt_simc simc          ON simc.cityid = o_addr.city_id
    LEFT JOIN teryt_ulic ulic          ON ulic.id = o_addr.street_id
    WHERE d.id = ?',
    array($_GET['id'])
);

$divisionUsers = $LMS->GetUserList(array('divisions' => $id));
unset($divisionUsers['total']);
if ($divisionUsers) {
    $divisionUsers = array_keys($divisionUsers);
}

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

    $division['id'] = $olddiv['id'];

    if ($division['name'] == '') {
        $error['name'] = trans('Division long name is required!');
    }

    if ($division['shortname'] == '') {
        $error['shortname'] = trans('Division short name is required!');
    } else if ($olddiv['shortname'] != $division['shortname']
        && $DB->GetOne('SELECT 1 FROM divisions WHERE shortname = ?', array($division['shortname']))) {
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

    if (!ConfigHelper::checkPrivilege('full_access') && ConfigHelper::checkConfig('phpui.teryt_required')
        && !empty($division['location_city_name']) && ($division['location_country_id'] == 2 || empty($division['location_country_id']))
        && (!isset($division['teryt']) || empty($division['location_city'])) && $LMS->isTerritState($division['location_state_name'])) {
        $error['division[teryt]'] = trans('TERYT address is required!');
    }

    $division['office_address']['address_id'] = $olddiv['office_address_id'];

    if (!$error) {
        $diffUsersAdd = array();
        $diffUsersDel = array();
        // check if division users list has changed
        if ($divisionUsers) {
            foreach ($divisionUsers as $divisionUser) {
                if (in_array(intval($divisionUser), $division['users'])) {
                    continue;
                } else {
                    $diffUsersDel[] = intval($divisionUser);
                }
            }
        }
        unset($divisionUser);

        foreach ($division['users'] as $divisionUser) {
            if (in_array(intval($divisionUser), $divisionUsers)) {
                continue;
            } else {
                $diffUsersAdd[] = intval($divisionUser);
            }
        }
        unset($divisionUser);
        $division['diff_users_del'] = $diffUsersDel;
        $division['diff_users_add'] = $diffUsersAdd;

        $LMS->UpdateDivision($division);

        $SESSION->redirect('?m=divisionlist');
    }

    $division['office_address']['prefix'] = 'division[office_address]';
} else {
    if ($olddiv['location_city'] || $olddiv['location_street']) {
        $olddiv['teryt'] = true;
        if ($olddiv['location_city'] && $olddiv['location_street']) {
            preg_match(
                '/^(?<city>.+)\s*,\s*(?<address>.+)$/',
                location_str(array('city_name' => $olddiv['location_city'], 'street_name' => $olddiv['location_street'])),
                $m
            );
            $olddiv['city'] = $m['city'];
            $oldciv['address'] = $m['address'];
        }
    }

    $olddiv['office_address'] = array(
        'prefix' => 'division[office_address]',
        'address_id' => $olddiv['office_address_id'],
        'location' => $olddiv['location_office'],
        'location_name' => $olddiv['location_office_name'],
        'location_state_name' => $olddiv['location_office_state_name'],
        'location_state' => $olddiv['location_office_state'],
        'location_city_name' => $olddiv['location_office_city_name'],
        'location_city' => $olddiv['location_office_city'],
        'location_street_name' => $olddiv['location_office_street_name'],
        'location_street' => $olddiv['location_office_street'],
        'location_house' => $olddiv['location_office_house'],
        'location_flat' => $olddiv['location_office_flat'],
        'location_zip' => $olddiv['location_office_zip'],
        'location_country_id' => $olddiv['location_office_country_id'],
        'location_postoffice' => $olddiv['location_office_postoffice'],
        'teryt' => false,
        'terc' => $olddiv['office_terc'],
        'simc' => $olddiv['office_simc'],
        'ulic' => $olddiv['office_ulic'],
    );

    if ($olddiv['location_office_city'] || $olddiv['location_office_street']) {
        $olddiv['office_address']['teryt'] = true;
        if ($olddiv['location_office_city'] && $olddiv['location_office_street']) {
            preg_match(
                '/^(?<city>.+)\s*,\s*(?<address>.+)$/',
                location_str(array('city_name' => $olddiv['location_office_city'], 'street_name' => $olddiv['location_office_street'])),
                $m
            );
            $olddiv['office_address']['city'] = $m['city'];
            $oldciv['office_address']['address'] = $m['address'];
        }
    }
}

$layout['pagetitle'] = trans('Edit Division: $a', $olddiv['shortname']);

if (Localisation::getCurrentSystemLanguage() == 'pl_PL') {
    require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'tax_office_codes.php');
}

$usersList = $LMS->GetUserList(array('superuser' => 1));
unset($usersList['total']);

$SESSION->add_history_entry();

$SMARTY->assign('division', !empty($division) ? $division : $olddiv);
$SMARTY->assign('division_users', $divisionUsers);
$SMARTY->assign('userslist', $usersList);
$SMARTY->assign('error', $error);
$SMARTY->display('division/divisionedit.html');
