<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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
    CREATE TABLE twofactorauthtrusteddevices (
        id int(11) NOT NULL auto_increment,
        userid int(11) NOT NULL,
        cookiename varchar(64) NOT NULL,
        useragent varchar(256) NOT NULL,
        ipaddr int(16) DEFAULT NULL,
        expires int(11) NOT NULL,
        PRIMARY KEY (id),
        CONSTRAINT twofactorauthtrusteddevices_userid_fkey
            FOREIGN KEY (userid) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2019091600', 'dbversion'));

$this->CommitTrans();
