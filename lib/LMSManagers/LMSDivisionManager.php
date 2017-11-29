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
}
