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

    case 'transgus':
        $division = intval($_POST['division']);
        $phonecontacts = isset($_POST['phonecontacts']);

        $customers = $DB->GetCol(
            'SELECT id FROM customers
                WHERE deleted = 0 AND divisionid = ? AND type = ? AND status = ? AND name <> ?',
            array($division, CTYPES_PRIVATE, CSTATUS_CONNECTED, '')
        );

        $division = $LMS->GetDivision($division);

        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $content .= "<Operator>\n";
        $content .= "\t<Jednostka>\n";
        $content .= "\t\t<dataStanNa>" . date('d-m-Y') . "</dataStanNa>\n";
        $content .= "\t\t<Nazwa>" . htmlspecialchars($division['name']) . "</Nazwa>\n";
        $content .= "\t\t<Regon>" . $division['regon'] . "</Regon>\n";
        $content .= "\t\t<Abonenci>\n";

        $state_ident_by_ids = $DB->GetAllByKey('SELECT id, ident FROM location_states', 'id');
        if (empty($state_ident_by_ids)) {
            $state_ident_by_ids = array();
        }

        $city_idents_by_ids = $DB->GetAllByKey(
            'SELECT lc.id, lc.ident AS city_ident, lc.name AS city_name,
                lb.ident AS borough_ident, lb.name AS borough_name, lb.type AS borough_type,
                ld.ident AS district_ident, ld.name AS district_name
            FROM location_cities lc
            JOIN location_boroughs lb ON lb.id = lc.boroughid
            JOIN location_districts ld ON ld.id = lb.districtid
            ORDER BY lc.id',
            'id'
        );

        $street_ident_by_ids = $DB->GetAllByKey(
            'SELECT id, ident FROM location_streets
            ORDER BY id',
            'id'
        );

        foreach ($customers as $customerid) {
            $customer = $LMS->GetCustomer($customerid);
            $content .= "\t\t\t<Abonent>\n";
            $content .= "\t\t\t\t<Nazwisko>" . htmlspecialchars($customer['lastname']) . "</Nazwisko>\n";
            $content .= "\t\t\t\t<Imie>" . htmlspecialchars($customer['name']) . "</Imie>\n";
            if (preg_match('/^[0-9]{11}$/', $customer['ssn'])) {
                $content .= "\t\t\t\t<Pesel>" . $customer['ssn'] . "</Pesel>\n";
            }

            if ($phonecontacts && !empty($customer['phones'])) {
                $phones = array();
                foreach ($customer['phones'] as $phone) {
                    if (!($phone['type'] & CONTACT_DISABLED)) {
                        $phones[] = $phone['phone'];
                    }
                }
                $content .= "\t\t\t\t<NrTel>" . implode(', ', $phones) . "</NrTel>\n";
            }

            $locations = '';

            foreach ($customer['addresses'] as $address) {
                if (empty($address['location_city_name'])
                    || ($address['location_address_type'] != BILLING_ADDRESS
                        && $address['location_address_type'] != POSTAL_ADDRESS)) {
                    continue;
                }

                switch ($address['location_address_type']) {
                    case BILLING_ADDRESS:
                        $location_content = "\t\t\t\t<AdresZamieszkania>\n";
                        break;
                    case POSTAL_ADDRESS:
                        $location_content = "\t\t\t\t<AdresKoresp>\n";
                        break;
                }

                $location_content .= "\t\t\t\t\t<Województwo>\n";
                $location_content .= "\t\t\t\t\t\t<NazwaWojew>"
                    . htmlspecialchars($address['location_state_name'] ?: trans('(undefined)'))
                    . "</NazwaWojew>\n";
                if (isset($state_ident_by_ids[$address['location_state']])) {
                    $location_content .= "\t\t\t\t\t\t<KodWojew>"
                        . $state_ident_by_ids[$address['location_state']]['ident']
                        . "</KodWojew>\n";
                }
                $location_content .= "\t\t\t\t\t</Województwo>\n";

                $teryt_city = isset($city_idents_by_ids[$address['location_city']])
                    ? $city_idents_by_ids[$address['location_city']] : null;

                $location_content .= "\t\t\t\t\t<Powiat>\n";
                $location_content .= "\t\t\t\t\t\t<NazwaPowiatu>"
                    . htmlspecialchars($teryt_city ? $teryt_city['district_name'] : trans('(undefined)'))
                    . "</NazwaPowiatu>\n";
                if ($teryt_city) {
                    $location_content .= "\t\t\t\t\t\t<KodPowiatu>"
                        . $teryt_city['district_ident']
                        . "</KodPowiatu>\n";
                }
                $location_content .= "\t\t\t\t\t</Powiat>\n";

                $location_content .= "\t\t\t\t\t<Gmina>\n";
                $location_content .= "\t\t\t\t\t\t<NazwaGminy>"
                    . htmlspecialchars($teryt_city ? $teryt_city['borough_name'] : trans('(undefined)'))
                    . "</NazwaGminy>\n";
                if ($teryt_city) {
                    $location_content .= "\t\t\t\t\t\t<KodGminy>"
                        . $teryt_city['borough_ident'] . $teryt_city['borough_type']
                        . "</KodGminy>\n";
                }
                $location_content .= "\t\t\t\t\t</Gmina>\n";

                $location_content .= "\t\t\t\t\t<Miejscowosc>\n";
                $location_content .= "\t\t\t\t\t\t<NazwaMiejscowosci>"
                    . htmlspecialchars($address['location_city_name'])
                    . "</NazwaMiejscowosci>\n";
                if ($teryt_city) {
                    $location_content .= "\t\t\t\t\t\t<KodMiejscowosci>"
                        . $teryt_city['city_ident']
                        . "</KodMiejscowosci>\n";
                }
                $location_content .= "\t\t\t\t\t</Miejscowosc>\n";

                if (!empty($address['location_street_name'])) {
                    $location_content .= "\t\t\t\t\t<Ulica>\n";
                    $location_content .= "\t\t\t\t\t\t<NazwaUlicy>"
                        . htmlspecialchars($address['location_street_name'])
                        . "</NazwaUlicy>\n";
                    if (!empty($address['location_street']) && isset($street_ident_by_ids[$address['location_street']])) {
                        $location_content .= "\t\t\t\t\t\t<KodUlicy>"
                            . $street_ident_by_ids[$address['location_street']]['ident']
                            . "</KodUlicy>\n";
                    }
                    $location_content .= "\t\t\t\t\t</Ulica>\n";
                }

                if (!empty($address['location_house'])) {
                    $location_content .= "\t\t\t\t\t<NumerDomu>" . $address['location_house'] . "</NumerDomu>\n";
                    if (!empty($address['location_flat'])) {
                        $location_content .= "\t\t\t\t\t<NumerLokalu>" . $address['location_flat'] . "</NumerLokalu>\n";
                    }
                } else {
                    $location_content .= "\t\t\t\t\t<NumerDomu>" . trans('(undefined)') . "</NumerDomu>\n";
                }

                switch ($address['location_address_type']) {
                    case BILLING_ADDRESS:
                        $locations = $location_content . "\t\t\t\t</AdresZamieszkania>\n" . $locations;
                        break;
                    case POSTAL_ADDRESS:
                        $locations = $locations . $location_content . "\t\t\t\t</AdresKoresp>\n";
                        break;
                }
            }

            $content .= $locations;

            $content .= "\t\t\t</Abonent>\n";
        }

        $content .= "\t\t</Abonenci>\n";
        $content .= "\t</Jednostka>\n";
        $content .= "</Operator>\n";

        $attachment_name = strftime('TRANSGUS-%Y-%m-%d-%H-%M-%S.xml');

        header('Content-Type: text/xml');
        header('Content-Disposition: attachment; filename="' . $attachment_name . '"');
        header('Pragma: public');

        echo $content;

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
