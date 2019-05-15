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

$this->Execute("CREATE TABLE tarifftags (
	id int(11) NOT NULL AUTO_INCREMENT,
	name varchar(255) NOT NULL,
	description text NULL,
	PRIMARY KEY (id),
	UNIQUE KEY name (name)
    )  ENGINE=InnoDB;");

$this->Execute("CREATE TABLE tariffassignments (
        id int(11) NOT NULL AUTO_INCREMENT,
        tariffid int(11) NOT NULL,
        tarifftagid int(11) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY tariffid_tarifftagid_idx (tariffid,tarifftagid),
        KEY tarifftagid_idx (tarifftagid),
        CONSTRAINT tariffassignments_tariffid_key FOREIGN KEY (tariffid) REFERENCES tariffs (id) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT tariffassignments_tarifftagid_key FOREIGN KEY (tarifftagid) REFERENCES tarifftags (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016070700', 'dbversion'));

$this->CommitTrans();
