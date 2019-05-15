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
	CREATE TABLE logtransactions (
		id int(11)		NOT NULL auto_increment,
		userid int(11)		DEFAULT '0' NOT NULL,
		time int(11)		DEFAULT '0' NOT NULL,
		module varchar(50)	DEFAULT '' NOT NULL,
		PRIMARY KEY (id),
		INDEX userid (userid),
		INDEX time (time)
	) ENGINE=InnoDB
");
$this->Execute("
	CREATE TABLE logmessages (
		id int(11)		NOT NULL auto_increment,
		transactionid int(11)	NOT NULL,
		resource int(11)	DEFAULT '0' NOT NULL,
		operation int(11)	DEFAULT '0' NOT NULL,
		PRIMARY KEY (id),
		INDEX transactionid (transactionid),
		INDEX resource (resource),
		INDEX operation (operation),
		FOREIGN KEY (transactionid) REFERENCES logtransactions (id) ON DELETE CASCADE ON UPDATE CASCADE
	) ENGINE=InnoDB
");
$this->Execute("
	CREATE TABLE logmessagekeys (
		logmessageid int(11)	NOT NULL,
		name varchar(32)	NOT NULL,
		value int(11)		NOT NULL,
		INDEX logmessageid (logmessageid),
		INDEX name (name),
		INDEX value (value),
		FOREIGN KEY (logmessageid) REFERENCES logmessages (id) ON DELETE CASCADE ON UPDATE CASCADE
	) ENGINE=InnoDB
");
$this->Execute("
	CREATE TABLE logmessagedata (
		logmessageid int(11)	NOT NULL,
		name varchar(32)	NOT NULL,
		value text		DEFAULT '',
		INDEX logmessageid (logmessageid),
		INDEX name (name),
		FOREIGN KEY (logmessageid) REFERENCES logmessages (id) ON DELETE CASCADE ON UPDATE CASCADE
	) ENGINE=InnoDB
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2013042600', 'dbversion'));

$this->CommitTrans();
