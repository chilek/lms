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
	ALTER TABLE assignments ADD customerid integer;
	UPDATE assignments SET customerid = userid;
	ALTER TABLE assignments DROP userid;
	ALTER TABLE assignments ALTER customerid SET NOT NULL;
	ALTER TABLE assignments ALTER customerid SET DEFAULT 0;

	ALTER TABLE events ADD customerid integer;
	UPDATE events SET customerid = userid;
	ALTER TABLE events DROP userid;
	ALTER TABLE events ALTER customerid SET NOT NULL;
	ALTER TABLE events ALTER customerid SET DEFAULT 0;

	ALTER TABLE rttickets ADD customerid integer;
	UPDATE rttickets SET customerid = userid;
	ALTER TABLE rttickets DROP userid;
	ALTER TABLE rttickets ALTER customerid SET NOT NULL;
	ALTER TABLE rttickets ALTER customerid SET DEFAULT 0;

	ALTER TABLE rtmessages ADD customerid integer;
	UPDATE rtmessages SET customerid = userid;
	ALTER TABLE rtmessages DROP userid;
	ALTER TABLE rtmessages ALTER customerid SET NOT NULL;
	ALTER TABLE rtmessages ALTER customerid SET DEFAULT 0;

	DROP INDEX cash_userid_idx;
	ALTER TABLE cash ADD customerid integer;
	UPDATE cash SET customerid = userid;
	ALTER TABLE cash DROP userid;
	ALTER TABLE cash ALTER customerid SET NOT NULL;
	ALTER TABLE cash ALTER customerid SET DEFAULT 0;
	CREATE INDEX cash_customerid_idx ON cash(customerid);

	ALTER TABLE userassignments DROP CONSTRAINT userassignments_usergroupid_key;
	ALTER TABLE userassignments ADD customerid integer;
	UPDATE userassignments SET customerid = userid;
	ALTER TABLE userassignments DROP userid;
	ALTER TABLE userassignments ALTER customerid SET NOT NULL;
	ALTER TABLE userassignments ALTER customerid SET DEFAULT 0;
	ALTER TABLE userassignments ADD UNIQUE (usergroupid, customerid);
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2005052300', 'dbversion'));

$this->CommitTrans();
