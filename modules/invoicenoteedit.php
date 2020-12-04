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

$taxeslist = $LMS->GetTaxes();
$action = isset($_GET['action']) ? $_GET['action'] : '';

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
        $deleted = $item['value'] == 0;
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
            $nitem['valuenetto']    = $iitem['basevalue'];
            $nitem['valuebrutto']   = $iitem['value'];
            $nitem['s_valuenetto']  = $iitem['totalbase'];
            $nitem['s_valuebrutto'] = $iitem['total'];
        } else {
            $nitem['count']     = str_replace(',', '.', $item['count']);
            $pdiscount = floatval($item['pdiscount']);
            $nitem['discount']  = (!empty($pdiscount) ? str_replace(',', '.', $item['pdiscount']) : str_replace(',', '.', $item['vdiscount']));
            $nitem['discount_type'] = (!empty($pdiscount) ? DISCOUNT_PERCENTAGE : DISCOUNT_AMOUNT);
            $nitem['pdiscount'] = str_replace(',', '.', $item['pdiscount']);
            $nitem['vdiscount'] = str_replace(',', '.', $item['vdiscount']);
            $nitem['content']       = str_replace(',', '.', $item['content']);
            $nitem['valuenetto']    = str_replace(',', '.', $item['basevalue']);
            $nitem['valuebrutto']   = str_replace(',', '.', $item['value']);
            $nitem['s_valuenetto']  = str_replace(',', '.', $item['totalbase']);
            $nitem['s_valuebrutto'] = str_replace(',', '.', $item['total']);
        }
        $nitem['tax']       = isset($taxeslist[$item['taxid']]) ? $taxeslist[$item['taxid']]['label'] : '';
        $nitem['taxid']     = $item['taxid'];
        $nitem['taxcategory'] = $item['taxcategory'];
        $cnotecontents[$item['itemid']] = $nitem;
    }
    $SESSION->save('cnotecontents', $cnotecontents, true);

    $cnote['oldcdate'] = $cnote['cdate'];
    $cnote['oldsdate'] = $cnote['sdate'];
    $cnote['olddeadline'] = $cnote['deadline'] = $cnote['cdate'] + $cnote['paytime'] * 86400;
    $cnote['oldnumber'] = $cnote['number'];
    $cnote['oldnumberplanid'] = $cnote['numberplanid'];
    $cnote['oldcustomerid'] = $cnote['customerid'];
    $cnote['oldflags'] = $cnote['flags'];
    $cnote['oldcurrency'] = $cnote['currency'];
    $cnote['oldcurrencyvalue'] = $cnote['currencyvalue'];

    $hook_data = array(
        'contents' => $cnotecontents,
        'cnote' => $cnote,
    );
    $hook_data = $LMS->ExecuteHook('invoicenoteedit_init', $hook_data);
    $cnotecontents = $hook_data['contents'];
    $cnote = $hook_data['cnote'];

    $SESSION->save('cnote', $cnote, true);
    $SESSION->save('cnoteid', $cnote['id'], true);
}

$SESSION->restore('cnotecontents', $contents, true);
$SESSION->restore('cnote', $cnote, true);
$SESSION->restore('cnoteediterror', $error, true);
$itemdata = r_trim($_POST);

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
            $cnote['sdate'] = $cnote['oldsdate'];
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
            } elseif (($cnote['oldcdate'] != $cnote['cdate'] || $cnote['oldnumber'] != $cnote['number']
                    || ($cnote['oldnumber'] == $cnote['number'] && $cnote['oldcustomerid'] != $cnote['customerid'])
                    || $cnote['oldnumberplanid'] != $cnote['numberplanid']) && ($docid = $LMS->DocumentExists(array(
                    'number' => $cnote['number'],
                    'doctype' => DOC_CNOTE,
                    'planid' => $cnote['numberplanid'],
                    'cdate' => $cnote['cdate'],
                    'customerid' => $cnote['customerid'],
                    ))) > 0 && $docid != $cnote['id']) {
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
            $cdate = $cnote['cdate'] ? $cnote['cdate'] : $currtime;
        } else {
            $cdate = $cnote['oldcdate'];
        }

        if (ConfigHelper::checkPrivilege('invoice_sale_date')) {
            $sdate = $cnote['sdate'] ? $cnote['sdate'] : $currtime;
        } else {
            $sdate = $cnote['oldsdate'];
        }

        $cnote['currency'] = $cnote['oldcurrency'];
        $cnote['currencyvalue'] = $cnote['oldcurrencyvalue'];

        $deadline = $cnote['deadline'] ? $cnote['deadline'] : $currtime;
        $paytime = $cnote['paytime'] = round(($cnote['deadline'] - $cnote['cdate']) / 86400);
        $iid   = $cnote['id'];

        $invoicecontents = $cnote['invoice']['content'];
        $cnotecontents = $cnote['content'];
        $newcontents = r_trim($_POST);

        foreach ($contents as $idx => $item) {
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
                $contents[$idx]['pdiscount'] = (!empty($contents[$idx]['discount_type']) && $contents[$idx]['discount_type'] == DISCOUNT_PERCENTAGE ? floatval($contents[$idx]['discount']) : 0);
                $contents[$idx]['vdiscount'] = (!empty($contents[$idx]['discount_type']) && $contents[$idx]['discount_type'] == DISCOUNT_AMOUNT ? floatval($contents[$idx]['discount']) : 0);
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
                            $new_valuebrutto = f_round($orig_valuebrutto);
                            $old_valuebrutto = $invoicecontents[$idx]['value'];
                            $contents[$idx]['valuebrutto'] = f_round($new_valuebrutto - $old_valuebrutto);
                            $contents[$idx]['pdiscount'] = 0;
                        } else {
                            $orig_valuebrutto = f_round($item['valuebrutto'] + $item['vdiscount']);
                            $new_valuebrutto = f_round($orig_valuebrutto);
                            $old_valuebrutto = $invoicecontents[$idx]['value'];
                            $contents[$idx]['valuebrutto'] = f_round($new_valuebrutto - $old_valuebrutto);
                            $contents[$idx]['vdiscount'] = 0;
                        }
                    } else {
                        if ($contents[$idx]['old_discount_type'] == DISCOUNT_PERCENTAGE) {
                            $orig_valuebrutto = f_round((100 * $item['valuebrutto']) / (100 - $item['pdiscount']));
                            $new_valuebrutto = f_round($orig_valuebrutto - $contents[$idx]['vdiscount']);
                            $old_valuebrutto = $invoicecontents[$idx]['value'];
                            $contents[$idx]['valuebrutto'] = f_round($new_valuebrutto - $old_valuebrutto);
                            $contents[$idx]['pdiscount'] = 0;
                        } else {
                            $orig_valuebrutto = f_round($item['valuebrutto'] + $item['vdiscount']);
                            $new_valuebrutto = f_round($orig_valuebrutto * (1 - ($contents[$idx]['pdiscount'] / 100)));
                            $old_valuebrutto = $invoicecontents[$idx]['value'];
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
                                $old_valuebrutto = $invoicecontents[$idx]['value'];
                                $contents[$idx]['valuebrutto'] = f_round($new_valuebrutto - $old_valuebrutto);
                            } else {
                                $orig_valuebrutto = f_round((100 * $item['valuebrutto']) / (100 - $item['pdiscount']));
                                $new_valuebrutto = f_round($orig_valuebrutto - $contents[$idx]['vdiscount']);
                                $old_valuebrutto = $invoicecontents[$idx]['value'];
                                $contents[$idx]['valuebrutto'] = f_round($new_valuebrutto - $old_valuebrutto);
                            }
                        } else {
                            //change vdiscount to pdiscount
                            if ($discount_method == 'first') {
                                $orig_valuebrutto = f_round($item['valuebrutto'] + $item['vdiscount']);
                                $new_valuebrutto = f_round($orig_valuebrutto * (1 - ($contents[$idx]['pdiscount'] / 100)));
                                $old_valuebrutto = $invoicecontents[$idx]['value'];
                                $contents[$idx]['valuebrutto'] = f_round($new_valuebrutto - $old_valuebrutto);
                            } else {
                                $orig_valuebrutto = f_round($item['valuebrutto'] + $item['vdiscount']);
                                $new_valuebrutto = f_round($orig_valuebrutto * (1 - ($contents[$idx]['pdiscount'] / 100)));
                                $old_valuebrutto = $invoicecontents[$idx]['value'];
                                $contents[$idx]['valuebrutto'] = f_round($new_valuebrutto - $old_valuebrutto);
                            }
                        }
                    } else {
                        //only discount value was changed and discount type was not changed
                        if ($discount_method == 'first') {
                            if ($contents[$idx]['discount_type'] == DISCOUNT_PERCENTAGE) {
                                $orig_valuebrutto = f_round((100 * $item['valuebrutto']) / (100 - $item['pdiscount']));
                                $new_valuebrutto = f_round($orig_valuebrutto * (1 - ($contents[$idx]['pdiscount'] / 100)));
                                $old_valuebrutto = $invoicecontents[$idx]['value'];
                                $contents[$idx]['valuebrutto'] = f_round($new_valuebrutto - $old_valuebrutto);
                            } else {
                                $orig_valuebrutto = f_round($item['valuebrutto'] + $item['vdiscount']);
                                $new_valuebrutto = f_round($orig_valuebrutto - $contents[$idx]['vdiscount']);
                                $old_valuebrutto = $invoicecontents[$idx]['value'];
                                $contents[$idx]['valuebrutto'] = f_round($new_valuebrutto - $old_valuebrutto);
                            }
                        } else {
                            if ($contents[$idx]['discount_type'] == DISCOUNT_PERCENTAGE) {
                                $orig_valuebrutto = f_round((100 * $item['valuebrutto']) / (100 - $item['pdiscount']));
                                $new_valuebrutto = f_round($orig_valuebrutto * (1 - ($contents[$idx]['pdiscount'] / 100)));
                                $old_valuebrutto = $invoicecontents[$idx]['value'];
                                $contents[$idx]['valuebrutto'] = f_round($new_valuebrutto - $old_valuebrutto);
                            } else {
                                $orig_valuebrutto = f_round($item['valuebrutto'] + $item['vdiscount']);
                                $new_valuebrutto = f_round($orig_valuebrutto - $contents[$idx]['vdiscount']);
                                $old_valuebrutto = $invoicecontents[$idx]['value'];
                                $contents[$idx]['valuebrutto'] = f_round($new_valuebrutto - $old_valuebrutto);
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
                if ($contents[$idx]['valuenetto'] != floatval($item['valuenetto'])) {
                    $contents[$idx]['valuebrutto'] = $contents[$idx]['valuenetto'] * ($taxvalue / 100 + 1);
                    $contents[$idx]['pdiscount'] = 0;
                    $contents[$idx]['vdiscount'] = 0;
                } elseif (f_round($contents[$idx]['valuebrutto']) == f_round($item['valuebrutto'])) {
                    $contents[$idx]['valuebrutto'] = $item['valuebrutto'];
                }

                if ((isset($item['deleted']) && $item['deleted']) || empty($contents[$idx]['count'])) {
                    $contents[$idx]['valuebrutto'] = f_round(-1 * $invoicecontents[$idx]['value'] * $invoicecontents[$idx]['count']);
                    $contents[$idx]['cash'] = f_round($invoicecontents[$idx]['value'] * $invoicecontents[$idx]['count'], 2);
                    $contents[$idx]['count'] = f_round(-1 * $invoicecontents[$idx]['count'], 3);
                } elseif ($contents[$idx]['count'] != $item['count']
                    || $contents[$idx]['valuebrutto'] != $item['valuebrutto']) {
                    $contents[$idx]['valuebrutto'] = f_round($contents[$idx]['valuebrutto'] - $invoicecontents[$idx]['value']);
                    $contents[$idx]['count'] = f_round($contents[$idx]['count'] - $invoicecontents[$idx]['count'], 3);
                    $contents[$idx]['pdiscount'] = 0;
                    $contents[$idx]['vdiscount'] = 0;
                    if (empty($contents[$idx]['count'])) {
                        $contents[$idx]['cash'] = f_round(-1 * $contents[$idx]['valuebrutto'] * $invoicecontents[$idx]['count'], 2);
                    } elseif (empty($contents[$idx]['valuebrutto'])) {
                        $contents[$idx]['cash'] = f_round(-1 * $invoicecontents[$idx]['value'] * $contents[$idx]['count'], 2);
                    } else {
                        $contents[$idx]['cash'] = f_round(-1 * $invoicecontents[$idx]['value'] * $invoicecontents[$idx]['count'], 2);
                    }
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

        $use_current_customer_data = isset($cnote['use_current_customer_data']);
        if ($use_current_customer_data) {
            $customer = $LMS->GetCustomer($cnote['customerid'], true);
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
            'splitpayment' => $cnote['splitpayment'],
            'flags' => (empty($cnote['flags'][DOC_FLAG_RECEIPT]) ? 0 : DOC_FLAG_RECEIPT)
                + (empty($cnote['flags'][DOC_FLAG_TELECOM_SERVICE]) || $customer['type'] == CTYPES_COMPANY ? 0 : DOC_FLAG_TELECOM_SERVICE)
                + ($use_current_customer_data
                    ? (isset($customer['flags'][CUSTOMER_FLAG_RELATED_ENTITY]) ? DOC_FLAG_RELATED_ENTITY : 0)
                    : (!empty($cnote['oldflags'][DOC_FLAG_RELATED_ENTITY]) ? DOC_FLAG_RELATED_ENTITY : 0)
                ),
            SYSLOG::RES_CUST => $cnote['customerid'],
            'name' => $use_current_customer_data ? $customer['customername'] : $cnote['name'],
            'address' => $use_current_customer_data ? (($customer['postoffice'] && $customer['postoffice'] != $customer['city'] && $customer['street']
                ? $customer['postoffice'] . ', ' : '') . $customer['address']) : $cnote['address'],
            'ten' => $use_current_customer_data ? $customer['ten'] : $cnote['ten'],
            'ssn' => $use_current_customer_data ? $customer['ssn'] : $cnote['ssn'],
            'zip' => $use_current_customer_data ? $customer['zip'] : $cnote['zip'],
            'city' => $use_current_customer_data ? ($customer['postoffice'] ? $customer['postoffice'] : $customer['city'])
                : $cnote['city'],
            SYSLOG::RES_COUNTRY => $use_current_customer_data ? (empty($customer['countryid']) ? null : $customer['countryid'])
                : (empty($cnote['countryid']) ? null : $cnote['countryid']),
            'reason' => $cnote['reason'],
            SYSLOG::RES_DIV => $use_current_customer_data ? $customer['divisionid'] : $cnote['divisionid'],
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
            'currency' => $cnote['currency'],
            'currencyvalue' => $cnote['currencyvalue'],
            'memo' => $use_current_customer_data ? (empty($customer['documentmemo']) ? null : $customer['documentmemo']) : $cnote['memo'],
        );
        $args['number'] = $cnote['number'];
        if ($cnote['numberplanid']) {
            $args['fullnumber'] = docnumber(array(
                'number' => $cnote['number'],
                'template' => $DB->GetOne('SELECT template FROM numberplans WHERE id = ?', array($cnote['numberplanid'])),
                'cdate' => $cnote['cdate'],
                'customerid' => $cnote['customerid'],
            ));
        } else {
            $args['fullnumber'] = null;
        }
        $args[SYSLOG::RES_NUMPLAN] = !empty($cnote['numberplanid']) ? $cnote['numberplanid'] : null;
        $args[SYSLOG::RES_DOC] = $iid;

        $DB->Execute('UPDATE documents SET cdate = ?, sdate = ?, paytime = ?, paytype = ?, splitpayment = ?, flags = ?, customerid = ?,
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
                    'value' => str_replace(',', '.', $item['valuebrutto']),
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
                    'itemid' => $itemid
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
            $which = isset($_GET['which']) ? $_GET['which'] : 0;

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

$SMARTY->assign('error', $error);
$SMARTY->assign('contents', $contents);
$SMARTY->assign('cnote', $cnote);
$SMARTY->assign('refdoc', $cnote);
$SMARTY->assign('taxeslist', $taxeslist);

$args = array(
    'doctype' => DOC_CNOTE,
    'cdate' => date('Y/m', $cnote['cdate']),
    'customerid' => $cnote['customerid'],
    'division' => $DB->GetOne('SELECT divisionid FROM customers WHERE id = ?', array($cnote['customerid'])),
);
$SMARTY->assign('numberplanlist', $LMS->GetNumberPlans($args));
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
