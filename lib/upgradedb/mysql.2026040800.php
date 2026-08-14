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

if ($this->ResourceExists('ksefdelays', LMSDB::RESOURCE_TYPE_TABLE)) {
    $this->Execute("DROP TABLE ksefdelays");
}

if ($this->ResourceExists('ksefallconsumers', LMSDB::RESOURCE_TYPE_TABLE)) {
    $this->Execute("DROP TABLE ksefallconsumers");
}

if ($this->ResourceExists('ksefboundarydates', LMSDB::RESOURCE_TYPE_TABLE)) {
    $this->Execute("DROP TABLE ksefboundarydates");
}

if ($this->ResourceExists('ksefshowbalancesummaries', LMSDB::RESOURCE_TYPE_TABLE)) {
    $this->Execute("DROP TABLE ksefshowbalancesummaries");
}

if (!$this->ResourceExists('ksefconfig', LMSDB::RESOURCE_TYPE_TABLE)) {
    $this->Execute("
        CREATE TABLE ksefconfig (
            id int(11) NOT NULL AUTO_INCREMENT,
            divisionid int(11) NOT NULL,
            delay int(11) NOT NULL,
            allconsumers smallint NOT NULL,
            boundarydate int(16) NOT NULL,
            showbalancesummary smallint NOT NULL,
            PRIMARY KEY (id),
            CONSTRAINT ksefconfig_divisionid_fkey
                FOREIGN KEY (divisionid) REFERENCES divisions (id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB
    ");
}


$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026040800', 'dbversion'));

$this->CommitTrans();
