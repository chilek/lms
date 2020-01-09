<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

if (!ConfigHelper::checkConfig('privileges.superuser') && !ConfigHelper::checkConfig('privileges.reports')) {
    access_denied();
}

$type = isset($_GET['type']) ? $_GET['type'] : '';

switch ($type) {
    case 'customerlist':
        $time = 0;
        $search['type'] = $_POST['type'];
        $search['linktype'] = $_POST['linktype'];

        if (isset($_POST['division'])) {
            $division = intval($_POST['division']);
        } else {
            $division = null;
        }

        if ($_POST['day']) {
            list($year, $month, $day) = explode('/', $_POST['day']);
            $time = mktime(0, 0, 0, $month, $day+1, $year);
        }

        if ($_POST['docfrom']) {
            list($year, $month, $day) = explode('/', $_POST['docfrom']);
            $docfrom = mktime(0, 0, 0, $month, $day, $year);
        } else {
            $docfrom = 0;
        }

        if ($_POST['docto']) {
            list($year, $month, $day) = explode('/', $_POST['docto']);
            $docto = mktime(23, 59, 59, $month, $day, $year);
        } else {
            $docto = 0;
        }

        if (!empty($_POST['doctype']) || !empty($docfrom) || !empty($docto)) {
            $search['doctype'] = intval($_POST['doctype']).':'.$docfrom.':'.$docto;
        }
        if (!empty($_POST['stateid'])) {
            $search['stateid'] = intval($_POST['stateid']);
        }

        $order = $_POST['order'].','.$_POST['direction'];
        $state = $_POST['filter'];
        $statesqlskey = $_POST['sk'];
        $network = $_POST['network'];
        $customergroup = $_POST['customergroup'];
        $sqlskey = 'AND';
        $nodegroup = $_POST['nodegroup'];
        $sendingregister = isset($_POST['sendingregister']);
        $customernodes = isset($_POST['customernodes']);

        $without_nodes = false;
        $state_labels = array();
        foreach ($state as $state_item) {
            switch ($state_item) {
                case 0:
                    $state_labels[] = trans('All');
                    break;
                case CSTATUS_INTERESTED:
                    $state_labels[] = trans('Interested<!status>');
                    break;
                case CSTATUS_WAITING:
                    $state_labels[] = trans('Awaiting');
                    break;
                case CSTATUS_DISCONNECTED:
                    $state_labels[] = trans('Disconnected<!status>');
                    break;
                case CSTATUS_CONNECTED:
                    $state_labels[] = trans('Connected<!status>');
                    break;
                case CSTATUS_DEBT_COLLECTION:
                    $state_labels[] = trans('Debt Collection<!status>');
                    break;
                case 51:
                    $state_labels[] = trans('Disconnected<!status>');
                    break;
                case 52:
                case 57:
                case 58:
                    $state_labels[] = trans('Indebted<!status>');
                    break;
                case 59:
                    $state_labels[] = trans('Without Contracts');
                    break;
                case 60:
                    $state_labels[] = trans('Expired Contracts');
                    break;
                case 61:
                    $state_labels[] = trans('Expiring Contracts');
                    break;
                case 63:
                    $state_labels[] = trans('Connected<!status>');
                    break;
                case -1:
                    $state_labels[] = trans('Without Nodes');
                    $without_nodes = true;
                    break;
            }
        }

        $param_labels = array();
        if ($_POST['network']) {
            $param_labels[] = trans('Network: $a', $LMS->GetNetworkName($_POST['network']));
        }
        if ($_POST['customergroup']) {
            $param_labels[] = trans('Group: $a', $LMS->CustomergroupGetName($_POST['customergroup']));
        }
        $layout['pagetitle'] = trans(
            '$a Customer List $b',
            implode(' ' . trans($statesqlskey == 'OR' ? 'or<!operator>' : 'and<!operator>') . ' ', array_unique($state_labels)),
            empty($param_labels) ? '' : '<br>(' . implode(', ', $param_labels) . ')'
        );

        if ($without_nodes) {
            if ($customerlist = $LMS->GetCustomerList(compact("order", "customergroup", "search", "time", "sqlskey", "division"))) {
                unset($customerlist['total']);
                unset($customerlist['state']);
                unset($customerlist['order']);
                unset($customerlist['below']);
                unset($customerlist['over']);
                unset($customerlist['direction']);

                foreach ($customerlist as $idx => $row) {
                    if (! $row['account']) {
                        $ncustomerlist[] = $customerlist[$idx];
                    }
                }
            }
            $SMARTY->assign('customerlist', $ncustomerlist);
        } else {
            $SMARTY->assign('customerlist', $LMS->GetCustomerList(
                compact("order", "state", "statesqlskey", "network", "customergroup", "search", "time", "sqlskey", "nodegroup", "division", "customernodes")
            ));
        }

        $SMARTY->assign('contactlist', $DB->GetAllByKey(
            'SELECT customerid, MIN(contact) AS phone
				FROM customercontacts WHERE contact <> \'\' AND type & ' . (CONTACT_MOBILE | CONTACT_FAX | CONTACT_LANDLINE) . ' > 0
				GROUP BY customerid',
            'customerid',
            array()
        ));

        $SMARTY->assign('customernodes', $customernodes);

        if ($sendingregister) {
            $print_template = 'print/printcustomerlist-sendingbook.html';
        } else {
            $print_template = 'print/printcustomerlist.html';
        }

        if (strtolower(ConfigHelper::getConfig('phpui.report_type')) == 'pdf') {
            $output = $SMARTY->fetch("$print_template");
            html2pdf($output, trans('Reports'), $layout['pagetitle'], null, null, 'L');
        } else {
            $SMARTY->display("$print_template");
        }
        break;

    case 'customerbalance':
        /********************************************/

        $from = $_POST['from'];
        $to = $_POST['to'];

        // date format 'yyyy/mm/dd'
        list($year, $month, $day) = explode('/', $from);
        $date['from'] = mktime(0, 0, 0, $month, $day, $year);

        if ($to) {
            list($year, $month, $day) = explode('/', $to);
            $date['to'] = mktime(23, 59, 59, $month, $day, $year);
        } else {
            $to = date('Y/m/d', time());
            $date['to'] = mktime(23, 59, 59); //koniec dnia dzisiejszego
        }

        $layout['pagetitle'] = trans(
            'Customer $a Balance Sheet ($b to $c)',
            $LMS->GetCustomerName($_POST['customer']),
            ($from ? $from : ''),
            $to
        );

        $id = $_POST['customer'];

        if ($tslist = $DB->GetAll('SELECT cash.id AS id, time, cash.value AS value,
			taxes.label AS taxlabel, customerid, comment, name AS username 
				    FROM cash 
				    LEFT JOIN taxes ON (taxid = taxes.id)
				    LEFT JOIN users ON users.id=userid 
				    WHERE customerid=? ORDER BY time', array($id))
        ) {
            foreach ($tslist as $row) {
                foreach ($row as $column => $value) {
                    $saldolist[$column][] = $value;
                }
            }
        }

        if (count($saldolist['id']) > 0) {
            foreach ($saldolist['id'] as $i => $v) {
                $saldolist['after'][$i] = $saldolist['balance'] + $saldolist['value'][$i];
                $saldolist['balance'] += $saldolist['value'][$i];
                $saldolist['date'][$i] = date('Y/m/d H:i', $saldolist['time'][$i]);

                if ($saldolist['time'][$i]>=$date['from'] && $saldolist['time'][$i]<=$date['to']) {
                    $list['id'][] = $saldolist['id'][$i];
                    $list['after'][] = $saldolist['after'][$i];
                    $list['before'][] = $saldolist['balance'];
                    $list['value'][] = $saldolist['value'][$i];
                    $list['taxlabel'][] = $saldolist['taxlabel'][$i];
                    $list['date'][] = date('Y/m/d H:i', $saldolist['time'][$i]);
                    $list['username'][] = $saldolist['username'][$i];
                    $list['comment'][] = $saldolist['comment'][$i];
                    $list['summary'] += $saldolist['value'][$i];
                }
            }

            $list['total'] = count($list['id']);
        } else {
            $list['balance'] = 0;
        }

        $list['customerid'] = $id;

        $SMARTY->assign('balancelist', $list);
        if (strtolower(ConfigHelper::getConfig('phpui.report_type')) == 'pdf') {
            $output = $SMARTY->fetch('print/printcustomerbalance.html');
            html2pdf($output, trans('Reports'), $layout['pagetitle']);
        } else {
            $SMARTY->display('print/printcustomerbalance.html');
        }
        break;

    default:
        /*******************************************************/

        $layout['pagetitle'] = trans('Reports');

        $yearstart = date('Y', (int) $DB->GetOne('SELECT MIN(dt) FROM stats'));
        $yearend = date('Y', (int) $DB->GetOne('SELECT MAX(dt) FROM stats'));
        for ($i=$yearstart; $i<$yearend+1; $i++) {
            $statyears[] = $i;
        }
        for ($i=1; $i<13; $i++) {
            $months[$i] = strftime('%B', mktime(0, 0, 0, $i, 1));
        }

        if (!ConfigHelper::checkConfig('phpui.big_networks')) {
            $SMARTY->assign('customers', $LMS->GetCustomerNames());
        }
        $SMARTY->assign('currmonth', date('n'));
        $SMARTY->assign('curryear', date('Y'));
        $SMARTY->assign('statyears', $statyears);
        $SMARTY->assign('months', $months);
        $SMARTY->assign('networks', $LMS->GetNetworks());
        $SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
        $SMARTY->assign('nodegroups', $LMS->GetNodeGroupNames());
        $SMARTY->assign('cstateslist', $LMS->GetCountryStates());
        $SMARTY->assign('divisions', $LMS->GetDivisions());
        $SMARTY->assign('printmenu', 'customer');
        $SMARTY->display('print/printindex.html');
        break;
}
