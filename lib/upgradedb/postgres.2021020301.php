<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2021 LMS Developers
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
    CREATE TABLE customercontactproperties (
    contactid   integer         NOT NULL
        CONSTRAINT customercontactproperties_contactid_fkey REFERENCES customercontacts (id) ON DELETE CASCADE ON UPDATE CASCADE,
    name        varchar(255)    NOT NULL,
    value       varchar(255)    NOT NULL,
    CONSTRAINT customercontactproperties_contactid_name_ukey UNIQUE (contactid, name)
    );
    CREATE INDEX customercontactproperties_name_idx ON customercontactproperties (name);
    CREATE INDEX customercontactproperties_value_idx ON customercontactproperties (value);
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2021020301', 'dbversion'));

$this->CommitTrans();
