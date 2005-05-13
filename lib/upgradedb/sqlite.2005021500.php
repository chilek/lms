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

// upgrade 2005012600 - 2005021500

$DB->Execute("
    CREATE TABLE events (
	id integer PRIMARY KEY,
	title varchar(255) DEFAULT '' NOT NULL,
	description text DEFAULT '' NOT NULL,
	note text DEFAULT '' NOT NULL,
	date integer DEFAULT 0 NOT NULL,
	begintime smallint DEFAULT 0 NOT NULL,
	endtime smallint DEFAULT 0 NOT NULL,
	adminid integer DEFAULT 0 NOT NULL,
	userid integer DEFAULT 0 NOT NULL,
	private smallint DEFAULT 0 NOT NULL,
	closed smallint DEFAULT 0 NOT NULL
	)
");

$DB->Execute("
    CREATE TABLE eventassignments (
	eventid integer DEFAULT 0 NOT NULL,
	adminid integer DEFAULT 0 NOT NULL,
	UNIQUE (eventid, adminid)
	)
");

$DB->Execute("CREATE INDEX events_date_idx ON events(date)");

$DB->Execute("CREATE TEMP TABLE users_t AS SELECT * FROM users");
$DB->Execute("DROP TABLE users");
$DB->Execute("CREATE TABLE users (
	id integer PRIMARY KEY,
	lastname varchar(255)	DEFAULT '' NOT NULL,
	name varchar(255)	DEFAULT '' NOT NULL,
	status smallint 	DEFAULT 0 NOT NULL,
	email varchar(255) 	DEFAULT '' NOT NULL,
	phone1 varchar(255) 	DEFAULT '' NOT NULL,
	phone2 varchar(255) 	DEFAULT '' NOT NULL,
	phone3 varchar(255) 	DEFAULT '' NOT NULL,
	gguin integer 		DEFAULT 0 NOT NULL,
	address varchar(255) 	DEFAULT '' NOT NULL,
	zip varchar(10)		DEFAULT '' NOT NULL,
	city varchar(32) 	DEFAULT '' NOT NULL,
	nip varchar(16) 	DEFAULT '' NOT NULL,
	pesel varchar(11) 	DEFAULT '' NOT NULL,
	info text		DEFAULT '' NOT NULL,
	serviceaddr text	DEFAULT '' NOT NULL,
	creationdate integer 	DEFAULT 0 NOT NULL,
	moddate integer 	DEFAULT 0 NOT NULL,
	creatorid integer 	DEFAULT 0 NOT NULL,
	modid integer 		DEFAULT 0 NOT NULL,
	deleted smallint 	DEFAULT 0 NOT NULL,
	message text		DEFAULT '' NOT NULL,
	pin integer		DEFAULT 0 NOT NULL
)");
$DB->Execute("INSERT INTO users(id, lastname, name, status, email, phone1, phone2, phone3, gguin, address, zip, city, nip, pesel, info, serviceaddr, creationdate, moddate, creatorid, modid, deleted, message, pin)
		SELECT id, lastname, name, status, email, phone1, phone2, phone3, gguin, address, zip, city, nip, pesel, info, serviceaddr, creationdate, moddate, creatorid, modid, deleted, message, pin FROM users_t");
$DB->Execute("DROP TABLE users_t");

$DB->Execute("CREATE TEMP TABLE cash_t AS SELECT * FROM cash");
$DB->Execute("DROP TABLE cash");
$DB->Execute("CREATE TABLE cash (
	id integer 		PRIMARY KEY,
	time integer 		DEFAULT 0 NOT NULL,
	adminid integer 	DEFAULT 0 NOT NULL,
	type smallint 		DEFAULT 0 NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	taxvalue numeric(9,2)   DEFAULT 0,
	userid integer 		DEFAULT 0 NOT NULL,
	comment varchar(255) 	DEFAULT '' NOT NULL,
	invoiceid integer 	DEFAULT 0 NOT NULL,
	itemid smallint		DEFAULT 0 NOT NULL
)");
$DB->Execute("INSERT INTO cash(id, time, adminid, type, value, taxvalue, userid, comment, invoiceid)
		SELECT id, time, adminid, type, value, taxvalue, userid, comment, invoiceid FROM cash_t");
$DB->Execute("DROP TABLE cash_t");

$DB->Execute("CREATE TEMP TABLE invoices_t AS SELECT * FROM invoices");
$DB->Execute("DROP TABLE invoices");
$DB->Execute("CREATE TABLE invoices (
	id integer PRIMARY KEY,
        number integer 		DEFAULT 0 NOT NULL,
        cdate integer 		DEFAULT 0 NOT NULL,
        paytime smallint 	DEFAULT 0 NOT NULL,
	paytype varchar(255) 	DEFAULT '' NOT NULL,
        customerid integer 	DEFAULT 0 NOT NULL,
        name varchar(255) 	DEFAULT '' NOT NULL,
        address varchar(255) 	DEFAULT '' NOT NULL,
        nip varchar(16) 	DEFAULT '' NOT NULL,
	pesel varchar(11) 	DEFAULT '' NOT NULL,
        zip varchar(10)		DEFAULT '' NOT NULL,
        city varchar(32) 	DEFAULT '' NOT NULL,
        phone varchar(255) 	DEFAULT '' NOT NULL,
        finished smallint 	DEFAULT 0 NOT NULL
)");
$DB->Execute("INSERT INTO invoices(id, number, cdate, paytime, paytype, customerid, name, address, nip, pesel, zip, city, phone, finished)
		SELECT id, number, cdate, paytime, paytype, customerid, name, address, nip, pesel, zip, city, phone, finished FROM invoices_t");
$DB->Execute("DROP TABLE invoices_t");

$DB->Execute("CREATE TEMP TABLE invoicecontents_t AS SELECT * FROM invoicecontents");
$DB->Execute("DROP TABLE invoicecontents");
$DB->Execute("CREATE TABLE invoicecontents (
	invoiceid integer 	DEFAULT 0 NOT NULL,
	itemid smallint		DEFAULT 0 NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	taxvalue numeric(9,2) 	DEFAULT 0,
	pkwiu varchar(255) 	DEFAULT '' NOT NULL,
	content varchar(16) 	DEFAULT '' NOT NULL,
	count numeric(9,2) 	DEFAULT 0 NOT NULL,
	description varchar(255) DEFAULT '' NOT NULL,
	tariffid integer 	DEFAULT 0 NOT NULL
)");	 
$DB->Execute("INSERT INTO invoicecontents(invoiceid, value, taxvalue, pkwiu, content, count, description, tariffid)
		SELECT invoiceid, value, taxvalue, pkwiu, content, count, description, tariffid FROM invoicecontents_t");
$DB->Execute("DROP TABLE invoicecontents_t");

$DB->Execute("CREATE INDEX cash_invoiceid_idx ON cash(invoiceid)");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?",array('2005021500', 'dbversion'));

?>
