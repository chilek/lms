<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

$this->Execute("
	CREATE SEQUENCE nodelocks_id_seq;
	CREATE TABLE nodelocks (
		id integer		DEFAULT nextval('nodelocks_id_seq'::text) NOT NULL,
		nodeid integer		NOT NULL
			REFERENCES nodes (id) ON DELETE CASCADE ON UPDATE CASCADE,
		days smallint		DEFAULT 0 NOT NULL,
		fromsec integer		DEFAULT 0 NOT NULL,
		tosec integer		DEFAULT 0 NOT NULL,
		PRIMARY KEY (id)
	);
	INSERT INTO nodelocks (nodeid, days, fromsec, tosec) 
		(SELECT na.nodeid, days, fromsec, tosec FROM assignmentlocks al 
			LEFT JOIN assignments a ON a.id = al.assignmentid 
			LEFT JOIN nodeassignments na ON na.assignmentid = a.id);
	DROP TABLE assignmentlocks;
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2012040700', 'dbversion'));

$this->CommitTrans();
