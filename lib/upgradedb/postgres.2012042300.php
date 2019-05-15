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

$this->Execute("
	CREATE SEQUENCE nodesessions_id_seq;
	CREATE TABLE nodesessions (
		id integer		DEFAULT nextval('nodesessions_id_seq'::text) NOT NULL,
		customerid integer	DEFAULT 0 NOT NULL,
		nodeid integer		DEFAULT 0 NOT NULL,
		ipaddr bigint		DEFAULT 0 NOT NULL,
		mac varchar(17)		DEFAULT '' NOT NULL,
		start integer		DEFAULT 0 NOT NULL,
		stop integer		DEFAULT 0 NOT NULL,
		download bigint		DEFAULT 0,
		upload bigint		DEFAULT 0,
		tag varchar(32)		DEFAULT '' NOT NULL,
		PRIMARY KEY (id)
	);
	CREATE INDEX nodesessions_tag_idx ON nodesessions(tag);
	CREATE INDEX nodesessions_customerid_idx ON nodesessions(customerid);
	CREATE INDEX nodesessions_nodeid_idx ON nodesessions(nodeid);
	ALTER TABLE stats ADD COLUMN nodesessionid integer DEFAULT 0 NOT NULL;
	CREATE INDEX stats_nodesessionid_idx ON stats(nodesessionid);
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2012042300', 'dbversion'));

$this->CommitTrans();
