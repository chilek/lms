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

if ($this->ResourceExists('ksefdivisions', LMSDB::RESOURCE_TYPE_TABLE)) {
    $this->Execute('DROP TABLE ksefdivisions');
}

if ($this->ResourceExists('ksefdocuments.refnumber', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute('DROP TABLE ksefdocuments');
}

if (!$this->ResourceExists('ksefbatchsessions', LMSDB::RESOURCE_TYPE_TABLE)) {
    $this->Execute("
        CREATE TABLE ksefbatchsessions (
            id int(11) NOT NULL AUTO_INCREMENT,
            ksefnumber varchar(40) NOT NULL,
            cdate int(16) NOT NULL,
            lastupdate int(16) NOT NULL,
            status smallint NOT NULL DEFAULT 0,
            statusdescription text DEFAULT NULL,
            environment smallint NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            INDEX ksefbatchsessions_ksefnumber_idx (ksefnumber),
            INDEX ksefbatchsessions_status_idx (status)
        ) ENGINE=InnoDB
    ");
}

$this->Execute("
    CREATE TABLE ksefdocuments (
        id int(11) NOT NULL AUTO_INCREMENT,
        batchsessionid int(11) NOT NULL,
        docid integer NOT NULL,
        ordinalnumber int(11) NOT NULL,
        ksefnumber varchar(40) DEFAULT NULL,
        hash varchar(50) NOT NULL,
        status smallint NOT NULL DEFAULT 0,
        statusdescription text DEFAULT NULL,
        statusdetails text DEFAULT NULL,
        PRIMARY KEY (id),
        CONSTRAINT ksefbatchsessions_batchsessionid_fkey
            FOREIGN KEY (batchsessionid) REFERENCES ksefbatchsessions (id) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT ksefdocuments_docid_fkey
            FOREIGN KEY (docid) REFERENCES documents (id) ON DELETE RESTRICT ON UPDATE CASCADE,
        INDEX ksefdocuments_status_idx (status)
    ) ENGINE=InnoDB
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026012200', 'dbversion'));

$this->CommitTrans();
