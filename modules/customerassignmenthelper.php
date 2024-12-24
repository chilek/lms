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

$data = array(
    'locations' => array(),
    'nodes' => array(),
    'netdevnodes' => array(),
    'voipaccounts' => array(),
    'addresses' => array(),
    'document-separation-groups' => array(),
);

if (isset($_GET['customerid'])) {
    $locationAddressPreselection = ConfigHelper::checkConfig('documents.location_address_preselection');
    $addressesWithEndPoints = $LMS->GetCustomerAddressesWithEndPoints($_GET['customerid']);
    $addressesWithoutEndPoints = $LMS->GetCustomerAddressesWithoutEndPoints($_GET['customerid']);
    if ($locationAddressPreselection) {
        $allAddresses = array_merge($addressesWithEndPoints, $addressesWithoutEndPoints);
        $LMS->determineDefaultCustomerAddress($allAddresses);
        $defaultAddress = array_filter(
            $allAddresses,
            function ($address) {
                return !empty($address['default_address']);
            }
        );
        $defaultAddress = reset($defaultAddress);
        $defaultAddressId = $defaultAddress['id'];
        if (isset($addressesWithEndPoints[$defaultAddressId])) {
            $addressesWithEndPoints[$defaultAddressId]['default_address'] = true;
        } else {
            $addressesWithoutEndPoints[$defaultAddressId]['default_address'] = true;
        }
    }
    $data['with-end-points'] = $addressesWithEndPoints;
    $data['without-end-points'] = $addressesWithoutEndPoints;
    $data['nodes'] = $LMS->GetCustomerNodes($_GET['customerid']);
    $data['netdevnodes'] = $LMS->getCustomerNetDevNodes($_GET['customerid']);
    $data['voipaccounts'] = $LMS->GetCustomerVoipAccounts($_GET['customerid']);
    $data['addresses'] = $LMS->getCustomerAddresses($_GET['customerid']);
    $data['document-separation-groups'] = $DB->GetCol(
        'SELECT DISTINCT separatedocument
        FROM assignments
        WHERE customerid = ?
            AND separatedocument IS NOT NULL',
        array(
            $_GET['customerid'],
        )
    );
}

header('Content-Type: application/json');
die(json_encode($data));
