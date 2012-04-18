<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

$DB->BeginTrans();

$DB->Execute("
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

$DB->Execute("ALTER TABLE cashimport ADD sourcefileid int(11) DEFAULT NULL");
$DB->Execute("ALTER TABLE cashimport ADD INDEX sourcefileid (sourcefileid)");
$DB->Execute("ALTER TABLE cashimport ADD FOREIGN KEY (sourcefileid)
	REFERENCES sourcefiles (id) ON DELETE SET NULL ON UPDATE CASCADE");

$DB->Execute("ALTER TABLE cashimport MODIFY customerid int(11) DEFAULT NULL");
$DB->Execute("UPDATE cashimport SET customerid = NULL WHERE customerid NOT IN (SELECT id FROM customers)");
$DB->Execute("ALTER TABLE cashimport ADD INDEX customerid (customerid)");
$DB->Execute("ALTER TABLE cashimport ADD FOREIGN KEY (customerid)
	REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE");

$DB->Execute("UPDATE cashimport SET sourceid = NULL WHERE sourceid NOT IN (SELECT id FROM cashsources)");
$DB->Execute("ALTER TABLE cashimport ADD INDEX sourceid (sourceid)");
$DB->Execute("ALTER TABLE cashimport ADD FOREIGN KEY (sourceid)
	REFERENCES cashsources (id) ON DELETE SET NULL ON UPDATE CASCADE");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2010122000', 'dbversion'));

$DB->CommitTrans();

?>
