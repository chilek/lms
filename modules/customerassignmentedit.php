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

if (isset($_GET['action']) && $_GET['action'] == 'suspend') {
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
            'note' => !empty($a['note']) ? $a['note'] : null,
            'suspended' => empty($a['suspended']) ? 0 : 1,
            'period' => $period,
            'backwardperiod' => isset($a['backwardperiod']) ? 1 : 0,
            'at' => $at,
            'count' => $count,
            'invoice' => isset($a['invoice']) ? intval($a['invoice']) : 0,
            'separatedocument'  => strlen($a['separatedocumentvalue']) ? $a['separatedocumentvalue'] : (strlen($a['separatedocument']) ? $a['separatedocument'] : null),
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
            'recipient_address_id' => ($a['recipient_address_id'] > 0) ? $a['recipient_address_id'] : null,
            SYSLOG::RES_ASSIGN => $a['id']
        );

        $DB->Execute('UPDATE assignments SET tariffid=?, customerid=?, attribute=?, note = ?, suspended = ?, period=?,
            backwardperiod=?, at=?, count=?,
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
        a.settlement, a.pdiscount, a.vdiscount, a.attribute, a.suspended, a.liabilityid,
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

$layout['pagetitle'] = trans('Liability Edit: $a', '<A href="?m=customerinfo&id='.$customer['id'].'">'.$customer['name'].'</A>');

$SESSION->add_history_entry();

$LMS->executeHook(
    'customerassignmentedit_before_display',
    array(
        'a' => $a,
        'smarty' => $SMARTY,
    )
);

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

// -----

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
$assignments = $LMS->GetCustomerAssignments($customer['id'], true, false);
$SMARTY->assign('assignments', $assignments);
$SMARTY->assign('customerinfo', $customer);

$document_separation_groups = array();
foreach ($assignments as $assignment) {
    if (isset($assignment['separatedocument'])) {
        $document_separation_groups[$assignment['separatedocument']] = $assignment['separatedocument'];
    }
}
sort($document_separation_groups, SORT_STRING);
$SMARTY->assign('document_separation_groups', $document_separation_groups);

$SMARTY->display('customer/customerassignmentsedit.html');
