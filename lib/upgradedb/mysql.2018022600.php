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

$this->Execute("ALTER TABLE records MODIFY COLUMN type varchar(10)");
$this->Execute("ALTER TABLE records MODIFY COLUMN content varchar(65535)");
$this->Execute("ALTER TABLE records ADD COLUMN ordername varchar(255) DEFAULT NULL");
$this->Execute("ALTER TABLE supermasters MODIFY COLUMN ip varchar(64)");

$this->Execute("
	CREATE TABLE comments (
		id				int(11) auto_increment,
		domain_id		int(11) NOT NULL,
		name			varchar(255) NOT NULL,
		type			varchar(10) NOT NULL,
		modified_at		int(11) NOT NULL,
		account			varchar(40) CHARACTER SET 'utf8' DEFAULT NULL,
		comment			text NOT NULL,
		PRIMARY KEY (id),
		INDEX domain_id (domain_id),
		INDEX name (name, type),
		INDEX modified_at (domain_id, modified_at),
		CONSTRAINT comments_domain_id_fkey
			FOREIGN KEY (domain_id) REFERENCES domains (id) ON DELETE CASCADE ON UPDATE CASCADE
	) Engine=InnoDB
");

$this->Execute("
	CREATE TABLE cryptokeys (
		id				int(11) auto_increment,
		domain_id		int(11) NOT NULL,
		flags			int(11) NOT NULL,
		active			bool,
		content			text,
		PRIMARY KEY (id),
		INDEX domain_id (domain_id),
		CONSTRAINT cryptokeys_domain_id_fkey
			FOREIGN KEY (domain_id) REFERENCES domains (id) ON DELETE CASCADE ON UPDATE CASCADE
	) Engine=InnoDB
");

$this->Execute("
	CREATE TABLE tsigkeys (
		id				int(11) auto_increment,
		name			varchar(255),
		algorithm		varchar(50),
		secret			varchar(255),
		PRIMARY KEY (id),
		UNIQUE KEY name (name, algorithm)
	) Engine=InnoDB
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2018022600', 'dbversion'));

$this->CommitTrans();

?>
