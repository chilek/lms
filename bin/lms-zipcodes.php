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
    'providers:' => 'p:',
    'sources:' => 's:',
    'debug' => 'd',
    'force' => 'f',
);

$script_help = <<<EOF
-p, --providers=<prg,osm,pna>
-s, --sources=<prg,osm,pna>  use PRG, OpenStreetMap oraz local PNA database
                                to determine ZIP codes (in specified order)
-d, --debug                     only try to determine ZIP codes without updating database
-f, --force                     force update ZIP codes even if they are non-empty;
EOF;

require_once('script-options.php');

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes

$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

$debug = isset($options['debug']);
$force = isset($options['force']);

$providers = array();
if (isset($options['providers'])) {
    $providers = explode(',', $options['providers']);
} elseif (isset($options['sources'])) {
    $providers = explode(',', $options['sources']);
}
if (empty($providers)) {
    $providers = strtolower(ConfigHelper::getConfig('phpui.zipcode_backend', 'osm'));
    $providers = preg_split('/([\s]+|[\s]*[;,|][\s]*)/', $providers, -1, PREG_SPLIT_NO_EMPTY);
}
$providers = array_filter(
    $providers,
    function ($provider) {
        static $all_providers = array(
            'pna' => true,
            'osm' => true,
            'prg' => true,
        );
        return isset($all_providers[$provider]);
    }
);
if (empty($providers)) {
    $providers = array('osm');
}

$addresses = $DB->GetAll(
    'SELECT
        a.id,
        (CASE WHEN ca.customer_id IS NOT NULL THEN ca.customer_id ELSE (CASE WHEN nd.id IS NOT NULL THEN nd.id ELSE nn.id END) END) AS resourceid,
        (CASE WHEN ca.customer_id IS NOT NULL THEN 1 ELSE (CASE WHEN nd.id IS NOT NULL THEN 2 ELSE 3 END) END) AS type,
        a.state,
        a.state_id,
        a.city,
        a.city_id,
        a.street,
        a.street_id,
        a.country_id,
        c.name AS country,
        a.house,
        a.flat,
        a.postoffice,
        a.location
    FROM vaddresses a
    LEFT JOIN customer_addresses ca ON ca.address_id = a.id
    LEFT JOIN netdevices nd ON nd.address_id = a.id AND nd.ownerid IS NULL
    LEFT JOIN netnodes nn ON nn.address_id = a.id AND nn.ownerid IS NULL
    LEFT JOIN countries c ON c.id = a.country_id
    WHERE (ca.address_id IS NOT NULL OR nd.address_id IS NOT NULL OR nn.address_id IS NOT NULL)
        ' . ($force ? '' : ' AND a.zip IS NULL')
    . ' ORDER BY (CASE WHEN ca.customer_id IS NOT NULL THEN 1 ELSE (CASE WHEN nd.id IS NOT NULL THEN 2 ELSE 3 END) END),
        (CASE WHEN ca.customer_id IS NOT NULL THEN ca.customer_id ELSE (CASE WHEN nd.id IS NOT NULL THEN nd.id ELSE nn.id END) END),
        a.id'
);

$resourceTypeNames = [
    1 => 'Customer',
    2 => 'Network device',
    3 => 'Network node',
];

foreach ($addresses as $address) {
    foreach ($providers as $provider) {
        $params = array();

        switch ($provider) {
            case 'osm':
                if (empty($address['house']) || empty($address['city'])) {
                    break;
                }

                $street = '';
                if (!empty($address['street'])) {
                    $street = preg_replace(
                        '/^((ul|al|pl|bulw|os|wyb)\.|rondo|park|szosa|inne|skwer|rynek|droga|ogród|wyspa|bulwar)\s+/',
                        '',
                        $address['street']
                    );
                }

                if (!empty($address['city'])) {
                    $params['city'] = $address['city'];
                    if (!empty($street)) {
                        $params['street'] = $street;
                    } else {
                        break;
                    }
                }

                if (!empty($street) && !empty($address['house'])) {
                    $params['street'] = $street . ' ' . $address['house'];
                }

                //$params['format'] = 'json';
                $params['format'] = 'jsonv2';
                $params['addressdetails'] = 1;

                if (!empty($address['country_id'])) {
                    $params['country'] = $address['country'];
                }

                if (!isset($curl)) {
                    if (!function_exists('curl_init')) {
                        die(trans('Curl extension not loaded!') . PHP_EOL);
                    }
                    $curl = curl_init();
                }

                $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query($params);

                curl_setopt_array(
                    $curl,
                    [
                        CURLOPT_URL            => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_CONNECTTIMEOUT => 10,
                        CURLOPT_TIMEOUT        => 20,
                        CURLOPT_SSL_VERIFYPEER => true,
                        CURLOPT_HTTPHEADER     => [
                            'Accept: application/json',
                            //'Accept-Language: ' . $acceptLanguage,
                            // Bardzo ważne: własny UA z kontaktem (Nominatim policy)
                            'User-Agent: MyCompany-PostalCodeLookup/1.0 (admin@example.com)',
                        ],
                    ]
                );

                $result = curl_exec($curl);
                if (curl_error($curl)) {
                    if (!$quiet) {
                        echo $provider . ': '
                            . $resourceTypeNames[$address['type']]
                            . ' #' . $address['resourceid']
                            . ', Address #' . $address['id']
                            . ' - ERROR - Building: ' . $address['location']
                            . ', cURL error: ' . curl_error($curl) . PHP_EOL;
                    }

                    //sleep(1);

                    break;
                }

                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);

                if ($httpCode !== 200) {
                    if (!$quiet) {
                        echo $provider . ': '
                            . $resourceTypeNames[$address['type']]
                            . ' #' . $address['resourceid']
                             . ', Address #' . $address['id']
                            . ' - ERROR - Building: ' . $address['location']
                            . ', HTTP error: ' . $httpCode . PHP_EOL;
                    }

                    //sleep(1);

                    break;
                }

                $result = json_decode($result, true);
                if (empty($result)) {
                    if (!$quiet) {
                        echo $provider . ': '
                            . $resourceTypeNames[$address['type']]
                            . ' #' . $address['resourceid']
                            . ', Address #' . $address['id']
                            . ' - ERROR - Building: ' . $address['location'] . PHP_EOL;
                    }

                    //sleep(1);

                    break;
                }

                if (count($result) > 1) {
                    if (!$quiet) {
                        echo $provider . ': '
                            . $resourceTypeNames[$address['type']]
                            . ' #' . $address['resourceid']
                            . ', Address #' . $address['id']
                            . ' - ERROR - Building: ' . $address['location'] . PHP_EOL;
                    }

                    //sleep(1);

                    break;
                }

                $result = reset($result);

                if (!empty($result['address']) && !empty($result['address']['postcode'])) {
                    $zipCode = $result['address']['postcode'];

                    if (!$debug) {
                        $DB->Execute(
                            'UPDATE addresses SET zip = ? WHERE id = ?',
                            array(
                                $zipCode,
                                $address['id'],
                            )
                        );
                    }

                    if (!$quiet) {
                        echo $provider . ': '
                            . $resourceTypeNames[$address['type']]
                            . ' #' . $address['resourceid']
                            . ', Address #' . $address['id']
                            . ' - OK - Building: ' . $address['location'] . ' (ZIP: ' . $zipCode . ')' . PHP_EOL;
                    }

                    //sleep(1);

                    break 2;
                }

                //sleep(1);

                break;

            case 'prg':
            case 'pna':
                if (!empty($address['country_id']) && $LMS->GetCountryName($address['country_id']) != 'Poland') {
                    break;
                }

                if (empty($address['house']) || (empty($address['city']) && empty($address['city_id']))) {
                    break;
                }

                if (!empty($address['city_id'])) {
                    $params['cityid'] = $address['city_id'];
                    if (!empty($address['street_id'])) {
                        $params['streetid'] = $address['street_id'];
                    }
                } elseif (!empty($address['city'])) {
                    $params['city'] = $address['city'];
                    if (!empty($address['street'])) {
                        $params['street'] = $address['street'];
                    } else {
                        break;
                    }
                }

                $params['house'] = $address['house'];
                $params['provider'] = $provider;

                $zipCode = $LMS->GetZipCode($params);

                if (empty($zipCode)) {
                    if (!$quiet) {
                        echo $provider . ': '
                            . $resourceTypeNames[$address['type']]
                            . ' #' . $address['resourceid']
                            . ', Address #' . $address['id']
                            . ' - ERROR - Building: ' . $address['location'] . PHP_EOL;
                    }
                    break;
                }

                if (!$debug) {
                    $DB->Execute(
                        'UPDATE addresses SET zip = ? WHERE id = ?',
                        array(
                            $zipCode,
                            $address['id'],
                        )
                    );
                }

                if (!$quiet) {
                    echo $provider . ': '
                        . $resourceTypeNames[$address['type']]
                        . ' #' . $address['resourceid']
                        . ', Address #' . $address['id']
                        . ' - OK - Building: ' . $address['location'] . ' (ZIP: ' . $zipCode . ')' . PHP_EOL;
                }

                break 2;
        }
    }
}
