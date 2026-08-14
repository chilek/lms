#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2024 LMS Developers
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
    'test' => 't',
    'resources:' => 'r:',
    'time-limit:' => 't:',
    'balance-limit:' => 'b:',
);

$script_help = <<<EOF
-t, --test                      no changes are made to database;
-r, --resources=<finances,customers>
                                system resource type list to clean up;
-t, --time-limit=<days>
                                only resources older than specified 'days' are cleaned up;
-b, --balance-limit=<value>
                                only resources related to customers with balance greater than
                                or equal to specified 'value' are cleanup up;
EOF;

require_once('script-options.php');

$test = isset($options['test']);
if ($test) {
    echo "WARNING! You are using test mode." . PHP_EOL;
}

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes

$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

Localisation::initDefaultCurrency();
Localisation::setUILanguage(Localisation::getCurrentSystemLanguage());

$SYSLOG->NewTransaction('lms-cleanup.php');

$resources = array();

if (isset($options['resources'])) {
    $resources = explode(',', $options['resources']);
}

if (empty($resources)) {
    die('Fatal error: No resource types to clean up specified!' . PHP_EOL);
}

$supported_resources = array(
    'finances' => true,
    'customers' => true,
);
foreach ($resources as $resource) {
    if (!isset($supported_resources[$resource])) {
        die('Fatal error: resource type \'' . $resource . '\' is not supported!' . PHP_EOL);
    }
}

if (isset($options['time-limit'])) {
    if (!preg_match('/^[0-9]+$/', $options['time-limit'])) {
        die('Fatal error: --time-limit parameter value syntax error!' . PHP_EOL);
    }
    $time_limit = intval($options['time-limit']);
} else {
    $time_limit = 6 * 366;
}

$time = strtotime($time_limit . ' days ago');

if (isset($options['balance-limit'])) {
    $balance_limit = filter_var($options['balance-limit'], FILTER_VALIDATE_FLOAT);
    if ($balance_limit === false) {
        $balance_limit = null;
    }
}

$currency = Localisation::getDefaultCurrency();

$resources = array_flip($resources);

if (!$quiet) {
    echo PHP_EOL . 'Current time limit operation threshold: ' . $time_limit . ' days' . PHP_EOL;
    if (isset($balance_limit)) {
        echo 'Current balance limit operation threshold: ' . $balance_limit . PHP_EOL;
    }
}

if (isset($resources['finances'])) {
    if (!$quiet) {
        echo PHP_EOL;
        echo '###################' . PHP_EOL;
        echo 'Financial resources' . PHP_EOL;
        echo '###################' . PHP_EOL;
    }

    $balances = $DB->GetAllByKey(
        'SELECT customerid, SUM(value * currencyvalue) AS balance
        FROM cash
        WHERE time < ?
            AND customerid IS NOT NULL
        GROUP BY customerid',
        'customerid',
        array($time)
    );

    if (empty($balances)) {
        $balances = array();
    } else {
        $balances = Utils::array_column($balances, 'balance', 'customerid');
    }

    $DB->BeginTrans();

    if (!$quiet) {
        echo 'Creating starting balance records... ';
    }
    foreach ($balances as $customerid => $balance) {
        $DB->Execute(
            'INSERT INTO cash (customerid, time, type, value, currency, comment) VALUES (?, ?, ?, ?, ?, ?)',
            array(
                $customerid,
                $time,
                1,
                $balance,
                $currency,
                trans('Starting balance at $a', date('Y/m/d', $time)),
            )
        );
    }
    if (!$quiet) {
        echo count($balances) . ' record(s) created.' . PHP_EOL;
    }

    $documents = $DB->GetAll(
        'SELECT DISTINCT cash.docid AS id, d.archived
        FROM cash
        JOIN documents d ON d.id = cash.docid
        WHERE d.type IN ? AND cash.time < ?',
        array(
            array(DOC_INVOICE, DOC_CNOTE, DOC_INVOICE_PRO, DOC_DNOTE),
            $time,
        )
    );
    if (!empty($documents)) {
        if (!$quiet) {
            echo 'Removing trade and cash document(s)... ';
        }
        foreach ($documents as $document) {
            if (!empty($document['archived'])) {
                $LMS->DeleteArchiveTradeDocument($document['id']);
            }
        }

        $docids = Utils::array_column($documents, 'id');
        $count = $DB->Execute(
            'DELETE FROM documents
            WHERE id IN ?',
            array(Utils::array_column($documents, 'id'))
        );
        if (!$quiet) {
            echo $count . ' removed.' . PHP_EOL;
        }
    }

    if (!$quiet) {
        echo 'Removing cash import record(s)... ';
    }
    $count = $DB->Execute(
        'DELETE FROM cashimport
        WHERE id IN (
            SELECT importid
            FROM cash
            WHERE time < ?
        )',
        array($time)
    );
    if (!$quiet) {
        echo (empty($count) ? '0' : $count) . ' cash import(s) removed. ';
    }

    $count = $DB->Execute(
        'DELETE FROM sourcefiles
        WHERE idate < ?
          AND NOT EXISTS (
              SELECT 1
              FROM cashimport
              WHERE cashimport.sourcefileid = sourcefiles.id
          )',
        array($time)
    );

    if (!$quiet) {
        echo (empty($count) ? '0' : $count) . ' source file(s) removed. ' . PHP_EOL;
    }

    if (!$quiet) {
        echo 'Removing financial operations... ';
    }
    $count = $DB->Execute(
        'DELETE FROM cash
        WHERE cash.time < ?',
        array(
            $time,
        )
    );

    if (!$quiet) {
        echo (empty($count) ? '0' : $count) . ' operation(s) removed. ' . PHP_EOL;
    }

    if ($test) {
        $DB->RollbackTrans();
    } else {
        $DB->CommitTrans();
    }
}

if (isset($resources['customers'])) {
    if (!$quiet) {
        echo PHP_EOL;
        echo '###################' . PHP_EOL;
        echo 'Customer resources' . PHP_EOL;
        echo '###################' . PHP_EOL;
    }

    $customers = $DB->GetAll(
        'SELECT
            c.id
        FROM customers c
        WHERE
            NOT EXISTS (
                SELECT 1
                FROM documents d
                WHERE d.customerid = c.id
                    AND d.cdate > ?
            )'
            . (isset($balance_limit)
                ? ' AND (EXISTS (
                    SELECT 1
                    FROM customerbalances b
                    WHERE b.customerid = c.id
                        AND b.balance >= ' . $balance_limit . '
                ) OR NOT EXISTS (
                    SELECT 1
                    FROM customerbalances b
                    WHERE b.customerid = c.id
                ))'
                : ''),
        array(
            $time,
        )
    );

    $DB->BeginTrans();

    if (!$quiet) {
        echo 'Permanently deleting customer(s)... ';
    }

    $count = 0;
    if (!empty($customers)) {
        foreach ($customers as $customer) {
            $result = $LMS->deleteCustomerPermanent($customer['id'], false);
            if (!empty($result)) {
                $count++;
            }
        }
    }

    if (!$quiet) {
        echo $count . ' customer(s) deleted. ' . PHP_EOL;
    }

    if ($test) {
        $DB->RollbackTrans();
    } else {
        $DB->CommitTrans();
    }
}
