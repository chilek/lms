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

$this->BeginTrans();

$this->Execute("
	ALTER TABLE passwd ADD quota_sh integer;
	UPDATE passwd SET quota_sh = 0;
	ALTER TABLE passwd ALTER quota_sh SET NOT NULL;
	ALTER TABLE passwd ALTER quota_sh SET DEFAULT 0;

	ALTER TABLE passwd ADD quota_mail integer;
	UPDATE passwd SET quota_mail = 0;
	ALTER TABLE passwd ALTER quota_mail SET NOT NULL;
	ALTER TABLE passwd ALTER quota_mail SET DEFAULT 0;

	ALTER TABLE passwd ADD quota_www integer;
	UPDATE passwd SET quota_www = 0;
	ALTER TABLE passwd ALTER quota_www SET NOT NULL;
	ALTER TABLE passwd ALTER quota_www SET DEFAULT 0;

	ALTER TABLE passwd ADD quota_ftp integer;
	UPDATE passwd SET quota_ftp = 0;
	ALTER TABLE passwd ALTER quota_ftp SET NOT NULL;
	ALTER TABLE passwd ALTER quota_ftp SET DEFAULT 0;

	ALTER TABLE passwd ADD realname varchar(255);
	UPDATE passwd SET realname = '';
	ALTER TABLE passwd ALTER realname SET NOT NULL;
	ALTER TABLE passwd ALTER realname SET DEFAULT '';
	
	ALTER TABLE passwd ADD createtime integer;
	UPDATE passwd SET createtime = 0;
	ALTER TABLE passwd ALTER createtime SET NOT NULL;
	ALTER TABLE passwd ALTER createtime SET DEFAULT 0;
");

$this->Execute("
	CREATE SEQUENCE daemonhosts_id_seq;
	CREATE TABLE daemonhosts (
	id integer DEFAULT nextval('daemonhosts_id_seq'::text) NOT NULL,
	name varchar(255) DEFAULT '' NOT NULL,
	description text DEFAULT '' NOT NULL,
	lastreload integer DEFAULT 0 NOT NULL,
	reload smallint DEFAULT 0 NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (name))
");
$this->Execute("
	CREATE SEQUENCE daemoninstances_id_seq;
	CREATE TABLE daemoninstances (
	id integer DEFAULT nextval('daemoninstances_id_seq'::text) NOT NULL,
	name varchar(255) DEFAULT '' NOT NULL,
	hostid integer DEFAULT 0 NOT NULL,
	module varchar(255) DEFAULT '' NOT NULL,
	crontab varchar(255) DEFAULT '' NOT NULL,
	priority integer DEFAULT 0 NOT NULL,
	description text DEFAULT '' NOT NULL,
	disabled smallint DEFAULT 0 NOT NULL,
	PRIMARY KEY (id))
");
$this->Execute("
	CREATE SEQUENCE daemonconfig_id_seq;
	CREATE TABLE daemonconfig (
	id integer DEFAULT nextval('daemonconfig_id_seq'::text) NOT NULL,
	instanceid integer DEFAULT 0 NOT NULL,
	var varchar(64) DEFAULT '' NOT NULL,
	value text DEFAULT '' NOT NULL,
	description text DEFAULT '' NOT NULL,
	disabled smallint DEFAULT 0 NOT NULL,
	PRIMARY KEY (id),
	UNIQUE(instanceid, var))
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2005033103', 'dbversion'));

$this->CommitTrans();
