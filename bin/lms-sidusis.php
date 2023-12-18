#!/usr/bin/env php
<?php

/*
 * LMS version 28.x
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
 *  Author: Jarosław Kłopotek github:@interduo,
 */

ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_DEPRECATED);

$parameters = array(
    'config-file:' => 'C:',
    'quiet' => 'q',
    'help' => 'h',
    'version' => 'v',
    'export-ranges' => 'e',
    'replace-ranges' => 'r',
    'debug' => 'd',
    'import-demands' => 'i',
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
lms-sidusis.php
(C) 2001-2023 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
lms-sidusis.php
(C) 2001-2022 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;
-d, --debug                     print all possible output;
-e, --export-ranges		export sidusis ranges to internet.gov.pl;
-r, --replace-ranges		replace all ranges in internet.gov.pl instead of sending incremental report;
-i, --import-demands		import demands for internet - reported with internet.gov.pl sidusis portal,

EOF;
    exit(0);
}

$debug = array_key_exists('debug', $options);
$quiet = array_key_exists('quiet', $options);

if (!$quiet) {
    print <<<EOF
lms-sidusis.php
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
$CONFIG['directories']['mod_dir'] = (!isset($CONFIG['directories']['mod_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'modules' : $CONFIG['directories']['mod_dir']);
$CONFIG['directories']['doc_dir'] = (!isset($CONFIG['directories']['doc_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'documents' : $CONFIG['directories']['doc_dir']);
$CONFIG['directories']['smarty_compile_dir'] = (!isset($CONFIG['directories']['smarty_compile_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'templates_c' : $CONFIG['directories']['smarty_compile_dir']);
$CONFIG['directories']['smarty_templates_dir'] = (!isset($CONFIG['directories']['smarty_templates_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'templates' : $CONFIG['directories']['smarty_templates_dir']);
$CONFIG['directories']['plugin_dir'] = (!isset($CONFIG['directories']['plugin_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'plugins' : $CONFIG['directories']['plugin_dir']);
$CONFIG['directories']['plugins_dir'] = $CONFIG['directories']['plugin_dir'];

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('DOC_DIR', $CONFIG['directories']['doc_dir']);
define('STORAGE_DIR', $CONFIG['directories']['storage_dir']);
define('SMARTY_COMPILE_DIR', $CONFIG['directories']['smarty_compile_dir']);
define('SMARTY_TEMPLATES_DIR', $CONFIG['directories']['smarty_templates_dir']);
define('PLUGIN_DIR', $CONFIG['directories']['plugin_dir']);
define('PLUGINS_DIR', $CONFIG['directories']['plugin_dir']);

//define('K_TCPDF_EXTERNAL_CONFIG', true);

// Load autoloader
$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More information at https://getcomposer.org/" . PHP_EOL);
}

// Init database
$DB = null;

try {
    $DB = LMSDB::getInstance();
} catch (Exception $ex) {
    trigger_error($ex->getMessage(), E_USER_WARNING);
    // can't working without database
    die("Fatal error: cannot connect to database!" . PHP_EOL);
}

if (!class_exists('ZipArchive')) {
    die('Error: ZipArchive class not found! Install php-zip module.');
}

// Initialize templates engine (must be before locale settings)
// $SMARTY = new LMSSmarty;

// add LMS's custom plugins directory
// $SMARTY->addPluginsDir(LIB_DIR . DIRECTORY_SEPARATOR . 'SmartyPlugins');
// $SMARTY->muteUndefinedOrNullWarnings();

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

$lmsUrl = ConfigHelper::getConfig('rt.lms_url', 'http://localhost/lms');
$apiAuthToken = ConfigHelper::getConfig('sidusis.api_token');
$apiHost = ConfigHelper::getConfig('sidusis.api_host', 'https://internet.gov.pl');
$sidusisMapUrl = ConfigHelper::getConfig('sidusis.map_url', 'https://internet.gov.pl/map/?center=');
$sidusisZoomFactor = ConfigHelper::getConfig('sidusis.zoom_factor', 20);

$apiEndPoints = array(
    'export-ranges' => '/api/import_network_ranges/',
    'import-demands' => '/api/demand_notifications/?page_size=all&status=new/',
    'mark-demand-as-read' => '/api/demand_notifications/%id/read/',
);

$sidusisSelectedDivision = ConfigHelper::getConfig('sidusis.selected_division', 1);
$sidusisTemporaryFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lms-sidusis-' . time() . '.zip';
$sidusisOperatorOfferUrl = ConfigHelper::getConfig('uke.sidusis_operator_offer_url', ConfigHelper::getConfig('sidusis.operator_offer_url', 'http://firma.pl/offer/'));
$sidusisOperatorPhone = ConfigHelper::getConfig('uke.sidusis_operator_phone', ConfigHelper::getConfig('sidusis.operator_project_phone', ''));

$internet_demands_queueid = ConfigHelper::getConfig('sidusis.internet_demands_queueid', 1);
$internet_demands_categoryids = ConfigHelper::getConfig('sidusis.internet_demands_categoryids');

$did = in_array('division', $options) ? intval($options['division']) : $sidusisSelectedDivision;

function getSidusisRangesReport($divisionid)
{
    global $LMS, $DB, $SIDUSIS_LINKTECHNOLOGIES, $sidusisOperatorOfferUrl, $sidusisOperatorPhone;

    $division = $LMS->GetDivision($divisionid) ?? die();

    if (preg_match('/^\+/', $division['phone'])) {
        $phone = '+';
        $division['phone'] = substr($division['phone'], 1);
    } else {
        $phone = '';
    }
    $phone .= preg_replace('/[^0-9+]/', '', $division['phone']);
    if (!strlen($division['email']) && !strlen($phone)) {
        die('Division email or phone should be not empty at least!');
    }

    $buildings = $DB->GetAll(
        'SELECT b.*,
            r.id AS netrangeid,
            r.linktype,
            r.linktechnology,
            r.downlink,
            r.uplink,
            r.type,
            r.services,
            r.invprojectid,
            p.name AS invprojectname,
            p.cdate AS invprojectcdate,
            ls.ident AS state_ident,
            ld.ident AS district_ident,
            lb.ident AS borough_ident,
            lb.type AS borough_type,
            lc.name AS city_name,
            lc.ident AS city_ident,
            lst.name AS street_name,
            lst.name2 AS street_name2,
            (CASE WHEN lst.name2 IS NOT NULL THEN ' . $DB->Concat('lst.name', "' '", 'lst.name2') . ' ELSE lst.name END) AS street_label,
            (CASE WHEN lst.name2 IS NOT NULL THEN ' . $DB->Concat('lst.name2', "' '", 'lst.name') . ' ELSE lst.name END) AS street_rlabel,
            t.name AS street_typename,
            lst.ident AS street_ident
        FROM location_buildings b
            JOIN netranges r ON r.buildingid = b.id
            LEFT JOIN invprojects p ON p.id = r.invprojectid
            JOIN location_cities lc ON lc.id = b.city_id
            JOIN location_boroughs lb ON lb.id = lc.boroughid
            JOIN location_districts ld ON ld.id = lb.districtid
            JOIN location_states ls ON ls.id = ld.stateid
            LEFT JOIN location_streets lst ON lst.id = b.street_id
            LEFT JOIN location_street_types t ON t.id = lst.typeid
        WHERE '
        . (empty($projects) ? 'r.invprojectid IS NULL' : 'r.invprojectid IN (' . implode(',', $projects) . ')')
    );

    if (empty($buildings)) {
        die('Network range database is empty!');
    }

    define('OPERATOR_REPRESENTATIVE_ID', 1);

    $fh = fopen('php://temp', 'r+');

    //division data
    fputcsv(
        $fh,
        array(
            'DI',
            $division['name'],
            $division['telecomnumber'],
            '',
            preg_replace('/[^0-9]/', '', $division['ten']),
        )
    );

    fputcsv(
        $fh,
        array(
            'PO',
            OPERATOR_REPRESENTATIVE_ID,
            $division['email'],
            $sidusisOperatorPhone,
            $sidusisOperatorOfferUrl
        )
    );

    foreach ($buildings as $building) {
        switch ($building['linktype']) {
            case LINKTYPE_WIRE:
                if ($building['linktechnology'] >= 50 && $building['linktechnology'] < 100) {
                    $linktype = 'kablowe współosiowe miedziane';
                } else {
                    $linktype = 'kablowe parowe miedziane';
                }
                break;
            case LINKTYPE_WIRELESS:
                $linktype = 'radiowe (FWA)';
                break;
            case LINKTYPE_FIBER:
                $linktype = 'światłowodowe';
                break;
        }

        //print ranges
        fputcsv(
            $fh,
            array(
                'ZS',
                $building['netrangeid'],
                $building['state_ident'] . $building['district_ident'] . $building['borough_ident'] . $building['borough_type'],
                $building['city_name'],
                $building['city_ident'],
                $building['street_rlabel'],
                $building['street_ident'],
                $building['building_num'],
                sprintf('%02.6F', round($building['latitude'], 6)),
                sprintf('%02.6F', round($building['longitude'], 6)),
                $linktype,
                $SIDUSIS_LINKTECHNOLOGIES[$building['linktype']][$building['linktechnology']],
                $building['downlink'],
                $building['uplink'],
                $building['type'] == '1' ? 'rzeczywisty' : 'teoretyczny',
                ($building['services'] & 1) ? 'TAK' : 'NIE',
                ($building['services'] & 2) ? 'TAK' : 'NIE',
                OPERATOR_REPRESENTATIVE_ID,
            )
        );
    }

    $filesize = ftell($fh);
    rewind($fh);
    $content = fread($fh, $filesize);
    fclose($fh);

    return $content;
}

$httpHeaders = array(
    'Accept: application/json',
    empty($apiAuthToken) ? null : 'Authorization: Token ' . $apiAuthToken,
);

$curl_options = array(
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $httpHeaders,
    CURLOPT_VERBOSE => $debug,
);

if (array_key_exists('import-demands', $options)) {
    $curl_options[CURLOPT_POST] = false;
    $ch = curl_init($apiHost . $apiEndPoints['import-demands']);
    curl_setopt_array($ch, $curl_options);
    $response = json_decode(curl_exec($ch), true);
    $response = (array) $response;
    $response_count = sizeof($response);

    if ($debug) {
        print_r($response);
        echo 'Ilość znalezionych zapotrzebowań na Internet: ' . $response_count . PHP_EOL;
    }

    curl_close($ch);

    if (!empty($response_count)) {
        foreach ($response as $res) {
            $params = array(
                'divisionid' => $did,
                'requestor' => $res['submitter']['last_name'] . ' ' . $res['submitter']['first_name'],
                'requestor_mail' => $res['email'],
                'requestor_phone' => $res['phone_no'],
                'subject' => 'Zapotrzebowanie nr ' . $res['id'] . ': ' . $res['city_name'] . ' ' . (empty($res['street_name']) ? '' : $res['street_name'] . ' ') . $res['house_number'] . (empty($res['apt_number']) ? '' : '/' . $res['apt_number']),
                'body' => $lmsUrl . '/index.php?m=maplink&action=get-sidusis-link&longitude=' . str_replace(',', '.', $res['geometry']['x']) . '&latitude=' . str_replace(',', '.', $res['geometry']['y']),
                'queue' => $internet_demands_queueid,
                'source' => defined('RT_SOURCE_SIDUSIS') ? RT_SOURCE_SIDUSIS : RT_SOURCE_CALLCENTER,
                'service' => SERVICE_INTERNET,
                'type' => RT_TYPE_OFFER,
            );
            empty($internet_demands_categoryids) ? null : $params['categories'] = $internet_demands_categoryids;
            $ticketid = $LMS->TicketAdd($params);
            if (!empty($ticketid)) {
                if ($debug) {
                    echo 'Utworzyłem zgłoszenie: ' . $ticketid . PHP_EOL;
                }
                $curl_options[CURLOPT_CUSTOMREQUEST] = 'PATCH';
                $ch = curl_init($apiHost . str_replace('%id', $res['id'], $apiEndPoints['mark-demand-as-read']));
                curl_setopt_array($ch, $curl_options);
                curl_exec($ch);
                curl_close($ch);
            }
        }
    }
}

if (array_key_exists('export-ranges', $options)) {
    $export = getSidusisRangesReport($did);
    $zip = new ZipArchive();
    if ($zip->open($sidusisTemporaryFilePath, ZipArchive::CREATE)) {
        $zip->addFromString('lms-sidusis.csv', $export);
        $zip->close();
    }

    $curl_options[CURLOPT_POST] = false;
    $curl_options[CURLOPT_POSTFIELDS] = array(
        'is_incremental' => array_key_exists('replace-ranges', $options) ? false : true,
        'file' => curl_file_create(
            $sidusisTemporaryFilePath,
            'application/zip',
            basename($sidusisTemporaryFilePath)
        ),
    );

    $ch = curl_init($apiHost . $apiEndPoints['export-ranges']);
    curl_setopt_array($ch, $curl_options);
    $response = curl_exec($ch);
    if ($debug) {
        print_r(json_decode($response));
    }
    curl_close($ch);
    @unlink($sidusisTemporaryFilePath);
}
