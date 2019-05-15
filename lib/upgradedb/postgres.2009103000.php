<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
 *
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

$this->Execute("ALTER TABLE domains ADD master varchar(128) DEFAULT NULL");
$this->Execute("ALTER TABLE domains ADD last_check integer DEFAULT NULL");
$this->Execute("ALTER TABLE domains ADD type varchar(6) DEFAULT '' NOT NULL");
$this->Execute("ALTER TABLE domains ADD notified_serial integer DEFAULT NULL");
$this->Execute("ALTER TABLE domains ADD account varchar(40) DEFAULT NULL");

$this->Execute("
	CREATE SEQUENCE records_id_seq;
	CREATE TABLE records (
		id integer		DEFAULT nextval('records_id_seq'::text) NOT NULL,
		domain_id integer	DEFAULT NULL
			REFERENCES domains (id) ON DELETE CASCADE ON UPDATE CASCADE,
		name varchar(255)	DEFAULT NULL,
		type varchar(6)		DEFAULT NULL,
		content varchar(255)	DEFAULT NULL,
		ttl integer		DEFAULT NULL,
		prio integer		DEFAULT NULL,
		change_date integer	DEFAULT NULL,
		PRIMARY KEY (id)
	);
");

$this->Execute("CREATE INDEX records_name_type_idx ON records (name, type, domain_id)");
$this->Execute("CREATE INDEX records_domain_id_idx ON records (domain_id)");

$this->Execute("
	CREATE SEQUENCE supermasters_id_seq;
	CREATE TABLE supermasters (
		id integer		DEFAULT nextval('supermasters_id_seq'::text) NOT NULL,		
		ip varchar(25)		NOT NULL,
		nameserver varchar(255)	NOT NULL,
		account varchar(40)	DEFAULT NULL,
		PRIMARY KEY (id)
	)
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2009103000', 'dbversion'));

$this->CommitTrans();
