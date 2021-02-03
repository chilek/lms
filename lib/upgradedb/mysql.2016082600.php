<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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
	CREATE TABLE documentattachments (
		id int(11) NOT NULL AUTO_INCREMENT,
		docid int(11) NOT NULL,
		filename varchar(255) NOT NULL,
		contenttype varchar(255) NOT NULL,
		md5sum varchar(32) NOT NULL,
		main smallint DEFAULT 1 NOT NULL,
		PRIMARY KEY (id),
		INDEX md5sum (md5sum),
		UNIQUE KEY docid (docid, md5sum),
		FOREIGN KEY (docid) REFERENCES documents (id) ON DELETE CASCADE ON UPDATE CASCADE
	) ENGINE=InnoDB");

$this->Execute("DELETE FROM documentcontents WHERE docid NOT IN (SELECT id FROM documents)");
$this->Execute("ALTER TABLE documentcontents CHANGE docid docid integer NOT NULL");
$this->Execute("ALTER TABLE documentcontents ADD FOREIGN KEY (docid)
	REFERENCES documents (id) ON DELETE CASCADE ON UPDATE CASCADE");

$this->Execute("INSERT INTO documentattachments (docid, filename, contenttype, md5sum, main)
	(SELECT docid, filename, contenttype, md5sum, (CASE WHEN contenttype = 'text/html' THEN 1 ELSE 0 END)
		FROM documentcontents)");

$this->Execute("ALTER TABLE documentcontents DROP COLUMN filename");
$this->Execute("ALTER TABLE documentcontents DROP COLUMN contenttype");
$this->Execute("ALTER TABLE documentcontents DROP COLUMN md5sum");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016082600', 'dbversion'));

$this->CommitTrans();
