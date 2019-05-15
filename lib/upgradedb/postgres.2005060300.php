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
	DROP INDEX cash_invoiceid_idx;
	ALTER TABLE cash ADD docid integer;
	UPDATE cash SET docid = invoiceid;
	ALTER TABLE cash DROP invoiceid;
	ALTER TABLE cash ALTER docid SET NOT NULL;
	ALTER TABLE cash ALTER docid SET DEFAULT 0;
	CREATE INDEX cash_docid_idx ON cash(docid); 

	-- by the way change polish column names

	ALTER TABLE customers ADD ssn varchar(11);
	ALTER TABLE customers ADD ten varchar(16);
	UPDATE customers SET ssn = pesel;
	UPDATE customers SET ten = nip;
	ALTER TABLE customers DROP pesel;
	ALTER TABLE customers DROP nip;
	ALTER TABLE customers ALTER ssn SET NOT NULL;
	ALTER TABLE customers ALTER ten SET NOT NULL;
	ALTER TABLE customers ALTER ssn SET DEFAULT '';
	ALTER TABLE customers ALTER ten SET DEFAULT '';

	DROP INDEX invoicecontents_invoiceid_idx;
	ALTER TABLE invoicecontents ADD docid integer;
	UPDATE invoicecontents SET docid = invoiceid;
	ALTER TABLE invoicecontents DROP invoiceid;
	ALTER TABLE invoicecontents ALTER docid SET NOT NULL;
	ALTER TABLE invoicecontents ALTER docid SET DEFAULT 0;
	CREATE INDEX invoicecontents_docid_idx ON invoicecontents(docid); 
	
	CREATE SEQUENCE documents_id_seq;
	CREATE TABLE documents (
		id integer DEFAULT nextval('documents_id_seq'::text) NOT NULL,
		type smallint		DEFAULT 0 NOT NULL,  --new
    		number integer 		DEFAULT 0 NOT NULL,
    		cdate integer 		DEFAULT 0 NOT NULL,
	        customerid integer 	DEFAULT 0 NOT NULL,
		userid integer		DEFAULT 0 NOT NULL,  --new		
    		name varchar(255) 	DEFAULT '' NOT NULL,
    		address varchar(255) 	DEFAULT '' NOT NULL,
		zip varchar(10)		DEFAULT '' NOT NULL,
    		city varchar(32) 	DEFAULT '' NOT NULL,
		ten varchar(16) 	DEFAULT '' NOT NULL, --nip
		ssn varchar(11) 	DEFAULT '' NOT NULL, --pesel
    		paytime smallint 	DEFAULT 0 NOT NULL,
		paytype varchar(255) 	DEFAULT '' NOT NULL,
		PRIMARY KEY (id)
	);
	INSERT INTO documents (id, type, number, cdate, paytime, paytype, customerid, userid, name, address, zip, city, ten, ssn)
		SELECT invoices.id, 1, number, cdate, paytime, paytype, invoices.customerid, cash.userid, name, address, zip, city, nip, pesel
		FROM invoices LEFT JOIN cash ON (invoices.id = cash.docid)
		WHERE cash.type = 4
		GROUP BY invoices.id, number, cdate, paytime, paytype, invoices.customerid, cash.userid, name, address, zip, city, nip, pesel;
	DROP TABLE invoices;
	DROP SEQUENCE invoices_id_seq;
	CREATE INDEX documents_cdate_idx ON documents(cdate);
	SELECT setval('documents_id_seq', MAX(id)) FROM documents;
	
	CREATE TABLE receiptcontents (
		docid integer		DEFAULT 0 NOT NULL,
    		itemid smallint		DEFAULT 0 NOT NULL,
		value numeric(9,2)	DEFAULT 0 NOT NULL,
		description varchar(255) DEFAULT '' NOT NULL
	);
	CREATE INDEX receiptcontents_docid_idx ON receiptcontents(docid);
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2005060300', 'dbversion'));

$this->CommitTrans();
