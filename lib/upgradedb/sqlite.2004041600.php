<?php

/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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
$DB->Execute("BEGIN");
$DB->Execute("CREATE TABLE usergroups (
	id integer PRIMARY KEY, 
	name varchar(255) DEFAULT '' NOT NULL, 
	description text DEFAULT '' NOT NULL, 
	UNIQUE (name))");
$DB->Execute("CREATE TABLE userassignments (
	id integer PRIMARY KEY, 
	usergroupid integer DEFAULT 0 NOT NULL, 
	userid integer DEFAULT 0 NOT NULL, 
	UNIQUE (usergroupid, userid))");
$DB->Execute("UPDATE dbinfo SET keyvalue = '2004041600' WHERE keytype = 'dbversion'");
$DB->Execute("COMMIT");

?>
