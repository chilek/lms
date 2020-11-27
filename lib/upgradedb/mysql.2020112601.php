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
    CREATE TABLE netdevicetypes (
        id int(11) NOT NULL auto_increment,
        name varchar(50) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY netdevicetypes_name_ukey (name)
    ) ENGINE=InnoDB
");

$this->Execute("
    ALTER TABLE netdevicemodels ADD COLUMN type int(11) DEFAULT NULL
");

$this->Execute("
    ALTER TABLE netdevicemodels ADD CONSTRAINT netdevicemodels_type_fkey FOREIGN KEY (type) REFERENCES netdevicetypes (id) ON DELETE SET NULL ON UPDATE CASCADE
");

$this->Execute(
    "INSERT INTO netdevicetypes (name)
        VALUES (?), (?), (?), (?), (?), (?), (?), (?), (?), (?), (?)",
    array('router', 'switch', 'antenna', 'access-point', 'PON OLT', 'PON ONT', 'PON splitter', 'GSM modem', 'DSL modem', 'power line adapter', 'IPTV decoder')
);

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2020112601', 'dbversion'));

$this->CommitTrans();
