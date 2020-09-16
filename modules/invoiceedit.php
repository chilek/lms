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

include(MODULES_DIR . DIRECTORY_SEPARATOR . 'invoicexajax.inc.php');
include(MODULES_DIR . DIRECTORY_SEPARATOR . 'invoiceajax.inc.php');

function cleanUpValue($value)
{
    return strlen($value) ? preg_replace(
        array(
            '/(\d{1,3})\s+(\d{3})/',
            '/^(\d+(?:[\.,]\d+)?)(\s*[^\d].*)?$/',
            '/,/',
        ),
        array(
            '$1$2',
            '$1',
            '.',
        ),
        $value
    ) : $value;
}

$taxeslist = $LMS->GetTaxes();
$action = isset($_GET['action']) ? $_GET['action'] : '';

if (isset($_GET['id']) && ($action == 'edit' || $action == 'init')) {
    if (!$LMS->isInvoiceEditable($_GET['id'])) {
        return;
    }

    $invoice = $LMS->GetInvoiceContent($_GET['id']);

    if (!empty($invoice['cancelled'])) {
        return;
    }

    $invoice['proforma'] = isset($_GET['proforma']) ? $action : 0;

    $SESSION->remove('invoicecontents', true);
    $SESSION->remove('invoice', true);
    $SESSION->remove('invoicecustomer', true);
    $SESSION->remove('invoiceediterror', true);

    $invoicecontents = array();
    foreach ($invoice['content'] as $item) {
        $invoicecontents[] = array(
            'tariffid' => $item['tariffid'],
            'name' => $item['description'],
            'prodid' => $item['prodid'],
            'count' => str_replace(',', '.', $item['count']),
            'discount' => str_replace(',', '.', $item['pdiscount']),
            'pdiscount' => str_replace(',', '.', $item['pdiscount']),
            'vdiscount' => str_replace(',', '.', $item['vdiscount']),
            'jm' => str_replace(',', '.', $item['content']),
            'valuenetto' => str_replace(',', '.', $item['basevalue']),
            'valuebrutto' => str_replace(',', '.', $item['value']),
            's_valuenetto' => str_replace(',', '.', $item['totalbase']),
            's_valuebrutto' => str_replace(',', '.', $item['total']),
            'tax' => isset($taxeslist[$item['taxid']]) ? $taxeslist[$item['taxid']]['label'] : '',
            'taxid' => $item['taxid'],
            'taxcategory' => $item['taxcategory'],
        );
    }

    $invoice['oldcdate'] = $invoice['cdate'];
    $invoice['oldsdate'] = $invoice['sdate'];
    $invoice['olddeadline'] = $invoice['deadline'] = $invoice['cdate'] + $invoice['paytime'] * 86400;
    $invoice['oldnumber'] = $invoice['number'];
    $invoice['oldnumberplanid'] = $invoice['numberplanid'];
    $invoice['oldcustomerid'] = $invoice['customerid'];
    $invoice['oldcomment'] = $invoice['comment'];

    $hook_data = array(
        'contents' => $invoicecontents,
        'invoice' => $invoice,
    );
    $hook_data = $LMS->ExecuteHook('invoiceedit_init', $hook_data);
    $invoicecontents = $hook_data['contents'];
    $invoice = $hook_data['invoice'];

    $SESSION->save('invoicecontents', $invoicecontents, true);
    $SESSION->save('invoice', $invoice, true);
    $SESSION->save('invoicecustomer', $invoice['customerid'], true);
    $SESSION->save('invoiceid', $invoice['id'], true);
}

$SESSION->restore('invoicecontents', $contents, true);
$SESSION->restore('invoice', $invoice, true);
$SESSION->restore('invoicecustomer', $customerid, true);
$SESSION->restore('invoiceediterror', $error, true);
$itemdata = r_trim($_POST);

$ntempl = docnumber(array(
    'number' => $invoice['number'],
    'template' => $invoice['template'],
    'cdate' => $invoice['cdate'],
    'customerid' => $invoice['customerid'],
));
if ($invoice['doctype'] == DOC_INVOICE_PRO) {
    $layout['pagetitle'] = trans('Pro Forma Invoice Edit: $a', $ntempl);
} else {
    $layout['pagetitle'] = trans('Invoice Edit: $a', $ntempl);
}

if (isset($_GET['customerid']) && $_GET['customerid'] != '' && $LMS->CustomerExists($_GET['customerid'])) {
    $action = 'setcustomer';
}

function changeContents($contents, $newcontents)
{
    $result = array();

    foreach ($newcontents as $posuid => &$newposition) {
        if (isset($contents[$posuid])) {
            $result[] = $contents[$posuid];
        }
    }
    unset($newposition);

    return $result;
}

switch ($action) {
    case 'additem':
    case 'savepos':
        if ($invoice['closed']) {
            break;
        }

        $error = array();

        $itemdata = r_trim($_POST);
        $contents = changeContents($contents, $itemdata['invoice-contents']);

        if ($action == 'savepos') {
            if (!isset($_GET['posuid']) || !isset($contents[$_GET['posuid']])) {
                die;
            }
            $posuid = $_GET['posuid'];
            $itemdata = $itemdata['invoice-contents'][$posuid];
        }

        unset($itemdata['invoice-contents']);

        $error_index = $action == 'savepos' ? 'invoice-contents[' . $posuid . '][%variable]' : '%variable';

        if (empty($itemdata['name'])) {
            $error[str_replace('%variable', 'name', $error_index)] = trans('Field cannot be empty!');
        }

        if (strlen($itemdata['count']) && !preg_match('/^[0-9]+([\.,][0-9]+)*$/', $itemdata['count'])) {
            $error[str_replace('%variable', 'count', $error_index)] = trans('Invalid format!');
        }

        if (empty($itemdata['valuenetto']) && empty($itemdata['valuebrutto'])) {
            $error[str_replace('%variable', 'valuenetto', $error_index)] = trans('Field cannot be empty!');
            $error[str_replace('%variable', 'valuebrutto', $error_index)] = trans('Field cannot be empty!');
        } else {
            $itemdata['valuenetto'] = cleanUpValue($itemdata['valuenetto']);
            if (strlen($itemdata['valuenetto']) && !preg_match('/^[0-9]+([\.,][0-9]+)*$/', $itemdata['valuenetto'])) {
                $error[str_replace('%variable', 'valuenetto', $error_index)] = trans('Invalid format!');
            }
            $itemdata['valuebrutto'] = cleanUpValue($itemdata['valuebrutto']);
            if (strlen($itemdata['valuebrutto']) && !preg_match('/^[0-9]+([\.,][0-9]+)*$/', $itemdata['valuebrutto'])) {
                $error[str_replace('%variable', 'valuebrutto', $error_index)] = trans('Invalid format!');
            }
        }

        $itemdata['discount'] = (!empty($itemdata['discount']) ? str_replace(',', '.', $itemdata['discount']) : 0);
        $itemdata['pdiscount'] = 0;
        $itemdata['vdiscount'] = 0;
        if (preg_match('/^[0-9]+(\.[0-9]+)*$/', $itemdata['discount'])) {
            $itemdata['pdiscount'] = ($itemdata['discount_type'] == DISCOUNT_PERCENTAGE ? floatval($itemdata['discount']) : 0);
            $itemdata['vdiscount'] = ($itemdata['discount_type'] == DISCOUNT_AMOUNT ? floatval($itemdata['discount']) : 0);
        } else {
            $error[str_replace('%variable', 'discount', $error_index)] =
                trans('Wrong discount value!');
        }
        if ($itemdata['pdiscount'] < 0 || $itemdata['pdiscount'] > 99.9 || $itemdata['vdiscount'] < 0) {
            $error[str_replace('%variable', 'discount', $error_index)] =
                trans('Wrong discount value!');
        }

        if (ConfigHelper::checkConfig('phpui.tax_category_required')
            && empty($itemdata['taxcategory'])) {
            $error[str_replace('%variable', 'taxcategory', $error_index)] =
                trans('Tax category selection is required!');
        }

        $hook_data = array(
            'contents' => $contents,
            'itemdata' => $itemdata,
            'invoice' => $invoice,
        );
        $hook_data = $LMS->ExecuteHook('invoiceedit_savepos_validation', $hook_data);
        if (isset($hook_data['error']) && is_array($hook_data['error'])) {
            $error = array_merge($error, $hook_data['error']);
        }

        if (!empty($error)) {
            $SMARTY->assign('itemdata', $hook_data['itemdata']);
            if (isset($posuid)) {
                $error['posuid'] = $posuid;
            }
            break;
        }

        $itemdata = $hook_data['itemdata'];

        foreach (array('discount', 'pdiscount', 'vdiscount', 'valuenetto', 'valuebrutto') as $key) {
            $itemdata[$key] = f_round($itemdata[$key]);
        }
        $itemdata['count'] = f_round($itemdata['count'], 3);

        if ($itemdata['count'] > 0 && $itemdata['name'] != '') {
            $taxvalue = $taxeslist[$itemdata['taxid']]['value'];
            if ($itemdata['valuenetto'] != 0 && $itemdata['valuebrutto'] == 0) {
                $itemdata['valuenetto'] = f_round(($itemdata['valuenetto'] - $itemdata['valuenetto']
                    * f_round($itemdata['pdiscount']) / 100)
                    - ((100 * $itemdata['vdiscount']) / (100 + $taxvalue)));
                $itemdata['valuebrutto'] = $itemdata['valuenetto'] * ($taxvalue / 100 + 1);
                $itemdata['s_valuebrutto'] = f_round(($itemdata['valuenetto'] * $itemdata['count']) * ($taxvalue / 100 + 1));
            } elseif ($itemdata['valuebrutto'] != 0) {
                $itemdata['valuebrutto'] = f_round(($itemdata['valuebrutto'] - $itemdata['valuebrutto'] * $itemdata['pdiscount'] / 100)
                    - $itemdata['vdiscount']);
                $itemdata['valuenetto'] = round($itemdata['valuebrutto'] / ($taxvalue / 100 + 1), 2);
                $itemdata['s_valuebrutto'] = f_round($itemdata['valuebrutto'] * $itemdata['count']);
            }

            // str_replace here is needed because of bug in some PHP versions (4.3.10)
            $itemdata['s_valuenetto'] = f_round($itemdata['s_valuebrutto'] / ($taxvalue / 100 + 1));
            $itemdata['valuenetto'] = f_round($itemdata['valuenetto']);
            $itemdata['count'] = f_round($itemdata['count'], 3);
            $itemdata['discount'] = f_round($itemdata['discount']);
            $itemdata['pdiscount'] = f_round($itemdata['pdiscount']);
            $itemdata['vdiscount'] = f_round($itemdata['vdiscount']);
            $itemdata['tax'] = $taxeslist[$itemdata['taxid']]['label'];
            if ($action == 'savepos') {
                $contents[$posuid] = $itemdata;
            } else {
                $contents[] = $itemdata;
            }
        }
        break;

    case 'deletepos':
        if ($invoice['closed']) {
            break;
        }

        if (isset($contents[$_GET['posuid']])) {
            unset($contents[$_GET['posuid']]);
        }

        $contents = changeContents($contents, $_POST['invoice-contents']);
        break;

    case 'setcustomer':
        $olddeadline = $invoice['olddeadline'];
        $oldcdate = $invoice['oldcdate'];
        $oldsdate = $invoice['oldsdate'];
        $oldnumber = $invoice['oldnumber'];
        $oldnumberplanid = $invoice['oldnumberplanid'];
        $oldcustomerid = $invoice['oldcustomerid'];
        $oldcomment = $invoice['oldcomment'];
        $closed   = $invoice['closed'];
        $divisionid = $invoice['divisionid'];
        $name = $invoice['name'];
        $address = $invoice['address'];
        $ten = $invoice['ten'];
        $ssn = $invoice['ssn'];
        $zip = $invoice['zip'];
        $city = $invoice['city'];
        $countryid = $invoice['countryid'];
        $recipient_address = $invoice['recipient_address'];

        unset($invoice);
        unset($error);
        $error = null;

        if ($invoice = $_POST['invoice']) {
            foreach ($invoice as $key => $val) {
                $invoice[$key] = $val;
            }
        }

        $invoice['olddeadline'] = $olddeadline;
        $invoice['oldcdate'] = $oldcdate;
        $invoice['oldsdate'] = $oldsdate;
        $invoice['oldnumber'] = $oldnumber;
        $invoice['oldnumberplanid'] = $oldnumberplanid;
        $invoice['oldcustomerid'] = $oldcustomerid;
        $invoice['oldcomment'] = $oldcomment;
        $invoice['divisionid'] = $divisionid;
        $invoice['name'] = $name;
        $invoice['address'] = $address;
        $invoice['ten'] = $ten;
        $invoice['ssn'] = $ssn;
        $invoice['zip'] = $zip;
        $invoice['city'] = $city;
        $invoice['countryid'] = $countryid;
        $invoice['recipient_address'] = $recipient_address;

        $currtime = time();

        if (ConfigHelper::checkPrivilege('invoice_consent_date')) {
            if ($invoice['cdate']) { // && !$invoice['cdatewarning'])
                list ($year, $month, $day) = explode('/', $invoice['cdate']);
                if (checkdate($month, $day, $year)) {
                    $oldday = date('d', $invoice['oldcdate']);
                    $oldmonth = date('m', $invoice['oldcdate']);
                    $oldyear = date('Y', $invoice['oldcdate']);

                    if ($oldday != $day || $oldmonth != $month || $oldyear != $year) {
                        $invoice['cdate'] = mktime(
                            date('G', $currtime),
                            date('i', $currtime),
                            date('s', $currtime),
                            $month,
                            $day,
                            $year
                        );
                    } else { // save hour/min/sec value if date is the same
                        $invoice['cdate'] = $invoice['oldcdate'];
                    }
                } else {
                    $error['cdate'] = trans('Incorrect date format!');
                }
            }
        } else {
            $invoice['cdate'] = $invoice['oldcdate'];
        }

        if (ConfigHelper::checkPrivilege('invoice_sale_date')) {
            if ($invoice['sdate']) {
                list ($syear, $smonth, $sday) = explode('/', $invoice['sdate']);
                if (checkdate($smonth, $sday, $syear)) {
                    $oldsday = date('d', $invoice['oldsdate']);
                    $oldsmonth = date('m', $invoice['oldsdate']);
                    $oldsyear = date('Y', $invoice['oldsdate']);

                    if ($oldsday != $sday || $oldsmonth != $smonth || $oldsyear != $syear) {
                        $invoice['sdate'] = mktime(
                            date('G', $currtime),
                            date('i', $currtime),
                            date('s', $currtime),
                            $smonth,
                            $sday,
                            $syear
                        );
                    } else { // save hour/min/sec value if date is the same
                        $invoice['sdate'] = $invoice['oldsdate'];
                    }
                } else {
                    $error['sdate'] = trans('Incorrect date format!');
                }
            }
        } else {
            $invoice['sdate'] = $invoice['cdate'];
        }

        if ($invoice['deadline']) {
            list ($dyear, $dmonth, $dday) = explode('/', $invoice['deadline']);
            if (checkdate($dmonth, $dday, $dyear)) {
                $olddday = date('d', $invoice['oldddate']);
                $olddmonth = date('m', $invoice['oldddate']);
                $olddyear = date('Y', $invoice['oldddate']);

                if ($olddday != $dday || $olddmonth != $dmonth || $olddyear != $dyear) {
                    $invoice['deadline'] = mktime(date('G', $currtime), date('i', $currtime), date('s', $currtime), $dmonth, $dday, $dyear);
                } else { // save hour/min/sec value if date is the same
                    $invoice['deadline'] = $invoice['olddeadline'];
                }
            } else {
                $error['deadline'] = trans('Incorrect date format!');
            }
        }

        if ($invoice['deadline'] < $invoice['cdate']) {
            $error['deadline'] = trans('Deadline date should be later than consent date!');
        }

        $invoice['customerid'] = $_POST['customerid'];
        $invoice['closed']     = $closed;

        if ($invoice['number']) {
            if (!preg_match('/^[0-9]+$/', $invoice['number'])) {
                $error['number'] = trans('Invoice number must be integer!');
            } elseif (($invoice['oldcdate'] != $invoice['cdate'] || $invoice['oldnumber'] != $invoice['number']
                ||  ($invoice['oldnumber'] == $invoice['number'] && $invoice['oldcustomerid'] != $invoice['customerid'])
                || $invoice['oldnumberplanid'] != $invoice['numberplanid']) && ($docid = $LMS->DocumentExists(array(
                    'number' => $invoice['number'],
                    'doctype' => $invoice['proforma'] === 'edit' ? DOC_INVOICE_PRO : DOC_INVOICE,
                    'planid' => $invoice['numberplanid'],
                    'cdate' => $invoice['cdate'],
                    'customerid' => $invoice['customerid'],
                ))) > 0 && $docid != $invoice['id']) {
                $error['number'] = trans('Invoice number $a already exists!', $invoice['number']);
            }
        }

        if (!$error) {
            if (!$LMS->CustomerExists($invoice['customerid'])) {
                unset($invoice['customerid']);
            }
        }
        break;

    case 'save':
        if (empty($contents) || empty($invoice['customerid']) || !$LMS->CustomerExists($invoice['customerid'])) {
            break;
        }

        $error = array();

        $contents = changeContents($contents, $_POST['invoice-contents']);

        $SESSION->restore('invoiceid', $invoice['id'], true);
        $invoice['type'] = $invoice['doctype'];

        if (!ConfigHelper::checkPrivilege('invoice_consent_date')) {
            $invoice['cdate'] = $invoice['oldcdate'];
        }

        if (!ConfigHelper::checkPrivilege('invoice_sale_date')) {
            $invoice['sdate'] = $invoice['cdate'];
        }

        if (!isset($CURRENCIES[$invoice['currency']])) {
            $error['currency'] = trans('Invalid currency selection!');
        }

        $hook_data = array(
            'contents' => $contents,
            'invoice' => $invoice,
        );
        $hook_data = $LMS->ExecuteHook('invoiceedit_save_validation', $hook_data);
        if (isset($hook_data['error']) && is_array($hook_data['error'])) {
            $error = array_merge($error, $hook_data['error']);
        }

        if (!empty($error)) {
            break;
        }

        // updates customer recipient address stored in document
        $prev_rec_addr = $DB->GetOne('SELECT recipient_address_id FROM documents WHERE id = ?', array($invoice['id']));
        if (empty($prev_rec_addr)) {
            $prev_rec_addr = -1;
        }

        if ($prev_rec_addr != $invoice['recipient_address_id']) {
            if ($prev_rec_addr > 0) {
                $DB->Execute('DELETE FROM addresses WHERE id = ?', array($prev_rec_addr));
            }

            if ($invoice['recipient_address_id'] > 0) {
                $DB->Execute(
                    'UPDATE documents SET recipient_address_id = ? WHERE id = ?',
                    array(
                                $LMS->CopyAddress($invoice['recipient_address_id']),
                                $invoice['id']
                            )
                );
            }
        }

        // updates customer post address stored in document
        $LMS->UpdateDocumentPostAddress($invoice['id'], $invoice['customerid']);

        $currtime = time();
        $cdate = $invoice['cdate'] ? $invoice['cdate'] : $currtime;
        $sdate = $invoice['sdate'] ? $invoice['sdate'] : $currtime;
        $deadline = $invoice['deadline'] ? $invoice['deadline'] : $currtime;
        $comment = $invoice['comment'] ? $invoice['comment'] : null;
        $paytime = round(($deadline - $cdate) / 86400);
        $iid   = $invoice['id'];

        $invoice['currencyvalue'] = $LMS->getCurrencyValue($invoice['currency'], $sdate);
        if (!isset($invoice['currencyvalue'])) {
            die('Fatal error: couldn\'t get quote for ' . $invoice['currency'] . ' currency!<br>');
        }

        $DB->BeginTrans();
        $tables = array('documents', 'cash', 'invoicecontents', 'numberplans', 'divisions', 'vdivisions',
            'customerview', 'customercontacts', 'netdevices', 'nodes',
            'logtransactions', 'logmessages', 'logmessagekeys', 'logmessagedata');
        if (ConfigHelper::getConfig('database.type') == 'postgres') {
            $tables = array_merge($tables, array('customers', 'customer_addresses'));
        } else {
            $tables = array_merge($tables, array('customers cv', 'customer_addresses ca'));
        }
        $DB->LockTables($tables);

        $use_current_customer_data = isset($invoice['use_current_customer_data']) || $invoice['customerid'] != $customerid;
        if ($use_current_customer_data) {
            $customer = $LMS->GetCustomer($invoice['customerid'], true);
        }

        $division = $LMS->GetDivision($use_current_customer_data ? $customer['divisionid'] : $invoice['divisionid']);

        if (!$invoice['number']) {
            $invoice['number'] = $LMS->GetNewDocumentNumber(array(
                'doctype' => $invoice['proforma'] === 'edit' ? DOC_INVOICE_PRO : DOC_INVOICE,
                'planid' => $invoice['numberplanid'],
                'cdate' => $invoice['cdate'],
                'customerid' => $invoice['customerid'],
            ));
        } else {
            if (!preg_match('/^[0-9]+$/', $invoice['number'])) {
                $error['number'] = trans('Invoice number must be integer!');
            } elseif (($invoice['cdate'] != $invoice['oldcdate'] || $invoice['number'] != $invoice['oldnumber']
                ||  ($invoice['oldnumber'] == $invoice['number'] && $invoice['oldcustomerid'] != $invoice['customerid'])
                || $invoice['numberplanid'] != $invoice['oldnumberplanid']) && ($docid = $LMS->DocumentExists(array(
                    'number' => $invoice['number'],
                    'doctype' => $invoice['proforma'] === 'edit' ? DOC_INVOICE_PRO : DOC_INVOICE,
                    'planid' => $invoice['numberplanid'],
                    'cdate' => $invoice['cdate'],
                    'customerid' => $invoice['customerid'],
                ))) > 0 && $docid != $iid) {
                $error['number'] = trans('Invoice number $a already exists!', $invoice['number']);
            }

            if ($error) {
                $invoice['number'] = $LMS->GetNewDocumentNumber(array(
                    'doctype' => $invoice['proforma'] === 'edit' ? DOC_INVOICE_PRO : DOC_INVOICE,
                    'planid' => $invoice['numberplanid'],
                    'cdate' => $invoice['cdate'],
                    'customerid' => $invoice['customerid'],
                ));
                $error = null;
            }
        }

        $hook_data = array(
            'contents' => $contents,
            'invoice' => $invoice,
        );
        $hook_data = $LMS->ExecuteHook('invoiceedit_save_before_submit', $hook_data);
        $contents = $hook_data['contents'];
        $invoice = $hook_data['invoice'];

        $args = array(
            'cdate' => $cdate,
            'sdate' => $sdate,
            'paytime' => $paytime,
            'paytype' => $invoice['paytype'],
            'splitpayment' => empty($invoice['splitpayment']) ? 0 : 1,
            'flags' => empty($invoice['flags'][DOC_FLAG_RECEIPT]) ? 0 : DOC_FLAG_RECEIPT,
            SYSLOG::RES_CUST => $invoice['customerid'],
            'name' => $use_current_customer_data ? $customer['customername'] : $invoice['name'],
            'address' => $use_current_customer_data ? (($customer['postoffice'] && $customer['postoffice'] != $customer['city'] && $customer['street']
                ? $customer['city'] . ', ' : '') . $customer['address']) : $invoice['address'],
            'ten' => $use_current_customer_data ? $customer['ten'] : $invoice['ten'],
            'ssn' => $use_current_customer_data ? $customer['ssn'] : $invoice['ssn'],
            'zip' => $use_current_customer_data ? $customer['zip'] : $invoice['zip'],
            'city' => $use_current_customer_data ? ($customer['postoffice'] ? $customer['postoffice'] : $customer['city'])
                : $invoice['city'],
            SYSLOG::RES_COUNTRY => $use_current_customer_data ? (empty($customer['countryid']) ? null : $customer['countryid'])
                : (empty($invoice['countryid']) ? null : $invoice['countryid']),
            SYSLOG::RES_DIV => $use_current_customer_data ? (empty($customer['divisionid']) ? null : $customer['divisionid'])
                : (empty($invoice['divisionid']) ? null : $invoice['divisionid']),
            'div_name' => ($division['name'] ? $division['name'] : ''),
            'div_shortname' => ($division['shortname'] ? $division['shortname'] : ''),
            'div_address' => ($division['address'] ? $division['address'] : ''),
            'div_city' => ($division['city'] ? $division['city'] : ''),
            'div_zip' => ($division['zip'] ? $division['zip'] : ''),
            'div_' . SYSLOG::getResourceKey(SYSLOG::RES_COUNTRY) => ($division['countryid'] ? $division['countryid'] : null),
            'div_ten'=> ($division['ten'] ? $division['ten'] : ''),
            'div_regon' => ($division['regon'] ? $division['regon'] : ''),
            'div_bank' => $division['bank'] ?: null,
            'div_account' => ($division['account'] ? $division['account'] : ''),
            'div_inv_header' => ($division['inv_header'] ? $division['inv_header'] : ''),
            'div_inv_footer' => ($division['inv_footer'] ? $division['inv_footer'] : ''),
            'div_inv_author' => ($division['inv_author'] ? $division['inv_author'] : ''),
            'div_inv_cplace' => ($division['inv_cplace'] ? $division['inv_cplace'] : ''),
            'comment' => ($invoice['comment'] ? $invoice['comment'] : null),
            'currency' => $invoice['currency'],
            'currencyvalue' => $invoice['currencyvalue'],
            'memo' => $use_current_customer_data ? (empty($customer['documentmemo']) ? null : $customer['documentmemo']) : $invoice['memo'],
        );

        $args['type'] = $invoice['proforma'] === 'edit' ? DOC_INVOICE_PRO : DOC_INVOICE;
        $args['number'] = $invoice['number'];
        if ($invoice['numberplanid']) {
            $args['fullnumber'] = docnumber(array(
                'number' => $invoice['number'],
                'template' => $DB->GetOne('SELECT template FROM numberplans WHERE id = ?', array($invoice['numberplanid'])),
                'cdate' => $invoice['cdate'],
                'customerid' => $invoice['customerid'],
            ));
        } else {
            $args['fullnumber'] = null;
        }
        $args[SYSLOG::RES_NUMPLAN] = $invoice['numberplanid'] ?: null;
        //$args['recipient_address_id'] = $invoice
        $args[SYSLOG::RES_DOC] = $iid;
        $DB->Execute('UPDATE documents SET cdate = ?, sdate = ?, paytime = ?, paytype = ?, splitpayment = ?, flags = ?, customerid = ?,
				name = ?, address = ?, ten = ?, ssn = ?, zip = ?, city = ?, countryid = ?, divisionid = ?,
				div_name = ?, div_shortname = ?, div_address = ?, div_city = ?, div_zip = ?, div_countryid = ?,
				div_ten = ?, div_regon = ?, div_bank = ?, div_account = ?, div_inv_header = ?, div_inv_footer = ?,
				div_inv_author = ?, div_inv_cplace = ?, comment = ?, currency = ?, currencyvalue = ?, memo = ?,
				type = ?, number = ?, fullnumber = ?, numberplanid = ?
				WHERE id = ?', array_values($args));
        if ($SYSLOG) {
            $SYSLOG->AddMessage(
                SYSLOG::RES_DOC,
                SYSLOG::OPER_UPDATE,
                $args,
                array('div_' . SYSLOG::getResourceKey(SYSLOG::RES_COUNTRY))
            );
        }

        if (!$invoice['closed']) {
            if ($SYSLOG) {
                if ($invoice['doctype'] == DOC_INVOICE) {
                    $cashids = $DB->GetCol('SELECT id FROM cash WHERE docid = ?', array($iid));
                    foreach ($cashids as $cashid) {
                        $args = array(
                            SYSLOG::RES_CASH => $cashid,
                            SYSLOG::RES_DOC => $iid,
                            SYSLOG::RES_CUST => $customer['id'],
                        );
                        $SYSLOG->AddMessage(SYSLOG::RES_CASH, SYSLOG::OPER_DELETE, $args);
                    }
                }
                $itemids = $DB->GetCol('SELECT itemid FROM invoicecontents WHERE docid = ?', array($iid));
                foreach ($itemids as $itemid) {
                    $args = array(
                        SYSLOG::RES_DOC => $iid,
                        SYSLOG::RES_CUST => $customer['id'],
                        'itemid' => $itemid,
                    );
                    $SYSLOG->AddMessage(SYSLOG::RES_INVOICECONT, SYSLOG::OPER_DELETE, $args);
                }
            }
            $DB->Execute('DELETE FROM invoicecontents WHERE docid = ?', array($iid));
            $DB->Execute('DELETE FROM cash WHERE docid = ?', array($iid));

            $itemid=0;
            foreach ($contents as $idx => $item) {
                $itemid++;

                $args = array(
                    SYSLOG::RES_DOC => $iid,
                    'itemid' => $itemid,
                    'value' => str_replace(',', '.', $item['valuebrutto']),
                    SYSLOG::RES_TAX => $item['taxid'],
                    'taxcategory' => $item['taxcategory'],
                    'prodid' => $item['prodid'],
                    'content' => $item['jm'],
                    'count' => str_replace(',', '.', $item['count']),
                    'pdiscount' => str_replace(',', '.', $item['pdiscount']),
                    'vdiscount' => str_replace(',', '.', $item['vdiscount']),
                    'name' => $item['name'],
                    SYSLOG::RES_TARIFF => empty($item['tariffid']) ? null : $item['tariffid'],
                );
                $DB->Execute('INSERT INTO invoicecontents (docid, itemid, value,
					taxid, taxcategory, prodid, content, count, pdiscount, vdiscount, description, tariffid)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
                if ($SYSLOG) {
                    $args[SYSLOG::RES_CUST] = $customer['id'];
                    $SYSLOG->AddMessage(SYSLOG::RES_INVOICECONT, SYSLOG::OPER_ADD, $args);
                }

                if ($invoice['doctype'] == DOC_INVOICE || ConfigHelper::checkConfig('phpui.proforma_invoice_generates_commitment')) {
                    $LMS->AddBalance(array(
                        'time' => $cdate,
                        'value' => $item['valuebrutto']*$item['count']*-1,
                        'currency' => $invoice['currency'],
                        'currencyvalue' => $invoice['currencyvalue'],
                        'taxid' => $item['taxid'],
                        'customerid' => $invoice['customerid'],
                        'comment' => $item['name'],
                        'docid' => $iid,
                        'itemid' => $itemid
                        ));
                }
            }
        } elseif ($invoice['doctype'] == DOC_INVOICE || ConfigHelper::checkConfig('phpui.proforma_invoice_generates_commitment')) {
            if ($SYSLOG) {
                $cashids = $DB->GetCol('SELECT id FROM cash WHERE docid = ?', array($iid));
                foreach ($cashids as $cashid) {
                    $args = array(
                        SYSLOG::RES_CASH => $cashid,
                        SYSLOG::RES_DOC => $iid,
                        SYSLOG::RES_CUST => $customer['id'],
                    );
                    $SYSLOG->AddMessage(SYSLOG::RES_CASH, SYSLOG::OPER_UPDATE, $args);
                }
            }
            $DB->Execute(
                'UPDATE cash SET customerid = ? WHERE docid = ?',
                array($invoice['customerid'], $iid)
            );
        }

        $hook_data = array(
            'contents' => $contents,
            'invoice' => $invoice,
        );
        $hook_data = $LMS->ExecuteHook('invoiceedit_save_after_submit', $hook_data);

        $DB->UnLockTables();
        $DB->CommitTrans();

        if (isset($_GET['print'])) {
            $which = isset($_GET['which']) ? $_GET['which'] : 0;

            $SESSION->save('invoiceprint', array(
                'invoice' => $invoice['id'],
                'which' => $which
            ), true);
        }

        $SESSION->redirect('?m=invoicelist' . (isset($invoice['proforma']) && $invoice['proforma'] === 'edit' ? '&proforma=1' : ''));
        break;
}

$SESSION->save('invoice', $invoice, true);
$SESSION->save('invoicecontents', $contents, true);
$SESSION->save('invoicecustomer', $customerid, true);
$SESSION->save('invoiceediterror', $error, true);

if ($action && !$error) {
    // redirect needed because we don't want to destroy contents of invoice in order of page refresh
    $SESSION->redirect('?m=invoiceedit');
}

if (!ConfigHelper::checkConfig('phpui.big_networks')) {
    $SMARTY->assign('customers', $LMS->GetCustomerNames());
}

$SMARTY->assign('error', $error);
if (isset($invoice['customerid']) && !empty($invoice['customerid'])) {
    $customer = $LMS->GetCustomer($invoice['customerid'], true);
} else {
    $customer = null;
}
$SMARTY->assign('tariffs', $LMS->GetTariffs());
$SMARTY->assign('taxeslist', $taxeslist);

$args = array(
    'doctype' => isset($invoice['proforma']) && $invoice['proforma'] === 'edit' ? DOC_INVOICE_PRO : DOC_INVOICE,
    'cdate' => date('Y/m', $invoice['cdate']),
);
if (isset($invoice['customerid']) && !empty($invoice['customerid'])) {
    $args['customerid'] = $invoice['customerid'];
    $args['division'] = $DB->GetOne('SELECT divisionid FROM customers WHERE id = ?', array($invoice['customerid']));
}
$SMARTY->assign('numberplanlist', $LMS->GetNumberPlans($args));

$hook_data = array(
    'customer' => $customer,
    'contents' => $contents,
    'invoice' => $invoice,
);
$hook_data = $LMS->ExecuteHook('invoiceedit_before_display', $hook_data);
$customer = $hook_data['customer'];
$contents = $hook_data['contents'];
$invoice = $hook_data['invoice'];

if (isset($customer)) {
    $addresses = $LMS->getCustomerAddresses($customer['id']);
    if (isset($invoice['recipient_address'])) {
        $addresses = array_replace(
            array($invoice['recipient_address']['address_id'] => $invoice['recipient_address']),
            $addresses
        );
        $invoice['recipient_address'] = base64_encode(json_encode($invoice['recipient_address']));
    }
    $SMARTY->assign('addresses', $addresses);
}

$SMARTY->assign('customer', $customer);
$SMARTY->assign('contents', $contents);
$SMARTY->assign('invoice', $invoice);

$total_value = 0;
if (!empty($contents)) {
    foreach ($contents as $item) {
        $total_value += $item['s_valuebrutto'];
    }
}

$SMARTY->assign('is_split_payment_suggested', $LMS->isSplitPaymentSuggested(
    isset($customer) ? $customer['id'] : null,
    date('Y/m/d', $invoice['cdate']),
    $total_value
));

$SMARTY->display('invoice/invoiceedit.html');
