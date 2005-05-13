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

$DB->BeginTrans();

$DB->Execute("
	CREATE TABLE daemonhosts (
	id integer PRIMARY KEY,
	name varchar(255) DEFAULT '' NOT NULL,
	description text DEFAULT '' NOT NULL,
	lastreload integer DEFAULT 0 NOT NULL,
	reload smallint DEFAULT 0 NOT NULL,
	UNIQUE (name))
");
$DB->Execute("
	CREATE TABLE daemoninstances (
	id integer PRIMARY KEY,
	name varchar(255) DEFAULT '' NOT NULL,
	hostid integer DEFAULT 0 NOT NULL,
	module varchar(255) DEFAULT '' NOT NULL,
	crontab varchar(255) DEFAULT '' NOT NULL,
	priority integer DEFAULT 0 NOT NULL,
	description text DEFAULT '' NOT NULL,
	disabled smallint DEFAULT 0 NOT NULL
	)
");
$DB->Execute("
	CREATE TABLE daemonconfig (
	id integer PRIMARY KEY,
	instanceid integer DEFAULT 0 NOT NULL,
	var varchar(64) DEFAULT '' NOT NULL,
	value text DEFAULT '' NOT NULL,
	description text DEFAULT '' NOT NULL,
	disabled smallint DEFAULT 0 NOT NULL,
	UNIQUE(instanceid, var))
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
    domainid integer		DEFAULT '' NOT NULL,
    realname varchar(255)	DEFAULT '' NOT NULL,
    createtime integer		DEFAULT 0 NOT NULL,
    quota_sh integer		DEFAULT 0 NOT NULL,
    quota_mail integer		DEFAULT 0 NOT NULL,
    quota_www integer		DEFAULT 0 NOT NULL,
    quota_ftp integer		DEFAULT 0 NOT NULL,
    UNIQUE (login))
");
$DB->Execute("INSERT INTO passwd (id, ownerid, login, password, lastlogin, uid, home, type, expdate, domainid)
		SELECT id, ownerid, login, password, lastlogin, uid, home, type, expdate, domainid
		FROM passwd_t");
$DB->Execute("DROP TABLE passwd_t");

$DB->Execute("CREATE TEMP TABLE a_t AS SELECT * FROM assignments");
$DB->Execute("DROP TABLE assignments");
$DB->Execute("CREATE TABLE assignments (
	id integer PRIMARY KEY,
	tariffid integer 	DEFAULT 0 NOT NULL,
	userid integer 		DEFAULT 0 NOT NULL,
	period integer 		DEFAULT 0 NOT NULL,
	at integer 		DEFAULT 0 NOT NULL,
	datefrom integer	DEFAULT 0 NOT NULL,
	dateto integer		DEFAULT 0 NOT NULL,
	invoice smallint 	DEFAULT 0 NOT NULL,
	discount numeric(4,2)   DEFAULT 0 NOT NULL,
	suspended smallint	DEFAULT 0 NOT NULL)
");
$DB->Execute("INSERT INTO assignments (id, tariffid, userid, period, at, datefrom, dateto, invoice, suspended)
		SELECT id, tariffid, userid, period, at, datefrom, dateto, invoice, suspended
		FROM a_t");
$DB->Execute("DROP TABLE a_t");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?",array('2005033103', 'dbversion'));

$DB->CommitTrans();

?>
