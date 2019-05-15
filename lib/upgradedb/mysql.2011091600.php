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
	CREATE TABLE rtcategories (
		id int(11)		NOT NULL auto_increment,
		name varchar(255)	DEFAULT '' NOT NULL,
		description text	DEFAULT '' NOT NULL,
		PRIMARY KEY (id),
		UNIQUE KEY (name)
	) ENGINE=INNODB");
$this->Execute("
	CREATE TABLE rtcategoryusers (
		id int(11)		NOT NULL auto_increment,
		userid int(11)		NOT NULL
			REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
		categoryid int(11)	NOT NULL
			REFERENCES rtcategories (id) ON DELETE CASCADE ON UPDATE CASCADE,
		PRIMARY KEY (id),
		UNIQUE KEY userid (userid, categoryid)
	) ENGINE=INNODB");
$this->Execute("CREATE TABLE rtticketcategories (
		id int(11)		NOT NULL auto_increment,
		ticketid int(11)	NOT NULL
			REFERENCES rttickets (id) ON DELETE CASCADE ON UPDATE CASCADE,
		categoryid int(11)	NOT NULL
			REFERENCES rtcategories (id) ON DELETE CASCADE ON UPDATE CASCADE,
		PRIMARY KEY (id),
		UNIQUE KEY ticketid (ticketid, categoryid)
	) ENGINE=INNODB");

$this->Execute("INSERT INTO rtcategories (name, description) VALUES(?, ?)", array('default', 'default category'));
$default_catid = $this->GetLastInsertID('rtcategories');
$this->Execute(
    "INSERT INTO rtcategoryusers (userid, categoryid) 
		SELECT id, ? FROM users WHERE deleted = 0",
    array($default_catid)
);
$this->Execute(
    "INSERT INTO rtticketcategories (ticketid, categoryid) 
		SELECT id, ? FROM rttickets",
    array($default_catid)
);

$this->Execute("INSERT INTO uiconfig (section, var, value) VALUES ('userpanel', 'default_categories', ?)", array($default_catid));

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2011091600', 'dbversion'));

$this->CommitTrans();
