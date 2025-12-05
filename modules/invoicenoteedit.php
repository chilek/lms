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

include(MODULES_DIR . DIRECTORY_SEPARATOR . 'invoiceajax.inc.php');

$taxeslist = $LMS->GetTaxes();
$action = $_GET['action'] ?? '';

if (isset($_GET['id']) && $action == 'edit') {
    if ($LMS->isDocumentPublished($_GET['id']) && !ConfigHelper::checkPrivilege('published_document_modification')) {
        return;
    }

    if ($LMS->isDocumentReferenced($_GET['id'])) {
        return;
    }

    if ($LMS->isArchiveDocument($_GET['id'])) {
        return;
    }

    $cnote = $LMS->GetInvoiceContent($_GET['id']);

    if (!empty($cnote['cancelled'])) {
        return;
    }

    $invoice = array();
    foreach ($cnote['invoice']['content'] as $item) {
        $invoice[$item['itemid']] = $item;
    }
    $cnote['invoice']['content'] = $invoice;

    $SESSION->remove('cnotecontents', true);
    $SESSION->remove('cnote', true);
    $SESSION->remove('cnoteediterror', true);

    $cnotecontents = array();
    foreach ($cnote['content'] as $item) {
        $deleted = ($item['basevalue'] == 0 || $item['value'] == 0 || $item['count'] == 0);
        $nitem['deleted'] = $deleted;
        $nitem['tariffid']  = $item['tariffid'];
        $nitem['name']      = $item['description'];
        $nitem['prodid']    = $item['prodid'];
        if ($deleted) {
            $iitem = $invoice[$item['itemid']];
            $nitem['count'] = $iitem['count'];
            $nitem['discount']  = $iitem['discount'];
            $nitem['pdiscount'] = $iitem['pdiscount'];
            $nitem['vdiscount'] = $iitem['vdiscount'];
            $nitem['content']       = $iitem['content'];
            $nitem['valuenetto']    = $iitem['netprice'];
            $nitem['valuebrutto']   = $iitem['grossprice'];
            $nitem['s_valuenetto']  = $iitem['totalbase'];
            $nitem['s_valuebrutto'] = $iitem['total'];
        } else {
            $nitem['count']     = str_replace(',', '.', $item['count']);
            $pdiscount = floatval($item['pdiscount']);
            $vdiscount = floatval($item['vdiscount']);
            $nitem['discount']  = (!empty($pdiscount) ? str_replace(',', '.', $item['pdiscount']) : str_replace(',', '.', $item['vdiscount']));
            $nitem['discount_type'] = ((!empty($pdiscount) && empty($vdiscount)) || (empty($pdiscount) && empty($vdiscount)) ? DISCOUNT_PERCENTAGE : DISCOUNT_AMOUNT);
            $nitem['pdiscount'] = str_replace(',', '.', $item['pdiscount']);
            $nitem['vdiscount'] = str_replace(',', '.', $item['vdiscount']);
            $nitem['content']       = str_replace(',', '.', $item['content']);
            $nitem['valuenetto']    = str_replace(',', '.', $item['netprice']);
            $nitem['valuebrutto']   = str_replace(',', '.', $item['grossprice']);
            $nitem['s_valuenetto']  = str_replace(',', '.', $item['totalbase']);
            $nitem['s_valuebrutto'] = str_replace(',', '.', $item['total']);
        }
        $nitem['tax']       = isset($taxeslist[$item['taxid']]) ? $taxeslist[$item['taxid']]['label'] : '';
        $nitem['taxid']     = $item['taxid'];
        $nitem['servicetype'] = $item['servicetype'];
        $nitem['taxcategory'] = $item['taxcategory'];
        $nitem['itemid'] = $item['itemid'];
        $cnotecontents[$item['itemid']] = $nitem;
    }

    $cnote['oldcdate'] = $cnote['cdate'];
    $cnote['oldsdate'] = $cnote['sdate'];
    $cnote['olddeadline'] = $cnote['deadline'] = strtotime('today + ' . ($cnote['paytime'] + 1) . ' days', $cnote['cdate']) - 1;
    $cnote['oldnumber'] = $cnote['number'];
    $cnote['oldnumberplanid'] = $cnote['numberplanid'];
    $cnote['oldcustomerid'] = $cnote['customerid'];
    $cnote['oldflags'] = $cnote['flags'];
    $cnote['oldcurrency'] = $cnote['currency'];
    $cnote['oldcurrencyvalue'] = $cnote['currencyvalue'];

    //old header values
    $cnote['oldheader'] = array(
        'sdate' => date("Y/m/d", $cnote['sdate']),
        'flags' => serialize($cnote['flags']),
        'netflag' => $cnote['netflag'],
        'paytype' => $cnote['paytype'],
        'deadline' => date("Y/m/d", intval($cnote['deadline'])),
        'recipient_address_id' => $cnote['recipient_address_id'],
        'recipient_ten' => $cnote['recipient_ten'],
        'use_current_customer_data' => isset($cnote['use_current_customer_data']),
        'reason' => $cnote['reason'],
    );
    $cnote['content_diff'] = 1;

    if (date('Y/m/d', $cnote['cdate']) == date('Y/m/d', $cnote['sdate'])) {
        $cnote['copy-cdate'] = 1;
    }

    $hook_data = array(
        'contents' => $cnotecontents,
        'cnote' => $cnote,
    );
    $hook_data = $LMS->ExecuteHook('invoicenoteedit_init', $hook_data);
    $cnotecontents = $hook_data['contents'];
    $cnote = $hook_data['cnote'];

    $SESSION->save('cnotecontents', $cnotecontents, true);
    $SESSION->save('cnote', $cnote, true);
    $SESSION->save('cnoteid', $cnote['id'], true);
}

$SESSION->restore('cnotecontents', $contents, true);
$SESSION->restore('cnote', $cnote, true);
$SESSION->restore('cnoteediterror', $error, true);

$ntempl = docnumber(array(
    'number' => $cnote['number'],
    'template' => $cnote['template'],
    'cdate' => $cnote['cdate'],
    'customerid' => $cnote['customerid'],
));
$layout['pagetitle'] = trans('Credit Note for Invoice Edit: $a', $ntempl);

switch ($action) {
    case 'deletepos':
        if ($cnote['closed']) {
            break;
        }
        $contents[$_GET['itemid']]['deleted'] = true;
        break;

    case 'recoverpos':
        if ($cnote['closed']) {
            break;
        }
        $contents[$_GET['itemid']]['deleted'] = false;
        break;

    case 'setheader':
        $oldcdate = $cnote['oldcdate'];
        $oldsdate = $cnote['oldsdate'];
        $oldnumber = $cnote['oldnumber'];
        $oldnumberplanid = $cnote['oldnumberplanid'];
        $oldcustomerid = $cnote['oldcustomerid'];
        $oldflags = $cnote['oldflags'];
        $oldcurrency = $cnote['oldcurrency'];
        $oldcurrencyvalue = $cnote['oldcurrencyvalue'];
        $oldHeader = $cnote['oldheader'];

        $oldcnote = $cnote;
        $cnote = null;
        $error = null;

        if ($cnote = $_POST['cnote']) {
            foreach ($cnote as $key => $val) {
                $cnote[$key] = $val;
            }
        }

        if (!isset($cnote['splitpayment'])) {
            $cnote['splitpayment'] = 0;
        }

        if (!isset($cnote['flags'][DOC_FLAG_RECEIPT])) {
            $cnote['flags'][DOC_FLAG_RECEIPT] = 0;
        }

        if (!isset($cnote['flags'][DOC_FLAG_TELECOM_SERVICE])) {
            $cnote['flags'][DOC_FLAG_TELECOM_SERVICE] = 0;
        }

        $cnote['oldcdate'] = $oldcdate;
        $cnote['oldsdate'] = $oldsdate;
        $cnote['oldnumber'] = $oldnumber;
        $cnote['oldnumberplanid'] = $oldnumberplanid;
        $cnote['oldcustomerid'] = $oldcustomerid;
        $cnote['oldflags'] = $oldflags;
        $cnote['oldcurrency'] = $oldcurrency;
        $cnote['oldcurrencyvalue'] = $oldcurrencyvalue;

        $invoice = $oldcnote['invoice'];

        $SESSION->restore('cnoteid', $cnote['id'], true);

        $currtime = time();

        if (ConfigHelper::checkPrivilege('invoice_consent_date')) {
            if ($cnote['cdate']) {
                [$year, $month, $day] = explode('/', $cnote['cdate']);
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
                [$syear, $smonth, $sday] = explode('/', $cnote['sdate']);
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
            $cnote['sdate'] = $cnote['oldsdate'];
        }

        if ($cnote['deadline']) {
            $deadline = strtotime($cnote['deadline'] . ' + 1 day') - 1;
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

        if (($cnote['numberplanid'] && !$LMS->checkNumberPlanAccess($cnote['numberplanid']))
            || ($cnote['oldnumberplanid'] && !$LMS->checkNumberPlanAccess($cnote['oldnumberplanid']))) {
            $cnote['numberplanid'] = $cnote['oldnumberplanid'];
        }

        $use_current_customer_data = isset($cnote['use_current_customer_data']);

        if ($use_current_customer_data) {
            $customer = $LMS->GetCustomer($cnote['customerid'], true);
        }

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
            'recipient_address_id' => $_POST['cnote[recipient_address_id]'],
            'use_current_customer_data' => isset($cnote['use_current_customer_data']),
            'reason' => $cnote['reason'],
        );
        $cnote['content_diff'] = 1;

        //old header values
        $cnote['oldheader'] = $oldHeader;

        $divisionid = $use_current_customer_data ? $customer['divisionid'] : $cnote['divisionid'];

        $args = array(
            'doctype' => DOC_CNOTE,
            'cdate' => $cnote['cdate'],
            'customerid' => $cnote['customerid'],
            'division' => $divisionid,
            'customertype' => $cnote['customertype'],
            'next' => false,
        );
        $numberplans = $LMS->GetNumberPlans($args);

        if ($numberplans && count($numberplans) && empty($cnote['numberplanid']) && $cnote['numberplanid'] != 0) {
            $error['numberplanid'] = trans('Select numbering plan');
        }

        if ($cnote['number']) {
            if (!preg_match('/^[0-9]+$/', $cnote['number'])) {
                $error['number'] = trans('Credit note number must be integer!');
            } elseif ((
                    $cnote['oldcdate'] != $cnote['cdate']
                    || $cnote['oldnumber'] != $cnote['number']
                    || $cnote['oldnumberplanid'] != $cnote['numberplanid']
                    || ($cnote['oldcustomerid'] != $cnote['customerid'] && preg_match('/%[0-9]*C/', $cnote['template']))
                )
                && ($docid = $LMS->DocumentExists(array(
                    'number' => $cnote['number'],
                    'doctype' => DOC_CNOTE,
                    'planid' => $cnote['numberplanid'],
                    'cdate' => $cnote['cdate'],
                    'customerid' => $cnote['customerid'],
                    ))) > 0
                && $docid != $cnote['id']
            ) {
                $error['number'] = trans('Credit note number $a already exists!', $cnote['number']);
            }
        }

        $cnote = array_merge($oldcnote, $cnote);
        break;

    case 'save':
        if (empty($contents)) {
            break;
        }

        $error = array();

        $SESSION->restore('cnoteid', $cnote['id'], true);
        $cnote['type'] = DOC_CNOTE;

        $currtime = time();

        if (ConfigHelper::checkPrivilege('invoice_consent_date')) {
            $cdate = $cnote['cdate'] ?: $currtime;
        } else {
            $cdate = $cnote['oldcdate'];
        }

        if (ConfigHelper::checkPrivilege('invoice_sale_date')) {
            $sdate = $cnote['sdate'] ?: $currtime;
        } else {
            $sdate = $cnote['oldsdate'];
        }

        $cnote['currency'] = $cnote['oldcurrency'];
        $cnote['currencyvalue'] = $cnote['oldcurrencyvalue'];

        $deadline = $cnote['deadline'] ?: $currtime;
        $paytime = $cnote['paytime'] = round(($cnote['deadline'] - $cnote['cdate']) / 86400);
        $iid   = $cnote['id'];

        if ($deadline < $cdate) {
            break;
        }

        $invoicecontents = $cnote['invoice']['content'];
        $cnotecontents = $cnote['content'];
        $oldcnotecontents = $contents;
        $newcontents = r_trim($_POST);

        foreach ($contents as $idx => $item) {
            if (ConfigHelper::checkConfig('phpui.tax_category_required')
                && (empty($newcontents['taxcategory'][$idx]))) {
                $error['taxcategory[' . $idx . ']'] = trans('Tax category selection is required!');
            }

            $contents[$idx]['taxid'] = $newcontents['taxid'][$idx] ?? $item['taxid'];
            $contents[$idx]['taxcategory'] = $newcontents['taxcategory'][$idx] ?? $item['taxcategory'];
            $contents[$idx]['servicetype'] = $newcontents['servicetype'][$idx] ?? $item['servicetype'];
            $contents[$idx]['prodid'] = $newcontents['prodid'][$idx] ?? $item['prodid'];
            $contents[$idx]['content'] = $newcontents['content'][$idx] ?? $item['content'];
            $contents[$idx]['count'] = $newcontents['count'][$idx] ?? $item['count'];

            $contents[$idx]['discount'] = str_replace(',', '.', $newcontents['discount'][$idx] ?? $item['discount']);
            $contents[$idx]['pdiscount'] = 0;
            $contents[$idx]['vdiscount'] = 0;
            $contents[$idx]['discount_type'] = $newcontents['discount_type'][$idx] ?? $item['discount_type'];
            if (preg_match('/^[0-9]+(\.[0-9]+)*$/', $contents[$idx]['discount'])) {
                $contents[$idx]['pdiscount'] = (!empty($contents[$idx]['discount_type']) && $contents[$idx]['discount_type'] == DISCOUNT_PERCENTAGE ? floatval($contents[$idx]['discount']) : 0);
                $contents[$idx]['vdiscount'] = (!empty($contents[$idx]['discount_type']) && $contents[$idx]['discount_type'] == DISCOUNT_AMOUNT ? floatval($contents[$idx]['discount']) : 0);
            }
            if ($contents[$idx]['pdiscount'] < 0 || $contents[$idx]['pdiscount'] > 99.99 || $contents[$idx]['vdiscount'] < 0) {
                $error['discount[' . $idx . ']'] = trans('Wrong discount value!');
            }

            $contents[$idx]['name'] = $newcontents['name'][$idx] ?? $item['name'];

            if (!strlen($contents[$idx]['name'])) {
                $error['name[' . $idx . ']'] = trans('Field cannot be empty!');
            }

            $contents[$idx]['tariffid'] = $newcontents['tariffid'][$idx] ?? $item['tariffid'];
            if ($cnote['netflag']) {
                if ($newcontents['valuenetto'][$idx] == '') {
                    $error['valuenetto[' . $idx . ']'] = trans('Wrong value!');
                }
            } else {
                if ($newcontents['valuebrutto'][$idx] == '') {
                    $error['valuebrutto[' . $idx . ']'] = trans('Wrong value!');
                }
            }
            $contents[$idx]['valuebrutto'] = $newcontents['valuebrutto'][$idx] != '' ? $newcontents['valuebrutto'][$idx] : $item['valuebrutto'];
            $contents[$idx]['valuenetto'] = $newcontents['valuenetto'][$idx] != '' ? $newcontents['valuenetto'][$idx] : $item['valuenetto'];
            $contents[$idx]['valuebrutto'] = f_round($contents[$idx]['valuebrutto']);
            $contents[$idx]['valuenetto'] = f_round($contents[$idx]['valuenetto']);
            $contents[$idx]['count'] = f_round($contents[$idx]['count'], 3);
            $contents[$idx]['discount_type'] = intval($contents[$idx]['discount_type']);
            $contents[$idx]['pdiscount'] = f_round($contents[$idx]['pdiscount']);
            $contents[$idx]['vdiscount'] = f_round($contents[$idx]['vdiscount']);
            $taxvalue = $taxeslist[$contents[$idx]['taxid']]['value'];

            if ($cnote['netflag']) {
                if ((isset($item['deleted']) && $item['deleted']) || empty($contents[$idx]['count']) || empty($contents[$idx]['valuenetto'])) {
                    $contents[$idx]['pdiscount'] = 0;
                    $contents[$idx]['vdiscount'] = 0;
                    $contents[$idx]['valuenetto'] = f_round($invoicecontents[$idx]['basevalue']);
                    $contents[$idx]['cash'] = f_round($invoicecontents[$idx]['total']);
                    $contents[$idx]['count'] = 0;
                } elseif (f_round($contents[$idx]['valuenetto']) != f_round($item['valuenetto'])
                    || f_round($contents[$idx]['count'], 3) != f_round($item['count'], 3)
                    || f_round($contents[$idx]['pdiscount']) != f_round($item['pdiscount'])
                    || f_round($contents[$idx]['vdiscount']) != f_round($item['vdiscount'])
                ) {
                    if (f_round($contents[$idx]['count'], 3) != f_round($item['count'], 3)
                        || f_round($contents[$idx]['pdiscount']) != f_round($item['pdiscount'])
                        || f_round($contents[$idx]['vdiscount']) != f_round($item['vdiscount'])
                    ) {
                        if (floatval($invoicecontents[$idx]['pdiscount'])) {
                            $orig_valuenetto = f_round((100 * $invoicecontents[$idx]['basevalue']) / (100 - $invoicecontents[$idx]['pdiscount']));
                        } else {
                            $orig_valuenetto = f_round($invoicecontents[$idx]['basevalue'] + $invoicecontents[$idx]['vdiscount']);
                        }
                        $contents[$idx]['valuenetto'] = f_round($orig_valuenetto * (1 - $contents[$idx]['pdiscount'] / 100) - $contents[$idx]['vdiscount']);
                    } else {
                        $contents[$idx]['pdiscount'] = 0;
                        $contents[$idx]['vdiscount'] = 0;
                    }

                    $contents[$idx]['s_valuenetto'] = f_round($contents[$idx]['count'] * $contents[$idx]['valuenetto']);
                    $contents[$idx]['s_taxvalue'] = f_round($contents[$idx]['s_valuenetto'] * $taxvalue / 100);
                    $contents[$idx]['s_valuebrutto'] = $contents[$idx]['s_valuenetto'] + $contents[$idx]['s_taxvalue'];
                    $contents[$idx]['cash'] = -1 * f_round($contents[$idx]['s_valuebrutto'] - $invoicecontents[$idx]['total']);
                } else {
                    $contents[$idx]['cash'] = -1 * f_round($contents[$idx]['s_valuebrutto'] - $invoicecontents[$idx]['total']);
                    $contents[$idx]['valuenetto'] = f_round($cnotecontents[$idx]['basevalue']);
                    $contents[$idx]['pdiscount'] = f_round($cnotecontents[$idx]['pdiscount']);
                    $contents[$idx]['vdiscount'] = f_round($cnotecontents[$idx]['vdiscount']);
                    $contents[$idx]['count'] = f_round($cnotecontents[$idx]['count'], 3);
                }
            } else {
                if ((isset($item['deleted']) && $item['deleted']) || empty($contents[$idx]['count']) || empty($contents[$idx]['valuebrutto'])) {
                    $contents[$idx]['pdiscount'] = 0;
                    $contents[$idx]['vdiscount'] = 0;
                    $contents[$idx]['valuebrutto'] = f_round($invoicecontents[$idx]['value']);
                    $contents[$idx]['cash'] = f_round($invoicecontents[$idx]['total']);
                    $contents[$idx]['count'] = 0;
                } elseif (f_round($contents[$idx]['valuebrutto']) != f_round($item['valuebrutto'])
                    || f_round($contents[$idx]['count'], 3) != f_round($item['count'], 3)
                    || f_round($contents[$idx]['pdiscount']) != f_round($item['pdiscount'])
                    || f_round($contents[$idx]['vdiscount']) != f_round($item['vdiscount'])
                ) {
                    if (f_round($contents[$idx]['count'], 3) != f_round($item['count'], 3)
                        || f_round($contents[$idx]['pdiscount']) != f_round($item['pdiscount'])
                        || f_round($contents[$idx]['vdiscount']) != f_round($item['vdiscount'])
                    ) {
                        if (floatval($invoicecontents[$idx]['pdiscount'])) {
                            $orig_valuebrutto = f_round((100 * $invoicecontents[$idx]['value']) / (100 - $invoicecontents[$idx]['pdiscount']));
                        } else {
                            $orig_valuebrutto = f_round($invoicecontents[$idx]['value'] + $invoicecontents[$idx]['vdiscount']);
                        }
                        $contents[$idx]['valuebrutto'] = f_round($orig_valuebrutto * (1 - $contents[$idx]['pdiscount'] / 100) - $contents[$idx]['vdiscount']);
                    } else {
                        $contents[$idx]['pdiscount'] = 0;
                        $contents[$idx]['vdiscount'] = 0;
                    }

                    $contents[$idx]['s_valuebrutto'] = f_round($contents[$idx]['count'] * $contents[$idx]['valuebrutto']);
                    $contents[$idx]['s_taxvalue'] = round($contents[$idx]['s_valuebrutto'] * $taxvalue / (100 + $taxvalue), 2);
                    $contents[$idx]['s_valuenetto'] = $contents[$idx]['s_valuebrutto'] - $contents[$idx]['s_taxvalue'];
                    $contents[$idx]['cash'] = -1 * f_round($contents[$idx]['s_valuebrutto'] - $invoicecontents[$idx]['total']);
                } else {
                    $contents[$idx]['cash'] = -1 * f_round($contents[$idx]['s_valuebrutto'] - $invoicecontents[$idx]['total']);
                    $contents[$idx]['valuebrutto'] = f_round($cnotecontents[$idx]['value']);
                    $contents[$idx]['pdiscount'] = f_round($cnotecontents[$idx]['pdiscount']);
                    $contents[$idx]['vdiscount'] = f_round($cnotecontents[$idx]['vdiscount']);
                    $contents[$idx]['count'] = f_round($cnotecontents[$idx]['count'], 3);
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
            foreach ($oldcnotecontents as $item) {
                $idx = $item['itemid'];

                $itemContentDiff = ($oldcnotecontents[$idx]['deleted'] != $contents[$idx]['deleted']
                    || f_round($oldcnotecontents[$idx]['s_valuebrutto']) !== f_round($contents[$idx]['s_valuebrutto'])
                    || f_round($oldcnotecontents[$idx]['s_valuenetto']) !== f_round($contents[$idx]['s_valuenetto'])
                    || f_round($oldcnotecontents[$idx]['count'], 3) !== f_round($contents[$idx]['count'], 3)
                    || $oldcnotecontents[$idx]['content'] != $contents[$idx]['content']
                    || f_round($oldcnotecontents[$idx]['discount']) !== f_round($contents[$idx]['discount'])
                    || f_round($oldcnotecontents[$idx]['discount_type']) !== f_round($contents[$idx]['discount_type'])
                    || intval($oldcnotecontents[$idx]['taxid']) !== intval($contents[$idx]['taxid'])
                    || intval($oldcnotecontents[$idx]['taxcategory']) !== intval($contents[$idx]['taxcategory'])
                    || intval($oldcnotecontents[$idx]['servicetype']) !== intval($contents[$idx]['servicetype'])
                    || $oldcnotecontents[$idx]['prodid'] != $contents[$idx]['prodid']
                    || $oldcnotecontents[$idx]['name'] != $contents[$idx]['name']
                    || intval($oldcnotecontents[$idx]['tariffid']) !== intval($contents[$idx]['tariffid'])
                );

                if ($itemContentDiff) {
                    $contentDiff = true;
                    break;
                }
            }
            $cnote['content_diff'] = $contentDiff ? 1 : 0;
            if (empty($cnote['content_diff'])) {
                break;
            }
        }

        if (($cnote['numberplanid'] && !$LMS->checkNumberPlanAccess($cnote['numberplanid']))
            || ($cnote['oldnumberplanid'] && !$LMS->checkNumberPlanAccess($cnote['oldnumberplanid']))) {
            $cnote['numberplanid'] = $cnote['oldnumberplanid'];
            $error['numberplanid'] = trans('Persmission denied!');
        }

        $use_current_customer_data = isset($cnote['use_current_customer_data']);

        if ($use_current_customer_data) {
            $customer = $LMS->GetCustomer($cnote['customerid'], true);
        }

        $args = array(
            'doctype' => DOC_CNOTE,
            'cdate' => $cnote['cdate'],
            'customerid' => $cnote['customerid'],
            'division' => $use_current_customer_data ? $customer['divisionid'] : $cnote['divisionid'],
            'customertype' => $cnote['customertype'],
            'next' => false,
        );
        $numberplans = $LMS->GetNumberPlans($args);

        if ($numberplans && count($numberplans) && empty($cnote['numberplanid'])) {
            $error['numberplanid'] = trans('Select numbering plan');
        }

        $hook_data = array(
            'contents' => $contents,
            'cnote' => $cnote,
        );
        $hook_data = $LMS->ExecuteHook('invoicenoteedit_save_validation', $hook_data);
        if (isset($hook_data['error']) && is_array($hook_data['error'])) {
            $error = array_merge($error, $hook_data['error']);
        }

        if (!empty($error)) {
            foreach ($contents as $idx => $item) {
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

        // updates customer recipient address stored in document
        $prev_rec_addr = $DB->GetOne('SELECT recipient_address_id FROM documents WHERE id = ?', array($iid));
        if (empty($prev_rec_addr)) {
            $prev_rec_addr = -1;
        }

        if ($prev_rec_addr != $cnote['recipient_address_id']) {
            if ($prev_rec_addr > 0) {
                $DB->Execute('DELETE FROM addresses WHERE id = ?', array($prev_rec_addr));
            }

            if ($cnote['recipient_address_id'] > 0) {
                $recipient_ten = $LMS->getRecipientTen($cnote['recipient_address_id']);
                $DB->Execute(
                    'UPDATE documents SET recipient_address_id = ?, recipient_ten = ? WHERE id = ?',
                    array(
                        $LMS->CopyAddress($cnote['recipient_address_id']),
                        $recipient_ten,
                        $iid,
                    )
                );
            }
        }

        if ($use_current_customer_data) {
            $LMS->UpdateDocumentPostAddress($iid, $cnote['customerid']);
        }

        $division = $LMS->GetDivision($use_current_customer_data ? $customer['divisionid'] : $cnote['divisionid']);

        if (!$cnote['number']) {
            $cnote['number'] = $LMS->GetNewDocumentNumber(array(
                'doctype' => DOC_CNOTE,
                'planid' => $cnote['numberplanid'],
                'cdate' => $cnote['cdate'],
                'customerid' => $cnote['customerid'],
            ));
        } else {
            if (!preg_match('/^[0-9]+$/', $cnote['number'])) {
                $error['number'] = trans('Credit note number must be integer!');
            } elseif (($cnote['cdate'] != $cnote['oldcdate'] || $cnote['number'] != $cnote['oldnumber']
                || ($cnote['oldnumber'] == $cnote['number'] && $cnote['oldcustomerid'] != $cnote['customerid'])
                || $cnote['numberplanid'] != $cnote['oldnumberplanid']) && ($docid = $LMS->DocumentExists(array(
                    'number' => $cnote['number'],
                    'doctype' => DOC_CNOTE,
                    'planid' => $cnote['numberplanid'],
                    'cdate' => $cnote['cdate'],
                    'customerid' => $cnote['customerid'],
                ))) > 0 && $docid != $iid) {
                $error['number'] = trans('Credit note number $a already exists!', $cnote['number']);
            }

            if ($error) {
                $cnote['number'] = $LMS->GetNewDocumentNumber(array(
                    'doctype' => DOC_CNOTE,
                    'planid' => $cnote['numberplanid'],
                    'cdate' => $cnote['cdate'],
                    'customerid' => $cnote['customerid'],
                ));
                $error = null;
            }
        }

        $args = array(
            'cdate' => $cdate,
            'sdate' => $sdate,
            'paytime' => $paytime,
            'paytype' => $cnote['paytype'],
            'flags' => (empty($cnote['flags'][DOC_FLAG_RECEIPT]) ? 0 : DOC_FLAG_RECEIPT)
                + (empty($cnote['flags'][DOC_FLAG_TELECOM_SERVICE]) || $customer['type'] == CTYPES_COMPANY ? 0 : DOC_FLAG_TELECOM_SERVICE)
                + ($use_current_customer_data
                    ? (isset($customer['flags'][CUSTOMER_FLAG_RELATED_ENTITY]) ? DOC_FLAG_RELATED_ENTITY : 0)
                    : (!empty($cnote['oldflags'][DOC_FLAG_RELATED_ENTITY]) ? DOC_FLAG_RELATED_ENTITY : 0)
                )
                + (empty($cnote['splitpayment']) ? 0 : DOC_FLAG_SPLIT_PAYMENT)
                + (empty($cnote['netflag']) ? 0 : DOC_FLAG_NET_ACCOUNT),
            SYSLOG::RES_CUST => $cnote['customerid'],
            'name' => $use_current_customer_data ? $customer['customername'] : $cnote['name'],
            'address' => $use_current_customer_data ? (($customer['postoffice'] && $customer['postoffice'] != $customer['city'] && $customer['street']
                ? $customer['postoffice'] . ', ' : '') . $customer['address']) : $cnote['address'],
            'ten' => $use_current_customer_data ? $customer['ten'] : $cnote['ten'],
            'ssn' => $use_current_customer_data ? $customer['ssn'] : $cnote['ssn'],
            'zip' => $use_current_customer_data ? $customer['zip'] : $cnote['zip'],
            'city' => $use_current_customer_data ? ($customer['postoffice'] ?: $customer['city'])
                : $cnote['city'],
            SYSLOG::RES_COUNTRY => $use_current_customer_data ? (empty($customer['countryid']) ? null : $customer['countryid'])
                : (empty($cnote['countryid']) ? null : $cnote['countryid']),
            'reason' => $cnote['reason'],
            SYSLOG::RES_DIV => $use_current_customer_data ? $customer['divisionid'] : $cnote['divisionid'],
            'div_name' => ($division['name'] ?: ''),
            'div_shortname' => ($division['shortname'] ?: ''),
            'div_address' => ($division['address'] ?: ''),
            'div_city' => ($division['city'] ?: ''),
            'div_zip' => ($division['zip'] ?: ''),
            'div_' . SYSLOG::getResourceKey(SYSLOG::RES_COUNTRY) => ($division['countryid'] ?: null),
            'div_ten'=> ($division['ten'] ?: ''),
            'div_regon' => ($division['regon'] ?: ''),
            'div_bank' => $division['bank'] ?: null,
            'div_account' => ($division['account'] ?: ''),
            'div_inv_header' => ($division['inv_header'] ?: ''),
            'div_inv_footer' => ($division['inv_footer'] ?: ''),
            'div_inv_author' => ($division['inv_author'] ?: ''),
            'div_inv_cplace' => ($division['inv_cplace'] ?: ''),
            'currency' => $cnote['currency'],
            'currencyvalue' => $cnote['currencyvalue'],
            'memo' => $use_current_customer_data ? (empty($customer['documentmemo']) ? null : $customer['documentmemo']) : $cnote['memo'],
        );
        $args['number'] = $cnote['number'];
        $args['fullnumber'] = docnumber(array(
            'number' => $cnote['number'],
            'template' => $cnote['numberplanid']
                ? $DB->GetOne('SELECT template FROM numberplans WHERE id = ?', array($cnote['numberplanid']))
                : null,
            'cdate' => $cnote['cdate'],
            'customerid' => $cnote['customerid'],
        ));
        $args[SYSLOG::RES_NUMPLAN] = !empty($cnote['numberplanid']) ? $cnote['numberplanid'] : null;
        $args[SYSLOG::RES_DOC] = $iid;

        $DB->Execute('UPDATE documents SET cdate = ?, sdate = ?, paytime = ?, paytype = ?, flags = ?, customerid = ?,
				name = ?, address = ?, ten = ?, ssn = ?, zip = ?, city = ?, countryid = ?, reason = ?, divisionid = ?,
				div_name = ?, div_shortname = ?, div_address = ?, div_city = ?, div_zip = ?, div_countryid = ?,
				div_ten = ?, div_regon = ?, div_bank = ?, div_account = ?, div_inv_header = ?, div_inv_footer = ?,
				div_inv_author = ?, div_inv_cplace = ?, currency = ?, currencyvalue = ?, memo = ?,
				number = ?, fullnumber = ?, numberplanid = ?
				WHERE id = ?', array_values($args));
        if ($SYSLOG) {
            $SYSLOG->AddMessage(
                SYSLOG::RES_DOC,
                SYSLOG::OPER_UPDATE,
                $args,
                array('div_' . SYSLOG::getResourceKey(SYSLOG::RES_COUNTRY))
            );
        }

        if (!$cnote['closed']) {
            if ($SYSLOG) {
                $cashids = $DB->GetCol('SELECT id FROM cash WHERE docid = ?', array($iid));
                foreach ($cashids as $cashid) {
                    $args = array(
                        SYSLOG::RES_CASH => $cashid,
                        SYSLOG::RES_DOC => $iid,
                        SYSLOG::RES_CUST => $cnote['customerid'],
                    );
                    $SYSLOG->AddMessage(SYSLOG::RES_CASH, SYSLOG::OPER_DELETE, $args);
                }
                $itemids = $DB->GetCol('SELECT itemid FROM invoicecontents WHERE docid = ?', array($iid));
                foreach ($itemids as $itemid) {
                    $args = array(
                        SYSLOG::RES_DOC => $iid,
                        SYSLOG::RES_CUST => $cnote['customerid'],
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
                    'value' => (empty($cnote['netflag']) ? str_replace(',', '.', $item['valuebrutto'])
                        : str_replace(',', '.', $item['valuenetto'])),
                    SYSLOG::RES_TAX => $item['taxid'],
                    'taxcategory' => $item['taxcategory'],
                    'prodid' => $item['prodid'],
                    'content' => $item['content'],
                    'count' => $item['count'],
                    'pdiscount' => str_replace(',', '.', $item['pdiscount']),
                    'vdiscount' => str_replace(',', '.', $item['vdiscount']),
                    'name' => $item['name'],
                    SYSLOG::RES_TARIFF => empty($item['tariffid']) ? null : $item['tariffid'],
                );
                $DB->Execute('INSERT INTO invoicecontents (docid, itemid, value,
					taxid, taxcategory, prodid, content, count, pdiscount, vdiscount, description, tariffid)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
                if ($SYSLOG) {
                    $args[SYSLOG::RES_CUST] = $cnote['customerid'];
                    $SYSLOG->AddMessage(SYSLOG::RES_INVOICECONT, SYSLOG::OPER_ADD, $args);
                }

                $LMS->AddBalance(array(
                    'time' => $cdate,
                    'value' => $item['cash'],
                    'currency' => $cnote['currency'],
                    'currencyvalue' => $cnote['currencyvalue'],
                    'taxid' => $item['taxid'],
                    'customerid' => $cnote['customerid'],
                    'comment' => $item['name'],
                    'docid' => $iid,
                    'itemid' => $itemid,
                    'servicetype' => $item['servicetype'],
                ));
            }
        } else {
            if ($SYSLOG) {
                $cashids = $DB->GetCol('SELECT id FROM cash WHERE docid = ?', array($iid));
                foreach ($cashids as $cashid) {
                    $args = array(
                        SYSLOG::RES_CASH => $cashid,
                        SYSLOG::RES_DOC => $iid,
                        SYSLOG::RES_CUST => $cnote['customerid'],
                    );
                    $SYSLOG->AddMessage(SYSLOG::RES_CASH, SYSLOG::OPER_UPDATE, $args);
                }
            }
            $DB->Execute(
                'UPDATE cash SET customerid = ? WHERE docid = ?',
                array($cnote['customerid'], $iid)
            );
        }

        $DB->CommitTrans();

        if (isset($_GET['print'])) {
            $which = $_GET['which'] ?? 0;

            $SESSION->save('invoiceprint', array('invoice' => $iid, 'which' => $which), true);
        }

        $SESSION->redirect('?m=invoicelist');
        break;
}

$SESSION->save('cnote', $cnote, true);
$SESSION->save('cnotecontents', $contents, true);
$SESSION->save('cnoteediterror', $error, true);

if ($action && !$error) {
    // redirect needed because we don't want to destroy contents of invoice in order of page refresh
    $SESSION->redirect('?m=invoicenoteedit');
}

$hook_data = array(
    'contents' => $contents,
    'cnote' => $cnote,
);
$hook_data = $LMS->ExecuteHook('invoicenoteedit_before_display', $hook_data);
$contents = $hook_data['contents'];
$cnote = $hook_data['cnote'];

$addresses = $LMS->getCustomerAddresses($cnote['customerid']);
if (isset($cnote['recipient_address'])) {
    $addresses = array_replace(
        array($cnote['recipient_address']['address_id'] => $cnote['recipient_address']),
        $addresses
    );
}
$SMARTY->assign('addresses', $addresses);

$SMARTY->assign('error', $error);
$SMARTY->assign('contents', $contents);
$SMARTY->assign('cnote', $cnote);
$SMARTY->assign('refdoc', $cnote);
$SMARTY->assign('taxeslist', $taxeslist);

$args = array(
    'doctype' => DOC_CNOTE,
    'cdate' => $cnote['cdate'],
    'customerid' => $cnote['customerid'],
    'division' => $DB->GetOne('SELECT divisionid FROM customers WHERE id = ?', array($cnote['customerid'])),
    'customertype' => $cnote['customertype'],
);
$numberplanlist = $LMS->GetNumberPlans($args);
if (!$numberplanlist) {
    $numberplanlist = $LMS->getSystemDefaultNumberPlan($args);
}

$SMARTY->assign('numberplanlist', $numberplanlist);
$SMARTY->assign('planDocumentType', DOC_CNOTE);
$SMARTY->assign('messagetemplates', $LMS->GetMessageTemplates(TMPL_CNOTE_REASON));

$total_value = 0;
if (!empty($contents)) {
    foreach ($contents as $item) {
        $total_value += $item['s_valuebrutto'];
    }
}

$SMARTY->assign('suggested_flags', array(
    'splitpayment' => $LMS->isSplitPaymentSuggested(
        $cnote['customerid'],
        date('Y/m/d', $cnote['cdate']),
        $total_value
    ),
    'telecomservice' => true,
));

$SMARTY->display('invoice/invoicenotemodify.html');
