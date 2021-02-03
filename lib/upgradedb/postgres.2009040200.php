<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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
CREATE SEQUENCE nastypes_id_seq;
CREATE TABLE nastypes (
    	id integer 	DEFAULT nextval('nastypes_id_seq'::text) NOT NULL,
	name varchar(255) NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (name)
);

ALTER TABLE nodes ADD nas smallint NOT NULL DEFAULT 0;
ALTER TABLE netdevices ADD shortname varchar(32) NOT NULL DEFAULT '';
ALTER TABLE netdevices ADD nastype integer NOT NULL DEFAULT 0;
ALTER TABLE netdevices ADD clients integer NOT NULL DEFAULT 0;
ALTER TABLE netdevices ADD secret varchar(60) NOT NULL DEFAULT '';
ALTER TABLE netdevices ADD community varchar(50) NOT NULL DEFAULT '';

CREATE VIEW nas AS 
SELECT no.id, inet_ntoa(no.ipaddr) AS nasname, nd.shortname, nd.nastype AS type,
nd.clients AS ports, nd.secret, nd.community, nd.description 
FROM nodes no 
JOIN netdevices nd ON (no.netdev = nd.id) 
WHERE no.nas = 1;

INSERT INTO nastypes (name) VALUES ('mikrotik_snmp');
INSERT INTO nastypes (name) VALUES ('cisco');
INSERT INTO nastypes (name) VALUES ('computone');
INSERT INTO nastypes (name) VALUES ('livingston');
INSERT INTO nastypes (name) VALUES ('max40xx');
INSERT INTO nastypes (name) VALUES ('multitech');
INSERT INTO nastypes (name) VALUES ('netserver');
INSERT INTO nastypes (name) VALUES ('pathras');
INSERT INTO nastypes (name) VALUES ('patton');
INSERT INTO nastypes (name) VALUES ('portslave');
INSERT INTO nastypes (name) VALUES ('tc');
INSERT INTO nastypes (name) VALUES ('usrhiper');
INSERT INTO nastypes (name) VALUES ('other');

");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2009040200', 'dbversion'));

$this->CommitTrans();
