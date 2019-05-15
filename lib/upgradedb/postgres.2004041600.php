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
$this->Execute("
    BEGIN;
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
	usergroupid integer DEFAULT 0 NOT NULL, 
	userid integer DEFAULT 0 NOT NULL, 
	PRIMARY KEY (id),
	UNIQUE (usergroupid, userid)
    );
    UPDATE dbinfo SET keyvalue = '2004041600' WHERE keytype = 'dbversion';
    COMMIT;
");
