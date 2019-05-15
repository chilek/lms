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
	CREATE SEQUENCE macs_id_seq;
	CREATE TABLE macs (
		id		integer		DEFAULT nextval('macs_id_seq'::text) NOT NULL,
		mac		varchar(17)	DEFAULT '' NOT NULL,
		nodeid		integer		NOT NULL
			REFERENCES nodes (id) ON DELETE CASCADE ON UPDATE CASCADE,
		PRIMARY KEY (id),
		CONSTRAINT macs_mac_key UNIQUE (mac, nodeid)
	);

	INSERT INTO macs (mac, nodeid) 
		SELECT mac, id FROM nodes;

	ALTER TABLE nodes DROP mac;
");

if (!$this->GetOne("SELECT COUNT(*) FROM pg_aggregate a JOIN pg_proc p ON (p.oid = a.aggfnoid)
    WHERE p.proname='array_agg'")) {
    $this->Execute("
		CREATE AGGREGATE array_agg (
		    BASETYPE=anyelement,
			SFUNC=array_append,
			STYPE=anyarray,
			INITCOND='{}'
		);
	");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2010050600', 'dbversion'));

$this->CommitTrans();
