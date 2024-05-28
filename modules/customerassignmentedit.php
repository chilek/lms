<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2024 LMS Developers
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

if (isset($_GET['action']) && $_GET['action'] == 'edit_suspension') {
    $sid = isset($_GET['sid']) ? intval($_GET['sid']) : null;
    $cid = isset($_GET['cid']) ? intval($_GET['cid']) : null;
    $suspensionId = null;
    $customerid = null;
    if (!empty($sid)) {
        $suspensionId = $DB->GetOne('SELECT id FROM suspensions WHERE id = ?', array($sid));
    }
    if (!empty($cid)) {
        $customer = $DB->GetRow(
            'SELECT c.id AS id, c.divisionid, '
            . $DB->Concat('c.lastname', "' '", 'c.name') . ' AS name
        FROM customerview c
        WHERE c.id = ?',
            array($cid)
        );
        if (!empty($customer)) {
            $customerId = $customer['id'];
        }
    }
    if (empty($suspensionId) || empty($customerId)) {
        $SESSION->redirect_to_history_entry();
    }

    if (isset($_POST['assignment'])) {
        $suspension = r_trim($_POST['assignment']);
        $origSuspension = $LMS->getSuspensions(array('suspension_id' => $suspensionId, 'by_suspension_id' => true));

        $defaultTaxIds = $LMS->GetTaxes(null, null, true);
        if (is_array($defaultTaxIds)) {
            $defaultTaxId = reset($defaultTaxIds);
            $defaultTaxId = $defaultTaxId['id'];
        } else {
            $defaultTaxId = 0;
        }

        $today = strtotime('today');
        $suspensionValue = null;
        $suspensionPercentage = null;
        $suspension_at = null;
        $suspension_netflag = null;
        $suspension_currency = null;
        $suspension_taxid = null;

        if ($suspension['suspension_charge_method'] != SUSPENSION_CHARGE_METHOD_NONE) {
            if ($suspension['suspension_calculation_method'] == SUSPENSION_CALCULATION_METHOD_VALUE) {
                $suspension_netflag = isset($suspension['suspension_netflag']) ? 1 : 0;

                if ($suspension_netflag) {
                    if (!empty($suspension['suspension_net_value']) && f_round($suspension['suspension_net_value']) != 0) {
                        $suspensionValue = str_replace(',', '.', f_round($suspension['suspension_net_value']));
                    } elseif ($suspension['suspension_net_value'] == '') {
                        $suspensionValue = null;
                    } elseif (isset($suspension['suspension_net_value']) && f_round($suspension['suspension_net_value']) == 0) {
                        $suspensionValue = 0;
                    }
                } else {
                    if (!empty($suspension['suspension_gross_value']) && f_round($suspension['suspension_gross_value']) != 0) {
                        $suspensionValue = str_replace(',', '.', f_round($suspension['suspension_gross_value']));
                    } elseif ($suspension['suspension_gross_value'] == '') {
                        $suspensionValue = null;
                    } elseif (isset($suspension['suspension_gross_value']) && f_round($suspension['suspension_gross_value']) == 0) {
                        $suspensionValue = 0;
                    }
                }

                $suspension_currency = $suspension['currency'];
                $suspension_taxid = $suspension['taxid'];
            } elseif ($suspension['suspension_calculation_method'] == SUSPENSION_CALCULATION_METHOD_PERCENTAGE) {
                if (!empty($suspension['suspension_percentage']) && f_round($suspension['suspension_percentage']) != 0) {
                    $suspensionPercentage = str_replace(',', '.', f_round($suspension['suspension_percentage']));
                } elseif ($suspension['suspension_percentage'] == '') {
                    $suspensionPercentage = null;
                } elseif (isset($suspension['suspension_percentage']) && f_round($suspension['suspension_percentage']) == 0) {
                    $suspensionPercentage = 0;
                }
            }
        } else {
            $suspension['suspension_calculation_method'] = SUSPENSION_CALCULATION_METHOD_PERCENTAGE;
            $suspensionPercentage = 0;
        }

        $error = $LMS->ValidateSuspension($suspension);
        if (empty($error)) {
            $DB->BeginTrans();
            $DB->LockTables(array('suspensions', 'assignmentsuspensions'));

            if (!empty($suspension['suspended_assignments'])) {
                $origSuspension = $LMS->getSuspensions(array('suspension_id' => $suspensionId, 'by_suspension_id' => true));
                $allSuspenedAssignemnts = $LMS->getCustomerAssignments($customerId, array('show_expired' => false, 'suspended' => true));

                if (isset($origSuspension[$suspensionId])) {
                    foreach ($origSuspension[$suspensionId]['suspended_assignments'] as $akey => $sassignment) {
                        if (!isset($suspension['suspended_assignments'][$akey])) {
                            $DB->Execute(
                                'DELETE FROM assignmentsuspensions WHERE suspensionid = ? AND assignmentid = ?',
                                array(
                                    $suspensionId,
                                    $akey)
                            );
                        }
                    }

                    foreach ($suspension['suspended_assignments'] as $akey => $sassignment) {
                        if (!isset($origSuspension[$suspensionId]['suspended_assignments'][$akey]) && !isset($allSuspenedAssignemnts[$akey])) {
                            $args = array(
                                'suspension_id' => $suspensionId,
                                'assignment_id' => $akey,
                                'customerid' => $customerId,
                            );
                            $LMS->addAssignmentSuspension($args);
                        }
                    }

                    $assignments = $LMS->getCustomerAssignments($customerId, array('suspension_id' => $suspensionId));
                    if (!empty($assignments)) {
                        foreach ($assignments as $row) {
                            switch ($suspension['suspension_charge_method']) {
                                case SUSPENSION_CHARGE_METHOD_ONCE:
                                    $startdate = $row['datefrom'] > $today ? $row['datefrom'] : $today;
                                    [$year, $month, $dom] = explode('/', date('Y/n/j', $startdate));
                                    $commingPayDate = 0;
                                    $commingPayTimestamp = 0;
                                    switch ($row['periodvalue']) {
                                        case DISPOSABLE:
                                            $commingPayTimestamp = $row['at'];
                                            $commingPayDate = date('Y-m-d', $commingPayTimestamp);
                                            break;
                                        case MONTHLY:
                                            if ($row['at'] == 0) {
                                                $commingPayTimestamp = mktime(0, 0, 0, $month + 1, $row['at'], $year);
                                            } else {
                                                $commingPayTimestamp = mktime(0, 0, 0, $month, $row['at'], $year);
                                            }
                                            $commingPayDate = date('Y-m-d', $commingPayTimestamp);
                                            break;
                                        case QUARTERLY:
                                            [$d, $m] = explode('/', $row['at']);
                                            $quarterlyDate1 = mktime(0, 0, 0, $m, $d, $year);
                                            $quarterlyDate2 = strtotime('+3 months', $quarterlyDate1);
                                            $quarterlyDate3 = strtotime('+6 months', $quarterlyDate1);
                                            $quarterlyDate4 = strtotime('+9 months', $quarterlyDate1);

                                            if ($quarterlyDate1 <= $row['datefrom']) {
                                                if ($quarterlyDate2 <= $row['datefrom']) {
                                                    if ($quarterlyDate3 <= $row['datefrom']) {
                                                        if ($quarterlyDate4 <= $row['datefrom']) {
                                                            $commingPayTimestamp = strtotime('+3 months', $quarterlyDate4);
                                                        } else {
                                                            $commingPayTimestamp = $quarterlyDate3;
                                                        }
                                                    } else {
                                                        $commingPayTimestamp = $quarterlyDate3;
                                                    }
                                                } else {
                                                    $commingPayTimestamp = $quarterlyDate2;
                                                }
                                            } else {
                                                $commingPayTimestamp = $quarterlyDate1;
                                            }
                                            $commingPayDate = date('Y-m-d', $commingPayTimestamp);
                                            break;
                                        case HALFYEARLY:
                                            [$d, $m] = explode('/', $row['at']);
                                            $halfyearlyDate1 = mktime(0, 0, 0, $m, $d, $year);
                                            $halfyearlyDate2 = strtotime('+6 months', $halfyearlyDate1);

                                            if ($halfyearlyDate1 <= $row['datefrom']) {
                                                if ($halfyearlyDate2 <= $row['datefrom']) {
                                                    $commingPayTimestamp = strtotime('+6 months', $halfyearlyDate2);
                                                } else {
                                                    $commingPayTimestamp = $halfyearlyDate2;
                                                }
                                            } else {
                                                $commingPayTimestamp = $halfyearlyDate1;
                                            }
                                            $commingPayDate = date('Y-m-d', $commingPayTimestamp);
                                            break;
                                        case YEARLY:
                                            [$d, $m] = explode('/', $row['at']);
                                            $yearlyDate = mktime(0, 0, 0, $m, $d, $year);
                                            if ($yearlyDate <= $row['datefrom']) {
                                                $commingPayTimestamp = strtotime('+1 year', $yearlyDate);
                                            } else {
                                                $commingPayTimestamp = $yearlyDate;
                                            }
                                            $commingPayDate = date('Y-m-d', $commingPayTimestamp);
                                            break;
                                    }

                                    if (!isset($suspension_at) || $commingPayTimestamp < $suspension_at) {
                                        $suspension_at = $commingPayTimestamp;
                                    }
                                    break;
                                case SUSPENSION_CHARGE_METHOD_PERIODICALLY:
                                    $startdate = $row['datefrom'] > $today ? $row['datefrom'] : $today;
                                    [$year, $month, $dom] = explode('/', date('Y/n/j', $startdate));
                                    $commingPayDay = 0;
                                    $commingPayTimestamp = 0;
                                    switch ($row['periodvalue']) {
                                        case DISPOSABLE:
                                            $commingPayTimestamp = $row['at'];
                                            $commingPayDay = date('j', $row['at']);
                                            break;
                                        case MONTHLY:
                                            $commingPayTimestamp = mktime(0, 0, 0, $month, $row['at'], $year);
                                            $commingPayDay = $row['at'];
                                            break;
                                        case QUARTERLY:
                                        case HALFYEARLY:
                                        case YEARLY:
                                            [$d, $m] = explode('/', $row['at']);
                                            $commingPayTimestamp = mktime(0, 0, 0, $month, $d, $year);
                                            $commingPayDay = $d;
                                            break;
                                    }

                                    if (!isset($suspension_at) || $commingPayDay < $suspension_at) {
                                        $suspension_at = $commingPayDay;
                                    }
                                    break;
                            }
                        }
                    }
                }
            }

            $suspensionArgs = array(
                'at' => $suspension_at,
                'datefrom' => !empty($suspension['datefrom']) ? $suspension['datefrom'] : 0,
                'dateto' => !empty($suspension['dateto']) ? (strtotime('+ 1 day', $suspension['dateto']) - 1) : 0,
                'chargemethod' => $suspension['suspension_charge_method'],
                'calculationmethod' => $suspension['suspension_calculation_method'],
                'value' => $suspensionValue,
                'percentage' => $suspensionPercentage,
                'netflag' => $suspension_netflag,
                'currency' => $suspension_currency,
                'taxid' => $suspension_taxid,
                'note' => !empty($suspension['note']) ? Utils::removeInsecureHtml($suspension['note']) : null,
                'customerid' => isset($suspension['suspend_all']) ? $customerId : null,
                'id' => $suspensionId,
            );

            $LMS->updateSuspension($suspensionArgs);

            $DB->UnLockTables();
            $DB->CommitTrans();
            $SESSION->redirect_to_history_entry();
        }
    } else {
        $suspension = $LMS->getSuspensions(array('suspension_id' => $suspensionId, 'by_suspension_id' => true));
        $a = $suspension[$suspensionId];
        $a['tariffid'] = -3;
        $a['customer_id'] = $customerId;
        $a['suspension_id'] = $suspensionId;
        $a['datefrom'] = $a['suspension_datefrom'];
        $a['dateto'] = $a['suspension_dateto'];
        $a['netflag'] = $a['suspension_netflag'];
        $a['suspension_gross_value'] = $a['suspension_value'];
        $a['suspension_net_value'] = $a['suspension_value'];
        $a['suspend_all'] = $a['suspension_suspend_all'];
        $a['currency'] = !empty($a['suspension_currency']) ? $a['suspension_currency'] : Localisation::getDefaultCurrency();
        $a['taxid'] = $a['suspension_tax_id'];
    }
} else {
    // get customer name and check privileges using customerview
    $aids = $_POST['customerassignments'] ?? array($_GET['id']);
    $aids = Utils::filterIntegers($aids);
    if (empty($aids)) {
        $SESSION->redirect_to_history_entry();
    }

    if (count($aids) == 1) {
        $aid = reset($aids);
        $customer = $DB->GetRow(
            'SELECT a.customerid AS id, c.divisionid, '
            . $DB->Concat('c.lastname', "' '", 'c.name') . ' AS name
        FROM assignments a
        JOIN customerview c ON (c.id = a.customerid)
        WHERE a.id = ?',
            array($aid)
        );
        if (!$customer) {
            $SESSION->redirect_to_history_entry();
        }
    } else {
        if ($DB->GetOne(
            'SELECT COUNT(a.id)
            FROM assignments a
            JOIN customerview c ON c.id = a.customerid
            WHERE a.id IN ?',
            array($aids)
        ) != count($aids)) {
            $SESSION->redirect_to_history_entry();
        }
    }

    if (isset($_GET['action']) && $_GET['action'] == 'toggle_suspension') {
        foreach ($aids as $aid) {
            $LMS->toggleAssignmentSuspension($aid);
        }
        $SESSION->redirect_to_history_entry();
    }

    if (isset($_POST['assignment'])) {
        $a = $_POST['assignment'];

        foreach ($a as $key => $val) {
            if (!is_array($val)) {
                $a[$key] = trim($val);
            }
        }

        $a['id'] = $_GET['id'];
        $a['customerid'] = $customer['id'];
        $a['liabilityid'] = $_GET['lid'] ?? null;

        $period = sprintf('%d', $a['period']);

        switch ($period) {
            case DAILY:
                $at = 0;
                break;

            case WEEKLY:
                $at = sprintf('%d', $a['at']);

                if (ConfigHelper::checkConfig('phpui.use_current_payday') && $at == 0) {
                    $at = date('N', time());
                }

                if ($at < 1 || $at > 7) {
                    $error['at'] = trans('Incorrect day of week (1-7)!');
                }
                break;

            case MONTHLY:
                if ($a['at'] == '') {
                    if (ConfigHelper::checkConfig('phpui.use_current_payday')) {
                        $at = date('j', time());
                    } elseif (!ConfigHelper::checkConfig('phpui.use_current_payday')
                        && ConfigHelper::getConfig('phpui.default_monthly_payday') > 0) {
                        $at = ConfigHelper::getConfig('phpui.default_monthly_payday');
                    } else {
                        $at = -1;
                    }
                } else {
                    $at = intval($a['at']);
                }

                if ($at > 28 || $at < 0) {
                    $error['at'] = trans('Incorrect day of month (1-28)!');
                } else {
                    $a['at'] = $at;
                }

                break;

            case QUARTERLY:
                if (ConfigHelper::checkConfig('phpui.use_current_payday') && !$a['at']) {
                    $d = date('j', time());
                    $m = date('n', time());
                    $a['at'] = $d.'/'.$m;
                } elseif (!preg_match('/^[0-9]{2}\/[0-9]{2}$/', $a['at'])) {
                    $error['at'] = trans('Incorrect date format! Enter date in DD/MM format!');
                } else {
                    [$d, $m] = explode('/', $a['at']);
                }

                if (!$error) {
                    if ($d>30 || $d<1 || ($d>28 && $m==2)) {
                        $error['at'] = trans('This month doesn\'t contain specified number of days');
                    }
                    if ($m>3 || $m<1) {
                        $error['at'] = trans('Incorrect month number (max.3)!');
                    }

                    $at = ($m-1) * 100 + $d;
                }
                break;

            case HALFYEARLY:
                if (!preg_match('/^[0-9]{2}\/[0-9]{2}$/', $a['at']) && $a['at']) {
                    $error['at'] = trans('Incorrect date format! Enter date in DD/MM format!');
                } elseif (ConfigHelper::checkConfig('phpui.use_current_payday') && !$a['at']) {
                    $d = date('j', time());
                    $m = date('n', time());
                    $a['at'] = $d.'/'.$m;
                } else {
                    [$d, $m] = explode('/', $a['at']);
                }

                if (!$error) {
                    if ($d>30 || $d<1 || ($d>28 && $m==2)) {
                        $error['at'] = trans('This month doesn\'t contain specified number of days');
                    }
                    if ($m>6 || $m<1) {
                        $error['at'] = trans('Incorrect month number (max.6)!');
                    }

                    $at = ($m-1) * 100 + $d;
                }
                break;

            case YEARLY:
                if (ConfigHelper::checkConfig('phpui.use_current_payday') && !$a['at']) {
                    $d = date('j', time());
                    $m = date('n', time());
                    $a['at'] = $d.'/'.$m;
                } elseif (!preg_match('/^[0-9]{2}\/[0-9]{2}$/', $a['at'])) {
                    $error['at'] = trans('Incorrect date format! Enter date in DD/MM format!');
                } else {
                    [$d, $m] = explode('/', $a['at']);
                }

                if (!$error) {
                    if ($d>30 || $d<1 || ($d>28 && $m==2)) {
                        $error['at'] = trans('This month doesn\'t contain specified number of days');
                    }
                    if ($m>12 || $m<1) {
                        $error['at'] = trans('Incorrect month number');
                    }

                    $ttime = mktime(12, 0, 0, $m, $d, 1990);
                    $at = date('z', $ttime) + 1;
                }
                break;

            default: // DISPOSABLE
                $period = DISPOSABLE;

                if (preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $a['at'])) {
                    [$y, $m, $d] = explode('/', $a['at']);
                    if (checkdate($m, $d, $y)) {
                        $at = mktime(0, 0, 0, $m, $d, $y);
                        if (empty($a['atwarning']) && $at < mktime(0, 0, 0)) {
                            $a['atwarning'] = true;
                            $error['at'] = trans('Incorrect date!');
                        }
                    } else {
                        $error['at'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
                    }
                } else {
                    $error['at'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
                }
                break;
        }

        if (isset($a['count'])) {
            if ($a['count'] == '') {
                $count = 1;
            } elseif (preg_match('/^[0-9]+(\.[0-9]+)?$/', $a['count'])) {
                $count = floatval($a['count']);
            } else {
                $error['count'] = trans('Incorrect count format! Numeric value required!');
            }
        }

        if (isset($a['paytime'])) {
            if (empty($a['paytime'])) {
                $paytime = 0;
            } elseif (preg_match('/^[\-]?[0-9]+$/', $a['paytime'])) {
                $paytime = intval($a['paytime']);
                if ($paytime == -1) {
                    $paytime = null;
                }
            } else {
                $error['paytime'] = trans('Invalid deadline format!');
            }
        }

        if (empty($a['datefrom'])) {
            $from = 0;
        } elseif (!preg_match('/^[0-9]+$/', $a['datefrom'])) {
            $error['datefrom'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
        } else {
            $from = $a['datefrom'];
        }

        if (empty($a['dateto'])) {
            $to = 0;
        } elseif (!preg_match('/^[0-9]+$/', $a['dateto'])) {
            $error['dateto'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
        } else {
            $to = strtotime('+ 1 day', $a['dateto']) - 1;
        }

        if ($to < $from && $to != 0 && $from != 0) {
            $error['dateto'] = trans('Incorrect date range!');
        }

        $a['discount'] = str_replace(',', '.', $a['discount']);
        $a['pdiscount'] = 0.0;
        $a['vdiscount'] = 0.0;
        if (preg_match('/^[0-9]+(\.[0-9]+)*$/', $a['discount'])) {
            $a['pdiscount'] = ($a['discount_type'] == DISCOUNT_PERCENTAGE ? floatval($a['discount']) : 0);
            $a['vdiscount'] = ($a['discount_type'] == DISCOUNT_AMOUNT ? floatval($a['discount']) : 0);
        }
        if ($a['pdiscount'] < 0 || $a['pdiscount'] > 100) {
            $error['discount'] = trans('Wrong discount value!');
        }

        if ($a['tariffid'] == -1) {
            unset($error['at']);
            $at = 0;
        } elseif (!$a['tariffid']) {
            if ($a['name'] == '') {
                $error['name'] = trans('Liability name is required!');
            }
            if (!$a['value'] && empty($a['netflag'])) {
                $error['value'] = trans('Liability value is required!');
            } elseif (!$a['netvalue'] && !empty($a['netflag'])) {
                $error['netvalue'] = trans('Liability value is required!');
            } elseif (!preg_match('/^[-]?[0-9.,]+$/', $a['value']) && empty($a['netflag'])) {
                $error['value'] = trans('Incorrect value!');
            } elseif (!preg_match('/^[-]?[0-9.,]+$/', $a['netvalue']) && !empty($a['netflag'])) {
                $error['netvalue'] = trans('Incorrect value!');
            } elseif ($a['discount_type'] == 2 && $a['discount'] && $a['value'] - $a['discount'] < 0) {
                $error['value'] = trans('Value less than discount are not allowed!');
                $error['discount'] = trans('Value less than discount are not allowed!');
            }
        } else {
            if ($a['discount_type'] == DISCOUNT_AMOUNT && $a['discount']
                && $DB->GetOne('SELECT value FROM tariffs WHERE id = ?', array($a['tariffid'])) - $a['discount'] < 0) {
                $error['value'] = trans('Value less than discount are not allowed!');
                $error['discount'] = trans('Value less than discount are not allowed!');
            }
        }

        if ($a['tarifftype'] != SERVICE_PHONE) {
            unset($a['phones']);
        }

        // try to restrict node assignment sharing
        if ($a['tariffid'] > 0 && !empty($a['nodes'])) {
            $restricted_nodes = $LMS->CheckNodeTariffRestrictions($a['id'], $a['nodes'], $from, $to);
            $node_multi_tariff_restriction = ConfigHelper::getConfig(
                'phpui.node_multi_tariff_restriction',
                '',
                true
            );
            if (preg_match('/^(error|warning)$/', $node_multi_tariff_restriction) && !empty($restricted_nodes)) {
                foreach ($restricted_nodes as $nodeid) {
                    if ($node_multi_tariff_restriction == 'error') {
                        $error['assignment[nodes][' . $nodeid . ']'] = trans('This item is already bound with another assignment!');
                    } else {
                        if (!isset($a['node_warns'][$nodeid])) {
                            $error['assignment[nodes][' . $nodeid . ']'] = trans('This item is already bound with another assignment!');
                        }
                        $a['node_warns'][$nodeid] = $nodeid;
                    }
                }
            }
        }

        if (!isset($CURRENCIES[$a['currency']])) {
            $error['currency'] = trans('Invalid currency selection!');
        }

        $hook_data = $LMS->executeHook(
            'customerassignmentedit_validation_before_submit',
            array(
                'a' => $a,
                'error' => $error
            )
        );

        $a = $hook_data['a'];
        $error = $hook_data['error'];

        if (!$error) {
            $DB->BeginTrans();

            if ($a['liabilityid']) {
                if ($a['tariffid'] > 0) {
                    $DB->Execute(
                        'UPDATE assignments SET tariffid = ?, liabilityid = NULL WHERE id = ?',
                        array($a['tariffid'], $a['id'])
                    );
                    $DB->Execute('DELETE FROM liabilities WHERE id=?', array($a['liabilityid']));
                    if ($SYSLOG) {
                        $args = array(
                            SYSLOG::RES_LIAB => $a['liabilityid'],
                            SYSLOG::RES_CUST => $customer['id']);
                        $SYSLOG->AddMessage(SYSLOG::RES_LIAB, SYSLOG::OPER_DELETE, $args);
                    }
                    $a['liabilityid'] = null;
                } else {
                    $args = array(
                        'value' => str_replace(',', '.', $a['value']),
                        'flags' => (isset($a['splitpayment']) ? LIABILITY_FLAG_SPLIT_PAYMENT : 0)
                            + (isset($a['netflag']) ? LIABILITY_FLAG_NET_ACCOUT : 0),
                        'taxcategory' => $a['taxcategory'],
                        'currency' => $a['currency'],
                        'name' => $a['name'],
                        SYSLOG::RES_TAX => intval($a['taxid']),
                        'prodid' => $a['prodid'],
                        'type' => $a['type'],
                        'netvalue' => str_replace(',', '.', $a['netvalue']),
                        'note' => htmlspecialchars($a['note']),
                        SYSLOG::RES_LIAB => $a['liabilityid']
                    );
                    $DB->Execute(
                        'UPDATE liabilities SET value = ?, flags = ?, taxcategory = ?, currency = ?, name = ?,
                        taxid = ?, prodid = ?, type = ?, netvalue = ?, note = ?
                        WHERE id = ?',
                        array_values($args)
                    );
                    if ($SYSLOG) {
                        $args[SYSLOG::RES_CUST] = $customer['id'];
                        $SYSLOG->AddMessage(SYSLOG::RES_LIAB, SYSLOG::OPER_UPDATE, $args);
                    }
                }
            } else if (!$a['tariffid']) {
                $args = array(
                    'name' => $a['name'],
                    'value' => $a['value'],
                    'flags' => (isset($a['splitpayment']) ? LIABILITY_FLAG_SPLIT_PAYMENT : 0)
                        + (isset($a['netflag']) ? LIABILITY_FLAG_NET_ACCOUT : 0),
                    'taxcategory' => $a['taxcategory'],
                    'currency' => $a['currency'],
                    SYSLOG::RES_TAX => intval($a['taxid']),
                    'prodid' => $a['prodid'],
                    'type' => $a['type'],
                    'netvalue' => str_replace(',', '.', $a['netvalue']),
                    'note' => htmlspecialchars($a['note']),
                );
                $DB->Execute(
                    'INSERT INTO liabilities (name, value, flags, taxcategory, currency, taxid, prodid, type, netvalue, note)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    array_values($args)
                );

                $a['liabilityid'] = $DB->GetLastInsertID('liabilities');

                if ($SYSLOG) {
                    $args[SYSLOG::RES_LIAB] = $a['liabilityid'];
                    $args[SYSLOG::RES_CUST] = $customer['id'];
                    $SYSLOG->AddMessage(SYSLOG::RES_LIAB, SYSLOG::OPER_ADD, $args);
                }
            }

            if ($a['tariffid'] == -1) {
                $a['tariffid'] = 0;
                $a['discount'] = 0;
                $a['pdiscount'] = 0;
                $a['vdiscount'] = 0;
                unset($a['invoice']);
                unset($a['settlement']);
            }

            $args = array(
                SYSLOG::RES_TARIFF => empty($a['tariffid']) ? null : intval($a['tariffid']),
                SYSLOG::RES_CUST => $customer['id'],
                'attribute' => !empty($a['attribute']) ? $a['attribute'] : null,
                'period' => $period,
                'backwardperiod' => isset($a['backwardperiod']) ? 1 : 0,
                'at' => $at,
                'count' => $count,
                'note' => htmlspecialchars($a['note']),
                'invoice' => isset($a['invoice']) ? intval($a['invoice']) : 0,
                'separatedocument' => empty($a['separatedocument']) ? 0 : 1,
                'separateitem' => empty($a['separateitem']) ? 0 : 1,
                'settlement' => empty($a['settlement']) ? 0 : 1,
                'datefrom' => $from,
                'dateto' => $to,
                'pdiscount' => str_replace(',', '.', $a['pdiscount']),
                'vdiscount' => str_replace(',', '.', $a['vdiscount']),
                SYSLOG::RES_LIAB => $a['liabilityid'],
                SYSLOG::RES_NUMPLAN => !empty($a['numberplanid']) ? $a['numberplanid'] : null,
                'paytime' => $paytime ?? null,
                'paytype' => !empty($a['paytype']) ? $a['paytype'] : null,
                'recipient_address_id' => ($a['recipient_address_id'] >= 0) ? $a['recipient_address_id'] : null,
                SYSLOG::RES_ASSIGN => $a['id']
            );

            $DB->Execute('UPDATE assignments SET tariffid=?, customerid=?, attribute=?, period=?,
                backwardperiod=?, at=?, count=?, note=?,
                invoice=?, separatedocument=?, separateitem = ?, settlement=?, datefrom=?, dateto=?, pdiscount=?, vdiscount=?,
                liabilityid=?, numberplanid=?, paytime = ?, paytype=?, recipient_address_id=?
                WHERE id=?', array_values($args));
            if ($SYSLOG) {
                $SYSLOG->AddMessage(SYSLOG::RES_ASSIGN, SYSLOG::OPER_UPDATE, $args);

                $nodeassigns = $DB->GetAll('SELECT id, nodeid FROM nodeassignments WHERE assignmentid=?', array($a['id']));
                if (!empty($nodeassigns)) {
                    foreach ($nodeassigns as $nodeassign) {
                        $args = array(
                        SYSLOG::RES_NODEASSIGN => $nodeassign['id'],
                        SYSLOG::RES_CUST => $customer['id'],
                        SYSLOG::RES_NODE => $nodeassign['nodeid'],
                        SYSLOG::RES_ASSIGN => $a['id']
                        );
                        $SYSLOG->AddMessage(SYSLOG::RES_NODEASSIGN, SYSLOG::OPER_DELETE, $args);
                    }
                }
            }
            $DB->Execute('DELETE FROM nodeassignments WHERE assignmentid=?', array($a['id']));
            $DB->Execute('DELETE FROM voip_number_assignments WHERE assignment_id = ?', array($a['id']));

            if (!empty($a['nodes'])) {
                foreach ($a['nodes'] as $nodeid) {
                    $DB->Execute(
                        'INSERT INTO nodeassignments (nodeid, assignmentid) VALUES (?,?)',
                        array($nodeid, $a['id'])
                    );
                    if ($SYSLOG) {
                        $nodeaid = $DB->GetOne(
                            'SELECT id FROM nodeassignments WHERE nodeid = ? AND assignmentid = ?',
                            array($nodeid, $a['id'])
                        );
                        $args = array(
                            SYSLOG::RES_NODEASSIGN => $nodeaid,
                            SYSLOG::RES_CUST => $customer['id'],
                            SYSLOG::RES_NODE => $nodeid,
                            SYSLOG::RES_ASSIGN => $a['id']
                        );
                        $SYSLOG->AddMessage(SYSLOG::RES_NODEASSIGN, SYSLOG::OPER_ADD, $args);
                    }
                }
            }

            if (!empty($a['phones'])) {
                foreach ($a['phones'] as $p) {
                    $DB->Execute(
                        'INSERT INTO voip_number_assignments (number_id, assignment_id) VALUES (?,?)',
                        array($p, $a['id'])
                    );
                }
            }

            $LMS->executeHook(
                'customerassignmentedit_after_submit',
                array('a' => $a)
            );

            $DB->CommitTrans();

            $SESSION->redirect_to_history_entry();
        }

        $a['alltariffs'] = isset($a['alltariffs']);

        $SMARTY->assign('error', $error);
    } else {
        $a = $DB->GetRow(
            'SELECT a.id AS id, a.customerid, a.tariffid, a.period, a.backwardperiod,
            a.at, a.count, a.datefrom, a.dateto, a.numberplanid, a.paytime, a.paytype,
            a.invoice,
            a.separatedocument, a.separateitem,
            a.note,
            liabilities.type,
            (CASE WHEN liabilityid IS NULL
                THEN (CASE WHEN tariffs.flags & ? > 0 THEN 1 ELSE 0 END)
                ELSE (CASE WHEN liabilities.flags & ? > 0 THEN 1 ELSE 0 END)
            END) AS splitpayment,
            (CASE WHEN liabilityid IS NULL
                THEN (CASE WHEN tariffs.flags & ? > 0 THEN 1 ELSE 0 END)
                ELSE (CASE WHEN liabilities.flags & ? > 0 THEN 1 ELSE 0 END)
            END) AS netflag,
            (CASE WHEN liabilityid IS NULL THEN tariffs.taxcategory ELSE liabilities.taxcategory END) AS taxcategory,
            a.settlement, a.pdiscount, a.vdiscount, a.attribute, a.liabilityid,
            (CASE WHEN liabilityid IS NULL THEN tariffs.name ELSE liabilities.name END) AS name,
            liabilities.value AS value, liabilities.currency AS currency,
            liabilities.netvalue AS netvalue,
            liabilities.prodid AS prodid, liabilities.taxid AS taxid,
            recipient_address_id
            FROM assignments a
            LEFT JOIN tariffs ON (tariffs.id = a.tariffid)
            LEFT JOIN liabilities ON (liabilities.id = a.liabilityid)
            WHERE a.id = ?',
            array(
                TARIFF_FLAG_SPLIT_PAYMENT,
                LIABILITY_FLAG_SPLIT_PAYMENT,
                TARIFF_FLAG_NET_ACCOUNT,
                LIABILITY_FLAG_NET_ACCOUT,
                $_GET['id']
            )
        );

        $a['pdiscount'] = floatval($a['pdiscount']);
        $a['vdiscount'] = floatval($a['vdiscount']);
        if (!empty($a['pdiscount'])) {
            $a['discount'] = $a['pdiscount'];
            $a['discount_type'] = DISCOUNT_PERCENTAGE;
        } elseif (!empty($a['vdiscount'])) {
            $a['discount'] = $a['vdiscount'];
            $a['discount_type'] = DISCOUNT_AMOUNT;
        }

        switch ($a['period']) {
            case HALFYEARLY:
            case QUARTERLY:
                $a['at'] = sprintf('%02d/%02d', $a['at']%100, $a['at']/100+1);
                break;
            case YEARLY:
                $a['at'] = date('d/m', ($a['at']-1)*86400);
                break;
            case DISPOSABLE:
                if ($a['at']) {
                    $a['at'] = date('Y/m/d', $a['at']);
                }
                break;
        }

        $a['count'] = floatval($a['count']);

        if (!$a['tariffid'] && !$a['liabilityid']) {
            $a['tariffid'] = -1;
        }

        // tariff price variants
        if (!empty($a['tariffid'])) {
            $a['tariff_price_variants'] = $LMS->getTariffPriceVariants($a['tariffid']);
        }

        // nodes assignments
        $a['nodes'] = $DB->GetCol('SELECT nodeid FROM nodeassignments WHERE assignmentid=?', array($a['id']));

        // phone numbers assignments
        $a['phones'] = $DB->GetCol('SELECT number_id FROM voip_number_assignments WHERE assignment_id=?', array($a['id']));

        if (empty($a['currency'])) {
            $a['currency'] = Localisation::getDefaultCurrency();
        }

        if (empty($a['pdiscount']) && empty($a['vdiscount'])) {
            $default_assignment_discount_type = ConfigHelper::getConfig(
                'assignments.default_discount_type',
                ConfigHelper::getConfig('phpui.default_assignment_discount_type', 'percentage')
            );
            $a['discount_type'] = $default_assignment_discount_type == 'percentage' ? DISCOUNT_PERCENTAGE : DISCOUNT_AMOUNT;
        }

        if (!empty($a['tariffid'])) {
            $a['netflag'] = ConfigHelper::checkConfig('assignments.default_net_account');
        }
    }


    $SESSION->add_history_entry();

    $LMS->executeHook(
        'customerassignmentedit_before_display',
        array(
            'a' => $a,
            'smarty' => $SMARTY,
        )
    );
}

$layout['pagetitle'] = trans('Liability Edit: $a', '<A href="?m=customerinfo&id='.$customer['id'].'">'.$customer['name'].'</A>');
$SMARTY->assign('customernodes', $LMS->GetCustomerNodes($customer['id']));
$SMARTY->assign('customernetdevnodes', $LMS->getCustomerNetDevNodes($customer['id']));
$SMARTY->assign('voipaccounts', $LMS->GetCustomerVoipAccounts($customer['id']));
$SMARTY->assign('customeraddresses', $LMS->getCustomerAddresses($customer['id']));
$SMARTY->assign('numberplanlist', $LMS->GetNumberPlans(array(
    'doctype' => DOC_INVOICE,
    'cdate' => null,
    'division' => $customer['divisionid'],
    'next' => false,
)));

$SMARTY->assign('tags', $LMS->TarifftagGetAll());

$SMARTY->assign('tariffs', $LMS->GetTariffs($a['tariffid']));
$SMARTY->assign('taxeslist', $LMS->GetTaxes());
$defaultTaxIds = $LMS->GetTaxes(null, null, true);
if (is_array($defaultTaxIds)) {
    $defaultTaxId = reset($defaultTaxIds);
    $defaultTaxId = $defaultTaxId['id'];
} else {
    $defaultTaxId = 0;
}
$SMARTY->assign('defaultTaxId', $defaultTaxId);
if (!empty($a['nodes']) && is_array($a['nodes'])) {
    $a['nodes'] = array_flip($a['nodes']);
}
$SMARTY->assign('assignment', $a);
$assignmentsWithSuspensions = $LMS->getCustomerAssignments($customer['id'], array('show_expired' => true, 'show_approved' => false, 'with_suspensions' => true));
if (!empty($assignmentsWithSuspensions)) {
    $assignments = !empty($assignmentsWithSuspensions['assignments']) ? $assignmentsWithSuspensions['assignments'] : array();
    $suspensions = !empty($assignmentsWithSuspensions['suspensions']) ? $assignmentsWithSuspensions['suspensions'] : array();
}
$SMARTY->assign('assignments', $assignments);
$SMARTY->assign('suspensions', $suspensions);
$SMARTY->assign('customerinfo', $customer);
$SMARTY->display('customer/customerassignmentsedit.html');
