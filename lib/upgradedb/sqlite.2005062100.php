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

$DB->Execute("CREATE TEMP TABLE tariffs_t AS SELECT * FROM tariffs");
$DB->Execute("DROP TABLE tariffs");
$DB->Execute("CREATE TABLE tariffs (
	id integer PRIMARY KEY,
	name varchar(255) 	DEFAULT '' NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	taxid integer 		DEFAULT 0 NOT NULL,
	prodid varchar(255) 	DEFAULT '' NOT NULL,
	uprate integer		DEFAULT 0 NOT NULL,
	upceil integer		DEFAULT 0 NOT NULL,
	downrate integer	DEFAULT 0 NOT NULL,
	downceil integer	DEFAULT 0 NOT NULL,
	climit integer		DEFAULT 0 NOT NULL,
	plimit integer		DEFAULT 0 NOT NULL,
	description text	DEFAULT '' NOT NULL,
	UNIQUE (name))");
$DB->Execute("INSERT INTO tariffs (id, name, value, taxid, prodid, uprate, upceil, downrate, downceil, climit, plimit, description) 
	    SELECT id, name, value, taxid, pkwiu, uprate, upceil, downrate, downceil, climit, plimit, description FROM tariffs_t");
$DB->Execute("DROP TABLE tariffs_t");

$DB->Execute("CREATE TEMP TABLE i_t AS SELECT * FROM invoicecontents");
$DB->Execute("DROP TABLE invoicecontents");
$DB->Execute("CREATE TABLE invoicecontents (
	docid integer 		DEFAULT 0 NOT NULL,
	itemid smallint		DEFAULT 0 NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	taxid integer 		DEFAULT 0 NOT NULL,
	prodid varchar(255) 	DEFAULT '' NOT NULL,
	content varchar(16) 	DEFAULT '' NOT NULL,
	count numeric(9,2) 	DEFAULT 0 NOT NULL,
	description varchar(255) DEFAULT '' NOT NULL,
	tariffid integer 	DEFAULT 0 NOT NULL)");
$DB->Execute("INSERT INTO invoicecontents (docid, itemid, value, taxid, prodid, content, count, description, tariffid) 
	    SELECT docid, itemid, value, taxid, pkwiu, content, count, description, tariffid FROM i_t");
$DB->Execute("DROP TABLE i_t");
$DB->Execute("CREATE INDEX invoicecontents_docid_idx ON invoicecontents(docid)");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?",array('2005062100', 'dbversion'));

$DB->CommitTrans();

?>
