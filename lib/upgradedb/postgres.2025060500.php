<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2025 LMS Developers
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

if (!$this->ResourceExists('ksefdocuments', LMSDB::RESOURCE_TYPE_TABLE)) {
    $this->Execute("
        CREATE TABLE ksefdocuments (
            docid integer NOT NULL
                CONSTRAINT ksefdocuments_docid_fkey REFERENCES documents (id) ON DELETE CASCADE ON UPDATE CASCADE,
            refnumber varchar(40) NOT NULL,
            elemrefnumber varchar(40) NOT NULL,
            ksefnumber varchar(40) DEFAULT NULL,
            status smallint NOT NULL DEFAULT 0,
            statusdescription text DEFAULT NULL,
            hash varchar(130) NOT NULL
        );
        CREATE INDEX ksefdocuments_refnumber_idx ON ksefdocuments (refnumber);
        CREATE INDEX ksefdocuments_elemrefnumber_idx ON ksefdocuments (elemrefnumber);
        CREATE INDEX ksefdocuments_ksefnumber_idx ON ksefdocuments (ksefnumber);
        CREATE INDEX ksefdocuments_status_idx ON ksefdocuments (status)
    ");
}

if (!$this->ResourceExists('ksefdivisions', LMSDB::RESOURCE_TYPE_TABLE)) {
    $this->Execute("
        CREATE TABLE ksefdivisions (
            divisionid integer NOT NULL
                CONSTRAINT ksefdivisions_divisionid_fkey REFERENCES divisions (id) ON DELETE CASCADE ON UPDATE CASCADE,
            token varchar(70) NOT NULL
        )
    ");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2025060500', 'dbversion'));

$this->CommitTrans();
