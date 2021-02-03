<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

// name2 support for teryt location_street
$this->Execute("DROP VIEW teryt_ulic");

$this->Execute("ALTER TABLE location_streets ADD name2 varchar(128) DEFAULT NULL");

$this->Execute("
	CREATE VIEW teryt_ulic AS
		SELECT st.ident AS woj, d.ident AS pow, b.ident AS gmi, b.type AS rodz_gmi,
			c.ident AS sym, s.ident AS sym_ul, s.name AS nazwa_1, s.name2 AS nazwa_2, t.name AS cecha, s.id
		FROM location_streets s
		JOIN location_street_types t ON (s.typeid = t.id)
		JOIN location_cities c ON (s.cityid = c.id)
		JOIN location_boroughs b ON (c.boroughid = b.id)
		JOIN location_districts d ON (b.districtid = d.id)
		JOIN location_states st ON (d.stateid = st.id)
");

// netlink and node link speed support
$this->Execute("DROP VIEW vnodes");
$this->Execute("DROP VIEW vmacs");

$this->Execute("ALTER TABLE netlinks ADD speed int(11) DEFAULT '100000' NOT NULL");
$this->Execute("ALTER TABLE nodes ADD linkspeed int(11) DEFAULT '100000' NOT NULL");

$this->Execute("CREATE VIEW vnodes AS
		SELECT n.*, m.mac
		FROM nodes n
		LEFT JOIN vnodes_mac m ON (n.id = m.nodeid)");
$this->Execute("CREATE VIEW vmacs AS
		SELECT n.*, m.mac, m.id AS macid
		FROM nodes n
		JOIN macs m ON (n.id = m.nodeid)");


$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2012030801', 'dbversion'));

$this->CommitTrans();
