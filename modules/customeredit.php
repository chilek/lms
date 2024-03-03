<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2021 LMS Developers
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

    require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'customercontacttypes.php');
    if ($customerlist && (
        (!empty($_POST['consents']))
        || ($_GET['oper'] == 'changetype' && ($_GET['type'] == CTYPES_PRIVATE || $_GET['type'] == CTYPES_COMPANY))
        || (isset($_GET['type']) && !empty($_POST['contactflags'][$_GET['type']])
            && isset($CUSTOMERCONTACTTYPES[$_GET['type']]))
        || ($_GET['oper'] == 'changestatus' && isset($_GET['status']) && isset($CSTATUSES[$_GET['status']]))
        || ($_GET['oper'] == 'restore')
    )) {
        foreach ($customerlist as $row) {
            switch ($_GET['oper']) {
                case 'addconsents':
                    $LMS->addCustomerConsents($row['id'], $_POST['consents']);
                    break;
                case 'removeconsents':
                    $LMS->removeCustomerConsents($row['id'], $_POST['consents']);
                    break;
                case 'addflags':
                    $LMS->addCustomerContactFlags($row['id'], $_GET['type'], $_POST['contactflags'][$_GET['type']]);
                    break;
                case 'removeflags':
                    $LMS->removeCustomerContactFlags($row['id'], $_GET['type'], $_POST['contactflags'][$_GET['type']]);
                    break;
                case 'changetype':
                    $LMS->changeCustomerType($row['id'], $_GET['type']);
                    break;
                case 'changestatus':
                    $LMS->changeCustomerStatus($row['id'], $_GET['status']);
                    break;
                case 'restore':
                    if (!empty($row['deleted'])) {
                        $LMS->restoreCustomer($row['id']);
                    }
                    break;
            }
        }
    }

    $SESSION->redirect_to_history_entry('m=customerlist');
}

if (isset($_GET['oper'])) {
    switch ($_GET['oper']) {
        case 'karma-raise':
            header('Content-Type: application/json');
            $result = $LMS->raiseCustomerKarma($_GET['id']);
            die(json_encode($result));
        case 'karma-lower':
            header('Content-Type: application/json');
            $result = $LMS->lowerCustomerKarma($_GET['id']);
            die(json_encode($result));
        case 'check-conflict':
            header('Content-Type: application/json');
            $SESSION->restore('customer_edit_start', $customer_edit_start, true);
            if (empty($customer_edit_start) || $customer_edit_start['id'] != $_GET['id']) {
                die('[]');
            }
            $modification = $LMS->getCustomerModificationInfo($_GET['id']);
            if ($customer_edit_start['date'] > $modification['date']) {
                die('[]');
            }
            die(json_encode(trans(
                "In meantime user '\$a' has modified edited customer (\$b).\n"
                . 'Despite this you want to make customer modification which you had made in form?',
                empty($modification['username']) ? trans('unknown') : htmlspecialchars($modification['username']),
                date('Y/m/d H:i:s')
            )));
    }
}
if (!isset($_POST['xjxfun'])) {
    require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'customercontacttypes.php');

    $action = $_GET['action'] ?? '';
    $exists = $LMS->CustomerExists($_GET['id']);

    if ($exists < 0 && $action != 'recover') {
        $SESSION->redirect('?m=customerinfo&id='.$_GET['id']);
    } elseif (!$exists) {
        $SESSION->redirect('?m=customerlist');
    } else {
        $history_entry = $SESSION->get_history_entry();
        $backurl = $history_entry ? '?' . $history_entry : '?m=customerlist';

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

                $countryCode = null;
                if (!empty($v['location_country_id'])) {
                    $countryCode = $LMS->getCountryCodeById($v['location_country_id']);
                    if ($v['location_address_type'] == BILLING_ADDRESS) {
                        $billingCountryCode = $countryCode;
                    }
                }

                if (!ConfigHelper::checkPrivilege('full_access') && ConfigHelper::checkConfig('phpui.teryt_required')
                    && !empty($v['location_city_name']) && ($v['location_country_id'] == 2 || empty($v['location_country_id']))
                    && (!isset($v['teryt']) || empty($v['location_city'])) && $LMS->isTerritState($v['location_state_name'])) {
                    $error['customerdata[addresses][' . $k . '][teryt]'] = trans('TERYT address is required!');
                    $customerdata['addresses'][ $k ]['show'] = true;
                }

                Localisation::setSystemLanguage($countryCode);
                if ($v['location_zip'] && !check_zip($v['location_zip'])) {
                    $error['customerdata[addresses][' . $k . '][location_zip]'] = trans('Incorrect ZIP code!');
                    $customerdata['addresses'][ $k ]['show'] = true;
                }
            }

            if (isset($billingCountryCode)) {
                Localisation::setSystemLanguage($billingCountryCode);
            }

            if (isset($customerdata['icexpires'])) {
                $ic_expires = $customerdata['icexpires'] > 0 && $customerdata['icexpires'] < time();
                if ($ic_expires) {
                    $identity_card_expiration_check = ConfigHelper::getConfig(
                        'phpui.customer_identity_card_expiration_check',
                        'none'
                    );
                    switch ($identity_card_expiration_check) {
                        case 'warning':
                            if (!isset($warnings['customerdata-icexpires-'])) {
                                $warning['customerdata[icexpires]'] = trans('Customer identity card expired or expires soon!');
                            }
                            break;
                        case 'error':
                            $error['icexpires'] = trans('Customer identity card expired or expires soon!');
                            break;
                    }
                }
            }

            if (isset($customerdata['ten'])) {
                if ($customerdata['ten'] != '' && $customerdata['ten'] != $LMS->getCustomerTen($_GET['id'])) {
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
            }

            if (isset($customerdata['ssn'])) {
                if ($customerdata['ssn'] != '' && $customerdata['ssn'] != $LMS->getCustomerSsn($_GET['id'])) {
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
            }

            if ($customerdata['regon'] != '' && !check_regon($customerdata['regon'])) {
                $error['regon'] = trans('Incorrect Business Registration Number!');
            }

            if (isset($customerdata['icn'])) {
                if ($customerdata['icn'] != '' && $customerdata['ict'] == 0 && !isset($customerdata['icnwarning']) && !check_icn($customerdata['icn'])) {
                    $warning['icn'] = trans('Incorrect Identity Card Number! If you are sure you want to accept, then click "Submit" again.');
                    $icnwarning = 1;
                }
            }

            Localisation::resetSystemLanguage();

            $pin_check_result = $LMS->checkCustomerPin($customerdata['id'], $customerdata['pin']);
            if (is_string($pin_check_result)) {
                $error['pin'] = $pin_check_result;
            }

            if ($customerdata['status'] == CSTATUS_INTERESTED && $LMS->GetCustomerNodesNo($customerdata['id'])) {
                $error['status'] = trans('Interested customers can\'t have computers!');
            }

            $contacts = array();

            $emaileinvoice = false;

            foreach ($CUSTOMERCONTACTTYPES as $properties) {
                $properties['validator']($customerdata, $contacts, $error);
            }

            $customer_invoice_notice_consent_check = ConfigHelper::getConfig('phpui.customer_invoice_notice_consent_check', 'error');
            if ($customer_invoice_notice_consent_check != 'none') {
                if (isset($customerdata['emails']) && $customerdata['emails']) {
                    foreach ($customerdata['emails'] as $val) {
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
                $cutoffstop = intval(2 ** 31 - 1);
            } elseif ($customerdata['cutoffstop'] == '') {
                $cutoffstop = 0;
            } elseif ($cutoffstop = date_to_timestamp($customerdata['cutoffstop'])) {
                $cutoffstop = strtotime('tomorrow', $cutoffstop) - 1;
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
                $SESSION->remove('customer_edit_start', true);

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
                $id = $hook_data['id'] ?? null;

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

                $DB->BeginTrans();
                $DB->Execute('DELETE FROM customercontacts WHERE customerid = ?', array($customerdata['id']));
                foreach ($contacts as $contact) {
                    if ($contact['type'] & CONTACT_BANKACCOUNT) {
                        $contact['contact'] = preg_replace('/[^a-zA-Z0-9]/', '', $contact['contact']);
                    }
                    $DB->Execute(
                        'INSERT INTO customercontacts (customerid, contact, name, type) VALUES (?, ?, ?, ?)',
                        array($customerdata['id'], $contact['contact'], $contact['name'], $contact['type'])
                    );

                    if ($contact['type'] & CONTACT_EMAIL && !empty($contact['properties'])) {
                        $contactid = $DB->GetLastInsertID('customercontacts');
                        foreach ($contact['properties'] as $property) {
                            $DB->Execute(
                                'INSERT INTO customercontactproperties (contactid, name, value)
                                    VALUES (?, ?, ?)',
                                array(
                                    $contactid,
                                    $property['name'],
                                    $property['value']
                                )
                            );
                        }
                    }

                    if ($SYSLOG) {
                        $contactid = $DB->GetLastInsertID('customercontacts');
                        $args = array(
                            SYSLOG::RES_CUSTCONTACT => $contactid,
                            SYSLOG::RES_CUST => $customerdata['id'],
                            'contact' => $contact['contact'],
                            'name' => $contact['name'],
                            'type' => $contact['type'],
                            'properties' => isset($contact['properties']) ? serialize($contact['properties']) : null,
                        );
                        $SYSLOG->AddMessage(SYSLOG::RES_CUSTCONTACT, SYSLOG::OPER_ADD, $args);
                    }
                }
                $DB->CommitTrans();

                $SESSION->remove_history_entry();
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
                $customerinfo['stateid'] = $olddata['stateid'] ?? 0;
                $customerinfo['post_stateid'] = $olddata['post_stateid'] ?? 0;
                $customerinfo['tenwarning'] = empty($tenwarning) ? 0 : 1;
                $customerinfo['tenexistencewarning'] = empty($tenexistencewarning) ? 0 : 1;
                $customerinfo['ssnwarning'] = empty($ssnwarning) ? 0 : 1;
                $customerinfo['ssnexistencewarning'] = empty($ssnexistencewarning) ? 0 : 1;
                $customerinfo['icnwarning'] = empty($icnwarning) ? 0 : 1;
                if ($olddata['icexpires'] === '0') {
                    $olddata['icexpires'] = -1;
                }
            }
        } else {
            $customerinfo = $LMS->GetCustomer($_GET['id']);

            $customerinfo['cutoffstopindefinitely'] = 0;
            if ($customerinfo['cutoffstop']) {
                if ($customerinfo['cutoffstop'] == intval(2 ** 31 - 1)) {
                    $customerinfo['cutoffstop'] = 0;
                    $customerinfo['cutoffstopindefinitely'] = 1;
                } else {
                    $customerinfo['cutoffstop'] = date('Y/m/d', $customerinfo['cutoffstop']);
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

            if ($customerinfo['icexpires'] === '0') {
                $customerinfo['icexpires'] = -1;
            }
        }
        $SMARTY->assign('backurl', $backurl);

        $SESSION->save(
            'customer_edit_start',
            array(
                'id' => $customerinfo['id'],
                'date' => time(),
            ),
            true
        );
    }

    $layout['pagetitle'] = trans('Customer Edit: $a', $customerinfo['customername']);

    $customerid = $customerinfo['id'];

    include(MODULES_DIR.'/customer.inc.php');
}

$LMS->InitXajax();

$hook_data = $LMS->executeHook(
    'customeredit_before_display',
    array(
        'customerinfo' => $customerinfo ?? array(),
        'smarty' => $SMARTY
    )
);
$customerinfo = $hook_data['customerinfo'];

$SMARTY->assign('xajax', $LMS->RunXajax());
$SMARTY->assign($LMS->getCustomerPinRequirements());
$SMARTY->assign('customerinfo', $customerinfo);
$SMARTY->assign('divisions', $LMS->GetDivisions(array('userid' => Auth::GetCurrentUser())));
$SMARTY->assign('recover', ($action == 'recover' ? 1 : 0));
$SMARTY->assign('customeredit_sortable_order', $SESSION->get_persistent_setting('customeredit-sortable-order'));
$SMARTY->display('customer/customeredit.html');
