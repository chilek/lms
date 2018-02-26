<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2018 LMS Developers
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

/**
 * LMSDivisionManager
 *
 */
class LMSDivisionManager extends LMSManager implements LMSDivisionManagerInterface {
	public function GetDivision($id) {
		return $this->db->GetRow('SELECT * FROM vdivisions WHERE id = ?', array($id));
	}

	public function GetDivisionByName($name) {
		return $this->db->GetRow('SELECT * FROM vdivisions WHERE shortname = ?', array($name));
	}

	public function GetDivisions($params = array()) {
		extract($params);

		if (isset($order) && is_null($order))
			$order = 'shortname,asc';

		list($order, $direction) = sscanf($order, '%[^,],%s');

		($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

		switch ($order) {
			case 'name':
				$sqlord = ' ORDER BY name';
				break;
			default:
				$sqlord = ' ORDER BY shortname';
				break;
		}

		return $this->db->GetAllByKey('SELECT * FROM vdivisions
			WHERE 1=1'
			. (isset($status) ? ' AND status = ' . intval($status) : '')
			. ($sqlord != '' ? $sqlord . ' ' . $direction : ''),
			'id');
	}

	public function AddDivision($division) {
		$lm = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);
		$address_id = $lm->InsertAddress($division);

		$args = array(
			'name'            => $division['name'],
			'shortname'       => $division['shortname'],
			'ten'             => $division['ten'],
			'regon'           => $division['regon'],
			'rbe'             => $division['rbe'],
			'rbename'         => $division['rbename'] ? $division['rbename'] : '',
			'telecomnumber'   => $division['telecomnumber'] ? $division['telecomnumber'] : '',
			'account'         => $division['account'],
			'inv_header'      => $division['inv_header'],
			'inv_footer'      => $division['inv_footer'],
			'inv_author'      => $division['inv_author'],
			'inv_cplace'      => $division['inv_cplace'],
			'inv_paytime'     => $division['inv_paytime'],
			'inv_paytype'     => $division['inv_paytype'] ? $division['inv_paytype'] : null,
			'description'     => $division['description'],
			'tax_office_code' => $division['tax_office_code'],
			'address_id'      => ($address_id >= 0 ? $address_id : null)
		);

		$this->db->Execute('INSERT INTO divisions (name, shortname,
			ten, regon, rbe, rbename, telecomnumber, account, inv_header, inv_footer, inv_author,
			inv_cplace, inv_paytime, inv_paytype, description, tax_office_code, address_id)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));

		$divisionid = $this->db->GetLastInsertID('divisions');

		if ($this->syslog) {
			$args[SYSLOG::RES_DIV] = $divisionid;
			$this->syslog->AddMessage(SYSLOG::RES_DIV, SYSLOG::OPER_ADD, $args);
		}

		return $divisionid;
	}

	public function DeleteDivision($id) {
		if ($this->db->GetOne('SELECT COUNT(*) FROM divisions', array($id)) != 1) {
			if ($this->syslog) {
				$countryid = $this->db->GetOne('SELECT country_id FROM vdivisions
				WHERE id = ?', array($id));
				$args = array(
					SYSLOG::RES_DIV => $id,
					SYSLOG::RES_COUNTRY => $countryid
				);
				$this->syslog->AddMessage(SYSLOG::RES_DIV, SYSLOG::OPER_DELETE, $args);
				$assigns = $this->db->GetAll('SELECT * FROM numberplanassignments WHERE divisionid = ?', array($id));
				if (!empty($assigns))
					foreach ($assigns as $assign) {
						$args = array(
							SYSLOG::RES_NUMPLANASSIGN => $assign['id'],
							SYSLOG::RES_NUMPLAN => $assign['planid'],
							SYSLOG::RES_DIV => $assign['divisionid'],
						);
						$this->syslog->AddMessage(SYSLOG::RES_NUMPLANASSIGN, SYSLOG::OPER_DELETE, $args);
					}
			}

			$this->db->Execute('DELETE FROM addresses a
				WHERE a.id = (SELECT address_id FROM divisions d WHERE d.id = ?)', array($id));
			$this->db->Execute('DELETE FROM divisions WHERE id=?', array($id));
		}
	}

	public function UpdateDivision($division) {
		$args = array(
			'name'        => $division['name'],
			'shortname'   => $division['shortname'],
			'ten'         => $division['ten'],
			'regon'       => $division['regon'],
			'rbe'         => $division['rbe'] ? $division['rbe'] : '',
			'rbename'     => $division['rbename'] ? $division['rbename'] : '',
			'telecomnumber'     => $division['telecomnumber'] ? $division['telecomnumber'] : '',
			'account'     => $division['account'],
			'inv_header'  => $division['inv_header'],
			'inv_footer'  => $division['inv_footer'],
			'inv_author'  => $division['inv_author'],
			'inv_cplace'  => $division['inv_cplace'],
			'inv_paytime' => $division['inv_paytime'],
			'inv_paytype' => $division['inv_paytype'] ? $division['inv_paytype'] : null,
			'description' => $division['description'],
			'status'      => !empty($division['status']) ? 1 : 0,
			'tax_office_code' => $division['tax_office_code'],
			SYSLOG::RES_DIV   => $division['id']
		);

		$this->db->Execute('UPDATE divisions SET name=?, shortname=?,
			ten=?, regon=?, rbe=?, rbename=?, telecomnumber=?, account=?, inv_header=?,
			inv_footer=?, inv_author=?, inv_cplace=?, inv_paytime=?,
			inv_paytype=?, description=?, status=?, tax_office_code = ?
			WHERE id=?', array_values($args));

		$lm = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);
		$lm->UpdateAddress($division);

		if ($this->syslog)
			$this->syslog->AddMessage(SYSLOG::RES_DIV, SYSLOG::OPER_UPDATE, $args);
	}
}
