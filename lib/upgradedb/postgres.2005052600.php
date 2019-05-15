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


	ALTER TABLE userassignments ADD customergroupid integer;
	UPDATE userassignments SET customergroupid = usergroupid;
	ALTER TABLE userassignments DROP usergroupid;
	ALTER TABLE userassignments ALTER customergroupid SET NOT NULL;
	ALTER TABLE userassignments ALTER customergroupid SET DEFAULT 0;
	CREATE SEQUENCE customerassignments_id_seq;
	CREATE TABLE customerassignments AS SELECT * FROM userassignments;
	SELECT setval('customerassignments_id_seq', nextval('userassignments_id_seq'));
	DROP SEQUENCE userassignments_id_seq;
	ALTER TABLE customerassignments ADD UNIQUE (customergroupid, customerid);
	DROP TABLE userassignments;

	ALTER TABLE usergroups DROP CONSTRAINT usergroups_name_key;	
	CREATE SEQUENCE customergroups_id_seq;
	CREATE TABLE customergroups AS SELECT * FROM usergroups;
	SELECT setval('customergroups_id_seq', nextval('usergroups_id_seq'));
	DROP SEQUENCE usergroups_id_seq;
	ALTER TABLE customergroups ADD UNIQUE (name);
	DROP TABLE usergroups;

	CREATE SEQUENCE customers_id_seq;
	CREATE TABLE customers AS SELECT * FROM users;
	SELECT setval('customers_id_seq', nextval('users_id_seq'));
	DROP SEQUENCE users_id_seq;
	DROP TABLE users;
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2005052600', 'dbversion'));

$this->CommitTrans();
