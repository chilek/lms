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
    $a = $_POST['assignment'];

    $result = $LMS->ValidateAssignment($a);
    extract($result);
    if (empty($a['taxid'])) {
        $error['taxid'] = trans('— no tax rates defined —');
    }

    if (isset($schemaid) && !$LMS->CheckSchemaModifiedValues($a)) {
        $error['promotion-select'] = trans('Illegal promotion schema period value modification!');
    }

    // try to restrict node assignment sharing
    if ($a['tariffid'] > 0 && !empty($a['nodes'])) {
        $restricted_nodes = $LMS->CheckNodeTariffRestrictions($a['id'] ?? null, $a['nodes'], $from, $to);
        $node_multi_tariff_restriction = ConfigHelper::getConfig(
            'nodes.multi_tariff_restriction',
            ConfigHelper::getConfig(
                'phpui.node_multi_tariff_restriction',
                '',
                true
            )
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
        $a['period']     = $period;
        $a['at']         = $at;
        $a['datefrom']   = $from;
        $a['dateto']     = $to;
        $a['count']      = $count;
        $a['paytime']    = $paytime;

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
    $a['target_price_trigger'] = ConfigHelper::checkConfig('assignments.default_target_discounted_price');

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

    $a['suspended'] = ConfigHelper::checkConfig('assignments.default_suspended');

    if (isset($_GET['nodeid']) && ($nodeid = intval($_GET['nodeid'])) > 0) {
        $a['nodes'] = array(
            $nodeid => $nodeid,
        );
    }

    $a['netflag'] = ConfigHelper::checkConfig('assignments.default_net_account');

    $a['count'] = 1;
    $a['currency'] = Localisation::getDefaultCurrency();
}

$layout['pagetitle'] = trans('New Liability: $a', '<A href="?m=customerinfo&id='.$customer['id'].'">'.$customer['name'].'</A>');

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
$assignments = $LMS->GetCustomerAssignments($customer['id'], true, false);
$SMARTY->assign('assignments', $assignments);
$SMARTY->assign('customerinfo', $customer);

$document_separation_groups = array();
if (!empty($assignments)) {
    foreach ($assignments as $assignment) {
        if (isset($assignment['separatedocument'])) {
            $document_separation_groups[$assignment['separatedocument']] = $assignment['separatedocument'];
        }
    }
    sort($document_separation_groups, SORT_STRING);
}
$SMARTY->assign('document_separation_groups', $document_separation_groups);

$SMARTY->display('customer/customerassignmentsedit.html');
