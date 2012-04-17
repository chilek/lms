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
	CREATE SEQUENCE rtcategories_id_seq;
	CREATE TABLE rtcategories (
		id integer		DEFAULT nextval('rtcategories_id_seq'::text) NOT NULL,
		name varchar(255)	DEFAULT '' NOT NULL,
		description text	DEFAULT '' NOT NULL,
		PRIMARY KEY (id),
		UNIQUE (name)
	);
	CREATE SEQUENCE rtcategoryusers_id_seq;
	CREATE TABLE rtcategoryusers (
		id integer		DEFAULT nextval('rtcategoryusers_id_seq'::text) NOT NULL,
		userid integer		NOT NULL
			REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
		categoryid integer	NOT NULL
			REFERENCES rtcategories (id) ON DELETE CASCADE ON UPDATE CASCADE,
		PRIMARY KEY (id),
		CONSTRAINT rtcategories_userid_key UNIQUE (userid, categoryid)
	);
	CREATE SEQUENCE rtticketcategories_id_seq;
	CREATE TABLE rtticketcategories (
		id integer		DEFAUlT nextval('rtticketcategories_id_seq'::text) NOT NULL,
		ticketid integer	NOT NULL
			REFERENCES rttickets (id) ON DELETE CASCADE ON UPDATE CASCADE,
		categoryid integer	NOT NULL
			REFERENCES rtcategories (id) ON DELETE CASCADE ON UPDATE CASCADE,
		PRIMARY KEY (id),
		CONSTRAINT rtticketcategories_ticketid_key UNIQUE (ticketid, categoryid)
	);
");

$DB->Execute("INSERT INTO rtcategories (name, description) VALUES(?, ?)", array('default', 'default category'));
$default_catid = $DB->GetLastInsertID('rtcategories');
$DB->Execute("INSERT INTO rtcategoryusers (userid, categoryid) 
		SELECT id, ? FROM users WHERE deleted = 0",
		array($default_catid));
$DB->Execute("INSERT INTO rtticketcategories (ticketid, categoryid) 
		SELECT id, ? FROM rttickets",
		array($default_catid));

$DB->Execute("INSERT INTO uiconfig (section, var, value) VALUES ('userpanel', 'default_categories', ?)", array($default_catid));

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2011091600', 'dbversion'));

$DB->CommitTrans();

?>
