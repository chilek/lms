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
    CREATE TABLE vlans (
        id smallint NOT NULL auto_increment,
        vlanid smallint NOT NULL,
        description varchar(254) DEFAULT NULL,
        customerid int(11) DEFAULT NULL,
        PRIMARY KEY (id),
        CONSTRAINT vlans_customerid_fkey
            FOREIGN KEY (customerid) REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE,
        UNIQUE KEY vlans_ukey (vlanid, customerid)
    );
");

$this->Execute("INSERT INTO vlans (vlanid) SELECT DISTINCT vlanid FROM networks WHERE vlanid IS NOT NULL");

$this->Execute("UPDATE networks SET vlanid = (SELECT id FROM vlans WHERE vlans.vlanid = networks.vlanid)");

$this->Execute("ALTER TABLE networks ADD CONSTRAINT networks_vlanid_fkey
                    FOREIGN KEY (vlanid) REFERENCES vlans (id) ON DELETE SET NULL ON UPDATE CASCADE");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2020122200', 'dbversion'));

$this->CommitTrans();
