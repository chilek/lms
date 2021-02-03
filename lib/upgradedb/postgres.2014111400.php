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
	CREATE SEQUENCE invprojects_id_seq;
	CREATE TABLE invprojects (
		id integer DEFAULT nextval('invprojects_id_seq'::text) NOT NULL,
		name varchar(255) NOT NULL,
		type smallint DEFAULT 0,
		PRIMARY KEY(id)
	);
");

$this->Execute("INSERT INTO invprojects (name,type) VALUES ('inherited',1)");

$this->Execute("
	CREATE SEQUENCE netnodes_id_seq;
	CREATE TABLE netnodes (
		id integer DEFAULT nextval('netnodes_id_seq'::text) NOT NULL,
		name varchar(255) NOT NULL,
		type smallint DEFAULT 0,
		invprojectid integer  REFERENCES invprojects (id) ON DELETE SET NULL ON UPDATE CASCADE,
		status smallint DEFAULT 0,
		location varchar(255) DEFAULT '',
		location_city integer DEFAULT NULL,
		location_street integer DEFAULT NULL,
		location_house varchar(8) DEFAULT NULL,
		location_flat varchar(8) DEFAULT NULL,
		longitude numeric(10,6) DEFAULT NULL,
		latitude numeric(10,6) DEFAULT NULL,
		ownership smallint DEFAULT 0,
		coowner varchar(255) DEFAULT '',
		uip smallint DEFAULT 0,
		miar smallint DEFAULT 0,
		PRIMARY KEY(id)
	);
");

$this->Execute("ALTER TABLE netdevices ADD COLUMN netnodeid integer DEFAULT NULL");
$this->Execute("ALTER TABLE netdevices ADD CONSTRAINT netdevices_netnode_fkey FOREIGN KEY (netnodeid) REFERENCES netnodes(id) ON DELETE SET NULL ON UPDATE CASCADE");

$this->Execute("ALTER TABLE netdevices ADD COLUMN invprojectid integer DEFAULT NULL");
$this->Execute("ALTER TABLE netdevices ADD CONSTRAINT netdevices_invproject_fkey FOREIGN KEY (invprojectid) REFERENCES invprojects(id) ON DELETE SET NULL ON UPDATE CASCADE");

$this->Execute("ALTER TABLE netdevices ADD COLUMN status smallint DEFAULT 0");

$this->Execute("ALTER TABLE nodes ADD COLUMN invprojectid integer DEFAULT NULL");
$this->Execute("ALTER TABLE nodes ADD CONSTRAINT nodes_invproject_fkey FOREIGN KEY (invprojectid) REFERENCES invprojects(id) ON DELETE SET NULL ON UPDATE CASCADE");

$this->Execute("DROP VIEW vnodes; DROP VIEW vmacs;");
$this->Execute("CREATE VIEW vnodes AS
		SELECT n.*, m.mac
		FROM nodes n
		LEFT JOIN (SELECT nodeid, array_to_string(array_agg(mac), ',') AS mac
			FROM macs GROUP BY nodeid) m ON (n.id = m.nodeid);
	CREATE VIEW vmacs AS
	SELECT n.*, m.mac, m.id AS macid
		FROM nodes n
		JOIN macs m ON (n.id = m.nodeid);");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2014111400', 'dbversion'));

$this->CommitTrans();
