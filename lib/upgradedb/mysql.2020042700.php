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

$this->Execute("ALTER TABLE netnodes MODIFY COLUMN admcontact text DEFAULT NULL");
$this->Execute("ALTER TABLE messageitems MODIFY COLUMN externalmsgid int(11) DEFAULT 0 NOT NULL");
$this->Execute("ALTER TABLE location_buildings MODIFY COLUMN updated smallint DEFAULT 0");
$this->Execute("ALTER TABLE voip_prefixes MODIFY COLUMN groupid int(11) NOT NULL");
$this->Execute("ALTER TABLE rttickets MODIFY COLUMN source tinyint(4) NOT NULL DEFAULT 0");
if (!$this->ResourceExists('voip_numbers.tariff_id', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE voip_numbers ADD COLUMN tariff_id int(11) NULL");
    $this->Execute("ALTER TABLE voip_numbers ADD CONSTRAINT voip_numbers_tariff_id_fkey FOREIGN KEY (tariff_id) REFERENCES tariffs (id) ON DELETE SET NULL ON UPDATE CASCADE");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2020042700', 'dbversion'));

$this->CommitTrans();
