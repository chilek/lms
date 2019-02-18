<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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
 */

$this->BeginTrans();

$all_streets = $this->GetAllByKey("SELECT lst.id, lst.name, lst.name2, lstt.name AS typestr
	FROM location_streets lst
	JOIN location_street_types lstt ON lstt.id = lst.typeid
	WHERE lst.name2 IS NOT NULL", 'id');

if (!empty($all_streets)) {
	$addresses = $this->GetAll("
		(
			SELECT a.id, a.street, a.street_id
				FROM addresses a
				JOIN customer_addresses ca ON ca.address_id = a.id
				JOIN location_streets lst ON lst.id = a.street_id
				WHERE a.street_id IS NOT NULL AND lst.name2 IS NOT NULL 
		) UNION (
			SELECT a.id, a.street, a.street_id
				FROM addresses a
				JOIN netdevices nd ON nd.address_id = a.id
				JOIN location_streets lst ON lst.id = a.street_id
				WHERE a.street_id IS NOT NULL AND lst.name2 IS NOT NULL
		) UNION (
			SELECT a.id, a.street, a.street_id
				FROM addresses a
				JOIN netnodes nn ON nn.address_id = a.id
				JOIN location_streets lst ON lst.id = a.street_id
				WHERE a.street_id IS NOT NULL AND lst.name2 IS NOT NULL
		)
	");

	if (!empty($addresses))
		foreach ($addresses as $address) {
			$address_id = $address['id'];
			$street_id = $address['street_id'];
			if (isset($all_streets[$street_id])) {
				$street_name = $all_streets[$street_id]['typestr'] . ' '
					. $all_streets[$street_id]['name2'] . ' ' . $all_streets[$street_id]['name'];
				$this->Execute("UPDATE addresses
					SET street = ?
					WHERE id = ?",
					array($street_name, $address_id));
			}
		}
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2018070200', 'dbversion'));

$this->CommitTrans();

?>
