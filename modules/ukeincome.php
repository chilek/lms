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

if (isset($_POST['type'])) {
    $type = isset($_POST['type']) && $_POST['type'] == 'linktechnologies' ? 'linktechnologies' : 'servicetypes';
    $filter['uke-income']['type'] = $type;
} else {
    $type = 'servicetypes';
    unset($filter['uke-income']['type']);
}

if (isset($_POST['brutto'])) {
    $filter['uke-income']['brutto'] = 1;

    $value_formula = 'cash.value';
} else {
    unset($filter['uke-income']['brutto']);

    $value_formula = '(cash.value * 100) / (100 + t.value)';
}

$bandwidths = isset($_POST['bandwidths']);
if ($bandwidths) {
    $filter['uke-income']['bandwidths'] = 1;
} else {
    unset($filter['uke-income']['bandwidths']);
}

$division = intval($_POST['division']);
if ($division) {
    $filter['uke-income']['division'] = $division;
} else {
    unset($filter['uke-income']['division']);
}

$SESSION->saveFilter($filter['uke-income'], 'print', null, false, 'uke-income');

$income = $DB->GetAll('
	SELECT ' . ($type == 'linktechnologies' ? 'cash.linktechnology' : 'tf.type') . ' AS type,
		COUNT(DISTINCT CASE WHEN c.type = 0 THEN c.id ELSE null END) AS privatecount,
		COUNT(DISTINCT CASE WHEN c.type = 1 THEN c.id ELSE null END) AS bussinesscount,
		COUNT(DISTINCT c.id) AS totalcount,
		SUM(CASE WHEN c.type = 0 THEN ' . $value_formula . ' ELSE 0 END) * -1 AS privateincome,
		SUM(CASE WHEN c.type = 1 THEN ' . $value_formula . ' ELSE 0 END) * -1 AS bussinessincome,
		SUM(' . $value_formula . ') * -1 AS totalincome
	FROM cash
    LEFT JOIN documents d ON d.id = cash.docid
    LEFT JOIN invoicecontents ic ON ic.docid = d.id AND ic.itemid = cash.itemid
    LEFT JOIN tariffs tf ON tf.id = ic.tariffid
	JOIN customers c ON c.id = cash.customerid
	JOIN taxes t ON t.id = cash.taxid
	WHERE cash.type = 0 AND time >= ? AND time <= ?'
    . ($division ? ' AND ((cash.docid IS NOT NULL AND d.divisionid = ' . $division . ')
            OR (cash.docid IS NULL AND c.divisionid = ' . $division . '))' : '')
    . ($type == 'linktechnologies' ?
        ' GROUP BY cash.linktechnology
	    ORDER BY cash.linktechnology' :
        ' AND cash.docid IS NOT NULL
        GROUP BY tf.type
        ORDER BY tf.type'
    ), array($unixfrom, $unixto));

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

    $bandwidth_variation = array();

    $customer_links = $DB->GetAll(
        'SELECT ' . ($type == 'linktechnologies' ? 'cash.linktechnology' : 't.type') . ' AS type,
            t.downceil,
            SUM(CASE WHEN c.type = 0 THEN ROUND(ic.count) ELSE 0 END) AS private,
            SUM(CASE WHEN c.type = 1 THEN ROUND(ic.count) ELSE 0 END) AS bussiness,
            SUM(ROUND(ic.count)) AS total
        FROM cash
        JOIN customers c ON c.id = cash.customerid
        JOIN invoicecontents ic ON ic.docid = cash.docid AND ic.itemid = cash.itemid
        JOIN tariffs t ON t.id = ic.tariffid
        WHERE ' . ($type == 'linktechnologies' ? 't.type = ' . SERVICE_INTERNET . ' AND cash.linktechnology IS NOT NULL' : '1=1') . '
            AND t.downceil > 0 AND t.upceil > 0
            AND cash.time >= ? AND cash.time <= ? '
        . ($division ? ' AND ((cash.docid IS NOT NULL AND d.divisionid = ' . $division . ')
            OR (cash.docid IS NULL AND c.divisionid = ' . $division . '))' : '') . '
        GROUP BY ' . ($type == 'linktechnologies' ? 'cash.linktechnology' : 't.type') . ', t.downceil
        ORDER BY ' . ($type == 'linktechnologies' ? 'cash.linktechnology' : 't.type'),
        array($unixfrom, $unixto)
    );
    if (!empty($customer_links)) {
        foreach ($customer_links as $customer_link) {
            $bandwidth_type = $customer_link['type'];
            if (!isset($bandwidth_variation[$bandwidth_type])) {
                $bandwidth_variation[$bandwidth_type] = $bandwidth_intervals;
            }
            $downceil = intval($customer_link['downceil']);
            foreach ($bandwidth_variation[$bandwidth_type] as $label => &$bandwidth_interval) {
                if ($downceil >= $bandwidth_interval['min']
                    && (!isset($bandwidth_interval['max']) || $downceil < $bandwidth_interval['max'])) {
                    $bandwidth_interval['total'] += $customer_link['total'];
                    $bandwidth_interval['private'] += $customer_link['private'];
                    $bandwidth_interval['bussiness'] += $customer_link['bussiness'];
                    break;
                }
            }
            unset($bandwidth_interval);
        }
    }
    $SMARTY->assign('bandwidth_variation', $bandwidth_variation);
}

if ($type == 'linktechnologies') {
    $linktechnologies = array();
    foreach ($LINKTECHNOLOGIES as $linktype => $technologies) {
        foreach ($technologies as $techid => $techlabel) {
            $linktechnologies[$techid] = trans('<!link>$a ($b)', $techlabel, $LINKTYPES[$linktype]);
        }
    }
    $SMARTY->assign('linktechnologies', $linktechnologies);
}

$layout['pagetitle'] = trans('UKE income report for period $a - $b', $from, $to);

$SMARTY->assign('income', $income);
$SMARTY->display('print/printukeincome.html');
