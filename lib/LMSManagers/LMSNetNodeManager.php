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
				addr.location, addr.name as location_name, addr.id as address_id,
				addr.state as location_state_name, addr.state_id as location_state,
				lb.name AS location_borough_name, lb.id AS location_borough, lb.type AS location_borough_type,
				ld.name AS location_district_name, ld.id AS location_district,
				addr.zip as location_zip, addr.country_id as location_country,
				addr.city as location_city_name, addr.street as location_street_name,
				addr.city_id as location_city, addr.street_id as location_street,
				addr.house as location_house, addr.flat as location_flat,
				d.shortname AS division
			FROM netnodes n
				LEFT JOIN vaddresses addr ON n.address_id = addr.id
				LEFT JOIN invprojects p ON n.invprojectid = p.id
				LEFT JOIN divisions d ON d.id = n.divisionid
				LEFT JOIN location_cities lc ON lc.id = addr.city_id
				LEFT JOIN location_boroughs lb ON lb.id = lc.boroughid
				LEFT JOIN location_districts ld ON ld.id = lb.districtid
				LEFT JOIN location_states ls ON ls.id = ld.stateid
			WHERE n.id=?", array($id));
	}

	public function GetNetNodeList($search, $order) {
		list ($order, $dir) = sscanf($order, '%[^,],%s');
		($dir == 'desc') ? $dir = 'desc' : $dir = 'asc';

		switch ($order) {
			case 'id':
				$ostr = 'ORDER BY n.id';
				break;
			case 'name':
				$ostr = 'ORDER BY n.name';
				break;
			case 'type':
				$ostr = 'ORDER BY n.type';
				break;
			case 'status':
				$ostr = 'ORDER BY n.status';
				break;
			case 'lastinspectiontime':
				$ostr = 'ORDER BY n.lastinspectiontime';
			default:
				$ostr = 'ORDER BY n.name';
				break;
		}

		$where = array();
		foreach ($search as $key => $value) {
			$val = intval($value);
			if (!$val)
				continue;
			switch ($key) {
				case 'type':
					if ($val != -1)
						$where[] = 'n.type = ' . $val;
					break;
				case 'status':
					if ($val != -1)
						$where[] = 'n.status = ' . $val;
					break;
				case 'invprojectid':
					if ($val == -2)
						$where[] = 'n.invprojectid IS NULL';
					elseif ($val != -1)
						$where[] = 'n.invprojectid = ' . $val;
					break;
				case 'ownership':
					if ($val != -1)
						$where[] = 'n.ownership = ' . $val;
					break;
				case 'divisionid':
					if ($val != -1)
						$where[] = 'n.divisionid = ' . $val;
			}
		}

		$nlist = $this->db->GetAllByKey('SELECT n.id, n.name, n.type, n.status, n.invprojectid, n.info, n.lastinspectiontime, p.name AS project,
				n.divisionid,
				lb.id AS location_borough, lb.name AS location_borough_name, lb.type AS location_borough_type,
				ld.id AS location_district, ld.name AS location_district_name,
				ls.id AS location_state, ls.name AS location_state_name,
				addr.name as location_name,
				addr.city as location_city_name, addr.street as location_street_name,
				addr.city_id as location_city, addr.street_id as location_street,
				addr.house as location_house, addr.flat as location_flat
			FROM netnodes n
				LEFT JOIN addresses addr        ON addr.id = n.address_id
				LEFT JOIN invprojects p         ON (n.invprojectid = p.id)
				LEFT JOIN location_cities lc    ON lc.id = addr.city_id
				LEFT JOIN location_boroughs lb  ON lb.id = lc.boroughid
				LEFT JOIN location_districts ld ON ld.id = lb.districtid
				LEFT JOIN location_states ls    ON ls.id = ld.stateid '
			. (empty($where) ? '' : ' WHERE ' . implode(' AND ', $where)) . ' ' . $ostr . ' ' . $dir,
			'id');

		if ( $nlist ) {
			foreach ($nlist as &$netnode)
				$netnode['location'] = location_str(
					array('city_name'      => $netnode['location_city_name'],
						'location_house' => $netnode['location_house'],
						'location_flat'  => $netnode['location_flat'],
						'street_name'    => $netnode['location_street_name'])
				);
			unset($netnode);
		}

		$nlist['total']        = sizeof($nlist);
		$nlist['order']        = $order;
		$nlist['direction']    = $dir;

		return $nlist;
	}

	public function NetNodeAdd($netnodedata) {
		$location_manager = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);

		$address_id = $location_manager->InsertAddress( $netnodedata );

		$args = array(
			'name'            => $netnodedata['name'],
			'type'            => $netnodedata['type'],
			'status'          => $netnodedata['status'],
			'longitude'       => !empty($netnodedata['longitude']) ? str_replace(',', '.', $netnodedata['longitude']) : null,
			'latitude'        => !empty($netnodedata['latitude'])  ? str_replace(',', '.', $netnodedata['latitude'])  : null,
			'ownership'       => $netnodedata['ownership'],
			'coowner'         => $netnodedata['coowner'],
			'uip'             => $netnodedata['uip'],
			'miar'            => $netnodedata['miar'],
			'createtime'      => time(),
			'divisionid'      => !empty($netnodedata['divisionid']) ? $netnodedata['divisionid'] : null,
			'address_id'      => ($address_id >= 0 ? $address_id : null),
			'invprojectid'    => intval($netnodedata['invprojectid']) > 0 ? $netnodedata['invprojectid'] : null,
			'info'		  => $netnodedata['info'],
			'admcontact' => empty($netnodedata['admcontact']) ? null : $netnodedata['admcontact'],
			'lastinspectiontime' => $netnodedata['lastinspectiontime']
			);

		$this->db->Execute("INSERT INTO netnodes (" . implode(', ', array_keys($args))
			. ") VALUES (" . implode(', ', array_fill(0, count($args), '?')) . ")", array_values($args));

		return $netnodeid = $this->db->GetLastInsertID('netnodes');
	}

	public function NetNodeExists($id) {
		return $this->db->GetOne('SELECT id FROM netnodes WHERE id = ?', array($id)) > 0;
	}

	public function NetNodeDelete($id) {
		$addr_id = $this->db->GetOne('SELECT address_id FROM netnodes WHERE id = ?', array($id));

		if (!empty($addr_id)) {
			$this->db->Execute('DELETE FROM addresses WHERE id = ?', array($addr_id));
		}

		$this->db->Execute("DELETE FROM netnodes WHERE id=?", array($id));
	}

	public function NetNodeUpdate($netnodedata) {
		$location_manager = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);

		$args = array(
			'name'            => $netnodedata['name'],
			'type'            => $netnodedata['type'],
			'status'          => $netnodedata['status'],
			'longitude'       => !empty($netnodedata['longitude']) ? str_replace(',', '.', $netnodedata['longitude']) : null,
			'latitude'        => !empty($netnodedata['latitude'])  ? str_replace(',', '.', $netnodedata['latitude'])  : null,
			'ownership'       => $netnodedata['ownership'],
			'coowner'         => $netnodedata['coowner'],
			'uip'             => $netnodedata['uip'],
			'miar'            => $netnodedata['miar'],
			'divisionid'      => $netnodedata['divisionid'],
			'invprojectid'    => intval($netnodedata['invprojectid']) > 0 ? $netnodedata['invprojectid'] : null,
			'info'         	  => $netnodedata['info'],
			'admcontact'      => empty($netnodedata['admcontact']) ? null : $netnodedata['admcontact'],
			'lastinspectiontime' => date_to_timestamp($netnodedata['lastinspectiontime']),
		);

		// if address_id is set then update
		if ( isset($netnodedata['address_id']) ) {
			$location_manager->UpdateAddress( $netnodedata );
		} else {
		// else insert new address
			$addr_id = $location_manager->InsertAddress( $netnodedata );

			if ($addr_id >= 0)
				$args['address_id'] = $addr_id;
		}

		$this->db->Execute('UPDATE netnodes SET ' . implode(' = ?, ', array_keys($args)) . ' = ? WHERE id = ?',
			array_merge(array_values($args), array($netnodedata['id'])));
	}
}
