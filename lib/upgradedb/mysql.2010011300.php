<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2009 LMS Developers
 *
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

$this->Execute("DELETE FROM nodeassignments WHERE assignmentid NOT IN (SELECT id FROM assignments)");
$this->Execute("DELETE FROM nodeassignments WHERE nodeid NOT IN (SELECT id FROM nodes)");

$this->Execute("ALTER TABLE nodeassignments ALTER nodeid DROP DEFAULT");
$this->Execute("ALTER TABLE nodeassignments ALTER assignmentid DROP DEFAULT");

$this->Execute("ALTER TABLE nodeassignments ADD INDEX assignmentid (assignmentid)");

$this->Execute("ALTER TABLE nodeassignments ADD FOREIGN KEY (nodeid)
		REFERENCES nodes (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE nodeassignments ADD FOREIGN KEY (assignmentid)
		REFERENCES assignments (id) ON DELETE CASCADE ON UPDATE CASCADE");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2010011300', 'dbversion'));
