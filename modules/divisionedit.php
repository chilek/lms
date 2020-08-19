<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

$olddiv = $DB->GetRow('SELECT d.*,
		addr.name as location_name,
		addr.city as location_city_name, addr.street as location_street_name,
		addr.city_id as location_city, addr.street_id as location_street,
		addr.house as location_house, addr.flat as location_flat,
		addr.zip as location_zip, addr.state as location_state_name,
		addr.state_id as location_state, addr.country_id as location_country_id,
		addr.postoffice AS location_postoffice
	FROM divisions d
		LEFT JOIN addresses addr           ON addr.id = d.address_id
		LEFT JOIN location_cities lc       ON lc.id = addr.city_id
		LEFT JOIN location_streets ls      ON ls.id = addr.street_id
		LEFT JOIN location_street_types lt ON lt.id = ls.typeid
	WHERE d.id = ?', array($_GET['id']));

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

    if ($division['location_city_name'] == '') {
        $error['division[location_city_name]'] = trans('City is required!');
    }

    Localisation::setSystemLanguage($LMS->getCountryCodeById($division['location_country_id']));
    if ($division['location_zip'] == '') {
        $error['division[location_zip]'] = trans('Zip code is required!');
    } else if (!check_zip($division['location_zip'])) {
        $error['division[location_zip]'] = trans('Incorrect ZIP code!');
    }
    Localisation::resetSystemLanguage();

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
} else {
    if ($olddiv['location_city'] || $olddiv['location_street']) {
        $olddiv['teryt'] = true;
        if ($olddiv['location_city'] && $olddiv['location_street']) {
            preg_match('/^(?<city>.+)\s*,\s*(?<address>.+)$/', location_str($olddiv), $m);
            $olddiv['city'] = $m['city'];
            $oldciv['address'] = $m['address'];
        }
    }
}

$layout['pagetitle'] = trans('Edit Division: $a', $olddiv['shortname']);

if (Localisation::getCurrentSystemLanguage() == 'pl_PL') {
    require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'tax_office_codes.php');
}

$usersList = $LMS->GetUserList(array('superuser' => 1));
unset($usersList['total']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('division', !empty($division) ? $division : $olddiv);
$SMARTY->assign('division_users', $divisionUsers);
$SMARTY->assign('userslist', $usersList);
$SMARTY->assign('error', $error);
$SMARTY->display('division/divisionedit.html');
