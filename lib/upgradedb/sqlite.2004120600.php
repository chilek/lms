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

$DB->BeginTrans();
$DB->Execute("
    CREATE TABLE aliases (
	id integer PRIMARY KEY,
	login varchar(255) DEFAULT '' NOT NULL,
	accountid integer DEFAULT 0 NOT NULL,
	UNIQUE (login, accountid)
    )
");
$DB->Execute("
    CREATE TABLE domains (
	id integer PRIMARY KEY,
	name varchar(255) DEFAULT '' NOT NULL,
	description text DEFAULT '' NOT NULL,
	UNIQUE (name)
    )
");

$DB->Execute("CREATE TEMP TABLE passwd_t AS SELECT * FROM passwd");
$DB->Execute("DROP TABLE passwd");
$DB->Execute("CREATE TABLE passwd (
    id integer PRIMARY KEY,
    ownerid integer 		DEFAULT 0 NOT NULL,
    login varchar(200) 		DEFAULT '' NOT NULL,
    password varchar(200) 	DEFAULT '' NOT NULL,
    lastlogin integer 		DEFAULT 0 NOT NULL,
    uid integer 		DEFAULT 0 NOT NULL,
    home varchar(255) 		DEFAULT '' NOT NULL,
    type smallint 		DEFAULT 0 NOT NULL,
    expdate integer		DEFAULT 0 NOT NULL,
    domainid integer		DEFAULT 0 NOT NULL,
    UNIQUE (login)
    )
");
$DB->Execute("INSERT INTO passwd(id, ownerid, login, password, lastlogin, uid, home, type, expdate, domain, domainid) 
		SELECT id, ownerid, login, password, lastlogin, uid, home, type, expdate, domain, 0
		FROM passwd_t");
$DB->Execute("DROP TABLE passwd_t");

if($domains = $DB->GetAll('SELECT id, name FROM domains'))
	foreach($domains as $row)
		$DB->Execute('UPDATE passwd SET domainid=? WHERE domain=?', array($row['id'], $row['name']));

$DB->Execute("CREATE TEMP TABLE passwd_t AS SELECT * FROM passwd");
$DB->Execute("DROP TABLE passwd");
$DB->Execute("CREATE TABLE passwd (
    id integer PRIMARY KEY,
    ownerid integer 		DEFAULT 0 NOT NULL,
    login varchar(200) 		DEFAULT '' NOT NULL,
    password varchar(200) 	DEFAULT '' NOT NULL,
    lastlogin integer 		DEFAULT 0 NOT NULL,
    uid integer 		DEFAULT 0 NOT NULL,
    home varchar(255) 		DEFAULT '' NOT NULL,
    type smallint 		DEFAULT 0 NOT NULL,
    expdate integer		DEFAULT 0 NOT NULL,
    domainid integer		DEFAULT 0 NOT NULL,
    UNIQUE (login)
    )
");
$DB->Execute("INSERT INTO passwd(id, ownerid, login, password, lastlogin, uid, home, type, expdate, domainid) 
		SELECT id, ownerid, login, password, lastlogin, uid, home, type, expdate, domainid 
		FROM passwd_t");
$DB->Execute("DROP TABLE passwd_t");

$DB->Execute("UPDATE dbinfo SET keyvalue = '2004120600' WHERE keytype = 'dbversion'");
$DB->CommitTrans();

?>
