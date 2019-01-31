<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

$tariffs = $this->GetAllByKey("SELECT id, value FROM tariffs", 'id');
$schemas = $this->GetAllByKey("SELECT id, data, continuation, ctariffid
	FROM promotionschemas", 'id');
$assignments = $this->GetAll("SELECT id, promotionschemaid, tariffid, data
	FROM promotionassignments");

if (!empty($assignments))
	foreach ($assignments as $assignment) {
		$schema = $schemas[$assignment['promotionschemaid']];
		$this->Execute("UPDATE promotionassignments
			SET data = ?
			WHERE id = ?",
			array($assignment['data'] . ';' . (empty($schema['continuation']) ? 'NULL' : $tariffs[$assignment['tariffid']]['value']),
				$assignment['id']));
	}

if (!empty($schemas))
	foreach ($schemas as $schemaid => $schema)
		if (!empty($schema['continuation']) && !empty($schema['ctariffid'])) {
			$periods = array();
			if (strlen($schema['data']))
				$periods = explode(';', $schema['data']);
			$this->Execute("INSERT INTO promotionassignments (promotionschemaid, tariffid, data)
				VALUES (?, ?, ?, ?)",
				array(
					$schema['id'],
					$schema['ctariffid'],
					implode(';', array_fill(0, count($periods) + 1, 'NULL'))
					. ';' . $tariffs[$schema['ctariffid']]['value'],
				));
		}

$this->Execute("ALTER TABLE promotionschemas DROP COLUMN continuation");
$this->Execute("ALTER TABLE promotionschemas DROP COLUMN ctariffid");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2019013100', 'dbversion'));

$this->CommitTrans();

?>
