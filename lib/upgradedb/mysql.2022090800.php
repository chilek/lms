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


if ($this->ResourceExists('voip_cdr_type_uniqueid_ukey', LMSDB::RESOURCE_TYPE_CONSTRAINT)) {
    $this->Execute("ALTER TABLE voip_cdr DROP CONSTRAINT voip_cdr_type_uniqueid_ukey");
}

if (!$this->ResourceExists('voip_cdr.direction', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE voip_cdr ADD COLUMN direction smallint");
    $this->Execute("UPDATE voip_cdr SET direction = type");
    $this->Execute("ALTER TABLE voip_cdr MODIFY COLUMN direction smallint NOT NULL");
    $this->Execute("CREATE INDEX voip_cdr_direction_idx ON voip_cdr (direction)");
    $this->Execute("ALTER TABLE voip_cdr MODIFY COLUMN type smallint NOT NULL DEFAULT 0");
    $this->Execute("UPDATE voip_cdr SET type = 0");
    $this->Execute("DROP INDEX voip_cdr_subtype_idx ON voip_cdr");
    $this->Execute("ALTER TABLE voip_cdr CHANGE COLUMN subtype incremental smallint NOT NULL DEFAULT 0");
}
