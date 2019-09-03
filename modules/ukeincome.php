<?php

/**
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

$from = $_POST['from'];
$to = $_POST['to'];

// date format 'yyyy/mm/dd'
if ($from) {
    list ($year, $month, $day) = explode('/', $from);
    $unixfrom = mktime(0, 0, 0, $month, $day, $year);
} else {
    $from = date('Y/m/d', time());
    $unixfrom = mktime(0, 0, 0); //today
}
if ($to) {
    list ($year, $month, $day) = explode('/', $to);
    $unixto = mktime(23, 59, 59, $month, $day, $year);
} else {
    $to = date('Y/m/d', time());
    $unixto = mktime(23, 59, 59); //today
}

if (isset($_POST['brutto'])) {
    $value_formula = 'cash.value';
} else {
    $value_formula = '(cash.value * 100) / (100 + t.value)';
}

$bandwidths = isset($_POST['bandwidths']);

$income = $DB->GetAll('
	SELECT cash.linktechnology AS technology,
		COUNT(DISTINCT CASE WHEN c.type = 0 THEN c.id ELSE null END) AS privatecount,
		COUNT(DISTINCT CASE WHEN c.type = 1 THEN c.id ELSE null END) AS bussinesscount,
		COUNT(DISTINCT c.id) AS totalcount,
		SUM(CASE WHEN c.type = 0 THEN ' . $value_formula . ' ELSE 0 END) * -1 AS privateincome,
		SUM(CASE WHEN c.type = 1 THEN ' . $value_formula . ' ELSE 0 END) * -1 AS bussinessincome,
		SUM(' . $value_formula . ') * -1 AS totalincome
	FROM cash
	JOIN customers c ON c.id = cash.customerid
	JOIN taxes t ON t.id = cash.taxid
	WHERE cash.type = 0 AND time >= ? AND time <= ?
	GROUP BY cash.linktechnology
	ORDER BY cash.linktechnology', array($unixfrom, $unixto));

if ($bandwidths) {
    $bandwidth_intervals = array(
        '>= 144 kbit/s < 2 Mbit/s' => array(
            'min' => 144,
            'max' => 2000,
            'total' => 0,
            'private' => 0,
            'bussiness' => 0,
        ),
        '>= 2 Mbit/s < 10 Mbit/s' => array(
            'min' => 2000,
            'max' => 10000,
            'total' => 0,
            'private' => 0,
            'bussiness' => 0,
        ),
        '>= 10 Mbit/s < 30 Mbit/s' => array(
            'min' => 10000,
            'max' => 30000,
            'total' => 0,
            'private' => 0,
            'bussiness' => 0,
        ),
        '>= 10 Mbit/s < 30 Mbit/s' => array(
            'min' => 10000,
            'max' => 30000,
            'total' => 0,
            'private' => 0,
            'bussiness' => 0,
        ),
        '>= 30 Mbit/s < 100 Mbit/s' => array(
            'min' => 30000,
            'max' => 100000,
            'total' => 0,
            'private' => 0,
            'bussiness' => 0,
        ),
        '>= 100 Mbit/s < 1 Gbit/s' => array(
            'min' => 100000,
            'max' => 1000000,
            'total' => 0,
            'private' => 0,
            'bussiness' => 0,
        ),
        '>= 1 Gbit/s' => array(
            'min' => 1000000,
            'total' => 0,
            'private' => 0,
            'bussiness' => 0,
        ),
    );

    $bandwidth_linktechnologies = array();

    $customer_links = $DB->GetAll('
        SELECT cash.linktechnology, t.downceil, t.upceil,
            SUM(CASE WHEN c.type = 0 THEN 1 ELSE 0 END) AS private,
            SUM(CASE WHEN c.type = 1 THEN 1 ELSE 0 END) AS bussiness,
            COUNT(*) AS total
        FROM cash
        JOIN customers c ON c.id = cash.customerid
        JOIN invoicecontents ic ON ic.docid = cash.docid AND ic.itemid = cash.itemid
        JOIN tariffs t ON t.id = ic.tariffid
        JOIN assignments a ON a.tariffid = t.id AND a.customerid = cash.customerid
        JOIN nodeassignments na ON na.assignmentid = a.id
        LEFT JOIN (
            SELECT customerid FROM assignments a
            WHERE a.tariffid IS NULL AND a.liabilityid IS NULL
                AND a.datefrom <= ?
                AND (a.dateto >= ? OR a.dateto = 0)
        ) allsuspended ON allsuspended.customerid = cash.customerid
        WHERE t.type = ? AND cash.linktechnology IS NOT NULL
            AND t.downceil > 0 AND t.upceil > 0
            AND allsuspended.customerid IS NULL
            AND cash.time >= ? AND cash.time <= ?
            AND a.datefrom <= ?
            AND (a.dateto = 0 OR a.dateto >= ?)
            AND a.suspended = 0
        GROUP BY cash.linktechnology, t.downceil, t.upceil
        ORDER BY cash.linktechnology', array($unixfrom, $unixto, SERVICE_INTERNET, $unixfrom, $unixto, $unixfrom, $unixto));
    if (!empty($customer_links)) {
        foreach ($customer_links as $customer_link) {
            $linktechnology = $customer_link['linktechnology'];
            if (!isset($bandwidth_linktechnologies[$linktechnology])) {
                $bandwidth_linktechnologies[$linktechnology] = $bandwidth_intervals;
            }
            $downceil = intval($customer_link['downceil']);
            foreach ($bandwidth_linktechnologies[$linktechnology] as $label => &$bandwidth_interval) {
                if ($downceil >= $bandwidth_interval['min']
                    && (!isset($bandwidth_interval['max']) || $downceil <= $bandwidth_interval['max'])) {
                    $bandwidth_interval['total'] += $customer_link['total'];
                    $bandwidth_interval['private'] += $customer_link['private'];
                    $bandwidth_interval['bussiness'] += $customer_link['bussiness'];
                    break;
                }
            }
            unset($bandwidth_interval);
        }
    }
    $SMARTY->assign('bandwidth_linktechnologies', $bandwidth_linktechnologies);
}

$linktechnologies = array();
foreach ($LINKTECHNOLOGIES as $linktype => $technologies) {
    foreach ($technologies as $techid => $techlabel) {
        $linktechnologies[$techid] = trans('<!link>$a ($b)', $techlabel, $LINKTYPES[$linktype]);
    }
}

$layout['pagetitle'] = trans('UKE income report for period $a - $b', $from, $to);

$SMARTY->assign('income', $income);
$SMARTY->assign('linktechnologies', $linktechnologies);
$SMARTY->display('print/printukeincome.html');
