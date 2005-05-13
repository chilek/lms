<?php

/*
 * LMS version 1.6-cvs
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
$DB->Execute("CREATE TEMP TABLE temp_tariffs AS SELECT * FROM tariffs");
$DB->Execute("DROP TABLE tariffs");
$DB->Execute("CREATE TABLE tariffs (
	id integer PRIMARY KEY,
	name varchar(255) 	DEFAULT '' NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	taxvalue numeric(9,2) 	DEFAULT 0,
	pkwiu varchar(255) 	DEFAULT '' NOT NULL,
	uprate integer		DEFAULT 0 NOT NULL,
	downrate integer	DEFAULT 0 NOT NULL,
	upceil integer		DEFAULT 0 NOT NULL,
	downceil integer	DEFAULT 0 NOT NULL,
	climit integer		DEFAULT 0 NOT NULL,
	plimit integer		DEFAULT 0 NOT NULL,
	description text	DEFAULT '' NOT NULL,
	UNIQUE (name)
	)
");
$DB->Execute("INSERT INTO tariffs SELECT id, name, value, taxvalue, pkwiu, uprate, downrate, uprate AS upceil, downrate AS downceil, 0 AS climit, 0 AS plimit, description FROM temp_tariffs");
$DB->Execute("DROP TABLE temp_tariffs");
$DB->Execute("UPDATE dbinfo SET keyvalue = '2004061900' WHERE keytype = 'dbversion'");
$DB->Execute("COMMIT");

?>
