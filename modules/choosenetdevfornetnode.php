<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

$layout['pagetitle'] = trans('Select net devices');

$p = isset($_GET['p']) ? $_GET['p'] : '';
if ($p == 'main') {
	$list = $DB->GetAll("SELECT n.name, n.id, n.producer, n.model, n.ownerid,
			addr.city as location_city_name, addr.street as location_street_name,
			addr.house as location_house, addr.flat as location_flat
		FROM netdevices n
			LEFT JOIN addresses addr ON n.address_id = addr.id
		WHERE (n.netnodeid IS NULL) OR (n.netnodeid <> ?) AND n.netnodeid IS NULL
		ORDER BY n.name", array($_GET['id']));

	if ($list) {
		global $LMS;

		foreach ($list as $k=>$acc) {
			$tmp = array('city_name'     => $acc['location_city_name'],
						'location_house' => $acc['location_house'],
						'location_flat'  => $acc['location_flat'],
						'street_name'    => $acc['location_street_name']);

			$location = location_str( $tmp );

			if ( $location ) {
				$list[$k]['location'] = $location;
			} else if ( $acc['ownerid'] ) {
				$list[$k]['location'] = $LMS->getAddressForCustomerStuff( $acc['ownerid'] );
			}
		}
	}

	$list['total'] = count($list);
	$SMARTY->assign('netdevlist', $list);
}

$SMARTY->assign('objectid', $_GET['id']);
$SMARTY->assign('part', $p);
$SMARTY->display('choose/choosenetdevfornetnode.html');

?>
