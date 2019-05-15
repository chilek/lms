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
	CREATE SEQUENCE logtransactions_id_seq;
	CREATE TABLE logtransactions (
		id integer		DEFAULT nextval('logtransactions_id_seq'::text) NOT NULL,
		userid integer		DEFAULT 0 NOT NULL,
		time integer		DEFAULT 0 NOT NULL,
		module varchar(50)	DEFAULT '' NOT NULL,
		PRIMARY KEY (id)
	);
	CREATE INDEX logtransactions_userid_idx ON logtransactions (userid);
	CREATE INDEX logtransactions_time_idx ON logtransactions (time);

	CREATE SEQUENCE logmessages_id_seq;
	CREATE TABLE logmessages (
		id integer		DEFAULT nextval('logmessages_id_seq'::text) NOT NULL,
		transactionid integer	NOT NULL
			REFERENCES logtransactions (id) ON DELETE CASCADE ON UPDATE CASCADE,
		resource integer	DEFAULT 0 NOT NULL,
		operation integer	DEFAULT 0 NOT NULL,
		PRIMARY KEY (id)
	);
	CREATE INDEX logmessages_transactionid_idx ON logmessages (transactionid);
	CREATE INDEX logmessages_resource_idx ON logmessages (resource);
	CREATE INDEX logmessages_operation_idx ON logmessages (operation);

	CREATE TABLE logmessagekeys (
		logmessageid integer	NOT NULL
			REFERENCES logmessages (id) ON DELETE CASCADE ON UPDATE CASCADE,
		name varchar(32)	NOT NULL,
		value integer		NOT NULL
	);
	CREATE INDEX logmessagekeys_logmessageid_idx ON logmessagekeys (logmessageid);
	CREATE INDEX logmessagekeys_name_idx ON logmessagekeys (name);
	CREATE INDEX logmessagekeys_value_idx ON logmessagekeys (value);

	CREATE TABLE logmessagedata (
		logmessageid integer	NOT NULL
			REFERENCES logmessages (id) ON DELETE CASCADE ON UPDATE CASCADE,
		name varchar(32)	NOT NULL,
		value text		DEFAULT ''
	);
	CREATE INDEX logmessagedata_logmessageid_idx ON logmessagedata (logmessageid);
	CREATE INDEX logmessagedata_name_idx ON logmessagedata (name);
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2013042600', 'dbversion'));

$this->CommitTrans();
