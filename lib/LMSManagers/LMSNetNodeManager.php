<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2017 LMS Developers
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

class LMSNetNodeManager extends LMSManager implements LMSNetNodeManagerInterface {

	public function GetNetNode($id) {
		return $this->db->GetRow("SELECT n.*, p.name AS projectname,
				addr.name as location_name, addr.id as address_id,
				addr.state as location_state_name, addr.state_id as location_state,
				lb.name AS location_borough_name, lb.id AS location_borough, lb.type AS location_borough_type,
				ld.name AS location_district_name, ld.id AS location_district,
				addr.zip as location_zip, addr.country_id as location_country,
				addr.city as location_city_name, addr.street as location_street_name,
				addr.city_id as location_city, addr.street_id as location_street,
				addr.house as location_house, addr.flat as location_flat,
				d.shortname AS division
			FROM netnodes n
				LEFT JOIN addresses addr ON n.address_id = addr.id
				LEFT JOIN invprojects p ON n.invprojectid = p.id
				LEFT JOIN divisions d ON d.id = n.divisionid
				LEFT JOIN location_cities lc ON lc.id = addr.city_id
				LEFT JOIN location_boroughs lb ON lb.id = lc.boroughid
				LEFT JOIN location_districts ld ON ld.id = lb.districtid
				LEFT JOIN location_states ls ON ls.id = ld.stateid
			WHERE n.id=?", array($id));
	}

}
