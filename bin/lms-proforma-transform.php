#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2025 LMS Developers
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

$script_parameters = array(
    'customerid:' => null,
);

$script_help = <<<EOF
    --customerid=<id>           limit assignments to specifed customer
EOF;

require_once('script-options.php');

$test = isset($options['test']);
if ($test) {
    echo "WARNING! You are using test mode." . PHP_EOL;
}

if (isset($options['customerid'])) {
    if (preg_match('/^[0-9]+(,[0-9]+)*$/', $options['customerid'])) {
        $customerid = array_map(
            function ($customerid) {
                return intval($customerid);
            },
            explode(',', $options['customerid'])
        );
    } else {
        $customerid = null;
    }
} else {
    $customerid = null;
}

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes

$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

$plugin_manager = LMSPluginManager::getInstance();
$LMS->setPluginManager($plugin_manager);

// prepare customergroups in sql query
$customergroups = " AND EXISTS (SELECT 1 FROM customergroups g, vcustomerassignments ca
	WHERE c.id = ca.customerid
	AND g.id = ca.customergroupid
	AND (%groups)) ";
$groupnames = ConfigHelper::getConfig('proforma-transform.customergroups');
$groupsql = "";
$groups = preg_split("/[[:blank:]]+/", $groupnames, -1, PREG_SPLIT_NO_EMPTY);
foreach ($groups as $group) {
    if (!empty($groupsql)) {
        $groupsql .= " OR ";
    }
    $groupsql .= "UPPER(g.name) = UPPER('".$group."')";
}
if (!empty($groupsql)) {
    $customergroups = preg_replace("/\%groups/", $groupsql, $customergroups);
}

// **********************************************************
// convert pro forma invoices to invoices (if it is possible)
// **********************************************************

$customers = $DB->GetAll(
    'SELECT c.id, SUM(c.balance) AS balance FROM
        (
            (
                SELECT customerid AS id, balance AS balance
                FROM customerbalances
            ) UNION (
                SELECT d.customerid AS id, -SUM(ROUND(ic.grossvalue * d.currencyvalue, 2)) AS balance
                FROM documents d
                JOIN vinvoicecontents ic ON ic.docid = d.id
                WHERE d.type = ? AND d.cancelled = 0'
                    . (ConfigHelper::checkConfig('phpui.proforma_invoice_generates_commitment') ? 'AND 1=0' : '') . '
                GROUP BY d.customerid
            )
        ) c
    WHERE c.id IN (SELECT DISTINCT customerid FROM documents WHERE type = ? AND closed = 0)'
    . (empty($customerid) ? '' : ' AND c.id IN (' . implode(',', $customerid) . ')')
    . (!empty($groupnames) ? $customergroups : '') . '
    GROUP BY c.id
    ORDER BY c.id',
    array(DOC_INVOICE_PRO, DOC_INVOICE_PRO)
);

if (empty($customers)) {
    if (!$quiet) {
        die('No customers with open pro forma invoices to transform!' . PHP_EOL);
    } else {
        die;
    }
}

foreach ($customers as $customer) {
    $customerid = $customer['id'];
    $balance = $customer['balance'];

    if (!$quiet) {
        print 'CID=' . $customerid . ': looking for pro forma invoices ...' . PHP_EOL;
    }

    $documents = $DB->GetAll(
        '(
                SELECT cash.time, cash.docid, documents.type AS doctype, documents.closed,
                    documents.archived, SUM(ROUND(cash.value * cash.currencyvalue, 2)) AS value
                FROM cash
                LEFT JOIN documents ON documents.id = docid
                WHERE cash.customerid = ? AND cash.value <> 0 AND (cash.value < 0 OR documents.type = ?)
                GROUP BY cash.time, cash.docid, documents.type, documents.closed, documents.archived
            ) UNION (
                SELECT d.cdate AS time, d.id AS docid, d.type AS doctype, d.closed,
                    d.archived, -SUM(ROUND(ic.grossvalue * d.currencyvalue, 2)) AS value
                FROM documents d
                JOIN vinvoicecontents ic ON ic.docid = d.id
                WHERE ' . (ConfigHelper::checkConfig('phpui.proforma_invoice_generates_commitment') ? '1=0 AND' : '') . '
                    d.customerid = ? AND d.type = ? AND d.cancelled = 0
                GROUP BY d.cdate, d.id, d.type, d.closed, d.archived
            )
            ORDER BY time DESC',
        array($customerid, DOC_CNOTE, $customerid, DOC_INVOICE_PRO)
    );

    if (empty($documents)) {
        if (!$quiet) {
            print 'CID=' . $customerid . ': no proforma invoices to transform!' . PHP_EOL;
        }
        continue;
    }

    while ($balance < 0 && !empty($documents)) {
        $document = array_shift($documents);
        $balance -= $document['value'];
    }
    if ($balance < 0 || empty($documents)) {
        if (!$quiet) {
            print 'CID=' . $customerid . ': no proforma invoices to transform (no new payments?)!' . PHP_EOL;
        }
        continue;
    }

    $documents = array_reverse($documents);
    foreach ($documents as $document) {
        if ($document['doctype'] == DOC_INVOICE_PRO) {
            if (!$quiet) {
                if (!empty($document['closed'])) {
                    print 'CID=' . $customerid . ': proforma invoice (DOCID=' . $document['docid'] . ') is closed!' . PHP_EOL;
                }
                if (!empty($document['archived'])) {
                    print 'CID=' . $customerid . ': proforma invoice (DOCID=' . $document['docid'] . ') is archived!' . PHP_EOL;
                }
            }
            if (!empty($document['closed']) || !empty($document['archived'])) {
                continue;
            }

            $result = $LMS->transformProformaInvoice($document['docid']);
            if (is_string($result)) {
                if (!$quiet) {
                    print 'CID=' . $customerid . ': proforma invoice transformation error: ' . $result . PHP_EOL;
                }
                continue;
            }
            if (!$quiet) {
                if ($result) {
                    print 'CID=' . $customerid . ': proforma invoice (DOCID=' . $document['docid']
                        . ') transformed to invoice (DOCID=' . $result . ').' . PHP_EOL;
                } else {
                    print 'CID=' . $customerid . ': proforma invoice (DOCID=' . $document['docid']
                        . ') transformation to invoice failed!' . PHP_EOL;
                }
            }
        }
    }
}
