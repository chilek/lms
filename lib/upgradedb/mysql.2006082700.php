<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

/* tariffs with nodes many-to-many assignments */

$DB->Execute("CREATE TABLE nodeassignments (
	id int(11) NOT NULL auto_increment,
	nodeid int(11) NOT NULL DEFAULT '0',
	assignmentid int(11) NOT NULL DEFAULT '0',
	PRIMARY KEY (id),
	UNIQUE KEY nodeid (nodeid, assignmentid)
) ENGINE=MyISAM;
");

if($assign = $DB->GetAll('SELECT id, nodeid FROM assignments WHERE nodeid!=0'))
{
	foreach($assign as $item)
		$DB->Execute('INSERT INTO nodeassignments (nodeid, assignmentid) VALUES (?,?)',
			array($item['nodeid,'], $item['id']));
}

$DB->Execute("ALTER TABLE assignments DROP COLUMN nodeid");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?",array('2006082700', 'dbversion'));

$DB->CommitTrans();

?>
