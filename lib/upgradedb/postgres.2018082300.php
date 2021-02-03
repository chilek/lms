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
	CREATE SEQUENCE filecontainers_id_seq;
	CREATE TABLE filecontainers (
		id integer DEFAULT nextval('filecontainers_id_seq'::text) NOT NULL,
		creationdate integer NOT NULL DEFAULT 0,
		creatorid integer DEFAULT NULL
			CONSTRAINT filecontainers_creatorid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
		description text NOT NULL,
		netdevid integer DEFAULT NULL
			CONSTRAINT filecontainers_netdevid_fkey REFERENCES netdevices (id) ON DELETE CASCADE ON UPDATE CASCADE,
		netnodeid integer DEFAULT NULL
			CONSTRAINT filecontainers_netnodeid_fkey REFERENCES netnodes (id) ON DELETE CASCADE ON UPDATE CASCADE,
		PRIMARY KEY (id)
	);
	CREATE SEQUENCE files_id_seq;
	CREATE TABLE files (
		id integer DEFAULT nextval('files_id_seq'::text) NOT NULL,
		containerid integer NOT NULL
			CONSTRAINT files_containerid_fkey REFERENCES filecontainers (id) ON DELETE CASCADE ON UPDATE CASCADE,
		filename varchar(255) NOT NULL,
		contenttype varchar(255) NOT NULL,
		md5sum varchar(32) NOT NULL,
		PRIMARY KEY (id),
		CONSTRAINT files_containerid_key UNIQUE (containerid, md5sum)
	);
	CREATE INDEX files_md5sum_idx ON files (md5sum)
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2018082300', 'dbversion'));

$this->CommitTrans();
