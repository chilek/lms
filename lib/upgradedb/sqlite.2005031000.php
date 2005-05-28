<?php

/*
 * LMS version 1.6-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

// include upgrades 2005021500 ... 2005031000

$DB->BeginTrans();

$DB->Execute("CREATE TABLE sessions (
    id varchar(50) NOT NULL default '', 
    ctime integer NOT NULL default 0, 
    mtime integer NOT NULL default 0, 
    atime integer NOT NULL default 0, 
    vdata text NOT NULL, 
    content text NOT NULL, 
    PRIMARY KEY (id)
)");

$DB->Execute("CREATE TABLE cashimport (
    id integer PRIMARY KEY,
    date integer DEFAULT 0 NOT NULL,
    value numeric(9,2) DEFAULT 0 NOT NULL,
    customer varchar(150) DEFAULT '' NOT NULL,
    description varchar(150) DEFAULT '' NOT NULL,
    customerid integer DEFAULT 0 NOT NULL,
    hash varchar(50) DEFAULT '' NOT NULL,
    closed smallint DEFAULT 0 NOT NULL
)");

$DB->Execute("CREATE INDEX assignments_tariffid_idx ON assignments (tariffid)");
$DB->Execute("CREATE INDEX nodes_netdev_idx ON nodes (netdev)");
$DB->Execute("CREATE INDEX rttickets_queueid_idx ON rttickets (queueid)");
$DB->Execute("CREATE INDEX cash_time_idx ON cash (time)");
$DB->Execute("CREATE INDEX invoices_cdate_idx ON invoices (cdate)");
$DB->Execute("CREATE INDEX invoicecontents_invoiceid_idx ON invoicecontents (invoiceid)");
$DB->Execute("CREATE INDEX cashimport_hash_idx ON cashimport (hash)");

$DB->Execute("CREATE TEMP TABLE nodes_t AS SELECT * FROM nodes");
$DB->Execute("DROP TABLE nodes");
$DB->Execute("CREATE TABLE nodes (
	id integer PRIMARY KEY,
	name varchar(16) 	DEFAULT '' NOT NULL,
	mac varchar(20) 	DEFAULT '' NOT NULL,
	ipaddr bigint 		DEFAULT 0 NOT NULL,
	passwd varchar(32)	DEFAULT '' NOT NULL,
	ownerid integer 	DEFAULT 0 NOT NULL,
	netdev integer 		DEFAULT 0 NOT NULL,
	linktype smallint	DEFAULT 0 NOT NULL,
	creationdate integer 	DEFAULT 0 NOT NULL,
	moddate integer 	DEFAULT 0 NOT NULL,
	creatorid integer 	DEFAULT 0 NOT NULL,
	modid integer 		DEFAULT 0 NOT NULL,
	access smallint 	DEFAULT 1 NOT NULL,
	warning smallint 	DEFAULT 0 NOT NULL,
	lastonline smallint 	DEFAULT 0 NOT NULL,
	info text 		DEFAULT '' NOT NULL,
	UNIQUE (name),
	UNIQUE (ipaddr)
)");
$DB->Execute("INSERT INTO nodes(id, name, mac, ipaddr, ownerid, netdev, linktype, creationdate, moddate, creatorid, modid, access, warning, lastonline, info)
			SELECT id, name, mac, ipaddr, ownerid, netdev, linktype, creationdate, moddate, creatorid, modid, access, warning, lastonline, info
			FROM nodes_t");
$DB->Execute("DROP TABLE nodes_t");

$DB->Execute("UPDATE dbinfo SET keyvalue='2005031000' WHERE keytype='dbversion'");

$DB->CommitTrans();

?>