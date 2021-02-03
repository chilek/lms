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
CREATE TABLE promotions (
    id int(11)      NOT NULL auto_increment,
    name varchar(255) NOT NULL,
    description text DEFAULT NULL,
    disabled tinyint(1) DEFAULT '0' NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY name (name)
) ENGINE=InnoDB");

$this->Execute("
CREATE TABLE promotionschemas (
    id int(11)      NOT NULL auto_increment,
    name varchar(255) NOT NULL,
    description text DEFAULT NULL,
    data text DEFAULT NULL,
    promotionid int(11) DEFAULT NULL
        REFERENCES promotions (id) ON DELETE CASCADE ON UPDATE CASCADE,
    disabled tinyint(1) DEFAULT '0' NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY promotionid (promotionid, name)
) ENGINE=InnoDB");

$this->Execute("
CREATE TABLE promotionassignments (
    id int(11)      NOT NULL auto_increment,
    promotionschemaid int(11) DEFAULT NULL
        REFERENCES promotionschemas (id) ON DELETE CASCADE ON UPDATE CASCADE,
    tariffid int(11) DEFAULT NULL
        REFERENCES tariffs (id) ON DELETE CASCADE ON UPDATE CASCADE,
    data text DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY promotionschemaid (promotionschemaid, tariffid),
    INDEX tariffid (tariffid)
) ENGINE=InnoDB");

$this->Execute("ALTER TABLE tariffs DROP KEY name");
$this->Execute("ALTER TABLE tariffs ADD UNIQUE KEY name (name, value, period)");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2011022000', 'dbversion'));

$this->CommitTrans();
