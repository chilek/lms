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

$DB->Execute("CREATE TABLE documents (
	id integer PRIMARY KEY,
	type smallint		DEFAULT 0 NOT NULL,
    	number integer 		DEFAULT 0 NOT NULL,
    	cdate integer 		DEFAULT 0 NOT NULL,
	customerid integer 	DEFAULT 0 NOT NULL,
	userid integer		DEFAULT 0 NOT NULL,		
    	name varchar(255) 	DEFAULT '' NOT NULL,
    	address varchar(255) 	DEFAULT '' NOT NULL,
	zip varchar(10)		DEFAULT '' NOT NULL,
    	city varchar(32) 	DEFAULT '' NOT NULL,
	ten varchar(16) 	DEFAULT '' NOT NULL,
	ssn varchar(11) 	DEFAULT '' NOT NULL,
    	paytime smallint 	DEFAULT 0 NOT NULL,
	paytype varchar(255) 	DEFAULT '' NOT NULL)");
$DB->Execute("INSERT INTO documents (id, type, number, cdate, paytime, paytype, customerid, userid, name, address, zip, city, ten, ssn)
		SELECT invoices.id, 1, number, cdate, paytime, paytype, invoices.customerid, cash.userid, name, address, zip, city, nip, pesel
		FROM invoices LEFT JOIN cash ON (invoices.id = cash.invoiceid)
		WHERE cash.type = 4
		GROUP BY invoices.id, number, cdate, paytime, paytype, invoices.customerid, cash.userid, name, address, zip, city, nip, pesel");
$DB->Execute("DROP TABLE invoices");
$DB->Execute("CREATE INDEX documents_cdate_idx ON documents(cdate)");
	
$DB->Execute("CREATE TABLE receiptcontents (
	docid integer		DEFAULT 0 NOT NULL,
    	itemid smallint		DEFAULT 0 NOT NULL,
	value numeric(9,2)	DEFAULT 0 NOT NULL,
	description varchar(255) DEFAULT '' NOT NULL)");
$DB->Execute("CREATE INDEX receiptcontents_docid_idx ON receiptcontents(docid)");

$DB->Execute("CREATE TABLE taxes (
	id integer PRIMARY KEY,
	value numeric(4,2) DEFAULT 0 NOT NULL,
	taxed smallint DEFAULT 0 NOT NULL,
	label varchar(16) DEFAULT '' NOT NULL,
	validfrom integer DEFAULT 0 NOT NULL,
	validto integer DEFAULT 0 NOT NULL)");

$DB->Execute("CREATE TEMP TABLE customers_t AS SELECT * FROM customers");
$DB->Execute("DROP TABLE customers");
$DB->Execute("CREATE TABLE customers (
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
	zip varchar(10) 	DEFAULT '' NOT NULL,
	city varchar(32) 	DEFAULT '' NOT NULL,
	ten varchar(16) 	DEFAULT '' NOT NULL,
	ssn varchar(11) 	DEFAULT '' NOT NULL,
	info text		DEFAULT '' NOT NULL,
	serviceaddr text	DEFAULT '' NOT NULL,
	creationdate integer 	DEFAULT 0 NOT NULL,
	moddate integer 	DEFAULT 0 NOT NULL,
	creatorid integer 	DEFAULT 0 NOT NULL,
	modid integer 		DEFAULT 0 NOT NULL,
	deleted smallint 	DEFAULT 0 NOT NULL,
	message text		DEFAULT '' NOT NULL,
	pin integer		DEFAULT 0 NOT NULL)");
$DB->Execute("INSERT INTO customers (id, lastname, name, status, email, phone1, phone2, phone3, gguin, address, zip, city, ten, ssn, info, serviceaddr, creationdate, moddate, creatorid, modid, deleted, message, pin) 
	    SELECT id, lastname, name, status, email, phone1, phone2, phone3, gguin, address, zip, city, nip, pesel, info, serviceaddr, creationdate, moddate, creatorid, modid, deleted, message, pin FROM customers_t");
$DB->Execute("DROP TABLE customers_t");

$DB->Execute("CREATE TABLE tariffs_t AS SELECT * FROM tariffs");
$DB->Execute("DROP TABLE tariffs");
$DB->Execute("CREATE TABLE tariffs (
	id integer PRIMARY KEY,
	name varchar(255) 	DEFAULT '' NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	taxid integer 		DEFAULT 0 NOT NULL,
	taxvalue decimal(9,2),
	pkwiu varchar(255) 	DEFAULT '' NOT NULL,
	uprate integer		DEFAULT 0 NOT NULL,
	upceil integer		DEFAULT 0 NOT NULL,
	downrate integer	DEFAULT 0 NOT NULL,
	downceil integer	DEFAULT 0 NOT NULL,
	climit integer		DEFAULT 0 NOT NULL,
	plimit integer		DEFAULT 0 NOT NULL,
	description text	DEFAULT '' NOT NULL)");
$DB->Execute("INSERT INTO tariffs (id, name, value, taxvalue, pkwiu, uprate, upceil, downrate, downceil, climit, plimit, description) 
	    SELECT id, name, value, taxvalue, pkwiu, uprate, upceil, downrate, downceil, climit, plimit, description FROM tariffs_t");
$DB->Execute("DROP TABLE tariffs_t");

//$DB->Execute("DROP INDEX invoicecontents_invoiceid_idx");
$DB->Execute("CREATE TABLE i_t AS SELECT * FROM invoicecontents");
$DB->Execute("DROP TABLE invoicecontents");
$DB->Execute("CREATE TABLE invoicecontents (
	docid integer 		DEFAULT 0 NOT NULL,
	itemid smallint		DEFAULT 0 NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	taxid integer 		DEFAULT 0 NOT NULL,
	taxvalue decimal(9,2),
	pkwiu varchar(255) 	DEFAULT '' NOT NULL,
	content varchar(16) 	DEFAULT '' NOT NULL,
	count numeric(9,2) 	DEFAULT 0 NOT NULL,
	description varchar(255) DEFAULT '' NOT NULL,
	tariffid integer 	DEFAULT 0 NOT NULL)");
$DB->Execute("INSERT INTO invoicecontents (docid, itemid, value, taxvalue, pkwiu, content, count, description, tariffid) 
	    SELECT invoiceid, itemid, value, taxvalue, pkwiu, content, count, description, tariffid FROM i_t");
$DB->Execute("DROP TABLE i_t");

//$DB->Execute("DROP INDEX cash_customerid_idx");
//$DB->Execute("DROP INDEX cash_invoiceid_idx");
//$DB->Execute("DROP INDEX cash_time_idx");
$DB->Execute("CREATE TABLE cash_t AS SELECT * FROM cash");
$DB->Execute("DROP TABLE cash");
$DB->Execute("CREATE TABLE cash (
	id integer 		PRIMARY KEY,
	time integer 		DEFAULT 0 NOT NULL,
	userid integer 		DEFAULT 0 NOT NULL,
	type smallint 		DEFAULT 0 NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	taxid integer   	DEFAULT 0 NOT NULL,
	taxvalue numeric(9,2),
	customerid integer 	DEFAULT 0 NOT NULL,
	comment varchar(255) 	DEFAULT '' NOT NULL,
	docid integer 		DEFAULT 0 NOT NULL,
	itemid smallint		DEFAULT 0 NOT NULL,
	reference integer	DEFAULT 0 NOT NULL)");
$DB->Execute("INSERT INTO cash (id, time, userid, type, value, taxvalue, customerid, comment, docid, itemid) 
	    SELECT id, time, userid, type, value, taxvalue, customerid, comment, invoiceid, itemid FROM cash_t");
$DB->Execute("DROP TABLE cash_t");

$i=0;
if($taxes = $DB->GetCol("SELECT taxvalue FROM cash GROUP BY taxvalue
			UNION
			SELECT taxvalue FROM tariffs GROUP BY taxvalue
			UNION
			SELECT taxvalue FROM invoicecontents GROUP BY taxvalue
			")
)
	foreach($taxes as $tax)
	{    
		$i++;
		if( $tax=='' ) //tax-free
		{
			$DB->Execute("INSERT INTO taxes (value, taxed, label) VALUES(0,0,'tax-free')");
			$DB->Execute("UPDATE cash SET taxid=? WHERE taxvalue IS NULL", array($i));
			$DB->Execute("UPDATE tariffs SET taxid=? WHERE taxvalue IS NULL", array($i));
			$DB->Execute("UPDATE invoicecontents SET taxid=? WHERE taxvalue IS NULL", array($i));
		}
		else
		{
			$DB->Execute("INSERT INTO taxes (value, taxed, label) VALUES(?,1,?)", array($tax, $tax.' %'));
			$DB->Execute("UPDATE cash SET taxid=? WHERE taxvalue=?", array($i, $tax));
			$DB->Execute("UPDATE tariffs SET taxid=? WHERE taxvalue=?", array($i, $tax));
			$DB->Execute("UPDATE invoicecontents SET taxid=? WHERE taxvalue=?", array($i, $tax));
		}
	}
	
$DB->Execute("CREATE TABLE tariffs_t AS SELECT * FROM tariffs");
$DB->Execute("DROP TABLE tariffs");
$DB->Execute("CREATE TABLE tariffs (
	id integer PRIMARY KEY,
	name varchar(255) 	DEFAULT '' NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	taxid integer 		DEFAULT 0 NOT NULL,
	pkwiu varchar(255) 	DEFAULT '' NOT NULL,
	uprate integer		DEFAULT 0 NOT NULL,
	upceil integer		DEFAULT 0 NOT NULL,
	downrate integer	DEFAULT 0 NOT NULL,
	downceil integer	DEFAULT 0 NOT NULL,
	climit integer		DEFAULT 0 NOT NULL,
	plimit integer		DEFAULT 0 NOT NULL,
	description text	DEFAULT '' NOT NULL,
	UNIQUE (name))");
$DB->Execute("INSERT INTO tariffs (id, name, value, taxid, pkwiu, uprate, upceil, downrate, downceil, climit, plimit, description) 
	    SELECT id, name, value, taxid, pkwiu, uprate, upceil, downrate, downceil, climit, plimit, description FROM tariffs_t");
$DB->Execute("DROP TABLE tariffs_t");

$DB->Execute("CREATE TABLE i_t AS SELECT * FROM invoicecontents");
$DB->Execute("DROP TABLE invoicecontents");
$DB->Execute("CREATE TABLE invoicecontents (
	docid integer 		DEFAULT 0 NOT NULL,
	itemid smallint		DEFAULT 0 NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	taxid integer 		DEFAULT 0 NOT NULL,
	pkwiu varchar(255) 	DEFAULT '' NOT NULL,
	content varchar(16) 	DEFAULT '' NOT NULL,
	count numeric(9,2) 	DEFAULT 0 NOT NULL,
	description varchar(255) DEFAULT '' NOT NULL,
	tariffid integer 	DEFAULT 0 NOT NULL)");
$DB->Execute("INSERT INTO invoicecontents (docid, itemid, value, taxid, pkwiu, content, count, description, tariffid) 
	    SELECT docid, itemid, value, taxid, pkwiu, content, count, description, tariffid FROM i_t");
$DB->Execute("DROP TABLE i_t");

$DB->Execute("CREATE TABLE cash_t AS SELECT * FROM cash");
$DB->Execute("DROP TABLE cash");
$DB->Execute("CREATE TABLE cash (
	id integer 		PRIMARY KEY,
	time integer 		DEFAULT 0 NOT NULL,
	userid integer 		DEFAULT 0 NOT NULL,
	type smallint 		DEFAULT 0 NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	taxid integer   	DEFAULT 0 NOT NULL,
	customerid integer 	DEFAULT 0 NOT NULL,
	comment varchar(255) 	DEFAULT '' NOT NULL,
	docid integer 		DEFAULT 0 NOT NULL,
	itemid smallint		DEFAULT 0 NOT NULL,
	reference integer	DEFAULT 0 NOT NULL)");

$DB->Execute("INSERT INTO cash (id, time, userid, type, value, taxid, customerid, comment, docid, itemid) 
	    SELECT id, time, userid, type, value, taxid, customerid, comment, docid, itemid FROM cash_t");
$DB->Execute("DROP TABLE cash_t");

$DB->Execute("CREATE INDEX cash_customerid_idx ON cash(customerid)");
$DB->Execute("CREATE INDEX cash_reference_idx ON cash(reference)");
$DB->Execute("CREATE INDEX cash_docid_idx ON cash(docid)");
$DB->Execute("CREATE INDEX cash_time_idx ON cash(time)");
$DB->Execute("CREATE INDEX invoicecontents_docid_idx ON invoicecontents(docid)");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?",array('2005061200', 'dbversion'));

$DB->CommitTrans();

?>
