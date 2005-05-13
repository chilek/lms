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

$DB->Execute("BEGIN");
$DB->Execute("CREATE TEMP TABLE cash_temp AS SELECT * FROM cash");
$DB->Execute("DROP TABLE cash");
$DB->Execute("CREATE TABLE cash (
	id integer 		PRIMARY KEY,
	time integer 		DEFAULT 0 NOT NULL,
	adminid integer 	DEFAULT 0 NOT NULL,
	type smallint 		DEFAULT 0 NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	taxvalue numeric(9,2)	DEFAULT 0,
	userid integer 		DEFAULT 0 NOT NULL,
	comment varchar(255) 	DEFAULT '' NOT NULL,
	invoiceid integer 	DEFAULT 0 NOT NULL
    )");
$DB->Execute("INSERT INTO cash(id, time, adminid, type, value, userid, comment, invoiceid) SELECT id, time, adminid, type, value, userid, comment, invoiceid  FROM cash_temp");
$DB->Execute("DROP TABLE cash_temp");
$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?",array('2004040800', 'dbversion'));
$DB->Execute("COMMIT");

?>
