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
    CREATE TABLE usergroups (
	id int(11) NOT NULL auto_increment, 
	name varchar(255) NOT NULL default '', 
	description text NOT NULL, 
	PRIMARY KEY (id), 
	UNIQUE KEY name (name)
    )
");
$this->Execute("
    CREATE TABLE userassignments (
	id int(11) NOT NULL auto_increment, 
	usergroupid int(11) NOT NULL default '0', 
	userid int(11) NOT NULL default '0', 
	PRIMARY KEY (id),
	UNIQUE KEY userassignment (usergroupid, userid)
    )
");
$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2004041600', 'dbversion'));
