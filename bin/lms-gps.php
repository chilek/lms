#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2023 LMS Developers
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

ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_DEPRECATED);

$parameters = array(
    'config-file:' => 'C:',
    'quiet' => 'q',
    'help' => 'h',
    'version' => 'v',
    'update' => 'u',
    'update-netdevices' => 'U',
    'update-netnodes' => 'N',
    'providers:' => 'p:',
    'sources:' => 's:',
    'debug' => 'd',
    'force' => 'f',
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
lms-gps.php
(C) 2001-2023 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
lms-gps.php
(C) 2001-2023 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-u, --update                    update nodes GPS coordinates
-U, --update-netdevices         update netdevices GPS coordinates
-N, --update-netnodes           update netnodes GPS coordinates
-p, --providers=<google,osm,prg>
-s, --sources=<google,osm,prg>  use Google Maps API and/or PRG building location database
                                to determine GPS coordinates (in specified order)
-d, --debug                     only try to determine GPS coordinates without updating database
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;
-f, --force                     force update GPS coordinates even if they are non-empty;

EOF;
    exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
    print <<<EOF
lms-gps.php
(C) 2001-2023 LMS Developers

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
    die("Unable to read configuration file [".$CONFIG_FILE."]!" . PHP_EOL);
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
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More information at https://getcomposer.org/");
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
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes
$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

/*
$_APIKEY = ConfigHelper::getConfig('google.apikey');
if (!$_APIKEY)
    die("Unable to read apikey from configuration file." . PHP_EOL);
*/

$debug = isset($options['debug']);

$types = array();
if (isset($options['update-netdevices'])) {
    $types['Network devices:'] = 'netdevices';
}
if (isset($options['update-netnodes'])) {
    $types['Network nodes:'] = 'netnodes';
}
if (isset($options['update'])) {
    $types['Nodes:'] = 'nodes';
}

function array_provider_filter($provider)
{
    static $all_providers = array(
        'google' => true,
        'osm' => true,
        'siis' => true,
        'prg' => true,
    );
    return isset($all_providers[$provider]);
}

$providers = array();
if (isset($options['providers'])) {
    $providers = explode(',', $options['providers']);
} elseif (isset($options['sources'])) {
    $providers = explode(',', $options['sources']);
}
if (empty($providers)) {
    $providers = trim(ConfigHelper::getConfig('phpui.gps_coordinate_providers', 'google,osm,prg'));
    $providers = preg_split('/([\s]+|[\s]*[,|][\s]*)/', $providers, -1, PREG_SPLIT_NO_EMPTY);
}
$providers = array_filter($providers, 'array_provider_filter');
if (empty($providers)) {
    $providers = array('google');
}

$google_api_key = ConfigHelper::getConfig(
    'google.geocode_api_key',
    ConfigHelper::getConfig('phpui.googlemaps_api_key', '', true),
    true
);

foreach ($types as $label => $type) {
    $locations = $DB->GetAll(
        "SELECT
            t.id, va.location, va.city_id, va.street_id, va.house, ls.name AS state_name,
            ld.name AS district_name, lb.name AS borough_name,
            va.city AS city_name,
            va.zip AS zip,
            c.name AS country_name,
            " . $DB->Concat('(CASE WHEN lst.name2 IS NULL THEN lst.name ELSE ' . $DB->Concat('lst.name2', "' '", 'lst.name') . ' END)') . " AS simple_street_name
        FROM " . $type . " t
        JOIN (
            SELECT n.id,
                COALESCE(n.address_id, MIN(ca2.address_id)) AS address_id
            FROM " . $type . " n
            LEFT JOIN (
                SELECT n2.id,
                    MAX(ca.type) AS address_type
                FROM " . $type . " n2
                JOIN customer_addresses ca ON ca.customer_id = n2.ownerid
                WHERE ca.type IN ?
                    AND n2.address_id IS NULL
                GROUP BY n2.id
            ) at ON n.address_id IS NULL AND at.id = n.id
            LEFT JOIN customer_addresses ca2 ON ca2.customer_id = n.ownerid AND ca2.type = at.address_type
            WHERE n.address_id IS NOT NULL
                OR ca2.address_id IS NOT NULL
            GROUP BY n.id
        ) t2 ON t2.id = t.id
        LEFT JOIN vaddresses va ON va.id = t2.address_id
        LEFT JOIN location_cities lc ON lc.id = va.city_id
        LEFT JOIN location_boroughs lb ON lb.id = lc.boroughid
        LEFT JOIN location_districts ld ON ld.id = lb.districtid
        LEFT JOIN location_states ls ON ls.id = ld.stateid
        LEFT JOIN countries c ON c.id = va.country_id
        LEFT JOIN location_streets lst ON lst.id = va.street_id
        WHERE va.location IS NOT NULL "
            . (isset($options['force']) ? '' : 'AND t.longitude IS NULL AND t.latitude IS NULL') . "
            AND va.location_house IS NOT NULL
            AND va.location <> ''
            AND va.location_house <> ''",
        array(
            array(BILLING_ADDRESS, DEFAULT_LOCATION_ADDRESS),
        )
    );
    if (!empty($locations)) {
        $label_displayed = false;

        foreach ($locations as $row) {
            if (!$quiet && !$label_displayed) {
                echo $label . PHP_EOL;
                $label_displayed = true;
            }

            foreach ($providers as $provider) {
                if ($provider == 'google') {
                    $res = geocode((empty($row['state_name']) ? '' : $row['state_name'] . ', ' . $row['district_name'] . ', ' . $row['borough_name'])
                        . $row['location'] . " Poland");
                    if (($res['status'] == "OK") && ($res['accuracy'] == "ROOFTOP")) {
                        if (!$debug) {
                            $DB->Execute(
                                "UPDATE " . $type . " SET latitude = ?, longitude = ? WHERE id = ?",
                                array(
                                    $res['latitude'],
                                    $res['longitude'],
                                    $row['id'],
                                )
                            );
                        }
                        if (!$quiet) {
                            echo 'google: #' . $row['id'] . " - OK - Building: " . $row['location'] . " - Accuracy: " . $res['accuracy']
                                . " (lat.: " . $res['latitude'] . " long.: " . $res['longitude'] . ")" . PHP_EOL;
                        }
                        sleep(2);
                        break;
                    } else {
                        if (!$quiet) {
                            echo 'google: #' . $row['id'] . " - ERROR - Building: " . $row['location']
                                . " - Status: " . $res['status'] . ' (' . $res['error'] . ')' . PHP_EOL;
                        }
                    }
                    if (empty($google_api_key)) {
                        sleep(2);
                    } else {
                        usleep(50000);
                    }
                } elseif ($provider == 'osm') {
                    $params = array(
                        'city' => $row['city_name'],
                    );
                    if (isset($row['country_name']) && !empty($row['country_name'])) {
                        $params['country'] = $row['country_name'];
                    }
                    if (isset($row['state_name']) && !empty($row['state_name'])) {
                        $params['state'] = $row['state_name'];
                    }
                    if (isset($row['simple_street_name']) && !empty($row['simple_street_name'])) {
                        $params['street'] = (isset($row['house']) && mb_strlen($row['house'])
                                ? $row['house'] . ' '
                                : ''
                            ) . $row['simple_street_name'];
                    }
                    if (isset($row['zip']) && !empty($row['zip'])) {
                        $params['postalcode'] = $row['zip'];
                    }
                    $res = osm_geocode($params);
                    if (empty($res)
                        || isset($res['latitude']) && !strlen($res['latitude'])
                        || isset($res['longitude']) && !strlen($res['longitude'])) {
                        if (!$quiet) {
                            echo 'osm: #' . $row['id'] . " - ERROR - Building: " . $row['location'] . PHP_EOL;
                        }
                        continue;
                    }

                    if (!$debug) {
                        $DB->Execute(
                            "UPDATE " . $type . " SET latitude = ?, longitude = ? WHERE id = ?",
                            array(
                                $res['latitude'],
                                $res['longitude'],
                                $row['id'],
                            )
                        );
                    }
                    if (!$quiet) {
                        echo 'osm: #' . $row['id'] . " - OK - Building: " . $row['location'] . " (lat.: " . $res['latitude'] . " long.: " . $res['longitude'] . ")" . PHP_EOL;
                    }

                    sleep(1);

                    break;
                } elseif (($provider == 'prg' || $provider == 'siis') && !empty($row['state_name'])) {
                    $args = array(
                        'city_id' => $row['city_id'],
                    );
                    if (!empty($row['street_id'])) {
                        $args['street_id'] = $row['street_id'];
                    }
                    if (!empty($row['house'])) {
                        $args['building_num'] = $row['house'];
                    }
                    $coordinates = $LMS->getCoordinatesForAddress($args);
                    if (empty($coordinates)) {
                        if (!$quiet) {
                            echo 'prg: #' . $row['id'] . " - ERROR - Building: " . $row['location'] . PHP_EOL;
                        }
                        continue;
                    }

                    if (!$debug) {
                        $DB->Execute(
                            "UPDATE " . $type . " SET latitude = ?, longitude = ? WHERE id = ?",
                            array(
                                $coordinates['latitude'],
                                $coordinates['longitude'],
                                $row['id'],
                            )
                        );
                    }

                    if (!$quiet) {
                        echo 'prg: #' . $row['id'] . " - OK - Building: " . $row['location'] . " (lat.: " . $coordinates['latitude']
                            . " long.: " . $coordinates['longitude'] . ")" . PHP_EOL;
                    }

                    break;
                }
            }
        }
    }
}
