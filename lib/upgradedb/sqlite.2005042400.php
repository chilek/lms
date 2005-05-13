<?php

/*
 * LMS version 1.7-cvs
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

$DB->BeginTrans();

$DB->Execute("CREATE TEMP TABLE nodes_t AS SELECT * FROM nodes");
$DB->Execute("DROP TABLE nodes");
$DB->Execute("CREATE TABLE nodes (
	id integer PRIMARY KEY,
	name varchar(16) 	DEFAULT '' NOT NULL,
	mac varchar(20) 	DEFAULT '' NOT NULL,
	ipaddr bigint 		DEFAULT 0 NOT NULL,
	ipaddr_pub bigint 	DEFAULT 0 NOT NULL,
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
	UNIQUE (ipaddr))
");
$DB->Execute("INSERT INTO nodes (id, name, mac, ipaddr, passwd, ownerid, netdev, linktype, creationdate, moddate, creatorid, modid, access, warning, lastonline, info)
		SELECT id, name, mac, ipaddr, passwd, ownerid, netdev, linktype, creationdate, moddate, creatorid, modid, access, warning, lastonline, info
		FROM nodes_t");
$DB->Execute("DROP TABLE nodes_t");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?",array('2005042400', 'dbversion'));

$DB->CommitTrans();

?>

