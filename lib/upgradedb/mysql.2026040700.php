<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2026 LMS Developers
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

if (!$this->ResourceExists('ksefboundarydates', LMSDB::RESOURCE_TYPE_TABLE)) {
    $this->Execute("
        CREATE TABLE ksefboundarydates (
            id int(11) NOT NULL AUTO_INCREMENT,
            divisionid int(11) NOT NULL,
            dt int(16) NOT NULL,
            PRIMARY KEY (id),
            CONSTRAINT ksefboundarydates_divisionid_fkey
                FOREIGN KEY (divisionid) REFERENCES divisions (id) ON DELETE CASCADE ON UPDATE CASCADE,
        ) ENGINE=InnoDB
    ");
}

if (!$this->ResourceExists('ksefshowbalancesummaries', LMSDB::RESOURCE_TYPE_TABLE)) {
    $this->Execute("
        CREATE TABLE ksefshowbalancesummaries (
            id int(11) NOT NULL AUTO_INCREMENT,
            divisionid int(11) NOT NULL,
            showsummary smallint NOT NULL,
            PRIMARY KEY (id),
            CONSTRAINT ksefshowbalancesummaries_divisionid_fkey
                FOREIGN KEY (divisionid) REFERENCES divisions (id) ON DELETE CASCADE ON UPDATE CASCADE,
        ) ENGINE=InnoDB
    ");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026040700', 'dbversion'));

$this->CommitTrans();
