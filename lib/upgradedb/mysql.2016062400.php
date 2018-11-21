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

$this->Execute("CREATE TABLE voip_emergency_numbers (
	location_borough int(11) NOT NULL,
	number int(11) NOT NULL,
	fullnumber varchar(20) NOT NULL,
	INDEX voip_emergency_numbers_number_idx (number),
	UNIQUE KEY number (location_borough, number),
	FOREIGN KEY (location_borough) REFERENCES location_boroughs (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016062400', 'dbversion'));

$this->CommitTrans();

?>
