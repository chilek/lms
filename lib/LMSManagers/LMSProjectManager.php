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
 * LMSProjectManager
 *
 */
class LMSProjectManager extends LMSManager implements LMSProjectManagerInterface {
	public function CleanupProjects() {
		if (ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.auto_remove_investment_project', true)))
			$this->db->Execute("DELETE FROM invprojects WHERE type <> ? AND id NOT IN
				(SELECT DISTINCT invprojectid FROM netdevices WHERE invprojectid IS NOT NULL
					UNION SELECT DISTINCT invprojectid FROM vnodes WHERE invprojectid IS NOT NULL
					UNION SELECT DISTINCT invprojectid FROM netnodes WHERE invprojectid IS NOT NULL)",
				array(INV_PROJECT_SYSTEM));
	}

	public function GetProjects() {
		return $this->db->GetAllByKey('SELECT ip.id, ip.name, ip.divisionid, 
				n.ncount AS nodes, nn.ncount AS netnodes
			FROM invprojects ip
			LEFT JOIN (
				SELECT invprojectid, COUNT(*) AS ncount FROM nodes
				GROUP BY invprojectid
			) n ON n.invprojectid = ip.id
			LEFT JOIN (
				SELECT invprojectid, COUNT(*) AS ncount FROM netnodes
				GROUP BY invprojectid
			) nn ON n.invprojectid = ip.id
			WHERE ip.type <> ?
			ORDER BY ip.name', 'id', array(INV_PROJECT_SYSTEM));
	}

	public function GetProject($id) {
		return $this->db->GetRow('SELECT * FROM invprojects
			WHERE type <> ? AND id = ?',
			array(INV_PROJECT_SYSTEM, $id));
	}

	public function GetProjectName($id) {
		return $this->db->GetOne('SELECT name FROM invprojects
			WHERE type <> ? AND id = ?',
			array(INV_PROJECT_SYSTEM, $id));
	}

	public function GetProjectByName($name) {
		return $this->db->GetRow('SELECT * FROM invprojects
			WHERE type <> ? AND name = ?',
			array(INV_PROJECT_SYSTEM, $name));
	}

	public function ProjectByNameExists($name) {
		return $this->db->GetOne("SELECT id FROM invprojects
			WHERE name = ? AND type <> ?",
			array($name, INV_PROJECT_SYSTEM));
	}

	public function AddProject($project) {
		$this->db->Execute("INSERT INTO invprojects (name, divisionid, type) VALUES (?, ?, ?)",
			array(
				$project['project'],
				isset($project['divisionid']) && !empty($project['divisionid']) ? $project['divisionid'] : null,
				INV_PROJECT_REGULAR
			)
		);
		return $this->db->GetLastInsertID('invprojects');
	}

	public function DeleteProject($id) {
		return $this->db->Execute('DELETE FROM invprojects WHERE id=?', array($id));
	}

	public function UpdateProject($id, $project) {
		$project['type'] = INV_PROJECT_REGULAR;
		$project['id'] = $id;
		return $this->db->Execute('UPDATE invprojects SET name=?, divisionid=?, type=?
            WHERE id = ?', array_values($project));
	}

	public function GetProjectType($id) {
		return $this->db->GetOne('SELECT type FROM invprojects WHERE id = ?',
			array($id));
	}
}
