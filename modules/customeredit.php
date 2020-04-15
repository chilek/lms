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

if (isset($_GET['search'])) {
    $SESSION->restore('customersearch', $search);
    $SESSION->restore('cslo', $order);
    $SESSION->restore('csls', $state);
    $SESSION->restore('cslsk', $statesqlkey);
    $SESSION->restore('csln', $network);
    $SESSION->restore('cslng', $nodegroup);
    $SESSION->restore('cslg', $customergroup);
    $SESSION->restore('cslk', $sqlskey);
    $SESSION->restore('csld', $division);

    $customerlist = $LMS->GetCustomerList(compact(
        'order',
        'state',
        'statesqlkey',
        'network',
        'customergroup',
        'search',
        'time',
        'sqlskey',
        'nodegroup',
        'division'
    ));

    unset($customerlist['total']);
    unset($customerlist['state']);
    unset($customerlist['network']);
    unset($customerlist['customergroup']);
    unset($customerlist['direction']);
    unset($customerlist['order']);
    unset($customerlist['below']);
    unset($customerlist['over']);

    if ($customerlist && isset($_POST['consents']) && !empty($_POST['consents'])) {
        foreach ($customerlist as $row) {
            switch ($_GET['oper']) {
                case 'addconsents':
                    $LMS->addCustomerConsents($row['id'], $_POST['consents']);
                    break;
                case 'removeconsents':
                    $LMS->removeCustomerConsents($row['id'], $_POST['consents']);
                    break;
            }
        }
    }

    if ($SESSION->is_set('backto')) {
        $SESSION->redirect('?' . $SESSION->get('backto'));
    } else {
        $SESSION->redirect('?m=customerlist');
    }
}

if (!isset($_POST['xjxfun'])) {
    require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'customercontacttypes.php');

    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $exists = $LMS->CustomerExists($_GET['id']);

    if ($exists < 0 && $action != 'recover') {
        $SESSION->redirect('?m=customerinfo&id='.$_GET['id']);
    } elseif (!$exists) {
        $SESSION->redirect('?m=customerlist');
    } else {
        $backurl = $SESSION->is_set('backto') ? '?' . $SESSION->get('backto') : '?m=customerlist';

        $pin_min_size = intval(ConfigHelper::getConfig('phpui.pin_min_size', 4));
        if (!$pin_min_size) {
            $pin_min_size = 4;
        }
        $pin_max_size = intval(ConfigHelper::getConfig('phpui.pin_max_size', 6));
        if (!$pin_max_size) {
            $pin_max_size = 6;
        }
        if ($pin_min_size > $pin_max_size) {
            $pin_max_size = $pin_min_size;
        }
        $pin_allowed_characters = ConfigHelper::getConfig('phpui.pin_allowed_characters', '0123456789');

        if (isset($_POST['customerdata'])) {
            $customerdata = $_POST['customerdata'];

            $contacttypes = array_keys($CUSTOMERCONTACTTYPES);
            foreach ($contacttypes as &$contacttype) {
                $contacttype .= 's';
            }

            foreach ($customerdata as $key => $value) {
                if ($key != 'uid' && $key != 'wysiwyg' && !in_array($key, $contacttypes)) {
                    $customerdata[$key] = trim_rec($value);
                }
            }

            if ($customerdata['lastname'] == '') {
                $error['lastname'] = trans('Last/Company name cannot be empty!');
            }

            if ($customerdata['name'] == '' && !$customerdata['type']) {
                $error['name'] = trans('First name cannot be empty!');
            }

            // check addresses
            foreach ($customerdata['addresses'] as $k => $v) {
                if ($v['location_address_type'] == BILLING_ADDRESS && !$v['location_city_name']) {
                    $error['customerdata[addresses][' . $k . '][location_city_name]'] = trans('City name required!');
                    $customerdata['addresses'][ $k ]['show'] = true;
                }

                if (!ConfigHelper::checkPrivilege('full_access') && ConfigHelper::checkConfig('phpui.teryt_required')
                    && !empty($v['location_city_name']) && ($v['location_country_id'] == 2 || empty($v['location_country_id']))
                    && (!isset($v['teryt']) || empty($v['location_city']))) {
                    $error['customerdata[addresses][' . $k . '][teryt]'] = trans('TERRIT address is required!');
                    $customerdata['addresses'][ $k ]['show'] = true;
                }

                if ($v['location_zip'] && !check_zip($v['location_zip'])) {
                    $error['customerdata[addresses][' . $k . '][location_zip]'] = trans('Incorrect ZIP code!');
                    $customerdata['addresses'][ $k ]['show'] = true;
                }
            }

            if ($customerdata['ten'] !='') {
                if (!isset($customerdata['tenwarning']) && !check_ten($customerdata['ten'])) {
                    $warning['ten'] = trans('Incorrect Tax Exempt Number! If you are sure you want to accept it, then click "Submit" again.');
                    $tenwarning = 1;
                }
                $ten_existence_check = ConfigHelper::getConfig('phpui.customer_ten_existence_check', 'none');
                $ten_existence_scope = ConfigHelper::getConfig('phpui.customer_ten_existence_scope', 'global');
                if (preg_match('/^(global|division)$/', $ten_existence_scope)) {
                    $ten_existence_scope = 'global';
                }
                $ten_exists = $LMS->checkCustomerTenExistence(
                    $_GET['id'],
                    $customerdata['ten'],
                    $ten_existence_scope == 'global' ? null : $customerdata['divisionid']
                );
                switch ($ten_existence_check) {
                    case 'warning':
                        if (!isset($customerdata['tenexistencewarning']) && $ten_exists) {
                            $warning['ten'] = trans('Customer with specified Tax Exempt Number already exists! If you are sure you want to accept it, then click "Submit" again.');
                            $tenexistencewarning = 1;
                        }
                        break;
                    case 'error':
                        if ($ten_exists) {
                            $error['ten'] = trans('Customer with specified Tax Exempt Number already exists!');
                        }
                        break;
                }
            }

            if ($customerdata['ssn'] != '') {
                if (!isset($customerdata['ssnwarning']) && !check_ssn($customerdata['ssn'])) {
                    $warning['ssn'] = trans('Incorrect Social Security Number! If you are sure you want to accept it, then click "Submit" again.');
                    $ssnwarning = 1;
                }
                $ssn_existence_check = ConfigHelper::getConfig('phpui.customer_ssn_existence_check', 'none');
                $ssn_existence_scope = ConfigHelper::getConfig('phpui.customer_ssn_existence_scope', 'global');
                if (preg_match('/^(global|division)$/', $ssn_existence_scope)) {
                    $ssn_existence_scope = 'global';
                }
                $ssn_exists = $LMS->checkCustomerSsnExistence(
                    $_GET['id'],
                    $customerdata['ssn'],
                    $ssn_existence_scope == 'global' ? null : $customerdata['divisionid']
                );
                switch ($ssn_existence_check) {
                    case 'warning':
                        if (!isset($customerdata['ssnexistencewarning']) && $ssn_exists) {
                            $warning['ssn'] = trans('Customer with specified Social Security Number already exists! If you are sure you want to accept it, then click "Submit" again.');
                            $ssnexistencewarning = 1;
                        }
                        break;
                    case 'error':
                        if ($ssn_exists) {
                            $error['ssn'] = trans('Customer with specified Social Security Number already exists!');
                        }
                        break;
                }
            }

            if ($customerdata['regon'] != '' && !check_regon($customerdata['regon'])) {
                $error['regon'] = trans('Incorrect Business Registration Number!');
            }

            if ($customerdata['icn'] != '' && !isset($customerdata['icnwarning']) && !check_icn($customerdata['icn'])) {
                $warning['icn'] = trans('Incorrect Identity Card Number! If you are sure you want to accept, then click "Submit" again.');
                $icnwarning = 1;
            }

            if ($customerdata['pin'] == '') {
                $error['pin'] = trans('PIN code is required!');
            } elseif (!validate_random_string($customerdata['pin'], $pin_min_size, $pin_max_size, $pin_allowed_characters)) {
                $error['pin'] = trans('Incorrect PIN code!');
            }

            if ($customerdata['status'] == 1 && $LMS->GetCustomerNodesNo($customerdata['id'])) {
                $error['status'] = trans('Interested customers can\'t have computers!');
            }

            $contacts = array();

            $emaileinvoice = false;

            foreach ($CUSTOMERCONTACTTYPES as $contacttype => $properties) {
                $properties['validator']($customerdata, $contacts, $error);
            }

            $customer_invoice_notice_consent_check = ConfigHelper::getConfig('phpui.customer_invoice_notice_consent_check', 'error');
            if ($customer_invoice_notice_consent_check != 'none') {
                if ($customerdata['emails']) {
                    foreach ($customerdata['emails'] as $idx => $val) {
                        if ($val['type'] & (CONTACT_INVOICES | CONTACT_DISABLED)) {
                            $emaileinvoice = true;
                        }
                    }
                }
            }

            if (isset($customerdata['consents'][CCONSENT_INVOICENOTICE]) && !$emaileinvoice) {
                if ($customer_invoice_notice_consent_check == 'error') {
                    $error['chkconsent' . CCONSENT_INVOICENOTICE] =
                        trans('If the customer wants to receive an electronic invoice must be checked e-mail address to which to send e-invoices');
                } elseif ($customer_invoice_notice_consent_check == 'warning'
                    && !isset($warnings['customerdata-consents--' . CCONSENT_INVOICENOTICE . '-'])) {
                    $warning['customerdata[consents][' . CCONSENT_INVOICENOTICE . ']'] =
                        trans('If the customer wants to receive an electronic invoice must be checked e-mail address to which to send e-invoices');
                }
            }

            if (isset($customerdata['cutoffstopindefinitely'])) {
                $cutoffstop = intval(pow(2, 31) - 1);
            } elseif ($customerdata['cutoffstop'] == '') {
                $cutoffstop = 0;
            } elseif ($cutoffstop = date_to_timestamp($customerdata['cutoffstop'])) {
                $cutoffstop += 86399;
            } else {
                $error['cutoffstop'] = trans('Incorrect date of cutoff suspending!');
            }

            if (!preg_match('/^[\-]?[0-9]+$/', $customerdata['paytime'])) {
                $error['paytime'] = trans('Invalid deadline format!');
            }

            $hook_data = $LMS->executeHook(
                'customeredit_validation_before_submit',
                array(
                    'customerdata' => $customerdata,
                    'error' => $error,
                    'warning' => $warning,
                )
            );

            $customerdata = $hook_data['customerdata'];
            $error = $hook_data['error'];
            $warning = $hook_data['warning'];

            if (!$error && !$warning) {
                $customerdata['cutoffstop'] = $cutoffstop;

                if (!isset($customerdata['consents'])) {
                    $customerdata['consents'] = array();
                }

                if (isset($customerdata['consents'][CCONSENT_DATE])) {
                    $consent = $DB->GetOne('SELECT consentdate FROM customeraddressview WHERE id = ?', array($customerdata['id']));
                    if ($consent) {
                        $customerdata['consents'][CCONSENT_DATE] = $consent;
                    }
                }

                if (!isset($customerdata['divisionid'])) {
                    $customerdata['divisionid'] = 0;
                }

                $LMS->CustomerUpdate($customerdata);

                    $hook_data = $LMS->executeHook(
                        'customeredit_after_submit',
                        array(
                            'customerdata' => $customerdata,
                        )
                    );
                        $customerdata = $hook_data['customerdata'];
                        $id = $hook_data['id'];

                if ($SYSLOG) {
                        $contactids = $DB->GetCol('SELECT id FROM customercontacts WHERE customerid = ?', array($customerdata['id']));
                    if (!empty($contactids)) {
                        foreach ($contactids as $contactid) {
                            $args = array(
                                SYSLOG::RES_CUSTCONTACT => $contactid,
                                SYSLOG::RES_CUST => $customerdata['id']
                            );
                            $SYSLOG->AddMessage(SYSLOG::RES_CUSTCONTACT, SYSLOG::OPER_DELETE, $args);
                        }
                    }
                }

                $DB->Execute('DELETE FROM customercontacts WHERE customerid = ?', array($customerdata['id']));
                if (!empty($contacts)) {
                    foreach ($contacts as $contact) {
                        if ($contact['type'] & CONTACT_BANKACCOUNT) {
                            $contact['contact'] = preg_replace('/[^a-zA-Z0-9]/', '', $contact['contact']);
                        }
                        $DB->Execute(
                            'INSERT INTO customercontacts (customerid, contact, name, type) VALUES (?, ?, ?, ?)',
                            array($customerdata['id'], $contact['contact'], $contact['name'], $contact['type'])
                        );
                        if ($SYSLOG) {
                            $contactid = $DB->GetLastInsertID('customercontacts');
                            $args = array(
                                SYSLOG::RES_CUSTCONTACT => $contactid,
                                SYSLOG::RES_CUST => $customerdata['id'],
                                'contact' => $contact['contact'],
                                'name' => $contact['name'],
                                'type' => $contact['type'],
                            );
                            $SYSLOG->AddMessage(SYSLOG::RES_CUSTCONTACT, SYSLOG::OPER_ADD, $args);
                        }
                    }
                }

                $SESSION->redirect($backurl);
            } else {
                $olddata = $LMS->GetCustomer($_GET['id']);

                $customerinfo = $customerdata;
                $customerinfo['createdby'] = $olddata['createdby'];
                $customerinfo['modifiedby'] = $olddata['modifiedby'];
                $customerinfo['creationdateh'] = $olddata['creationdateh'];
                $customerinfo['moddateh'] = $olddata['moddateh'];
                $customerinfo['customername'] = $olddata['customername'];
                $customerinfo['balance'] = $olddata['balance'];
                $customerinfo['stateid'] = isset($olddata['stateid']) ? $olddata['stateid'] : 0;
                $customerinfo['post_stateid'] = isset($olddata['post_stateid']) ? $olddata['post_stateid'] : 0;
                $customerinfo['tenwarning'] = empty($tenwarning) ? 0 : 1;
                $customerinfo['tenexistencewarning'] = empty($tenexistencewarning) ? 0 : 1;
                $customerinfo['ssnwarning'] = empty($ssnwarning) ? 0 : 1;
                $customerinfo['ssnexistencewarning'] = empty($ssnexistencewarning) ? 0 : 1;
                $customerinfo['icnwarning'] = empty($icnwarning) ? 0 : 1;
            }
        } else {
            $customerinfo = $LMS->GetCustomer($_GET['id']);

            $customerinfo['cutoffstopindefinitely'] = 0;
            if ($customerinfo['cutoffstop']) {
                if ($customerinfo['cutoffstop'] == intval(pow(2, 31) - 1)) {
                    $customerinfo['cutoffstop'] = 0;
                    $customerinfo['cutoffstopindefinitely'] = 1;
                } else {
                    $customerinfo['cutoffstop'] = strftime('%Y/%m/%d', $customerinfo['cutoffstop']);
                }
            } else {
                $customerinfo['cutoffstop'] = 0;
            }

            if (!empty($customerinfo['accounts'])) {
                foreach ($customerinfo['accounts'] as &$account) {
                    $account['contact'] = format_bankaccount($account['contact']);
                }
            }

            if (empty($customerinfo['emails'])) {
                $customerinfo['emails'] = array(
                0 => array(
                    'contact' => '',
                    'name' => '',
                    'type' => 0
                )
                );
            }
            if (empty($customerinfo['phones'])) {
                $customerinfo['phones'] = array(
                0 => array(
                    'contact' => '',
                    'name' => '',
                    'type' => 0
                )
                );
            }
        }
        $SMARTY->assign('backurl', $backurl);
    }

    $layout['pagetitle'] = trans('Customer Edit: $a', $customerinfo['customername']);

    $customerid = $customerinfo['id'];

    include(MODULES_DIR.'/customer.inc.php');
}

$LMS->InitXajax();

$hook_data = $LMS->executeHook(
    'customeredit_before_display',
    array(
        'customerinfo' => $customerinfo,
        'smarty' => $SMARTY
    )
);
$customerinfo = $hook_data['customerinfo'];

$SMARTY->assign('xajax', $LMS->RunXajax());
$SMARTY->assign(compact('pin_min_size', 'pin_max_size', 'pin_allowed_characters'));
$SMARTY->assign('customerinfo', $customerinfo);
$SMARTY->assign('cstateslist', $LMS->GetCountryStates());
$SMARTY->assign('countrieslist', $LMS->GetCountries());
$SMARTY->assign('divisions', $LMS->GetDivisions());
$SMARTY->assign('recover', ($action == 'recover' ? 1 : 0));
$SMARTY->assign('customeredit_sortable_order', $SESSION->get_persistent_setting('customeredit-sortable-order'));
$SMARTY->display('customer/customeredit.html');
