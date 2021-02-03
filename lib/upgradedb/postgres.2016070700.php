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

$this->BeginTrans();

$this->Execute("CREATE SEQUENCE tarifftags_id_seq;");

$this->Execute("CREATE TABLE tarifftags (
	id integer DEFAULT nextval('tarifftags_id_seq'::text) NOT NULL,
	name varchar(255) NOT NULL,
	description text NULL,
	PRIMARY KEY (id),
	UNIQUE (name)
    );");

$this->Execute("CREATE SEQUENCE tariffassignments_id_seq;");

$this->Execute("CREATE TABLE tariffassignments (
	id integer DEFAULT nextval('tariffassignments_id_seq'::text) NOT NULL,
        tariffid integer NOT NULL
            REFERENCES tariffs (id) ON DELETE CASCADE ON UPDATE CASCADE,
        tarifftagid integer NOT NULL
            REFERENCES tarifftags (id) ON DELETE CASCADE ON UPDATE CASCADE,
        PRIMARY KEY (id),
        CONSTRAINT tariffassignments_tarifftagid_key UNIQUE (tariffid,tarifftagid)
);");

$this->Execute("CREATE INDEX tariffassignments_tarifftagid_idx ON tariffassignments (tarifftagid);");


$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016070700', 'dbversion'));

$this->CommitTrans();
