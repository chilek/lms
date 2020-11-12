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
    CREATE TABLE location_city_types (
        id int(11) NOT NULL auto_increment,
        ident varchar(8) NOT NULL,
        name varchar(64) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY location_city_types_name_ukey (name)
    ) ENGINE=INNODB
");

$this->Execute("
    ALTER TABLE location_cities ADD COLUMN type int(11) DEFAULT NULL
");

$this->Execute("
    ALTER TABLE location_cities ADD CONSTRAINT location_cities_type_fkey
        FOREIGN KEY (type) REFERENCES location_city_types (id) ON DELETE CASCADE ON UPDATE CASCADE
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2020092100', 'dbversion'));

$this->CommitTrans();
