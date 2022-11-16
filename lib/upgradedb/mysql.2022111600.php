<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

if (!$this->ResourceExists('netranges', LMSDB::RESOURCE_TYPE_TABLE)) {
    $this->Execute("
        CREATE TABLE netranges (
            id int(11) NOT NULL auto_increment,
            buildingid int(11) NOT NULL
            linktype smallint NOT NULL,
            linktechnology smallint NOT NULL,
            downlink int(11) NOT NULL,
            uplink int(11) NOT NULL,
            type smallint NOT NULL,
            services smallint NOT NULL,
            PRIMARY KEY (id),
            CONSTRAINT netranges_buildingid_fkey
                FOREIGN KEY (buildingid) REFERENCES location_buildings (id) ON DELETE CASCADE ON UPDATE CASCADE,
            INDEX netranges_linktype_idx (linktype),
            INDEX netranges_linktechnology_idx (linktechnology),
            INDEX netranges_downlink_idx (downlink),
            INDEX netranges_uplink_idx (uplink),
            INDEX netranges_type_idx (type),
            INDEX netranges_services_idx (services)
        ) ENGINE=InnoDB;
    ");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2022111600', 'dbversion'));

$this->CommitTrans();
