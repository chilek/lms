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

if($temp = $DB->GetOne('SELECT value FROM uiconfig WHERE section=? AND var=? AND disabled=0', 
		array('invoices', 'number_template')))
	$CONFIG['invoices']['number_template'] = $temp;
	
if($temp = $DB->GetOne('SELECT value FROM uiconfig WHERE section=? AND var=? AND disabled=0', 
		array('receipts', 'number_template')))
	$CONFIG['receipts']['number_template'] = $temp;

if($temp = $DB->GetOne('SELECT value FROM uiconfig WHERE section=? AND var=? AND disabled=0', 
		array('invoices', 'monthly_numbering')))
	$CONFIG['invoices']['monthly_numbering'] = $temp;

if($temp = $DB->GetOne('SELECT value FROM uiconfig WHERE section=? AND var=? AND disabled=0', 
		array('receipts', 'monthly_numbering')))
	$CONFIG['receipts']['monthly_numbering'] = $temp;


$DB->BeginTrans();

$DB->Execute("CREATE TABLE numberplans (
	id integer PRIMARY KEY,
	template varchar(255) DEFAULT '' NOT NULL,
	period smallint DEFAULT 0 NOT NULL,
	doctype integer DEFAULT 0 NOT NULL,
	isdefault smallint DEFAULT 0 NOT NULL
	)
");

$DB->Execute("INSERT INTO numberplans (template, period, doctype, isdefault) VALUES(?,?,1,1)", 
		array(str_replace('%M','%m',$CONFIG['invoices']['number_template']), $CONFIG['invoices']['monthly_numbering'] ? 3 : 5));
$DB->Execute("INSERT INTO numberplans (template, period, doctype, isdefault) VALUES(?,?,2,1)", 
		array(str_replace('%M','%m',$CONFIG['receipts']['number_template']), $CONFIG['receipts']['monthly_numbering'] ? 3 : 5));

$DB->Execute("CREATE TEMP TABLE doc AS SELECT * FROM documents");
$DB->Execute("DROP TABLE documents");
$DB->Execute("CREATE TABLE documents (
	id integer PRIMARY KEY,
	type smallint		DEFAULT 0 NOT NULL,
    	number integer 		DEFAULT 0 NOT NULL,
	numberplanid integer	DEFAULT 0 NOT NULL,
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
	paytype varchar(255) 	DEFAULT '' NOT NULL
)");
$DB->Execute("INSERT INTO documents (id, type, number, numberplanid, cdate, customerid, userid, name, address, zip, city, ten, ssn, paytime, paytype)
		SELECT id, type, number, type, cdate, customerid, userid, name, address, zip, city, ten, ssn, paytime, paytype FROM doc");
$DB->Execute("DROP TABLE doc");

$DB->Execute("CREATE INDEX documents_numberplanid_idx ON documents(numberplanid)");
$DB->Execute("CREATE INDEX documents_cdate_idx ON documents(cdate)");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?",array('2005080300', 'dbversion'));

$DB->CommitTrans();

?>
