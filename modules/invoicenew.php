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

include(MODULES_DIR . DIRECTORY_SEPARATOR . 'invoiceajax.inc.php');

// Invoiceless liabilities: Zobowiazania/obciazenia na ktore nie zostala wystawiona faktura
function GetCustomerCovenants($customerid, $currency)
{
    global $DB;

    if (!$customerid) {
        return null;
    }

    return $DB->GetAll('SELECT c.time, c.value*-1 AS value, c.currency, c.comment, c.taxid, 
			taxes.label AS tax, c.id AS cashid,
			ROUND(c.value / (taxes.value/100+1), 2)*-1 AS net,
            c.servicetype
			FROM cash c
			LEFT JOIN taxes ON (c.taxid = taxes.id)
			WHERE c.customerid = ? AND c.docid IS NULL AND c.currency = ? AND c.value < 0
			ORDER BY time', array($customerid, $currency));
}

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

$SESSION->restore('invoicecontents', $contents, true);
$SESSION->restore('invoicecustomer', $customer, true);
$SESSION->restore('invoice', $invoice, true);
$SESSION->restore('invoicenewerror', $error, true);

$itemdata = r_trim($_POST);

$action = $_GET['action'] ?? null;

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

$value_regexp = ConfigHelper::checkConfig('invoices.allow_negative_values') ? '/^[-]?[0-9]+([\.,][0-9]+)*$/' : '/^[0-9]+([\.,][0-9]+)*$/';

switch ($action) {
    case 'init':
        unset($invoice);
        unset($customer);
        unset($error);
        $contents = null;

        if (isset($_GET['id'])) {
            $invoice = $LMS->GetInvoiceContent($_GET['id']);

            $contents = array();
            foreach ($invoice['content'] as $item) {
                $contents[] = array(
                    'tariffid' => $item['tariffid'],
                    'tariff' => !empty($item['tariffid']) ? $LMS->GetTariff($item['tariffid']) : array(),
                    'name' => $item['description'],
                    'prodid' => $item['prodid'],
                    'count' => str_replace(',', '.', $item['count']),
                    'discount' => str_replace(',', '.', $item['pdiscount']),
                    'pdiscount' => str_replace(',', '.', $item['pdiscount']),
                    'vdiscount' => str_replace(',', '.', $item['vdiscount']),
                    'jm' => str_replace(',', '.', $item['content']),
                    'valuenetto' => str_replace(',', '.', $item['netprice']),
                    'valuebrutto' => str_replace(',', '.', $item['grossprice']),
                    's_valuenetto' => str_replace(',', '.', $item['netvalue']),
                    's_valuebrutto' => str_replace(',', '.', $item['grossvalue']),
                    'tax' => isset($taxeslist[$item['taxid']]) ? $taxeslist[$item['taxid']]['label'] : '',
                    'taxid' => $item['taxid'],
                    'taxcategory' => $item['taxcategory'],
                );
            }

            $customer = $LMS->GetCustomer($invoice['customerid']);
            if (!isset($_GET['clone'])) {
                $invoice['proformaid'] = $_GET['id'];
                $invoice['proformanumber'] = docnumber(array(
                    'doctype' => DOC_INVOICE_PRO,
                    'cdate' => $invoice['cdate'],
                    'template' => $invoice['template'],
                    'customerid' => $invoice['customerid'],
                ));
                $invoice['preserve-proforma'] = ConfigHelper::checkConfig('phpui.default_preserve_proforma_invoice');
            }
        } else {
            if (!empty($_GET['customerid']) && $LMS->CustomerExists($_GET['customerid'])) {
                $customer = $LMS->GetCustomer($_GET['customerid'], true);
                $invoice['customerid'] = $_GET['customerid'];
            }
            $invoice['currency'] = Localisation::getDefaultCurrency();
            $invoice['netflag'] = ConfigHelper::checkConfig('invoices.default_net_account');
        }
        $invoice['number'] = '';

        if (ConfigHelper::checkConfig('invoices.force_telecom_service_flag')) {
            $invoice['flags'][DOC_FLAG_TELECOM_SERVICE] = time() < mktime(0, 0, 0, 7, 1, 2021) ? 1 : 0;
        }

        // get default invoice's numberplanid and next number
        $currtime = time();
        $invoice['cdate'] = $currtime;
        $invoice['sdate'] = $currtime;
        $invoice['copy-cdate'] = 1;

        $invoice['proforma'] = isset($_GET['proforma']) ? 1 : 0;

        if (isset($_GET['id'])) {
            $invoice['deadline'] = $invoice['cdate'] + $invoice['paytime'] * 86400;
        } else {
            if (isset($customer)) {
                if ($customer['paytime'] != -1) {
                    $paytime = $customer['paytime'];
                } elseif (($paytime = $DB->GetOne('SELECT inv_paytime FROM divisions
                     WHERE id = ?', array($customer['divisionid']))) === null) {
                    $paytime = ConfigHelper::getConfig('invoices.paytime');
                }
            } else {
                $paytime = ConfigHelper::getConfig('invoices.paytime');
            }
            $invoice['deadline'] = $currtime + $paytime * 86400;
        }

        if (!isset($_GET['clone'])) {
            $invoice['numberplanid'] = $LMS->getDefaultNumberPlanID(
                $invoice['proforma'] ? DOC_INVOICE_PRO : DOC_INVOICE,
                empty($customer) ? null : $customer['divisionid']
            );
        }

        $hook_data = array(
            'invoice' => $invoice,
            'contents' => $contents,
        );
        $hook_data = $LMS->ExecuteHook('invoicenew_init', $hook_data);
        $invoice = $hook_data['invoice'];
        $contents = $hook_data['contents'];

        break;

    case 'additem':
    case 'savepos':
        $error = array();

        $itemdata = r_trim($_POST);
        if (isset($itemdata['invoice-contents'])) {
            $contents = changeContents($contents, $itemdata['invoice-contents']);
        }
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
            if (strlen($itemdata['valuenetto']) && !preg_match($value_regexp, $itemdata['valuenetto'])) {
                $error[str_replace('%variable', 'valuenetto', $error_index)] = trans('Invalid format!');
            }
            $itemdata['valuebrutto'] = cleanUpValue($itemdata['valuebrutto']);
            if (strlen($itemdata['valuebrutto']) && !preg_match($value_regexp, $itemdata['valuebrutto'])) {
                $error[str_replace('%variable', 'valuebrutto', $error_index)] = trans('Invalid format!');
            }
        }

        $itemdata['discount'] = str_replace(',', '.', $itemdata['discount']);
        $itemdata['pdiscount'] = 0;
        $itemdata['vdiscount'] = 0;
        if (preg_match('/^[0-9]+(\.[0-9]+)*$/', $itemdata['discount'])) {
            $itemdata['pdiscount'] = ($itemdata['discount_type'] == DISCOUNT_PERCENTAGE ? floatval($itemdata['discount']) : 0);
            $itemdata['vdiscount'] = ($itemdata['discount_type'] == DISCOUNT_AMOUNT ? floatval($itemdata['discount']) : 0);
        } elseif (!empty($itemdata['discount'])) {
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

        foreach (array('discount', 'pdiscount', 'vdiscount', 'valuenetto', 'valuebrutto', 'count') as $key) {
            $itemdata[$key] = f_round($itemdata[$key], 3);
        }

        if ($itemdata['count'] > 0 && $itemdata['name'] != '') {
            $taxvalue = isset($itemdata['taxid']) ? $taxeslist[$itemdata['taxid']]['value'] : 0;
            $itemdata['count'] = f_round($itemdata['count'], 3);

            if ($invoice['netflag']) {
                $itemdata['valuenetto'] = f_round(($itemdata['valuenetto'] - $itemdata['valuenetto'] * f_round($itemdata['pdiscount']) / 100)
                    - $itemdata['vdiscount'], 3);
                $itemdata['s_valuenetto'] = f_round($itemdata['valuenetto'] * $itemdata['count']);
                $itemdata['tax_from_s_valuenetto'] = f_round($itemdata['s_valuenetto'] * ($taxvalue / 100));
                $itemdata['s_valuebrutto'] = f_round($itemdata['s_valuenetto'] + $itemdata['tax_from_s_valuenetto']);
                $itemdata['valuebrutto'] = f_round($itemdata['valuenetto'] * ($taxvalue / 100 + 1), 3);
            } else {
                $itemdata['valuebrutto'] = f_round(($itemdata['valuebrutto'] - $itemdata['valuebrutto'] * f_round($itemdata['pdiscount']) / 100)
                    - $itemdata['vdiscount'], 3);
                $itemdata['s_valuebrutto'] = f_round($itemdata['valuebrutto'] * $itemdata['count']);
                $itemdata['tax_from_s_valuebrutto'] = f_round(($itemdata['s_valuebrutto'] * $taxvalue)
                    / (100 + $taxvalue));
                $itemdata['s_valuenetto'] = f_round($itemdata['s_valuebrutto'] - $itemdata['tax_from_s_valuebrutto']);
                $itemdata['valuenetto'] = f_round($itemdata['valuebrutto'] / ($taxvalue / 100 + 1), 3);
            }

            $itemdata['tax'] = isset($itemdata['taxid']) ? $taxeslist[$itemdata['taxid']]['label'] : '';
        }

        if ($itemdata['tariffid'] > 0) {
            $itemdata['tariff'] = $LMS->GetTariff($itemdata['tariffid']);
        }

        $hook_data = array(
            'customer' => $customer,
            'contents' => $contents,
            'itemdata' => $itemdata,
            'invoice' => $invoice,
        );
        $hook_data = $LMS->ExecuteHook('invoicenew_savepos_validation', $hook_data);
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

        if ($itemdata['count'] > 0 && $itemdata['name'] != '') {
            if ($action == 'savepos') {
                $contents[$posuid] = $itemdata;
            } else {
                $contents[] = $itemdata;
            }
        }
        break;

    case 'additemlist':
        if ($marks = $_POST['marks']) {
            foreach ($marks as $id) {
                $cash = $DB->GetRow('SELECT value, comment, taxid FROM cash WHERE id = ?', array($id));

                $itemdata['cashid'] = $id;
                $itemdata['name'] = $cash['comment'];
                $itemdata['taxid'] = $cash['taxid'];
                $itemdata['servicetype'] = empty($_POST['l_servicetype'][$id]) ? null : $_POST['l_servicetype'][$id];
                $itemdata['taxcategory'] = $_POST['l_taxcategory'][$id];
                $itemdata['tax'] = isset($taxeslist[$itemdata['taxid']]) ? $taxeslist[$itemdata['taxid']]['label'] : '';
                $itemdata['discount'] = 0;
                $itemdata['pdiscount'] = 0;
                $itemdata['vdiscount'] = 0;
                $itemdata['count'] = f_round($_POST['l_count'][$id], 3);
                $itemdata['valuebrutto'] = f_round((-$cash['value'])/$itemdata['count']);
                $itemdata['s_valuebrutto'] = f_round(-$cash['value']);
                $itemdata['tax_from_s_valuebrutto'] = f_round(($itemdata['s_valuebrutto'] * $taxeslist[$itemdata['taxid']]['value']) / (100 + $taxeslist[$itemdata['taxid']]['value']));
                $itemdata['s_valuenetto'] = f_round($itemdata['s_valuebrutto'] - $itemdata['tax_from_s_valuebrutto']);
                $itemdata['valuenetto'] = f_round($itemdata['valuebrutto'] / ($taxeslist[$itemdata['taxid']]['value'] / 100 + 1));
                $itemdata['prodid'] = $_POST['l_prodid'][$id];
                $itemdata['jm'] = $_POST['l_jm'][$id];
                $itemdata['tariffid'] = 0;
                $contents[] = $itemdata;
            }
        }
        break;

    case 'deletepos':
        if (isset($contents[$_GET['posuid']])) {
            unset($contents[$_GET['posuid']]);
        }

        $contents = changeContents($contents, $_POST['invoice-contents']);
        break;

    case 'setcustomer':
        $customer_paytime = isset($customer) ? $customer['paytime'] : -1;

        unset($invoice);
        unset($customer);
        unset($error);

        if ($invoice = $_POST['invoice']) {
            foreach ($invoice as $key => $val) {
                $invoice[$key] = $val;
            }
        }

        $invoice['customerid'] = $_POST['customerid'];

        $currtime = time();

        if (ConfigHelper::checkPrivilege('invoice_consent_date')) {
            if ($invoice['cdate']) {
                [$year, $month, $day] = explode('/', $invoice['cdate']);
                if (checkdate($month, $day, $year)) {
                    $invoice['cdate'] = mktime(
                        date('G', $currtime),
                        date('i', $currtime),
                        date('s', $currtime),
                        $month,
                        $day,
                        $year
                    );
                    $currmonth = $month;
                } else {
                    $error['cdate'] = trans('Incorrect date format!');
                    $invoice['cdate'] = $currtime;
                    break;
                }
            }
        } else {
            $invoice['cdate'] = $currtime;
        }

        if (ConfigHelper::checkPrivilege('invoice_consent_date') && $invoice['cdate'] && !isset($warnings['invoice-cdate-'])) {
            if (empty($invoice['numberplanid'])) {
                $maxdate = $DB->GetOne(
                    'SELECT MAX(cdate) FROM documents WHERE type = ? AND numberplanid IS NULL',
                    array($invoice['proforma'] ? DOC_INVOICE_PRO : DOC_INVOICE)
                );
            } else {
                $maxdate = $DB->GetOne(
                    'SELECT MAX(cdate) FROM documents WHERE type = ? AND numberplanid = ?',
                    array($invoice['proforma'] ? DOC_INVOICE_PRO : DOC_INVOICE, $invoice['numberplanid'])
                );
            }

            if ($invoice['cdate'] < $maxdate) {
                $warning['invoice[cdate]'] = trans(
                    'Last date of invoice settlement is $a. If sure, you want to write invoice with date of $b, then click "Submit" again.',
                    date('Y/m/d H:i', $maxdate),
                    date('Y/m/d H:i', $invoice['cdate'])
                );
            }
        } elseif (!$invoice['cdate']) {
            $invoice['cdate'] = $currtime;
        }

        if (ConfigHelper::checkPrivilege('invoice_sale_date')) {
            if ($invoice['sdate']) {
                [$syear, $smonth, $sday] = explode('/', $invoice['sdate']);
                if (checkdate($smonth, $sday, $syear)) {
                    $invoice['sdate'] = mktime(date('G', $currtime), date('i', $currtime), date('s', $currtime), $smonth, $sday, $syear);
                    $scurrmonth = $smonth;
                } else {
                    $error['sdate'] = trans('Incorrect date format!');
                    $invoice['sdate'] = $currtime;
                    break;
                }
            } else {
                $invoice['sdate'] = $currtime;
            }
        } else {
            $invoice['sdate'] = $invoice['cdate'];
        }

        $cid = isset($_GET['customerid']) && $_GET['customerid'] != '' ? intval($_GET['customerid']) : intval($_POST['customerid']);

        if ($LMS->CustomerExists($cid)) {
            $customer = $LMS->GetCustomer($cid, true);
        }

        if ($invoice['deadline']) {
            [$dyear, $dmonth, $dday] = explode('/', $invoice['deadline']);
            if (checkdate($dmonth, $dday, $dyear)) {
                $invoice['deadline'] = mktime(date('G', $currtime), date('i', $currtime), date('s', $currtime), $dmonth, $dday, $dyear);
                $dcurrmonth = $dmonth;
            } else {
                $error['deadline'] = trans('Incorrect date format!');
                $invoice['deadline'] = $currtime;
                break;
            }
        } else {
            if ($customer_paytime != -1) {
                $paytime = $customer_paytime;
            } elseif (!empty($customer) && ($paytime = $DB->GetOne('SELECT inv_paytime FROM divisions
				WHERE id = ?', array($customer['divisionid']))) === null) {
                $paytime = ConfigHelper::getConfig('invoices.paytime');
            }
            $invoice['deadline'] = $invoice['cdate'] + $paytime * 86400;
        }

        if ($invoice['deadline'] < $invoice['cdate']) {
            $error['deadline'] = trans('Deadline date should be later than consent date!');
        }

        if ($invoice['number']) {
            if (!preg_match('/^[0-9]+$/', $invoice['number'])) {
                $error['number'] = trans('Invoice number must be integer!');
            } elseif ($LMS->DocumentExists(array(
                    'number' => $invoice['number'],
                    'doctype' => $invoice['proforma'] ? DOC_INVOICE_PRO : DOC_INVOICE,
                    'planid' => $invoice['numberplanid'],
                    'cdate' => $invoice['cdate'],
                    'customerid' => $cid
                ))) {
                $error['number'] = trans('Invoice number $a already exists!', $invoice['number']);
            }
        }

        if (!isset($CURRENCIES[$invoice['currency']])) {
            $error['currency'] = trans('Invalid currency selection!');
        }

        if (empty($error)) {
            // finally check if selected customer can use selected numberplan
            $args = array(
                'doctype' => $invoice['proforma'] ? DOC_INVOICE_PRO : DOC_INVOICE,
                'cdate' => $invoice['cdate'],
                'customerid' => $customer['id'],
                'division' => $customer['divisionid'],
                'customertype' => $customer['type'],
                'next' => false,
            );
            $numberplans = $LMS->GetNumberPlans($args);

            if ($invoice['numberplanid'] && isset($customer)
                && !isset($numberplans[$invoice['numberplanid']])) {
                $error['number'] = trans('Selected numbering plan doesn\'t match customer\'s division!');
                unset($customer);
            }

            if ($numberplans && count($numberplans) && empty($invoice['numberplanid'])) {
                $error['numberplanid'] = trans('Select numbering plan');
            }
        }
        break;

    case 'save':
        if (empty($contents) || empty($customer)) {
            break;
        }

        $error = array();

        $contents = changeContents($contents, $_POST['invoice-contents']);

        if ($invoice['deadline']) {
            $deadline = intval($invoice['deadline']);
            $cdate = intval($invoice['cdate']);
            if ($deadline < $cdate) {
                break;
            }
            $invoice['paytime'] = round(($deadline - $cdate) / 86400);
        } elseif ($customer['paytime'] != -1) {
            $invoice['paytime'] = $customer['paytime'];
        } elseif (($paytime = $DB->GetOne('SELECT inv_paytime FROM divisions 
			WHERE id = ?', array($customer['divisionid']))) !== null) {
            $invoice['paytime'] = $paytime;
        } else {
            $invoice['paytime'] = ConfigHelper::getConfig('invoices.paytime');
        }

        // set paytype
        if (empty($invoice['paytype'])) {
            if ($customer['paytype']) {
                $invoice['paytype'] = $customer['paytype'];
            } elseif ($paytype = $DB->GetOne('SELECT inv_paytype FROM divisions 
				WHERE id = ?', array($customer['divisionid']))) {
                $invoice['paytype'] = $paytype;
            } else if (($paytype = intval(ConfigHelper::getConfig('invoices.paytype'))) && isset($PAYTYPES[$paytype])) {
                $invoice['paytype'] = $paytype;
            } else {
                $error['paytype'] = trans('Default payment type not defined!');
            }
        }

        if (!ConfigHelper::checkPrivilege('invoice_consent_date')) {
            $invoice['cdate'] = time();
        }

        if (!ConfigHelper::checkPrivilege('invoice_sale_date')) {
            $invoice['sdate'] = $invoice['cdate'];
        }

        if (!isset($CURRENCIES[$invoice['currency']])) {
            $error['currency'] = trans('Invalid currency selection!');
        }

        if (!empty($invoice['numberplanid']) && !$LMS->checkNumberPlanAccess($invoice['numberplanid'])) {
            $error['numberplanid'] = trans('Permission denied!');
        }

        $args = array(
            'doctype' => $invoice['proforma'] ? DOC_INVOICE_PRO : DOC_INVOICE,
            'cdate' => $invoice['cdate'],
            'customerid' => $customer['id'],
            'division' => $customer['divisionid'],
            'customertype' => $customer['type'],
            'next' => false,
        );
        $numberplans = $LMS->GetNumberPlans($args);

        if ($numberplans && count($numberplans) && empty($invoice['numberplanid'])) {
            $error['numberplanid'] = trans('Select numbering plan');
        }

        $hook_data = array(
            'customer' => $customer,
            'contents' => $contents,
            'invoice' => $invoice,
        );
        $hook_data = $LMS->ExecuteHook('invoicenew_save_validation', $hook_data);
        if (isset($hook_data['error']) && is_array($hook_data['error'])) {
            $error = array_merge($error, $hook_data['error']);
        }

        if (!empty($error)) {
            break;
        }

        $invoice['currencyvalue'] = $LMS->getCurrencyValue(
            $invoice['currency'],
            strtotime('yesterday', min($invoice['sdate'], $invoice['cdate'], time()))
        );
        if (!isset($invoice['currencyvalue'])) {
            die('Fatal error: couldn\'t get quote for ' . $invoice['currency'] . ' currency!<br>');
        }

        $DB->BeginTrans();
        $tables = array('documents', 'cash', 'invoicecontents', 'numberplans', 'divisions', 'vdivisions',
            'addresses', 'customers', 'customer_addresses');
        if (ConfigHelper::getConfig('database.type') != 'postgres') {
            $tables = array_merge($tables, array('addresses a', 'customers c', 'customer_addresses ca'));
        }

        if ($SYSLOG) {
            $tables = array_merge($tables, array('logmessages', 'logmessagekeys', 'logmessagedata', 'logtransactions'));
        }

        $hook_data = array(
            'tables' => array(),
        );
        $hook_data = $LMS->ExecuteHook('invoicenew_save_lock_tables', $hook_data);
        if (is_array($hook_data['tables']) && !empty($hook_data['tables'])) {
            $tables = array_unique(array_merge($tables, $hook_data['tables']));
        }

        $DB->LockTables($tables);

        if (!$invoice['number']) {
            $invoice['number'] = $LMS->GetNewDocumentNumber(array(
                'doctype' => $invoice['proforma'] ? DOC_INVOICE_PRO : DOC_INVOICE,
                'planid' => $invoice['numberplanid'],
                'cdate' => $invoice['cdate'],
                'customerid' => $customer['id'],
            ));
        } else {
            if (!preg_match('/^[0-9]+$/', $invoice['number'])) {
                $error['number'] = trans('Invoice number must be integer!');
            } elseif ($LMS->DocumentExists(array(
                    'number' => $invoice['number'],
                    'doctype' => $invoice['proforma'] ? DOC_INVOICE_PRO : DOC_INVOICE,
                    'planid' => $invoice['numberplanid'],
                    'cdate' => $invoice['cdate'],
                    'customerid' => $customer['id'],
                ))) {
                $error['number'] = trans('Invoice number $a already exists!', $invoice['number']);
            }

            if ($error) {
                $invoice['number'] = $LMS->GetNewDocumentNumber(array(
                    'doctype' => $invoice['proforma'] ? DOC_INVOICE_PRO : DOC_INVOICE,
                    'planid' => $invoice['numberplanid'],
                    'cdate' => $invoice['cdate'],
                    'customerid' => $customer['id'],
                ));
                $error = null;
            }
        }

        $invoice['type'] = $invoice['proforma'] ? DOC_INVOICE_PRO : DOC_INVOICE;

        $hook_data = array(
            'customer' => $customer,
            'contents' => $contents,
            'invoice' => $invoice,
        );
        $hook_data = $LMS->ExecuteHook('invoicenew_save_before_submit', $hook_data);

        $iid = $LMS->AddInvoice($hook_data);

        $hook_data['invoice']['id'] = $iid;
        $hook_data['contents'] = $contents;
        $hook_data = $LMS->ExecuteHook('invoicenew_save_after_submit', $hook_data);

        $contents = $hook_data['contents'];
        $invoice = $hook_data['invoice'];

        // usuwamy wczesniejsze zobowiazania bez faktury
        foreach ($contents as $item) {
            if (!empty($item['cashid'])) {
                $ids[] = intval($item['cashid']);
            }
        }

        if (!empty($ids)) {
            if ($SYSLOG) {
                foreach ($ids as $cashid) {
                    $args = array(
                    SYSLOG::RES_CASH => $cashid,
                    SYSLOG::RES_CUST => $customer['id'],
                    );
                    $SYSLOG->AddMessage(SYSLOG::RES_CASH, SYSLOG::OPER_DELETE, $args);
                }
            }
            $DB->Execute('DELETE FROM cash WHERE id IN (' . implode(',', $ids) . ')');
        }

        if (!empty($invoice['proformaid'])) {
            if (!empty($invoice['preserve-proforma'])) {
                $LMS->PreserveProforma($invoice['proformaid']);
            } else {
                $LMS->DeleteArchiveTradeDocument($invoice['proformaid']);
                $LMS->InvoiceDelete($invoice['proformaid']);
            }
        }

        $DB->UnLockTables();
        $DB->CommitTrans();

        $SESSION->remove('invoicecontents', true);
        $SESSION->remove('invoicecustomer', true);
        $SESSION->remove('invoice', true);
        $SESSION->remove('invoicenewerror', true);

        $contents = $customer = $error = $invoice = null;

        if (isset($_GET['print'])) {
            $which = $_GET['which'] ?? 0;

            $SESSION->save('invoiceprint', array('invoice' => $iid, 'which' => $which), true);
        }

        if (isset($_POST['reuse']) || isset($_GET['print'])) {
            $SESSION->redirect('?m=invoicenew&action=init');
        } else {
            $SESSION->redirect_to_history_entry();
        }

        break;
}

$SESSION->save('invoice', $invoice, true);
$SESSION->save('invoicecontents', $contents ?? null, true);
$SESSION->save('invoicecustomer', $customer ?? null, true);
$SESSION->save('invoicenewerror', $error ?? null, true);

if ($action && empty($error) && empty($warning)) {
    // redirect needed because we don't want to destroy contents of invoice in order of page refresh
    $SESSION->redirect('?m=invoicenew');
}

$covenantlist = array();
if (isset($customer)) {
    $list = GetCustomerCovenants($customer['id'], $invoice['currency']);
}

if (isset($list)) {
    if ($contents) {
        foreach ($list as $row) {
            $i = 0;
            foreach ($contents as $item) {
                if (isset($item['cashid']) && $row['cashid'] == $item['cashid']) {
                    $i = 1;
                    break;
                }
            }
            if (!$i) {
                $covenantlist[] = $row;
            }
        }
    } else {
        $covenantlist = $list;
    }
}

if (!ConfigHelper::checkConfig('phpui.big_networks')) {
    $SMARTY->assign('customers', $LMS->GetCustomerNames());
}

if ($newinvoice = $SESSION->get('invoiceprint', true)) {
    $SMARTY->assign('newinvoice', $newinvoice);
    $SESSION->remove('invoiceprint', true);
}

$SMARTY->assign('covenantlist', $covenantlist);
$SMARTY->assign('error', $error);
$SMARTY->assign('tariffs', $LMS->GetTariffs());

$args = array(
    'doctype' => !empty($invoice['proforma']) ? DOC_INVOICE_PRO : DOC_INVOICE,
    'cdate' => $invoice['cdate'],
);
if (isset($customer)) {
    $args['customerid'] = $customer['id'];
    $args['division'] = $customer['divisionid'];
    $args['customertype'] = $customer['type'];
} else {
    $args['customerid'] = null;
}

$numberplanlist = $LMS->GetNumberPlans($args);
if (!$numberplanlist) {
    $numberplanlist = $LMS->getSystemDefaultNumberPlan($args);
}
$SMARTY->assign('numberplanlist', $numberplanlist);

$SMARTY->assign('taxeslist', $taxeslist);

if (!empty($invoice['proformaid'])) {
    $layout['pagetitle'] = trans('Conversion Pro Forma Invoice $a To Invoice', $invoice['proformanumber']);
} elseif (!empty($invoice['proforma'])) {
    $layout['pagetitle'] = trans('New Pro Forma Invoice');
} else {
    $layout['pagetitle'] = trans('New Invoice');
}

$hook_data = array(
    'customer' => $customer,
    'contents' => $contents,
    'invoice' => $invoice,
);
$hook_data = $LMS->ExecuteHook('invoicenew_before_display', $hook_data);
$customer = $hook_data['customer'];
$contents = $hook_data['contents'];
$invoice = $hook_data['invoice'];

if (isset($customer)) {
    $addresses = $LMS->getCustomerAddresses($customer['id']);
    $SMARTY->assign('addresses', $addresses);
}

$SMARTY->assign('customer', $customer);
$SMARTY->assign('contents', $contents);
$SMARTY->assign('invoice', $invoice);
$SMARTY->assign('planDocumentType', $invoice['proforma'] ? DOC_INVOICE_PRO : DOC_INVOICE);

$total_value = 0;
if (!empty($contents)) {
    foreach ($contents as $item) {
        $total_value += $item['s_valuebrutto'];
    }
}

$SMARTY->assign('suggested_flags', array(
    'splitpayment' => $LMS->isSplitPaymentSuggested(
        isset($customer) ? $customer['id'] : null,
        date('Y/m/d', $invoice['cdate']),
        $total_value
    ),
    'telecomservice' => true,
));

$SMARTY->display('invoice/invoicenew.html');
