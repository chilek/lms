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
				addr.postoffice AS location_postoffice,
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

	public function GetNetNodes() {
		return $this->db->GetAll("SELECT * FROM netnodes ORDER BY name");
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
				n.divisionid, d.shortname AS division, longitude, latitude, ownership, coowner, uip, miar,
				lc.ident AS location_city_ident, lst.ident AS location_street_ident,
				lb.id AS location_borough, lb.name AS location_borough_name, lb.ident AS location_borough_ident,
				lb.type AS location_borough_type,
				ld.id AS location_district, ld.name AS location_district_name, ld.ident AS location_district_ident,
				ls.id AS location_state, ls.name AS location_state_name, ls.ident AS location_state_ident,
				addr.location, addr.name as location_name,
				addr.city as location_city_name, addr.street as location_street_name,
				addr.city_id as location_city, addr.street_id as location_street,
				addr.house as location_house, addr.flat as location_flat
			FROM netnodes n
				LEFT JOIN divisions d ON d.id = n.divisionid
				LEFT JOIN vaddresses addr        ON addr.id = n.address_id
				LEFT JOIN invprojects p         ON (n.invprojectid = p.id)
				LEFT JOIN location_streets lst  ON lst.id = addr.street_id
				LEFT JOIN location_cities lc    ON lc.id = addr.city_id
				LEFT JOIN location_boroughs lb  ON lb.id = lc.boroughid
				LEFT JOIN location_districts ld ON ld.id = lb.districtid
				LEFT JOIN location_states ls    ON ls.id = ld.stateid '
			. (empty($where) ? '' : ' WHERE ' . implode(' AND ', $where)) . ' ' . $ostr . ' ' . $dir,
			'id');

		if ( $nlist ) {
			foreach ($nlist as &$netnode) {
				$netnode['terc'] = empty($netnode['location_state_ident']) ? null
					: $netnode['location_state_ident'] . $netnode['location_district_ident']
						. $netnode['location_borough_ident'] . $netnode['location_borough_type'];
				$netnode['simc'] = empty($netnode['location_city_ident']) ? null : $netnode['location_city_ident'];
				$netnode['ulic'] = empty($netnode['location_street_ident']) ? null : $netnode['location_street_ident'];
			}
			unset($netnode);
		}

		$nlist['total']        = count($nlist);
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

		return $this->db->Execute("DELETE FROM netnodes WHERE id=?", array($id));
	}

	public function NetNodeUpdate($netnodedata) {
		$location_manager = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);

		$args = array();
		if (array_key_exists('name', $netnodedata))
			$args['name'] = $netnodedata['name'];
		if (array_key_exists('type', $netnodedata))
			$args['type'] = $netnodedata['type'];
		if (array_key_exists('status', $netnodedata))
			$args['status'] = $netnodedata['status'];
		if (array_key_exists('longitude', $netnodedata))
			$args['longitude'] = empty($netnodedata['longitude']) ? null : str_replace(',', '.', $netnodedata['longitude']);
		if (array_key_exists('latitude', $netnodedata))
			$args['latitude'] = empty($netnodedata['latitude']) ? null : str_replace(',', '.', $netnodedata['latitude']);
		if (array_key_exists('ownership', $netnodedata))
			$args['ownership'] = $netnodedata['ownership'];
		if (array_key_exists('coowner', $netnodedata))
			$args['coowner'] = $netnodedata['coowner'];
		if (array_key_exists('uip', $netnodedata))
			$args['uip'] = $netnodedata['uip'];
		if (array_key_exists('miar', $netnodedata))
			$args['miar'] = $netnodedata['miar'];
		if (array_key_exists('divisionid', $netnodedata))
			$args['divisionid'] = $netnodedata['divisionid'];
		if (array_key_exists('invprojectid', $netnodedata))
			$args['invprojectid'] = intval($netnodedata['invprojectid']) > 0 ? $netnodedata['invprojectid'] : null;
		if (array_key_exists('info', $netnodedata))
			$args['info'] = $netnodedata['info'];
		if (array_key_exists('admcontact', $netnodedata))
			$args['admcontact'] = empty($netnodedata['admcontact']) ? null : $netnodedata['admcontact'];
		if (array_key_exists('lastinspectiontime', $netnodedata))
			$args['lastinspectiontime'] = date_to_timestamp($netnodedata['lastinspectiontime']);

		// if address_id is set then update
		if (isset($netnodedata['address_id']))
			$location_manager->UpdateAddress( $netnodedata );
		else {
		// else insert new address
			$addr_id = $location_manager->InsertAddress( $netnodedata );

			if ($addr_id >= 0)
				$args['address_id'] = $addr_id;
		}

		if (empty($args))
			return null;

		return $this->db->Execute('UPDATE netnodes SET ' . implode(' = ?, ', array_keys($args)) . ' = ? WHERE id = ?',
			array_merge(array_values($args), array($netnodedata['id'])));
	}
}
