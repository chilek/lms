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

$this->Execute("ALTER TABLE netnodes ALTER COLUMN admcontact TYPE text");
$this->Execute("ALTER TABLE messageitems ALTER COLUMN externalmsgid TYPE integer");
$this->Execute("ALTER TABLE location_buildings ALTER COLUMN updated TYPE smallint");
$this->Execute("ALTER TABLE voip_prefixes ALTER COLUMN groupid TYPE integer");
$this->Execute("ALTER TABLE rttickets ALTER COLUMN source TYPE smallint");
if (!$this->ResourceExists('voip_numbers.tariff_id', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE voip_numbers ADD COLUMN tariff_id integer NULL");
    $this->Execute("ALTER TABLE voip_numbers ADD CONSTRAINT voip_numbers_tariff_id_fkey FOREIGN KEY (tariff_id) REFERENCES tariffs (id) ON DELETE SET NULL ON UPDATE CASCADE");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2020042700', 'dbversion'));

$this->CommitTrans();
