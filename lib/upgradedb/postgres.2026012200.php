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
        CREATE SEQUENCE ksefbatchsessions_id_seq;
        CREATE TABLE ksefbatchsessions (
            id integer DEFAULT nextval('ksefbatchsessions_id_seq'::text) NOT NULL,
            ksefnumber varchar(40) NOT NULL,
            cdate bigint NOT NULL,
            lastupdate bigint NOT NULL,
            status smallint NOT NULL DEFAULT 0,
            statusdescription text DEFAULT NULL,
            environment smallint NOT NULL DEFAULT 0,
            PRIMARY KEY (id)
        );
        CREATE INDEX ksefbatchsessions_ksefnumber_idx ON ksefbatchsessions (ksefnumber);
        CREATE INDEX ksefbatchsessions_status_idx ON ksefbatchsessions (status)
    ");
}

$this->Execute("
    CREATE SEQUENCE ksefdocuments_id_seq;
    CREATE TABLE ksefdocuments (
        id integer DEFAULT nextval('ksefdocuments_id_seq'::text) NOT NULL,
        batchsessionid integer NOT NULL
            CONSTRAINT ksefbatchsessions_batchsessionid_fkey REFERENCES ksefbatchsessions (id) ON DELETE CASCADE ON UPDATE CASCADE,
        docid integer NOT NULL
            CONSTRAINT ksefdocuments_docid_fkey REFERENCES documents (id) ON DELETE RESTRICT ON UPDATE CASCADE,
        ordinalnumber integer NOT NULL,
        ksefnumber varchar(40) DEFAULT NULL,
        hash varchar(50) NOT NULL,
        status smallint NOT NULL DEFAULT 0,
        statusdescription text DEFAULT NULL,
        statusdetails text DEFAULT NULL,
        PRIMARY KEY (id)
    );
    CREATE INDEX ksefdocuments_status_idx ON ksefdocuments (status)
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026012200', 'dbversion'));

$this->CommitTrans();
