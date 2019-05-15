<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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

$this->Execute("ALTER TABLE records ADD COLUMN disabled tinyint(1) DEFAULT '0'");
$this->Execute("ALTER TABLE records ADD COLUMN auth tinyint(1) DEFAULT '1'");
$this->Execute("
	CREATE TABLE domainmetadata (
		id int(11) NOT NULL auto_increment,
		domain_id int(11) NOT NULL,
		kind varchar(32),
		content text,
		PRIMARY KEY (id),
		INDEX domainmetadata (domain_id, kind),
		FOREIGN KEY (domain_id) REFERENCES domains (id) ON DELETE CASCADE ON UPDATE CASCADE
	) Engine=InnoDB");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2015111300', 'dbversion'));

$this->CommitTrans();
