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
$DB->Execute("CREATE TEMP TABLE temp_admins AS SELECT * FROM admins");
$DB->Execute("DROP TABLE admins");
$DB->Execute("CREATE TABLE admins (
	id integer PRIMARY KEY,
	login varchar(32) 	DEFAULT '' NOT NULL,
	name varchar(64) 	DEFAULT '' NOT NULL,
	email varchar(255) 	DEFAULT '' NOT NULL,
	rights varchar(64) 	DEFAULT '' NOT NULL,
	passwd varchar(255) 	DEFAULT '' NOT NULL,
	lastlogindate integer 	DEFAULT 0  NOT NULL,
	lastloginip varchar(16) DEFAULT '' NOT NULL,
	failedlogindate integer DEFAULT 0  NOT NULL,
	failedloginip varchar(16) DEFAULT '' NOT NULL,
	deleted smallint	DEFAULT 0 NOT NULL,
	UNIQUE (login))");
$DB->Execute("INSERT INTO admins SELECT id, login, name, email, rights, passwd, lastlogindate, lastloginip, failedlogindate, failedloginip, 0 AS deleted FROM temp_admins");
$DB->Execute("DROP TABLE temp_admins");
$DB->Execute("UPDATE dbinfo SET keyvalue = '2004042300' WHERE keytype = 'dbversion'");
$DB->Execute("COMMIT");

?>
