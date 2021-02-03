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
	CREATE SEQUENCE netradiosectors_id_seq;
	CREATE TABLE netradiosectors (
		id integer DEFAULT nextval('netradiosectors_id_seq'::text) NOT NULL,
		name varchar(64) NOT NULL,
		azimuth numeric(9,2) DEFAULT 0 NOT NULL,
		radius numeric(9,2) DEFAULT 0 NOT NULL,
		altitude smallint DEFAULT 0 NOT NULL,
		rsrange integer DEFAULT 0 NOT NULL,
		netdev integer NOT NULL
			REFERENCES netdevices (id) ON DELETE CASCADE ON UPDATE CASCADE,
		PRIMARY KEY (id),
		UNIQUE (name, netdev)
	);
	CREATE INDEX netradiosectors_netdev_idx ON netradiosectors (netdev);
	DROP VIEW vnodes;
	DROP VIEW vmacs;
	ALTER TABLE nodes ADD COLUMN linkradiosector integer DEFAULT NULL;
	ALTER TABLE nodes ADD CONSTRAINT nodes_linkradiosector_fkey
		FOREIGN KEY (linkradiosector) REFERENCES netradiosectors (id) ON DELETE SET NULL ON UPDATE CASCADE;
	CREATE VIEW vnodes AS
		SELECT n.*, m.mac
			FROM nodes n
		LEFT JOIN (SELECT nodeid, array_to_string(array_agg(mac), ',') AS mac
			FROM macs GROUP BY nodeid) m ON (n.id = m.nodeid);
	CREATE VIEW vmacs AS
		SELECT n.*, m.mac, m.id AS macid
		FROM nodes n
		JOIN macs m ON (n.id = m.nodeid);
	CREATE INDEX nodes_linkradiosector_idx ON nodes (linkradiosector);
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2015040200', 'dbversion'));

$this->CommitTrans();
