<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2014 LMS Developers
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
 */

$DB->BeginTrans();

$DB->Execute("
	CREATE SEQUENCE invprojects_id_seq;
	CREATE TABLE invprojects (
		id integer DEFAULT nextval('invprojects_id_seq'::text) NOT NULL,
		name varchar(255) NOT NULL,
		type varchar(255) DEFAULT '',
		PRIMARY KEY(id)
	);
");

$DB->Execute("
	CREATE SEQUENCE netdevclusters_id_seq;
	CREATE TABLE netdevclusters (
		id integer DEFAULT nextval('netdevclusters_id_seq'::text) NOT NULL,
		name varchar(255) NOT NULL,
		type smallint DEFAULT 0,
		invprojectid integer  REFERENCES invprojects (id),
		status smallint DEFAULT 0,
		location varchar(255) DEFAULT '',
		location_city integer DEFAULT NULL,
		location_street integer DEFAULT NULL,
		location_house varchar(8) DEFAULT NULL,
		location_flat varchar(8) DEFAULT NULL,
		longitude numeric(10,6) DEFAULT NULL,
		latitude numeric(10,6) DEFAULT NULL,
		PRIMARY KEY(id)
	);
");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2014091601', 'dbversion'));

$DB->CommitTrans();

?>
