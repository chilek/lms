<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

$this->Execute("ALTER TABLE divisions ADD COLUMN location_city int(11) DEFAULT NULL,
		ADD FOREIGN KEY (location_city) REFERENCES location_cities (id) ON UPDATE CASCADE ON DELETE SET NULL");
$this->Execute("ALTER TABLE divisions ADD COLUMN location_street int(11) DEFAULT NULL,
		ADD FOREIGN KEY (location_street) REFERENCES location_streets (id) ON UPDATE CASCADE ON DELETE SET NULL");
$this->Execute("ALTER TABLE divisions ADD COLUMN location_house varchar(32) DEFAULT NULL");
$this->Execute("ALTER TABLE divisions ADD COLUMN location_flat varchar(32) DEFAULT NULL");
$this->Execute("ALTER TABLE divisions ADD COLUMN tax_office_code varchar(8) DEFAULT NULL");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016120600', 'dbversion'));

$this->CommitTrans();
