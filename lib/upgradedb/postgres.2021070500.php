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


$this->Execute("
    CREATE SEQUENCE customercalls_id_seq;
    CREATE TABLE customercalls (
        id integer DEFAULT nextval('customercalls_id_seq'::text) NOT NULL,
        dt integer DEFAULT 0 NOT NULL,
        filename varchar(150) NOT NULL,
        outgoing smallint DEFAULT 0 NOT NULL,
        phone varchar(12) NOT NULL,
        duration integer DEFAULT 0 NOT NULL,
        PRIMARY KEY (id)
    );
    CREATE INDEX customercalls_dt_idx ON customercalls (dt);
    CREATE INDEX customercalls_filename_idx ON customercalls (filename);
    CREATE INDEX customercalls_phone_idx ON customercalls (phone);

    CREATE SEQUENCE customercallassignments_id_seq;
    CREATE TABLE customercallassignments (
        id integer DEFAULT nextval('customercallassignments_id_seq'::text) NOT NULL,
        customercallid integer NOT NULL
            CONSTRAINT customercallassignments_customercallid_fkey REFERENCES customercalls (id) ON UPDATE CASCADE ON DELETE CASCADE,
        customerid integer NOT NULL
            CONSTRAINT customercallassignments_customerid_fkey REFERENCES customers (id) ON UPDATE CASCADE ON DELETE CASCADE,
        PRIMARY KEY (id),
        CONSTRAINT customercallassignments_customercallid_ukey
            UNIQUE (customercallid, customerid)
    )
");
