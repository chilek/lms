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

$DB->Execute("
	CREATE TABLE aliasassignments (
		id		int(11)		NOT NULL auto_increment,
		aliasid		int(11)		DEFAULT '0' NOT NULL,
		accountid	int(11)		DEFAULT '0' NOT NULL,
		PRIMARY KEY (id),
		UNIQUE KEY aliasid (aliasid, accountid)
	)
");
$DB->Execute("ALTER TABLE aliases ADD domainid integer NOT NULL DEFAULT '0'");
	
$DB->Execute("UPDATE aliases SET domainid = (SELECT domainid FROM passwd WHERE id = accountid)");

$DB->Execute("INSERT INTO aliasassignments (aliasid, accountid) 
		SELECT id, accountid FROM aliases");
	
$DB->Execute("ALTER TABLE aliases DROP accountid");
$DB->Execute("ALTER TABLE aliases ADD UNIQUE KEY (login, domainid)");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2008021500', 'dbversion'));

$DB->CommitTrans();

?>
