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

$cid = $document['customerid'];
$pdf_output = '';

$assignments_from = $document['fromdate'];
if (empty($error) && empty($assignments_from)) {
    $error['fromdate'] = trans('Requiered!');
}

$assignments_to = $document['todate'];
if (empty($error) && empty($assignments_to)) {
    $error['todate'] = trans('Requiered!');
}

$default_payment_aggregation = (isset($document['booklet']['payment_aggregation']) ? $document['booklet']['payment_aggregation'] : MONTHLY);
$payment_at_period_begin = (isset($document['booklet']['payment_calculation']) ? $document['booklet']['payment_calculation'] : 0);
$payment_title = $document['booklet']['payment_title'];

if (empty($error) && empty($payment_title)) {
    $error['booklet_title'] = trans('Requiered!');
}

// get assignments that suspend all assignments
$suspentions_in_assignments = array();
$suspentions_in_assignments = $DB->GetAll('SELECT
        a.id AS id, a.datefrom, a.dateto
    FROM assignments a
    WHERE a.customerid = ? 
    AND a.commited = 1
    AND a.tariffid IS NULL
    AND a.liabilityid IS NULL
    AND (a.datefrom <= ' . $assignments_to . ' OR a.datefrom = 0)
    AND (a.dateto >= ' . $assignments_from . ' OR a.dateto = 0)
    ORDER BY
    a.datefrom', array($cid));

// check if all assignments are not susspended for all time
if ($suspentions_in_assignments) {
    foreach ($suspentions_in_assignments as $sia) {
        if ($sia['datefrom'] == 0 && $sia['dateto'] == 0) {
            $error['templ'] = trans('All assignements for customer are suspended!');
        }
    }
}

if (empty($error)) {
    $assignments = $DB->GetAll('SELECT
        a.id AS id, a.tariffid, a.customerid, a.period,
        a.at, a.suspended, a.invoice, a.settlement,
        a.datefrom, a.dateto, a.pdiscount,
        (a.vdiscount * a.count) AS vdiscount,                                                                                        
        a.attribute, a.liabilityid, a.separatedocument,
        t.type AS tarifftype,
        a.count,
        (CASE WHEN t.value IS NULL THEN l.value ELSE t.value END) * a.count AS value,
        (CASE WHEN t.currency IS NULL THEN l.currency ELSE t.currency END) AS currency,
        (CASE WHEN t.name IS NULL THEN l.name ELSE t.name END) AS name,
        d.number AS docnumber, d.type AS doctype, d.cdate, np.template,
        d.fullnumber,
        commited
    FROM assignments a
    LEFT JOIN tariffs t     ON (a.tariffid = t.id)
    LEFT JOIN liabilities l ON (a.liabilityid = l.id)
    LEFT JOIN documents d ON d.id = a.docid
    LEFT JOIN numberplans np ON np.id = d.numberplanid
    WHERE a.customerid = ? 
    AND a.commited = 1
    AND a.suspended = 0
    AND (a.tariffid IS NOT NULL OR a.liabilityid IS NOT NULL)
    AND (a.datefrom <= ' . $assignments_to . ' OR a.datefrom = 0)
    AND (a.dateto >= ' . $assignments_from . ' OR a.dateto = 0)
    ORDER BY
    a.datefrom, t.name, value', array($cid));

    if ($assignments) {
        $currency_payments_items = array();
        $transferform = new LMSTcpdfTransferForm('Booklet', $pagesize = 'A4', $orientation = 'portrait');
        $tranferforms_data = array();
        $tranferform_common_data = $transferform->GetCommonData(array('customerid' => $cid));

        //<editor-fold desc="prepare array with payments indexed by day of payment">
        foreach ($assignments as $idx => $row) {
            $payments_pdate = 0;
            $payments_pdate_ts = 0;

            //<editor-fold desc="calculate discounted vale">
            $assignments[$idx]['discounted_value'] = (((100 - $row['pdiscount']) * $row['value']) / 100) - $row['vdiscount'];
            if ($row['suspended'] == 1) {
                $assignments[$idx]['discounted_value'] = $assignments[$idx]['discounted_value'] * ConfigHelper::getConfig('finances.suspension_percentage') / 100;
            }
            $assignments[$idx]['discounted_value'] = round($assignments[$idx]['discounted_value'], 2);
            //</editor-fold>

            if ($row['period'] == DISPOSABLE) {
                $assignment_date = $row['at'];
                $payments_pdate_ts = mktime(12, 0, 0, date('n', $assignment_date), date('d', $assignment_date), date('Y', $assignment_date));
                $payments_pdate = date('Y/m/d', $payments_pdate_ts);

                if ($assignment_date < $assignments_from || $assignment_date > $assignments_to) {
                    continue;
                } else {
                    // eliminate if payment is suspended
                    foreach ($suspentions_in_assignments as $sia) {
                        if (($payments_pdate_ts >= $sia['datefrom'] || $sia['datefrom'] == 0)
                            && ($payments_pdate_ts <= $sia['dateto'] || $sia['dateto'] == 0)) {
                            $suspended_payemnt = 1;
                            break;
                        }
                    }
                    if ($suspended_payemnt == 0 && $payments_pdate_ts >= $assignments_from && $payments_pdate_ts <= $assignments_to) {
                        $currency_payments_items[$assignments[$idx]['currency']][$payments_pdate_ts][$row['id']]['at'] = $payments_pdate_day;
                        $currency_payments_items[$assignments[$idx]['currency']][$payments_pdate_ts][$row['id']]['discounted_value'] = $assignments[$idx]['discounted_value'];
                        $currency_payments_items[$assignments[$idx]['currency']][$payments_pdate_ts][$row['id']]['currency'] = $assignments[$idx]['currency'];
                        $currency_payments_items[$assignments[$idx]['currency']][$payments_pdate_ts][$row['id']]['payments_pdate_ts'] = $payments_pdate_ts;
                        $currency_payments_items[$assignments[$idx]['currency']][$payments_pdate_ts][$row['id']]['payments_pdate'] = $payments_pdate;
                    }
                    unset($suspended_payemnt);
                }
            }

            if ($row['period'] == MONTHLY) {
                $assignment_from_date = ($row['datefrom'] == 0 ? $assignments_from : $row['datefrom']);
                $assignment_from_day = mktime(12, 0, 0, date('n', $assignment_from_date), date('d', $assignment_from_date), date('Y', $assignment_from_date));
                $assignment_from_month = date('n', $assignment_from_day);
                $assignment_from_year = date('Y', $assignment_from_day);


                $assignment_to_date = ($row['dateto'] == 0 ? $assignments_to : ($row['dateto'] < $assignments_to ? $row['dateto'] : $assignments_to));
                $assignment_to_day = mktime(12, 0, 0, date('n', $assignment_to_date), date('d', $assignment_to_date), date('Y', $assignment_to_date));
                $assignment_to_month = date('n', $assignment_to_day);
                $assignment_to_year = date('Y', $assignment_to_day);

                $assignment_month_diff = (($assignment_to_year - $assignment_from_year) * 12) + ($assignment_to_month - $assignment_from_month);

                for ($j = 0; $j <= $assignment_month_diff; $j++) {
                    $payments_pdate_ts = mktime(12, 0, 0, ($row['at'] != 0 ? ($assignment_from_month + $j) : ($assignment_from_month + $j + 1)), $row['at'], $assignment_from_year);
                    $payments_pdate = date('Y/m/d', $payments_pdate_ts);
                    $payments_pdate_day = date('d', $payments_pdate_ts);
                    $suspended_payemnt = 0;

                    // eliminate suspended payments
                    foreach ($suspentions_in_assignments as $sia) {
                        if (($payments_pdate_ts >= $sia['datefrom'] || $sia['datefrom'] == 0)
                            && ($payments_pdate_ts <= $sia['dateto'] || $sia['dateto'] == 0)) {
                            $suspended_payemnt = 1;
                            break;
                        }
                    }

                    if ($suspended_payemnt == 0 && $payments_pdate_ts >= $assignments_from && $payments_pdate_ts <= $assignments_to) {
                        $currency_payments_items[$assignments[$idx]['currency']][$payments_pdate_ts][$row['id']]['at'] = $payments_pdate_day;
                        $currency_payments_items[$assignments[$idx]['currency']][$payments_pdate_ts][$row['id']]['discounted_value'] = $assignments[$idx]['discounted_value'];
                        $currency_payments_items[$assignments[$idx]['currency']][$payments_pdate_ts][$row['id']]['currency'] = $assignments[$idx]['currency'];
                        $currency_payments_items[$assignments[$idx]['currency']][$payments_pdate_ts][$row['id']]['payments_pdate_ts'] = $payments_pdate_ts;
                        $currency_payments_items[$assignments[$idx]['currency']][$payments_pdate_ts][$row['id']]['payments_pdate'] = $payments_pdate;
                    }
                    unset($suspended_payemnt);
                }
            }

            if ($row['period'] == QUARTERLY) {
                $assignment_at = sprintf('%02d/%02d', $row['at'] % 100, $row['at'] / 100 + 1);
                $assignment_at_month = ltrim(substr($assignment_at, 3, 2), '0');
                $assignment_at_day = substr($assignment_at, 0, 2);

                $assignment_from_date = ($row['datefrom'] == 0 ? $assignments_from : $row['datefrom']);
                $assignment_from_day = mktime(12, 0, 0, date('n', $assignment_from_date), date('d', $assignment_from_date), date('Y', $assignment_from_date));
                $assignment_from_month = date('n', $assignment_from_day);
                $assignment_from_year = date('Y', $assignment_from_day);


                $assignment_to_date = ($row['dateto'] == 0 ? $assignments_to : ($row['dateto'] < $assignments_to ? $row['dateto'] : $assignments_to));
                $assignment_to_day = mktime(12, 0, 0, date('n', $assignment_to_date), date('d', $assignment_to_date), date('Y', $assignment_to_date));
                $assignment_to_month = date('n', $assignment_to_day);
                $assignment_to_year = date('Y', $assignment_to_day);

                $assignment_month_diff = (($assignment_to_year - $assignment_from_year) * 12) + ($assignment_to_month - $assignment_from_month);

                for ($j = 0; $j <= $assignment_month_diff; $j++) {
                    $payments_pdate_ts_tmp = mktime(12, 0, 0, ($assignment_from_month + $j), $assignment_at_day, $assignment_from_year);
                    $payments_pdate_tmp = date('Y/m/d', $payments_pdate_ts_tmp);
                    $payments_pdate_ts_tmp_month =  date('n', $payments_pdate_ts_tmp);

                    if ($assignment_at_month == 2 && ($payments_pdate_ts_tmp_month == 2 || $payments_pdate_ts_tmp_month == 5 || $payments_pdate_ts_tmp_month == 8 || $payments_pdate_ts_tmp_month == 11)) {
                        $payments_pdate_ts = $payments_pdate_ts_tmp;
                    } elseif ($assignment_at_month == 1 && ($payments_pdate_ts_tmp_month == 1 || $payments_pdate_ts_tmp_month == 4 || $payments_pdate_ts_tmp_month == 7 || $payments_pdate_ts_tmp_month == 10)) {
                        $payments_pdate_ts = $payments_pdate_ts_tmp;
                    } elseif ($assignment_at_month == 3 && ($payments_pdate_ts_tmp_month % $assignment_at_month) == 0) {
                        $payments_pdate_ts = $payments_pdate_ts_tmp;
                    } else {
                        continue;
                    }

                    $payments_pdate = date('Y/m/d', $payments_pdate_ts);
                    $payments_pdate_day = date('d', $payments_pdate_ts);
                    $suspended_payemnt = 0;

                    // eliminate suspended payments
                    foreach ($suspentions_in_assignments as $sia) {
                        if (($payments_pdate_ts >= $sia['datefrom'] || $sia['datefrom'] == 0)
                            && ($payments_pdate_ts <= $sia['dateto'] || $sia['dateto'] == 0)) {
                            $suspended_payemnt = 1;
                            break;
                        }
                    }

                    if ($suspended_payemnt == 0 && $payments_pdate_ts >= $assignments_from && $payments_pdate_ts <= $assignments_to) {
                        $currency_payments_items[$assignments[$idx]['currency']][$payments_pdate_ts][$row['id']]['at'] = $payments_pdate_day;
                        $currency_payments_items[$assignments[$idx]['currency']][$payments_pdate_ts][$row['id']]['discounted_value'] = $assignments[$idx]['discounted_value'];
                        $currency_payments_items[$assignments[$idx]['currency']][$payments_pdate_ts][$row['id']]['currency'] = $assignments[$idx]['currency'];
                        $currency_payments_items[$assignments[$idx]['currency']][$payments_pdate_ts][$row['id']]['payments_pdate_ts'] = $payments_pdate_ts;
                        $currency_payments_items[$assignments[$idx]['currency']][$payments_pdate_ts][$row['id']]['payments_pdate'] = $payments_pdate;
                    }
                    unset($suspended_payemnt);
                }
            }

            if ($row['period'] == HALFYEARLY) {
                $assignment_at = sprintf('%02d/%02d', $row['at'] % 100, $row['at'] / 100 + 1);
                $assignment_at_month = ltrim(substr($assignment_at, 3, 2), '0');
                $assignment_at_day = substr($assignment_at, 0, 2);

                $assignment_from_date = ($row['datefrom'] == 0 ? $assignments_from : $row['datefrom']);
                $assignment_from_day = mktime(12, 0, 0, date('n', $assignment_from_date), date('d', $assignment_from_date), date('Y', $assignment_from_date));
                $assignment_from_month = date('n', $assignment_from_day);
                $assignment_from_year = date('Y', $assignment_from_day);


                $assignment_to_date = ($row['dateto'] == 0 ? $assignments_to : ($row['dateto'] < $assignments_to ? $row['dateto'] : $assignments_to));
                $assignment_to_day = mktime(12, 0, 0, date('n', $assignment_to_date), date('d', $assignment_to_date), date('Y', $assignment_to_date));
                $assignment_to_month = date('n', $assignment_to_day);
                $assignment_to_year = date('Y', $assignment_to_day);

                $assignment_month_diff = (($assignment_to_year - $assignment_from_year) * 12) + ($assignment_to_month - $assignment_from_month);

                for ($j = 0; $j <= $assignment_month_diff; $j++) {
                    $payments_pdate_ts_tmp = mktime(12, 0, 0, ($assignment_from_month + $j), $assignment_at_day, $assignment_from_year);
                    $payments_pdate_tmp = date('Y/m/d', $payments_pdate_ts_tmp);
                    $payments_pdate_ts_tmp_month =  date('n', $payments_pdate_ts_tmp);

                    if ($payments_pdate_ts_tmp_month == $assignment_at_month || ($payments_pdate_ts_tmp_month - 6) == $assignment_at_month) {
                        $payments_pdate_ts = $payments_pdate_ts_tmp;
                    } else {
                        continue;
                    }

                    $payments_pdate = date('Y/m/d', $payments_pdate_ts);
                    $payments_pdate_day = date('d', $payments_pdate_ts);
                    $suspended_payemnt = 0;

                    // eliminate suspended payments
                    foreach ($suspentions_in_assignments as $sia) {
                        if (($payments_pdate_ts >= $sia['datefrom'] || $sia['datefrom'] == 0)
                            && ($payments_pdate_ts <= $sia['dateto'] || $sia['dateto'] == 0)) {
                            $suspended_payemnt = 1;
                            break;
                        }
                    }

                    if ($suspended_payemnt == 0 && $payments_pdate_ts >= $assignments_from && $payments_pdate_ts <= $assignments_to) {
                        $currency_payments_items[$assignments[$idx]['currency']][$payments_pdate_ts][$row['id']]['at'] = $payments_pdate_day;
                        $currency_payments_items[$assignments[$idx]['currency']][$payments_pdate_ts][$row['id']]['discounted_value'] = $assignments[$idx]['discounted_value'];
                        $currency_payments_items[$assignments[$idx]['currency']][$payments_pdate_ts][$row['id']]['currency'] = $assignments[$idx]['currency'];
                        $currency_payments_items[$assignments[$idx]['currency']][$payments_pdate_ts][$row['id']]['payments_pdate_ts'] = $payments_pdate_ts;
                        $currency_payments_items[$assignments[$idx]['currency']][$payments_pdate_ts][$row['id']]['payments_pdate'] = $payments_pdate;
                    }
                    unset($suspended_payemnt);
                }
            }

            if ($row['period'] == YEARLY) {
                $assignment_at = date('d/m', ($row['at'] - 1) * 86400);
                $assignment_at_month = ltrim(substr($assignment_at, 3, 2), '0');
                $assignment_at_day = substr($assignment_at, 0, 2);

                $assignment_from_date = ($row['datefrom'] == 0 ? $assignments_from : $row['datefrom']);
                $assignment_from_day = mktime(12, 0, 0, date('n', $assignment_from_date), date('d', $assignment_from_date), date('Y', $assignment_from_date));
                $assignment_from_month = date('n', $assignment_from_day);
                $assignment_from_year = date('Y', $assignment_from_day);


                $assignment_to_date = ($row['dateto'] == 0 ? $assignments_to : ($row['dateto'] < $assignments_to ? $row['dateto'] : $assignments_to));
                $assignment_to_day = mktime(12, 0, 0, date('n', $assignment_to_date), date('d', $assignment_to_date), date('Y', $assignment_to_date));
                $assignment_to_month = date('n', $assignment_to_day);
                $assignment_to_year = date('Y', $assignment_to_day);

                $assignment_month_diff = (($assignment_to_year - $assignment_from_year) * 12) + ($assignment_to_month - $assignment_from_month);

                for ($j = 0; $j <= $assignment_month_diff; $j++) {
                    $payments_pdate_ts_tmp = mktime(12, 0, 0, ($assignment_from_month + $j), $assignment_at_day, $assignment_from_year);
                    $payments_pdate_tmp = date('Y/m/d', $payments_pdate_ts_tmp);
                    $payments_pdate_ts_tmp_month =  date('n', $payments_pdate_ts_tmp);

                    if ($payments_pdate_ts_tmp_month == $assignment_at_month) {
                        $payments_pdate_ts = $payments_pdate_ts_tmp;
                    } else {
                        continue;
                    }

                    $payments_pdate = date('Y/m/d', $payments_pdate_ts);
                    $payments_pdate_day = date('d', $payments_pdate_ts);
                    $suspended_payemnt = 0;

                    // eliminate suspended payments
                    foreach ($suspentions_in_assignments as $sia) {
                        if (($payments_pdate_ts >= $sia['datefrom'] || $sia['datefrom'] == 0)
                            && ($payments_pdate_ts <= $sia['dateto'] || $sia['dateto'] == 0)) {
                            $suspended_payemnt = 1;
                            break;
                        }
                    }

                    if ($suspended_payemnt == 0 && $payments_pdate_ts >= $assignments_from && $payments_pdate_ts <= $assignments_to) {
                        $currency_payments_items[$assignments[$idx]['currency']][$payments_pdate_ts][$row['id']]['at'] = $payments_pdate_day;
                        $currency_payments_items[$assignments[$idx]['currency']][$payments_pdate_ts][$row['id']]['discounted_value'] = $assignments[$idx]['discounted_value'];
                        $currency_payments_items[$assignments[$idx]['currency']][$payments_pdate_ts][$row['id']]['currency'] = $assignments[$idx]['currency'];
                        $currency_payments_items[$assignments[$idx]['currency']][$payments_pdate_ts][$row['id']]['payments_pdate_ts'] = $payments_pdate_ts;
                        $currency_payments_items[$assignments[$idx]['currency']][$payments_pdate_ts][$row['id']]['payments_pdate'] = $payments_pdate;
                    }
                    unset($suspended_payemnt);
                }
            }
        }
        //</editor-fold>

        if ($currency_payments_items) {
            //<editor-fold desc="prepare data for booklet of fees">
            foreach ($currency_payments_items as $curkey => $payments_items) {
                ksort($payments_items);
                $payments_items_daily_aggregate = array();
                $payments_items_monthly_aggregate = array();
                $payments_items_quaterly_aggregate = array();
                $payments_items_halfyearly_aggregate = array();
                $payments_items_yearly_aggregate = array();
                $payments_items_print = array();

                // aggregate payments in the same day
                foreach ($payments_items as $pikey => $payments_item) {
                    $payments_items_daily_aggregate[$pikey]['discounted_value'] = 0;
                    $payments_items_daily_aggregate[$pikey]['payments_pdate_ts'] = $pikey;
                    $payments_items_daily_aggregate[$pikey]['payments_pdate'] = date('Y/m/d', $pikey);
                    foreach ($payments_item as $payment_item) {
                        $payments_items_daily_aggregate[$pikey]['at'] = date('d', $pikey);
                        $payments_items_daily_aggregate[$pikey]['month'] = date('m', $pikey); // only for test
                        $payments_items_daily_aggregate[$pikey]['year'] = date('Y', $pikey); // only for test
                        $payments_items_daily_aggregate[$pikey]['discounted_value'] += $payment_item['discounted_value'];
                        $payments_items_daily_aggregate[$pikey]['currency'] = $payment_item['currency'];
                    }
                }

                // aggregate payments in the same month
                // data płatności jest ustawiana na najwcześniejszy dzień spośród zobowiązań
                if ($default_payment_aggregation == MONTHLY) {
                    foreach ($payments_items_daily_aggregate as $pikey => $payments_item) {
                        $payments_item_month = date('n', $pikey);
                        $payments_item_year = date('Y', $pikey);
                        $payments_item_month_year = date('Y/m', $pikey);
                        if (!isset($payments_items_monthly_aggregate[$payments_item_month_year])) {
                            $payments_items_monthly_aggregate[$payments_item_month_year]['at'] = date('d', $pikey);
                        } else {
                            if (date('d', $pikey) < $payments_items_monthly_aggregate[$payments_item_month_year]['at']) {
                                $payments_items_monthly_aggregate[$payments_item_month_year]['at'] = date('d', $pikey);
                            }
                        }

                        if (!isset($payments_items_monthly_aggregate[$payments_item_month_year]['discounted_value'])) {
                            $payments_items_monthly_aggregate[$payments_item_month_year]['discounted_value'] = 0;
                        }
                        $payments_items_monthly_aggregate[$payments_item_month_year]['discounted_value'] += $payments_item['discounted_value'];
                        $payments_items_monthly_aggregate[$payments_item_month_year]['currency'] = $payments_item['currency'];
                        $payments_items_monthly_aggregate[$payments_item_month_year]['payments_pdate_ts'] = mktime(12, 0, 0, $payments_item_month, $payments_items_monthly_aggregate[$payments_item_month_year]['at'], $payments_item_year);
                        $payments_items_monthly_aggregate[$payments_item_month_year]['payments_pdate'] = date('Y/m/d', $payments_items_monthly_aggregate[$payments_item_month_year]['payments_pdate_ts']);
                    }
                }

                // aggregate payments in the same quarter
                // data płatności jest ustawiana na koniec lub początek kwartału
                if ($default_payment_aggregation == QUARTERLY) {
                    foreach ($payments_items_daily_aggregate as $pikey => $payments_item) {
                        $payments_item_month = date('n', $pikey);
                        $payments_item_year = date('Y', $pikey);
                        if ($payments_item_month <= 3) {
                            $month = (empty($payment_at_period_begin) ? 3 : 1);
                            $payments_item_quarter_year = date('Y', $pikey) . '/1';
                            $dom = (empty($payment_at_period_begin) ? date('j', mktime(0, 0, 0, 4, 0, $payments_item_year)) : 1);
                        } elseif ($payments_item_month > 3 && $payments_item_month <= 6) {
                            $month = (empty($payment_at_period_begin) ? 6 : 4);
                            $payments_item_quarter_year = date('Y', $pikey) . '/2';
                            $dom = (empty($payment_at_period_begin) ? date('j', mktime(0, 0, 0, 7, 0, $payments_item_year)) : 1);
                        } elseif ($payments_item_month > 6 && $payments_item_month <= 9) {
                            $month = (empty($payment_at_period_begin) ? 9 : 7);
                            $payments_item_quarter_year = date('Y', $pikey) . '/3';
                            $dom = (empty($payment_at_period_begin) ? date('j', mktime(0, 0, 0, 10, 0, $payments_item_year)) : 1);
                        } else {
                            $month = (empty($payment_at_period_begin) ? 12 : 10);
                            $payments_item_quarter_year = date('Y', $pikey) . '/4';
                            $dom = (empty($payment_at_period_begin) ? 31 : 1);
                        }

                        if (!isset($payments_items_quaterly_aggregate[$payments_item_quarter_year]['discounted_value'])) {
                            $payments_items_quaterly_aggregate[$payments_item_quarter_year]['discounted_value'] = 0;
                        }
                        $payments_items_quaterly_aggregate[$payments_item_quarter_year]['discounted_value'] += $payments_item['discounted_value'];
                        $payments_items_quaterly_aggregate[$payments_item_quarter_year]['currency'] = $payments_item['currency'];
                        $payments_items_quaterly_aggregate[$payments_item_quarter_year]['payments_pdate_ts'] = mktime(12, 0, 0, $month, $dom, $payments_item_year);
                        $payments_items_quaterly_aggregate[$payments_item_quarter_year]['payments_pdate'] = date('Y/m/d', $payments_items_quaterly_aggregate[$payments_item_quarter_year]['payments_pdate_ts']);
                    }
                }

                // aggregate payments in the same half of the year
                // data płatności jest ustawiana na koniec lub początek półrocza
                if ($default_payment_aggregation == HALFYEARLY) {
                    foreach ($payments_items_daily_aggregate as $pikey => $payments_item) {
                        $payments_item_month = date('n', $pikey);
                        $payments_item_year = date('Y', $pikey);
                        if ($payments_item_month <= 6) {
                            $month = (empty($payment_at_period_begin) ? 6 : 1);
                            $dom = (empty($payment_at_period_begin) ? 30 : 1);
                            $payments_item_halfyear_year = date('Y', $pikey) . '/1';
                        } else {
                            $month = (empty($payment_at_period_begin) ? 12 : 7);
                            $dom = (empty($payment_at_period_begin) ? 31 : 1);
                            $payments_item_halfyear_year = date('Y', $pikey) . '/2';
                        }

                        if (!isset($payments_items_halfyearly_aggregate[$payments_item_halfyear_year]['discounted_value'])) {
                            $payments_items_halfyearly_aggregate[$payments_item_halfyear_year]['discounted_value'] = 0;
                        }
                        $payments_items_halfyearly_aggregate[$payments_item_halfyear_year]['discounted_value'] += $payments_item['discounted_value'];
                        $payments_items_halfyearly_aggregate[$payments_item_halfyear_year]['currency'] = $payments_item['currency'];
                        $payments_items_halfyearly_aggregate[$payments_item_halfyear_year]['payments_pdate_ts'] = mktime(12, 0, 0, $month, $dom, $payments_item_year);
                        $payments_items_halfyearly_aggregate[$payments_item_halfyear_year]['payments_pdate'] = date('Y/m/d', $payments_items_halfyearly_aggregate[$payments_item_halfyear_year]['payments_pdate_ts']);
                    }
                }

                // aggregate payments in the same year
                // data płatności jest ustawiana na koniec lub początek roku
                if ($default_payment_aggregation == YEARLY) {
                    foreach ($payments_items_daily_aggregate as $pikey => $payments_item) {
                        $payments_item_month = date('n', $pikey);
                        $payments_item_year = date('Y', $pikey);

                        $month = (empty($payment_at_period_begin) ? 12 : 1);
                        $dom = (empty($payment_at_period_begin) ? 31 : 1);

                        if (!isset($payments_items_yearly_aggregate[$payments_item_year]['discounted_value'])) {
                            $payments_items_yearly_aggregate[$payments_item_year]['discounted_value'] = 0;
                        }
                        $payments_items_yearly_aggregate[$payments_item_year]['discounted_value'] += $payments_item['discounted_value'];
                        $payments_items_yearly_aggregate[$payments_item_year]['currency'] = $payments_item['currency'];
                        $payments_items_yearly_aggregate[$payments_item_year]['payments_pdate_ts'] = mktime(12, 0, 0, $month, $dom, $payments_item_year);
                        $payments_items_yearly_aggregate[$payments_item_year]['payments_pdate'] = date('Y/m/d', $payments_items_yearly_aggregate[$payments_item_year]['payments_pdate_ts']);
                    }
                }

                $payments_items_print = array();
                switch ($default_payment_aggregation) {
                    case DAILY:
                        $payments_items_print = array_values($payments_items_daily_aggregate);
                        break;
                    case MONTHLY:
                        $payments_items_print = array_values($payments_items_monthly_aggregate);
                        break;
                    case QUARTERLY:
                        $payments_items_print = array_values($payments_items_quaterly_aggregate);
                        break;
                    case HALFYEARLY:
                        $payments_items_print = array_values($payments_items_halfyearly_aggregate);
                        break;
                    case YEARLY:
                        $payments_items_print = array_values($payments_items_yearly_aggregate);
                        break;
                }

                foreach ($payments_items_print as $payment_item_print) {
                    $tranferform_custom_data = array(
                        'value' => $payment_item_print['discounted_value'],
                        'currency' => $payment_item_print['currency'],
                        'pdate' => $payment_item_print['payments_pdate_ts'],
                        'pdate_date' => $payment_item_print['payments_pdate'],
                        'title' => $payment_title,
                    );
                    $tranferforms_data[$curkey][] = $transferform->SetCustomData($tranferform_common_data, $tranferform_custom_data);
                }
            }
            //</editor-fold>

            //<editor-fold desc="Draw booklet of fees">
            $tranferforms_data_count = count($tranferforms_data);
            $form_data_count = 0;
            foreach ($tranferforms_data as $tranferform_data) {
                $form_data_count++;
                $pdfcdate = time();
                $form_count = 0;
                $perpage_form_count = 1;
                $form_translateY = 0;
                $tranferform_data_count = count($tranferform_data);
                foreach ($tranferform_data as $tkey => $tdata) {
                    if ($perpage_form_count == 1) {
                        $form_translateY = 0;
                    } elseif ($perpage_form_count == 2) {
                        $form_translateY = 107;
                    } elseif ($perpage_form_count == 3) {
                        $form_translateY = 215;
                    }

                    $transferform->Draw($tdata, 0, $form_translateY, 92, 100);

                    $perpage_form_count++;
                    $form_count++;
                    if ($form_count < $tranferform_data_count && $perpage_form_count == 4) {
                        $perpage_form_count = 1;
                        $transferform->NewPage();
                    }
                }
                if ($form_data_count != $tranferforms_data_count) {
                    $transferform->NewPage();
                }
            }
            //</editor-fold>

            $pdf_output = $transferform->WriteToString();
        } else {
            if (empty($error)) {
                $error['templ'] = trans('There is no assignments in selected period!');
            }
        }
    } else {
        if (empty($error)) {
            $error['templ'] = trans('There is no assignments in selected period!');
        }
    }
}

$output = $pdf_output;
