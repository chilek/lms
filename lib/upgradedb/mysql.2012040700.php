<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

$DB->BeginTrans();

$DB->Execute("
	CREATE TABLE nodelocks (
		id int(11)		NOT NULL auto_increment,
		nodeid int(11)		NOT NULL
			REFERENCES nodes (id) ON DELETE CASCADE ON UPDATE CASCADE,
		days smallint		DEFAULT 0 NOT NULL,
		fromsec int(11)		DEFAULT 0 NOT NULL,
		tosec int(11)		DEFAULT 0 NOT NULL,
		PRIMARY KEY (id)
	) ENGINE=INNODB");
$DB->Execute("
	INSERT INTO nodelocks (nodeid, days, fromsec, tosec) 
		(SELECT na.nodeid, days, fromsec, tosec FROM assignmentlocks al 
			LEFT JOIN assignments a ON a.id = al.assignmentid 
			LEFT JOIN nodeassignments na ON na.assignmentid = a.id)");
$DB->Execute("DROP TABLE assignmentlocks");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2012040700', 'dbversion'));

$DB->CommitTrans();

?>
