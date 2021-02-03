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
    CREATE SEQUENCE \"events_id_seq\";
    CREATE TABLE events (
	id integer default nextval('rtqueues_id_seq'::text) NOT NULL,
	title varchar(255) DEFAULT '' NOT NULL,
	description text DEFAULT '' NOT NULL,
	note text DEFAULT '' NOT NULL,
	date integer DEFAULT 0 NOT NULL,
	begintime smallint DEFAULT 0 NOT NULL,
	endtime smallint DEFAULT 0 NOT NULL,
	adminid integer DEFAULT 0 NOT NULL,
	userid integer DEFAULT 0 NOT NULL,
	private smallint DEFAULT 0 NOT NULL,
	closed smallint DEFAULT 0 NOT NULL,
	PRIMARY KEY (id))
");

$this->Execute("
    CREATE TABLE eventassignments (
	eventid integer DEFAULT 0 NOT NULL,
	adminid integer DEFAULT 0 NOT NULL,
	UNIQUE (eventid, adminid))
");

$this->Execute("CREATE INDEX events_date_idx ON events(date)");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2005012600', 'dbversion'));

$this->CommitTrans();
