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

$type = $_GET['type'] ?? '';

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
            [$year, $month, $day] = explode('/', $_POST['day']);
            $time = mktime(0, 0, 0, $month, $day+1, $year);
        }

        if ($_POST['docfrom']) {
            [$year, $month, $day] = explode('/', $_POST['docfrom']);
            $docfrom = mktime(0, 0, 0, $month, $day, $year);
        } else {
            $docfrom = 0;
        }

        if ($_POST['docto']) {
            [$year, $month, $day] = explode('/', $_POST['docto']);
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
                case 51:
                case CSTATUS_DISCONNECTED:
                    $state_labels[] = trans('Disconnected<!status>');
                    break;
                case 63:
                case CSTATUS_CONNECTED:
                    $state_labels[] = trans('Connected<!status>');
                    break;
                case CSTATUS_DEBT_COLLECTION:
                    $state_labels[] = trans('Debt Collection<!status>');
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
                        $ncustomerlist[] = $row;
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
            Utils::html2pdf(array(
                'content' => $output,
                'subject' => trans('Reports'),
                'title' => $layout['pagetitle'],
                'orientation' => 'L',
            ));
        } else {
            $SMARTY->display("$print_template");
        }
        break;

    case 'customerbalance':
        /********************************************/

        $from = $_POST['from'];
        $to = $_POST['to'];

        // date format 'yyyy/mm/dd'
        [$year, $month, $day] = explode('/', $from);
        $date['from'] = mktime(0, 0, 0, $month, $day, $year);

        if ($to) {
            [$year, $month, $day] = explode('/', $to);
            $date['to'] = mktime(23, 59, 59, $month, $day, $year);
        } else {
            $to = date('Y/m/d', time());
            $date['to'] = mktime(23, 59, 59); //koniec dnia dzisiejszego
        }

        $layout['pagetitle'] = trans(
            'Customer $a Balance Sheet ($b to $c)',
            $LMS->GetCustomerName($_POST['customer']),
            ($from ?: ''),
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
            Utils::html2pdf(array(
                'content' => $output,
                'subject' => trans('Reports'),
                'title' => $layout['pagetitle'],
            ));
        } else {
            $SMARTY->display('print/printcustomerbalance.html');
        }
        break;

    case 'transgus':
        function get_phone_type($phone)
        {
            $phone = preg_replace('/[\s\-]+/', '', $phone);
            return preg_match(
                '/^(450|459|5[0137]|60[0-9]|6[69]|7[2389]|88)/',
                $phone
            ) ? CONTACT_MOBILE : CONTACT_LANDLINE;
        }

        $division = intval($_POST['division']);
        $phonecontacts = isset($_POST['phonecontacts']);
        if (isset($_POST['customergroups'])) {
            $customergroups = Utils::filterIntegers($_POST['customergroups']);
        } else {
            $customergroups = array();
        }
        $soletraders = isset($_POST['soletraders']);

        $customers = $DB->GetAllByKey(
            'SELECT c.id, c.lastname, c.name, c.ssn, c.ten, ' . $DB->GroupConcat('va.id') . ' AS voipaccounts FROM customers c
            JOIN voipaccounts va ON va.ownerid = c.id
            JOIN voip_numbers n ON n.voip_account_id = va.id
            WHERE c.deleted = 0 AND c.divisionid = ? AND (c.type = ?' . ($soletraders ? ' OR ' . $DB->RegExp('c.rbename', '^(CENTRALNA[\s]+){0,1}EWIDENCJA([\s]+I[\s]+INFORMACJA[\s]+O){0,1}[\s]+DZIAŁALNOŚCI[\s]+GOSPODARCZEJ$') : '') . ') AND c.status = ?
                AND EXISTS (
                    SELECT 1 FROM assignments a
                    JOIN tariffs t ON t.id = a.tariffid
                    JOIN voip_number_assignments vna ON vna.assignment_id = a.id
                    WHERE a.customerid = c.id AND t.type = ? AND vna.number_id = n.id
                )'
                . (empty($customergroups) ? '' : ' AND EXISTS (SELECT 1 FROM vcustomerassignments ca WHERE ca.customerid = c.id AND ca.customergroupid IN (' . implode(',', $customergroups) . '))')
            . ' GROUP BY c.id, c.lastname, c.name
            ORDER BY c.lastname, c.name ASC',
            'id',
            array($division, CTYPES_PRIVATE, CSTATUS_CONNECTED, SERVICE_PHONE)
        );

        if (empty($customers)) {
            $customers = array();
        }

        $landline_customers = array();
        $mobile_customers = array();
        $customer_locations = array();

        foreach ($customers as $customer) {
            $customerid = $customer['id'];
            $voipaccounts = array_unique(explode(',', $customer['voipaccounts']));
            $customer['voipaccounts'] = array();
            foreach ($voipaccounts as $voipaccountid) {
                $voipaccount = $LMS->GetVoipAccount($voipaccountid);
                if (empty($voipaccount['location_city_name'])) {
                    if (!isset($customer_locations[$customerid])) {
                        $customer_locations[$customerid] = $LMS->GetAddress($LMS->detectCustomerLocationAddress($customerid));
                    }
                    $location = $customer_locations[$customerid];
                    $voipaccount = array_merge($voipaccount, array(
                        'location_state_name' => $location['state'],
                        'location_state' => $location['state_id'],
                        'location_city_name' => $location['city'],
                        'location_city' => $location['city_id'],
                        'location_street_name' => $location['street'],
                        'location_street' => $location['street_id'],
                        'location_house' => $location['house'],
                        'location_flat' => $location['flat'],
                    ));
                }
                $location = $voipaccount['location'];
                foreach ($voipaccount['phones'] as $phone) {
                    $phone['phone'] = preg_replace('/^48([0-9]{9})$/', '$1', $phone['phone']);
                    if (!preg_match('/^[0-9]{9}$/', $phone['phone'])) {
                        continue;
                    }
                    $phone_type = get_phone_type($phone['phone']);
                    switch ($phone_type) {
                        case CONTACT_LANDLINE:
                            if (!isset($landline_customers[$customerid])) {
                                $landline_customers[$customerid] = $customer;
                                $landline_customers[$customerid]['locations'] = array();
                            }
                            if (!isset($landline_customers[$customerid]['locations'][$location])) {
                                $landline_customers[$customerid]['locations'][$location] = array(
                                    'location_state_name' => $voipaccount['location_state_name'],
                                    'location_state' => $voipaccount['location_state'],
                                    'location_city_name' => $voipaccount['location_city_name'],
                                    'location_city' => $voipaccount['location_city'],
                                    'location_street_name' => $voipaccount['location_street_name'],
                                    'location_street' => $voipaccount['location_street'],
                                    'location_house' => $voipaccount['location_house'],
                                    'location_flat' => $voipaccount['location_flat'],
                                    'phones' => array(),

                                );
                            }
                            $landline_customers[$customerid]['locations'][$location]['phones'][] = $phone['phone'];
                            break;
                        case CONTACT_MOBILE:
                            if (!isset($mobile_customers[$customerid])) {
                                $mobile_customers[$customerid] = $customer;
                                $mobile_customers[$customerid]['phones'] = array();
                            }
                            $mobile_customers[$customerid]['phones'][] = $phone['phone'];
                            break;
                    }
                }
            }
        }

        $result = $plugin_manager->executeHook(
            'transgus_data_prepare',
            compact('customergroups', 'division', 'soletraders', 'landline_customers', 'mobile_customers')
        );
        extract($result);

        $division = $LMS->GetDivision($division);
        $division_address = $LMS->GetAddress($division['address_id']);

        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $content .= "<Operator>\n";
        $content .= "\t<Jednostka>\n";
        $content .= "\t\t<dataStanNa>" . date('d-m-Y') . "</dataStanNa>\n";
        $content .= "\t\t<Nazwa>" . htmlspecialchars($division['name']) . "</Nazwa>\n";
        $content .= "\t\t<Regon>" . $division['regon'] . "</Regon>\n";
/*
        $content .= "\t\t<AdresOperatora>\n";
        $content .= "\t\t\t<Miejscowosc>" . htmlspecialchars($division['city']) . "</Miejscowosc>\n";
        if (!empty($division_address['street'])) {
            $content .= "\t\t\t<Ulica>" . htmlspecialchars($division_address['street']) . "</Ulica>\n";
        }
        $content .= "\t\t\t<NrDom>" . htmlspecialchars($division_address['house']) . "</NrDom>\n";
        if (!empty($division_address['flat'])) {
            $content .= "\t\t\t<NrLokal>" . htmlspecialchars($division_address['flat']) . "</NrLokal>\n";
        }
        $content .= "\t\t\t<KodPoczta>" . $division_address['zip'] . "</KodPoczta>\n";
        $content .= "\t\t\t<Poczta>" . htmlspecialchars(empty($division_address['postoffice']) ? $division['city'] : $division_address['postoffice']) . "</Poczta>\n";
        $content .= "\t\t\t<Telefon>" . $division['phone'] . "</Telefon>\n";
        $content .= "\t\t</AdresOperatora>\n";
*/

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

        if (!empty($landline_customers)) {
            //$content .= "\t\t<AbonenciStacj>\n";
            $content .= "\t\t<Abonenci>\n";
            foreach ($landline_customers as $customerid => $landline_customer) {
                $customer = $LMS->GetCustomer($customerid);

                foreach ($landline_customer['locations'] as $location => $address) {
                    $content .= "\t\t\t<Abonent>\n";

/*
                    $content .= "\t\t\t\t<Nazwisko>" . htmlspecialchars($customer['lastname']) . "</Nazwisko>\n";
                    $content .= "\t\t\t\t<Imie>" . htmlspecialchars($customer['name']) . "</Imie>\n";
*/
                    $content .= "\t\t\t\t<Pesel>" . (preg_match('/^[0-9]{11}$/', $customer['ssn']) ? $customer['ssn'] : '00000000000') . "</Pesel>\n";
                    if (preg_match('/^[0-9]{10}$/', $customer['ten'])) {
                        $content .= "\t\t\t\t<Nip>" . $customer['ten'] . "</Nip>\n";
                    }

                    $content .= "\t\t\t\t<NumeryAbonenckie>\n";
                    foreach ($address['phones'] as $phone) {
                        $content .= "\t\t\t\t\t<NumerAbonenta>" . $phone . "</NumerAbonenta>\n";
                    }
                    $content .= "\t\t\t\t</NumeryAbonenckie>\n";

                    $content .= "\t\t\t\t<TelefonyKontaktowe>\n";
                    if ($phonecontacts) {
                        foreach ($customer['phones'] as $phone) {
                            if (!($phone['type'] & CONTACT_DISABLED) && preg_match('/^[0-9]{9}$/', $phone['phone'])) {
                                $content .= "\t\t\t\t\t<NumerKontaktowy>" . $phone['phone'] . "</NumerKontaktowy>\n";
                            }
                        }
                    }
                    $content .= "\t\t\t\t</TelefonyKontaktowe>\n";

/*
                    $locations = '';

                    $location_content = "\t\t\t\t<AdresPunktu>\n";

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

                    $teryt_city = $city_idents_by_ids[$address['location_city']] ?? null;

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

                    $locations .= $location_content . "\t\t\t\t</AdresPunktu>\n";

                    $content .= $locations;
*/

                    $content .= "\t\t\t</Abonent>\n";
                }
            }
            //$content .= "\t\t</AbonenciStacj>\n";
            $content .= "\t\t</Abonenci>\n";
        }

        if (!empty($mobile_customers)) {
            $content .= "\t\t<AbonenciMobil>\n";
            foreach ($mobile_customers as $customerid => $mobile_customer) {
                $customer = $LMS->GetCustomer($customerid);

                $content .= "\t\t\t<Abonent>\n";

                $content .= "\t\t\t\t<Nazwisko>" . htmlspecialchars($customer['lastname']) . "</Nazwisko>\n";
                $content .= "\t\t\t\t<Imie>" . htmlspecialchars($customer['name']) . "</Imie>\n";
                $content .= "\t\t\t\t<Pesel>" . (preg_match('/^[0-9]{11}$/', $customer['ssn']) ? $customer['ssn'] : '00000000000') . "</Pesel>\n";
                if (preg_match('/^[0-9]{10}$/', $customer['ten'])) {
                    $content .= "\t\t\t\t<Nip>" . $customer['ten'] . "</Nip>\n";
                }

                $content .= "\t\t\t\t<NumeryAbonenckie>\n";
                foreach ($mobile_customer['phones'] as $phone) {
                    $content .= "\t\t\t\t\t<NumerAbonenta>" . $phone . "</NumerAbonenta>\n";
                }
                $content .= "\t\t\t\t</NumeryAbonenckie>\n";

                $content .= "\t\t\t\t<TelefonyKontaktowe>\n";
                if ($phonecontacts) {
                    foreach ($customer['phones'] as $phone) {
                        if (!($phone['type'] & CONTACT_DISABLED) && preg_match('/^[0-9]{9}$/', $phone['phone'])) {
                            $content .= "\t\t\t\t\t<NumerKontaktowy>" . $phone['phone'] . "</NumerKontaktowy>\n";
                        }
                    }
                }
                $content .= "\t\t\t\t</TelefonyKontaktowe>\n";

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

                    $teryt_city = $city_idents_by_ids[$address['location_city']] ?? null;

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

            $content .= "\t\t</AbonenciMobil>\n";
        }

        $content .= "\t</Jednostka>\n";
        $content .= "</Operator>\n";

        $attachment_name = 'TRANSGUS-' . date('Y-m-d-H-i-s') . '.xml';

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
            $months[$i] = date('F', mktime(0, 0, 0, $i, 1));
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
