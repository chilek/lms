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

if (!isset($_GET['action'])) {
    die;
}
$action = $_GET['action'];

switch ($action) {
    case 'get-geoportal-link':
        $latitude = filter_var($_GET['latitude'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $longitude = filter_var($_GET['longitude'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $geoportalApiUrl = ConfigHelper::getConfig('geoportal.api_url', 'https://mapy.geoportal.gov.pl/imap/Imgp_2.html?composition=default&bbox=');
        $geoportalZoomFactor = ConfigHelper::getConfig('geoportal.zoom_factor', 100);
        //converts URL containing coordinates in latitude/longitude format to Geoportal BBOX (EPSG:2180)
        $mapPosition = Utils::convertToGeoportalCoordinates($latitude, $longitude);
        $pos = array(
            'xMin' => number_format($mapPosition[0] - $geoportalZoomFactor, 6, '.', ''),
            'xMax' => number_format($mapPosition[0] + $geoportalZoomFactor, 6, '.', ''),
            'yMin' => number_format($mapPosition[1] - $geoportalZoomFactor, 6, '.', ''),
            'yMax' => number_format($mapPosition[1] + $geoportalZoomFactor, 6, '.', ''),
        );

        header('Location: ' . $geoportalApiUrl . $pos['xMin'] . ',' . $pos['yMin'] . ',' . $pos['xMax'] . ',' . $pos['yMax']);
        break;
    case 'get-sidusis-link':
        $latitude = filter_var($_GET['latitude'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $longitude = filter_var($_GET['longitude'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $sidusisApiUrl = ConfigHelper::getConfig('sidusis.web_url', 'https://internet.gov.pl/map/?center=');
        $sidusisZoomFactor = ConfigHelper::getConfig('sidusis.zoom_factor', 20);
        //converts URL containing coordinates in latitude/longitude format to SIDUSIS (EPSG:3857) format
        $mapPosition = Utils::convertToSidusisCoordinates($latitude, $longitude);
        $pos = array(
            'x' => number_format($mapPosition[0], 6, '.', ''),
            'y' => number_format($mapPosition[1], 6, '.', ''),
        );
        header('Location: ' . $sidusisApiUrl . $pos['x'] . ';' . $pos['y'] . '&zoom=' . $sidusisZoomFactor);
        break;
    case 'geocoding':
        if (!isset($_POST['address'])) {
            die('[]');
        }
        $address = json_decode(base64_decode($_POST['address']), true);
        if (empty($address)) {
            die('[]');
        }
        $coordinates = $LMS->getCoordinatesForAddress($address);
        if (empty($coordinates)) {
            die('[]');
        }
        die(json_encode($coordinates));
        break;
    default:
        break;
}
