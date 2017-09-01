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

$this->Execute("
    CREATE TABLE invprojects (
        id int(11) NOT NULL auto_increment,
	name varchar(255) NOT NULL, 
	type tinyint DEFAULT 0, 
	PRIMARY KEY (id)
) ENGINE=INNODB");

$this->Execute("INSERT INTO invprojects (name,type) VALUES ('inherited',1)");

$this->Execute("
    CREATE TABLE netnodes (
        id int(11) NOT NULL auto_increment,
	name varchar(255) NOT NULL, 
	type tinyint DEFAULT 0,
	invprojectid int(11),
	status tinyint DEFAULT 0,
	location varchar(255) DEFAULT '',
	location_city int(11) DEFAULT NULL,
	location_street int(11) DEFAULT NULL,
	location_house varchar(8) DEFAULT NULL,
	location_flat varchar(8) DEFAULT NULL,
	longitude decimal(10,6) DEFAULT NULL,
	latitude decimal(10,6) DEFAULT NULL,
	ownership tinyint(1) DEFAULT 0,
	coowner varchar(255) DEFAULT '',
	uip tinyint(1) DEFAULT 0,
	miar tinyint(1) DEFAULT 0,
	PRIMARY KEY (id),
	FOREIGN KEY (invprojectid) REFERENCES invprojects (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=INNODB");


$this->Execute("ALTER TABLE netdevices ADD COLUMN netnodeid int(11) DEFAULT NULL");
$this->Execute("ALTER TABLE netdevices ADD FOREIGN KEY (netnodeid) REFERENCES netnodes(id) ON DELETE SET NULL ON UPDATE CASCADE");

$this->Execute("ALTER TABLE netdevices ADD COLUMN invprojectid int(11) DEFAULT NULL");
$this->Execute("ALTER TABLE netdevices ADD FOREIGN KEY (invprojectid) REFERENCES invprojects(id) ON DELETE SET NULL ON UPDATE CASCADE");

$this->Execute("ALTER TABLE netdevices ADD COLUMN status tinyint DEFAULT '0'");

$this->Execute("ALTER TABLE nodes ADD COLUMN invprojectid int(11) DEFAULT NULL");
$this->Execute("ALTER TABLE nodes ADD FOREIGN KEY (invprojectid) REFERENCES invprojects(id) ON DELETE SET NULL ON UPDATE CASCADE");

$this->Execute("DROP VIEW IF EXISTS vnodes");
$this->Execute("DROP VIEW IF EXISTS vmacs");
$this->Execute("CREATE VIEW vnodes AS
		SELECT n.*, m.mac
		FROM nodes n
		LEFT JOIN vnodes_mac m ON (n.id = m.nodeid)");
$this->Execute("CREATE VIEW vmacs AS
		SELECT n.*, m.mac, m.id AS macid
		FROM nodes n
		JOIN macs m ON (n.id = m.nodeid)");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2014111400', 'dbversion'));

$this->CommitTrans();

?>
