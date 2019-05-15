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
	ALTER TABLE cash ADD userid integer;
	UPDATE cash SET userid = adminid;
	ALTER TABLE cash DROP adminid;
	ALTER TABLE cash ALTER userid SET NOT NULL;
	ALTER TABLE cash ALTER userid SET DEFAULT 0;

	ALTER TABLE rtmessages ADD userid integer;
	UPDATE rtmessages SET userid = adminid;
	ALTER TABLE rtmessages DROP adminid;
	ALTER TABLE rtmessages ALTER userid SET NOT NULL;
	ALTER TABLE rtmessages ALTER userid SET DEFAULT 0;

	ALTER TABLE events ADD userid integer;
	UPDATE events SET userid = adminid;
	ALTER TABLE events DROP adminid;
	ALTER TABLE events ALTER userid SET NOT NULL;
	ALTER TABLE events ALTER userid SET DEFAULT 0;

	ALTER TABLE rtrights DROP CONSTRAINT rtrights_adminid_key;	
	ALTER TABLE rtrights ADD userid integer;
	UPDATE rtrights SET userid = adminid;
	ALTER TABLE rtrights DROP adminid;
	ALTER TABLE rtrights ALTER userid SET NOT NULL;
	ALTER TABLE rtrights ALTER userid SET DEFAULT 0;
	ALTER TABLE rtrights ADD UNIQUE (userid, queueid);

	ALTER TABLE eventassignments DROP CONSTRAINT eventassignments_eventid_key;	
	ALTER TABLE eventassignments ADD userid integer;
	UPDATE eventassignments SET userid = adminid;
	ALTER TABLE eventassignments DROP adminid;
	ALTER TABLE eventassignments ALTER userid SET NOT NULL;
	ALTER TABLE eventassignments ALTER userid SET DEFAULT 0;
	ALTER TABLE eventassignments ADD UNIQUE (eventid, userid);

	ALTER TABLE admins DROP CONSTRAINT admins_login_key;	
	CREATE SEQUENCE users_id_seq;
	CREATE TABLE users AS SELECT * FROM admins;
	SELECT setval('users_id_seq', nextval('admins_id_seq'));
	DROP SEQUENCE admins_id_seq;
	ALTER TABLE users ADD UNIQUE (login);
	DROP TABLE admins;
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2005052700', 'dbversion'));

$this->CommitTrans();
