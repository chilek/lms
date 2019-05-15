<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2014 LMS Developers
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

$this->Execute("DROP VIEW vnetworks;");
$this->Execute("
	CREATE VIEW vnetworks AS
		SELECT h.name AS hostname, ne.*, no.ownerid, no.location, no.location_city, no.location_street, no.location_house, no.location_flat, no.chkmac,
			inet_ntoa(ne.address) || '/' || mask2prefix(inet_aton(ne.mask)) AS ip, no.id AS nodeid
		FROM nodes no
		LEFT JOIN networks ne ON (ne.id = no.netid)
		LEFT JOIN hosts h ON (h.id = ne.hostid)
		WHERE no.ipaddr = 0 AND no.ipaddr_pub = 0;
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2015120202', 'dbversion'));

$this->CommitTrans();
