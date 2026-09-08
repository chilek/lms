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

if (!$this->ResourceExists('ksefallconsumers', LMSDB::RESOURCE_TYPE_TABLE)) {
    $this->Execute(
        "CREATE SEQUENCE ksefallconsumers_id_seq;
        CREATE TABLE ksefallconsumers (
            id integer DEFAULT nextval('ksefallconsumers_id_seq'::text) NOT NULL,
            divisionid integer
                CONSTRAINT ksefallconsumers_divisionid_fkey REFERENCES divisions (id) ON DELETE CASCADE ON UPDATE CASCADE,
            allconsumers smallint NOT NULL,
            PRIMARY KEY (id)
        )"
    );
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026030600', 'dbversion'));

$this->CommitTrans();
