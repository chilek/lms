<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2015 LMS Developers
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

$this->BeginTrans();

$this->Execute("
	ALTER TABLE voipaccounts ADD COLUMN location varchar(255) DEFAULT NULL;
	ALTER TABLE voipaccounts ADD COLUMN location_city integer;
	ALTER TABLE voipaccounts ADD COLUMN location_street integer;
	ALTER TABLE voipaccounts ADD COLUMN location_house varchar(32) DEFAULT NULL;
	ALTER TABLE voipaccounts ADD COLUMN location_flat varchar(32) DEFAULT NULL;
	CREATE INDEX voipaccounts_location_street_idx ON voipaccounts (location_street);
	CREATE INDEX voipaccounts_location_city_idx ON voipaccounts (location_city, location_street, location_house, location_flat);
	ALTER TABLE voipaccounts ADD CONSTRAINT voipaccounts_location_city_fkey
		FOREIGN KEY (location_city) REFERENCES location_cities (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE voipaccounts ADD CONSTRAINT voipaccounts_location_street_fkey
		FOREIGN KEY (location_street) REFERENCES location_streets (id) ON DELETE SET NULL ON UPDATE CASCADE;
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2015072300', 'dbversion'));

$this->CommitTrans();
