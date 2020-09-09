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

if ($this->ResourceExists('tariff_rule_id_fk', LMSDB::RESOURCE_TYPE_CONSTRAINT)) {
    $this->Execute("ALTER TABLE tariffs DROP CONSTRAINT tariff_rule_id_fk");
}
if ($this->ResourceExists('tariffs_voip_tariff_rule_id_fkey', LMSDB::RESOURCE_TYPE_CONSTRAINT)) {
    $this->Execute("ALTER TABLE tariffs DROP CONSTRAINT tariffs_voip_tariff_rule_id_fkey");
}

$this->Execute(
    "ALTER TABLE tariffs ADD CONSTRAINT tariffs_voip_tariff_rule_id_fkey
    FOREIGN KEY (voip_tariff_rule_id) REFERENCES voip_rule_groups (id) ON UPDATE CASCADE ON DELETE SET NULL"
);

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2020082600', 'dbversion'));

$this->CommitTrans();
