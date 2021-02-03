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
    CREATE SEQUENCE nodegroups_id_seq;
    CREATE TABLE nodegroups (
	id 		integer 	NOT NULL DEFAULT nextval('nodegroups_id_seq'::text),
        name		varchar(255) 	NOT NULL DEFAULT '',
	description	text 		NOT NULL DEFAULT '',
	PRIMARY KEY (id),
	UNIQUE (name)
    );

    CREATE SEQUENCE nodegroupassignments_id_seq;
    CREATE TABLE nodegroupassignments (
	id 		integer 	NOT NULL DEFAULT nextval('nodegroupassignments_id_seq'::text),
        nodegroupid	integer 	NOT NULL DEFAULT 0,
	nodeid		integer		NOT NULL DEFAULT 0,
	PRIMARY KEY (id),
	CONSTRAINT nodegroupassignments_nodeid_key UNIQUE (nodeid, nodegroupid)
    );
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2008010400', 'dbversion'));

$this->CommitTrans();
