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
    'dry-run' => 'd',
    'customerid:' => null,
    'customergroups:' => 'g:',
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
lms-vies.php
(C) 2001-2020 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
lms-vies.php
(C) 2001-2020 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;
-d, --dry-run                   dont change customer 'VAT payer' flag;
    --customerid=<id>           limit assignments to to specifed customer
-g, --customergroups=<group1,group2,...>
                                allow to specify customer groups to which notified customers
                                should be assigned

EOF;
    exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
    print <<<EOF
lms-vies.php
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

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);

// Load autoloader
$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/" . PHP_EOL);
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

$dry_run = array_key_exists('dry-run', $options);

$customerid = isset($options['customerid']) && intval($options['customerid']) ? $options['customerid'] : null;

// prepare customergroups in sql query
if (isset($options['customergroups'])) {
    $customergroups = $options['customergroups'];
}
if (!empty($customergroups)) {
    $ORs = preg_split("/([\s]+|[\s]*,[\s]*)/", mb_strtoupper($customergroups), -1, PREG_SPLIT_NO_EMPTY);
    $customergroup_ORs = array();
    foreach ($ORs as $OR) {
        $ANDs = preg_split("/([\s]*\+[\s]*)/", $OR, -1, PREG_SPLIT_NO_EMPTY);
        $customergroup_ANDs_regular = array();
        $customergroup_ANDs_inversed = array();
        foreach ($ANDs as $AND) {
            if (strpos($AND, '!') === false) {
                $customergroup_ANDs_regular[] = $AND;
            } else {
                $customergroup_ANDs_inversed[] = substr($AND, 1);
            }
        }
        $customergroup_ORs[] = '('
            . (empty($customergroup_ANDs_regular) ? '1 = 1' : "EXISTS (SELECT COUNT(*) FROM customergroups
                JOIN customerassignments ON customerassignments.customergroupid = customergroups.id
                WHERE customerassignments.customerid = c.id
                AND UPPER(customergroups.name) IN ('" . implode("', '", $customergroup_ANDs_regular) . "')
                HAVING COUNT(*) = " . count($customergroup_ANDs_regular) . ')')
            . (empty($customergroup_ANDs_inversed) ? '' : " AND NOT EXISTS (SELECT COUNT(*) FROM customergroups
                JOIN customerassignments ON customerassignments.customergroupid = customergroups.id
                WHERE customerassignments.customerid = c.id
                AND UPPER(customergroups.name) IN ('" . implode("', '", $customergroup_ANDs_inversed) . "')
                HAVING COUNT(*) > 0)")
            . ')';
    }
    $customergroups = ' AND (' . implode(' OR ', $customergroup_ORs) . ')';
}

$customers = $DB->GetAll(
    "SELECT c.id, " . $DB->Concat('c.lastname', "' '", 'c.name') . " AS customername,
        c.ten, c.countryid, c.ccode, c.divisionid, c.flags,
        (CASE WHEN d.label IS NULL THEN d.shortname ELSE d.label END) AS div_name,
        d.ten AS div_ten, d.countryid AS div_countryid, d.ccode AS div_ccode
    FROM customeraddressview c
    JOIN vdivisions d ON d.id = c.divisionid
    WHERE d.ten <> ? AND c.ten <> ? AND c.status IN ?"
    . ($customerid ? ' AND c.id = ' . $customerid : '')
    . ($customergroups ?: $customergroups)
    . " ORDER BY c.id",
    array(
        '',
        '',
        array(CSTATUS_CONNECTED, CSTATUS_DEBT_COLLECTION)
    )
);

if (empty($customers)) {
    die('No customers found to check their VAT payer status in VIES database!' . PHP_EOL);
}

foreach ($customers as $customer) {
    $div_ccode = empty($customer['div_ccode']) ? Localisation::getCurrentViesCode() : Localisation::getViesCodeByCountryCode($customer['div_ccode']);
    $customer_ccode = empty($customer['ccode']) ? $div_ccode : Localisation::getViesCodeByCountryCode($customer['ccode']);
    $customername = trim($customer['customername']);

    if (empty($div_ccode) || empty($customer_ccode)) {
        if (!$quiet) {
            if (empty($div_ccode)) {
                printf(
                    "Division '%s (#%04d)' country code '%s' is not supported!" . PHP_EOL,
                    $customer['div_name'],
                    $customer['divisionid'],
                    $customer['div_ccode']
                );
            }
            if (empty($customer_ccode)) {
                printf(
                    "Customer '%s (#%04d)' country code '%s' is not supported!" . PHP_EOL,
                    $customername,
                    $customer['id'],
                    $customer['ccode']
                );
            }
        }
        continue;
    }

    $div_ten = preg_replace('/[ -]/', '', $customer['div_ten']);
    $customer_ten = preg_replace('/[ -]/', '', $customer['ten']);

    try {
        $valid = Utils::validateVat($customer_ccode, $customer_ten, $div_ccode, $div_ten);
    } catch (Exception $e) {
        die($e->getMessage() . PHP_EOL);
    }

    if ($valid) {
        if (!$quiet) {
            printf(
                "Customer '%s (#%04d)' with VAT reg. no. '%s' is active VAT payer!" . PHP_EOL,
                $customername,
                $customer['id'],
                $customer_ccode . $customer_ten
            );
        }

        if (!($customer['flags'] & CUSTOMER_FLAG_VAT_PAYER)) {
            printf(
                "Customer '%s (#%04d)' with VAT reg. no. '%s' is VAT payer, but corresponding flag is NOT SET!" . PHP_EOL,
                $customername,
                $customer['id'],
                $customer_ccode . $customer_ten
            );

            if (!$dry_run) {
                $DB->Execute(
                    'UPDATE customers SET flags = flags | ? WHERE id = ?',
                    array(
                        CUSTOMER_FLAG_VAT_PAYER,
                        $customer['id'],
                    )
                );
            }
        }
    } else {
        if (!$quiet) {
            printf(
                "Customer '%s (#%04d)' with VAT reg. no. '%s' is NOT active VAT payer!" . PHP_EOL,
                $customername,
                $customer['id'],
                $customer_ccode . $customer_ten
            );
        }

        if ($customer['flags'] & CUSTOMER_FLAG_VAT_PAYER) {
            printf(
                "Customer '%s (#%04d)' with VAT reg. no. '%s' is NOT VAT payer, but corresponding flag is SET!" . PHP_EOL,
                $customername,
                $customer['id'],
                $customer_ccode . $customer_ten
            );

            if (!$dry_run) {
                $DB->Execute(
                    'UPDATE customers SET flags = flags & ? WHERE id = ?',
                    array(
                        ~CUSTOMER_FLAG_VAT_PAYER,
                        $customer['id'],
                    )
                );
            }
        }
    }
}
