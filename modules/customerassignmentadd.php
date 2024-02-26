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

// get customer name and check privileges using customerview
$customer = $DB->GetRow('SELECT id, divisionid, '
    .$DB->Concat('lastname', "' '", 'name').' AS name
	FROM customerview WHERE id = ?', array($_GET['id']));

if (!$customer) {
    $SESSION->redirect_to_history_entry();
}

if (isset($_POST['assignment'])) {
    $a = r_trim($_POST['assignment']);

    if (!isset($a['suspended_assignments']) && !isset($a['suspend_all'])) {
        $result = $LMS->ValidateAssignment($a);
        extract($result);
        if (empty($a['taxid'])) {
            $error['taxid'] = trans('— no tax rates defined —');
        }
        if (isset($schemaid) && !$LMS->CheckSchemaModifiedValues($a)) {
            $error['promotion-select'] = trans('Illegal promotion schema period value modification!');
        }// try to restrict node assignment sharing
        if ($a['tariffid'] > 0 && !empty($a['nodes'])) {
            $restricted_nodes = $LMS->CheckNodeTariffRestrictions($a['id'] ?? null, $a['nodes'], $from, $to);
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
        $hook_data = $LMS->executeHook(
            'customerassignmentadd_validation_before_submit',
            array(
                'a' => $a,
                'error' => $error
            )
        );
        $a = $hook_data['a'];
        $error = $hook_data['error'];
        if (!$error) {
            $a['customerid'] = $customer['id'];
            $a['period'] = $period;
            $a['at'] = $at;
            $a['datefrom'] = $from;
            $a['dateto'] = $to;
            $a['count'] = $count;
            $a['paytime'] = $paytime;

            $DB->BeginTrans();

            $LMS->UpdateExistingAssignments($a);

            if (isset($a['sassignmentid'][$schemaid]) && is_array($a['sassignmentid'][$schemaid])) {
                $modifiedvalues = $a['values'][$schemaid] ?? array();
                $counts = $a['counts'][$schemaid];
                $backwardperiods = $a['backwardperiods'][$schemaid];
                $copy_a = $a;
                $snodes = $a['snodes'][$schemaid] ?? array();
                $sphones = $a['sphones'][$schemaid] ?? array();

                foreach ($a['sassignmentid'][$schemaid] as $label => $v) {
                    if (!$v) {
                        continue;
                    }

                    $copy_a['promotionassignmentid'] = $v;
                    $copy_a['modifiedvalues'] = $modifiedvalues[$label][$v] ?? array();
                    $copy_a['count'] = $counts[$label];
                    $copy_a['backwardperiod'] = $backwardperiods[$label][$v];
                    $copy_a['nodes'] = $snodes[$label] ?? array();
                    $copy_a['phones'] = $sphones[$label] ?? array();
                    $tariffid = $LMS->AddAssignment($copy_a);
                }
            } else {
                $a['taxvalue'] = $DB->GetOne('SELECT value FROM taxes WHERE id = ?', array($a['taxid']));
                $tariffid = $LMS->AddAssignment($a);
            }

            if ($a['tarifftype'] == SERVICE_PHONE && !empty($a['phones'])) {
                $tariffid = $tariffid[0];
            }

            $DB->CommitTrans();

            $LMS->executeHook(
                'customerassignmentadd_after_submit',
                array(
                    'assignment' => $a,
                )
            );

            $SESSION->redirect_to_history_entry();
        }

        $a['alltariffs'] = isset($a['alltariffs']);
    } else {
        //region suspension common part
        $suspension = $a;
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
        );
        //endregion

        if (isset($suspension['suspend_all'])) {
            // suspend all
            $suspension['existing_suspend_all'] = $LMS->getCustomerAssignments($customer['id'], array('suspend_all' => true));
            $error = $LMS->ValidateSuspension($suspension);
            if (empty($error)) {
                $assignments = $LMS->getCustomerAssignments($customer['id'], array('not_suspended' => true));
                if (!empty($assignments)) {
                    foreach ($assignments as $row) {
                        switch ($row['assignment_charge_method']) {
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
                                        $commingPayTimestamp = mktime(0, 0, 0, $month, $row['at'], $year);
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
                                $dom = date('j', $startdate);
                                $commingPayDay = 0;

                                switch ($row['periodvalue']) {
                                    case DISPOSABLE:
                                        $commingPayDay = date('j', $row['at']);
                                        break;
                                    case MONTHLY:
                                        $commingPayDay = $row['at'];
                                        break;
                                    case QUARTERLY:
                                    case HALFYEARLY:
                                    case YEARLY:
                                        [$d, $m] = explode('/', $row['at']);
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

                $suspensionArgs['at'] = $suspension_at;
                $suspensionArgs['customerid'] = $customer['id'];

                $DB->BeginTrans();
                $DB->LockTables(array('suspensions', 'assignmentsuspensions'));

                $LMS->addSuspension($suspensionArgs);

                $DB->UnLockTables();
                $DB->CommitTrans();
                $SESSION->redirect_to_history_entry();
            }
        } elseif (!empty($suspension['suspended_assignments'])) {
            // suspend group
            $suspension['existing_suspensions'] = $LMS->getCustomerAssignments($customer['id'], array('suspended' => true));
            $error = $LMS->ValidateSuspension($suspension);
            if (empty($error)) {
                $suspendedAssignemnts = implode(',', array_keys($suspension['suspended_assignments']));
                $assignments = $LMS->getCustomerAssignments($customer['id'], array('assignments' => $suspendedAssignemnts));
                if (!empty($assignments)) {
                    foreach ($assignments as $row) {
                        switch ($row['assignment_charge_method']) {
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
                                        $commingPayTimestamp = mktime(0, 0, 0, $month, $row['at'], $year);
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
                                $dom = date('j', $startdate);
                                $commingPayDay = 0;

                                switch ($row['periodvalue']) {
                                    case DISPOSABLE:
                                        $commingPayDay = date('j', $row['at']);
                                        break;
                                    case MONTHLY:
                                        $commingPayDay = $row['at'];
                                        break;
                                    case QUARTERLY:
                                    case HALFYEARLY:
                                    case YEARLY:
                                        [$d, $m] = explode('/', $row['at']);
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

                $suspensionArgs['at'] = $suspension_at;
                $suspensionArgs['customerid'] = null;

                $DB->BeginTrans();
                $DB->LockTables(array('suspensions', 'assignmentsuspensions'));

                $suspensionId = $LMS->addSuspension($suspensionArgs);

                foreach ($suspension['suspended_assignments'] as $akey => $a) {
                    $args = array(
                        'suspension_id' => $suspensionId,
                        'assignment_id' => $akey,
                        'customerid' => $customer['id'],
                    );
                    $LMS->addAssignmentSuspension($args);
                }


                $DB->UnLockTables();
                $DB->CommitTrans();

                $SESSION->redirect_to_history_entry();
            }
        }
    }

    $SMARTY->assign('error', $error);
} else {
    $default_document_type = ConfigHelper::getConfig(
        'assignments.default_document_type',
        ConfigHelper::getConfig('phpui.default_assignment_invoice')
    );
    if (!empty($default_document_type)) {
        if (preg_match('/^[0-9]+$/', $default_document_type)) {
            $a['invoice'] = $default_document_type;
        } elseif (ConfigHelper::checkValue($default_document_type)) {
            $a['invoice'] = DOC_INVOICE;
        }
    }
    $default_assignment_settlement = ConfigHelper::getConfig(
        'assignments.default_begin_period_settlement',
        ConfigHelper::getConfig('phpui.default_assignment_settlement')
    );
    if (!empty($default_assignment_settlement)) {
        if (preg_match('/^[0-9]+$/', $default_assignment_settlement)) {
            $a['settlement'] = $default_assignment_settlement;
        } elseif (ConfigHelper::checkValue($default_assignment_settlement)) {
            $a['settlement'] = 1;
        }
    }
    $a['last-settlement'] = ConfigHelper::checkConfig(
        'assignments.default_end_period_settlement',
        ConfigHelper::checkConfig('phpui.default_assignment_last_settlement')
    );
    $a['align-periods'] = ConfigHelper::checkConfig(
        'assignments.default_align_periods',
        ConfigHelper::checkConfig('phpui.default_assignment_align_periods', true)
    );
    $default_assignment_period = ConfigHelper::getConfig(
        'assignments.default_period',
        ConfigHelper::getConfig('phpui.default_assignment_period')
    );
    if (!empty($default_assignment_period)) {
        $a['period'] = $default_assignment_period;
    }
    $default_assignment_at = ConfigHelper::getConfig(
        'assignments.default_at',
        ConfigHelper::getConfig('phpui.default_assignment_at')
    );
    if (!empty($default_assignment_at)) {
        $a['at'] = $default_assignment_at;
    }

    $a['type'] = intval(ConfigHelper::getConfig(
        'assignments.default_liability_type',
        ConfigHelper::getConfig('phpui.default_liability_type', '-1')
    ));

    $a['check_all_terminals'] =
        ConfigHelper::checkConfig(
            'promotions.schema_all_terminal_check',
            ConfigHelper::checkConfig('phpui.promotion_schema_all_terminal_check')
        );

    $default_assignment_discount_type = ConfigHelper::getConfig(
        'assignments.default_discount_type',
        ConfigHelper::getConfig('phpui.default_assignment_discount_type', 'percentage')
    );
    $a['discount_type'] = $default_assignment_discount_type == 'percentage' ? DISCOUNT_PERCENTAGE : DISCOUNT_AMOUNT;

    $default_existing_assignment_operation = ConfigHelper::getConfig(
        'assignments.default_existing_operation',
        ConfigHelper::getConfig('phpui.default_existing_assignment_operation', 'keep')
    );
    $existing_assignment_operation_map = array(
        'keep' => EXISTINGASSIGNMENT_KEEP,
        'suspend' => EXISTINGASSIGNMENT_SUSPEND,
        'cut' => EXISTINGASSIGNMENT_CUT,
        'delete' => EXISTINGASSIGNMENT_DELETE,
    );
    if (isset($existing_assignment_operation_map[$default_existing_assignment_operation])) {
        $a['existing_assignments']['operation'] = $existing_assignment_operation_map[$default_existing_assignment_operation];
    } else {
        $a['existing_assignments']['operation'] = EXISTINGASSIGNMENT_KEEP;
    }

    if (isset($_GET['nodeid']) && ($nodeid = intval($_GET['nodeid'])) > 0) {
        $a['nodes'] = array(
            $nodeid => $nodeid,
        );
    }

    $a['netflag'] = ConfigHelper::checkConfig('assignments.default_net_account');

    $a['count'] = 1;
    $a['currency'] = Localisation::getDefaultCurrency();
}

$layout['pagetitle'] = trans('New Liability: $a', '<a href="?m=customerinfo&id='.$customer['id'].'">'.$customer['name'].'</a>');

$SESSION->add_history_entry();

$LMS->executeHook(
    'customerassignmentadd_before_display',
    array(
        'a' => $a,
        'smarty' => $SMARTY,
    )
);

$SMARTY->assign('promotions', $LMS->GetPromotions());
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

$SMARTY->assign('assignment', $a);

$SMARTY->assign('tariffs', $LMS->GetTariffs());
$SMARTY->assign('taxeslist', $LMS->GetTaxes());
$defaultTaxIds = $LMS->GetTaxes(null, null, true);
if (is_array($defaultTaxIds)) {
    $defaultTaxId = reset($defaultTaxIds);
    $defaultTaxId = $defaultTaxId['id'];
} else {
    $defaultTaxId = 0;
}
$SMARTY->assign('defaultTaxId', $defaultTaxId);
//$SMARTY->assign('defaultCurrency', Localisation::getDefaultCurrency());
//$SMARTY->assign('defaultNetflag', ConfigHelper::getConfig('suspensions.default_netflag', 0));

$assignmentsWithSuspensions = $LMS->getCustomerAssignments($customer['id'], array('show_expired' => true, 'show_approved' => false, 'with_suspensions' => true));
if (!empty($assignmentsWithSuspensions)) {
    $assignments = !empty($assignmentsWithSuspensions['assignments']) ? $assignmentsWithSuspensions['assignments'] : array();
    $suspensions = !empty($assignmentsWithSuspensions['suspensions']) ? $assignmentsWithSuspensions['suspensions'] : array();
}
$SMARTY->assign('assignments', $assignments);
$SMARTY->assign('suspensions', $suspensions);
$SMARTY->assign('customerinfo', $customer);

$SMARTY->display('customer/customerassignmentsedit.html');
