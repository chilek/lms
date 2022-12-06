<?php

/**
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

$divisionid = intval($_POST['division']);
if (empty($divisionid)) {
    die;
}

$division = $LMS->GetDivision($divisionid);
if (empty($division)) {
    die;
}

if (!class_exists('ZipArchive')) {
    die('Error: ZipArchive class not found! Install php-zip module.');
}

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
    JOIN location_cities lc ON lc.id = b.city_id
    JOIN location_boroughs lb ON lb.id = lc.boroughid
    JOIN location_districts ld ON ld.id = lb.districtid
    JOIN location_states ls ON ls.id = ld.stateid
    LEFT JOIN location_streets lst ON lst.id = b.street_id
    LEFT JOIN location_street_types t ON t.id = lst.typeid'
);

if (empty($buildings)) {
    die('Network range database is empty!');
}

define('OPERATOR_REPRESENTATIVE_ID', 1);

$fh = fopen('php://temp', 'r+');

fputcsv(
    $fh,
    array(
        'DI',
        $division['name'],
        $division['telecomnumber'],
        preg_replace('/[^0-9]/', '', $division['ten']),
    )
);

fputcsv(
    $fh,
    array(
        'PO',
        OPERATOR_REPRESENTATIVE_ID,
        $division['email'],
        $phone,
        ConfigHelper::getConfig('sidusis.operator_offer_url', 'http://firma.pl/offer/')
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
            $linktype = 'radiowe';
            break;
        case LINKTYPE_FIBER:
            $linktype = 'światłowodowe';
            break;
    }

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

$filename = tempnam(sys_get_temp_dir(), 'lms-sidusis') . '.zip';
$zipname = 'lms-sidusis.zip';

$zip = new ZipArchive();
if ($zip->open($filename, ZipArchive::CREATE)) {
    $zip->addFromString('lms-sidusis.csv', $content);
    $zip->close();
}

header('Content-type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipname . '"');
header('Pragma: public');

readfile($filename);
unlink($filename);
