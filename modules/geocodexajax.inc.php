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

function array_provider_filter($provider) {
	static $all_providers = array(
		'google' => true,
		'siis' => true,
	);
	return isset($all_providers[$provider]);
}

function get_gps_coordinates($location, $latitude_selector, $longitude_selector) {

	global $LMS;

	$DB = LMSDB::getInstance();

	$result = new xajaxResponse();

	if (isset($location['address_id'])) {
		$address = $LMS->GetAddress($location['address_id']);
		$address['city_name'] = $address['city'];
		$location['street'] = $address['street'];
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
	} else {
		$address = array(
			'city_name' => $location['city'],
		);
		if (isset($location['state_name']) && !empty($location['state_name']))
			$address['state_name'] = $location['state'];
	}

	$providers = trim(ConfigHelper::getConfig('phpui.gps_coordinate_providers', 'google,siis'));
	$providers = preg_split('/\s*[,|]\s*/', $providers);
	$providers = array_filter($providers, 'array_provider_filter');

	$found = false;

	foreach ($providers as $provider)
		if ($provider == 'google') {
			$location_string = (isset($address['state_name']) && !empty($address['state_name']) ? $address['state_name'] . ', ' : '')
				. (isset($address['district_name']) && !empty($address['district_name']) ? $address['district_name'] . ', ' : '')
				. (isset($address['borough_name']) && !empty($address['borough_name']) ? $address['borough_name'] . ', ' : '')
				. $address['city_name']
				. (isset($location['street']) && !empty($location['street']) ? ', ' . $location['street'] : '')
				. (isset($location['house']) && mb_strlen($location['house']) ? ' ' . $location['house'] : '')
				. (isset($location['flat']) && mb_strlen($location['flat']) ? '/' . $location['flat'] : '');
			$geocode = geocode($location_string);
			if ($geocode['status'] == 'OK') {
				$found = true;
				if ($geocode['accuracy'] == 'ROOFTOP') {
					$result->script('
						$("' . $latitude_selector . '").val("' . $geocode['latitude'] . '");
						$("' . $longitude_selector . '").val("' . $geocode['longitude'] . '");
					');
					break;
				} else {
					$result->script('
						var longitude = "' . $geocode['longitude'] . '";
						var latitude = "' . $geocode['latitude'] . '";
						if (confirm($t("Determined gps coordinates are not precise.\nDo you still want to use them?"))) {
							$("' . $latitude_selector . '").val(latitude);
							$("' . $longitude_selector . '").val(longitude);
						}'
					);
					break;
				}
			}
		} elseif ($provider == 'siis' && isset($address) && isset($address['city_id'])
			&& !empty($address['city_id']) && $DB->GetOne('SELECT id FROM location_buildings LIMIT 1')) {
			$args = array(
				'city_id' => $address['city_id'],
			);
			if (!empty($address['street_id']))
				$args['street_id'] = $address['street_id'];
			if (!empty($location['house']))
				$args['building_num'] = $location['house'];
			$buildings = $DB->GetAll('SELECT * FROM location_buildings
				WHERE ' . implode(' = ? AND ', array_keys($args)) . ' = ?', array_values($args));
			if (empty($buildings) || count($buildings) > 1)
				break;
			$found = true;
			$result->script('
				$("' . $latitude_selector . '").val("' . $buildings[0]['latitude'] . '");
				$("' . $longitude_selector . '").val("' . $buildings[0]['longitude'] . '");
			');
		}

	if (!$found)
		$result->script('
			$("' . $latitude_selector . '").addClass("lms-ui-warning").removeAttr("data-tooltip").attr("title",
				$t("Unable to determine gps coordinates!"));
			$("' . $longitude_selector . '").addClass("lms-ui-warning").removeAttr("data-tooltip").attr("title",
				$t("Unable to determine gps coordinates!"));
		');

	return $result;
}

$LMS->RegisterXajaxFunction('get_gps_coordinates');

?>
