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

include(MODULES_DIR . DIRECTORY_SEPARATOR . 'invoiceajax.inc.php');

$action = isset($_GET['action']) ? $_GET['action'] : null;

if (isset($_GET['id']) && $action == 'init') {
    $docid = $LMS->GetDocumentLastReference($_GET['id']);
    $invoice = $LMS->GetInvoiceContent($docid);
    if ($invoice['doctype'] == DOC_CNOTE) {
        $invoice['number'] = $invoice['invoice']['number'];
        $invoice['template'] = $invoice['invoice']['template'];
        $invoice['numberplanid'] = $invoice['invoice']['numberplanid'];
        $invoice['cdate'] = $invoice['invoice']['cdate'];
    }

    if (!empty($invoice['cancelled'])) {
        $SESSION->redirect('?m=invoicelist');
    }

    $SESSION->remove('invoicecontents', true);
    $SESSION->remove('cnote', true);
    $SESSION->remove('cnoteerror', true);

    $taxeslist = $LMS->GetTaxes($invoice['cdate'], $invoice['cdate']);

    foreach ($invoice['content'] as $item) {
        $nitem['tariffid']  = $item['tariffid'];
        $nitem['name']      = $item['description'];
        $nitem['prodid']    = $item['prodid'];
        $nitem['count']     = str_replace(',', '.', $item['count']);
        $pdiscount = floatval($item['pdiscount']);
        $nitem['discount']  = (!empty($pdiscount) ? str_replace(',', '.', $item['pdiscount']) : str_replace(',', '.', $item['vdiscount']));
        $nitem['discount_type'] = (!empty($pdiscount) ? DISCOUNT_PERCENTAGE : DISCOUNT_AMOUNT);
        $nitem['pdiscount'] = str_replace(',', '.', $item['pdiscount']);
        $nitem['vdiscount'] = str_replace(',', '.', $item['vdiscount']);
        $nitem['content']       = str_replace(',', '.', $item['content']);
        $nitem['valuenetto']    = str_replace(',', '.', $item['basevalue']);
        // if position count is 0 (deleted position) then count value brutto based on value netto and tax value
        $nitem['valuebrutto']   = str_replace(
            ',',
            '.',
            empty($item['count']) ? round(($item['basevalue'] * ($item['taxvalue'] + 100)) / 100, 2) : $item['value']
        );
        $nitem['s_valuenetto']  = str_replace(',', '.', $item['totalbase']);
        $nitem['s_valuebrutto'] = str_replace(',', '.', $item['total']);
        $nitem['tax']       = isset($taxeslist[$item['taxid']]) ? $taxeslist[$item['taxid']]['label'] : 0;
        $nitem['taxid']     = $item['taxid'];
        $nitem['taxcategory'] = $item['taxcategory'];
        $nitem['itemid']    = $item['itemid'];
        $nitem['deleted'] = empty($item['total']);
        $invoicecontents[$nitem['itemid']] = $nitem;
    }
    $invoice['content'] = $invoicecontents;

    if (empty($invoice['divisionid'])) {
        $cnote['numberplanid'] = $DB->GetOne(
            'SELECT id FROM numberplans
			WHERE doctype = ? AND isdefault = 1',
            array(DOC_CNOTE)
        );
    } else {
        $cnote['numberplanid'] = $DB->GetOne(
            'SELECT p.id FROM numberplans p
			JOIN numberplanassignments a ON a.planid = p.id
			WHERE doctype = ? AND a.divisionid = ? AND isdefault = 1',
            array(DOC_CNOTE, $invoice['divisionid'])
        );
    }

    $currtime = time();
    $cnote['cdate'] = $currtime;
    //$cnote['sdate'] = $currtime;
    $cnote['sdate'] = $invoice['sdate'];
    $cnote['reason'] = '';
    $cnote['paytype'] = $invoice['paytype'];
    $cnote['splitpayment'] = $invoice['splitpayment'];
    $cnote['flags'] = array(
        DOC_FLAG_RECEIPT => empty($invoice['flags'][DOC_FLAG_RECEIPT]) ? 0 : 1,
    );
    $cnote['currency'] = $invoice['currency'];
    $cnote['oldcurrency'] = $invoice['currency'];

    $t = $invoice['cdate'] + $invoice['paytime'] * 86400;
    $deadline = mktime(23, 59, 59, date('m', $t), date('d', $t), date('Y', $t));

    if ($cnote['cdate'] > $deadline) {
        $cnote['paytime'] = 0;
    } else {
        $cnote['paytime'] = floor(($deadline - $cnote['cdate']) / 86400);
    }
    $cnote['deadline'] = $cnote['cdate'] + $cnote['paytime'] * 86400;

    $cnote['use_current_division'] = true;

    $hook_data = array(
        'invoice' => $invoice,
        'cnote' => $cnote,
    );
    $hook_data = $LMS->ExecuteHook('invoicenote_init', $hook_data);
    $invoice = $hook_data['invoice'];
    $cnote = $hook_data['cnote'];

    $SESSION->save('cnote', $cnote, true);
    $SESSION->save('invoice', $invoice, true);
    $SESSION->save('invoiceid', $invoice['id'], true);
    $SESSION->save('invoicecontents', $invoicecontents, true);
}

$SESSION->restore('invoicecontents', $contents, true);
$SESSION->restore('invoice', $invoice, true);
$SESSION->restore('cnote', $cnote, true);
$SESSION->restore('cnoteerror', $error, true);

$numberplanlist = $LMS->GetNumberPlans(array(
    'doctype' => DOC_CNOTE,
    'customerid' => $invoice['customerid'],
));

$taxeslist = $LMS->GetTaxes($invoice['cdate'], $invoice['cdate']);

$ntempl = docnumber(array(
    'number' => $invoice['number'],
    'template' => $invoice['template'],
    'cdate' => $invoice['cdate'],
    'customerid' => $invoice['customerid'],
));
$layout['pagetitle'] = trans('Credit Note for Invoice: $a', $ntempl);

switch ($action) {
    case 'deletepos':
        if ($invoice['closed']) {
            break;
        }
        $contents[$_GET['itemid']]['deleted'] = true;
        break;

    case 'recoverpos':
        if ($invoice['closed']) {
            break;
        }
        $contents[$_GET['itemid']]['deleted'] = false;
        break;

    case 'setheader':
        $oldcurrency = $cnote['oldcurrency'];

        $cnote = null;
        $error = null;

        if ($cnote = $_POST['cnote']) {
            foreach ($cnote as $key => $val) {
                $cnote[$key] = $val;
            }
        }

        $currtime = time();

        if (ConfigHelper::checkPrivilege('invoice_consent_date')) {
            if ($cnote['cdate']) {
                list ($year, $month, $day) = explode('/', $cnote['cdate']);
                if (checkdate($month, $day, $year)) {
                    $cnote['cdate'] = mktime(date('G', $currtime), date('i', $currtime), date('s', $currtime), $month, $day, $year);
                    if ($cnote['cdate'] < $invoice['cdate']) {
                        $error['cdate'] = trans('Credit note date cannot be earlier than invoice date!');
                    }
                } else {
                    $error['cdate'] = trans('Incorrect date format! Using current date.');
                    $cnote['cdate'] = $currtime;
                }
            } else {
                $cnote['cdate'] = $currtime;
            }
        } else {
            $cnote['cdate'] = $currtime;
        }

        if (ConfigHelper::checkPrivilege('invoice_sale_date')) {
            if ($cnote['sdate']) {
                list ($syear, $smonth, $sday) = explode('/', $cnote['sdate']);
                if (checkdate($smonth, $sday, $syear)) {
                    $sdate = mktime(23, 59, 59, $smonth, $sday, $syear);
                    $cnote['sdate'] = mktime(date('G', $currtime), date('i', $currtime), date('s', $currtime), $smonth, $sday, $syear);
                    if ($sdate < $invoice['sdate']) {
                        $error['sdate'] = trans('Credit note sale date cannot be earlier than invoice sale date!');
                    }
                } else {
                    $error['sdate'] = trans('Incorrect date format! Using current date.');
                    $cnote['sdate'] = $currtime;
                }
            } else {
                $cnote['sdate'] = $currtime;
            }
        } else {
            $cnote['sdate'] = $invoice['sdate'];
        }

        if ($cnote['deadline']) {
            list ($dyear, $dmonth, $dday) = explode('/', $cnote['deadline']);
            if (checkdate($dmonth, $dday, $dyear)) {
                $cnote['deadline'] = mktime(date('G', $currtime), date('i', $currtime), date('s', $currtime), $dmonth, $dday, $dyear);
            } else {
                $error['deadline'] = trans('Incorrect date format!');
                $cnote['deadline'] = $currtime;
                break;
            }
        } else {
            $cnote['deadline'] = $currtime;
        }

        if ($cnote['deadline'] < $cnote['cdate']) {
            $error['deadline'] = trans('Deadline date should be later than consent date!');
        }

        if ($cnote['number']) {
            if (!preg_match('/^[0-9]+$/', $cnote['number'])) {
                $error['number'] = trans('Credit note number must be integer!');
            } elseif ($LMS->DocumentExists(array(
                    'number' => $cnote['number'],
                    'doctype' => DOC_CNOTE,
                    'planid' => $cnote['numberplanid'],
                    'cdate' => $cnote['cdate'],
                ))) {
                $error['number'] = trans('Credit note number $a already exists!', $cnote['number']);
            }
        }

        $cnote['currency'] = $oldcurrency;
        $cnote['oldcurrency'] = $oldcurrency;

        // finally check if selected customer can use selected numberplan
        $divisionid = !empty($cnote['use_current_division']) ? $invoice['current_divisionid'] : $invoice['divisionid'];

        if ($cnote['numberplanid'] && !$DB->GetOne('SELECT 1 FROM numberplanassignments
			WHERE planid = ? AND divisionid = ?', array($cnote['numberplanid'], $divisionid))) {
                $error['number'] = trans('Selected numbering plan doesn\'t match customer\'s division!');
        }

        break;

    case 'save':
        if (empty($contents) || empty($cnote)) {
            break;
        }

        $error = array();

        $SESSION->restore('invoiceid', $invoice['id'], true);

        if (!ConfigHelper::checkPrivilege('invoice_consent_date')) {
            $cnote['cdate'] = time();
        }

        if (!ConfigHelper::checkPrivilege('invoice_sale_date')) {
            $cnote['sdate'] = $invoice['sdate'];
        }

        $invoicecontents = $invoice['content'];
        $newcontents = r_trim($_POST);

        foreach ($contents as $item) {
            $idx = $item['itemid'];

            if (ConfigHelper::checkConfig('phpui.tax_category_required')
                && (!isset($newcontents['taxcategory'][$idx]) || empty($newcontents['taxcategory'][$idx]))) {
                $error['taxcategory[' . $idx . ']'] = trans('Tax category selection is required!');
            }

            $contents[$idx]['taxid'] = isset($newcontents['taxid'][$idx]) ? $newcontents['taxid'][$idx] : $item['taxid'];
            $contents[$idx]['taxcategory'] = isset($newcontents['taxcategory'][$idx]) ? $newcontents['taxcategory'][$idx] : $item['taxcategory'];
            $contents[$idx]['prodid'] = isset($newcontents['prodid'][$idx]) ? $newcontents['prodid'][$idx] : $item['prodid'];
            $contents[$idx]['content'] = isset($newcontents['content'][$idx]) ? $newcontents['content'][$idx] : $item['content'];
            $contents[$idx]['count'] = isset($newcontents['count'][$idx]) ? $newcontents['count'][$idx] : $item['count'];

            $contents[$idx]['discount'] = str_replace(',', '.', !empty($newcontents['discount'][$idx]) ? $newcontents['discount'][$idx] : $item['discount']);
            $contents[$idx]['pdiscount'] = 0;
            $contents[$idx]['vdiscount'] = 0;
            $contents[$idx]['discount_type'] = isset($newcontents['discount_type'][$idx]) ? $newcontents['discount_type'][$idx] : $item['discount_type'];
            if (preg_match('/^[0-9]+(\.[0-9]+)*$/', $contents[$idx]['discount'])) {
                $contents[$idx]['pdiscount'] = ($contents[$idx]['discount_type'] == DISCOUNT_PERCENTAGE ? floatval($contents[$idx]['discount']) : 0);
                $contents[$idx]['vdiscount'] = ($contents[$idx]['discount_type'] == DISCOUNT_AMOUNT ? floatval($contents[$idx]['discount']) : 0);
            }
            if ($contents[$idx]['pdiscount'] < 0 || $contents[$idx]['pdiscount'] > 99.99 || $contents[$idx]['vdiscount'] < 0) {
                $error['discount[' . $idx . ']'] = trans('Wrong discount value!');
            }

            $contents[$idx]['name'] = isset($newcontents['name'][$idx]) ? $newcontents['name'][$idx] : $item['name'];
            $contents[$idx]['tariffid'] = isset($newcontents['tariffid'][$idx]) ? $newcontents['tariffid'][$idx] : $item['tariffid'];
            $contents[$idx]['valuebrutto'] = $newcontents['valuebrutto'][$idx] != '' ? $newcontents['valuebrutto'][$idx] : $item['valuebrutto'];
            $contents[$idx]['valuenetto'] = $newcontents['valuenetto'][$idx] != '' ? $newcontents['valuenetto'][$idx] : $item['valuenetto'];
            $contents[$idx]['valuebrutto'] = f_round($contents[$idx]['valuebrutto']);
            $contents[$idx]['valuenetto'] = f_round($contents[$idx]['valuenetto']);
            $contents[$idx]['count'] = f_round($contents[$idx]['count'], 3);
            $contents[$idx]['pdiscount'] = f_round($contents[$idx]['pdiscount']);
            $contents[$idx]['vdiscount'] = f_round($contents[$idx]['vdiscount']);
            $taxvalue = $taxeslist[$contents[$idx]['taxid']]['value'];

            $contents[$idx]['old_discount_type'] = $item['discount_type'];
            $discount_method = ConfigHelper::getConfig('invoices.credit_note_relation_to_invoice', 'first');
            //if discount was changed
            if (!(isset($item['deleted']) && $item['deleted'])
                && $contents[$idx]['valuenetto'] == floatval($item['valuenetto'])
                && $contents[$idx]['valuebrutto'] == floatval($item['valuebrutto'])
                && $contents[$idx]['count'] == floatval($item['count'])
                && (floatval(str_replace(',', '.', $newcontents['discount'][$idx])) != floatval($item['discount'])
                    || intval($contents[$idx]['discount_type']) != $contents[$idx]['old_discount_type'])) {
                if (floatval(str_replace(',', '.', $newcontents['discount'][$idx])) != floatval($item['discount'])
                    && floatval(str_replace(',', '.', $newcontents['discount'][$idx])) == 0) {
                    //when discount is removed or zeroed restore last document value
                    if ($discount_method == 'first') {
                        if ($contents[$idx]['old_discount_type'] == DISCOUNT_PERCENTAGE) {
                            $orig_valuebrutto = f_round((100 * $item['valuebrutto']) / (100 - $item['pdiscount']));
                            $new_valuebrutto = f_round($orig_valuebrutto * (1 - ($contents[$idx]['pdiscount'] / 100)));
                            $old_valuebrutto = $orig_valuebrutto;
                            $contents[$idx]['valuebrutto'] = f_round(-1 * ($new_valuebrutto - $old_valuebrutto));
                        } else {
                            $contents[$idx]['valuebrutto'] = f_round($contents[$idx]['vdiscount']);
                            $contents[$idx]['vdiscount'] = 0;
                        }
                    } else {
                        if ($contents[$idx]['old_discount_type'] == DISCOUNT_PERCENTAGE) {
                            $orig_valuebrutto = f_round((100 * $item['valuebrutto']) / (100 - $item['pdiscount']));
                            $new_valuebrutto = f_round($orig_valuebrutto - $contents[$idx]['vdiscount']);
                            $old_valuebrutto = $invoicecontents[$idx]['valuebrutto'];
                            $contents[$idx]['valuebrutto'] = f_round($new_valuebrutto - $old_valuebrutto);
                            $contents[$idx]['pdiscount'] = 0;
                        } else {
                            $orig_valuebrutto = f_round($item['valuebrutto'] + $item['vdiscount']);
                            $new_valuebrutto = f_round($orig_valuebrutto * (1 - ($contents[$idx]['pdiscount'] / 100)));
                            $old_valuebrutto = $invoicecontents[$idx]['valuebrutto'];
                            $contents[$idx]['valuebrutto'] = f_round($new_valuebrutto - $old_valuebrutto);
                            $contents[$idx]['vdiscount'] = 0;
                        }
                    }
                } else {
                    //when discount is changed, not removed or zeroed
                        //if discount type was changed (discount value could be changed too)
                    if (intval($contents[$idx]['discount_type']) != $contents[$idx]['old_discount_type']) {
                        // if document type was changed
                        if ($contents[$idx]['old_discount_type'] == DISCOUNT_PERCENTAGE) {
                            //change pdiscount to vdiscount
                            if ($discount_method == 'first') {
                                $orig_valuebrutto = f_round((100 * $item['valuebrutto']) / (100 - $item['pdiscount']));
                                $new_valuebrutto = f_round($orig_valuebrutto - $contents[$idx]['vdiscount']);
                                $old_valuebrutto = $invoicecontents[$idx]['valuebrutto'];
                                $contents[$idx]['valuebrutto'] = f_round($new_valuebrutto - $old_valuebrutto);
                            } else {
                                $orig_valuebrutto = f_round($item['valuebrutto'] + $item['vdiscount']);
                                $new_valuebrutto = f_round($orig_valuebrutto - $contents[$idx]['vdiscount']);
                                $old_valuebrutto = $invoicecontents[$idx]['valuebrutto'];
                                $contents[$idx]['valuebrutto'] = f_round($new_valuebrutto - $old_valuebrutto);
                            }
                        } else {
                            //change vdiscount to pdiscount
                            if ($discount_method == 'first') {
                                $orig_valuebrutto = f_round($item['valuebrutto'] + $item['vdiscount']);
                                $new_valuebrutto = f_round($orig_valuebrutto * (1 - ($contents[$idx]['pdiscount'] / 100)));
                                $old_valuebrutto = $invoicecontents[$idx]['valuebrutto'];
                                $contents[$idx]['valuebrutto'] = f_round($new_valuebrutto - $old_valuebrutto);
                            } else {
                                $orig_valuebrutto = f_round($item['valuebrutto']);
                                $new_valuebrutto = f_round($orig_valuebrutto * (1 - ($contents[$idx]['pdiscount'] / 100)));
                                $old_valuebrutto = $orig_valuebrutto;
                                $contents[$idx]['valuebrutto'] = f_round($new_valuebrutto - $old_valuebrutto);
                            }
                        }
                    } else {
                        //only discount value was changed and discount type was not changed
                        if ($discount_method == 'first') {
                            if ($contents[$idx]['discount_type'] == DISCOUNT_PERCENTAGE) {
                                $orig_valuebrutto = f_round((100 * $item['valuebrutto']) / (100 - $item['pdiscount']));
                                $new_valuebrutto = f_round($orig_valuebrutto * (1 - ($contents[$idx]['pdiscount'] / 100)));
                                $old_valuebrutto = $invoicecontents[$idx]['valuebrutto'];
                                $contents[$idx]['valuebrutto'] = f_round($new_valuebrutto - $old_valuebrutto);
                            } else {
                                $orig_valuebrutto = f_round($item['valuebrutto'] + $item['vdiscount']);
                                $new_valuebrutto = f_round($orig_valuebrutto - $contents[$idx]['vdiscount']);
                                $old_valuebrutto = $invoicecontents[$idx]['valuebrutto'];
                                $contents[$idx]['valuebrutto'] = f_round($new_valuebrutto - $old_valuebrutto);
                            }
                        } else {
                            if ($contents[$idx]['discount_type'] == DISCOUNT_PERCENTAGE) {
                                $orig_valuebrutto = f_round($item['valuebrutto']);
                                $new_valuebrutto = f_round($orig_valuebrutto * (1 - ($contents[$idx]['pdiscount'] / 100)));
                                $old_valuebrutto = $orig_valuebrutto;
                                $contents[$idx]['valuebrutto'] = f_round($new_valuebrutto - $old_valuebrutto);
                            } else {
                                $contents[$idx]['valuebrutto'] = f_round(-1 * $contents[$idx]['vdiscount']);
                            }
                        }
                    }
                }
                if (!empty($invoicecontents[$idx]['count']) && !empty($contents[$idx]['count'])) {
                    //cash value for recovered/restored invoice position
                    $contents[$idx]['cash'] = f_round(-1 * $contents[$idx]['valuebrutto'] * $contents[$idx]['count'], 2);
                } else {
                    $contents[$idx]['cash'] = 0;
                }

                $contents[$idx]['count'] = 0;
            } else { // if discount type or discount value dosen't change
                if ($contents[$idx]['valuenetto'] != $item['valuenetto']) {
                    $contents[$idx]['valuebrutto'] = $contents[$idx]['valuenetto'] * ($taxvalue / 100 + 1);
                    $contents[$idx]['pdiscount'] = 0;
                    $contents[$idx]['vdiscount'] = 0;
                } elseif (f_round($contents[$idx]['valuebrutto']) == f_round($item['valuebrutto'])) {
                    $contents[$idx]['valuebrutto'] = $item['valuebrutto'];
                }

                if ((isset($item['deleted']) && $item['deleted']) || empty($contents[$idx]['count'])) {
                    $contents[$idx]['valuebrutto'] = f_round(-1 * $invoicecontents[$idx]['valuebrutto']);
                    $contents[$idx]['cash'] = f_round($invoicecontents[$idx]['valuebrutto'] * $invoicecontents[$idx]['count'], 2);
                    $contents[$idx]['count'] = f_round(-1 * $invoicecontents[$idx]['count'], 3);
                } elseif ($contents[$idx]['count'] == $item['count']) {
                    $contents[$idx]['cash'] = f_round(-1 * f_round($contents[$idx]['valuebrutto'] - $invoicecontents[$idx]['valuebrutto']) * $contents[$idx]['count'], 2);
                    $contents[$idx]['valuebrutto'] = f_round($contents[$idx]['valuebrutto'] - $invoicecontents[$idx]['valuebrutto']);
                    $contents[$idx]['count'] = 0;
                } elseif ($contents[$idx]['valuenetto'] != $item['valuenetto']
                    || $contents[$idx]['valuebrutto'] != $item['valuebrutto']
                    || (empty($invoicecontents['count']) && !empty($contents[$idx]['count']))) {
                    $contents[$idx]['pdiscount'] = 0;
                    $contents[$idx]['vdiscount'] = 0;
                    if (empty($invoicecontents[$idx]['count']) && !empty($contents[$idx]['count'])) {
                        // cash value for recovered/restored invoice position
                        $contents[$idx]['cash'] = f_round(-1 * $contents[$idx]['valuebrutto'] * $contents[$idx]['count'], 2);
                    } else {
                        $contents[$idx]['cash'] = f_round(-1 * ($contents[$idx]['valuebrutto'] * $contents[$idx]['count']
                                - $invoicecontents[$idx]['valuebrutto'] * $invoicecontents[$idx]['count']), 2);
                    }

                    // determine new brutto value only if invoice position is NOT recovered/restored
                    if (!empty($invoicecontents[$idx]['count']) || empty($contents[$idx]['count'])) {
                        $contents[$idx]['valuebrutto'] = f_round($contents[$idx]['valuebrutto'] - $invoicecontents[$idx]['valuebrutto']);
                    }

                    $contents[$idx]['count'] = f_round($contents[$idx]['count'] - $invoicecontents[$idx]['count'], 3);
                } else {
                    $contents[$idx]['cash'] = 0;
                    $contents[$idx]['valuebrutto'] = 0;
                    $contents[$idx]['count'] = 0;
                }
            }

            $contents[$idx]['cash'] = str_replace(',', '.', $contents[$idx]['cash']);
            $contents[$idx]['valuebrutto'] = str_replace(',', '.', $contents[$idx]['valuebrutto']);
            $contents[$idx]['count'] = str_replace(',', '.', $contents[$idx]['count']);
        }

        $cnote['paytime'] = round(($cnote['deadline'] - $cnote['cdate']) / 86400);

        $cnote['currency'] = $cnote['oldcurrency'];

        $hook_data = array(
            'invoice' => $invoice,
            'contents' => $contents,
        );
        $hook_data = $LMS->ExecuteHook('invoicenote_save_validation', $hook_data);
        if (isset($hook_data['error']) && is_array($hook_data['error'])) {
            $error = array_merge($error, $hook_data['error']);
        }

        if (!empty($error)) {
            foreach ($contents as $item) {
                $idx = $item['itemid'];
                $contents[$idx]['taxid'] = $newcontents['taxid'][$idx];
                $contents[$idx]['taxcategory'] = $newcontents['taxcategory'][$idx];
                $contents[$idx]['prodid'] = $newcontents['prodid'][$idx];
                $contents[$idx]['content'] = $newcontents['content'][$idx];
                $contents[$idx]['count'] = $newcontents['count'][$idx];
                $contents[$idx]['discount'] = $newcontents['discount'][$idx];
                $contents[$idx]['discount_type'] = $newcontents['discount_type'][$idx];
                $contents[$idx]['name'] = $newcontents['name'][$idx];
                $contents[$idx]['tariffid'] = $newcontents['tariffid'][$idx];
                $contents[$idx]['valuebrutto'] = $newcontents['valuebrutto'][$idx];
                $contents[$idx]['valuenetto'] = $newcontents['valuenetto'][$idx];
            }
            break;
        }

        $cnote['currencyvalue'] = $LMS->getCurrencyValue($cnote['currency'], $cnote['sdate']);
        if (!isset($cnote['currencyvalue'])) {
            die('Fatal error: couldn\'t get quote for ' . $cnote['currency'] . ' currency!<br>');
        }

        $DB->BeginTrans();
        $DB->LockTables(array('documents', 'numberplans', 'divisions', 'vdivisions'));

        if (!isset($cnote['number']) || !$cnote['number']) {
            $cnote['number'] = $LMS->GetNewDocumentNumber(array(
                'doctype' => DOC_CNOTE,
                'planid' => $cnote['numberplanid'],
                'cdate' => $cnote['cdate'],
                'customerid' => $invoice['customerid'],
            ));
        } else {
            if (!preg_match('/^[0-9]+$/', $cnote['number'])) {
                $error['number'] = trans('Credit note number must be integer!');
            } elseif ($LMS->DocumentExists(array(
                    'number' => $cnote['number'],
                    'doctype' => DOC_CNOTE,
                    'planid' => $cnote['numberplanid'],
                    'cdate' => $cnote['cdate'],
                    'customerid' => $invoice['customerid'],
                ))) {
                $error['number'] = trans('Credit note number $a already exists!', $cnote['number']);
            }

            if ($error) {
                $cnote['number'] = $LMS->GetNewDocumentNumber(array(
                    'doctype' => DOC_CNOTE,
                    'planid' => $cnote['numberplanid'],
                    'cdate' => $cnote['cdate'],
                    'customerid' => $invoice['customerid'],
                ));
            }
        }

        $division = $LMS->GetDivision(!empty($cnote['use_current_division']) ? $invoice['current_divisionid'] : $invoice['divisionid']);

        if ($cnote['numberplanid']) {
            $fullnumber = docnumber(array(
                'number' => $cnote['number'],
                'template' => $DB->GetOne('SELECT template FROM numberplans WHERE id = ?', array($cnote['numberplanid'])),
                'cdate' => $cnote['cdate'],
                'customerid' => $invoice['customerid'],
            ));
        } else {
            $fullnumber = null;
        }

        if (!empty($invoice['recipient_address_id'])) {
            $invoice['recipient_address_id'] = $LMS->CopyAddress($invoice['recipient_address_id']);
        } else {
            $invoice['recipient_address_id'] = null;
        }

        if (empty($invoice['post_address_id'])) {
            $invoice['post_address_id'] = null;
        } else {
            $invoice['post_address_id'] = $LMS->CopyAddress($invoice['post_address_id']);
        }

        $use_current_customer_data = isset($cnote['use_current_customer_data']);
        if ($use_current_customer_data) {
            $customer = $LMS->GetCustomer($invoice['customerid'], true);
        }

        $args = array(
            'number' => $cnote['number'],
            SYSLOG::RES_NUMPLAN => !empty($cnote['numberplanid']) ? $cnote['numberplanid'] : null,
            'type' => DOC_CNOTE,
            'cdate' => $cnote['cdate'],
            'sdate' => $cnote['sdate'],
            'paytime' => $cnote['paytime'],
            'paytype' => $cnote['paytype'],
            'splitpayment' => empty($cnote['splitpayment']) ? 0 : 1,
            'flags' => empty($cnote['flags'][DOC_FLAG_RECEIPT]) ? 0 : DOC_FLAG_RECEIPT,
            SYSLOG::RES_USER => Auth::GetCurrentUser(),
            SYSLOG::RES_CUST => $invoice['customerid'],
            'name' => $use_current_customer_data ? $customer['customername'] : $invoice['name'],
            'address' => $use_current_customer_data ? (($customer['postoffice'] && $customer['postoffice'] != $customer['city'] && $customer['street']
                ? $customer['postoffice'] . ', ' : '') . $customer['address']) : $invoice['address'],
            'ten' => $use_current_customer_data ? $customer['ten'] : $invoice['ten'],
            'ssn' => $use_current_customer_data ? $customer['ssn'] : $invoice['ssn'],
            'zip' => $use_current_customer_data ? $customer['zip'] : $invoice['zip'],
            'city' => $use_current_customer_data ? ($customer['postoffice'] ? $customer['postoffice'] : $customer['city'])
                : $invoice['city'],
            SYSLOG::RES_COUNTRY => $use_current_customer_data ? (empty($customer['countryid']) ? null : $customer['countryid'])
                : (empty($invoice['countryid']) ? null : $invoice['countryid']),
            'reference' => $invoice['id'],
            'reason' => $cnote['reason'],
            SYSLOG::RES_DIV => !empty($cnote['use_current_division']) ? $invoice['current_divisionid']
                : (!empty($invoice['divisionid']) ? $invoice['divisionid'] : null),
            'div_name' => $division['name'] ? $division['name'] : '',
            'div_shortname' => $division['shortname'] ? $division['shortname'] : '',
            'div_address' => $division['address'] ? $division['address'] : '',
            'div_city' => $division['city'] ? $division['city'] : '',
            'div_zip' => $division['zip'] ? $division['zip'] : '',
            'div_' . SYSLOG::getResourceKey(SYSLOG::RES_COUNTRY) => !empty($division['countryid']) ? $division['countryid'] : null,
            'div_ten' => $division['ten'] ? $division['ten'] : '',
            'div_regon' => $division['regon'] ? $division['regon'] : '',
            'div_bank' => $division['bank'] ?: null,
            'div_account' => $division['account'] ? $division['account'] : '',
            'div_inv_header' => $division['inv_header'] ? $division['inv_header'] : '',
            'div_inv_footer' => $division['inv_footer'] ? $division['inv_footer'] : '',
            'div_inv_author' => $division['inv_author'] ? $division['inv_author'] : '',
            'div_inv_cplace' => $division['inv_cplace'] ? $division['inv_cplace'] : '',
            'fullnumber' => $fullnumber,
            'recipient_address_id' => $invoice['recipient_address_id'],
            'post_address_id' => $invoice['post_address_id'],
            'currency' => $cnote['currency'],
            'currencyvalue' => $cnote['currencyvalue'],
            'memo' => $use_current_customer_data ? (empty($customer['documentmemo']) ? null : $customer['documentmemo']) : $invoice['memo'],
        );
        $DB->Execute('INSERT INTO documents (number, numberplanid, type, cdate, sdate, paytime, paytype, splitpayment, flags,
				userid, customerid, name, address, ten, ssn, zip, city, countryid, reference, reason, divisionid,
				div_name, div_shortname, div_address, div_city, div_zip, div_countryid, div_ten, div_regon,
				div_bank, div_account, div_inv_header, div_inv_footer, div_inv_author, div_inv_cplace, fullnumber,
				recipient_address_id, post_address_id, currency, currencyvalue, memo)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
					?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));

        $id = $DB->GetOne(
            'SELECT id FROM documents WHERE number = ? AND cdate = ? AND type = ?',
            array($cnote['number'], $cnote['cdate'], DOC_CNOTE)
        );

        if ($SYSLOG) {
            $args[SYSLOG::RES_DOC] = $id;
            unset($args[SYSLOG::RES_USER]);
            $SYSLOG->AddMessage(
                SYSLOG::RES_DOC,
                SYSLOG::OPER_ADD,
                $args,
                array('div_' . SYSLOG::getResourceKey(SYSLOG::RES_DIV))
            );
        }

        $DB->UnLockTables();

        foreach ($contents as $idx => $item) {
            $item['valuebrutto'] = str_replace(',', '.', $item['valuebrutto']);
            $item['count'] = str_replace(',', '.', $item['count']);
            $item['pdiscount'] = str_replace(',', '.', $item['pdiscount']);
            $item['vdiscount'] = str_replace(',', '.', $item['vdiscount']);

            $args = array(
                SYSLOG::RES_DOC => $id,
                'itemid' => $idx,
                'value' => $item['valuebrutto'],
                SYSLOG::RES_TAX => $item['taxid'],
                'taxcategory' => $item['taxcategory'],
                'prodid' => $item['prodid'],
                'content' => $item['content'],
                'count' => $item['count'],
                'pdiscount' => $item['pdiscount'],
                'vdiscount' => $item['vdiscount'],
                'description' => $item['name'],
                SYSLOG::RES_TARIFF => empty($item['tariffid']) ? null : $item['tariffid'],
            );
            $DB->Execute('INSERT INTO invoicecontents (docid, itemid, value, taxid, taxcategory, prodid, content, count, pdiscount, vdiscount, description, tariffid)
					VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));

            if ($SYSLOG) {
                $args[SYSLOG::RES_CUST] = $invoice['customerid'];
                $SYSLOG->AddMessage(SYSLOG::RES_INVOICECONT, SYSLOG::OPER_ADD, $args);
            }

            if (isset($item['cash'])) {
                $args = array(
                    'time' => $cnote['cdate'],
                    SYSLOG::RES_USER => Auth::GetCurrentUser(),
                    'value' => str_replace(',', '.', $item['cash']),
                    'currency' => $cnote['currency'],
                    'currencyvalue' => $cnote['currencyvalue'],
                    SYSLOG::RES_TAX => $item['taxid'],
                    SYSLOG::RES_CUST => $invoice['customerid'],
                    'comment' => $item['name'],
                    SYSLOG::RES_DOC => $id,
                    'itemid' => $idx,
                );
                $DB->Execute('INSERT INTO cash (time, userid, value, currency, currencyvalue, taxid, customerid, comment, docid, itemid)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
                if ($SYSLOG) {
                    unset($args[SYSLOG::RES_USER]);
                    $args[SYSLOG::RES_CASH] = $DB->GetLastInsertID('cash');
                    $SYSLOG->AddMessage(SYSLOG::RES_CASH, SYSLOG::OPER_ADD, $args);
                }
            }
        }

        $hook_data = array(
            'cnote' => $cnote,
            'invoice' => $invoice,
            'contents' => $contents,
        );
        $hook_data = $LMS->ExecuteHook('invoicenote_save_after_submit', $hook_data);

        $DB->CommitTrans();

        $SESSION->remove('invoice', true);
        $SESSION->remove('invoiceid', true);
        $SESSION->remove('cnote', true);
        $SESSION->remove('invoicecontents', true);
        $SESSION->remove('cnoteerror', true);

        if (isset($_GET['print'])) {
            $which = isset($_GET['which']) ? $_GET['which'] : 0;

            $SESSION->save('invoiceprint', array('invoice' => $id, 'which' => $which), true);
        }

        $SESSION->redirect('?m=invoicelist');
        break;
}

$SESSION->save('invoice', $invoice, true);
$SESSION->save('cnote', $cnote, true);
$SESSION->save('invoicecontents', $contents, true);
$SESSION->save('cnoteerror', $error, true);

if ($action && !$error) {
    // redirect, to not prevent from invoice break with the refresh
    $SESSION->redirect('?m=invoicenote');
}

$hook_data = array(
    'contents' => $contents,
    'invoice' => $invoice,
);
$hook_data = $LMS->ExecuteHook('invoicenote_before_display', $hook_data);
$contents = $hook_data['contents'];
$invoice = $hook_data['invoice'];

$SMARTY->assign('error', $error);
$SMARTY->assign('contents', $contents);
$SMARTY->assign('cnote', $cnote);
$SMARTY->assign('invoice', $invoice);
$SMARTY->assign('refdoc', $invoice);
$SMARTY->assign('taxeslist', $taxeslist);
$SMARTY->assign('numberplanlist', $numberplanlist);
$SMARTY->assign('messagetemplates', $LMS->GetMessageTemplates(TMPL_CNOTE_REASON));

$total_value = 0;
if (!empty($contents)) {
    foreach ($contents as $item) {
        $total_value += $item['s_valuebrutto'];
    }
}

$SMARTY->assign('is_split_payment_suggested', $LMS->isSplitPaymentSuggested(
    $invoice['customerid'],
    date('Y/m/d', $cnote['cdate']),
    $total_value
));

$SMARTY->display('invoice/invoicenotemodify.html');
