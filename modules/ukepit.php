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

ini_set('memory_limit', '512M');
ini_set('max_execution_time', '0');

if (!class_exists('ZipArchive')) {
    die('Error: ZipArchive class not found! Install php-zip module.');
}

//$old_locale = setlocale(LC_NUMERIC, '0');
setlocale(LC_NUMERIC, 'C');

function sanitizeTechnologyName($technologyName)
{
    static $dash_character = null;

    if (!isset($dash_character)) {
        $dash_character = isset($_POST['dash-character']) && $_POST['dash-character'] == 'pit' ? 1 : 2;
    }

    if ($dash_character == 1) {
        return $technologyName;
    } else {
        return str_replace('–', '-', $technologyName);
    }
}

function technologyName($technology)
{
    static $LINKTECHNOLOGIES = null;

    if (!isset($LINKTECHNOLOGIES)) {
        $LINKTECHNOLOGIES = $GLOBALS['LINKTECHNOLOGIES'];
    }

    if ($technology < 100) {
        return sanitizeTechnologyName($LINKTECHNOLOGIES[LINKTYPE_WIRE][$technology]);
    } elseif ($technology < 200) {
        return sanitizeTechnologyName($LINKTECHNOLOGIES[LINKTYPE_WIRELESS][$technology]);
    } else {
        return sanitizeTechnologyName($LINKTECHNOLOGIES[LINKTYPE_FIBER][$technology]);
    }
}

function mediaCodeByTechnology($technology)
{
    static $LINKTECHNOLOGIES = null;

    if (!isset($LINKTECHNOLOGIES)) {
        $LINKTECHNOLOGIES = $GLOBALS['LINKTECHNOLOGIES'];
    }

    if ($technology < 50 || $technology >= 100) {
        if ($technology < 100) {
            return LINKTYPE_WIRE;
        } elseif ($technology < 200) {
            return LINKTYPE_WIRELESS;
        } else {
            return LINKTYPE_FIBER;
        }
    } else {
        return 3;
    }
}

function mediaNameByCode($mediaName)
{
    static $mediaNames = array(
        LINKTYPE_WIRE => 'kablowe parowe miedziane',
        LINKTYPE_WIRELESS => 'radiowe',
        LINKTYPE_FIBER => 'światłowodowe',
        3 => 'kablowe współosiowe miedziane',
    );

    return $mediaNames[$mediaName];
}

function mediaNameByTechnology($technology)
{
    return mediaNameByCode(mediaCodeByTechnology($technology));
}

function ethernetInterfaceCodeByTechnology($technology)
{
    static $LINKTECHNOLOGIES = null;
    static $ethernet_interface_codes = null;

    if (!isset($LINKTECHNOLOGIES)) {
        $LINKTECHNOLOGIES = $GLOBALS['LINKTECHNOLOGIES'];
    }


    if (!isset($ethernet_interface_codes)) {
        $ethernet_interface_codes = array(
            // Ethernet 100Mb/s
            7 => '01',
            204 => '01',
            // Ethernet 1 Gb/s
            8 => '02',
            205 => '02',
            // Ethernet 10 Gb/s
            9 => '03',
            206 => '03',
            // Ethernet 25 Gb/s
            215 => '04',
            // Ethernet 40 Gb/s
            210 => '05',
            // Ethernet 100 Gb/s
            207 => '06'
            // Ethernet 200 Gb/s = 07
            // Ethernet 400 Gb/s = 08
            // Ethernet 800 Gb/s = 09
        );
    }

    return $ethernet_interface_codes[$technology] ?? '10';
}

function pointCodeByNetNodeType($netNodeType)
{
    static $point_codes = null;

    if (!isset($point_codes)) {
        $point_codes = array(
            // Szafa kablowa
            17  => '01',
            // Studzienka
            9   => '02',
            // Mufa kablowa
            20  => '03',
            // Skrzynka kablowa
            8   => '04',
            // Kontener telekomunikacyjny
            6   => '05',
            // Słupek telekomunikacyjny
            19  => '06',
            // Słupek kablowy
            18  => '07',
            // Szafa telekomunikacyjna
            7   => '08',
            // Złącze kablowe
            16  => '09',
            // Maszt oświetleniowy
            15  => '10',
            // Maszt telekomunikacyjny
            4   => '11',
            // Słup
            14  => '12',
            // Wieża telekomunikacyjna
            5   => '13',
            // Inne określone w narzędziu teleinformatycznym = 14
        );
    }

    return $point_codes[$netNodeType] ?? '14';
}

function isMobileTechnology($technology)
{
    static $mobile_technologies = null;

    if (!isset($mobile_technologies)) {
        $mobile_technologies = array(
            // WiMAX
            102 => true,
            // LMDS
            103 => true,
            // 2G/GSM (w tym GPRS oraz EDGE)
            106 => true,
            // EDGE
            //107 => true,
            // 3G/CDMA2000
            152 => true,
            // 3G/UMTS,
            113 => true,
            // 3G/HSPA
            108 => true,
            // 3G/HSPA+
            109 => true,
            // 3G/DC-HSPA
            150 => true,
            // 3G/DC-HSPA+
            110 => true,
            // 3G/MC-HSPA
            151 => true,
            // 3G/MC-HSPA+
            111 => true,
            // 4G/LTE
            112 => true,
            // 4G/LTE-A
            153 => true,
            // 4G/LTE-Pro
            154 => true,
            // 5G/NR SA
            155 => true,
            // 5G/NR NSA
            156 => true,
        );
    }

    return isset($mobile_technologies[$technology]);
}

function routeTypeName($routetype)
{
    static $route_types = null;
    static $report_other_duct_type_as = null;

    if (!isset($route_types)) {
        $route_types = array(
            1 => 'Linia kablowa podziemna (umieszczona bezpośrednio w ziemi)',
            2 => 'Linia kablowa umieszczona w kanalizacji kablowej (w tym w rurociągu kablowym, mikrokanalizacji)',
            3 => 'Linia kablowa umieszczona w kanale technologicznym',
            4 => 'Linia kablowa nadziemna na podbudowie słupowej telekomunikacyjnej',
            5 => 'Linia kablowa nadziemna na podbudowie elektroenergetycznej, oświetleniowej lub trakcyjnej',
        );

        $report_other_duct_type_as = intval($_POST['report-other-duct-type-as']);
        $report_other_duct_type_as = $route_types[$report_other_duct_type_as] ?? 'Inna określona w narzędziu teleinformatycznym';
    }

    return $route_types[$routetype] ?? $report_other_duct_type_as;
}

function radioTransmissionNameByTechnology($technology)
{
    static $transmission_names = null;

    if (!isset($transmission_names)) {
        $transmission_names = array(
            // WiFi – 802.11a w paśmie 5GHz
            117 => 'WiFi',
            // WiFi – 802.11b w paśmie 2.4GHz
            118 => 'WiFi',
            // WiFi – 802.11g w paśmie 2.4GHz
            119 => 'WiFi',
            // WiFi – 802.11n w paśmie 2.4GHz
            100 => 'WiFi',
            // WiFi – 802.11n w paśmie 5GHz
            120 => 'WiFi',
            // WiFi – 802.11ac w paśmie 5GHz
            101 => 'WiFi',
            // WiFi – 802.11ax w paśmie 2.4GHz
            121 => 'WiFi',
            // WiFi – 802.11ax w paśmie 5GHz
            122 => 'WiFi',
            // WiFi – 802.11ax w paśmie 6GHz
            123 => 'WiFi',
            // WiFi – 802.11ad w paśmie 60GHz
            124 => 'WiFi',
            // WiMAX
            102 => 'WiMAX',
            // LMDS
            103 => 'LMDS',
            // radiolinia
            104 => 'Radiolinia',
            // 2G/GSM (w tym GPRS oraz EDGE)
            106 => 'Inny określony w narzędziu teleinformatycznym',
            //107 => 'EDGE',
            // 3G/CDMA2000
            152 => 'Inny określony w narzędziu teleinformatycznym',
            // 3G/UMTS
            113 => 'Inny określony w narzędziu teleinformatycznym',
            // 3G/HSPA
            108 => 'Inny określony w narzędziu teleinformatycznym',
            // 3G/HSPA+
            109 => 'Inny określony w narzędziu teleinformatycznym',
            // 3G/DC-HSPA
            150 => 'Inny określony w narzędziu teleinformatycznym',
            // 3G/DC-HSPA+
            110 => 'Inny określony w narzędziu teleinformatycznym',
            // 3G/MC-HSPA
            151 => 'Inny określony w narzędziu teleinformatycznym',
            // 3G/MC-HSPA+
            111 => 'Inny określony w narzędziu teleinformatycznym',
            // 4G/LTE
            112 => 'Inny określony w narzędziu teleinformatycznym',
            // 4G/LTE-A
            153 => 'Inny określony w narzędziu teleinformatycznym',
            // 4G/LTE-Pro
            154 => 'Inny określony w narzędziu teleinformatycznym',
            // 5G/NR SA
            155 => 'Inny określony w narzędziu teleinformatycznym',
            // 5G/NR NSA
            156 => 'Inny określony w narzędziu teleinformatycznym',
        );
    }

    return $transmission_names[$technology];
}

/*!
 * \brief Parse network speed
 */
function networkSpeedCode($speed)
{
    $speed = round($speed / $GLOBALS['speed_unit_type'], 2);

    if ($speed <= 2) {
        return '01';
    } elseif ($speed <= 10) {
        return '02';
    } elseif ($speed <= 20) {
        return '03';
    } elseif ($speed <= 30) {
        return '04';
    } elseif ($speed <= 40) {
        return '05';
    } elseif ($speed <= 50) {
        return '06';
    } elseif ($speed <= 60) {
        return '07';
    } elseif ($speed <= 70) {
        return '08';
    } elseif ($speed <= 80) {
        return '09';
    } elseif ($speed <= 90) {
        return '10';
    } elseif ($speed <= 100) {
        return '11';
    } elseif ($speed <= 200) {
        return '12';
    } elseif ($speed <= 300) {
        return '13';
    } elseif ($speed <= 400) {
        return '14';
    } elseif ($speed <= 500) {
        return '15';
    } elseif ($speed <= 600) {
        return '16';
    } elseif ($speed <= 700) {
        return '17';
    } elseif ($speed <= 800) {
        return '18';
    } elseif ($speed <= 900) {
        return '19';
    } elseif ($speed <= 1000) {
        return '20';
    } elseif ($speed <= 2000) {
        return '21';
    } elseif ($speed <= 3000) {
        return '22';
    } elseif ($speed <= 4000) {
        return '23';
    } elseif ($speed <= 5000) {
        return '24';
    } elseif ($speed <= 6000) {
        return '25';
    } elseif ($speed <= 7000) {
        return '26';
    } elseif ($speed <= 8000) {
        return '27';
    } elseif ($speed <= 9000) {
        return '28';
    } elseif ($speed <= 10000) {
        return '29';
    } else {
        return '30';
    }
}

define('EOL', "\r\n");

$customers = array();

$report_type = isset($_POST['report-type']) && $_POST['report-type'] == 'customer-services' ? 'customer-services' : 'full';

$speed_unit_type = intval(ConfigHelper::getConfig('phpui.speed_unit_type', 1000));
if (!$speed_unit_type) {
    $speed_unit_type = 1000;
}
$root_netdevice_id = intval(ConfigHelper::getConfig('phpui.root_netdevice_id'));

if ($report_type == 'full' && empty($root_netdevice_id)) {
    die(trans('Root network device ID is not defined! Use <strong>\'phpui.root_netdevice_id\'</strong> configuration setting to define it.'));
}

$division = isset($_POST['division']) ? intval($_POST['division']) : 0;
$aggregate_customer_services = isset($_POST['aggregate-customer-services']);
$customer_resources_as_operator_resources = isset($_POST['customer-resources-as-operator-resources']);
$summary_only = isset($_POST['summaryonly']);
$validate_teryt = isset($_POST['validate-teryt']);
$validate_building_numbers = isset($_POST['validate-building-numbers']);
$validate_gps = isset($_POST['validate-gps']);
$validate_wireless_links = isset($_POST['validate-wireless-links']);
$complete_breakdown_points = isset($_POST['complete-breakdown-points']);
$detect_loops = isset($_POST['detectloops']);
$report_elements_outside_network_infrastructure = isset($_POST['report-elements-outside-network-infrastructure']);
$verify_feeding_netnodes_of_flexibility_points = isset($POST['uke-pit-verify-feeding-netnodes-of-flexibility-points']);

$pit_ethernet_technologies = array();

foreach ($LINKTECHNOLOGIES as $linktype => $linktechnologies) {
    foreach ($linktechnologies as $linktechnology => $label) {
        if (stripos($label, 'ethernet') !== false) {
            $pit_ethernet_technologies[$linktechnology] = $linktechnology;
        }
    }
}

function to_csv($data)
{
    foreach ($data as $key => $val) {
        $data[$key] = '"' . (isset($val) ? str_replace('"', '""', $val) : '') . '"';
    }
    return implode(',', array_values($data));
}

function to_wgs84($coord, $ifLongitude = true)
{
    return str_replace(',', '.', sprintf("%.04f", $coord));
}

$borough_types = array(
    1 => 'gm. miejska',
    2 => 'gm. wiejska',
    3 => 'gm. miejsko-wiejska',
    4 => 'gm. miejsko-wiejska',
//  4 => 'miasto w gminie miejsko-wiejskiej',
    5 => 'gm. miejsko-wiejska',
//  5 => 'obszar wiejski gminy miejsko-wiejskiej',
    8 => 'dzielnica gminy Warszawa-Centrum',
    9 => 'dzielnica',
);

$projects = $LMS->GetProjects();
if (!empty($invprojects)) {
    foreach ($projects as $idx => $project) {
        if (!in_array($idx, $invprojects)) {
            unset($projects[$idx]);
        }
    }
}

$allradiosectors = $DB->GetAllByKey("SELECT * FROM netradiosectors ORDER BY id", "id");

$teryt_cities = $DB->GetAllByKey(
    "SELECT lc.id AS cityid,
        ls.name AS area_woj,
        ld.name AS area_pow,
        lb.name AS area_gmi,
        (" . $DB->Concat('ls.ident', 'ld.ident', 'lb.ident', 'lb.type') . ") AS area_terc,
        lc.name AS area_city,
        lc.ident AS area_simc,
        (CASE WHEN EXISTS (SELECT 1 FROM location_streets lst WHERE lst.cityid = lc.id) THEN 1 ELSE 0 END) AS with_streets
    FROM location_cities lc
    LEFT JOIN location_boroughs lb ON lb.id = lc.boroughid
    LEFT JOIN location_districts ld ON ld.id = lb.districtid
    LEFT JOIN location_states ls ON ls.id = ld.stateid
    JOIN (
        SELECT DISTINCT city_id FROM addresses
    ) a ON a.city_id = lc.id",
    'cityid'
);

$teryt_streets = $DB->GetAllByKey(
    "SELECT lst.cityid, lst.id AS streetid,
        tu.cecha AS address_cecha,
        (CASE WHEN lst.name2 IS NOT NULL THEN " . $DB->Concat('lst.name2', "' '", 'lst.name') . " ELSE lst.name END) AS address_ulica,
        tu.sym_ul AS address_symul
    FROM location_streets lst
    LEFT JOIN teryt_ulic tu ON tu.id = lst.id
    JOIN (
        SELECT DISTINCT street_id FROM addresses
    ) a ON a.street_id = lst.id",
    'streetid'
);

if ($report_type == 'full') {
    $real_netnodes = $DB->GetAllByKey(
        "SELECT nn.id, nn.name, nn.invprojectid, nn.type, nn.status, nn.ownership, nn.coowner,
            nn.longitude, nn.latitude,
            a.city_id as location_city, a.street_id as location_street, a.house as location_house, a.flat as location_flat,
            a.city as location_city_name, a.street as location_street_name,
            a.zip AS location_zip,
            (CASE WHEN (a.flat IS NULL OR a.flat = '') THEN a.house ELSE " . $DB->Concat('a.house', "'/'", 'a.flat') . " END) AS address_budynek
        FROM netnodes nn
        LEFT JOIN addresses a ON nn.address_id = a.id
        ORDER BY nn.id",
        'id'
    );

    // prepare info about network devices from lms database
    $netdevices = $DB->GetAllByKey(
        "SELECT nd.id, nd.ownerid, ports,
            nd.longitude, nd.latitude, nd.status, nd.netnodeid,
            (CASE WHEN nd.invprojectid = 1 THEN nn.invprojectid ELSE nd.invprojectid END) AS invprojectid,
            " . $DB->Concat('ts.woj', 'ts.pow', 'ts.gmi', 'ts.rodz_gmi') . " AS area_terc,
            a.city_id AS location_city,
            lc.ident AS area_simc,
            a.street_id AS location_street,
            lst.ident AS area_ulic,
            a.house AS location_house,
            a.flat AS location_flat,
            a.city AS location_city_name,
            a.street AS location_street_name,
            (CASE WHEN (a.flat IS NULL OR a.flat = '') THEN a.house ELSE " . $DB->Concat('a.house', "'/'", 'a.flat') . " END) AS address_budynek,
            a.zip AS location_zip,
            COALESCE(t.passive, 1) AS passive,
            nd.name AS name
        FROM netdevices nd
        LEFT JOIN netnodes nn ON nn.id = nd.netnodeid
        LEFT JOIN addresses a ON nd.address_id = a.id
        LEFT JOIN location_cities lc ON lc.id = a.city_id
        LEFT JOIN teryt_simc ts ON ts.cityid = a.city_id
        LEFT JOIN location_streets lst ON lst.id = a.street_id
        LEFT JOIN netdevicemodels m ON m.id = nd.netdevicemodelid
        LEFT JOIN netdevicetypes t ON t.id = m.type
        WHERE " . ($customer_resources_as_operator_resources ? '' : 'nd.ownerid IS NULL AND') . " EXISTS (
            SELECT id
            FROM netlinks nl
            WHERE nl.src = nd.id
                OR nl.dst = nd.id
        )
        ORDER BY nd.id",
        'id'
    );

    $all_netlinks = array();

    $tmp_netlinks = $DB->GetAll(
        "SELECT
            nl.id,
            nl.src,
            nl.dst,
            nl.type,
            nl.technology,
            nl.speed
        FROM netlinks nl
        JOIN netdevices ndsrc ON ndsrc.id = nl.src
        JOIN netdevices nddst ON nddst.id = nl.dst"
        . ($customer_resources_as_operator_resources ? '' : ' WHERE ndsrc.ownerid IS NULL AND nddst.ownerid IS NULL')
    );

    if (!empty($tmp_netlinks)) {
        foreach ($tmp_netlinks as $netlink) {
            if (!isset($all_netlinks[$netlink['src']])) {
                $all_netlinks[$netlink['src']] = array();
            }
            $all_netlinks[$netlink['src']][$netlink['id']] = array(
                'netdevid' => $netlink['dst'],
                'type' => $netlink['type'],
                'technology' => $netlink['technology'],
                'speed' => $netlink['speed'],
            );

            if (!isset($all_netlinks[$netlink['dst']])) {
                $all_netlinks[$netlink['dst']] = array();
            }
            $all_netlinks[$netlink['dst']][$netlink['id']] = array(
                'netdevid' => $netlink['src'],
                'type' => $netlink['type'],
                'technology' => $netlink['technology'],
                'speed' => $netlink['speed'],
            );
        }

        unset($tmp_netlinks);
    }

    if (!$customer_resources_as_operator_resources) {
        function find_nodes_for_netdev($customerid, $netdevid, &$customer_nodes, &$customer_netlinks)
        {
            static $processed_netdevices = array();

            $processed_netdevices[$netdevid] = true;

            if (isset($customer_nodes[$customerid . '_' . $netdevid])) {
                $nodeids = explode(',', $customer_nodes[$customerid . '_' . $netdevid]['nodeids']);
            } else {
                $nodeids = array();
            }

            if (!empty($customer_netlinks)) {
                foreach ($customer_netlinks as &$customer_netlink) {
                    if ($customer_netlink['src'] == $netdevid) {
                        $next_netdevid = $customer_netlink['dst'];
                    } else if ($customer_netlink['dst'] == $netdevid) {
                        $next_netdevid = $customer_netlink['src'];
                    } else {
                        continue;
                    }

                    if (isset($processed_netdevices[$next_netdevid])) {
                        continue;
                    }

                    $nodeids = array_merge($nodeids, find_nodes_for_netdev(
                        $customerid,
                        $next_netdevid,
                        $customer_nodes,
                        $customer_netlinks
                    ));
                }
                unset($customer_netlink);
            }

            return $nodeids;
        }

        // search for links between operator network devices and customer network devices
        $uni_links = $DB->GetAllByKey(
            "SELECT
                nl.id AS netlinkid,
                nl.type AS type,
                nl.technology AS technology,
                nl.speed AS speed,
                rs.frequency,
                rs.id AS radiosectorid,
                c.id AS customerid,
                c.type AS customertype,
                (CASE WHEN ndsrc.ownerid IS NULL THEN nl.src ELSE nl.dst END) AS operator_netdevid,
                (CASE WHEN ndsrc.ownerid IS NULL THEN ndsrc.name ELSE nddst.name END) AS operator_netdevname,
                (CASE WHEN ndsrc.ownerid IS NULL THEN ndsrc.status ELSE nddst.status END) AS operator_netdevstatus,
                (CASE WHEN ndsrc.ownerid IS NULL THEN nddst.invprojectid ELSE ndsrc.invprojectid END) AS invprojectid,
                (CASE WHEN ndsrc.ownerid IS NULL THEN nl.dst ELSE nl.src END) AS netdevid,
                (CASE WHEN ndsrc.ownerid IS NULL THEN nddst.name ELSE ndsrc.name END) AS netdevname,
                (CASE WHEN ndsrc.ownerid IS NULL THEN nddst.longitude ELSE ndsrc.longitude END) AS longitude,
                (CASE WHEN ndsrc.ownerid IS NULL THEN nddst.latitude ELSE ndsrc.latitude END) AS latitude,
                (CASE WHEN ndsrc.ownerid IS NULL THEN nddst.address_id ELSE ndsrc.address_id END) AS address_id,
                (CASE WHEN ndsrc.ownerid IS NULL THEN adst.city_id ELSE asrc.city_id END) AS location_city,
                (CASE WHEN ndsrc.ownerid IS NULL THEN adst.city ELSE asrc.city END) AS location_city_name,
                (CASE WHEN ndsrc.ownerid IS NULL THEN adst.street_id ELSE asrc.street_id END) AS location_street,
                (CASE WHEN ndsrc.ownerid IS NULL THEN adst.street ELSE asrc.street END) AS location_street_name,
                (CASE WHEN ndsrc.ownerid IS NULL THEN adst.house ELSE asrc.house END) AS location_house
            FROM netlinks nl
            JOIN netdevices ndsrc ON ndsrc.id = nl.src
            LEFT JOIN addresses asrc ON asrc.id = ndsrc.address_id
            JOIN netdevices nddst ON nddst.id = nl.dst
            LEFT JOIN addresses adst ON adst.id = nddst.address_id
            JOIN customers c ON (ndsrc.ownerid IS NULL AND c.id = nddst.ownerid)
                OR (nddst.ownerid IS NULL AND c.id = ndsrc.ownerid)
            LEFT JOIN netradiosectors rs ON (ndsrc.ownerid IS NULL AND rs.id = nl.srcradiosector)
                OR (nddst.ownerid IS NULL AND rs.id = nl.dstradiosector)
            WHERE (ndsrc.ownerid IS NULL AND nddst.ownerid IS NOT NULL)
                OR (nddst.ownerid IS NULL AND ndsrc.ownerid IS NOT NULL)
            ORDER BY nl.id",
            'netlinkid'
        );
        if (!empty($uni_links)) {
            $customer_netlinks = $DB->GetAllByKey(
                "SELECT "
                . $DB->Concat('nl.src', "'_'", 'nl.dst') . " AS netlink,
                    nl.src,
                    nl.dst
                FROM netlinks nl
                JOIN netdevices ndsrc ON ndsrc.id = nl.src
                JOIN netdevices nddst ON nddst.id = nl.dst
                WHERE ndsrc.ownerid IS NOT NULL AND nddst.ownerid IS NOT NULL
                    AND ndsrc.ownerid = nddst.ownerid",
                'netlink'
            );

            $customer_nodes = $DB->GetAllByKey(
                "SELECT "
                . $DB->GroupConcat('n.id') . " AS nodeids, "
                . $DB->Concat('CASE WHEN n.ownerid IS NULL THEN nd.ownerid ELSE n.ownerid END', "'_'", 'n.netdev') . " AS customerid_netdev
                FROM nodes n
                LEFT JOIN netdevices nd ON nd.id = n.netdev AND n.ownerid IS NULL AND nd.ownerid IS NOT NULL
                WHERE (n.ownerid IS NOT NULL OR nd.ownerid IS NOT NULL)
                    AND EXISTS (
                        SELECT na.id FROM nodeassignments na
                        JOIN assignments a ON a.id = na.assignmentid
                        LEFT JOIN vassignmentsuspensions vas ON vas.suspension_assignment_id = a.id
                            AND vas.suspension_datefrom <= ?NOW?
                            AND (vas.suspension_dateto >= ?NOW? OR vas.suspension_dateto = 0)
                            AND a.datefrom <= ?NOW? AND (a.dateto >= ?NOW? OR a.dateto = 0)
                        WHERE na.nodeid = n.id
                            AND a.commited = 1
                            AND vas.suspended IS NULL
                            AND a.period IN ?
                            AND a.datefrom < ?NOW?
                            AND (a.dateto = 0 OR a.dateto > ?NOW?)
                    )
                    AND NOT EXISTS (
                        SELECT id FROM assignments aa
                        LEFT JOIN vassignmentsuspensions vas ON vas.suspension_assignment_id = aa.id
                            AND vas.suspension_datefrom <= ?NOW?
                            AND (vas.suspension_dateto >= ?NOW? OR vas.suspension_dateto = 0)
                            AND (aa.dateto = 0 OR aa.dateto > ?NOW?)
                        WHERE aa.customerid = (CASE WHEN n.ownerid IS NULL THEN nd.ownerid ELSE n.ownerid END)
                            AND aa.commited = 1
                            AND aa.tariffid IS NULL
                            AND vas.suspension_suspend_all = 1
                            AND (aa.dateto = 0 OR aa.dateto > ?NOW?)
                    )
                GROUP BY customerid_netdev",
                'customerid_netdev',
                array(
                    array(YEARLY, HALFYEARLY, QUARTERLY, MONTHLY, DISPOSABLE),
                )
            );

            // collect customer node/node-netdev identifiers connected to customer subnetwork
            $netdevs = array();

            foreach ($uni_links as $netlinkid => &$netlink) {
                $nodes = find_nodes_for_netdev(
                    $netlink['customerid'],
                    $netlink['netdevid'],
                    $customer_nodes,
                    $customer_netlinks
                );
                if (empty($nodes)) {
                    unset($uni_links[$netlinkid]);
                } else {
                    $netlink['nodes'] = $nodes;
                }
            }
            unset($netlink);

            unset($customer_netlinks);
            unset($customer_nodes);
        }
    }

    if (empty($real_netnodes)) {
        $real_netnodes = array();
    } else {
        foreach ($real_netnodes as $k => $v) {
            $tmp = array(
                'city_name' => $v['location_city_name'],
                'location_house' => $v['location_house'],
                'location_flat' => $v['location_flat'],
                'street_name' => $v['location_street_name'],
            );

            $location = location_str($tmp);

            if (!$location) {
                $location = '';
            }

            $real_netnodes[$k]['location'] = $location;
        }
    }
}

// get node gps coordinates which are used for network range gps calculation
$nodecoords = $DB->GetAllByKey(
    "SELECT id, longitude, latitude
    FROM nodes
    WHERE longitude IS NOT NULL
        AND latitude IS NOT NULL",
    'id'
);

// prepare info about network nodes
if ($report_type == 'full') {
    $netnodes = array();
    $netdevs = array();
    $foreigners = array();
    $netnodeid = 1;

    $root_netnode_name = null;
    $processed_child_netlinks = array();
}

$errors = array(
    'netnodes' => array(),
    'netdevices' => array(),
    'nodes' =>  array(),
    'netlinks' => array(),
    'flexibility-points' => array(),
);

if ($report_type == 'full') {
    if ($netdevices) {
        foreach ($netdevices as $netdevid => &$netdevice) {
            $tmp = array(
                'city_name' => $netdevice['location_city_name'],
                'location_house' => $netdevice['location_house'],
                'location_flat' => $netdevice['location_flat'],
                'street_name' => $netdevice['location_street_name'],
            );

            $location = location_str($tmp);

            if ($location) {
                $netdevice['location'] = $location;
            } else if ($netdevice['ownerid']) {
                $netdevice['location'] = $LMS->getAddressForCustomerStuff($netdevice['ownerid']);
            }

            $accessports = $DB->GetAll(
                "SELECT
                    linktype AS type,
                    linktechnology AS technology,
                    linkspeed AS speed,
                    rs.frequency, "
                . $DB->GroupConcat('rs.id') . " AS radiosectors,
                    c.type AS customertype,
                    COUNT(port) AS portcount
                FROM nodes n
                JOIN customers c ON c.id = n.ownerid
                LEFT JOIN netradiosectors rs ON rs.id = n.linkradiosector
                WHERE n.netdev = ? " . ($customer_resources_as_operator_resources ? '' : 'AND n.ownerid IS NOT NULL') . "
                    AND EXISTS (
                        SELECT na.id FROM nodeassignments na
                        JOIN assignments a ON a.id = na.assignmentid
                        LEFT JOIN vassignmentsuspensions vas ON vas.suspension_assignment_id = a.id
                            AND vas.suspension_datefrom <= ?NOW?
                            AND (vas.suspension_dateto >= ?NOW? OR vas.suspension_dateto = 0)
                            AND a.datefrom <= ?NOW? AND (a.dateto >= ?NOW? OR a.dateto = 0)
                        WHERE na.nodeid = n.id
                            AND a.commited = 1
                            AND vas.suspended IS NULL
                            AND a.period IN ?
                            AND a.datefrom < ?NOW?
                            AND (a.dateto = 0 OR a.dateto > ?NOW?)
                    )
                    AND NOT EXISTS (
                        SELECT id 
                        FROM assignments aa
                        LEFT JOIN vassignmentsuspensions vas ON vas.suspension_assignment_id = aa.id
                            AND vas.suspension_datefrom <= ?NOW?
                            AND (vas.suspension_dateto >= ?NOW? OR vas.suspension_dateto = 0)
                            AND (aa.dateto > ?NOW? OR aa.dateto = 0)
                        WHERE aa.customerid = c.id
                            AND aa.commited = 1
                            AND vas.suspension_suspend_all = 1
                            AND aa.datefrom < ?NOW?
                            AND (aa.dateto > ?NOW? OR aa.dateto = 0)
                    )
                GROUP BY linktype, linktechnology, linkspeed, rs.frequency, c.type
                ORDER BY c.type",
                array(
                    $netdevice['id'],
                    array(YEARLY, HALFYEARLY, QUARTERLY, MONTHLY, DISPOSABLE),
                )
            );

            if (!$customer_resources_as_operator_resources) {
                // append uni links to access ports
                $access_links = $DB->GetAll(
                    "SELECT
                        nl.id
                    FROM netlinks nl
                    JOIN netdevices ndsrc ON ndsrc.id = nl.src
                    JOIN netdevices nddst ON nddst.id = nl.dst
                    WHERE (nl.src = ? AND ndsrc.ownerid IS NULL AND nddst.ownerid IS NOT NULL)
                        OR (nl.dst = ? AND nddst.ownerid IS NULL AND ndsrc.ownerid IS NOT NULL)",
                    array(
                        $netdevice['id'],
                        $netdevice['id'],
                    )
                );
                if (!empty($access_links)) {
                    if (empty($accessports)) {
                        $accessports = array();
                    }
                    foreach ($access_links as &$access_link) {
                        if (isset($uni_links[$access_link['id']])) {
                            $uni_link = &$uni_links[$access_link['id']];
                            $processed_access_link = false;
                            foreach ($accessports as &$access_port) {
                                if ($access_port['type'] == $uni_link['type']
                                    && $access_port['technology'] == $uni_link['technology']
                                    && $access_port['speed'] == $uni_link['speed']
                                    && $access_port['frequency'] == $uni_link['frequency']
                                    && $access_port['customertype'] == $uni_link['customertype']) {
                                    $processed_access_link = true;
                                    if (!empty($uni_link['radiosectorid'])) {
                                        if (empty($access_ports['radiosectors'])) {
                                            $access_port['radiosectors'] = $uni_link['radiosectorid'];
                                        } else {
                                            $access_port['radiosectors'] .= ',' . $uni_link['radiosectorid'];
                                        }
                                    }
                                    if (isset($access_port['uni_links'])) {
                                        $access_port['uni_links'][] = $access_link['id'];
                                    } else {
                                        $access_port['uni_links'] = array($access_link['id']);
                                    }
                                }
                            }
                            unset($access_port);
                            if (!$processed_access_link) {
                                $accessports[] = array(
                                    'type' => $uni_link['type'],
                                    'technology' => $uni_link['technology'],
                                    'speed' => $uni_link['speed'],
                                    'frequency' => $uni_link['frequency'],
                                    'radiosectors' => $uni_link['radiosectorid'],
                                    'customertype' => $uni_link['customertype'],
                                    'uni_links' => array($access_link['id']),
                                );
                            }
                        }
                    }
                    unset($access_link);
                }
            }

            $netdevice['invproject'] = $netdevice['invproject'] =
                !isset($netdevice['invprojectid']) || !strlen($netdevice['invprojectid']) ? '' : $projects[$netdevice['invprojectid']]['name'];

            $projectname = $prj = '';
            if (array_key_exists($netdevice['netnodeid'], $real_netnodes)) {
                $netnodename = mb_strtoupper($real_netnodes[$netdevice['netnodeid']]['name']);
                if (isset($real_netnodes[$netdevice['netnodeid']]['invprojectid']) && strlen($real_netnodes[$netdevice['netnodeid']]['invprojectid'])) {
                    $projectname = $prj = $projects[$real_netnodes[$netdevice['netnodeid']]['invprojectid']]['name'];
                }
            } else {
                if (empty($netdevice['location_city'])) {
                    $netnodename = $netdevice['location'] ?? '(pusty)';
                } else {
                    $netnodename = $netdevice['area_terc'] . '_' . $netdevice['area_simc'] . '_' . $netdevice['area_ulic'] . '_' . $netdevice['location_house'];
                }
                $netnodename = mb_strtoupper($netnodename);

                if (array_key_exists($netnodename, $netnodes)) {
                    if (strlen($netdevice['invproject']) && !in_array($netdevice['invproject'], $netnodes[$netnodename]['invproject'])) {
                        $netnodes[$netnodename]['invproject'][] = $netdevice['invproject'];
                    }
                } else {
                    $prj = $netdevice['invproject'];
                    $projectname = $prj;
                }
            }

            $netdevice['netnodename'] = $netnodename;

            if (!array_key_exists($netnodename, $netnodes)) {
                if (!$customer_resources_as_operator_resources) {
                    $netnodes[$netnodename]['uni_links'] = array();
                }

                $netnodes[$netnodename]['id'] = $netnodeid;
                $netnodes[$netnodename]['invproject'] = strlen($projectname) ? array($projectname) : array();
                $netnodes[$netnodename]['name'] = $netnodename;

                if (array_key_exists($netdevice['netnodeid'], $real_netnodes)) {
                    $netnode = $real_netnodes[$netdevice['netnodeid']];
                    $netnodes[$netnodename]['real_id'] = $netnode['id'];
                    $netnodes[$netnodename]['location'] = $netnode['location'];
                    $netnodes[$netnodename]['location_city'] = $netnode['location_city'];
                    $netnodes[$netnodename]['location_city_name'] = $netnode['location_city_name'];
                    $netnodes[$netnodename]['location_street'] = $netnode['location_street'];
                    $netnodes[$netnodename]['location_street_name'] = $netnode['location_street_name'];
                    $netnodes[$netnodename]['location_house'] = $netnode['location_house'];
                    $netnodes[$netnodename]['location_zip'] = $netnode['location_zip'];
                    $netnodes[$netnodename]['status'] = intval($netnode['status']);
                    $netnodes[$netnodename]['type'] = intval($netnode['type']);
                    $netnodes[$netnodename]['ownership'] = intval($netnode['ownership']);
                    $netnodes[$netnodename]['coowner'] = $netnode['coowner'];

                    if (strlen($netnode['coowner'])) {
                        $coowner = $netnode['coowner'];

                        if (!array_key_exists($coowner, $foreigners)) {
                            $foreigners[$coowner] = $coowner;
                        }
                    }

                    if (isset($teryt_cities[$netnode['location_city']])) {
                        $teryt_city = $teryt_cities[$netnode['location_city']];

                        $netnodes[$netnodename]['area_woj'] = $teryt_city['area_woj'];
                        $netnodes[$netnodename]['area_pow'] = $teryt_city['area_pow'];
                        $netnodes[$netnodename]['area_gmi'] = $teryt_city['area_gmi'];
                        $netnodes[$netnodename]['area_terc'] = $teryt_city['area_terc'];
                        $netnodes[$netnodename]['area_rodz_gmi'] = $borough_types[intval(substr($teryt_city['area_terc'], 6, 1))];
                        $netnodes[$netnodename]['area_city'] = $teryt_city['area_city'];
                        $netnodes[$netnodename]['area_simc'] = $teryt_city['area_simc'];

                        if (!empty($netnode['location_street']) && isset($teryt_streets[$netnode['location_street']])) {
                            $teryt_street = $teryt_streets[$netnode['location_street']];

                            $netnodes[$netnodename]['address_cecha'] = $teryt_street['address_cecha'];
                            $netnodes[$netnodename]['address_ulica'] = $teryt_street['address_ulica'];
                            $netnodes[$netnodename]['address_symul'] = $teryt_street['address_symul'];
                        }

                        if (!strlen($teryt_city['area_terc']) || !strlen($teryt_city['area_simc']) || !strlen($netnode['location_house'])) {
                            $error = array(
                                'id' => $netnode['id'],
                                'name' => $netnode['name'],
                            );
                            if (!strlen($teryt_city['area_terc'])) {
                                $error['terc'] = true;
                            }
                            if (!strlen($teryt_city['area_simc'])) {
                                $error['simc'] = true;
                            }
                            if (!strlen($netnode['location_house'])) {
                                $error['location_house'] = true;
                            }
                            $errors['netnodes'][] = $error;
                        }
                    } else {
                        $error = array(
                            'id' => $netnode['id'],
                            'name' => $netnode['name'],
                            'terc' => true,
                            'simc' => true,
                        );
                        if (!isset($netnode['location_house']) || !strlen($netnode['location_house'])) {
                            $error['location_house'] = true;
                        }
                        $errors['netnodes'][] = $error;
                    }

                    $netnodes[$netnodename]['address_budynek'] = $netnode['address_budynek'];

                    if (!empty($netnode['longitude']) && !empty($netnode['latitude'])) {
                        $netnodes[$netnodename]['longitude'] = $netnode['longitude'];
                        $netnodes[$netnodename]['latitude'] = $netnode['latitude'];
                    }
                } else {
                    $netnodes[$netnodename]['location'] = $netdevice['location'] ?? '';
                    $netnodes[$netnodename]['location_city'] = $netdevice['location_city'];
                    $netnodes[$netnodename]['location_city_name'] = $netdevice['location_city_name'];
                    $netnodes[$netnodename]['location_street'] = $netdevice['location_street'];
                    $netnodes[$netnodename]['location_street_name'] = $netdevice['location_street_name'];
                    $netnodes[$netnodename]['location_house'] = $netdevice['location_house'];
                    $netnodes[$netnodename]['location_zip'] = $netdevice['location_zip'];
                    $netnodes[$netnodename]['status'] = 0;
                    $netnodes[$netnodename]['type'] = 8;
                    $netnodes[$netnodename]['ownership'] = 0;
                    $netnodes[$netnodename]['coowner'] = '';

                    if (isset($teryt_cities[$netdevice['location_city']])) {
                        $teryt_city = $teryt_cities[$netdevice['location_city']];

                        $netnodes[$netnodename]['area_woj'] = $teryt_city['area_woj'];
                        $netnodes[$netnodename]['area_pow'] = $teryt_city['area_pow'];
                        $netnodes[$netnodename]['area_gmi'] = $teryt_city['area_gmi'];
                        $netnodes[$netnodename]['area_terc'] = $teryt_city['area_terc'];
                        $netnodes[$netnodename]['area_rodz_gmi'] = $borough_types[intval(substr($teryt_city['area_terc'], 6, 1))];
                        $netnodes[$netnodename]['area_city'] = $teryt_city['area_city'];
                        $netnodes[$netnodename]['area_simc'] = $teryt_city['area_simc'];

                        if (!empty($netdevice['location_street']) && isset($teryt_streets[$netdevice['location_street']])) {
                            $teryt_street = $teryt_streets[$netdevice['location_street']];

                            $netnodes[$netnodename]['address_cecha'] = $teryt_street['address_cecha'];
                            $netnodes[$netnodename]['address_ulica'] = $teryt_street['address_ulica'];
                            $netnodes[$netnodename]['address_symul'] = $teryt_street['address_symul'];
                        }

                        if (!strlen($teryt_city['area_terc']) || !strlen($teryt_city['area_simc']) || !strlen($netdevice['location_house'])) {
                            $error = array(
                                'id' => $netdevice['id'],
                                'name' => $netdevice['name'],
                            );
                            if (!strlen($teryt_city['area_terc'])) {
                                $error['terc'] = true;
                            }
                            if (!strlen($teryt_city['area_simc'])) {
                                $error['simc'] = true;
                            }
                            if (!strlen($netdevice['location_house'])) {
                                $error['location_house'] = true;
                            }
                            $errors['netdevices'][] = $error;
                        }
                    } else {
                        $error = array(
                            'id' => $netdevice['id'],
                            'name' => $netdevice['name'],
                            'terc' => true,
                            'simc' => true,
                        );
                        if (!isset($netdevice['location_house']) || !strlen($netdevice['location_house'])) {
                            $error['location_house'] = true;
                        }
                        $errors['netdevices'][] = $error;
                    }

                    $netnodes[$netnodename]['address_budynek'] = $netdevice['address_budynek'];
                }

                $netnodes[$netnodename]['netdevices'] = array();

                if (!isset($netnodes[$netnodename]['longitude']) && !isset($netnodes[$netnodename]['latitude'])) {
                    $netnodes[$netnodename]['longitudes'] = array();
                    $netnodes[$netnodename]['latitudes'] = array();
                }

                $netnodes[$netnodename]['mode'] = empty($netdevice['passive']) ? 2 : 1;

                $netnodes[$netnodename]['media'] = array();
                $netnodes[$netnodename]['technologies'] = array();
                $netnodes[$netnodename]['local_technologies'] = array();
                $netnodes[$netnodename]['parent_netnodename'] = null;

                $netnodeid++;
            } elseif (empty($netdevice['passive']) && $netnodes[$netnodename]['mode'] < 2) {
                $netnodes[$netnodename]['mode'] = 2;
            }

            $netdevice['ownership'] = $netnodes[$netnodename]['ownership'];

            $projectname = $prj = $netdevice['invproject'];
            if (!strlen($projectname)) {
                $status = 0;
            } else {
                $status = $netdevice['status'];
            }

            if ($netdevid == $root_netdevice_id) {
                $root_netnode_name = $netnodename;
            }

            if (!empty($accessports)) {
                foreach ($accessports as $ports) {
                    if (!$customer_resources_as_operator_resources && isset($ports['uni_links'])) {
                        $netnodes[$netnodename]['uni_links'] = array_merge($netnodes[$netnodename]['uni_links'], $ports['uni_links']);
                    }
                }
            }

            $netnodes[$netnodename]['netdevices'][] = $netdevice['id'];

            if (!isset($netnodes[$netnodename]['longitutde'])
                && !isset($netnodes[$netnodename]['latitude'])
                && !empty($netdevice['longitude'])
                && !empty($netdevice['latitude'])) {
                $netnodes[$netnodename]['longitudes'][] = $netdevice['longitude'];
                $netnodes[$netnodename]['latitudes'][] = $netdevice['latitude'];
            }

            $netdevs[$netdevid] = $netnodename;
        }
        unset($netdevice);
    }

    if (!isset($root_netnode_name)) {
        die(trans('Unable to determine root network node using <strong>\'phpui.root_netdevice_id\'</strong> configuration setting!'));
    }

    if ($netnodes) {
        $pit_netnode_types = $NETELEMENTTYPEGROUPS[trans('infrastructure elements (PIT)')];

        foreach ($netnodes as $netnodename => &$netnode) {
            if (!isset($pit_netnode_types[$netnode['type']])) {
                $errors['netnodes'][] = array(
                    'id' => $netnode['real_id'],
                    'name' => $netnode['name'],
                    'type' => true,
                );
            }

            // if teryt location is not set then try to get location address from network node name
            if (!isset($netnode['area_woj'])) {
                $address = mb_split("[[:blank:]]+", $netnodename);
                $street = mb_ereg_replace("[[:blank:]][[:alnum:]]+$", "", $netnodename);
            }

            // count gps coordinates basing on average longitude and latitude of all network devices located in this network node
            if (isset($netnode['longitudes']) && count($netnode['longitudes'])) {
                $netnode['longitude'] = $netnode['latitude'] = 0.0;
                foreach ($netnode['longitudes'] as $longitude) {
                    $netnode['longitude'] += floatval($longitude);
                }
                foreach ($netnode['latitudes'] as $latitude) {
                    $netnode['latitude'] += floatval($latitude);
                }
                $netnode['longitude'] = to_wgs84($netnode['longitude'] / count($netnode['longitudes']));
                $netnode['latitude'] = to_wgs84($netnode['latitude'] / count($netnode['latitudes']));
            } else {
                if (empty($netnode['longitude']) || empty($netnode['latitude'])) {
                    if (empty($netnode['real_id'])) {
                        foreach ($netnode['netdevices'] as $netdeviceid) {
                            $netdevice = $netdevices[$netdeviceid];
                            if (empty($netdevice['longitude']) || empty($netdevice['latitude'])) {
                                $errors['netdevices'][] = array(
                                    'id' => $netdevice['id'],
                                    'name' => $netdevice['name'],
                                    'gps' => true,
                                );
                            }
                        }
                    } else {
                        $errors['netnodes'][] = array(
                            'id' => $netnode['real_id'],
                            'name' => $netnode['name'],
                            'gps' => true,
                        );
                    }
                }
            }

            if ($netnode['ownership'] == 2) {
                continue;
            }

            $netnode['ranges'] = array();

            // save info about network ranges
            $ranges = $DB->GetAll(
                "SELECT
                    n.linktype,
                    n.linktechnology,
                    a.city_id AS location_city,
                    a.street AS location_street_name,
                    a.street_id AS location_street,
                    a.house AS location_house,
                    0 AS from_uni_link
                FROM nodes n
                LEFT JOIN addresses a ON n.address_id = a.id
                JOIN customers c ON c.id = n.ownerid
                WHERE n.ownerid IS NOT NULL
                    " . ($division ? ' AND c.divisionid = ' . $division : '') . "
                    AND a.city_id IS NOT NULL
                    AND n.netdev IN ?
                GROUP BY n.linktype, n.linktechnology, a.city_id, a.street, a.street_id, a.house",
                array(
                    $netnode['netdevices'],
                )
            );

            if (empty($ranges)) {
                $ranges = array();
            }

            if (!$customer_resources_as_operator_resources) {
                // collect ranges from customer uni links
                $uni_ranges = array();
                if (!empty($netnode['uni_links'])) {
                    foreach ($netnode['uni_links'] as $uni_link_id) {
                        $uni_link = &$uni_links[$uni_link_id];
                        // $uni_link['nodes']
                        $uni_ranges[] = array(
                            'linktype' => $uni_link['type'],
                            'linktechnology' => $uni_link['technology'],
                            'location_city' => $uni_link['location_city'],
                            'location_city_name' => $uni_link['location_city_name'],
                            'location_street' => $uni_link['location_street'],
                            'location_street_name' => $uni_link['location_street_name'],
                            'location_house' => $uni_link['location_house'],
                            'from_uni_link' => $uni_link_id,
                        );
                    }
                }
                $ranges = array_merge($ranges, $uni_ranges);
            }

            if (empty($ranges)) {
                continue;
            }

            foreach ($ranges as $range) {
                $teryt = array();

                // get teryt info for group of computers connected to network node
                if (isset($teryt_cities[$range['location_city']])) {
                    $teryt = $teryt_cities[$range['location_city']];

                    if (!empty($range['location_street']) && isset($teryt_streets[$range['location_street']])) {
                        $teryt_street = $teryt_streets[$range['location_street']];

                        $teryt['address_cecha'] = $teryt_street['address_cecha'];
                        $teryt['address_ulica'] = $teryt_street['address_ulica'];
                        $teryt['address_symul'] = $teryt_street['address_symul'];
                    }
                }

                $teryt['address_budynek'] = $range['location_house'];

                $nodes = array();
                $uni_nodes = array();
                if (isset($uni_link)) {
                    unset($uni_link);
                }
                if ($customer_resources_as_operator_resources || empty($range['from_uni_link'])) {
                    // get info about computers connected to network node
                    $nodes = $DB->GetAll(
                        "SELECT
                            na.nodeid,
                            n.name AS nodename,
                            n.longitude,
                            n.latitude,
                            n.linktype,
                            n.linktechnology,
                            n.address_id, "
                        . $DB->GroupConcat(
                            "DISTINCT (CASE t.type WHEN " . SERVICE_INTERNET . " THEN 'INT'
                                WHEN " . SERVICE_PHONE . " THEN 'TEL'
                                WHEN " . SERVICE_TV . " THEN 'TV'
                                ELSE 'INT' END)"
                        ) . " AS servicetypes,
                          SUM(t.downceil) AS downstream,
                            SUM(t.upceil) AS upstream
                        FROM nodeassignments na
                        JOIN nodes n             ON n.id = na.nodeid
                        LEFT JOIN addresses addr ON addr.id = n.address_id
                        JOIN customers c ON c.id = n.ownerid
                        JOIN assignments a       ON a.id = na.assignmentid
                        JOIN tariffs t           ON t.id = a.tariffid
                        LEFT JOIN vassignmentsuspensions vas ON vas.suspension_assignment_id = a.id
                            AND vas.suspension_datefrom <= ?NOW?
                            AND (vas.suspension_dateto >= ?NOW? OR vas.suspension_dateto = 0)
                            AND a.datefrom <= ?NOW? AND (a.dateto >= ?NOW? OR a.dateto = 0)
                        WHERE n.ownerid IS NOT NULL
                            AND n.netdev IS NOT NULL
                            " . ($division ? ' AND c.divisionid = ' . $division : '') . "
                            AND n.netdev IN ?
                            AND n.linktype = ?
                            AND n.linktechnology = ?
                            AND addr.city_id = ?
                            " . (empty($range['location_street_name']) ? '' : ' AND addr.street = ' . $DB->Escape($range['location_street_name'])) . "
                            AND (addr.street_id = ? OR addr.street_id IS NULL)
                            AND addr.house = ?
                            AND a.commited = 1
                            AND a.period IN ?
                            AND a.datefrom < ?NOW?
                            AND (a.dateto = 0 OR a.dateto > ?NOW?)
                            AND vas.suspended IS NULL
                        GROUP BY na.nodeid, n.name, n.longitude, n.latitude, n.linktype, n.linktechnology, n.address_id",
                        array(
                            $netnode['netdevices'],
                            $range['linktype'],
                            $range['linktechnology'],
                            $range['location_city'],
                            $range['location_street'],
                            $range['location_house'],
                            array(YEARLY, HALFYEARLY, QUARTERLY, MONTHLY, DISPOSABLE),
                        )
                    );
                    if (empty($nodes)) {
                        $nodes = array();
                    }
                } elseif (!$customer_resources_as_operator_resources) {
                    // get info about computers or network devices connected to network node though customer network device
                    $uni_link_id = $range['from_uni_link'];
                    $uni_link = &$uni_links[$uni_link_id];

                    $uni_nodes = $DB->GetAll(
                        "SELECT
                            na.nodeid,
                            n.name AS nodename, "
                        . $uni_link['type'] . " AS linktype, "
                        . $uni_link['technology'] . " AS linktechnology,
                            n.address_id, "
                        . $uni_link['operator_netdevid'] . " AS netdevid, "
                        . $DB->GroupConcat(
                            "DISTINCT (CASE t.type WHEN " . SERVICE_INTERNET . " THEN 'INT'
                                WHEN " . SERVICE_PHONE . " THEN 'TEL'
                                WHEN " . SERVICE_TV . " THEN 'TV'
                                ELSE 'INT' END)"
                        ) . " AS servicetypes,
                            SUM(t.downceil) AS downstream,
                            SUM(t.upceil) AS upstream
                        FROM nodeassignments na
                        JOIN nodes n             ON n.id = na.nodeid
                        JOIN assignments a       ON a.id = na.assignmentid
                        JOIN tariffs t           ON t.id = a.tariffid
                        LEFT JOIN vassignmentsuspensions vas ON vas.suspension_assignment_id = a.id
                            AND vas.suspension_datefrom <= ?NOW?
                            AND (vas.suspension_dateto >= ?NOW? OR vas.suspension_dateto = 0)
                            AND a.datefrom <= ?NOW? AND (a.dateto >= ?NOW? OR a.dateto = 0)
                        JOIN netdevices nd ON nd.id = n.netdev
                        JOIN customers c ON c.id = nd.ownerid
                        WHERE n.id IN ?
                            " . ($division ? ' AND c.divisionid = ' . $division : '') . "
                            AND a.commited = 1
                            AND a.period IN ?
                            AND a.datefrom < ?NOW?
                            AND (a.dateto = 0 OR a.dateto > ?NOW?)
                            AND vas.suspended IS NULL
                        GROUP BY na.nodeid, n.name, n.linktype, n.linktechnology, n.address_id",
                        array(
                            $uni_link['nodes'],
                            array(YEARLY, HALFYEARLY, QUARTERLY, MONTHLY, DISPOSABLE),
                        )
                    );

                    if (empty($uni_nodes)) {
                        $uni_nodes = array();
                    }
                }
                $nodes = array_merge($nodes, $uni_nodes);

                if (empty($nodes)) {
                    continue;
                }

                // check if this is range with the same location as owning network node
                if ($range['location_city'] == $netnode['location_city']
                    && $range['location_street'] == $netnode['location_street']
                    && $range['location_house'] == $netnode['location_house']) {
                    $range_netbuilding = true;
                }

                $netrange = array(
                    'longitude' => '',
                    'latitude' => '',
                    'count' => 0,
                );

                foreach ($nodes as $node) {
                    if (empty($node['netdevid'])) {
                        if (isset($nodecoords[$node['nodeid']])) {
                            if (!strlen($netrange['longitude'])) {
                                $netrange['longitude'] = 0;
                            }
                            if (!strlen($netrange['latitude'])) {
                                $netrange['latitude'] = 0;
                            }
                            $netrange['longitude'] += $nodecoords[$node['nodeid']]['longitude'];
                            $netrange['latitude'] += $nodecoords[$node['nodeid']]['latitude'];
                            $netrange['count']++;
                        }
                    } elseif (!empty($uni_link) && isset($uni_link['longitude'], $uni_link['latitude'])) {
                        if (!strlen($netrange['longitude'])) {
                            $netrange['longitude'] = 0;
                        }
                        if (!strlen($netrange['latitude'])) {
                            $netrange['latitude'] = 0;
                        }
                        $netrange['longitude'] += $uni_link['longitude'];
                        $netrange['latitude'] += $uni_link['latitude'];
                        $netrange['count']++;
                    }
                }

                // calculate network range gps coordinates as all nodes gps coordinates mean value
                if ($netrange['count']) {
                    $netrange['longitude'] /= $netrange['count'];
                    $netrange['latitude'] /= $netrange['count'];
                }

                $range = array(
                    'terc' => $teryt['area_terc'] ?? '',
                    'simc' => $teryt['area_simc'] ?? '',
                    'ulic' => $teryt['address_symul'] ?? '',
                    'building' => isset($teryt['address_budynek']) ? str_replace(' ', '', $teryt['address_budynek']) : '',
                    'latitude' => (!isset($netrange['latitude']) || is_string($netrange['latitude']))
                    && (!isset($netnode['latitude']) || is_string($netnode['latitude']))
                        ? ''
                        : sprintf(
                            '%.6f',
                            !isset($netrange['latitude']) || is_string($netrange['latitude'])
                                ? $netnode['latitude']
                                : $netrange['latitude']
                        ),
                    'longitude' => (!isset($netrange['longitude']) || is_string($netrange['longitude']))
                    && (!isset($netnode['longitude']) || is_string($netnode['longitude']))
                        ? ''
                        : sprintf(
                            '%.6f',
                            !isset($netrange['longitude']) || is_string($netrange['longitude'])
                                ? $netnode['longitude']
                                : $netrange['longitude']
                        ),
                );

                foreach ($nodes as $node) {
                    if (empty($node['linktechnology'])) {
                        $range['medium'] = LINKTYPE_WIRE;
                        // 1 Gigabit Ethernet
                        $range['technology'] = 8;
                    } else {
                        $range['medium'] = mediaCodeByTechnology($node['linktechnology']);
                        $range['technology'] = $node['linktechnology'];
                    }

                    $servicetypes = array_flip(explode(',', $node['servicetypes']));

                    $range_access_props = array(
                        'fixed-internet' => isset($servicetypes['INT']) && $node['linktype'] != LINKTYPE_WIRELESS,
                        'wireless-internet' => isset($servicetypes['INT']) && $node['linktype'] == LINKTYPE_WIRELESS,
                        'tv' => isset($servicetypes['TV']),
                        'phone' => isset($servicetypes['TEL']),
                        'network-speed' => networkSpeedCode($node['downstream']),
                        'downstream' => $node['downstream'],
                    );

                    $range_key = implode(
                        '_',
                        array_filter(
                            array_merge(
                                $range,
                                array_map(
                                    function ($value) {
                                        if (is_bool($value)) {
                                            return $value ? '1' : '0';
                                        } else {
                                            return $value;
                                        }
                                    },
                                    $range_access_props
                                )
                            ),
                            function ($value, $key) {
                                return $key != 'latitude' && $key != 'longitude' && $key != 'downstream';
                            },
                            ARRAY_FILTER_USE_BOTH
                        )
                    );

                    if (!isset($netnode['ranges'][$range_key])) {
                        $range['count'] = 0;
                        $netnode['ranges'][$range_key] = array_merge($range, $range_access_props);
                    }
                    $netnode['ranges'][$range_key]['count']++;

                    if (!strlen($range['terc']) || !strlen($range['simc']) || !strlen($range['building'])) {
                        if (empty($node['netdevid'])) {
                            $error = array(
                                'id' => $node['nodeid'],
                                'name' => $node['nodename'],
                            );
                            if (empty($node['address_id'])) {
                                $error['address_id'] = true;
                            } else {
                                if (!strlen($range['terc'])) {
                                    $error['terc'] = true;
                                }
                                if (!strlen($range['simc'])) {
                                    $error['simc'] = true;
                                }
                                if (!strlen($range['building'])) {
                                    $error['location_house'] = true;
                                }
                            }
                            $errors['nodes'][] = $error;
                        } elseif (!empty($uni_link)) {
                            $error = array(
                                'id' => $uni_link['netdevid'],
                                'name' => $uni_link['netdevname'],
                                'customerid' => $uni_link['customerid'],
                            );
                            if (empty($uni_link['address_id'])) {
                                $error['address_id'] = true;
                            } else {
                                if (!strlen($range['terc'])) {
                                    $error['terc'] = true;
                                }
                                if (!strlen($range['simc'])) {
                                    $error['simc'] = true;
                                }
                                if (!strlen($range['building'])) {
                                    $error['location_house'] = true;
                                }
                            }
                            $errors['netdevices'][] = $error;
                        }
                    }

                    if (empty($node['netdevid'])) {
                        if (empty($node['longitude']) || empty($node['latitude'])) {
                            $errors['nodes'][] = array(
                                'id' => $node['nodeid'],
                                'name' => $node['nodename'],
                                'gps' => true,
                            );
                        }
                    } elseif (!empty($uni_link) && (empty($uni_link['longitude']) || empty($uni_link['latitude']))) {
                        $errors['netdevices'][] = array(
                            'id' => $uni_link['netdevid'],
                            'name' => $uni_link['netdevname'],
                            'customerid' => $uni_link['customerid'],
                            'gps' => true,
                        );
                    } elseif (empty($range['longitude']) || empty($range['latitude'])) {
                        if (empty($node['netdevid'])) {
                            $errors['nodes'][] = array(
                                'id' => $node['nodeid'],
                                'name' => $node['nodename'],
                                'gps' => true,
                            );
                        } elseif (!empty($uni_link)) {
                            $errors['netdevices'][] = array(
                                'id' => $uni_link['netdevid'],
                                'name' => $uni_link['netdevname'],
                                'customerid' => $uni_link['customerid'],
                                'gps' => true,
                            );
                        }
                    }
                }

                $netnode['technologies'][$range['technology']] = $range['technology'];
            }
        }
    }
    unset($netnode);
} else {
    // save info about network ranges
    $nodes = $DB->GetAll(
        "SELECT
            na.nodeid,
            n.name,
            n.linktype,
            n.linktechnology,
            n.address_id,
            addr.city_id AS location_city,
            addr.street_id AS location_street,
            addr.house AS location_house, "
            . $DB->GroupConcat(
                "DISTINCT (CASE t.type WHEN " . SERVICE_INTERNET . " THEN 'INT'
                    WHEN " . SERVICE_PHONE . " THEN 'TEL'
                    WHEN " . SERVICE_TV . " THEN 'TV'
                    ELSE 'INT' END)"
            ) . " AS servicetypes,
            SUM(t.downceil) AS downstream,
            SUM(t.upceil) AS upstream
        FROM nodeassignments na
        JOIN nodes n             ON n.id = na.nodeid
        LEFT JOIN addresses addr ON addr.id = n.address_id
        " . ($division
            ? 'JOIN customers c ON c.id = n.ownerid'
            : '')
        . " JOIN assignments a       ON a.id = na.assignmentid
        JOIN tariffs t           ON t.id = a.tariffid
        LEFT JOIN vassignmentsuspensions vas ON vas.suspension_assignment_id = a.id
            AND vas.suspension_datefrom <= ?NOW?
            AND (vas.suspension_dateto >= ?NOW? OR vas.suspension_dateto = 0)
            AND a.datefrom <= ?NOW? AND (a.dateto >= ?NOW? OR a.dateto = 0)
        WHERE n.ownerid IS NOT NULL
            " . ($division ? ' AND c.divisionid = ' . $division : '') . "
            AND n.access = 1
            AND a.commited = 1
            AND a.period IN ?
            AND a.datefrom < ?NOW?
            AND (a.dateto = 0 OR a.dateto > ?NOW?)
            AND vas.suspended IS NULL
        GROUP BY na.nodeid,
            n.name,
            n.linktype,
            n.linktechnology,
            n.address_id,
            addr.city_id,
            addr.street_id,
            addr.house",
        array(
            array(YEARLY, HALFYEARLY, QUARTERLY, MONTHLY, DISPOSABLE),
        )
    );

    if (empty($nodes)) {
        $nodes = array();
    }

    $ranges = array();

    foreach ($nodes as $node) {
        $teryt = array();

        // get teryt info for group of computers connected to network node
        if (isset($teryt_cities[$node['location_city']])) {
            $teryt = $teryt_cities[$node['location_city']];

            if (!empty($node['location_street']) && isset($teryt_streets[$node['location_street']])) {
                $teryt_street = $teryt_streets[$node['location_street']];

                $teryt['address_cecha'] = $teryt_street['address_cecha'];
                $teryt['address_ulica'] = $teryt_street['address_ulica'];
                $teryt['address_symul'] = $teryt_street['address_symul'];
            }
        }

        $teryt['address_budynek'] = $node['location_house'];

        $netrange = array(
            'longitude' => '',
            'latitude' => '',
            'count' => 0,
        );

        if (isset($nodecoords[$node['nodeid']])) {
            if (!strlen($netrange['longitude'])) {
                $netrange['longitude'] = 0;
            }
            if (!strlen($netrange['latitude'])) {
                $netrange['latitude'] = 0;
            }
            $netrange['longitude'] += $nodecoords[$node['nodeid']]['longitude'];
            $netrange['latitude'] += $nodecoords[$node['nodeid']]['latitude'];
            $netrange['count']++;
        }

        // calculate network range gps coordinates as all nodes gps coordinates mean value
        if ($netrange['count']) {
            $netrange['longitude'] /= $netrange['count'];
            $netrange['latitude'] /= $netrange['count'];
        }

        if (!isset($teryt['area_terc']) || !isset($teryt['area_simc']) || !isset($node['location_house']) || !strlen($node['location_house'])) {
            $error = array(
                'id' => $node['nodeid'],
                'name' => $node['name'],
            );
            if (empty($node['address_id'])) {
                $error['address_id'] = true;
            } else {
                if (!isset($teryt['area_terc'])) {
                    $error['terc'] = true;
                }
                if (!isset($teryt['area_simc'])) {
                    $error['simc'] = true;
                }
                if (!isset($node['location_house']) || !strlen($node['location_house'])) {
                    $error['location_house'] = true;
                }
            }
            $errors['nodes'][] = $error;
        }

        if (!strlen($netrange['longitude']) || !strlen($netrange['latitude'])) {
            $errors['nodes'][] = array(
                'id' => $node['nodeid'],
                'name' => $node['name'],
                'gps' => true,
            );
        }

        $range = array(
            'terc' => $teryt['area_terc'] ?? '',
            'simc' => $teryt['area_simc'] ?? '',
            'ulic' => $teryt['address_symul'] ?? '',
            'building' => isset($teryt['address_budynek']) ? str_replace(' ', '', $teryt['address_budynek']) : '',
            'latitude' => !isset($netrange['latitude']) || is_string($netrange['latitude'])
                ? ''
                : sprintf(
                    '%.6f',
                    !isset($netrange['latitude']) || is_string($netrange['latitude'])
                        ? $netnode['latitude']
                        : $netrange['latitude']
                ),
            'longitude' => !isset($netrange['longitude']) || is_string($netrange['longitude'])
                ? ''
                : sprintf(
                    '%.6f',
                    !isset($netrange['longitude']) || is_string($netrange['longitude'])
                        ? $netnode['longitude']
                        : $netrange['longitude']
                ),
            'count' => 1,
        );

        if (empty($node['linktechnology'])) {
            $range['medium'] = LINKTYPE_WIRE;
            // 1 Gigabit Ethernet
            $range['technology'] = 8;
        } else {
            $range['medium'] = mediaCodeByTechnology($node['linktechnology']);
            $range['technology'] = $node['linktechnology'];
        }

        $servicetypes = array_flip(explode(',', $node['servicetypes']));

        $range_access_props = array(
            'fixed-internet' => isset($servicetypes['INT']) && $node['linktype'] != LINKTYPE_WIRELESS,
            'wireless-internet' => isset($servicetypes['INT']) && $node['linktype'] == LINKTYPE_WIRELESS,
            'tv' => isset($servicetypes['TV']),
            'phone' => isset($servicetypes['TEL']),
            'network-speed' => networkSpeedCode($node['downstream']),
            'downstream' => $node['downstream'],
        );

        if ($aggregate_customer_services) {
            $range_key = implode(
                '_',
                array_filter(
                    array_merge(
                        $range,
                        array_map(
                            function ($value) {
                                if (is_bool($value)) {
                                    return $value ? '1' : '0';
                                } else {
                                    return $value;
                                }
                            },
                            $range_access_props
                        )
                    ),
                    function ($value, $key) {
                        return $key != 'latitude' && $key != 'longitude' && $key != 'downstream';
                    },
                    ARRAY_FILTER_USE_BOTH
                )
            );

            if (isset($ranges[$range_key])) {
                $ranges[$range_key]['count']++;
            } else {
                $ranges[$range_key] = array_merge($range, $range_access_props);
            }
        } else {
            $ranges[] = array_merge($range, $range_access_props);
        }
    }
}

$url = ConfigHelper::getConfig('system.url');

if ($report_type == 'full') {
    function analyze_network_tree($netnode_name, $netnode_netdevid, $netnode_netlinkid, $same_netnode, $current_netnode_name, $netnode_name_stack, &$netnodes, &$netdevices, &$netlinks)
    {
        static $url,
        $processed_netnodes = array(),
        $processed_netdevices = array(),
        $processed_netlinks = array(),
        $detect_loops = null;

        if (!isset($url)) {
            $url = ConfigHelper::getConfig('system.url');
            $detect_loops = $GLOBALS['detect_loops'];
        }

        $netnode = &$netnodes[$netnode_name];

        if (isset($netnode_netlinkid)) {
            $processed_netlinks[$netnode_netlinkid] = true;
        }

        if ($detect_loops && (!$same_netnode && isset($netnode_name_stack[$netnode_name]) || $same_netnode && isset($processed_netdevices[$netnode_netdevid]))) {
            $netdev_stack = array();
            $back_trace = debug_backtrace();
            $last_netdevid = null;

            foreach ($back_trace as $bt) {
                if ($bt['function'] != __FUNCTION__) {
                    continue;
                }
                $bt_netnode_name = $bt['args'][0];
                $bt_netnode_netdevid = $bt['args'][1];
                $netdev_stack[] = array(
                    'name' => $bt_netnode_name,
                    'location' => $netnodes[$bt_netnode_name]['location_city_name']
                        . (empty($netnodes[$bt_netnode_name]['location_street_name']) ? '' : ', ' . $netnodes[$bt_netnode_name]['location_street_name'])
                        . ' ' . $netnodes[$bt_netnode_name]['location_house'],
                    'id' => $netnodes[$bt_netnode_name]['real_id'] ?? null,
                    'netdevid' => $bt_netnode_netdevid,
                );
                if (isset($last_netdevid) && $last_netdevid == $bt_netnode_netdevid) {
                    break;
                }
                if (!isset($last_netdevid)) {
                    $last_netdevid = $bt_netnode_netdevid;
                }
            }

            if (!$same_netnode && isset($netnode_name_stack[$netnode_name])) {
                foreach (array_reverse($netdev_stack) as $idx => $ns) {
                    if ($ns['name'] == $netnode_name) {
                        break;
                    }
                }
                if (!empty($idx)) {
                    $netdev_stack = array_slice($netdev_stack, 0, -$idx);
                }

                echo trans('Detected network loop on network node <strong>\'$a\'</strong>!', $netnode_name) . '<br>';
            } else {
                echo trans('Detected network loop on network device <strong>\'$a\'</strong>!', $netdevices[$bt_netnode_netdevid]['name']) . '<br>';
            }

            echo trans('Network devices which belong to this loop:') . '<br><br>';

            foreach (array_reverse($netdev_stack) as $nd) {
                echo trans(
                    '<!uke-pit>network node: <strong>$a</strong>',
                    isset($nd['id']) ? '<a href="' . $url . '?m=netnodeinfo&id=' . $nd['id'] . '">' . $nd['name'] . '</a>' : $nd['name']
                ) . '<br>';
                if (isset($nd['comment'])) {
                    echo '&nbsp;&nbsp;&nbsp;&nbsp;komentarz: <span style="color: red; font-weight: bold;">' . $nd['comment'] . '</span><br>';
                }
                echo '&nbsp;&nbsp;&nbsp;&nbsp;' . trans('<!uke-pit>location: $a', $nd['location']) . '<br>';
                echo '&nbsp;&nbsp;&nbsp;&nbsp;typ: ' . ($netnode['mode'] == 1 ? 'punkt elastyczności' : 'węzeł') . '<br>';
                echo '&nbsp;&nbsp;&nbsp;&nbsp;' . trans(
                    '<!uke-pit>device: $a (#$b)',
                    '<a href="' . $url . '?m=netdevinfo&id=' . $nd['netdevid'] . '">' . $netdevices[$nd['netdevid']]['name'] . '</a>',
                    $nd['netdevid']
                ) . '<br>';
                echo '<br>';
            }
            die;

            return;
        }

        if (!$same_netnode) {
            $netnode_name_stack[$netnode_name] = true;
            $processed_netnodes[$netnode_name] = true;

            if (isset($netlinks[$netnode_netdevid][$netnode_netlinkid]['technology'])) {
                $netnodes[$netnode_name]['uplink_technology'] = $netlinks[$netnode_netdevid][$netnode_netlinkid]['technology'];
            }
        }

        $processed_netdevices[$netnode_netdevid] = true;

        if (!$same_netnode) {
            if ($netnode['mode'] == 2) {
                $current_netnode_name = $netnode_name;
            } else {
                $netnode['parent_netnodename'] = $current_netnode_name;
                if (!isset($netnodes[$current_netnode_name]['technologies'])) {
                    $netnodes[$current_netnode_name]['technologies'] = array();
                }
                foreach ($netnode['technologies'] as $technology) {
                    $netnodes[$current_netnode_name]['technologies'][$technology] = $technology;
                }
            }
        }

        if (!empty($netlinks[$netnode_netdevid])) {
            foreach ($netlinks[$netnode_netdevid] as $netlinkid => $netlink) {
                $netdevice = $netdevices[$netlink['netdevid']];

                if (!isset($processed_netlinks[$netlinkid])) {
                    if ($netnode_name == $netdevice['netnodename']) {
                        $netnodes[$netnode_name]['local_technologies'][$netlink['technology']] = $netlink['technology'];
                    }

                    analyze_network_tree(
                        $netdevice['netnodename'],
                        $netdevice['id'],
                        $netlinkid,
                        $netnode_name == $netdevice['netnodename'],
                        $current_netnode_name,
                        $netnode_name_stack,
                        $netnodes,
                        $netdevices,
                        $netlinks
                    );
                }
            }
        }

        return $processed_netnodes;
    }

    foreach ($netnodes as $netnodename => $netnode) {
        if ($netnodename == $root_netnode_name && $netnode['mode'] != 2) {
            echo trans(
                '<!uke-pit>Root network node \'$a\' does not contain any active network devices!',
                empty($netnode['real_id'])
                    ? '<strong>' . $netnodename . '</strong>'
                    : '<a href="?m=netnodeinfo&id=' . $netnode['id'] . '"><strong>'
                    . $netnodename . ' (#' . $netnode['real_id'] . ')</strong></a>'
            ) . '<br>';
            foreach ($netnode['netdevices'] as $netdevid) {
                echo trans('<!uke-pit>Passive network devices:') . '<br>';
                echo trans(
                    '<!uke-pit>device: $a (#$b)',
                    '<a href="' . $url . '?m=netdevinfo&id=' . $netdevid . '">' . $netdevices[$netdevid]['name'] . '</a>',
                    $netdevid
                ) . '<br>';
            }

            die;
        }

        $netnode['local_technologies'] = $netnode['technologies'];
    }

    $processed_netnodes = analyze_network_tree($root_netnode_name, $root_netdevice_id, null, false, $root_netnode_name, array(), $netnodes, $netdevices, $all_netlinks);

    foreach ($netnodes as $netnodename => $netnode) {
        if ($netnode['mode'] != 2 && !isset($netnode['parent_netnodename'])
            && $verify_feeding_netnodes_of_flexibility_points
            && (isset($processed_netnodes[$netnodename]) || $report_elements_outside_network_infrastructure)) {
            $errors['flexibility-points'][] = array(
                'name' => $netnodename,
                'location' => $netnode['location_street_name'] . ' ' . $netnode['location_house'] . ', '
                    . (strlen($netnode['location_zip']) ? $netnode['location_zip'] . ' ' : '')
                    . $netnode['location_city_name'],
            );
        }
    }
}

$tmp_netlinkpoints = $DB->GetAll(
    'SELECT *
    FROM netlinkpoints
    ORDER BY id'
);
if (empty($tmp_netlinkpoints)) {
    $tmp_netlinkpoints = array();
}
$netlinkpoints = array();
foreach ($tmp_netlinkpoints as $netlinkpoint) {
    $netlinkid = $netlinkpoint['netlinkid'];
    $netlinkpointid = $netlinkpoint['id'];

    if (!isset($netlinkpoints[$netlinkid])) {
        $netlinkpoints[$netlinkid] = array();
    }

    $netlinkpoints[$netlinkid][$netlinkpointid] = $netlinkpoint;
}
unset($tmp_netlinkpoints);

if ($report_type == 'full') {
    //prepare info about network links (only between different network nodes)
    $processed_netlinks = array();
    $netlinks = array();
    if ($netdevices) {
        foreach ($netdevices as $netdevice) {
            $ndnetlinks = $DB->GetAll(
                "SELECT
                    nl.id,
                    nl.src,
                    nl.dst,
                    nl.type,
                    nl.speed,
                    nl.technology,
                    nl.routetype,
                    nl.linecount,
                    (CASE src WHEN ? THEN (CASE WHEN srcrs.license IS NULL THEN dstrs.license ELSE srcrs.license END)
                        ELSE (CASE WHEN dstrs.license IS NULL THEN srcrs.license ELSE dstrs.license END) END) AS license,
                    (CASE src WHEN ? THEN (CASE WHEN srcrs.frequency IS NULL THEN dstrs.frequency ELSE srcrs.frequency END)
                        ELSE (CASE WHEN dstrs.frequency IS NULL THEN srcrs.frequency ELSE dstrs.frequency END) END) AS frequency
                FROM netlinks nl
                JOIN netdevices ndsrc ON ndsrc.id = nl.src
                JOIN netdevices nddst ON nddst.id = nl.dst
                LEFT JOIN netradiosectors srcrs ON srcrs.id = nl.srcradiosector
                LEFT JOIN netradiosectors dstrs ON dstrs.id = nl.dstradiosector
                WHERE (src = ?" . ($customer_resources_as_operator_resources ? '' : ' AND nddst.ownerid IS NULL') . ")
                    OR (dst = ?" . ($customer_resources_as_operator_resources ? '' : ' AND ndsrc.ownerid IS NULL') . ")",
                array(
                    $netdevice['id'],
                    $netdevice['id'],
                    $netdevice['id'],
                    $netdevice['id'],
                )
            );
            if ($ndnetlinks) {
                foreach ($ndnetlinks as $netlink) {
                    $netdevnetnodename = $netdevs[$netdevice['id']];
                    $netdevnetnode = $netnodes[$netdevnetnodename];
                    $srcnetnodename = $netdevs[$netlink['src']];
                    $dstnetnodename = $netdevs[$netlink['dst']];
                    $srcnetdevice = $netdevices[$netlink['src']];
                    $dstnetdevice = $netdevices[$netlink['dst']];
                    $srcnetnode = $netnodes[$srcnetnodename];
                    $dstnetnode = $netnodes[$dstnetnodename];
                    $netnodeids = array($netnodes[$srcnetnodename]['id'], $netnodes[$dstnetnodename]['id']);

                    sort($netnodeids);

                    $netnodelinkid = implode('_', $netnodeids);

                    if (!isset($processed_netlinks[$netnodelinkid])) {
                        $linkspeed = $netlink['speed'];
                        $speed = floor($linkspeed / $speed_unit_type);

                        $othernetnode = null;
                        $othernetdevice = null;

                        if ($netlink['src'] == $netdevice['id']) {
                            if ($netdevnetnodename != $dstnetnodename) {
                                if ($srcnetdevice['invproject'] == $dstnetdevice['invproject']
                                    || strlen($srcnetdevice['invproject']) || strlen($dstnetdevice['invproject'])) {
                                    $invproject = $srcnetdevice['invproject'];
                                } else {
                                    $invproject = '';
                                }
                                if ($srcnetdevice['status'] == $dstnetdevice['status']) {
                                    $status = $srcnetdevice['status'];
                                } elseif ($srcnetdevice['status'] == 2 || $dstnetdevice['status'] == 2) {
                                    $status = 2;
                                } elseif ($srcnetdevice['status'] == 1 || $dstnetdevice['status'] == 1) {
                                    $status = 1;
                                }

                                $processed_netlinks[$netnodelinkid] = true;

                                $foreign = $netnodes[$netdevnetnodename]['ownership'] == 2 && $dstnetnode['ownership'] < 2
                                    || $netnodes[$netdevnetnodename]['ownership'] < 2 && $dstnetnode['ownership'] == 2;

                                $netlinks[] = array(
                                    'id' => $netlink['id'],
                                    'type' => $netlink['type'],
                                    'speed' => $speed,
                                    'technology' => $netlink['technology'],
                                    'src' => $netdevnetnodename,
                                    'dst' => $dstnetnodename,
                                    'license' => $netlink['license'] ?? '',
                                    'frequency' => $netlink['frequency'],
                                    'routetype' => $netlink['routetype'],
                                    'linecount' => $netlink['linecount'],
                                    'invproject' => $invproject,
                                    'status' => $status,
                                    'foreign' => $foreign,
                                );

                                $othernetnode = $dstnetnode;
                                $othernetdevice = $dstnetdevice;
                            }
                        } else if ($netdevnetnodename != $srcnetnodename) {
                            if ($srcnetdevice['invproject'] == $dstnetdevice['invproject']
                                || strlen($srcnetdevice['invproject']) || strlen($dstnetdevice['invproject'])) {
                                $invproject = $srcnetdevice['invproject'];
                            } else {
                                $invproject = '';
                            }
                            if ($srcnetdevice['status'] == $dstnetdevice['status']) {
                                $status = $netdevices[$netlink['src']]['status'];
                            } elseif ($srcnetdevice['status'] == 2 || $dstnetdevice['status'] == 2) {
                                $status = 2;
                            } elseif ($srcnetdevice['status'] == 1 || $dstnetdevice['status'] == 1) {
                                $status = 1;
                            }

                            $processed_netlinks[$netnodelinkid] = true;

                            $foreign = $netnodes[$netdevnetnodename]['ownership'] == 2 && $dstnetnode['ownership'] < 2
                                || $netnodes[$netdevnetnodename]['ownership'] < 2 && $dstnetnode['ownership'] == 2;

                            $netlinks[] = array(
                                'id' => $netlink['id'],
                                'type' => $netlink['type'],
                                'speed' => $speed,
                                'technology' => $netlink['technology'],
                                'src' => $netdevnetnodename,
                                'dst' => $srcnetnodename,
                                'license' => $netlink['license'] ?? '',
                                'frequency' => $netlink['frequency'],
                                'routetype' => $netlink['routetype'],
                                'linecount' => $netlink['linecount'],
                                'invproject' => $invproject,
                                'status' => $status,
                                'foreign' => $foreign,
                            );

                            $othernetnode = $srcnetnode;
                            $othernetdevice = $srcnetdevice;
                        }

                        if ($validate_wireless_links && isset($othernetnode) && $netlink['type'] == LINKTYPE_WIRELESS) {
                            $error = array(
                                'srcid' => $netdevice['id'],
                                'srcname' => $netdevice['name'],
                                'srcnetnode' => $netdevnetnode,
                                'dstid' => $othernetdevice['id'],
                                'dstname' => $othernetdevice['name'],
                                'dstnetnode' => $othernetnode,
                            );
                            if ($netdevnetnode['mode'] == 1) {
                                $error['srcerror'] = true;
                            }
                            if ($othernetnode['mode'] == 1) {
                                $error['dsterror'] = true;
                            }
                            if (isset($error['srcerror']) || isset($error['dsterror'])) {
                                $errors['netlinks'][] = $error;
                            }
                        }
                    }
                }
            }
        }
    }
}

$stop = false;

foreach (array('netnodes', 'netdevices', 'nodes', 'netlinks', 'flexibility-points') as $errorous_resource) {
    if (!empty($errors[$errorous_resource])) {
        foreach ($errors[$errorous_resource] as $error) {
            if ($errorous_resource == 'netnodes') {
                if (!isset($processed_netnodes[$error['name']])) {
                    continue;
                }
                $error_message = '<!uke-pit>Network node "$a" (#$b) has missed properties: $c';
                $url_prefix = '?m=netnodeinfo&id=';
            } elseif ($errorous_resource == 'netdevices') {
                if (empty($error['customerid']) && !isset($processed_netnodes[$netdevs[$error['id']]])) {
                    continue;
                }
                $error_message = '<!uke-pit>Network device "$a" (#$b) has missed properties: $c';
                $url_prefix = '?m=netdevinfo&id=';
            } elseif ($errorous_resource == 'nodes') {
                $error_message = '<!uke-pit>Node "$a" (#$b) has missed properties: $c';
                $url_prefix = '?m=nodeinfo&id=';
            } elseif ($errorous_resource == 'netlinks') {
                $error_message = '<!uke-pit>Wireless link can connect only network nodes, but it connects network device "$a" (#$b) located in $c "$d" with network device "$e" (#$f) located in $g "$h"';

                $srcnetnode = $error['srcnetnode'];
                $dstnetnode = $error['dstnetnode'];

                echo trans(
                    $error_message,
                    '<a href="' . '?m=netdevinfo&id=' . $error['srcid'] . '">' . $error['srcname'] . '</a>',
                    $error['srcid'],
                    trans(isset($error['srcerror']) ? '<!uke-pit>flexibility point' : '<!uke-pit>network node'),
                    (isset($srcnetnode['real_id']) ? '<a href="?m=netnodeinfo&id=' . $srcnetnode['real_id'] . '">' : '')
                        . (isset($error['srcerror']) ? 'P' : 'W') . '-' . $srcnetnode['name']
                        . (isset($srcnetnode['real_id']) ? '</a>' : ''),
                    '<a href="' . '?m=netdevinfo&id=' . $error['dstid'] . '">' . $error['dstname'] . '</a>',
                    $error['dstid'],
                    trans(isset($error['dsterror']) ? '<!uke-pit>flexibility point' : '<!uke-pit>network node'),
                    (isset($dstnetnode['real_id']) ? '<a href="?m=netnodeinfo&id=' . $dstnetnode['real_id'] . '">' : '')
                        . (isset($error['dsterror']) ? 'P' : 'W') . '-' . $dstnetnode['name']
                        . (isset($dstnetnode['real_id']) ? '</a>' : '')
                ) . '<br>';

                $stop = true;

                continue;
            } elseif ($errorous_resource == 'flexibility-points') {
                $error_message = '<!uke-pit>Flexibility point \'$a\' with location \'$b\' does not have defined feeding network node!';

                echo trans(
                    $error_message,
                    'P-' . (strlen($error['name']) ? $error['name'] : 'BEZ-NAZWY'),
                    $error['location']
                ) . '<br>';

                $stop = true;
            }
            $missed_properties = array();
            if ($validate_teryt) {
                if (($errorous_resource == 'nodes' || $errorous_resource == 'netdevices') && !empty($error['address_id'])) {
                    $missed_properties[] = trans('<!uke-pit>explicitly assigned address');
                } else {
                    if (isset($error['terc'])) {
                        $missed_properties[] = trans('<!uke-pit>TERC');
                    }
                    if (isset($error['simc'])) {
                        $missed_properties[] = trans('<!uke-pit>SIMC');
                    }
                }
            }
            if ($validate_building_numbers && isset($error['location_building'])) {
                $missed_properties[] = trans('<!uke-pit>building number');
            }
            if ($validate_gps && isset($error['gps'])) {
                $missed_properties[] = trans('<!uke-pit>GPS coordinates');
            }

            switch ($errorous_resource) {
                case 'netnodes':
                    if (isset($error['type'])) {
                        $missed_properties[] = trans('<!uke-pit>network node type out of allowed values');
                    }
                    break;
            }

            if (empty($missed_properties)) {
                continue;
            }
            $stop = true;
            echo trans($error_message, '<a href="' . $url_prefix . $error['id'] . '">' . $error['name'] . '</a>', $error['id'], implode(', ', $missed_properties)) . '<br>';
        }
    }
}
if ($stop) {
    die;
}

if (!$summary_only) {
    if ($report_type == 'full') {
        $w_buffer = 'we01_id_wezla,we02_tytul_do_wezla,we03_id_podmiotu_obcego,we04_terc,we05_simc,we06_ulic,'
            . 'we07_nr_porzadkowy,we08_szerokosc,we09_dlugosc,we10_medium_transmisyjne,we11_bsa,we12_technologia_dostepowa,'
            . 'we13_uslugi_transmisji_danych,we14_mozliwosc_zwiekszenia_liczby_interfejsow,we15_finansowanie_publ,'
            . 'we16_numery_projektow_publ,we17_infrastruktura_o_duzym_znaczeniu,we18_typ_interfejsu,we19_udostepnianie_ethernet' . EOL;

        $pe_buffer = 'pe01_id_pe,pe02_typ_pe,pe03_id_wezla,pe04_pdu,pe05_terc,pe06_simc,pe07_ulic,pe08_nr_porzadkowy,'
            . 'pe09_szerokosc,pe10_dlugosc,pe11_medium_transmisyjne,pe12_technologia_dostepowa,'
            . 'pe13_mozliwosc_swiadczenia_uslug,pe14_finansowanie_publ,pe15_numery_projektow_publ' . EOL;
    }

    $ua_buffer = '"ua01_id_punktu_adresowego","ua02_id_pe","ua03_id_po","ua04_terc","ua05_simc","ua06_ulic",'
        . '"ua07_nr_porzadkowy","ua08_szerokosc",ua09_dlugosc,ua10_medium_dochodzace_do_pa,ua11_technologia_dostepowa,'
        . 'ua12_instalacja_telekom,ua13_medium_instalacji_budynku,ua14_technologia_dostepowa,"ua15_identyfikacja_uslugi",'
        . '"ua16_dostep_stacjonarny","ua17_dostep_stacjonarny_bezprzewodowy","ua18_telewizja_cyfrowa","ua19_radio",'
        . '"ua20_usluga_telefoniczna","ua21_predkosc_uslugi_td","ua22_liczba_uzytkownikow_uslugi_td"' . EOL;
}

if ($report_type == 'full') {
    foreach ($netnodes as $netnodename => &$netnode) {
        if (!empty($netnode['uplink_technology'])) {
            $netnode['technologies'][$netnode['uplink_technology']] = $netnode['uplink_technology'];
            if (!empty($netnode['parent_netnodename'])) {
                $netnodes[$netnode['parent_netnodename']]['technologies'][$netnode['uplink_technology']] = $netnode['uplink_technology'];
            }
        }
        $netnode['ethernet_technologies'] = array_filter(
            array_unique($netnode['local_technologies']),
            function ($technology) use ($pit_ethernet_technologies) {
                return isset($pit_ethernet_technologies[$technology]);
            }
        );
    }
    unset($netnode);

    $used_foreigners = array();
    $range_keys = array();

    foreach ($netnodes as $netnodename => &$netnode) {
        if (!$summary_only) {
            if (!isset($processed_netnodes[$netnodename]) && !$report_elements_outside_network_infrastructure) {
                continue;
            }

            $media = array();
            foreach ($netnode['technologies'] as $technology) {
                $mediaCode = mediaCodeByTechnology($technology);
                if (!isset($media[$mediaCode])) {
                    $media[$mediaCode] = array();
                }
                $media[$mediaCode][$technology] = $technology;
            }

            if ($netnode['mode'] == 2) {
                if (strlen($netnode['coowner']) && !empty($netnode['ownership'])) {
                    $used_foreigners[$netnode['coowner']] = true;
                }

                $data = array(
                    'we01_id_wezla' => '',
                    'we02_tytul_do_wezla' => strlen($netnode['coowner']) && !empty($netnode['ownership']) ? 'Węzeł współdzielony z innym podmiotem' : 'Węzeł własny',
                    'we03_id_podmiotu_obcego' => strlen($netnode['coowner']) && !empty($netnode['ownership']) ? 'PO-' . $netnode['coowner'] : '',
                    'we04_terc' => $netnode['area_terc'] ?? '',
                    'we05_simc' => $netnode['area_simc'] ?? '',
                    'we06_ulic' => $netnode['address_symul'] ?? '',
                    'we07_nr_porzadkowy' => str_replace(' ', '', $netnode['address_budynek']),
                    'we08_szerokosc' => $netnode['latitude'] ?? '',
                    'we09_dlugosc' => $netnode['longitude'] ?? '',
                    'we10_medium' => '',
                    'we11_bsa' => 'Nie',
                    'we12_technologia_dostepowa' => '',
                    'we13_uslugi_transmisji_danych' => '',
                    'we14_mozliwosc_zwiekszenia_liczby_interfejsow' => 'Nie',
                    'we15_finansowanie_publ' => empty($netnode['invproject']) ? 'Nie' : 'Tak',
                    'we16_numery_projektow_publ' => empty($netnode['invproject'])
                        ? ''
                        : implode(';', $netnode['invproject']),
                    'we17_infrastruktura_o_duzym_znaczeniu' => 'Nie',
                    'we18_typ_interfejsu' => empty($netnode['ethernet_technologies'])
                        ? ''
                        : implode(
                            ';',
                            array_map(
                                function ($technology) {
                                    return ethernetInterfaceCodeByTechnology($technology);
                                },
                                $netnode['ethernet_technologies']
                            )
                        ),
                    'we19_udostepnianie_ethernet' => empty($netnode['ethernet_technologies']) ? '' : 'Nie',
                );

                $first = true;
                foreach ($media as $mediaCode => $technology) {
                    if (!isset($netnode['fullname'])) {
                        $netnode['fullname'] = (strlen($netnodename) ? $netnodename : 'BEZ-NAZWY') . '-' . $mediaCode;
                    }

                    $data['we01_id_wezla'] = 'W-' . (strlen($netnodename) ? $netnodename : 'BEZ-NAZWY') . '-' . $mediaCode;
                    $data['we10_medium'] = mediaNameByCode($mediaCode);
                    $data['we12_technologia_dostepowa'] = empty($netnode['technologies'])
                        ? ''
                        : implode(
                            ';',
                            array_map(
                                function ($technology) {
                                    return technologyName($technology);
                                },
                                array_filter(
                                    $netnode['technologies'],
                                    function ($technology) use ($mediaCode) {
                                        $technologyMediaCode = mediaCodeByTechnology($technology);
                                        return $technologyMediaCode == $mediaCode;
                                    }
                                )
                            )
                        );

                    $first = false;

                    $w_buffer .= to_csv($data) . EOL;
                }
            } else {
                $data = array(
                    'pe01_id_pe' => '',
                    'pe02_typ_pe' => pointCodeByNetNodeType($netnode['type']),
                    'pe03_id_wezla' => '',
                    'pe04_pdu' => '',
                    'pe05_terc' => $netnode['area_terc'] ?? '',
                    'pe06_simc' => $netnode['area_simc'] ?? '',
                    'pe07_ulic' => $netnode['address_symul'] ?? '',
                    'pe08_nr_porzadkowy' => str_replace(' ', '', $netnode['address_budynek']),
                    'pe09_szerokosc' => $netnode['latitude'] ?? '',
                    'pe10_dlugosc' => $netnode['longitude'] ?? '',
                    'pe11_medium_transmisyjne' => '',
                    'pe12_technologia_dostepowa' => '',
                    'pe13_mozliwosc_swiadczenia_uslug' => empty($netnode['technologies']) ? '' : '09',
                    'pe14_finansowanie_publ' => empty($netnode['invproject']) ? 'Nie' : 'Tak',
                    'pe15_numery_projektow_publ' => empty($netnode['invproject'])
                        ? ''
                        : implode(';', $netnode['invproject']),
                );

                $first = true;
                foreach ($media as $mediaCode => $technology) {
                    if (!isset($netnode['fullname'])) {
                        $netnode['fullname'] = (strlen($netnodename) ? $netnodename : 'BEZ-NAZWY') . '-' . $mediaCode;
                    }

                    $data['pe01_id_pe'] = 'P-' . (strlen($netnodename) ? $netnodename : 'BEZ-NAZWY') . '-' . $mediaCode;
                    $data['pe03_id_wezla'] = isset($netnode['parent_netnodename']) ? 'W-' . $netnode['parent_netnodename'] . '-' . $mediaCode : '';

                    $access_media = array();
                    foreach ($netnode['ranges'] as $range_key => $range) {
                        $access_media[$range['medium']] = true;
                    }

                    $data['pe04_pdu'] = isset($access_media[$mediaCode]) ? 'Tak' : 'Nie';

                    $data['pe11_medium_transmisyjne'] = mediaNameByCode($mediaCode);
                    $data['pe12_technologia_dostepowa'] = empty($netnode['technologies'])
                        ? ''
                        : implode(
                            ';',
                            array_map(
                                function ($technology) {
                                    return technologyName($technology);
                                },
                                array_filter(
                                    $netnode['technologies'],
                                    function ($technology) use ($mediaCode) {
                                        $technologyMediaCode = mediaCodeByTechnology($technology);
                                        return $technologyMediaCode == $mediaCode;
                                    }
                                )
                            )
                        );

                    $first = false;

                    $pe_buffer .= to_csv($data) . EOL;
                }
            }

            if (!empty($netnode['ranges'])) {
                foreach ($netnode['ranges'] as $range_key => $range) {
                    if ($netnode['mode'] == 2) {
                        $new_pe = $netnodes[$netnodename];
                        $new_pe['mode'] = 1;
                        $new_pe['parent_netnodename'] = $netnodename;
                        $new_netnodename = 'V-' . (strlen($netnodename) ? $netnodename : 'BEZ-NAZWY');
                        $netnodes[$new_netnodename] = $new_pe;
                        $processed_netnodes[$new_netnodename] = true;
                    } else {
                        $service_name = array();

                        if ($range['fixed-internet']) {
                            $service_name[] = 'INT';
                        }
                        if ($range['wireless-internet']) {
                            $service_name[] = 'WINT';
                        }
                        if ($range['tv']) {
                            $service_name[] = 'TV';
                        }
                        if ($range['phone']) {
                            $service_name[] = 'TEL';
                        }

                        $service_name[] = round($range['downstream'] / $speed_unit_type);

                        if (isset($range_keys[$range_key])) {
                            $range_keys[$range_key]++;
                            $range_key .= '-' . $range_keys[$range_key];
                        } else {
                            $range_keys[$range_key] = 0;
                        }

                        $data = array(
                            'ua01_id_punktu_adresowego' => $range_key,
                            'ua02_id_pe' => 'P-' . (strlen($netnodename) ? $netnodename : 'BEZ-NAZWY') . '-' . $range['medium'],
                            'ua03_id_po' => '',
                            'ua04_terc' => $range['terc'],
                            'ua05_simc' => $range['simc'],
                            'ua06_ulic' => $range['ulic'],
                            'ua07_nr_porzadkowy' => $range['building'],
                            'ua08_szerokosc' => $range['latitude'],
                            'ua09_dlugosc' => $range['longitude'],
                            'ua10_medium_dochodzace_do_pa' => mediaNameByCode($range['medium']),
                            'ua11_technologia_dostepowa' => technologyName($range['technology']),
                            'ua12_instalacja_telekom' => '',
                            'ua13_medium_instalacji_budynku' => '',
                            'ua14_technologia_dostepowa' => '',
                            //'ua15_identyfikacja_uslugi' => implode('-', $service_name),
                            'ua15_identyfikacja_uslugi' => $range_key,
                            'ua16_dostep_stacjonarny' => $range['fixed-internet'] ? 'Tak' : 'Nie',
                            'ua17_dostep_stacjonarny_bezprzewodowy' => $range['wireless-internet'] ? 'Tak' : 'Nie',
                            'ua18_telewizja_cyfrowa' => $range['tv'] ? 'Tak' : 'Nie',
                            'ua19_radio' => 'Nie',
                            'ua20_usluga_telefoniczna' => $range['phone'] ? 'Tak' : 'Nie',
                            'ua21_predkosc_uslugi_td' => $range['network-speed'],
                            'ua22_liczba_uzytkownikow_uslugi_td' => $range['count'],
                        );

                        $ua_buffer .= to_csv($data) . EOL;
                    }
                }
            }
        } else {
            echo '<strong>' . (isset($netnode['real_id']) ? '<a href="' . $url . '?m=netnodeinfo&id=' . $netnode['real_id'] . '">' . $netnodename . '</a>' : $netnodename) . '</strong>:<br>';
            echo '&nbsp;&nbsp;&nbsp;&nbsp;lokalizacja: ' . $netnode['location_city_name'] . (empty($netnode['location_street_name']) ? '' : ', ' . $netnode['location_street_name']) . ' ' . $netnode['location_house'] . '<br>';
            echo '&nbsp;&nbsp;&nbsp;&nbsp;typ: ' . ($netnode['mode'] == 1 ? 'punkt elastyczności' : 'węzeł') . '<br>';
            echo '&nbsp;&nbsp;&nbsp;&nbsp;obecny w drzewie: ';

            if (isset($processed_netnodes[$netnodename])) {
                echo '<span style="color: green; font-weight: bold;">tak</span>';
            } else {
                echo '<span style="color: red; font-weight: bold;">nie</span>';
            }
            echo '<br>';

            if ($netnode['mode'] == 1) {
                echo '&nbsp;&nbsp;&nbsp;&nbsp;zasilany z węzła: <strong>' . ($netnode['parent_netnodename'] ?? '-') . '</strong><br>';
            }

            echo '&nbsp;&nbsp;&nbsp;&nbsp;technologie dostępu:<br>';
            if (empty($netnode['technologies'])) {
                echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(brak)<br>';
            } else {
                foreach ($netnode['technologies'] as $technology) {
                    $technologyname = technologyName($technology);
                    $mediaName = mediaNameByTechnology($technology);

                    echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $technologyname . ' (' . $mediaName . ')<br>';
                }
            }

            if ($netnode['mode'] == 2) {
                echo '&nbsp;&nbsp;&nbsp;&nbsp;technologie ethernetowe w węźle:<br>';
                if (empty($netnode['ethernet_technologies'])) {
                    echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(brak)<br>';
                } else {
                    foreach ($netnode['ethernet_technologies'] as $technology) {
                        $technologyname = technologyName($technology);
                        $mediaName = mediaNameByTechnology($technology);

                        echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $technologyname . ' (' . $mediaName . ')<br>';
                    }
                }
            }

            /*
                echo '&nbsp;&nbsp;&nbsp;&nbsp;zasięgi: ';
                if (empty($netnode['ranges'])) {
                    echo '-';
                } else {
                    echo nl2br(print_r($netnode['ranges'], true));
                }
                echo '<br>';
            */
            echo '<br>';
        }
    }
    unset($netnode);

    $po_buffer = 'po01_id_podmiotu_obcego,po02_nip_pl,po03_nip_nie_pl' . EOL;
    foreach ($foreigners as $name => $foreigner) {
        if (isset($used_foreigners[$name])) {
            $data = array(
                // alternatively $foreingerid can be used
                'po01_id_podmiotu_obcego' => 'PO-' . $foreigner,
                'po02_nip_pil' => '',
                'po03_nip_nie_pl' => '',
            );
            $po_buffer .= to_csv($data) . EOL;
        }
    }
} elseif (!$summary_only) {
    if (!empty($ranges)) {
        foreach ($ranges as $range_key => $range) {
            $service_name = array();

            if ($range['fixed-internet']) {
                $service_name[] = 'INT';
            }
            if ($range['wireless-internet']) {
                $service_name[] = 'WINT';
            }
            if ($range['tv']) {
                $service_name[] = 'TV';
            }
            if ($range['phone']) {
                $service_name[] = 'TEL';
            }

            $service_name[] = round($range['downstream'] / $speed_unit_type);

            $data = array(
                'ua01_id_punktu_adresowego' => $aggregate_customer_services ? $range_key : ($range_key + 1),
                'ua02_id_pe' => '',
                'ua03_id_po' => '',
                'ua04_terc' => $range['terc'],
                'ua05_simc' => $range['simc'],
                'ua06_ulic' => $range['ulic'],
                'ua07_nr_porzadkowy' => $range['building'],
                'ua08_szerokosc' => $range['latitude'],
                'ua09_dlugosc' => $range['longitude'],
                'ua10_medium_dochodzace_do_pa' => mediaNameByCode($range['medium']),
                'ua11_technologia_dostepowa' => technologyName($range['technology']),
                'ua12_instalacja_telekom' => '',
                'ua13_medium_instalacji_budynku' => '',
                'ua14_technologia_dostepowa' => '',
                //'ua15_identyfikacja_uslugi' => implode('-', $service_name),
                'ua15_identyfikacja_uslugi' => $range_key,
                'ua16_dostep_stacjonarny' => $range['fixed-internet'] ? 'Tak' : 'Nie',
                'ua17_dostep_stacjonarny_bezprzewodowy' => $range['wireless-internet'] ? 'Tak' : 'Nie',
                'ua18_telewizja_cyfrowa' => $range['tv'] ? 'Tak' : 'Nie',
                'ua19_radio' => 'Nie',
                'ua20_usluga_telefoniczna' => $range['phone'] ? 'Tak' : 'Nie',
                'ua21_predkosc_uslugi_td' => $range['network-speed'],
                'ua22_liczba_uzytkownikow_uslugi_td' => $range['count'],
            );

            $ua_buffer .= to_csv($data) . EOL;
        }
    }
}
unset($teryt_cities);
unset($teryt_streets);

if ($report_type == 'full') {
    if (!$summary_only) {
        $lk_buffer = 'lk01_id_lk,lk02_id_punktu_poczatkowego,lk03_punkty_zalamania,lk04_id_punktu_koncowego,'
            . 'lk05_medium_transmisyjne,lk06_rodzaj_linii_kablowej,lk07_liczba_wlokien,lk08_liczba_wlokien_wykorzystywanych,'
            . 'lk09_liczba_wlokien_udostepnienia,lk10_finansowanie_publ,lk11_numery_projektow_publ,'
            . 'lk12_infrastruktura_o_duzym_znaczeniu' . EOL;

        $lb_buffer = 'lb01_id_lb,lb02_id_punktu_poczatkowego,lb03_id_punktu_koncowego,lb04_medium_transmisyjne,'
            . 'lb05_nr_pozwolenia_radiowego,lb06_pasmo_radiowe,lb07_system_transmisyjny,lb08_przepustowosc,'
            . 'lb09_mozliwosc_udostepniania' . EOL;

        // save info about network lines
        if ($netlinks) {
            foreach ($netlinks as $netlink) {
                $technology = $netlink['technology'];

                if ($netnodes[$netlink['src']]['id'] != $netnodes[$netlink['dst']]['id']) {
                    $srcnetnode = $netnodes[$netlink['src']];
                    $dstnetnode = $netnodes[$netlink['dst']];

                    if (!isset($srcnetnode['fullname']) || !isset($dstnetnode['fullname'])) {
                        continue;
                    }

                    $srcnetnodename = $srcnetnode['fullname'];
                    $dstnetnodename = $dstnetnode['fullname'];

                    if ($netlink['type'] == LINKTYPE_WIRELESS) {
                        if (!$technology) {
                            $technology = 101;
                        }

                        $frequency = $netlink['frequency'];
                        if (empty($frequency)) {
                            $frequency = 5.5;
                        } else {
                            $frequency = floatval($frequency);
                        }

                        $data = array(
                            'lb01_id_lb' => 'LB-' . $netlink['id'],
                            'lb02_id_punktu_poczatkowego' => ($srcnetnode['mode'] == 1 ? 'P' : 'W') . '-' . $srcnetnodename,
                            'lb03_id_punktu_koncowego' => ($dstnetnode['mode'] == 1 ? 'P' : 'W') . '-' . $dstnetnodename,
                            'lb04_medium_transmisyjne' => strlen($netlink['license']) ? 'radiowe na częstotliwości wymagającej uzyskanie pozwolenia radiowego' : 'radiowe na częstotliwości ogólnodostępnej',
                            'lb05_nr_pozwolenia_radiowego' => $netlink['license'],
                            'lb06_pasmo_radiowe' => strlen($netlink['license']) ? '' : $frequency,
                            'lb07_system_transmisyjny' => radioTransmissionNameByTechnology($technology),
                            'lb08_przepustowosc' => networkSpeedCode($netlink['speed'] * $speed_unit_type),
                            'lb09_mozliwosc_udostepnienia' => 'Nie',
                        );

                        $lb_buffer .= to_csv($data) . EOL;
                    } else {
                        $points = array(
                            0 => array(
                                'longitude' => $srcnetnode['longitude'],
                                'latitude' => $srcnetnode['latitude'],
                            ),
                        );
                        if (isset($netlinkpoints[$netlink['id']])) {
                            foreach ($netlinkpoints[$netlink['id']] as $netlinkpoint) {
                                $points[$netlinkpoint['id']] = array(
                                    'longitude' => $netlinkpoint['longitude'],
                                    'latitude' => $netlinkpoint['latitude'],
                                );
                            }
                        }
                        $points[PHP_INT_MAX] = array(
                            'longitude' => $dstnetnode['longitude'],
                            'latitude' => $dstnetnode['latitude'],
                        );

                        if (isset($complete_breakdown_points) && count($points) == 2) {
                            $points[1] = array(
                                'longitude' => round(($points[0]['longitude'] + $points[PHP_INT_MAX]['longitude']) / 2, 5),
                                'latitude' => round(($points[0]['latitude'] + $points[PHP_INT_MAX]['latitude']) / 2, 5),
                            );
                        }

                        $data = array(
                            'lk01_id_lk' => 'LK-' . $netlink['id'],
                            'lk02_id_punktu_poczatkowego' => ($srcnetnode['mode'] == 1 ? 'P' : 'W') . '-' . $srcnetnodename,
                            'lk03_punkty_zalamania' => 'LINESTRING('
                                . implode(
                                    ',',
                                    array_map(
                                        function ($point) {
                                            return sprintf('%.6f %.6f', $point['longitude'], $point['latitude']);
                                        },
                                        $points
                                    )
                                ) . ')',
                            'lk04_id_punktu_koncowego' => ($dstnetnode['mode'] == 1 ? 'P' : 'W') . '-' . $dstnetnodename,
                            'lk05_medium_transmisyjne' => mediaNameByTechnology($technology),
                            'lk06_rodzaj_linii_kablowej' => routeTypeName($netlink['routetype']),
                            'lk07_liczba_wlokien' => $netlink['type'] == LINKTYPE_FIBER
                                ? (empty($netlink['linecount']) ? '2' : $netlink['linecount'])
                                : '',
                            'lk08_liczba_wlokien_wykorzystywanych' => $netlink['type'] == LINKTYPE_FIBER
                                ? (empty($netlink['linecount']) ? '2' : $netlink['linecount'])
                                : '',
                            'lk09_liczba_wlokien_udostepnienia' => '0',
                            'lk10_finansowanie_publ' => empty($netlink['invproject']) ? 'Nie' : 'Tak',
                            'lk11_numery_projektow_publ' => empty($netlink['invproject']) ? '' : $netlink['invproject'],
                            'lk12_infrastruktura_o_duzym_znaczeniu' => 'Nie',
                        );

                        $lk_buffer .= to_csv($data) . EOL;
                    }
                }
            }
        }
    }
}

if (!$summary_only) {
    if ($report_type == 'full') {
        $filename = tempnam(sys_get_temp_dir(), 'lms-pit') . '.zip';
        $zipname = 'lms-pit.zip';

        $zip = new ZipArchive();
        if ($zip->open($filename, ZipArchive::CREATE)) {
            $zip->addFromString('podmioty_obce.csv', $po_buffer);
            $zip->addFromString('wezly.csv', $w_buffer);
            $zip->addFromString('punkty_elastycznosci.csv', $pe_buffer);
            $zip->addFromString('uslugi_w_adresach.csv', $ua_buffer);
            $zip->addFromString('linie_bezprzewodowe.csv', $lb_buffer);
            $zip->addFromString('linie_kablowe.csv', $lk_buffer);

            $zip->close();
        }

        header('Content-type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipname . '"');
        header('Pragma: public');

        readfile($filename);
        unlink($filename);
    } else {
        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="lms-pit-ua.csv"');
        header('Pragma: public');

        die($ua_buffer);
    }
}
