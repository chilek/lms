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

$this->Execute("ALTER TABLE customers CHANGE pesel ssn varchar(11) DEFAULT '' NOT NULL");
$this->Execute("ALTER TABLE customers CHANGE nip ten varchar(16) DEFAULT '' NOT NULL");

$this->Execute("ALTER TABLE cash DROP INDEX invoiceid");
$this->Execute("ALTER TABLE cash CHANGE invoiceid docid INT(11) DEFAULT '0' NOT NULL");
$this->Execute("ALTER TABLE cash ADD INDEX docid (docid)");

$this->Execute("ALTER TABLE invoicecontents DROP INDEX invoiceid");
$this->Execute("ALTER TABLE invoicecontents CHANGE invoiceid docid INT(11) DEFAULT '0' NOT NULL");
$this->Execute("ALTER TABLE invoicecontents ADD INDEX docid (docid)");

$this->Execute("CREATE TABLE documents (
	id int(11) NOT NULL auto_increment,
	type tinyint NOT NULL DEFAULT '0',
	number int(11) NOT NULL DEFAULT '0',
	cdate int(11) NOT NULL DEFAULT '0',
        customerid int(11) NOT NULL DEFAULT '0',
	userid int(11) NOT NULL DEFAULT '0',
	name varchar(255) NOT NULL DEFAULT '',
	address varchar(255) NOT NULL DEFAULT '',
	zip varchar(10) NOT NULL DEFAULT '',
	city varchar(32) NOT NULL DEFAULT '',
	ten varchar(16) NOT NULL DEFAULT '',
	ssn varchar(11) NOT NULL DEFAULT '',
	paytime tinyint NOT NULL DEFAULT '0',
	paytype varchar(255) NOT NULL DEFAULT '',
	PRIMARY KEY (id)
)");
$this->Execute("INSERT INTO documents (id, type, number, cdate, paytime, paytype, customerid, userid, name, address, zip, city, ten, ssn)
	SELECT invoices.id, 1, number, cdate, paytime, paytype, invoices.customerid, cash.userid, name, address, zip, city, nip, pesel
	FROM invoices LEFT JOIN cash ON (invoices.id = cash.docid)
	WHERE cash.type = 4
	GROUP BY invoices.id, number, cdate, paytime, paytype, invoices.customerid, cash.userid, name, address, zip, city, nip, pesel");
$this->Execute("DROP TABLE invoices");
$this->Execute("ALTER TABLE documents ADD INDEX cdate (cdate)");
    
$this->Execute("CREATE TABLE receiptcontents (
	docid INT(11) NOT NULL DEFAULT '0',
	itemid TINYINT NOT NULL DEFAULT '0',
	value decimal(9,2) NOT NULL DEFAULT '0',
	description varchar(255) NOT NULL DEFAULT '',
	INDEX docid (docid))
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2005060300', 'dbversion'));
