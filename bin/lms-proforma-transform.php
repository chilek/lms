#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

// REPLACE THIS WITH PATH TO YOUR CONFIG FILE

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************

ini_set('error_reporting', E_ALL&~E_NOTICE);

$parameters = array(
    'config-file:' => 'C:',
    'quiet' => 'q',
    'help' => 'h',
    'version' => 'v',
);

$long_to_shorts = array();
foreach ($parameters as $long => $short) {
    $long = str_replace(':', '', $long);
    if (isset($short)) {
        $short = str_replace(':', '', $short);
    }
    $long_to_shorts[$long] = $short;
}

$options = getopt(
    implode(
        '',
        array_filter(
            array_values($parameters),
            function ($value) {
                return isset($value);
            }
        )
    ),
    array_keys($parameters)
);

foreach (array_flip(array_filter($long_to_shorts, function ($value) {
    return isset($value);
})) as $short => $long) {
    if (array_key_exists($short, $options)) {
        $options[$long] = $options[$short];
        unset($options[$short]);
    }
}

if (array_key_exists('version', $options)) {
    print <<<EOF
lms-proforma-transform.php
(C) 2001-2020 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
lms-proforma-transform.php
(C) 2001-2020 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;

EOF;
    exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
    print <<<EOF
lms-proforma-transform.php
(C) 2001-2020 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options)) {
    $CONFIG_FILE = $options['config-file'];
} else {
    $CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';
}

if (!$quiet) {
    echo "Using file ".$CONFIG_FILE." as config." . PHP_EOL;
}

if (!is_readable($CONFIG_FILE)) {
    die('Unable to read configuration file ['.$CONFIG_FILE.']!');
}

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['plugin_dir'] = (!isset($CONFIG['directories']['plugin_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'plugins' : $CONFIG['directories']['plugin_dir']);
$CONFIG['directories']['plugins_dir'] = $CONFIG['directories']['plugin_dir'];

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('PLUGIN_DIR', $CONFIG['directories']['plugin_dir']);
define('PLUGINS_DIR', $CONFIG['directories']['plugin_dir']);

// Load autoloader
$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/");
}

// Init database

$DB = null;

try {
    $DB = LMSDB::getInstance();
} catch (Exception $ex) {
    trigger_error($ex->getMessage(), E_USER_WARNING);
    // can't work without database
    die("Fatal error: cannot connect to database!" . PHP_EOL);
}

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes

$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

$plugin_manager = new LMSPluginManager();
$LMS->setPluginManager($plugin_manager);

// prepare customergroups in sql query
$customergroups = " AND EXISTS (SELECT 1 FROM customergroups g, customerassignments ca
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
    'SELECT c.id, ROUND(SUM(c.balance), 2) AS balance FROM
        (
            (
                SELECT customerid AS id, balance AS balance
                FROM customerbalances
            ) UNION (
                SELECT d.customerid AS id, -SUM(ic.count * ic.value * d.currencyvalue) AS balance
                FROM documents d
                JOIN invoicecontents ic ON ic.docid = d.id
                WHERE d.type = ? AND d.cancelled = 0'
                    . (ConfigHelper::checkConfig('phpui.proforma_invoice_generates_commitment') ? 'AND 1=0' : '') . '
                GROUP BY d.customerid
            )
        ) c
        WHERE c.id IN (SELECT DISTINCT customerid FROM documents WHERE type = ? AND closed = 0)'
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
                    documents.archived, ROUND(SUM(cash.value * cash.currencyvalue), 2) AS value
                FROM cash
                LEFT JOIN documents ON documents.id = docid
                WHERE cash.customerid = ? AND cash.value <> 0 AND (cash.value < 0 OR documents.type = ?)
                GROUP BY cash.time, cash.docid, documents.type, documents.closed, documents.archived
            ) UNION (
                SELECT d.cdate AS time, d.id AS docid, d.type AS doctype, d.closed,
                    d.archived, ROUND(SUM(-ic.value * ic.count * d.currencyvalue)) AS value
                FROM documents d
                JOIN invoicecontents ic ON ic.docid = d.id
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
