<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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
    CREATE TABLE sourcefiles (
        id int(11)          NOT NULL auto_increment,
        userid integer     DEFAULT NULL
            REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
        name varchar(255)   NOT NULL,
        idate int(11)       NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY idate (idate, name),
        INDEX userid (userid)
    ) ENGINE=InnoDB
");

$this->Execute("ALTER TABLE cashimport ADD sourcefileid int(11) DEFAULT NULL");
$this->Execute("ALTER TABLE cashimport ADD INDEX sourcefileid (sourcefileid)");
$this->Execute("ALTER TABLE cashimport ADD FOREIGN KEY (sourcefileid)
	REFERENCES sourcefiles (id) ON DELETE SET NULL ON UPDATE CASCADE");

$this->Execute("ALTER TABLE cashimport MODIFY customerid int(11) DEFAULT NULL");
$this->Execute("UPDATE cashimport SET customerid = NULL WHERE customerid NOT IN (SELECT id FROM customers)");
$this->Execute("ALTER TABLE cashimport ADD INDEX customerid (customerid)");
$this->Execute("ALTER TABLE cashimport ADD FOREIGN KEY (customerid)
	REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE");

$this->Execute("UPDATE cashimport SET sourceid = NULL WHERE sourceid NOT IN (SELECT id FROM cashsources)");
$this->Execute("ALTER TABLE cashimport ADD INDEX sourceid (sourceid)");
$this->Execute("ALTER TABLE cashimport ADD FOREIGN KEY (sourceid)
	REFERENCES cashsources (id) ON DELETE SET NULL ON UPDATE CASCADE");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2010122000', 'dbversion'));

$this->CommitTrans();
