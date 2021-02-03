<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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
/**
 * @author Maciej_Wawryk
 */

$this->BeginTrans();

$this->Execute("
	CREATE SEQUENCE usergroups_id_seq;
	CREATE TABLE usergroups (
		id integer DEFAULT nextval('usergroups_id_seq'::text) NOT NULL,
		name varchar(255) DEFAULT '' NOT NULL,
		description text DEFAULT '' NOT NULL,
		PRIMARY KEY (id),
		UNIQUE (name)
	);
	CREATE SEQUENCE userassignments_id_seq;
	CREATE TABLE userassignments (
		id integer DEFAULT nextval('userassignments_id_seq'::text) NOT NULL,
		usergroupid integer NOT NULL
			REFERENCES usergroups (id) ON DELETE CASCADE ON UPDATE CASCADE,
		userid integer NOT NULL
			REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
		PRIMARY KEY (id),
		UNIQUE (usergroupid, userid)
	);
	CREATE INDEX userassignments_userid_idx ON userassignments (userid)
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2015121800', 'dbversion'));

$this->CommitTrans();
