<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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
CREATE SEQUENCE imessengers_id_seq;
CREATE TABLE imessengers (
  id integer 		DEFAULT nextval('imessengers_id_seq'::text) NOT NULL, 
  customerid integer	DEFAULT 0 NOT NULL, 
  uid varchar(32) 	DEFAULT '' NOT NULL,
  type smallint		DEFAULT 0 NOT NULL,
  PRIMARY KEY (id) 
  );
");

$DB->Execute("INSERT INTO imessengers (customerid, uid) 
	SELECT id, im::text FROM customers WHERE im > 0");

$DB->Execute("CREATE INDEX imessengers_customerid_idx ON imessengers (customerid)");

$DB->Execute("ALTER TABLE customers DROP COLUMN im");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?",array('2007033100', 'dbversion'));

$DB->CommitTrans();

?>
