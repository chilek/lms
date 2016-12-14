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

$this->Execute("CREATE TABLE location_buildings (
					id           int(11) auto_increment,
					city_id      int(11) NOT NULL,
					street_id    int(11) NULL,
					building_num varchar(20) NULL,
					flats        int(11) NULL,
					latitude     numeric(10,6) NULL,
					longitude    numeric(10,6) NULL,
					PRIMARY KEY (id),
					FOREIGN KEY (city_id) REFERENCES location_cities (id) ON DELETE CASCADE ON UPDATE CASCADE,
					FOREIGN KEY (street_id) REFERENCES location_streets (id) ON DELETE SET NULL ON UPDATE CASCADE,
					INDEX location_cityid_index (city_id)
				) ENGINE=InnoDB;");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016121400', 'dbversion'));

$this->CommitTrans();

?>
