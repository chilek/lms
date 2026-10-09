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

function array_provider_filter($provider)
{
    static $all_providers = array(
        'google' => true,
        'siis' => true,
        'prg' => true,
        'osm' => true,
    );
    return isset($all_providers[$provider]);
}

function get_gps_coordinates($location, $latitude_selector, $longitude_selector)
{

    global $LMS;

    $DB = LMSDB::getInstance();

    $result = new xajaxResponse();

    if (isset($location['address_id'])) {
        $address = $LMS->GetAddress($location['address_id']);
        $address['city_name'] = $address['city'];
        $location['street'] = $address['street'];
        if (isset($address['simple_street_name'])) {
            $location['simple_street'] = $address['simple_street_name'];
        }
        $location['house'] = $address['house'];
        $location['flat'] = $address['flat'];
    } elseif (isset($location['city_id'])) {
        $address = $DB->GetRow('SELECT ls.name AS state_name,
				ld.name AS district_name, lb.name AS borough_name,
				lc.id AS city_id, lc.name AS city_name FROM location_cities lc
			JOIN location_boroughs lb ON lb.id = lc.boroughid
			JOIN location_districts ld ON ld.id = lb.districtid
			JOIN location_states ls ON ls.id = ld.stateid
			WHERE lc.id = ?', array($location['city_id']));
        $address['street_id'] = $location['street_id'];
        $address['country_name'] = 'Polska';
    } else {
        $address = array(
            'city_name' => $location['city'],
        );
        if (!empty($location['state_name'])) {
            $address['state_name'] = $location['state'];
        }
    }

    $providers = trim(ConfigHelper::getConfig('phpui.gps_coordinate_providers', 'google,osm,prg'));
    $providers = preg_split('/\s*[,|]\s*/', $providers);
    $providers = array_filter($providers, 'array_provider_filter');

    $found = false;

    $error = null;

    foreach ($providers as $provider) {
        if ($provider == 'google') {
            $location_string = (!empty($address['state_name']) ? $address['state_name'] . ', ' : '')
                . (!empty($address['district_name']) ? $address['district_name'] . ', ' : '')
                . (!empty($address['borough_name']) ? $address['borough_name'] . ', ' : '')
                . (isset($address['zip']) ? $address['zip'] . ' ' : '') . $address['city_name']
                . (!empty($location['street']) ? ', ' . $location['street'] : '')
                . (isset($location['house']) && mb_strlen($location['house']) ? ' ' . $location['house'] : '')
                . (isset($location['flat']) && mb_strlen($location['flat']) ? '/' . $location['flat'] : '');
            $geocode = geocode($location_string);
            if (!isset($geocode['status'])) {
                continue;
            }

            if ($geocode['status'] == 'OK') {
                $found = true;
                if ($geocode['accuracy'] == 'ROOFTOP') {
                    $result->script('
						$("' . $latitude_selector . '").val("' . $geocode['latitude'] . '");
						$("' . $longitude_selector . '").val("' . $geocode['longitude'] . '");
					');
                } else {
                    $result->script('
						var longitude = "' . $geocode['longitude'] . '";
						var latitude = "' . $geocode['latitude'] . '";
						confirmDialog($t("Determined GPS coordinates are not precise.\nDo you still want to use them?"),
						    $("' . $longitude_selector . '")).done(function() {
    							$("' . $latitude_selector . '").val(latitude);
    							$("' . $longitude_selector . '").val(longitude);
						});');
                }
                break;
            } else {
                $error = $geocode['status'] . ': ' . $geocode['error'];
            }
        } elseif ($provider == 'osm') {
            $params = array(
                'city' => $address['city_name'],
            );
            if (!empty($address['country_name'])) {
                $params['country'] = $address['country_name'];
            }
            if (!empty($address['state_name'])) {
                $params['state'] = $address['state_name'];
            }
            if (!empty($location['street'])) {
                $params['street'] = (isset($location['house']) && mb_strlen($location['house'])
                    ? $location['house'] . ' '
                    : ''
                    ) . (isset($location['simple_street']) ? $location['simple_street'] : '');
            } elseif (isset($location['house']) && mb_strlen($location['house'])) {
                $params['street'] = $location['house'];
            }
            if (isset($address['zip'])) {
                $params['postalcode'] = $address['zip'];
            }
            $geocode = osm_geocode($params);
            if (empty($geocode)
                || isset($geocode['latitude'], $geocode['longitude']) && (!strlen($geocode['latitude']) || !strlen($geocode['longitude']))) {
                continue;
            }

            $found = true;
            $result->script('
                $("' . $latitude_selector . '").val("' . $geocode['latitude'] . '");
                $("' . $longitude_selector . '").val("' . $geocode['longitude'] . '");
            ');

            break;
        } elseif (($provider == 'siis' || $provider == 'prg') && isset($address)) {
            $args = array(
                'city_id' => $address['city_id'],
            );
            if (!empty($address['street_id'])) {
                $args['street_id'] = $address['street_id'];
            }
            if (!empty($location['house'])) {
                $args['building_num'] = $location['house'];
            }
            $coordinates = $LMS->getCoordinatesForAddress($args);
            if (empty($coordinates)) {
                continue;
            }
            $found = true;
            $result->script('
				$("' . $latitude_selector . '").val("' . $coordinates['latitude'] . '");
				$("' . $longitude_selector . '").val("' . $coordinates['longitude'] . '");
			');

            break;
        }
    }

    if (!$found) {
        $result->script('
			$("' . $latitude_selector . '").addClass("lms-ui-warning").removeAttr("data-tooltip").attr("title",
				$t("Unable to determine GPS coordinates!") + "' . ($error ? '<br>' . $error : '') . '");
			$("' . $longitude_selector . '").addClass("lms-ui-warning").removeAttr("data-tooltip").attr("title",
				$t("Unable to determine GPS coordinates!") + "' . ($error ? '<br>' . $error : '') . '");
		');
    }

    return $result;
}

$LMS->RegisterXajaxFunction('get_gps_coordinates');
