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

$this->Execute("CREATE TABLE netradiosectors (
	id int(11) NOT NULL auto_increment,
	name varchar(64) NOT NULL,
	azimuth decimal(9,2) DEFAULT 0 NOT NULL,
	radius decimal(9,2) DEFAULT 0 NOT NULL,
	altitude smallint DEFAULT 0 NOT NULL,
	rsrange int(11) DEFAULT 0 NOT NULL,
	netdev int(11) NOT NULL,
	PRIMARY KEY (id),
	UNIQUE KEY name (name, netdev),
	INDEX netdev (netdev),
	FOREIGN KEY (netdev) REFERENCES netdevices (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB");

$this->Execute("DROP VIEW IF EXISTS vnodes");
$this->Execute("DROP VIEW IF EXISTS vmacs");
$this->Execute("ALTER TABLE nodes ADD COLUMN linkradiosector int(11) DEFAULT NULL");
$this->Execute("ALTER TABLE nodes ADD INDEX linkradiosector (linkradiosector)");
$this->Execute("ALTER TABLE nodes ADD FOREIGN KEY (linkradiosector) REFERENCES netradiosectors (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("CREATE VIEW vnodes AS
		SELECT n.*, m.mac
		FROM nodes n
		LEFT JOIN vnodes_mac m ON (n.id = m.nodeid)");
$this->Execute("CREATE VIEW vmacs AS
		SELECT n.*, m.mac, m.id AS macid
		FROM nodes n
		JOIN macs m ON (n.id = m.nodeid)");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2015040200', 'dbversion'));

$this->CommitTrans();

?>
