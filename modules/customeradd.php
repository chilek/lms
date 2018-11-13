<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

if (isset($_GET['ajax'])) {
	header('Content-type: text/plain');
	$search = urldecode(trim($_GET['what']));

	switch ($_GET['mode']) {
		case 'street':
			$mode = 'street';
			break;

		case 'zip':
			$mode = 'zip';
			break;

		case 'city':
			$mode = 'city';
			break;
	}

	if (!isset($mode)) { print 'false;'; exit; }

	$candidates = $DB->GetAll('SELECT ' . $mode . ' as item, count(id) AS entries
		FROM customerview
		WHERE ' . $mode . ' != \'\' AND lower(' . $mode . ') ?LIKE? lower(' . $DB->Escape('%' . $search . '%') . ')
		GROUP BY item
		ORDER BY entries DESC, item ASC
		LIMIT 15');

	$result = array();
	if ($candidates)
		foreach ($candidates as $idx => $row) {
			$name = $row['item'];
			$name_class = '';
			$description = $row['entries'] . ' ' . trans('entries');
			$description_class = '';
			$action = '';

			$result[$row['item']] = compact('name', 'name_class', 'description', 'description_class', 'action');
		}
	header('Content-Type: application/json');
	echo json_encode(array_values($result));
	exit;
}

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'customercontacttypes.php');

$pin_min_size = intval(ConfigHelper::getConfig('phpui.pin_min_size', 4));
if (!$pin_min_size)
	$pin_min_size = 4;
$pin_max_size = intval(ConfigHelper::getConfig('phpui.pin_max_size', 6));
if (!$pin_max_size)
	$pin_max_size = 6;
if ($pin_min_size > $pin_max_size)
	$pin_max_size = $pin_min_size;
$pin_allowed_characters = ConfigHelper::getConfig('phpui.pin_allowed_characters', '0123456789');

$customeradd = array();

if (isset($_POST['customeradd'])) {
	$customeradd = $_POST['customeradd'];

	$contacttypes = array_keys($CUSTOMERCONTACTTYPES);
	foreach ($contacttypes as &$contacttype)
		$contacttype .= 's';

	if (count($customeradd)) {
		foreach ($customeradd as $key => $value) {
			if ($key != 'uid' && $key != 'wysiwyg' && !in_array($key, $contacttypes)) {
				$customeradd[$key] = trim_rec($value);
			}
		}
	}

	if ($customeradd['lastname'] == '')
		$error['lastname'] = trans('Last/Company name cannot be empty!');

	if ($customeradd['name'] == '' && !$customeradd['type'])
		$error['name'] = trans('First name cannot be empty!');
	
	if (ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.add_customer_group_required', false))) {
		if ($customeradd['group'] == 0)
			$error['group'] = trans('Group name required!');
	}

	if ($customeradd['ten'] !='') {
		if (!isset($customeradd['tenwarning']) && !check_ten($customeradd['ten'])) {
			$error['ten'] = trans('Incorrect Tax Exempt Number! If you are sure you want to accept it, then click "Submit" again.');
			$customeradd['tenwarning'] = 1;
		}
		$ten_existence_check = ConfigHelper::getConfig('phpui.customer_ten_existence_check', 'none');
		$ten_exists = $DB->GetOne("SELECT id FROM customers WHERE id <> ? AND REPLACE(REPLACE(ten, '-', ''), ' ', '') = ?",
			array($_GET['id'], preg_replace('/- /', '', $customeradd['ten']))) > 0;
		switch ($ten_existence_check) {
			case 'warning':
				if (!isset($customeradd['tenexistencewarning']) && $ten_exists) {
					$error['ten'] = trans('Customer with specified Tax Exempt Number already exists! If you are sure you want to accept it, then click "Submit" again.');
					$customeradd['tenexistencewarning'] = 1;
				}
				break;
			case 'error':
				if ($ten_exists)
					$error['ten'] = trans('Customer with specified Tax Exempt Number already exists!');
				break;
		}
	}

	if ($customeradd['ssn'] != '') {
		if (!isset($customeradd['ssnwarning']) && !check_ssn($customeradd['ssn'])) {
			$error['ssn'] = trans('Incorrect Social Security Number! If you are sure you want to accept it, then click "Submit" again.');
			$customeradd['ssnwarning'] = 1;
		}
		$ssn_existence_check = ConfigHelper::getConfig('phpui.customer_ssn_existence_check', 'none');
		$ssn_exists = $DB->GetOne("SELECT id FROM customers WHERE id <> ? AND REPLACE(REPLACE(ssn, '-', ''), ' ', '') = ?",
			array($_GET['id'], preg_replace('/- /', '', $customeradd['ssn']))) > 0;
		switch ($ssn_existence_check) {
			case 'warning':
				if (!isset($customeradd['ssnexistencewarning']) && $ssn_exists) {
					$error['ssn'] = trans('Customer with specified Social Security Number already exists! If you are sure you want to accept it, then click "Submit" again.');
					$customeradd['ssnexistencewarning'] = 1;
				}
				break;
			case 'error':
				if ($ssn_exists)
					$error['ssn'] = trans('Customer with specified Social Security Number already exists!');
				break;
		}
	}

	if ($customeradd['icn'] != '' && !isset($customeradd['icnwarning']) && !check_icn($customeradd['icn'])) {
		$error['icn'] = trans('Incorrect Identity Card Number! If you are sure you want to accept, then click "Submit" again.');
		$icnwarning = 1;
	}

	if ($customeradd['regon'] != '' && !check_regon($customeradd['regon']))
		$error['regon'] = trans('Incorrect Business Registration Number!');

	if ($customeradd['pin'] == '')
		$error['pin'] = trans('PIN code is required!');
	elseif (!validate_random_string($customeradd['pin'], $pin_min_size, $pin_max_size, $pin_allowed_characters))
		$error['pin'] = trans('Incorrect PIN code!');

	$contacts = array();

	$emaileinvoice = false;

	foreach ($CUSTOMERCONTACTTYPES as $contacttype => $properties)
		$properties['validator']($customeradd, $contacts, $error);

    if ( !empty($customeradd['emails']) ) {
		foreach ($customeradd['emails'] as $idx => $val) {
			if ($val['type'] & (CONTACT_INVOICES | CONTACT_DISABLED)) {
				$emaileinvoice = true;
			}
		}
	}
	
	// check addresses
	foreach ( $customeradd['addresses'] as $k=>$v ) {
		if ( $v['location_address_type'] == BILLING_ADDRESS && !$v['location_city_name'] ) {
			$error['customeradd[addresses][' . $k . '][location_city_name]'] = trans('City name required!');
			$customeradd['addresses'][ $k ]['show'] = true;
		}

		if ( $v['location_zip'] && !check_zip($v['location_zip']) ) {
			$error['customeradd[addresses][' . $k . '][location_zip]'] = trans('Incorrect ZIP code!');
			$customeradd['addresses'][ $k ]['show'] = true;
		}
	}

	if (isset($customeradd['invoicenotice']) && !$emaileinvoice)
		$error['invoicenotice'] = trans('If the customer wants to receive an electronic invoice must be checked e-mail address to which to send e-invoices');

	if (isset($customeradd['cutoffstopindefinitely']))
		$cutoffstop = intval(pow(2, 31) - 1);
	elseif ($customeradd['cutoffstop'] == '')
		$cutoffstop = 0;
	elseif ($cutoffstop = date_to_timestamp($customeradd['cutoffstop']))
		$cutoffstop += 86399;
	else
		$error['cutoffstop'] = trans('Incorrect date of cutoff suspending!');

        $hook_data = $LMS->executeHook(
            'customeradd_validation_before_submit', 
            array(
                'customeradd' => $customeradd,
                'error' => $error
            )
        );
        $customeradd = $hook_data['customeradd'];
        $error = $hook_data['error'];

	if (!$error) {
		$customeradd['cutoffstop'] = $cutoffstop;

		if(!isset($customeradd['consentdate']))
			$customeradd['consentdate'] = 0;
		if(!isset($customeradd['divisionid']))
			$customeradd['divisionid'] = 0;

		$id = $LMS->CustomerAdd($customeradd);

        $hook_data = $LMS->executeHook(
            'customeradd_after_submit',
                array(
                    'id' => $id,
                    'customeradd' => $customeradd,
                )
            );
        $customeradd = $hook_data['customeradd'];
        $id = $hook_data['id'];

		if ($id && !empty($contacts))
			foreach ($contacts as $contact) {
				if ($contact['type'] & CONTACT_BANKACCOUNT)
					$contact['contact'] = preg_replace('/[^a-zA-Z0-9]/', '', $contact['contact']);
				$DB->Execute('INSERT INTO customercontacts (customerid, contact, name, type)
					VALUES(?, ?, ?, ?)', array($id, $contact['contact'], $contact['name'], $contact['type']));
				if ($SYSLOG) {
					$contactid = $DB->GetLastInsertID('customercontacts');
					$args = array(
						SYSLOG::RES_CUSTCONTACT => $contactid,
						SYSLOG::RES_CUST => $id,
						'contact' => $contact['contact'],
						'name' => $contact['name'],
						'type' => $contact['type'],
					);
					$SYSLOG->AddMessage(SYSLOG::RES_CUSTCONTACT, SYSLOG::OPER_ADD, $args);
				}
			}

		if (!isset($customeradd['reuse'])) {
			$SESSION->redirect('?m=customerinfo&id='.$id);
		}

		$reuse['status'] = $customeradd['status'];
		foreach (array_keys($CUSTOMERCONTACTTYPES) as $contacttype)
			$reuse[$contacttype . 's'][] = array();
		unset($customeradd);
		$customeradd = $reuse;
		$customeradd['reuse'] = '1';
	}
} else {
	$customeradd['emails'] = array(
		0 => array(
			'contact' => '',
			'name' => '',
			'type' => 0
		)
	);
	$customeradd['phones'] = array(
		0 => array(
			'contact' => '',
			'name' => '',
			'type' => 0
		)
	);
}

if (!isset($customeradd['cutoffstopindefinitely']))
	$customeradd['cutoffstopindefinitely'] = 0;

$layout['pagetitle'] = trans('New Customer');

$LMS->InitXajax();

$hook_data = $LMS->executeHook(
    'customeradd_before_display',
    array(
        'customeradd' => $customeradd,
        'smarty' => $SMARTY
    )
);
$customeradd = $hook_data['customeradd'];

$SMARTY->assign('xajax'        , $LMS->RunXajax());
$SMARTY->assign(compact('pin_min_size', 'pin_max_size', 'pin_allowed_characters'));
$SMARTY->assign('cstateslist'  , $LMS->GetCountryStates());
$SMARTY->assign('countrieslist', $LMS->GetCountries());
$SMARTY->assign('divisions'    , $LMS->GetDivisions());
$SMARTY->assign('customeradd'  , $customeradd);
if (ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.add_customer_group_required',false))) {
		$SMARTY->assign('groups',$DB->GetAll('SELECT id,name FROM customergroups ORDER BY id'));
	}
$SMARTY->assign('error', $error);


$SMARTY->display('customer/customeradd.html');

?>
