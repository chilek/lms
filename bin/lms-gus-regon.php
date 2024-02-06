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
    'dry-run' => 'd',
    'customerid:' => null,
    'customergroups:' => 'g:',
    'properties:' => 'p:',
    'type:' => 't:',
);

$script_help = <<<EOF
-d, --dry-run                   dont change customer properties;
    --customerid=<id>           limit process to single customer with specified id;
-g, --customergroups=<group1,group2,...>
                                allow to specify customer groups to which customers should belong to;
-p, --properties=<name,address,ten,regon,rbe>
                                selects which customer properties are updated;
-t, --type=<ten,regon,rbe>
                                which customer register property is used for api lookup ('ten' is default value);
EOF;

require_once('script-options.php');

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
                JOIN vcustomerassignments ON vcustomerassignments.customergroupid = customergroups.id
                WHERE vcustomerassignments.customerid = c.id
                AND UPPER(customergroups.name) IN ('" . implode("', '", $customergroup_ANDs_regular) . "')
                HAVING COUNT(*) = " . count($customergroup_ANDs_regular) . ')')
            . (empty($customergroup_ANDs_inversed) ? '' : " AND NOT EXISTS (SELECT COUNT(*) FROM customergroups
                JOIN vcustomerassignments ON vcustomerassignments.customergroupid = customergroups.id
                WHERE vcustomerassignments.customerid = c.id
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
    "SELECT c.id, c.lastname, c.name, " . $DB->Concat('c.lastname', "' '", 'c.name') . " AS customername,
        c.ten, c.regon, c.rbe, c.rbename, c.countryid, c.ccode, c.divisionid,
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
        if (count($result) > 1) {
            if (!$quiet) {
                echo 'Ambigous data in REGON database!' . PHP_EOL;
            }
            continue;
        }

        $result = reset($result);

        if (!$quiet) {
            echo 'found in GUS Regon database!';
        }
    }

    if ($dry_run) {
        echo PHP_EOL;
    } else {
        $args = array();
        if (isset($properties['name'])) {
            if ($customer['lastname'] != $result['lastname']) {
                $args['lastname'] = $result['lastname'];
            }
            if ($customer['name'] != $result['name']) {
                $args['name'] = $result['name'];
            }
        }
        if (isset($properties['ten']) && $customer['ten'] != $result['ten']) {
            $args['ten'] = $result['ten'];
        }
        if (isset($properties['regon']) && $customer['regon'] != $result['regon']) {
            $args['regon'] = $result['regon'];
        }
        if (isset($properties['rbe'])) {
            if ($customer['rbename'] != $result['rbename']) {
                $args['rbename'] = empty($result['rbename']) ? '' : $result['rbename'];
            }
            if ($customer['rbe'] != $result['rbe']) {
                $args['rbe'] = empty($result['rbe']) ? '' : $result['rbe'];
            }
        }
        if (!empty($args)) {
            $DB->Execute(
                'UPDATE customers SET ' . implode(' = ?, ', array_keys($args)) . ' = ? WHERE id = ?',
                array_merge($args, array($customer['id']))
            );
        }

        if (isset($properties['address'])) {
            $current_address = $LMS->getAddress($customer['address_id']);
            $address = reset($result['addresses']);
            if ($address['location_state_name'] != $current_address['state_name']
                || $address['location_city_name'] != $current_address['city_name']
                || $address['location_street_name'] != $current_address['street']
                || $address['location_house'] != $current_address['house']
                || $address['location_flat'] != $current_address['flat']
                || $address['location_zip'] != $current_address['zip']
                || $address['location_postoffice'] != $current_address['postoffice']
                || $address['location_state'] != $current_address['state_id']
                || $address['location_city'] != $current_address['city_id']
                || $address['location_street'] != $current_address['street_id']) {
                $address['teryt'] = !empty($address['location_city']);
                $address['address_id'] = $customer['address_id'];
                $LMS->UpdateAddress(
                    $customer['id'],
                    $address
                );
                $args['addresses'] = $address;
            }
        }

        if (!$quiet) {
            if (empty($args)) {
                echo ' No updated properties!' . PHP_EOL;
            } else {
                echo ' Updated properties: ' . implode(',', array_keys($args)) . PHP_EOL;
            }
        }
    }
}
