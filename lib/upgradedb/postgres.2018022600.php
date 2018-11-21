<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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
	ALTER TABLE records ALTER COLUMN type TYPE varchar(10);
	ALTER TABLE records ALTER COLUMN content TYPE varchar(65535);
	ALTER TABLE records ADD COLUMN ordername varchar(255) DEFAULT NULL;
	ALTER TABLE supermasters ALTER COLUMN ip TYPE inet USING ip::inet;

	CREATE SEQUENCE comments_id_seq;
	CREATE TABLE comments (
		id						integer DEFAULT nextval('comments_id_seq'::text) NOT NULL,
		domain_id				integer NOT NULL
			CONSTRAINT comments_domain_id_fkey REFERENCES domains (id) ON DELETE CASCADE ON UPDATE CASCADE,
		name					varchar(255) NOT NULL,
		type					varchar(10) NOT NULL,
		modified_at				integer NOT NULL,
		account					varchar(40) DEFAULT NULL,
		comment					varchar(65535) NOT NULL,
		PRIMARY KEY				(id),
		CONSTRAINT comments_lowercase_name CHECK (((name)::text = LOWER((name)::text)))
	);
	CREATE INDEX comments_domain_id_idx ON comments (domain_id);
	CREATE INDEX comments_name_type_idx ON comments (name, type);
	CREATE INDEX comments_domain_id_modified_at_idx ON comments (domain_id, modified_at);
	
	CREATE SEQUENCE cryptokeys_id_seq;
	CREATE TABLE cryptokeys (
		id						integer DEFAULT nextval('cryptokeys_id_seq'::text) NOT NULL,
		domain_id				integer
			CONSTRAINT cryptokeys_domain_id_fkey REFERENCES domains (id) ON DELETE CASCADE ON UPDATE CASCADE,
		flags 					integer NOT NULL,
		active					boolean,
		content					text,
		PRIMARY KEY (id)
	);
	CREATE INDEX cryptokeys_domain_id_idx ON cryptokeys (domain_id);
	
	CREATE SEQUENCE tsigkeys_id_seq;
	CREATE TABLE tsigkeys (
		id						integer DEFAULT nextval('tsigkeys_id_seq'::text) NOT NULL,
		name					varchar(255),
		algorithm				varchar(50),
		secret					varchar(255),
		CONSTRAINT tsigkeys_lowercase_name CHECK (((name)::text = LOWER((name)::text))),
		CONSTRAINT tsigkeys_name_key UNIQUE (name, algorithm)
	);
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2018022600', 'dbversion'));

$this->CommitTrans();

?>
