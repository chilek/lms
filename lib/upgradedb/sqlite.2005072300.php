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

$DB->Execute("
	CREATE TABLE documentcontents (
	    docid integer DEFAULT 0 NOT NULL,
	    title text DEFAULT '' NOT NULL,
	    fromdate integer DEFAULT 0 NOT NULL,
	    todate integer DEFAULT 0 NOT NULL,
	    filename varchar(255) DEFAULT '' NOT NULL,
	    contenttype varchar(255) DEFAULT '' NOT NULL,
	    md5sum varchar(32) DEFAULT '' NOT NULL,
	    description text DEFAULT '' NOT NULL,
	    UNIQUE (docid))
");
$DB->Execute("CREATE INDEX documentcontents_md5sum_idx ON documentcontents (md5sum)");

$DB->Execute("CREATE TEMP TABLE a_t AS SELECT * FROM assignments");
$DB->Execute("DROP TABLE assignments");
$DB->Execute("CREATE TABLE assignments (
	id integer PRIMARY KEY,
	tariffid integer 	DEFAULT 0 NOT NULL,
	customerid integer 	DEFAULT 0 NOT NULL,
	period smallint		DEFAULT 0 NOT NULL,
	at smallint 		DEFAULT 0 NOT NULL,
	datefrom integer	DEFAULT 0 NOT NULL,
	dateto integer		DEFAULT 0 NOT NULL,
	invoice smallint 	DEFAULT 0 NOT NULL,
	suspended smallint	DEFAULT 0 NOT NULL,
	discount numeric(4,2)	DEFAULT 0 NOT NULL)
");
$DB->Execute("INSERT INTO assignments (id, tariffid, customerid, period, at, datefrom, dateto, invoice, suspended, discount)
		SELECT id, tariffid, customerid, period+2, at, datefrom, dateto, invoice, suspended, discount FROM a_t");
$DB->Execute("CREATE INDEX assignments_tariffid_idx ON assignments(tariffid);
$DB->Execute("DROP TABLE a_t");

$DB->Execute("CREATE TEMP TABLE p_t AS SELECT * FROM payments");
$DB->Execute("DROP TABLE payments");
$DB->Execute("CREATE TABLE payments (
	id integer PRIMARY KEY,
	name varchar(255) 	DEFAULT '' NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	creditor varchar(255) 	DEFAULT '' NOT NULL,
	period smallint		DEFAULT 0 NOT NULL,
	at smallint 		DEFAULT 0 NOT NULL,
	description text	DEFAULT '' NOT NULL
);
$DB->Execute("INSERT INTO payments (id, name, value, creditor, period, at, description)
		SELECT id, name, value, creditor, period+2, at, description FROM p_t");
$DB->Execute("DROP TABLE p_t");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?",array('2005072300', 'dbversion'));

$DB->CommitTrans();

?>
