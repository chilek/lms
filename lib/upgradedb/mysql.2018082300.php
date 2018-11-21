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
	CREATE TABLE filecontainers (
		id int(11) NOT NULL AUTO_INCREMENT,
		creationdate int(11) NOT NULL DEFAULT 0,
		creatorid int(11) DEFAULT NULL,
		description text NOT NULL,
		netdevid int(11) DEFAULT NULL,
		netnodeid int(11) DEFAULT NULL,
		PRIMARY KEY (id),
		CONSTRAINT filecontainers_creatorid_fkey
			FOREIGN KEY (creatorid) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
		CONSTRAINT filecontainers_netdevid_fkey
			FOREIGN KEY (netdevid) REFERENCES netdevices (id) ON DELETE CASCADE ON UPDATE CASCADE,
		CONSTRAINT filecontainers_netnodeid_fkey
			FOREIGN KEY (netnodeid) REFERENCES netnodes (id) ON DELETE CASCADE ON UPDATE CASCADE
	) ENGINE=InnoDB
");

$this->Execute("
	CREATE TABLE files (
		id int(11) NOT NULL AUTO_INCREMENT,
		containerid int(11) NOT NULL,
		filename varchar(255) NOT NULL,
		contenttype varchar(255) NOT NULL,
		md5sum varchar(32) NOT NULL,
		PRIMARY KEY (id),
		INDEX md5sum (md5sum),
		UNIQUE KEY files_containerid_key (containerid, md5sum),
		FOREIGN KEY (containerid) REFERENCES filecontainers (id) ON DELETE CASCADE ON UPDATE CASCADE
	) ENGINE=InnoDB
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2018082300', 'dbversion'));

$this->CommitTrans();

?>
