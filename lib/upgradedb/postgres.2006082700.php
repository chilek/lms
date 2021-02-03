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

/* tariffs with nodes many-to-many assignments */

$this->Execute("CREATE SEQUENCE nodeassignments_id_seq");
$this->Execute("CREATE TABLE nodeassignments (
	id integer 		DEFAULT nextval('nodeassignments_id_seq'::text) NOT NULL,
	nodeid integer 		DEFAULT 0 NOT NULL,
	assignmentid integer 	DEFAULT 0 NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (nodeid, assignmentid))
");

if ($assign = $this->GetAll('SELECT id, nodeid FROM assignments WHERE nodeid>0')) {
    foreach ($assign as $item) {
        $this->Execute(
            'INSERT INTO nodeassignments (nodeid, assignmentid) VALUES (?,?)',
            array($item['nodeid'], $item['id'])
        );
    }
}

$this->Execute("ALTER TABLE assignments DROP COLUMN nodeid");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2006082700', 'dbversion'));

$this->CommitTrans();
