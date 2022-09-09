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
    CREATE TABLE customercalls (
        id int(11) NOT NULL AUTO_INCREMENT,
        dt int(11) DEFAULT 0 NOT NULL,
        filename varchar(150) NOT NULL,
        outgoing tinyint(1) DEFAULT 0 NOT NULL,
        phone varchar(12) NOT NULL,
        duration int(11) DEFAULT 0 NOT NULL,
        PRIMARY KEY (id),
        KEY customercalls_dt_idx (dt),
        KEY customercalls_filename_idx (filename),
        KEY customercalls_phone_idx (phone)
    ) ENGINE=InnoDB
");

$this->Execute("
    CREATE TABLE customercallassignments (
        id int(11) NOT NULL AUTO_INCREMENT,
        customercallid int(11) NOT NULL,
        customerid int(11) NOT NULL,
        PRIMARY KEY (id),
        CONSTRAINT customercallassignments_customercallid_fkey
            FOREIGN KEY (customercallid) REFERENCES customercalls (id) ON UPDATE CASCADE ON DELETE CASCADE,
        CONSTRAINT customercallassignments_customerid_fkey
            FOREIGN KEY (customerid) REFERENCES customers (id) ON UPDATE CASCADE ON DELETE CASCADE,
        UNIQUE KEY customercallassignments_customercallid_ukey (customercallid, customerid)
    ) ENGINE=InnoDB
");
