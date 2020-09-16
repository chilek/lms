<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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
    CREATE SEQUENCE routednetworks_id_seq;
    CREATE TABLE routednetworks (
        id integer DEFAULT nextval('routednetworks_id_seq'::text) NOT NULL,
        nodeid integer NOT NULL
            CONSTRAINT routednetworks_nodeid_fkey REFERENCES nodes (id) ON DELETE CASCADE ON UPDATE CASCADE,
        netid integer NOT NULL
            CONSTRAINT routednetworks_netid_fkey REFERENCES networks (id) ON DELETE CASCADE ON UPDATE CASCADE,
        comment varchar(256) DEFAULT NULL,
        PRIMARY KEY (id),
        CONSTRAINT routednetworks_netid_key UNIQUE (netid)
    )
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2020091600', 'dbversion'));

$this->CommitTrans();
