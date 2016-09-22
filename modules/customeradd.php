<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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
		FROM customers
		WHERE ' . $mode . ' != \'\' AND lower(' . $mode . ') ?LIKE? lower(' . $DB->Escape('%' . $search . '%') . ')
		GROUP BY item
		ORDER BY entries DESC, item ASC
		LIMIT 15');

	$eglible = array(); $descriptions = array();
	if ($candidates)
		foreach ($candidates as $idx => $row) {
			$eglible[$row['item']] = escape_js($row['item']);
			$descriptions[$row['item']] = escape_js($row['entries'] . ' ' . trans('entries'));
		}
	if ($eglible) {
		print "this.eligible = [\"" . implode('","', $eglible) . "\"];\n";
		print "this.descriptions = [\"" . implode('","', $descriptions) . "\"];\n";
	} else
		print "false;\n";
	exit;
}

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'customercontacttypes.php');

$customeradd = array();

if (isset($_POST['customeradd']))
{
	$customeradd = $_POST['customeradd'];

	$contacttypes = array_keys($CUSTOMERCONTACTTYPES);
	foreach ($contacttypes as &$contacttype)
		$contacttype .= 's';

	if (sizeof($customeradd))
		foreach ($customeradd as $key => $value)
			if ($key != 'uid' && $key != 'wysiwyg' && !in_array($key, $contacttypes))
				$customeradd[$key] = trim($value);

	if($customeradd['name'] == '' && $customeradd['lastname'] == '' && $customeradd['address'] == '')
	{
		$SESSION->redirect('?m=customeradd');
	}

	if($customeradd['lastname'] == '')
		$error['lastname'] = trans('Last/Company name cannot be empty!');

	if($customeradd['name'] == '' && !$customeradd['type'])
		$error['name'] = trans('First name cannot be empty!');
	
	if (ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.add_customer_group_required',false))) {
		if($customeradd['group'] == 0)
			$error['group'] = trans('Group name required!');
	}
	
	if ($customeradd['street'] == '')
		$error['street'] = trans('Street name required!');

	if ($customeradd['building'] != '' && $customeradd['street'] == '')
		$error['street'] = trans('Street name required!');

	if ($customeradd['apartment'] != '' && $customeradd['building'] == '')
		$error['building'] = trans('Building number required!');

	if ($customeradd['post_building'] != '' && $customeradd['post_street'] == '')
		$error['post_street'] = trans('Street name required!');

	if ($customeradd['post_apartment'] != '' && $customeradd['post_building'] == '')
		$error['post_building'] = trans('Building number required!');

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

	if($customeradd['icn'] != '' && !check_icn($customeradd['icn']))
		$error['icn'] = trans('Incorrect Identity Card Number!');

	if($customeradd['regon'] != '' && !check_regon($customeradd['regon']))
		$error['regon'] = trans('Incorrect Business Registration Number!');

	if($customeradd['zip'] !='' && !check_zip($customeradd['zip']) && !isset($customeradd['zipwarning']))
	{
		$error['zip'] = trans('Incorrect ZIP code! If you are sure you want to accept it, then click "Submit" again.');
		$customeradd['zipwarning'] = 1;
	}
	if($customeradd['post_zip'] !='' && !check_zip($customeradd['post_zip']) && !isset($customeradd['post_zipwarning']))
	{
		$error['post_zip'] = trans('Incorrect ZIP code! If you are sure you want to accept it, then click "Submit" again.');
		$customeradd['post_zipwarning'] = 1;
	}

	if($customeradd['pin'] == '')
		$error['pin'] = trans('PIN code is required!');
        elseif(!preg_match('/^[0-9]{4,6}$/', $customeradd['pin']))
	        $error['pin'] = trans('Incorrect PIN code!');

	foreach($customeradd['uid'] as $idx => $val)
	{
		$val = trim($val);
		switch($idx)
		{
			case IM_GG:
				if($val!='' && !check_gg($val))
					$error['gg'] = trans('Incorrect IM uin!');
			break;
			case IM_YAHOO:
				if($val!='' && !check_yahoo($val))
					$error['yahoo'] = trans('Incorrect IM uin!');
			break;
			case IM_SKYPE:
				if($val!='' && !check_skype($val))
					$error['skype'] = trans('Incorrect IM uin!');
			break;
		}

		if($val) $im[$idx] = $val;
	}

	$contacts = array();

	$emaileinvoice = false;

	foreach ($CUSTOMERCONTACTTYPES as $contacttype => $properties)
		$properties['validator']($customeradd, $contacts, $error);

	foreach ($customeradd['emails'] as $idx => $val)
		if ($val['type'] & (CONTACT_INVOICES | CONTACT_DISABLED))
			$emaileinvoice = true;

	if (isset($customeradd['invoicenotice']) && !$emaileinvoice)
		$error['invoicenotice'] = trans('If the customer wants to receive an electronic invoice must be checked e-mail address to which to send e-invoices');

	if ($customeradd['cutoffstop'] == '')
		$cutoffstop = 0;
	elseif (check_date($customeradd['cutoffstop'])) {
			list ($y, $m, $d) = explode('/', $customeradd['cutoffstop']);
			if (checkdate($m, $d, $y))
				$cutoffstop = mktime(23, 59, 59, $m, $d, $y);
			else
				$error['cutoffstop'] = trans('Incorrect date of cutoff suspending!');
	} else
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
        
        
//	print_r($error);die;
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
                
		if(isset($im) && $id)
			foreach($im as $idx => $val) {
				$DB->Execute('INSERT INTO imessengers (customerid, uid, type)
					VALUES(?, ?, ?)', array($id, $val, $idx));
				if ($SYSLOG) {
					$contactid = $DB->GetLastInsertID('imessengers');
					$args = array(
						SYSLOG::RES_IMCONTACT => $contactid,
						SYSLOG::RES_CUST => $id,
						'uid' => $val,
						'type' => $idx
					);
					$SYSLOG->AddMessage(SYSLOG::RES_IMCONTACT, SYSLOG::OPER_ADD, $args);
				}
			}

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

		if(!isset($customeradd['reuse']))
		{
			$SESSION->redirect('?m=customerinfo&id='.$id);
		}

		$reuse['status'] = $customeradd['status'];
		foreach (array_keys($CUSTOMERCONTACTTYPES) as $contacttype)
			$reuse[$contacttype . 's'][] = array();
		unset($customeradd);
		$customeradd = $reuse;
		$customeradd['reuse'] = '1';
	}
} else
	foreach (array_keys($CUSTOMERCONTACTTYPES) as $contacttype)
		$customeradd[$contacttype . 's'][] = array();

$default_zip = ConfigHelper::getConfig('phpui.default_zip');
$default_city = ConfigHelper::getConfig('phpui.default_city');
$default_address = ConfigHelper::getConfig('phpui.default_address');
$default_stateid = ConfigHelper::getConfig('phpui.default_stateid');
$default_countryid = ConfigHelper::getConfig('phpui.default_countryid');
$default_status = ConfigHelper::getConfig('phpui.default_status');

if (!isset($customeradd['zip']) && $default_zip) {
	$customeradd['zip'] = $default_zip;
} if (!isset($customeradd['city']) && $default_city) {
	$customeradd['city'] = $default_city;
} if (!isset($customeradd['address']) && $default_address) {
	$customeradd['address'] = $default_address;
} if (!isset($customeradd['default_stateid']) && $default_stateid) {
	$customeradd['stateid'] = $default_stateid;
} if (!isset($customeradd['default_countryid']) && $default_countryid) {
	$customeradd['countryid'] = $default_countryid;
} if (!isset($customeradd['default_status']) && $default_status) {
        $customeradd['status'] = $default_status;
}

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

$SMARTY->assign('xajax', $LMS->RunXajax());
$SMARTY->assign('cstateslist', $LMS->GetCountryStates());
$SMARTY->assign('countrieslist', $LMS->GetCountries());
$SMARTY->assign('divisions', $DB->GetAll('SELECT id, shortname, status FROM divisions ORDER BY shortname'));
$SMARTY->assign('customeradd', $customeradd);
if (ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.add_customer_group_required',false))) {
		$SMARTY->assign('groups',$DB->GetAll('SELECT id,name FROM customergroups ORDER BY id'));
	}
$SMARTY->assign('error', $error);


$SMARTY->display('customer/customeradd.html');

?>
