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

$DB->BeginTrans();

$DB->Execute("

CREATE SEQUENCE divisions_id_seq;
CREATE TABLE divisions (
    	id 		integer DEFAULT nextval('divisions_id_seq'::text) NOT NULL ,
	shortname 	varchar(255) NOT NULL DEFAULT '',
	name 		text 	NOT NULL DEFAULT '',
	address		varchar(255) NOT NULL DEFAULT '',
	city		varchar(255) NOT NULL DEFAULT '',
	zip		varchar(255) NOT NULL DEFAULT '',
	account		varchar(48) NOT NULL DEFAULT '',
	inv_header 	text	NOT NULL DEFAULT '',
	inv_footer 	text	NOT NULL DEFAULT '',
	inv_author	text	NOT NULL DEFAULT '',
	inv_cplace	text	NOT NULL DEFAULT '',
	description 	text	NOT NULL DEFAULT '',
	status 		smallint NOT NULL DEFAULT 0,
	PRIMARY KEY (id),
	UNIQUE (shortname)
);

ALTER TABLE documents ADD divisionid integer NOT NULL DEFAULT 0;
UPDATE documents SET divisionid = 1;

ALTER TABLE customers ADD divisionid integer NOT NULL DEFAULT 0;
UPDATE customers SET divisionid = 1;

DROP VIEW customersview;
CREATE VIEW customersview AS
SELECT c.* FROM customers c
        WHERE NOT EXISTS (
	SELECT 1 FROM customerassignments a
		JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
		WHERE e.userid = lms_current_user() AND a.customerid = c.id);


");

$DB->Execute("INSERT INTO divisions (shortname, inv_header, inv_footer, inv_author, inv_cplace, name, address, city, zip, account)
		VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
	array(!empty(ConfigHelper::getConfig('finances.shortname')) && ConfigHelper::getConfig('finances.shortname') != 'finances/shortname' ? ConfigHelper::getConfig('finances.shortname') : 'default',
		!empty(ConfigHelper::getConfig('invoices.header')) ? str_replace("\\n", "\n", ConfigHelper::getConfig('invoices.header')) : '',
		!empty(ConfigHelper::getConfig('invoices.footer')) ? str_replace("\\n", "\n", ConfigHelper::getConfig('invoices.footer')) : '',
		!empty(ConfigHelper::getConfig('invoices.default_author')) ? ConfigHelper::getConfig('invoices.default_author') : '',
		!empty(ConfigHelper::getConfig('invoices.cplace')) ? ConfigHelper::getConfig('invoices.cplace') : '',
		!empty(ConfigHelper::getConfig('finances.name')) && ConfigHelper::getConfig('finances.name') != 'finances/name' ? ConfigHelper::getConfig('finances.name') : 'default',
		!empty(ConfigHelper::getConfig('finances.address')) && ConfigHelper::getConfig('finances.address') != 'finances/address' ? ConfigHelper::getConfig('finances.address') : '',
		!empty(ConfigHelper::getConfig('finances.city')) && ConfigHelper::getConfig('finances.city') != 'finances/city' ? ConfigHelper::getConfig('finances.city') : '',
		!empty(ConfigHelper::getConfig('finances.zip')) && ConfigHelper::getConfig('finances.zip') != 'finances/zip' ? ConfigHelper::getConfig('finances.zip') : '',
		!empty(ConfigHelper::getConfig('finances.account')) ? ConfigHelper::getConfig('finances.account') : '',
	));

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2008102000', 'dbversion'));

$DB->CommitTrans();

?>
