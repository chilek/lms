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
	CREATE TABLE aliasassignments (
		id		int(11)		NOT NULL auto_increment,
		aliasid		int(11)		DEFAULT '0' NOT NULL,
		accountid	int(11)		DEFAULT '0' NOT NULL,
		PRIMARY KEY (id),
		UNIQUE KEY aliasid (aliasid, accountid)
	)
");
$this->Execute("ALTER TABLE aliases ADD domainid integer NOT NULL DEFAULT '0'");
    
$this->Execute("UPDATE aliases SET domainid = (SELECT domainid FROM passwd WHERE id = accountid)");

$this->Execute("INSERT INTO aliasassignments (aliasid, accountid) 
		SELECT id, accountid FROM aliases");
    
$this->Execute("ALTER TABLE aliases DROP accountid");
$this->Execute("ALTER TABLE aliases ADD UNIQUE KEY (login, domainid)");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2008021500', 'dbversion'));

$this->CommitTrans();
