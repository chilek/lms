#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2021 LMS Developers
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
    'properties:' => 'p:',
    'type:' => 't:',
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
lms-gus-regon.php
(C) 2001-2021 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
lms-gus-regon.php
(C) 2001-2021 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;
-d, --dry-run                   dont change customer propertiesr;
    --customerid=<id>           limit process to single customer with specified id;
-g, --customergroups=<group1,group2,...>
                                allow to specify customer groups to which customers should belong to;
-p, --properties=<name,address,ten,regon,rbe>
                                selects which customer properties are updated;
-t, --type=<ten,regon,rbe>
                                which customer register property is used for api lookup ('ten' is default value);

EOF;
    exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
    print <<<EOF
lms-gus-regon.php
(C) 2001-2021 LMS Developers

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

$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

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

if (isset($options['type'])) {
    if (!in_array($options['type'], array('ten', 'regon', 'rbe'))) {
        die('Unsupported customer identifier type: ' . $options['type'] . '!' . PHP_EOL);
    }
    $type = $options['type'];
} else {
    $type = 'ten';
}

$all_properties = array('name', 'address', 'ten', 'regon', 'rbe');
if (isset($options['properties'])) {
    $properties = array_intersect(explode(',', $options['properties']), $all_properties);
    if (empty($properties)) {
        die('No given customer properties are supported!' . PHP_EOL);
    }
} else {
    $properties = $all_properties;
}
$properties = array_flip($properties);

$customers = $DB->GetAll(
    "SELECT c.id, " . $DB->Concat('c.lastname', "' '", 'c.name') . " AS customername,
        c.ten, c.regon, c.rbe, c.countryid, c.ccode, c.divisionid, c.flags,
        ca.address_id,
        d.ccode AS div_ccode
    FROM customeraddressview c
    JOIN customer_addresses ca ON ca.customer_id = c.id AND ca.type = ?
    JOIN vdivisions d ON d.id = c.divisionid
    WHERE c.type = ? AND c." . $type . " <> ? AND c.status IN ?"
    . ($customerid ? ' AND c.id = ' . $customerid : '')
    . ($customergroups ?: $customergroups)
    . " ORDER BY c.id",
    array(
        BILLING_ADDRESS,
        CTYPES_COMPANY,
        '',
        array(CSTATUS_CONNECTED, CSTATUS_DEBT_COLLECTION)
    )
);

if (empty($customers)) {
    die('No customers found to update their properties using GUS Regon database!' . PHP_EOL);
}

foreach ($customers as $customer) {
    $div_ccode = empty($customer['div_ccode']) ? Localisation::getCurrentViesCode() : Localisation::getViesCodeByCountryCode($customer['div_ccode']);
    $customer_ccode = empty($customer['ccode']) ? $div_ccode : Localisation::getViesCodeByCountryCode($customer['ccode']);
    $customername = trim($customer['customername']);

    if ($customer_ccode != 'PL') {
        if (!$quiet && !empty($customer_ccode)) {
            printf(
                "(#04d) %s: country code '%s' is not supported!" . PHP_EOL,
                $customer['id'],
                $customername,
                $customer['ccode']
            );
        }
        continue;
    }

    switch ($type) {
        case 'ten':
            $result = Utils::getGusRegonData(Utils::GUS_REGON_API_SEARCH_TYPE_TEN, $customer['ten']);
            break;
        case 'regon':
            $result = Utils::getGusRegonData(Utils::GUS_REGON_API_SEARCH_TYPE_REGON, $customer['regon']);
            break;
        case 'rbe':
            $result = Utils::getGusRegonData(Utils::GUS_REGON_API_SEARCH_TYPE_RBE, $customer['rbe']);
            break;
    }

    if (!$quiet) {
        printf('(#%04d) %s: ', $customer['id'], $customername);
    }

    if (is_int($result)) {
        switch ($result) {
            case Utils::GUS_REGON_API_RESULT_BAD_KEY:
                die('Bad REGON API user key!' . PHP_EOL);
            case Utils::GUS_REGON_API_RESULT_NO_DATA:
                if (!$quiet) {
                    echo 'No data found in REGON database!' . PHP_EOL;
                }
                break;
            case Utils::GUS_REGON_API_RESULT_AMBIGUOUS:
                if (!$quiet) {
                    echo 'Ambigous data in REGON database!' . PHP_EOL;
                }
                break;
        }
        continue;
    } elseif (is_string($result)) {
        die($result . PHP_EOL);
    } else {
        if (!$quiet) {
            echo 'found in GUS Regon database!';
        }
    }

    if ($dry_run) {
        echo PHP_EOL;
    } else {
        $args = array();
        if (isset($properties['name'])) {
            $args['lastname'] = $result['lastname'];
            $args['name'] = $result['name'];
        }
        if (isset($properties['ten'])) {
            $args['ten'] = $result['ten'];
        }
        if (isset($properties['regon'])) {
            $args['regon'] = $result['regon'];
        }
        if (isset($properties['rbe'])) {
            $args['rbename'] = empty($result['rbename']) ? '' : $result['rbename'];
            $args['rbe'] = empty($result['rbe']) ? '' : $result['rbe'];
        }
        $DB->Execute(
            'UPDATE customers SET ' . implode(' = ?, ', array_keys($args)) . ' = ? WHERE id = ?',
            array_merge($args, array($customer['id']))
        );

        if (isset($properties['address'])) {
            $address = reset($result['addresses']);
            $address['teryt'] = !empty($address['location_city']);
            $address['address_id'] = $customer['address_id'];
            $LMS->UpdateAddress(
                $customer['id'],
                $address
            );
        }

        if (!$quiet) {
            echo ' Updated in local database!' . PHP_EOL;
        }
    }
}
