<?php

/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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

$DB->Execute("BEGIN");
$DB->Execute("CREATE TEMP TABLE tariffs_temp AS SELECT * FROM tariffs");
$DB->Execute("DROP TABLE tariffs");
$DB->Execute("CREATE TABLE tariffs (
	id integer PRIMARY KEY,
	name varchar(255) 	DEFAULT '' NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	taxvalue numeric(9,2) 	DEFAULT 0,
	pkwiu varchar(255) 	DEFAULT '' NOT NULL,
	uprate integer		DEFAULT 0 NOT NULL,
	downrate integer	DEFAULT 0 NOT NULL,
	description text	DEFAULT '' NOT NULL,
	UNIQUE (name)
    )");
$DB->Execute("INSERT INTO tariffs SELECT * FROM tariffs_temp");
$DB->Execute("DROP TABLE tariffs_temp");
    
$DB->Execute("CREATE TEMP TABLE invoicecontents_temp AS SELECT * FROM invoicecontents");
$DB->Execute("DROP TABLE invoicecontents");
$DB->Execute("CREATE TABLE invoicecontents (
	invoiceid integer 	DEFAULT 0 NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	taxvalue numeric(9,2) 	DEFAULT 0,
	pkwiu varchar(255) 	DEFAULT '' NOT NULL,
	content varchar(16) 	DEFAULT '' NOT NULL,
	count numeric(9,2) 	DEFAULT 0 NOT NULL,
	description varchar(255) DEFAULT '' NOT NULL,
	tariffid integer 	DEFAULT 0 NOT NULL
    )");	 
$DB->Execute("INSERT INTO invoicecontents SELECT * FROM invoicecontents_temp");
$DB->Execute("DROP TABLE invoicecontents_temp");
    
$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?",array('2004040701', 'dbversion'));
$DB->Execute("COMMIT");
 

?>
