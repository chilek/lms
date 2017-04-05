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

function get_gps_coordinates($location, $callback) {
	global $LMS;
	$DB = LMSDB::getInstance();

	$result = new xajaxResponse();

	if (isset($location['address_id'])) {
		$address = $LMS->GetAddress($location['address_id']);
		$location['street'] = $address['street'];
		$location['house'] = $address['house'];
		$location['flat'] = $address['flat'];
	} elseif (isset($location['city_id'])) {
		$address = $DB->GetRow('SELECT ls.name AS state_name,
				ld.name AS district_name, lb.name AS borough_name,
				lc.name AS city_name FROM location_cities lc
			JOIN location_boroughs lb ON lb.id = lc.boroughid
			JOIN location_districts ld ON ld.id = lb.districtid
			JOIN location_states ls ON ls.id = ld.stateid
			WHERE lc.id = ?', array($location['city_id']));
	} else {
		$address = array(
			'city_name' => $location['city'],
		);
		if (isset($location['state_name']) && !empty($location['state_name']))
			$address['state_name'] = $location['state'];
	}

	$location_string = (isset($address['state_name']) && !empty($address['state_name']) ? $address['state_name'] . ', ' : '')
		. (isset($address['district_name']) && !empty($address['district_name']) ? $address['district_name'] . ', ' : '')
		. (isset($address['borough_name']) && !empty($address['borough_name']) ? $address['borough_name'] . ', ' : '')
		. $address['city_name']
		. (isset($location['street']) && !empty($location['street']) ? ', ' . $location['street'] : '')
		. (isset($location['house']) && mb_strlen($location['house']) ? ' ' . $location['house'] : '')
		. (isset($location['flat']) && mb_strlen($location['flat']) ? '/' . $location['flat'] : '');
	$geocode = geocode($location_string);
	if ($geocode['status'] == 'OK' && $geocode['accuracy'] == 'ROOFTOP') {
		$result->assign('latitude', 'value', $geocode['latitude']);
		$result->assign('longitude', 'value', $geocode['longitude']);
	}

	return $result;
}

$LMS->RegisterXajaxFunction('get_gps_coordinates');

?>
