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

$action = $_GET['action'] ?? null;

if (isset($_GET['id']) && $action == 'init') {
    $docid = $LMS->GetDocumentLastReference($_GET['id']);
    $invoice = $LMS->GetInvoiceContent($docid);
    if ($invoice['doctype'] == DOC_CNOTE) {
        $invoice['number'] = $invoice['invoice']['number'];
        $invoice['template'] = $invoice['invoice']['template'];
        $cnote['numberplanid'] = $invoice['numberplanid'];
        $invoice['cdate'] = $invoice['invoice']['cdate'];
    } else {
        $cnote['numberplanid'] = $invoice['numberplanid'] = $LMS->getDefaultNumberPlanID(
            DOC_CNOTE,
            empty($invoice['divisionid']) ? null : $invoice['divisionid']
        );
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
        $nitem['servicetype'] = $item['servicetype'];
        $nitem['name']      = $item['description'];
        $nitem['prodid']    = $item['prodid'];
        $nitem['count']     = str_replace(',', '.', $item['count']);
        $pdiscount = floatval($item['pdiscount']);
        $nitem['discount']  = (!empty($pdiscount) ? str_replace(',', '.', $item['pdiscount']) : str_replace(',', '.', $item['vdiscount']));
        $nitem['discount_type'] = (!empty($pdiscount) ? DISCOUNT_PERCENTAGE : DISCOUNT_AMOUNT);
        $nitem['pdiscount'] = str_replace(',', '.', $item['pdiscount']);
        $nitem['vdiscount'] = str_replace(',', '.', $item['vdiscount']);
        $nitem['content']       = str_replace(',', '.', $item['content']);
        $nitem['valuenetto']    = str_replace(',', '.', $item['netprice']);
        $nitem['valuebrutto']   = str_replace(',', '.', $item['grossprice']);
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

    $currtime = time();
    $cnote['cdate'] = $currtime;
    //$cnote['sdate'] = $currtime;
    $cnote['sdate'] = $invoice['sdate'];
    $cnote['customerid'] = $invoice['customerid'];
    $cnote['reason'] = '';
    $cnote['paytype'] = $invoice['paytype'];
    $cnote['splitpayment'] = $invoice['splitpayment'];
    $cnote['flags'] = array(
        DOC_FLAG_RECEIPT => empty($invoice['flags'][DOC_FLAG_RECEIPT]) ? 0 : 1,
        DOC_FLAG_TELECOM_SERVICE => empty($invoice['flags'][DOC_FLAG_TELECOM_SERVICE]) ? 0 : 1,
        DOC_FLAG_RELATED_ENTITY => empty($invoice['flags'][DOC_FLAG_RELATED_ENTITY]) ? 0 : 1,
    );
    $cnote['currency'] = $invoice['currency'];
    $cnote['netflag'] = $invoice['netflag'];
    $cnote['oldcurrency'] = $invoice['currency'];
    $cnote['oldcurrencyvalue'] = $invoice['currencyvalue'];

    $deadline = strtotime('today + ' . ($invoice['paytime'] + 1) . ' days', $invoice['cdate']) - 1;

    if ($cnote['cdate'] > $deadline) {
        $cnote['paytime'] = 0;
    } else {
        $cnote['paytime'] = floor(($deadline - $cnote['cdate']) / 86400);
    }
    $cnote['deadline'] = strtotime('today + ' . ($cnote['paytime'] + 1) . ' days', $cnote['cdate']) - 1;

    $cnote['use_current_division'] = true;

    $cnote['recipient_address_id'] = $invoice['recipient_address_id'];

    //old header values
    $cnote['oldheader'] = array(
        'sdate' => date("Y/m/d", $cnote['sdate']),
        'flags' => serialize($cnote['flags']),
        'netflag' => $cnote['netflag'],
        'paytype' => $cnote['paytype'],
        'deadline' => date("Y/m/d", intval($cnote['deadline'])),
        'recipient_address_id' => $cnote['recipient_address_id'],
        'use_current_customer_data' => isset($cnote['use_current_customer_data']),
        'reason' => $cnote['reason'],
    );
    $cnote['content_diff'] = 1;

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

$args = array(
    'doctype' => DOC_CNOTE,
    'cdate' => $invoice['cdate'],
    'customerid' => $invoice['customerid'],
    'division' => $invoice['divisionid'],
    'customertype' => $invoice['customertype'],
);
$numberplanlist = $LMS->GetNumberPlans($args);
if (!$numberplanlist) {
    $numberplanlist = $LMS->getSystemDefaultNumberPlan($args);
}

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
        $contents[$_GET['itemid']]['deleted'] = true;
        break;

    case 'recoverpos':
        $contents[$_GET['itemid']]['deleted'] = false;
        break;

    case 'setheader':
        $oldcurrency = $cnote['oldcurrency'];
        $oldcurrencyvalue = $cnote['oldcurrencyvalue'];
        $oldHeader = $cnote['oldheader'];

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
            $deadline = strtotime('tomorrow ' . $cnote['deadline']) - 1;
            if (empty($deadline)) {
                $error['deadline'] = trans('Incorrect date format!');
                $cnote['deadline'] = strtotime('tomorrow') - 1;
                break;
            } else {
                $cnote['deadline'] = $deadline;
            }
        } else {
            $cnote['deadline'] = strtotime('tomorrow') - 1;
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
        $cnote['oldcurrencyvalue'] = $oldcurrencyvalue;
        $cnote['customerid'] = $invoice['customerid'];

        //new header values
        $cnote['newheader'] = array(
            'sdate' => date("Y/m/d", $cnote['sdate']),
            'flags' => serialize(array(
                            DOC_FLAG_RECEIPT => empty($cnote['flags'][DOC_FLAG_RECEIPT]) ? 0 : 1,
                            DOC_FLAG_TELECOM_SERVICE => empty($cnote['flags'][DOC_FLAG_TELECOM_SERVICE]) ? 0 : 1,
                            DOC_FLAG_RELATED_ENTITY => empty($cnote['flags'][DOC_FLAG_RELATED_ENTITY]) ? 0 : 1,
                        )),
            'netflag' => $cnote['netflag'],
            'paytype' => $cnote['paytype'],
            'deadline' => date("Y/m/d", $cnote['deadline']),
            'recipient_address_id' => $cnote['recipient_address_id'],
            'use_current_customer_data' => isset($cnote['use_current_customer_data']),
            'reason' => $cnote['reason'],
        );
        $cnote['content_diff'] = 1;

        //old header values
        $cnote['oldheader'] = $oldHeader;

        // finally check if selected customer can use selected numberplan
        $divisionid = !empty($cnote['use_current_division']) ? $invoice['current_divisionid'] : $invoice['divisionid'];

        $args = array(
            'doctype' => DOC_CNOTE,
            'cdate' => $cnote['cdate'],
            'customerid' => $invoice['customerid'],
            'division' => $divisionid,
            'customertype' => $invoice['customertype'],
            'next' => false,
        );
        $numberplans = $LMS->GetNumberPlans($args);

        if ($cnote['numberplanid'] && !isset($numberplans[$cnote['numberplanid']])) {
            $error['number'] = trans('Selected numbering plan doesn\'t match customer\'s division!');
            unset($cnote['customerid']);
        }

        if ($numberplans && count($numberplans) && empty($cnote['numberplanid'])) {
            $error['numberplanid'] = trans('Select numbering plan');
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

        if ($cnote['deadline'] < $cnote['cdate']) {
            break;
        }

        $invoicecontents = $invoice['content'];
        $newcontents = r_trim($_POST);

        foreach ($contents as $item) {
            $idx = $item['itemid'];

            if (ConfigHelper::checkConfig('phpui.tax_category_required')
                && (empty($newcontents['taxcategory'][$idx]))) {
                $error['taxcategory[' . $idx . ']'] = trans('Tax category selection is required!');
            }

            $contents[$idx]['taxid'] = $newcontents['taxid'][$idx] ?? $item['taxid'];
            $contents[$idx]['taxcategory'] = $newcontents['taxcategory'][$idx] ?? $item['taxcategory'];
            $contents[$idx]['prodid'] = $newcontents['prodid'][$idx] ?? $item['prodid'];
            $contents[$idx]['content'] = $newcontents['content'][$idx] ?? $item['content'];
            $contents[$idx]['count'] = $newcontents['count'][$idx] ?? $item['count'];

            $contents[$idx]['discount'] = str_replace(',', '.', $newcontents['discount'][$idx] ?? $item['discount']);
            $contents[$idx]['pdiscount'] = 0;
            $contents[$idx]['vdiscount'] = 0;
            $contents[$idx]['discount_type'] = $newcontents['discount_type'][$idx] ?? $item['discount_type'];
            if (preg_match('/^[0-9]+(\.[0-9]+)*$/', $contents[$idx]['discount'])) {
                $contents[$idx]['pdiscount'] = ($contents[$idx]['discount_type'] == DISCOUNT_PERCENTAGE ? floatval($contents[$idx]['discount']) : 0);
                $contents[$idx]['vdiscount'] = ($contents[$idx]['discount_type'] == DISCOUNT_AMOUNT ? floatval($contents[$idx]['discount']) : 0);
            }
            if ($contents[$idx]['pdiscount'] < 0 || $contents[$idx]['pdiscount'] > 99.99 || $contents[$idx]['vdiscount'] < 0) {
                $error['discount[' . $idx . ']'] = trans('Wrong discount value!');
            }

            $contents[$idx]['name'] = $newcontents['name'][$idx] ?? $item['name'];
            if (!strlen($contents[$idx]['name'])) {
                $error['name[' . $idx . ']'] = trans('Field cannot be empty!');
            }

            $contents[$idx]['tariffid'] = $newcontents['tariffid'][$idx] ?? $item['tariffid'];
            $contents[$idx]['servicetype'] = $newcontents['servicetype'][$idx] ?? $item['servicetype'];
            $contents[$idx]['valuebrutto'] = isset($newcontents['valuebrutto'][$idx]) && $newcontents['valuebrutto'][$idx] != '' ? $newcontents['valuebrutto'][$idx] : $item['valuebrutto'];
            $contents[$idx]['valuenetto'] = isset($newcontents['valuenetto'][$idx]) && $newcontents['valuenetto'][$idx] != '' ? $newcontents['valuenetto'][$idx] : $item['valuenetto'];

            $contents[$idx]['valuebrutto'] = f_round($contents[$idx]['valuebrutto']);
            $contents[$idx]['valuenetto'] = f_round($contents[$idx]['valuenetto']);
            $contents[$idx]['count'] = f_round($contents[$idx]['count'], 3);
            $contents[$idx]['discount'] = f_round($contents[$idx]['discount']);
            $contents[$idx]['discount_type'] = intval($contents[$idx]['discount_type']);
            $contents[$idx]['pdiscount'] = f_round($contents[$idx]['pdiscount']);
            $contents[$idx]['vdiscount'] = f_round($contents[$idx]['vdiscount']);
            $taxvalue = $taxeslist[$contents[$idx]['taxid']]['value'];

            if ($cnote['netflag']) {
                if ((isset($item['deleted']) && $item['deleted']) || empty($contents[$idx]['count'])) {
                    $contents[$idx]['pdiscount'] = 0;
                    $contents[$idx]['vdiscount'] = 0;
                    $contents[$idx]['valuenetto'] = f_round($invoicecontents[$idx]['valuenetto']);
                    $contents[$idx]['cash'] = f_round($invoicecontents[$idx]['s_valuebrutto']);
                    $contents[$idx]['count'] = 0;
                } elseif (empty($contents[$idx]['valuenetto'])) {
                    $contents[$idx]['pdiscount'] = 0;
                    $contents[$idx]['vdiscount'] = 0;
                    $contents[$idx]['valuenetto'] = 0;
                    $contents[$idx]['cash'] = f_round($invoicecontents[$idx]['s_valuebrutto']);
                } elseif (f_round($contents[$idx]['valuenetto']) === f_round($item['valuenetto'])
                    && intval($contents[$idx]['taxid']) === intval($item['taxid'])
                    && f_round($contents[$idx]['count'], 3) === f_round($item['count'], 3)
                    && f_round($contents[$idx]['pdiscount']) === f_round($item['pdiscount'])
                    && f_round($contents[$idx]['vdiscount']) === f_round($item['vdiscount'])
                ) {
                    $contents[$idx]['cash'] = 0;
                    $contents[$idx]['valuenetto'] = f_round($invoicecontents[$idx]['valuenetto']);
                    $contents[$idx]['valuebrutto'] = f_round($invoicecontents[$idx]['valuebrutto']);
                    $contents[$idx]['pdiscount'] = f_round($invoicecontents[$idx]['pdiscount']);
                    $contents[$idx]['vdiscount'] = f_round($invoicecontents[$idx]['vdiscount']);
                    $contents[$idx]['count'] = f_round($invoicecontents[$idx]['count'], 3);
                } else {
                    if (f_round($contents[$idx]['valuenetto']) != f_round($item['valuenetto'])) {
                        $contents[$idx]['pdiscount'] = 0;
                        $contents[$idx]['vdiscount'] = 0;
                    } elseif (f_round($contents[$idx]['count'], 3) != f_round($item['count'], 3)
                        || f_round($contents[$idx]['pdiscount']) != f_round($item['pdiscount'])
                        || f_round($contents[$idx]['vdiscount']) != f_round($item['vdiscount'])
                    ) {
                        if (floatval($invoicecontents[$idx]['pdiscount'])) {
                            $orig_valuenetto = f_round((100 * $invoicecontents[$idx]['valuenetto']) / (100 - $invoicecontents[$idx]['pdiscount']));
                        } else {
                            $orig_valuenetto = f_round($invoicecontents[$idx]['valuenetto'] + $invoicecontents[$idx]['vdiscount']);
                        }
                        $contents[$idx]['valuenetto'] = f_round($orig_valuenetto * (1 - $contents[$idx]['pdiscount'] / 100) - $contents[$idx]['vdiscount']);
                    }

                    $contents[$idx]['s_valuenetto'] = f_round($contents[$idx]['count'] * $contents[$idx]['valuenetto']);
                    $contents[$idx]['s_taxvalue'] = f_round($contents[$idx]['s_valuenetto'] * $taxvalue / 100);
                    $contents[$idx]['s_valuebrutto'] = $contents[$idx]['s_valuenetto'] + $contents[$idx]['s_taxvalue'];
                    $contents[$idx]['cash'] = -1 * f_round($contents[$idx]['s_valuebrutto'] - $invoicecontents[$idx]['s_valuebrutto']);
                }
            } else {
                if ((isset($item['deleted']) && $item['deleted']) || empty($contents[$idx]['count'])) {
                    $contents[$idx]['pdiscount'] = 0;
                    $contents[$idx]['vdiscount'] = 0;
                    $contents[$idx]['valuebrutto'] = f_round($invoicecontents[$idx]['valuebrutto']);
                    $contents[$idx]['cash'] = f_round($invoicecontents[$idx]['s_valuebrutto']);
                    $contents[$idx]['count'] = 0;
                } elseif (empty($contents[$idx]['valuebrutto'])) {
                    $contents[$idx]['pdiscount'] = 0;
                    $contents[$idx]['vdiscount'] = 0;
                    $contents[$idx]['valuebrutto'] = 0;
                    $contents[$idx]['cash'] = f_round($invoicecontents[$idx]['s_valuebrutto']);
                } elseif (f_round($contents[$idx]['valuebrutto']) === f_round($item['valuebrutto'])
                    && intval($contents[$idx]['taxid']) === intval($item['taxid'])
                    && f_round($contents[$idx]['count'], 3) === f_round($item['count'], 3)
                    && f_round($contents[$idx]['pdiscount']) === f_round($item['pdiscount'])
                    && f_round($contents[$idx]['vdiscount']) === f_round($item['vdiscount'])
                ) {
                    $contents[$idx]['cash'] = 0;
                    $contents[$idx]['valuebrutto'] = f_round($invoicecontents[$idx]['valuebrutto']);
                    $contents[$idx]['valuenetto'] = f_round($invoicecontents[$idx]['valuenetto']);
                    $contents[$idx]['pdiscount'] = f_round($invoicecontents[$idx]['pdiscount']);
                    $contents[$idx]['vdiscount'] = f_round($invoicecontents[$idx]['vdiscount']);
                    $contents[$idx]['count'] = f_round($invoicecontents[$idx]['count'], 3);
                } else {
                    if (f_round($contents[$idx]['valuebrutto']) != f_round($item['valuebrutto'])) {
                        $contents[$idx]['pdiscount'] = 0;
                        $contents[$idx]['vdiscount'] = 0;
                    } elseif (f_round($contents[$idx]['count'], 3) != f_round($item['count'], 3)
                        || f_round($contents[$idx]['pdiscount']) != f_round($item['pdiscount'])
                        || f_round($contents[$idx]['vdiscount']) != f_round($item['vdiscount'])
                    ) {
                        if (floatval($invoicecontents[$idx]['pdiscount'])) {
                            $orig_valuebrutto = f_round((100 * $invoicecontents[$idx]['valuebrutto']) / (100 - $invoicecontents[$idx]['pdiscount']));
                        } else {
                            $orig_valuebrutto = f_round($invoicecontents[$idx]['valuebrutto'] + $invoicecontents[$idx]['vdiscount']);
                        }
                        $contents[$idx]['valuebrutto'] = f_round($orig_valuebrutto * (1 - $contents[$idx]['pdiscount'] / 100) - $contents[$idx]['vdiscount']);
                    }

                    $contents[$idx]['s_valuebrutto'] = f_round($contents[$idx]['count'] * $contents[$idx]['valuebrutto']);
                    $contents[$idx]['s_taxvalue'] = round($contents[$idx]['s_valuebrutto'] * $taxvalue / (100 + $taxvalue), 2);
                    $contents[$idx]['s_valuenetto'] = $contents[$idx]['s_valuebrutto'] - $contents[$idx]['s_taxvalue'];
                    $contents[$idx]['cash'] = -1 * f_round($contents[$idx]['s_valuebrutto'] - $invoicecontents[$idx]['s_valuebrutto']);
                }
            }

            $contents[$idx]['cash'] = str_replace(',', '.', $contents[$idx]['cash']);
            $contents[$idx]['valuebrutto'] = str_replace(',', '.', $contents[$idx]['valuebrutto']);
            $contents[$idx]['valuenetto'] = str_replace(',', '.', $contents[$idx]['valuenetto']);
            $contents[$idx]['count'] = str_replace(',', '.', $contents[$idx]['count']);
        }

        $headerDiff = array();
        if (isset($cnote['oldheader']) && isset($cnote['newheader'])) {
            $headerDiff = array_diff_assoc($cnote['oldheader'], $cnote['newheader']);
        }
        if (!isset($cnote['newheader']) || empty($headerDiff)) {
            $contentDiff = false;
            if ($invoicecontents) {
                foreach ($invoicecontents as $item) {
                    $idx = $item['itemid'];

                    $itemContentDiff = ($invoicecontents[$idx]['deleted'] != $contents[$idx]['deleted']
                        || f_round($invoicecontents[$idx]['s_valuebrutto']) !== f_round($contents[$idx]['s_valuebrutto'])
                        || f_round($invoicecontents[$idx]['s_valuenetto']) !== f_round($contents[$idx]['s_valuenetto'])
                        || f_round($invoicecontents[$idx]['valuebrutto']) !== f_round($contents[$idx]['valuebrutto'])
                        || f_round($invoicecontents[$idx]['valuenetto']) !== f_round($contents[$idx]['valuenetto'])
                        || f_round($invoicecontents[$idx]['count'], 3) !== f_round($contents[$idx]['count'], 3)
                        || $invoicecontents[$idx]['content'] != $contents[$idx]['content']
                        || f_round($invoicecontents[$idx]['pdiscount']) !== f_round($contents[$idx]['pdiscount'])
                        || f_round($invoicecontents[$idx]['vdiscount']) !== f_round($contents[$idx]['vdiscount'])
                        || intval($invoicecontents[$idx]['taxid']) !== intval($contents[$idx]['taxid'])
                        || intval($invoicecontents[$idx]['taxcategory']) !== intval($contents[$idx]['taxcategory'])
                        || intval($invoicecontents[$idx]['servicetype']) !== intval($contents[$idx]['servicetype'])
                        || $invoicecontents[$idx]['prodid'] != $contents[$idx]['prodid']
                        || $invoicecontents[$idx]['name'] != $contents[$idx]['name']
                        || intval($invoicecontents[$idx]['tariffid']) !== intval($contents[$idx]['tariffid'])
                    );

                    if ($itemContentDiff) {
                        $contentDiff = true;
                        break;
                    }
                }
            }
            $cnote['content_diff'] = $contentDiff ? 1 : 0;
            if (empty($cnote['content_diff'])) {
                break;
            }
        }

        $cnote['paytime'] = round(($cnote['deadline'] - $cnote['cdate']) / 86400);

        $cnote['currency'] = $cnote['oldcurrency'];
        $cnote['currencyvalue'] = $cnote['oldcurrencyvalue'];

        if (!empty($cnote['numberplanid']) && !$LMS->checkNumberPlanAccess($cnote['numberplanid'])) {
            $error['numberplanid'] = trans('Permission denied!');
        }

        $use_current_customer_data = isset($cnote['use_current_customer_data']);

        $args = array(
            'doctype' => DOC_CNOTE,
            'cdate' => $cnote['cdate'],
            'customerid' => $invoice['customerid'],
            'division' => !empty($cnote['use_current_division']) ? $invoice['current_divisionid']
                : (!empty($invoice['divisionid']) ? $invoice['divisionid'] : null),
            'customertype' => $invoice['customertype'],
            'next' => false,
        );
        $numberplans = $LMS->GetNumberPlans($args);

        if ($numberplans && count($numberplans) && empty($cnote['numberplanid'])) {
            $error['numberplanid'] = trans('Select numbering plan');
        }

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
                $contents[$idx]['servicetype'] = $newcontents['servicetype'][$idx];
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

        $DB->BeginTrans();
        $tables = array('documents', 'numberplans', 'divisions', 'vdivisions',
            'addresses', 'customers', 'customer_addresses');
        if (ConfigHelper::getConfig('database.type') != 'postgres') {
            $tables = array_merge($tables, array('addresses a', 'customers c', 'customer_addresses ca'));
        }

        if ($SYSLOG) {
            $tables = array_merge($tables, array('logmessages', 'logmessagekeys', 'logmessagedata'));
        }
        $DB->LockTables($tables);

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

        $fullnumber = docnumber(array(
            'number' => $cnote['number'],
            'template' => $cnote['numberplanid']
                ? $DB->GetOne('SELECT template FROM numberplans WHERE id = ?', array($cnote['numberplanid']))
                : null,
            'cdate' => $cnote['cdate'],
            'customerid' => $invoice['customerid'],
        ));

        if (!empty($cnote['recipient_address_id']) && $cnote['recipient_address_id'] != -1) {
            $cnote['recipient_address_id'] = $LMS->CopyAddress($cnote['recipient_address_id']);
        } else {
            $cnote['recipient_address_id'] = null;
        }

        if (empty($invoice['post_address_id'])) {
            $invoice['post_address_id'] = $LMS->GetCustomerAddress($invoice['customerid']);
        }
        $invoice['post_address_id'] = $LMS->CopyAddress($invoice['post_address_id']);

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
            'flags' => (empty($cnote['flags'][DOC_FLAG_RECEIPT]) ? 0 : DOC_FLAG_RECEIPT)
                + (empty($cnote['flags'][DOC_FLAG_TELECOM_SERVICE]) || $invoice['customertype'] == CTYPES_COMPANY ? 0 : DOC_FLAG_TELECOM_SERVICE)
                + ($use_current_customer_data
                    ? (isset($customer['flags'][CUSTOMER_FLAG_RELATED_ENTITY]) ? DOC_FLAG_RELATED_ENTITY : 0)
                    : (!empty($invoice['flags'][DOC_FLAG_RELATED_ENTITY]) ? DOC_FLAG_RELATED_ENTITY : 0)
                )
                + (empty($cnote['splitpayment']) ? 0 : DOC_FLAG_SPLIT_PAYMENT)
                + (empty($invoice['netflag']) ? 0 : DOC_FLAG_NET_ACCOUNT),
            SYSLOG::RES_USER => Auth::GetCurrentUser(),
            SYSLOG::RES_CUST => $invoice['customerid'],
            'name' => $use_current_customer_data ? $customer['customername'] : $invoice['name'],
            'address' => $use_current_customer_data ? (($customer['postoffice'] && $customer['postoffice'] != $customer['city'] && $customer['street']
                ? $customer['postoffice'] . ', ' : '') . $customer['address']) : $invoice['address'],
            'ten' => $use_current_customer_data ? $customer['ten'] : $invoice['ten'],
            'ssn' => $use_current_customer_data ? $customer['ssn'] : $invoice['ssn'],
            'zip' => $use_current_customer_data ? $customer['zip'] : $invoice['zip'],
            'city' => $use_current_customer_data ? ($customer['postoffice'] ?: $customer['city'])
                : $invoice['city'],
            SYSLOG::RES_COUNTRY => $use_current_customer_data ? (empty($customer['countryid']) ? null : $customer['countryid'])
                : (empty($invoice['countryid']) ? null : $invoice['countryid']),
            'reference' => $invoice['id'],
            'reason' => $cnote['reason'],
            SYSLOG::RES_DIV => !empty($cnote['use_current_division']) ? $invoice['current_divisionid']
                : (!empty($invoice['divisionid']) ? $invoice['divisionid'] : null),
            'div_name' => $division['name'] ?: '',
            'div_shortname' => $division['shortname'] ?: '',
            'div_address' => $division['address'] ?: '',
            'div_city' => $division['city'] ?: '',
            'div_zip' => $division['zip'] ?: '',
            'div_' . SYSLOG::getResourceKey(SYSLOG::RES_COUNTRY) => !empty($division['countryid']) ? $division['countryid'] : null,
            'div_ten' => $division['ten'] ?: '',
            'div_regon' => $division['regon'] ?: '',
            'div_bank' => $division['bank'] ?: null,
            'div_account' => $division['account'] ?: '',
            'div_inv_header' => $division['inv_header'] ?: '',
            'div_inv_footer' => $division['inv_footer'] ?: '',
            'div_inv_author' => $division['inv_author'] ?: '',
            'div_inv_cplace' => $division['inv_cplace'] ?: '',
            'fullnumber' => $fullnumber,
            'recipient_address_id' => $cnote['recipient_address_id'],
            'post_address_id' => $invoice['post_address_id'],
            'currency' => $cnote['currency'],
            'currencyvalue' => $cnote['currencyvalue'],
            'memo' => $use_current_customer_data ? (empty($customer['documentmemo']) ? null : $customer['documentmemo']) : $invoice['memo'],
        );
        $DB->Execute('INSERT INTO documents (number, numberplanid, type, cdate, sdate, paytime, paytype, flags,
				userid, customerid, name, address, ten, ssn, zip, city, countryid, reference, reason, divisionid,
				div_name, div_shortname, div_address, div_city, div_zip, div_countryid, div_ten, div_regon,
				div_bank, div_account, div_inv_header, div_inv_footer, div_inv_author, div_inv_cplace, fullnumber,
				recipient_address_id, post_address_id, currency, currencyvalue, memo)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
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
            $item['valuenetto'] = str_replace(',', '.', $item['valuenetto']);
            $item['count'] = str_replace(',', '.', $item['count']);
            $item['pdiscount'] = str_replace(',', '.', $item['pdiscount']);
            $item['vdiscount'] = str_replace(',', '.', $item['vdiscount']);

            $args = array(
                SYSLOG::RES_DOC => $id,
                'itemid' => $idx,
                'value' => empty($invoice['netflag']) ? $item['valuebrutto'] : $item['valuenetto'],
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
                    'servicetype' => empty($item['servicetype']) ? null : $item['servicetype'],
                );
                $DB->Execute('INSERT INTO cash (time, userid, value, currency, currencyvalue, taxid, customerid, comment, docid, itemid, servicetype)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
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
            $which = $_GET['which'] ?? 0;

            $SESSION->save('invoiceprint', array('invoice' => $id, 'which' => $which), true);
        } else {
            $SESSION->redirect_to_history_entry();
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

$addresses = $LMS->getCustomerAddresses($invoice['customerid']);
if (isset($invoice['recipient_address'])) {
    $addresses = array_replace(
        array($invoice['recipient_address']['address_id'] => $invoice['recipient_address']),
        $addresses
    );
}
$SMARTY->assign('addresses', $addresses);

$SMARTY->assign('error', $error);
$SMARTY->assign('contents', $contents);
$SMARTY->assign('cnote', $cnote);
$SMARTY->assign('invoice', $invoice);
$SMARTY->assign('refdoc', $invoice);
$SMARTY->assign('taxeslist', $taxeslist);
$SMARTY->assign('numberplanlist', $numberplanlist);
$SMARTY->assign('messagetemplates', $LMS->GetMessageTemplates(TMPL_CNOTE_REASON));
$SMARTY->assign('planDocumentType', DOC_CNOTE);

$total_value = 0;
if (!empty($contents)) {
    foreach ($contents as $item) {
        $total_value += $item['s_valuebrutto'];
    }
}

$SMARTY->assign('suggested_flags', array(
    'splitpayment' => $LMS->isSplitPaymentSuggested(
        $invoice['customerid'],
        date('Y/m/d', $cnote['cdate']),
        $total_value
    ),
    'telecomservice' => true,
));

$SMARTY->display('invoice/invoicenotemodify.html');
