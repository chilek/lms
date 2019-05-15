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
    CREATE SEQUENCE sourcefiles_id_seq;
    CREATE TABLE sourcefiles (
        id integer          DEFAULT nextval('sourcefiles_id_seq'::text) NOT NULL,
        userid integer     DEFAULT NULL
            REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
        name varchar(255)   NOT NULL,
        idate integer       NOT NULL,
        PRIMARY KEY (id),
        CONSTRAINT sourcefiles_idate_key UNIQUE (idate, name)
    );

    CREATE INDEX sourcefiles_userid_idx ON sourcefiles (userid);

    ALTER TABLE cashimport ADD sourcefileid integer DEFAULT NULL
        REFERENCES sourcefiles (id) ON DELETE SET NULL ON UPDATE CASCADE;

    ALTER TABLE cashimport ALTER customerid DROP NOT NULL;
    ALTER TABLE cashimport ALTER customerid SET DEFAULT NULL;
    UPDATE cashimport SET customerid = NULL WHERE customerid NOT IN (SELECT id FROM customers);
    ALTER TABLE cashimport ADD FOREIGN KEY (customerid)
        REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE;

    UPDATE cashimport SET sourceid = NULL WHERE sourceid NOT IN (SELECT id FROM cashsources);
    ALTER TABLE cashimport ADD FOREIGN KEY (sourceid)
        REFERENCES cashsources (id) ON DELETE SET NULL ON UPDATE CASCADE;

    CREATE INDEX cashimport_customerid_idx ON cashimport (customerid);
    CREATE INDEX cashimport_sourcefileid_idx ON cashimport (sourcefileid);
    CREATE INDEX cashimport_sourceid_idx ON cashimport (sourceid);
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2010122000', 'dbversion'));

$this->CommitTrans();
