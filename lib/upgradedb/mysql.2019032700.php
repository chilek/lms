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

if ($this->ResourceExists('location_buildings_ibfk_1', LMSDB::RESOURCE_TYPE_CONSTRAINT)) {
    $this->Execute("ALTER TABLE location_buildings
		DROP FOREIGN KEY location_buildings_ibfk_1");
}

if ($this->ResourceExists('location_buildings_ibfk_2', LMSDB::RESOURCE_TYPE_CONSTRAINT)) {
    $this->Execute("ALTER TABLE location_buildings
		DROP FOREIGN KEY location_buildings_ibfk_2");
}

$this->Execute("ALTER TABLE location_buildings ADD CONSTRAINT location_buildings_city_id_fkey
	FOREIGN KEY (city_id) REFERENCES location_cities (id) ON DELETE CASCADE ON UPDATE CASCADE");

$this->Execute("ALTER TABLE location_buildings ADD CONSTRAINT location_buildings_street_id_fkey
	FOREIGN KEY (street_id) REFERENCES location_streets (id) ON DELETE CASCADE ON UPDATE CASCADE");


$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2019032700', 'dbversion'));

$this->CommitTrans();
