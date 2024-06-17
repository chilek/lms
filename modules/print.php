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

if (!ConfigHelper::checkConfig('privileges.superuser') && !ConfigHelper::checkConfig('privileges.reports')) {
    access_denied();
}

$report_type = ConfigHelper::getConfig('phpui.report_type');
if (empty($report_type)) {
    $report_type = '';
}

$type = $_GET['type'] ?? '';

switch ($type) {
    case 'customerbalance':
        /********************************************/

        if (!ConfigHelper::checkConfig('privileges.superuser') && !ConfigHelper::checkConfig('privileges.finances_management')) {
            access_denied();
        }

        $from = $_POST['from'];
        $to = $_POST['to'];

        // date format 'yyyy/mm/dd'
        if ($from && preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $from)) {
            [$year, $month, $day] = explode('/', $from);
            $date['from'] = mktime(0, 0, 0, (int)$month, (int)$day, (int)$year);
        } else {
            $date['from'] = 0;
        }

        if ($to && preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $to)) {
            [$year, $month, $day] = explode('/', $to);
            $date['to'] = mktime(23, 59, 59, $month, $day, $year);
        } else {
            $to = date('Y/m/d', time());
            $date['to'] = mktime(23, 59, 59); //koniec dnia dzisiejszego
        }

        $id = intval($_POST['customer']);

        $aggregate_documents = !empty($_POST['aggregate_documents']);

        $layout['pagetitle'] = trans('Customer $a Balance Sheet ($b to $c)', $LMS->GetCustomerName($id), ($from ?: ''), $to);

        $list['balance'] = 0;
        $list['income'] = 0;
        $list['expense'] = 0;
        $list['liability'] = 0;
        $list['summary'] = 0;
        $list['customerid'] = $id;

        if ($tslist = $DB->GetAll('SELECT c.id AS id, time, c.type, c.value AS value,
                    c.currency, c.currencyvalue,
				    taxes.label AS taxlabel, c.customerid, c.comment, vusers.name AS username,
				    c.docid, d.number, d.fullnumber, d.cdate, d.type AS doctype, numberplans.template
				    FROM cash c
				    LEFT JOIN documents d ON d.id = c.docid
				    LEFT JOIN numberplans ON numberplans.id = d.numberplanid
				    LEFT JOIN taxes ON (c.taxid = taxes.id)
				    LEFT JOIN vusers ON (vusers.id = c.userid)
				    WHERE c.customerid = ?
					    AND NOT EXISTS (
				                    SELECT 1 FROM vcustomerassignments a
					            JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
					            WHERE e.userid = lms_current_user() AND a.customerid = ?)
				    ORDER BY time', array($id, $id))
        ) {
            if ($aggregate_documents) {
                $tslist = $LMS->AggregateDocuments(array('customerid' => $id, 'list' => $tslist));
                $tslist = $tslist['list'];
            }

            foreach ($tslist as $row) {
                foreach ($row as $column => $value) {
                    $saldolist[$column][] = $value;
                }
            }

            $saldolist['balance'] = 0;

            foreach ($saldolist['id'] as $i => $v) {
                $saldolist['after'][$i] = $saldolist['balance'] + $saldolist['value'][$i] * $saldolist['currencyvalue'][$i];
                $saldolist['balance'] += $saldolist['value'][$i] * $saldolist['currencyvalue'][$i];
                $saldolist['date'][$i] = date('Y/m/d H:i', $saldolist['time'][$i]);

                if ($saldolist['time'][$i]>=$date['from'] && $saldolist['time'][$i]<=$date['to']) {
                    $list['id'][] = $saldolist['id'][$i];
                    $list['type'][] = $saldolist['type'][$i];
                    $list['after'][] = $saldolist['after'][$i];
                    $list['before'][] = $saldolist['balance'];
                    $list['value'][] = $saldolist['value'][$i];
                    $list['taxlabel'][] = $saldolist['taxlabel'][$i];
                    $list['date'][] = date('Y/m/d H:i', $saldolist['time'][$i]);
                    $list['fullnumber'][] = $saldolist['fullnumber'][$i];
                    $list['username'][] = $saldolist['username'][$i];
                    $list['comment'][] = $saldolist['comment'][$i];
                    $list['currency'][] = $saldolist['currency'][$i];
                    $list['summary'] += $saldolist['value'][$i] * $saldolist['currencyvalue'][$i];

                    if ($saldolist['type'][$i]) {
                        if ($saldolist['value'][$i] > 0) {
                                //income
                                $list['income'] += $saldolist['value'][$i] * $saldolist['currencyvalue'][$i];
                        } else { //expense
                                $list['expense'] -= $saldolist['value'][$i] * $saldolist['currencyvalue'][$i];
                        }
                    } else {
                        $list['liability'] -= $saldolist['value'][$i] * $saldolist['currencyvalue'][$i];
                    }
                }
            }

            $list['total'] = count($list['id']);
        }

        $SMARTY->assign('balancelist', $list);
        if (strtolower($report_type) == 'pdf') {
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

    case 'balancelist':
        /********************************************/

        if (!ConfigHelper::checkConfig('privileges.superuser') && !ConfigHelper::checkConfig('privileges.finances_management')) {
            access_denied();
        }

        $from = $_POST['balancefrom'];
        $to = $_POST['balanceto'];
        $net = intval($_POST['network']);
        if (!empty($_POST['customergroup'])) {
            $group = $_POST['customergroup'];
            if (!is_array($group)) {
                $group = array($group);
            }
            $group = Utils::filterIntegers($group);
        } else {
            $group = array();
        }
        $division = intval($_POST['division']);
        $source = intval($_POST['source']);
        $types = isset($_POST['types']) ? Utils::filterIntegers($_POST['types']) : array();
        $docs = $_POST['docs'];

        $hideid = isset($_POST['printcustomerhideid']);
        $hidessnten = isset($_POST['printcustomerhidessnten']);
        $hideaddress = isset($_POST['printcustomerhideaddress']);

        // date format 'yyyy/mm/dd'
        if ($from) {
            [$year, $month, $day] = explode('/', $from);
            $date['from'] = mktime(0, 0, 0, (int)$month, (int)$day, (int)$year);
        }

        if ($to) {
            [$year, $month, $day] = explode('/', $to);
            $date['to'] = mktime(23, 59, 59, $month, $day, $year);
        } else {
            $to = date('Y/m/d', time());
            $date['to'] = mktime(23, 59, 59); //koniec dnia dzisiejszego
        }

        if ($net) {
                $net = $LMS->GetNetworkParams($net);
        }

        if ($user = $_POST['user']) {
            $layout['pagetitle'] = trans('Balance Sheet of User: $a ($b to $c)', $LMS->GetUserName($user), ($from ?: ''), $to);
        } else {
            $layout['pagetitle'] = trans('Balance Sheet ($a to $b)', ($from ?: ''), $to);
        }

        $typetxt = array();
        if (!empty($types)) {
            foreach ($types as $tt) {
                switch ($tt) {
                    case 1:
                        $typewhere[] = 'c.type = 0';
                        $typetxt[] = trans('Liability');
                        break;
                    case 2:
                        $typewhere[] = '(c.type = 1 AND c.value > 0)';
                        $typetxt[] = trans('Income');
                        break;
                    case 3: // expense
                        $typewhere[] = '(c.type = 1 AND c.value < 0)';
                        $typetxt[] = trans('Expense');
                        break;
                }
            }

            $typewhere = ' AND (' . implode(' OR ', $typewhere) . ')';
        }

        $customerslist = $DB->GetAllByKey('SELECT id, ' . $DB->Concat('UPPER(lastname)', "' '", 'name') . ' AS customername FROM customers', 'id');

        if (isset($date['from'])) {
            $lastafter = $DB->GetOne(
                'SELECT
                    SUM(CASE WHEN c.customerid IS NOT NULL AND c.type = 0 THEN 0 ELSE c.value * c.currencyvalue END)
                FROM cash c
                JOIN customerview ON customerview.id = c.customerid
                WHERE c.time < ?'
                . (empty($group) ? '' : ' AND EXISTS (SELECT 1 FROM vcustomerassignments a WHERE a.customerid = c.customerid AND a.customergroupid IN (' . implode(',', $group) . '))')
                . ($docs ? ($docs == 'documented' ? ' AND c.docid IS NOT NULL' : ' AND c.docid IS NULL') : '')
                . ($source ? ' AND c.sourceid = ' . intval($source) : '')
                . ($net ? ' AND EXISTS (SELECT 1 FROM vnodes WHERE c.customerid = ownerid AND ((ipaddr > ' . $net['address'] . ' AND ipaddr < ' . $net['broadcast'] . ') OR (ipaddr_pub > ' . $net['address'] . ' AND ipaddr_pub < ' . $net['broadcast'] . ')))' : '')
                . ($division ? ' AND customerview.divisionid = ' . $division : '')
                . (empty($types) ? '' : $typewhere),
                array(
                    $date['from'],
                )
            );
        } else {
            $lastafter = 0;
        }

        if ($balancelist = $DB->GetAll(
            'SELECT
                c.id AS id,
                c.time,
                c.userid,
                customerview.type AS ctype,
                COALESCE(d.ssn, customerview.ssn) AS ssn,
                COALESCE(d.ten, customerview.ten) AS ten,
                COALESCE(d.address, customerview.address) AS address,
                COALESCE(d.zip, customerview.zip) AS zip,
                COALESCE(d.city, customerview.city) AS city,
                c.value AS value,
                c.currency,
                c.currencyvalue,
                taxes.label AS taxlabel,
                c.customerid,
                c.comment,
                c.type AS type,
                cs.name AS sourcename
            FROM cash c
            JOIN customerview ON customerview.id = c.customerid
            LEFT JOIN documents d ON d.id = c.docid
            LEFT JOIN cashsources cs ON cs.id = c.sourceid
            LEFT JOIN taxes ON taxid = taxes.id
            WHERE time <= ?'
            . (empty($group) ? '' : ' AND EXISTS (SELECT 1 FROM vcustomerassignments a WHERE a.customerid = c.customerid AND a.customergroupid IN (' . implode(',', $group) . '))')
            . ($docs ? ($docs == 'documented' ? ' AND c.docid IS NOT NULL' : ' AND c.docid IS NULL') : '')
            . ($source ? ($source == -1 ? ' AND c.sourceid IS NULL' : ' AND c.sourceid = ' . intval($source)) : '')
            . (isset($date['from']) ? ' AND c.time >= ' . $date['from'] : '')
            . ($net ? ' AND EXISTS (SELECT 1 FROM vnodes WHERE c.customerid = ownerid AND ((ipaddr > ' . $net['address'] . ' AND ipaddr < ' . $net['broadcast'] . ') OR (ipaddr_pub > ' . $net['address'] . ' AND ipaddr_pub < ' . $net['broadcast'] . ')))' : '')
            . ($division ? ' AND customerview.divisionid = ' . $division : '')
            . (empty($types) ? '' : $typewhere)
            . ' ORDER BY c.time ASC',
            array(
                $date['to'],
            )
        )) {
            $listdata['income'] = 0;
            $listdata['expense'] = 0;
            $listdata['liability'] = 0;
            $x = 0;

            foreach ($balancelist as $idx => $row) {
                if ($user) {
                    if ($row['userid'] != $user) {
                        if ($row['value'] > 0 || !$row['customerid']) {  // skip cust. covenants
                            $lastafter += $row['value'];
                        }
                        unset($balancelist[$idx]);
                        continue;
                    }
                }

                $list[$x] = $row;
                $list[$x]['customername'] = empty($row['customerid']) ? '' : $customerslist[$row['customerid']]['customername'];

                if (!empty($row['customerid']) && empty($row['type'])) {
                    // customer covenant
                    $list[$x]['after'] = $lastafter;
                    $list[$x]['covenant'] = true;
                    $listdata['liability'] -= $row['value'] * $row['currencyvalue'];
                } else {
                    //customer payment
                    $list[$x]['after'] = $lastafter + $list[$x]['value'] * $row['currencyvalue'];

                    if ($row['value'] > 0) {
                        //income
                        $listdata['income'] += $list[$x]['value'] * $row['currencyvalue'];
                    } else {
                        //expense
                        $listdata['expense'] -= $list[$x]['value'] * $row['currencyvalue'];
                    }
                }

                $lastafter = $list[$x]['after'];
                $x++;
                unset($balancelist[$idx]);
            }

            $listdata['total'] = $listdata['income'] - $listdata['expense'];

            $SMARTY->assign('listdata', $listdata);
            $SMARTY->assign('balancelist', $list);
        }

        if ($net) {
            $SMARTY->assign('net', $net['name']);
        }

        $SMARTY->assign('types', array_flip(empty($types) ? array(1, 2, 3) : $types));
        $SMARTY->assign('typetxt', implode(', ', $typetxt));

        if (!empty($group)) {
            $SMARTY->assign('groups', $DB->GetCol('SELECT name FROM customergroups WHERE id IN ? ORDER BY name', array($group)));
        }
        if ($division) {
            $SMARTY->assign('division', $DB->GetOne('SELECT name FROM divisions WHERE id = ?', array($division)));
        }
        if ($source) {
            $SMARTY->assign('source', $DB->GetOne('SELECT name FROM cashsources WHERE id = ?', array($source)));
        }

        $SMARTY->assign(compact('hideid', 'hidessnten', 'hideaddress'));

        if (strtolower($report_type) == 'pdf') {
            $output = $SMARTY->fetch('print/printbalancelist.html');
            Utils::html2pdf(array(
                'content' => $output,
                'subject' => trans('Reports'),
                'title' => $layout['pagetitle'],
            ));
        } else {
            if (isset($_POST['disposition']) && $_POST['disposition'] == 'csv') {
                $filename = 'history-' . date('YmdHis') . '.csv';
                header('Content-Type: text/plain; charset=utf-8');
                header('Content-Disposition: attachment; filename=' . $filename);
                header('Pragma: public');
                $SMARTY->display('print/printbalancelist-csv.html');
            } else {
                $SMARTY->display('print/printbalancelist.html');
            }
        }
        break;

    case 'incomereport':
        /********************************************/

        if (!ConfigHelper::checkConfig('privileges.superuser') && !ConfigHelper::checkConfig('privileges.finances_management')) {
            access_denied();
        }

        $from = $_POST['from'];
        $to = $_POST['to'];

        // date format 'yyyy/mm/dd'
        [$year, $month, $day] = explode('/', $from);
        $date['from'] = mktime(0, 0, 0, (int)$month, (int)$day, (int)$year);

        if ($to) {
            [$year, $month, $day] = explode('/', $to);
            $date['to'] = mktime(23, 59, 59, $month, $day, $year);
        } else {
            $to = date("Y/m/d", time());
            $date['to'] = mktime(23, 59, 59); // end of today
        }

        $layout['pagetitle'] = trans('Total Invoiceless Income ($a to $b)', ($from ?: ''), $to);

        $incomelist = $DB->GetAll(
            'SELECT floor(time/86400)*86400 AS date, SUM(value * currencyvalue) AS value
			FROM cash c
			WHERE value>0 AND time>=? AND time<=? AND docid IS NULL
				AND NOT EXISTS (
			        	SELECT 1 FROM vcustomerassignments a
					JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
					WHERE e.userid = lms_current_user() AND a.customerid = c.customerid)
			GROUP BY date ORDER BY date ASC',
            array($date['from'], $date['to'])
        );

        $SMARTY->assign('incomelist', $incomelist);
        if (strtolower($report_type) == 'pdf') {
            $output = $SMARTY->fetch('print/printincomereport.html');
            Utils::html2pdf(array(
                'content' => $output,
                'subject' => trans('Reports'),
                'title' => $layout['pagetitle'],
            ));
        } else {
            $SMARTY->display('print/printincomereport.html');
        }
        break;

    case 'importlist':
        /********************************************/

        if (!ConfigHelper::checkConfig('privileges.superuser') && !ConfigHelper::checkConfig('privileges.finances_management')) {
            access_denied();
        }

        $from = $_POST['importfrom'];
        $to = $_POST['importto'];
        $source = $_POST['source'];

        // date format 'yyyy/mm/dd'
        if ($from) {
            [$year, $month, $day] = explode('/', $from);
            $date['from'] = mktime(0, 0, 0, (int)$month, (int)$day, (int)$year);
        } else {
            $date['from'] = 0;
        }

        if ($to) {
            [$year, $month, $day] = explode('/', $to);
            $date['to'] = mktime(23, 59, 59, $month, $day, $year);
        } else {
            $to = date("Y/m/d", time());
            $date['to'] = mktime(23, 59, 59); // end of today
        }

        $layout['pagetitle'] = trans('Cash Import History ($a to $b)', $from, $to);

        $importlist = $DB->GetAll('SELECT c.time, c.value, c.customerid, '
            .$DB->Concat('upper(v.lastname)', "' '", 'v.name').' AS customername
			FROM cash c
			JOIN customerview v ON (v.id = c.customerid)
			WHERE c.time >= ? AND c.time <= ?'
            .($source ? ' AND c.sourceid = '.intval($source) : '')
            .' AND c.importid IS NOT NULL
			ORDER BY time', array($date['from'], $date['to']));

        if ($source) {
            $SMARTY->assign('source', $DB->GetOne('SELECT name FROM cashsources WHERE id = ?', array($source)));
        }
        $SMARTY->assign('importlist', $importlist);
        if (strtolower($report_type) == 'pdf') {
            $output = $SMARTY->fetch('print/printimportlist.html');
            Utils::html2pdf(array(
                'content' => $output,
                'subject' => trans('Reports'),
                'title' => $layout['pagetitle'],
            ));
        } else {
            $SMARTY->display('print/printimportlist.html');
        }
        break;

    case 'invoices':
        /********************************************/

        if (!ConfigHelper::checkConfig('privileges.superuser') && !ConfigHelper::checkConfig('privileges.finances_management')) {
            access_denied();
        }

        $from = $_POST['invoicefrom'];
        $to = $_POST['invoiceto'];

        // date format 'yyyy/mm/dd'
        if ($to) {
            [$year, $month, $day] = explode('/', $to);
            $date['to'] = mktime(23, 59, 59, $month, $day, $year);
        } else {
            $to = date('Y/m/d', time());
            $date['to'] = mktime(23, 59, 59); //koniec dnia dzisiejszego
        }

        if ($from) {
            [$year, $month, $day] = explode('/', $from);
            $date['from'] = mktime(0, 0, 0, $month, $day, $year);
        } else {
            $from = date('Y/m/d', time());
            $date['from'] = mktime(0, 0, 0); //początek dnia dzisiejszego
        }

        $type = '';
        $type .= isset($_POST['invoiceorg']) ? '&original=1' : '';
        $type .= isset($_POST['invoicecopy']) ? '&copy=1' : '';
        $type .= isset($_POST['invoicedup']) ? '&duplicate=1' : '';
        if (!$type) {
            $type = '&oryginal=1';
        }

        $layout['pagetitle'] = trans('Invoices');

        header(
            'Location: ?m=invoice&fetchallinvoices=1' . (isset($_GET['jpk']) ? '&jpk=' . $_GET['jpk'] : '')
                . (isset($_GET['jpk_format']) ? '&jpk_format=' . $_GET['jpk_format'] : '')
                .$type
                .'&from='.$date['from']
                .'&to='.$date['to']
                .(!empty($_POST['einvoice']) ? '&einvoice=' . intval($_POST['einvoice']) : '')
                .(!empty($_POST['division']) ? '&divisionid='.intval($_POST['division']) : '')
                .(!empty($_POST['customer']) ? '&customerid='.intval($_POST['customer']) : '')
                .(!empty($_POST['group']) && is_array($_POST['group']) ? '&groupid[]='
                    . implode('&groupid[]=', Utils::filterIntegers($_POST['group'])) : '')
                . (isset($_POST['customer_type']) ? '&customertype=' . intval($_POST['customer_type']) : '')
                .(!empty($_POST['numberplan']) && is_array($_POST['numberplan']) ? '&numberplanid[]='
                    . implode('&numberplanid[]=', Utils::filterIntegers($_POST['numberplan'])) : '')
                .(!empty($_POST['groupexclude']) ? '&groupexclude=1' : '')
                .(!empty($_POST['autoissued']) ? '&autoissued=1' : '')
                .(!empty($_POST['manualissued']) ? '&manualissued=1' : '')
                . (isset($_POST['related-documents']) ? '&related-documents=1' : '')
        );
        break;

    case 'transferforms':
        /********************************************/

        if (!ConfigHelper::checkConfig('privileges.superuser') && !ConfigHelper::checkConfig('privileges.finances_management')) {
            access_denied();
        }

        $kind = isset($_GET['kind']) ? intval($_GET['kind']) : 2;

        switch ($kind) {
            case 1:
                $from = $_POST['invoicefrom'];
                $to = $_POST['invoiceto'];

                if ($to) {
                    [$year, $month, $day] = explode('/', $to);
                    $date['to'] = mktime(23, 59, 59, $month, $day, $year);
                } else {
                    $to = date('Y/m/d', time());
                    $date['to'] = mktime(23, 59, 59); //koniec dnia dzisiejszego
                }

                if ($from) {
                    [$year, $month, $day] = explode('/', $from);
                    $date['from'] = mktime(0, 0, 0, $month, $day, $year);
                } else {
                    $from = date('Y/m/d', time());
                    $date['from'] = mktime(0, 0, 0); //początek dnia dzisiejszego
                }

                $_GET['from'] = $date['from'];
                $_GET['to'] = $date['to'];
                $_GET['customerid'] = $_POST['customer'];
                $_GET['groupid'] = $_POST['group'];
                $_GET['numberplan'] = $_POST['numberplan'];
                $_GET['groupexclude'] = !empty($_POST['groupexclude']) ? 1 : 0;
                $which = '';

                break;
            case 2:
                $balance = isset($_POST['balance']) && strlen($_POST['balance']) ? floatval($_POST['balance']) : null;
                $customer = isset($_POST['customer']) ? intval($_POST['customer']) : 0;
                $group = isset($_POST['customergroup']) ? intval($_POST['customergroup']) : 0;
                $exclgroup = isset($_POST['groupexclude']) ? 1 : 0;

                break;
        }
        require_once(MODULES_DIR . DIRECTORY_SEPARATOR . 'transferforms.php');
        break;

    case 'liabilityreport':
        /********************************************/

        if (!ConfigHelper::checkConfig('privileges.superuser') && !ConfigHelper::checkConfig('privileges.finances_management')) {
            access_denied();
        }
        $reportlist = array();
        $total = array();
        $order = $_POST['order'];
        $direction = $_POST['direction'];
        $divisionid = (isset($_POST['division']) ? intval($_POST['division']) : 0);
        $customerid = (isset($_POST['customer']) ? intval($_POST['customer']) : 0);
        $reportdate = !empty($_POST['day']) ? $_POST['day'] : null;

        //region get taxes
        if (empty($reportdate)) {
            $currtime = time();
            $today = strtotime('today');
        } else {
            $today = $currtime = strtotime($reportdate);
        }
        $taxes = $LMS->GetTaxes($currtime, $currtime);
        //endregion

        $args = array(
            'customer_id' =>  isset($_POST['customer']) ? intval($_POST['customer']) : null,
            'division_id' =>  isset($_POST['division']) ? intval($_POST['division']) : null,
            'reportdate' =>  $reportdate,
        );

        $assignments = $LMS->getAssignments($args);

        //region Suspensions defaults
        $defaultSuspensionPercentage = ConfigHelper::getConfig(
            'suspensions.default_percentage',
            ConfigHelper::getConfig('payments.suspension_percentage', ConfigHelper::getConfig('finances.suspension_percentage', 0))
        );
        $defaultSuspensionPercentage = f_round($defaultSuspensionPercentage);
        $defaultSuspensionValue = f_round(ConfigHelper::getConfig('suspensions.default_value', 0));
        //endregion

        if (!empty($assignments)) {
            $suspensionsByCurrency = array();
            $suspensionsById = array();

            foreach ($assignments as $idx => &$row) {
                if ($row['t_period'] && $row['period'] != DISPOSABLE
                    && $row['t_period'] != $row['period']) {
                    if ($row['t_period'] == YEARLY) {
                        $row['base_price'] = $row['base_price'] / 12.0;
                        $row['price'] = $row['price'] / 12.0;
                    } elseif ($row['t_period'] == HALFYEARLY) {
                        $row['base_price'] = $row['base_price'] / 6.0;
                        $row['price'] = $row['price'] / 6.0;
                    } elseif ($row['t_period'] == QUARTERLY) {
                        $row['base_price'] = $row['base_price'] / 3.0;
                        $row['price'] = $row['price'] / 3.0;
                    }

                    if ($row['period'] == YEARLY) {
                        $row['base_price'] = $row['base_price'] * 12.0;
                        $row['price'] = $row['price'] * 12.0;
                    } elseif ($row['period'] == HALFYEARLY) {
                        $row['base_price'] = $row['base_price'] * 6.0;
                        $row['price'] = $row['price'] * 6.0;
                    } elseif ($row['period'] == QUARTERLY) {
                        $row['base_price'] = $row['base_price'] * 3.0;
                        $row['price'] = $row['price'] * 3.0;
                    } elseif ($row['period'] == WEEKLY) {
                        $row['base_price'] = $row['base_price'] / 4.0;
                        $row['price'] = $row['price'] / 4.0;
                    } elseif ($row['period'] == DAILY) {
                        $row['base_price'] = $row['base_price'] / 30.0;
                        $row['price'] = $row['price'] / 30.0;
                    }
                }

                //<editor-fold desc="assignment values">
                if (!empty($row['netflag'])) {
                    $row['base_net_price'] = $row['base_price'];
                    $row['base_net_value'] = f_round($row['base_net_price'] * $row['count']);
                    $row['base_gross_price'] = f_round($row['base_net_price'] * ($row['taxrate'] / 100 + 1), 3);
                    $row['base_tax_value'] = f_round($row['base_net_value'] * ($row['taxrate'] / 100));
                    $row['base_gross_value'] = f_round($row['base_net_value'] + $row['base_tax_value']);

                    $row['net_price'] = $row['price']; // price is discounted in sql already

                    $row['net_price_discount'] = f_round($row['base_net_price'] - $row['net_price'], 3);
                    $row['net_value'] = f_round($row['net_price'] * $row['count']);
                    $row['gross_price'] = f_round($row['net_price'] * ($row['taxrate'] / 100 + 1), 3);
                    $row['gross_price_discount'] = f_round($row['net_price_discount'] * ($row['taxrate'] / 100 + 1), 3);
                    $row['tax_value'] = f_round($row['net_value'] * ($row['taxrate'] / 100));
                    $row['gross_value'] = f_round($row['net_value'] + $row['tax_value']);
                } else {
                    $row['base_gross_price'] = $row['base_price'];
                    $row['base_gross_value'] = f_round($row['base_gross_price'] * $row['count']);
                    $row['base_net_price'] =  f_round($row['base_gross_price'] / ($row['taxrate'] / 100 + 1), 3);
                    $row['base_tax_value'] = f_round(($row['base_gross_value'] * $row['taxrate']) / (100 + $row['taxrate']));
                    $row['base_net_value'] = f_round($row['base_gross_value'] - $row['base_tax_value']);

                    $row['gross_price'] = $row['price']; // price is discounted in sql already

                    $row['gross_price_discount'] = f_round($row['base_gross_price'] - $row['gross_price'], 3);
                    $row['gross_value'] = f_round($row['gross_price'] * $row['count']);
                    $row['net_price'] = f_round($row['gross_price'] / ($row['taxrate'] / 100 + 1), 3);
                    $row['net_price_discount'] = f_round($row['gross_price_discount'] / ($row['taxrate'] / 100 + 1), 3);
                    $row['tax_value'] = f_round(($row['gross_value'] * $row['taxrate']) / (100 + $row['taxrate']));
                    $row['net_value'] = f_round(($row['gross_value'] - $row['tax_value']));
                }

                $row['net_value_discount'] = f_round($row['base_net_value'] - $row['net_value']);
                $row['gross_value_discount'] = f_round($row['base_gross_value'] - $row['gross_value']);
                //</editor-fold>

                if (!empty($row['suspended'])) {
                    if (!empty($row['charge_suspension'])) {
                        if ($row['suspension_charge_method'] != SUSPENSION_CHARGE_METHOD_NONE) {
                            switch ($row['suspension_calculation_method']) {
                                case SUSPENSION_CALCULATION_METHOD_PERCENTAGE:
                                    if (!isset($suspensionsByCurrency[$row['currency']][$row['taxid']][$row['suspension_id']])) {
                                        $suspensionsByCurrency[$row['currency']][$row['taxid']][$row['suspension_id']] = array(
                                            'name' => trans("Suspension"),
                                            'taxid' => $row['taxid'],
                                            'currency' => $row['currency'],
                                            'customerid' => $row['customerid'],
                                            'suspensionid' => $row['suspension_id'],
                                            'customername' => $row['customername'],
                                            'address' => $row['address'],
                                            'ten' => $row['ten'],
                                            'total_net_value' => 0,
                                            'total_gross_value' => 0,
                                            'total_tax_value' => 0,
                                        );
                                    }
                                    if (!isset($suspensionsById[$row['suspension_id']])) {
                                        $suspensionsById[$row['suspension_id']]['suspension_id'] = $row['suspension_id'];
                                    }

                                    $suspension = $suspensionsByCurrency[$row['currency']][$row['taxid']][$row['suspension_id']];
                                    $suspension['suspend_assignments'][$idx]['assignment_id'] = $idx;

                                    $suspensionPercentage = !is_null($row['suspension_percentage']) ? f_round($row['suspension_percentage']) : $defaultSuspensionPercentage;
                                    if (!empty($row['netflag'])) {
                                        $suspension['net_price'] = f_round($row['net_price'] * ($suspensionPercentage / 100), 3);
                                        if ($row['suspension_charge_method'] == SUSPENSION_CHARGE_METHOD_PERIODICALLY) {
                                            if ($row['period'] == YEARLY) {
                                                $suspension['net_price'] = $suspension['net_price'] / 12;
                                            } elseif ($row['period'] == HALFYEARLY) {
                                                $suspension['net_price'] = $suspension['net_price'] / 6;
                                            } elseif ($row['period'] == QUARTERLY) {
                                                $suspension['net_price'] = $suspension['net_price'] / 3;
                                            } elseif ($row['period'] == WEEKLY) {
                                                $suspension['net_price'] = $suspension['net_price'] * 4;
                                            } elseif ($row['period'] == DAILY) {
                                                $suspension['net_price'] = $suspension['net_price'] * 30;
                                            }
                                        }
                                        $suspension['net_value'] = f_round($suspension['net_price'] * $row['count']);
                                        $suspension['gross_price'] = f_round($suspension['net_price'] * ($row['taxrate'] / 100 + 1), 3);
                                        $suspension['tax_value'] = f_round($suspension['net_value'] * ($row['taxrate'] / 100));
                                        $suspension['gross_value'] = f_round($suspension['net_value'] + $suspension['tax_value']);
                                    } else {
                                        $suspension['gross_price'] = f_round($row['gross_price'] * ($suspensionPercentage / 100), 3);
                                        if ($row['suspension_charge_method'] == SUSPENSION_CHARGE_METHOD_PERIODICALLY) {
                                            if ($row['period'] == YEARLY) {
                                                $suspension['gross_price'] = $suspension['gross_price'] / 12;
                                            } elseif ($row['period'] == HALFYEARLY) {
                                                $suspension['gross_price'] = $suspension['gross_price'] / 6;
                                            } elseif ($row['period'] == QUARTERLY) {
                                                $suspension['gross_price'] = $suspension['gross_price'] / 3;
                                            } elseif ($row['period'] == WEEKLY) {
                                                $suspension['gross_price'] = $suspension['gross_price'] * 4;
                                            } elseif ($row['period'] == DAILY) {
                                                $suspension['gross_price'] = $suspension['gross_price'] * 30;
                                            }
                                        }
                                        $suspension['gross_value'] = f_round($suspension['gross_price'] * $row['count']);
                                        $suspension['net_price'] = f_round($suspension['gross_price'] / ($row['taxrate'] / 100 + 1), 3);
                                        $suspension['tax_value'] = f_round(($suspension['gross_value'] * $row['taxrate']) / (100 + $row['taxrate']));
                                        $suspension['net_value'] = f_round(($suspension['gross_value'] - $suspension['tax_value']));
                                    }

                                    $suspension['total_net_value'] += $suspension['net_value'];
                                    $suspension['total_tax_value'] += $suspension['tax_value'];
                                    $suspension['total_gross_value'] += $suspension['gross_value'];

                                    $suspensionsByCurrency[$row['currency']][$row['taxid']][$row['suspension_id']] = $suspension;
                                    break;
                                case SUSPENSION_CALCULATION_METHOD_VALUE:
                                    // account only once for all assignemnts having this suspension
                                    if (!isset($suspensionsByCurrency[$row['suspension_currency']][$row['suspension_tax_id']][$row['suspension_id']])
                                        && !isset($suspensionsById[$row['suspension_id']])) {
                                        $suspensionsByCurrency[$row['suspension_currency']][$row['suspension_tax_id']][$row['suspension_id']] = array(
                                            'name' => trans("Suspension"),
                                            'customerid' => $row['suspension_customer_id'],
                                            'suspensionid' => $row['suspension_id'],
                                            'customername' => $row['customername'],
                                            'address' => $row['address'],
                                            'ten' => $row['ten'],
                                            'taxid' => $row['suspension_tax_id'],
                                            'taxlabel' => $row['suspension_taxlabel'],
                                            'taxrate' => $row['suspension_taxrate'],
                                            'note' => $row['suspension_note'],
                                            'datefrom' => $row['suspension_datefrom'],
                                            'dateto' => $row['suspension_dateto'],
                                            'charge_method' => $row['suspension_charge_method'],
                                            'calculation_method' => $row['suspension_calculation_method'],
                                            'value' => !is_null($row['suspension_value']) ? $row['suspension_value'] : $defaultSuspensionValue,
                                            'netflag' => $row['suspension_netflag'],
                                            'currency' => $row['suspension_currency'],
                                            'total_net_value' => 0,
                                            'total_gross_value' => 0,
                                            'total_tax_value' => 0,
                                        );

                                        $suspensionsById[$row['suspension_id']]['suspension_id'] = $row['suspension_id'];

                                        $suspension = $suspensionsByCurrency[$row['suspension_currency']][$row['suspension_tax_id']][$row['suspension_id']];
                                        $suspension['suspend_assignments'][$idx]['assignment_id'] = $idx;

                                        if (!empty($row['suspension_netflag'])) {
                                            $suspension['net_value'] = f_round($suspension['value']);
                                            if ($row['suspension_charge_method'] == SUSPENSION_CHARGE_METHOD_PERIODICALLY) {
                                                if ($row['period'] == YEARLY) {
                                                    $suspension['net_value'] = $suspension['net_value'] / 12;
                                                } elseif ($row['period'] == HALFYEARLY) {
                                                    $suspension['net_value'] = $suspension['net_value'] / 6;
                                                } elseif ($row['period'] == QUARTERLY) {
                                                    $suspension['net_value'] = $suspension['net_value'] / 3;
                                                } elseif ($row['period'] == WEEKLY) {
                                                    $suspension['net_value'] = $suspension['net_value'] * 4;
                                                } elseif ($row['period'] == DAILY) {
                                                    $suspension['net_value'] = $suspension['net_value'] * 30;
                                                }
                                            }
                                            $suspension['tax_value'] = f_round($suspension['net_value'] * ($row['suspension_taxrate'] / 100));
                                            $suspension['gross_value'] = f_round($suspension['net_value'] + $suspension['tax_value']);
                                        } else {
                                            $suspension['gross_value'] = f_round($suspension['value']);
                                            if ($row['suspension_charge_method'] == SUSPENSION_CHARGE_METHOD_PERIODICALLY) {
                                                if ($row['period'] == YEARLY) {
                                                    $suspension['gross_value'] = $suspension['gross_value'] / 12;
                                                } elseif ($row['period'] == HALFYEARLY) {
                                                    $suspension['gross_value'] = $suspension['gross_value'] / 6;
                                                } elseif ($row['period'] == QUARTERLY) {
                                                    $suspension['gross_value'] = $suspension['gross_value'] / 3;
                                                } elseif ($row['period'] == WEEKLY) {
                                                    $suspension['gross_value'] = $suspension['gross_value'] * 4;
                                                } elseif ($row['period'] == DAILY) {
                                                    $suspension['gross_value'] = $suspension['gross_value'] * 30;
                                                }
                                            }
                                            $suspension['tax_value'] = f_round(($suspension['gross_value'] * $row['suspension_taxrate']) / (100 + $row['suspension_taxrate']));
                                            $suspension['net_value'] = f_round(($suspension['gross_value'] - $suspension['tax_value']));
                                        }

                                        $suspension['total_net_value'] = $suspension['net_value'];
                                        $suspension['total_gross_value'] = $suspension['gross_value'];
                                        $suspension['total_tax_value'] = $suspension['tax_value'];

                                        $suspensionsByCurrency[$row['suspension_currency']][$row['suspension_tax_id']][$row['suspension_id']] = $suspension;
                                    }
                                    $suspensionsByCurrency[$row['suspension_currency']][$row['suspension_tax_id']][$row['suspension_id']]['suspend_assignments'][$idx]['assignment_id'] = $idx;

                                    break;
                            }
                        } else {
                            switch ($row['suspension_calculation_method']) {
                                case SUSPENSION_CALCULATION_METHOD_PERCENTAGE:
                                    if (!isset($suspensionsByCurrency[$row['currency']][$row['taxid']][$row['suspension_id']])) {
                                        $suspensionsByCurrency[$row['currency']][$row['taxid']][$row['suspension_id']] = array(
                                            'name' => trans("Suspension"),
                                            'taxid' => $row['taxid'],
                                            'currency' => $row['currency'],
                                            'customerid' => $row['customerid'],
                                            'suspensionid' => $row['suspension_id'],
                                            'customername' => $row['customername'],
                                            'address' => $row['address'],
                                            'ten' => $row['ten'],
                                            'total_net_value' => 0,
                                            'total_gross_value' => 0,
                                            'total_tax_value' => 0,
                                        );
                                    }
                                    if (!isset($suspensionsById[$row['suspension_id']])) {
                                        $suspensionsById[$row['suspension_id']]['suspension_id'] = $row['suspension_id'];
                                    }

                                    $suspension = $suspensionsByCurrency[$row['currency']][$row['taxid']][$row['suspension_id']];
                                    $suspension['suspend_assignments'][$idx]['assignment_id'] = $idx;

                                    $suspension['net_price'] = 0;
                                    $suspension['net_value'] = 0;
                                    $suspension['gross_price'] = 0;
                                    $suspension['tax_value'] = 0;
                                    $suspension['gross_value'] = 0;

                                    $suspension['total_net_value'] = 0;
                                    $suspension['total_tax_value'] = 0;
                                    $suspension['total_gross_value'] = 0;

                                    $suspensionsByCurrency[$row['currency']][$row['taxid']][$row['suspension_id']] = $suspension;
                                    break;
                                case SUSPENSION_CALCULATION_METHOD_VALUE:
                                    // account only once for all assignemnts having this suspension
                                    if (!isset($suspensionsByCurrency[$row['suspension_currency']][$row['suspension_tax_id']][$row['suspension_id']])
                                        && !isset($suspensionsById[$row['suspension_id']])) {
                                        $suspensionsByCurrency[$row['suspension_currency']][$row['suspension_tax_id']][$row['suspension_id']] = array(
                                            'name' => trans("Suspension"),
                                            'customerid' => $row['suspension_customer_id'],
                                            'suspensionid' => $row['suspension_id'],
                                            'customername' => $row['customername'],
                                            'address' => $row['address'],
                                            'ten' => $row['ten'],
                                            'taxid' => $row['suspension_tax_id'],
                                            'taxlabel' => $row['suspension_taxlabel'],
                                            'taxrate' => $row['suspension_taxrate'],
                                            'note' => $row['suspension_note'],
                                            'datefrom' => $row['suspension_datefrom'],
                                            'dateto' => $row['suspension_dateto'],
                                            'charge_method' => $row['suspension_charge_method'],
                                            'calculation_method' => $row['suspension_calculation_method'],
                                            'value' => !is_null($row['suspension_value']) ? $row['suspension_value'] : $defaultSuspensionValue,
                                            'netflag' => $row['suspension_netflag'],
                                            'currency' => $row['suspension_currency'],
                                            'total_net_value' => 0,
                                            'total_gross_value' => 0,
                                            'total_tax_value' => 0,
                                        );

                                        $suspensionsById[$row['suspension_id']]['suspension_id'] = $row['suspension_id'];

                                        $suspension = $suspensionsByCurrency[$row['suspension_currency']][$row['suspension_tax_id']][$row['suspension_id']];
                                        $suspension['suspend_assignments'][$idx]['assignment_id'] = $idx;

                                        $suspension['net_value'] = 0;
                                        $suspension['tax_value'] = 0;
                                        $suspension['gross_value'] = 0;

                                        $suspension['total_net_value'] = 0;
                                        $suspension['total_gross_value'] = 0;
                                        $suspension['total_tax_value'] = 0;

                                        $suspensionsByCurrency[$row['suspension_currency']][$row['suspension_tax_id']][$row['suspension_id']] = $suspension;
                                    }

                                    $suspensionsByCurrency[$row['suspension_currency']][$row['suspension_tax_id']][$row['suspension_id']]['suspend_assignments'][$idx]['assignment_id'] = $idx;

                                    break;
                            }
                        }
                    }
                    unset($assignments[$idx]);
                }
            }
            unset($row, $suspension);

            if (!empty($suspensionsByCurrency)) {
                $suspensions = array();
                foreach ($suspensionsByCurrency as $currency => $currencySuspensions) {
                    foreach ($currencySuspensions as $taxid => $currencyTaxesSuspensions) {
                        foreach ($currencyTaxesSuspensions as $sid => $currencyTaxSuspension) {
                            if ($currencyTaxSuspension['total_net_value'] != 0) {
                                $suspensions[] = array(
                                    'currency' => $currencyTaxSuspension['currency'],
                                    'taxid' => $currencyTaxSuspension['taxid'],
                                    'gross_value' => $currencyTaxSuspension['total_gross_value'],
                                    'net_value' => $currencyTaxSuspension['total_net_value'],
                                    'tax_value' => $currencyTaxSuspension['total_tax_value'],
                                    'customerid' => $currencyTaxSuspension['customerid'],
                                    'customername' => $currencyTaxSuspension['customername'],
                                    'address' => $currencyTaxSuspension['address'],
                                    'ten' => $currencyTaxSuspension['ten'],
                                );
                            }
                        }
                    }
                }
            }

            if (!empty($suspensions)) {
                $assignments = array_values($assignments);
                $assignments = array_merge($assignments, $suspensions);
            }

            //<editor-fold desc="Report array">
            $assignmentsByTax = array();
            foreach ($assignments as $row) {
                if (!isset($assignmentsByTax[$row['customerid']])) {
                    $assignmentsByTax[$row['customerid']] = array(
                        'id' => $row['customerid'],
                        'customername' => $row['customername'],
                        'address' => $row['address'],
                        'ten' => $row['ten'],
                        'values' => array(),
                    );
                }

                if (!isset($assignmentsByTax[$row['customerid']]['values'][$row['currency']])) {
                    $assignmentsByTax[$row['customerid']]['values'][$row['currency']] = array(
                        'value' => 0,
                        'taxsum' => 0,
                    );
                }

                if (!isset($assignmentsByTax[$row['customerid']]['values'][$row['currency']][$row['taxid']])) {
                    $assignmentsByTax[$row['customerid']]['values'][$row['currency']][$row['taxid']]['netto'] = 0;
                    $assignmentsByTax[$row['customerid']]['values'][$row['currency']][$row['taxid']]['tax'] = 0;
                }

                $assignmentsByTax[$row['customerid']]['values'][$row['currency']]['value'] += $row['gross_value'];
                $assignmentsByTax[$row['customerid']]['values'][$row['currency']][$row['taxid']]['netto'] += $row['net_value'];
                $assignmentsByTax[$row['customerid']]['values'][$row['currency']][$row['taxid']]['tax'] += $row['tax_value'];
                $assignmentsByTax[$row['customerid']]['values'][$row['currency']]['taxsum'] += $row['tax_value'];

                if (!isset($total['netto'][$row['currency']][$row['taxid']])) {
                    $total['netto'][$row['currency']][$row['taxid']] = 0;
                    $total['tax'][$row['currency']][$row['taxid']] = 0;
                }

                $total['netto'][$row['currency']][$row['taxid']] += $assignmentsByTax[$row['customerid']]['values'][$row['currency']][$row['taxid']]['netto'];
                $total['tax'][$row['currency']][$row['taxid']] += $assignmentsByTax[$row['customerid']]['values'][$row['currency']][$row['taxid']]['tax'];
            }
            //</editor-fold>

            $reportlist = $assignmentsByTax;
            if (!empty($reportlist)) {
                $table = array();
                $tmplist = array();
                switch ($order) {
                    case 'customername':
                        foreach ($reportlist as $idx => $row) {
                            $table['idx'][] = $idx;
                            $table['customername'][] = $row['customername'];
                        }
                        if (!empty($table)) {
                            array_multisort($table['customername'], ($direction == 'desc' ? SORT_DESC : SORT_ASC), $table['idx']);
                            foreach ($table['idx'] as $idx) {
                                $tmplist[] = $reportlist[$idx];
                            }
                        }
                        $reportlist = empty($tmplist) ? array() : $tmplist;
                        break;
                    default:
                        foreach ($reportlist as $idx => $row) {
                            $table['idx'][] = $idx;
                            $table['value'][] = $row['value'];
                        }
                        if (is_array($table)) {
                            array_multisort($table['value'], ($direction == 'desc' ? SORT_DESC : SORT_ASC), $table['idx']);
                            foreach ($table['idx'] as $idx) {
                                $tmplist[] = $reportlist[$idx];
                            }
                        }
                        $reportlist = $tmplist;
                        break;
                }
            }
        }

        $SMARTY->assign('reportlist', $reportlist);
        $SMARTY->assign('total', $total);
        $SMARTY->assign('taxes', $taxes);
        $SMARTY->assign('taxescount', count($taxes));

        if (strtolower($report_type) == 'pdf') {
            $output = $SMARTY->fetch('print/printliabilityreport.html');
            Utils::html2pdf(array(
                'content' => $output,
                'subject' => trans('Reports'),
                'title' => $layout['pagetitle'],
            ));
        } else {
            $SMARTY->display('print/printliabilityreport.html');
        }
        break;

    case 'receiptlist':
        if (!ConfigHelper::checkConfig('privileges.superuser') && !ConfigHelper::checkConfig('privileges.cash_operations')) {
            access_denied();
        }

        if (!empty($_POST['from'])) {
            [$year, $month, $day] = explode('/', $_POST['from']);
            $from = mktime(0, 0, 0, $month, $day, $year);
        } else {
            $from = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        }

        if (!empty($_POST['to'])) {
            [$year, $month, $day] = explode('/', $_POST['to']);
            $to = mktime(23, 59, 59, $month, $day, $year);
        } else {
            $to = mktime(23, 59, 59, date('m'), date('d'), date('Y'));
        }

        $registry = isset($_POST['registry']) ? intval($_POST['registry']) : 0;
        $user = isset($_POST['user']) ? intval($_POST['user']) : 0;
        $group = isset($_POST['group']) ? intval($_POST['group']) : 0;
        $sorttype = $_POST['sorttype'] ?? null;
        $where = '';

        if ($registry) {
            $where .= ' AND regid = '.$registry;
        }
        if ($from) {
            $where .= ' AND cdate >= '.$from;
        }
        if ($to) {
            $where .= ' AND cdate <= '.$to;
        }
        if ($user) {
            $where .= ' AND userid = '.$user;
        }
        if ($group) {
                $groupwhere = ' AND '.(isset($_POST['groupexclude']) ? 'NOT' : '').'
			            EXISTS (SELECT 1 FROM vcustomerassignments a
				            WHERE a.customergroupid = '.$group.'
					    AND a.customerid = d.customerid)';
            $where .= $groupwhere;
        }

        if ($from > 0) {
            $listdata['startbalance'] = $DB->GetOne(
                'SELECT SUM(value * d.currencyvalue) FROM receiptcontents
						LEFT JOIN documents d ON (docid = d.id AND type = ?)
						WHERE cdate < ?'
                        .($registry ? ' AND regid='.$registry : '')
                        .($user ? ' AND userid='.$user : '')
                        .($group ? $groupwhere : '')
                        .' AND NOT EXISTS (
						        SELECT 1 FROM vcustomerassignments a
							JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
							WHERE e.userid = lms_current_user() AND a.customerid = d.customerid)',
                array(DOC_RECEIPT, $from)
            );
        }

        // Sorting
        switch ($sorttype) {
            case 'number':
                $sortcol = 'd.number';
                break;
            case 'cdate':
            default:
                $sortcol = 'd.cdate';
        }

        $listdata['totalincome'] = 0;
        $listdata['totalexpense'] = 0;
        $listdata['advances'] = 0;

        if ($list = $DB->GetAll(
            'SELECT d.id AS id, SUM(value) AS value, d.currency, d.currencyvalue, number, cdate, customerid,
				d.name, address, zip, city, numberplans.template, extnumber, closed,
				MIN(description) AS title, COUNT(*) AS posnumber
			FROM documents d
			LEFT JOIN numberplans ON (numberplanid = numberplans.id)
			LEFT JOIN receiptcontents ON (d.id = docid)
			WHERE d.type = ?'
            .$where.'
				AND NOT EXISTS (
					SELECT 1 FROM vcustomerassignments a
					JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
					WHERE e.userid = lms_current_user() AND a.customerid = d.customerid)
			GROUP BY d.id, number, cdate, customerid, d.name, address, zip, city, numberplans.template, extnumber, closed
			ORDER BY ' . $sortcol . ', d.id',
            array(DOC_RECEIPT)
        )) {
            foreach ($list as $idx => $row) {
                $list[$idx]['number'] = docnumber(array(
                    'number' => $row['number'],
                    'template' => $row['template'],
                    'cdate' => $row['cdate'],
                    'ext_num' => $row['extnumber'],
                ));
                $list[$idx]['customer'] = $row['name'].' '.$row['address'].' '.$row['zip'].' '.$row['city'];

                if ($row['posnumber'] > 1) {
                    $list[$idx]['title'] = $DB->GetCol('SELECT description FROM receiptcontents WHERE docid=? ORDER BY itemid', array($list[$idx]['id']));
                }

                // summary
                if ($row['value'] > 0) {
                    $listdata['totalincome'] += $row['value'] * $row['currencyvalue'];
                } else {
                    $listdata['totalexpense'] += -$row['value'] * $row['currencyvalue'];
                }

                if ($idx==0) {
                    $list[$idx]['after'] = $listdata['startbalance'] + $row['value'] * $row['currencyvalue'];
                } else {
                    $list[$idx]['after'] = $list[$idx-1]['after'] + $row['value'] * $row['currencyvalue'];
                }

                if (!$row['closed']) {
                    $listdata['advances'] -= $row['value'] * $row['currencyvalue'];
                }
            }
        }

        $listdata['endbalance'] = $listdata['startbalance'] + $listdata['totalincome'] - $listdata['totalexpense'];

        $from = date('Y/m/d', $from);
        $to = date('Y/m/d', $to);

        if ($from == $to) {
            $period = $from;
        } else {
            $period = $from.' - '.$to;
        }

        $layout['pagetitle'] = trans('Cash Report').' '.$period;

        if ($registry) {
            $layout['registry'] = trans('Registry: $a', ($registry ? $DB->GetOne('SELECT name FROM cashregs WHERE id=?', array($registry)) : trans('all')));
        }
        if ($user) {
            $layout['username'] = trans('Cashier: $a', $DB->GetOne('SELECT name FROM vusers WHERE id=?', array($user)));
        }
        if ($group) {
            $groupname = $DB->GetOne('SELECT name FROM customergroups WHERE id=?', array($group));

            if (isset($_POST['groupexclude'])) {
                $layout['group'] = trans('Group: all excluding $a', $groupname);
            } else {
                $layout['group'] = trans('Group: $a', $groupname);
            }
        }
        $SMARTY->assign('receiptlist', $list);
        $SMARTY->assign('listdata', $listdata);

        if (isset($_POST['extended'])) {
                $pages = array();
            $totals = array();

            // hidden option: max records count for one page of printout
            // I think 20 records is fine, but someone needs 19.
            $rows = ConfigHelper::getConfig('phpui.printout_pagelimit', 20);

            // create a new array and do some calculations
            // (summaries and page size calculations)
            $maxrows = $rows * 2;   // dwie linie na rekord
            $counter = $maxrows;
            $rows = 0;      // rzeczywista liczba rekordów na stronie
            $i = 1;
            $x = 1;

            foreach ($list as $row) {
                // tutaj musimy trochę pokombinować, bo liczba
                // rekordów na stronie będzie zmienna
                $tmp = is_array($row['title']) ? count($row['title']) : 2;
                $counter -= max($tmp, 2);
                if ($counter<0) {
                    $x++;
                    $rows = 0;
                    $counter = $maxrows;
                }

                $rows++;
                $page = $x;

                if ($row['value'] > 0) {
                    if (!isset($totals[$page]['income'])) {
                        $totals[$page]['income'] = 0;
                    }
                    $totals[$page]['income'] += $row['value'] * $row['currencyvalue'];
                } else {
                    if (!isset($totals[$page]['expense'])) {
                        $totals[$page]['expense'] = 0;
                    }
                    $totals[$page]['expense'] += -$row['value'] * $row['currencyvalue'];
                }

                $totals[$page]['rows'] = $rows;
            }

            foreach ($totals as $page => $t) {
                $pages[] = $page;

                $totals[$page]['totalincome'] = ($totals[$page - 1]['totalincome'] ?? 0) + $t['income'];
                $totals[$page]['totalexpense'] = ($totals[$page - 1]['totalexpense'] ?? 0)
                    + ($t['expense'] ?? 0);
                $totals[$page]['rowstart'] = isset($totals[$page - 1]) ? $totals[$page - 1]['rowstart'] + $totals[$page - 1]['rows'] : 0;
            }

            $SMARTY->assign('pages', $pages);
            $SMARTY->assign('totals', $totals);
            $SMARTY->assign('pagescount', count($pages));
            $SMARTY->assign('reccount', count($list));
            if (strtolower($report_type) == 'pdf') {
                $output = $SMARTY->fetch('print/printreceiptlist-ext.html');
                Utils::html2pdf(array(
                    'content' => $output,
                    'subject' => trans('Reports'),
                    'title' => $layout['pagetitle'],
                ));
            } else {
                $SMARTY->display('print/printreceiptlist-ext.html');
            }
        } else {
            if (strtolower($report_type) == 'pdf') {
                $output = $SMARTY->fetch('print/printreceiptlist.html');
                Utils::html2pdf(array(
                    'content' => $output,
                    'subject' => trans('Reports'),
                    'title' => $layout['pagetitle'],
                ));
            } else {
                $SMARTY->display('print/printreceiptlist.html');
            }
        }
        break;

    default:
        /*******************************************************/

        $layout['pagetitle'] = trans('Reports');

        if (!ConfigHelper::checkConfig('phpui.big_networks')) {
            $SMARTY->assign('customers', $LMS->GetCustomerNames());
        }
        $SMARTY->assign('users', $LMS->GetUserNames());
        $SMARTY->assign('networks', $LMS->GetNetworks());
        $SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
        $SMARTY->assign('numberplans', $LMS->GetNumberPlans(array(
            'doctype' => array(DOC_INVOICE, DOC_CNOTE),
        )));
        $SMARTY->assign('cashreglist', $DB->GetAllByKey('SELECT id, name FROM cashregs ORDER BY name', 'id'));
        $SMARTY->assign('divisions', $LMS->GetDivisions());
        $SMARTY->assign('sourcelist', $DB->GetAll('SELECT id, name FROM cashsources ORDER BY name'));
        $SMARTY->assign('printmenu', 'finances');

        $SMARTY->assign('invprojects', $LMS->GetProjects());

        $SMARTY->display('print/printindex.html');

        break;
}
