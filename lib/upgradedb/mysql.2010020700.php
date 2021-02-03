<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
 *
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
    CREATE TABLE ewx_channels (
        id int(11)              NOT NULL auto_increment,
        name varchar(32)        DEFAULT '' NOT NULL,
        upceil int(11)          DEFAULT '0' NOT NULL,
        downceil int(11)        DEFAULT '0' NOT NULL,
        upceil_n int(11)        DEFAULT NULL,
        downceil_n int(11)      DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY name (name)
) ENGINE=InnoDB
");

$this->Execute("ALTER TABLE netdevices ADD channelid int(11) DEFAULT NULL");
$this->Execute("ALTER TABLE netdevices ADD INDEX channelid (channelid)");
$this->Execute("ALTER TABLE netdevices ADD FOREIGN KEY (channelid) REFERENCES ewx_channels (id) ON DELETE SET NULL ON UPDATE CASCADE");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2010020700', 'dbversion'));
